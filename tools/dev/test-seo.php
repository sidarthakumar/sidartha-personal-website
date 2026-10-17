<?php
/** Database-free regression harness for the actual theme wp_head callback.
 * Run: php tools/dev/test-seo.php [WPSEO_VERSION|RANK_MATH_VERSION|AIOSEO_VERSION]
 * WordPress query/content APIs are doubles; this does not replace native integration.
 */
define( 'ABSPATH', __DIR__ . '/' );
if ( isset( $argv[1] ) ) {
	if ( ! in_array( $argv[1], array( 'WPSEO_VERSION', 'RANK_MATH_VERSION', 'AIOSEO_VERSION' ), true ) ) { exit( 1 ); }
	define( $argv[1], 'test' );
}
$actions = array();
$state = array();
class WP_Post {
	public $post_type = 'page';
	public $post_status = 'publish';
	public $post_password = '';
	public $post_excerpt = '';
	public $post_content = '';
}
function add_action( $hook, $callback, $priority = 10 ) { $GLOBALS['actions'][ $hook ][] = $callback; }
function is_front_page() { return ! empty( $GLOBALS['state']['front'] ); }
function is_home() { return ! empty( $GLOBALS['state']['home'] ); }
function is_singular() { return ! empty( $GLOBALS['state']['singular'] ); }
function is_search() { return ! empty( $GLOBALS['state']['search'] ); }
function is_404() { return ! empty( $GLOBALS['state']['404'] ); }
function get_option( $key ) { return 'page_for_posts' === $key ? ( $GLOBALS['state']['page_id'] ?? 12 ) : array(); }
function get_post( $id ) {
	if ( $id < 1 ) { throw new RuntimeException( 'Must not read global post for an unset Posts page' ); }
	return $GLOBALS['state']['page'] ?? null;
}
function get_queried_object_id() { return 42; }
function get_the_excerpt( $id ) { return 'Existing singular description'; }
function get_bloginfo( $key ) { return 'description' === $key ? 'Existing homepage introduction' : 'Sidartha Kumar'; }
function __( $text, $domain ) { return $text; }
function wp_strip_all_tags( $text ) { return trim( preg_replace( '@<(script|style)[^>]*?>.*?</\1>@si', '', strip_tags( $text ) ) ); }
function strip_shortcodes( $text ) { return preg_replace( '/\[\/?example[^\]]*\]/', '', $text ); }
function wp_trim_words( $text, $count, $more ) {
	$words = preg_split( '/\s+/', trim( $text ) );
	return implode( ' ', array_slice( $words, 0, $count ) ) . ( count( $words ) > $count ? $more : '' );
}
function esc_attr( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function home_url( $path ) { return 'http://127.0.0.1:8080' . $path; }
function wp_json_encode( $data, $flags ) { return json_encode( $data, $flags ); }
require dirname( __DIR__, 2 ) . '/wp-content/themes/sidartha/functions.php';
function render_seo( $state ) {
	$GLOBALS['state'] = $state;
	ob_start();
	foreach ( $GLOBALS['actions']['wp_head'] as $callback ) { $callback(); }
	return ob_get_clean();
}
function check_seo( $condition, $label ) {
	if ( ! $condition ) { fwrite( STDERR, 'FAIL: ' . $label . "\n" ); exit( 1 ); }
	echo 'PASS: ' . $label . "\n";
}
$page = new WP_Post();
$page->post_excerpt = 'Insights "quoted" & <b>practical learning</b>';
$page->post_content = 'Content should not override the excerpt';
$home = array( 'home' => true, 'page' => $page );
if ( isset( $argv[1] ) ) {
	check_seo( '' === render_seo( $home ) && '' === render_seo( array( 'front' => true ) ) && '' === render_seo( array( 'singular' => true ) ), 'SEO plugin owns all metadata: ' . $argv[1] );
	exit( 0 );
}
$output = render_seo( $home );
check_seo( 1 === substr_count( $output, '<meta name="description"' ) && false !== strpos( $output, 'Insights &quot;quoted&quot; &amp; practical learning' ), 'Posts page excerpt wins; one escaped description' );
check_seo( false === strpos( $output, 'canonical' ) && false === strpos( $output, 'robots' ) && false === strpos( $output, 'ld+json' ), 'Posts index emits no unrelated SEO directives or schema' );
$page->post_excerpt = ' <b> </b> [example] ';
$page->post_content = '<p>Content-based insights</p> [example]';
check_seo( false !== strpos( render_seo( $home ), 'content="Content-based insights"' ), 'Cleaned empty excerpt falls back to stripped content' );
$page->post_content = '';
$fallback = 'Explore Sidartha Kumar&#039;s insights on software development, AI, cloud technology, and learning through practical projects and ongoing experiments.';
check_seo( false !== strpos( render_seo( $home ), $fallback ), 'Empty Posts page uses requested fallback' );
check_seo( false !== strpos( render_seo( array( 'home' => true, 'page_id' => 0 ) ), $fallback ), 'Unset Posts page never uses the current loop post' );
check_seo( false !== strpos( render_seo( array( 'home' => true ) ), $fallback ), 'Missing Posts page uses fallback' );
$page->post_content = 'Private text must not leak';
foreach ( array( 'draft', 'private' ) as $status ) {
	$page->post_status = $status;
	check_seo( false !== strpos( render_seo( $home ), $fallback ), 'Unpublished page content is not exposed: ' . $status );
}
$page->post_status = 'publish';
$page->post_password = 'synthetic-fixture-only';
check_seo( false !== strpos( render_seo( $home ), $fallback ), 'Password-protected page uses public fallback' );
$page->post_password = '';
$page->post_excerpt = implode( ' ', array_fill( 0, 35, 'word' ) );
check_seo( false !== strpos( render_seo( $home ), implode( ' ', array_fill( 0, 30, 'word' ) ) . '…' ), 'Existing 30-word limit is retained' );
foreach ( array( array( 'front' => true ), array( 'front' => true, 'home' => true ) ) as $front ) {
	$output = render_seo( $front );
	check_seo( 1 === substr_count( $output, '<meta name="description"' ) && false !== strpos( $output, 'Existing homepage introduction' ) && false !== strpos( $output, 'application/ld+json' ), 'Homepage introduction and Person schema remain unchanged' );
}
check_seo( false !== strpos( render_seo( array( 'singular' => true ) ), 'Existing singular description' ), 'Existing page and individual post excerpt behavior is unchanged' );
foreach ( array( 'search', '404', 'archive' ) as $context ) {
	check_seo( '' === render_seo( array( $context => true ) ), 'No theme metadata on ' . $context );
}

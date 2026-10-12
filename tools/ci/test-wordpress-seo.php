<?php
/** Native WordPress SEO checks, exclusively in the disposable Actions fixture. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type() || 'http://127.0.0.1:8080' !== get_option( 'home' ) || ! defined( 'WP_HTTP_BLOCK_EXTERNAL' ) || ! WP_HTTP_BLOCK_EXTERNAL ) { exit( 1 ); }
if ( 'ci-admin' !== wp_get_current_user()->user_login || 'ci@example.invalid' !== get_option( 'admin_email' ) ) { exit( 1 ); }
function ci_seo_check( $condition, $label ) {
	if ( ! $condition ) { WP_CLI::error( 'FAIL: ' . $label ); }
	WP_CLI::log( 'PASS: ' . $label );
}
// Call the actual registered theme callback with real WordPress queries/APIs.
function ci_seo_render( $query ) {
	$GLOBALS['wp_query'] = new WP_Query( $query );
	$callback = null;
	foreach ( $GLOBALS['wp_filter']['wp_head']->callbacks[5] as $entry ) {
		if ( $entry['function'] instanceof Closure ) {
			$reflection = new ReflectionFunction( $entry['function'] );
			if ( get_template_directory() . '/functions.php' === $reflection->getFileName() ) { $callback = $entry['function']; }
		}
	}
	ci_seo_check( null !== $callback, 'native theme SEO callback registered' );
	ob_start();
	$callback();
	return ob_get_clean();
}
$pages = get_option( 'skp_pages' );
$insights = $pages['insights'];
$blog_query = array( 'post_type' => 'post' );
if ( ! empty( $args[0] ) ) {
	ci_seo_check( in_array( $args[0], array( 'WPSEO_VERSION', 'RANK_MATH_VERSION', 'AIOSEO_VERSION' ), true ), 'allowlisted SEO plugin mode' );
	define( $args[0], 'ci-fixture' );
	ci_seo_check( '' === ci_seo_render( $blog_query ) && '' === ci_seo_render( array( 'page_id' => $pages['home'] ) ), 'native SEO plugin detection suppresses theme metadata' );
	WP_CLI::success( 'Native SEO plugin ownership checks passed.' );
	return;
}
$output = ci_seo_render( $blog_query );
ci_seo_check( is_home() && ! is_front_page(), 'configured Insights page is the real WordPress posts index' );
ci_seo_check( 1 === substr_count( $output, '<meta name="description"' ) && false !== strpos( $output, 'Thoughts on development' ), 'native Insights content produces exactly one description' );
wp_update_post( array( 'ID' => $insights, 'post_excerpt' => 'Insights "quoted" & <b>practical learning</b>' ) );
$output = ci_seo_render( $blog_query );
ci_seo_check( false !== strpos( $output, 'Insights &quot;quoted&quot; &amp; practical learning' ), 'native excerpt precedence and attribute escaping' );
wp_update_post( array( 'ID' => $insights, 'post_excerpt' => '', 'post_content' => '' ) );
$output = ci_seo_render( $blog_query );
ci_seo_check( false !== strpos( $output, 'Explore Sidartha Kumar&#039;s insights' ), 'native empty content uses public fallback' );
wp_update_post( array( 'ID' => $insights, 'post_status' => 'draft', 'post_content' => 'Private synthetic text' ) );
$output = ci_seo_render( $blog_query );
ci_seo_check( false === strpos( $output, 'Private synthetic text' ) && false !== strpos( $output, 'Explore Sidartha' ), 'native draft content never leaks into metadata' );
$front = ci_seo_render( array( 'page_id' => $pages['home'] ) );
ci_seo_check( false !== strpos( $front, esc_attr( wp_trim_words( wp_strip_all_tags( skp_profile( 'introduction' ) ), 30, '…' ) ) ) && false !== strpos( $front, 'application/ld+json' ), 'native homepage description and Person schema preserved' );
$about = ci_seo_render( array( 'page_id' => $pages['about'] ) );
ci_seo_check( false !== strpos( $about, esc_attr( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $pages['about'] ) ), 30, '…' ) ) ), 'native other-page description preserved' );
$post = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Synthetic CI note', 'post_excerpt' => 'Existing individual post excerpt' ), true );
ci_seo_check( ! is_wp_error( $post ), 'synthetic single-post fixture created' );
ci_seo_check( false !== strpos( ci_seo_render( array( 'p' => $post ) ), 'Existing individual post excerpt' ), 'native single-post description preserved' );
ci_seo_check( '' === ci_seo_render( array( 's' => 'synthetic' ) ), 'native search exclusion preserved' );
WP_CLI::success( 'Native WordPress SEO checks passed.' );

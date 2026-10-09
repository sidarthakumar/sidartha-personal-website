<?php
/** Run via tools/dev/check-website.py inside the local sandbox. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type() || ! current_user_can( 'manage_options' ) ) {
	exit( 1 );
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
global $checks;
$checks = 0;
function skp_test( $condition, $label ) {
	global $checks;
	if ( ! $condition ) { WP_CLI::error( 'FAIL: ' . $label ); }
	++$checks;
	WP_CLI::log( 'PASS: ' . $label );
}

$administrator = get_current_user_id();
skp_test( 'sidartha' === get_stylesheet(), 'custom theme active' );
skp_test( is_plugin_active( 'sidartha-portfolio/sidartha-portfolio.php' ), 'companion plugin active' );
$pages = get_option( 'skp_pages', array() );
foreach ( array( 'home', 'about', 'skills', 'projects', 'journey', 'insights', 'contact' ) as $role ) {
	skp_test( isset( $pages[ $role ] ) && 'publish' === get_post_status( $pages[ $role ] ), $role . ' page exists and is published locally' );
}
skp_test( 'draft' === get_post_status( $pages['privacy'] ), 'privacy policy remains an owner-review draft' );
$before_map = get_option( 'skp_starter_map' );
global $wpdb;
$before_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" );
\Sidartha\Portfolio\seed_content( true );
skp_test( $before_map === get_option( 'skp_starter_map' ) && $before_count === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" ), 'starter import is idempotent and adds no duplicate posts' );

$fixture = (int) get_option( 'skp_qa_fixture' );
if ( ! $fixture || ! get_post( $fixture ) ) {
	$fixture = wp_insert_post( array( 'post_type' => 'sk_project', 'post_status' => 'draft', 'post_title' => 'Development QA fixture — unpublished', 'post_content' => '<p>Non-public fixture for editing and authorization checks. It is not a portfolio project.</p>', 'post_author' => $administrator ), true );
	if ( is_wp_error( $fixture ) ) { WP_CLI::error( 'FAIL: native content creation' ); }
	update_option( 'skp_qa_fixture', $fixture, false );
}
skp_test( 'sk_project' === get_post_type( $fixture ), 'native content creation fixture exists' );
$edit = wp_update_post( array( 'ID' => $fixture, 'post_excerpt' => 'Edited using the native WordPress content API.' ), true );
skp_test( ! is_wp_error( $edit ) && 'Edited using the native WordPress content API.' === get_post_field( 'post_excerpt', $fixture ), 'native content editing persists' );
update_post_meta( $fixture, 'sk_status', 'planned' );
$_POST = array( 'skp_details_nonce' => 'invalid', 'sk_status' => 'completed' );
\Sidartha\Portfolio\save_meta( $fixture );
skp_test( 'planned' === get_post_meta( $fixture, 'sk_status', true ), 'invalid nonce cannot update project details' );
$_POST = array( 'sk_status' => 'completed' );
\Sidartha\Portfolio\save_meta( $fixture );
skp_test( 'planned' === get_post_meta( $fixture, 'sk_status', true ), 'missing nonce cannot update project details' );
wp_set_current_user( 0 );
$_POST = array( 'skp_details_nonce' => wp_create_nonce( 'skp_save_details' ), 'sk_status' => 'completed' );
\Sidartha\Portfolio\save_meta( $fixture );
skp_test( 'planned' === get_post_meta( $fixture, 'sk_status', true ), 'nonce alone cannot authorize anonymous editing' );
skp_test( is_wp_error( \Sidartha\Portfolio\seed_content( true ) ), 'anonymous starter import rejected' );
$request = new WP_REST_Request( 'POST', '/wp/v2/sk_project/' . $fixture );
$request->set_param( 'title', 'Unauthorized change' );
$response = rest_do_request( $request );
skp_test( in_array( $response->get_status(), array( 401, 403 ), true ), 'anonymous REST update rejected' );
$request = new WP_REST_Request( 'POST', '/wp/v2/sk_project' );
$request->set_param( 'title', 'Unauthorized creation' );
skp_test( in_array( rest_do_request( $request )->get_status(), array( 401, 403 ), true ), 'anonymous REST creation rejected' );
$request = new WP_REST_Request( 'GET', '/wp/v2/sk_project/' . $fixture );
skp_test( in_array( rest_do_request( $request )->get_status(), array( 401, 403, 404 ), true ), 'anonymous draft REST read rejected' );
$request = new WP_REST_Request( 'GET', '/wp/v2/pages/' . $pages['privacy'] );
skp_test( in_array( rest_do_request( $request )->get_status(), array( 401, 403, 404 ), true ), 'privacy draft rejected through REST' );
$request = new WP_REST_Request( 'GET', '/wp/v2/sk_project/' . $before_map['project_website'] );
$public_project = rest_do_request( $request )->get_data();
skp_test( ! array_key_exists( 'sk_evidence', $public_project['meta'] ?? array() ), 'verification notes are excluded from public REST responses' );

wp_set_current_user( $administrator );
// Simulate an authenticated read-only principal in memory; do not alter any account.
wp_get_current_user()->allcaps = array( 'read' => true );
$_POST = array( 'skp_details_nonce' => wp_create_nonce( 'skp_save_details' ), 'sk_status' => 'completed' );
\Sidartha\Portfolio\save_meta( $fixture );
skp_test( 'planned' === get_post_meta( $fixture, 'sk_status', true ), 'authenticated read-only principal cannot edit with a valid nonce' );
skp_test( is_wp_error( \Sidartha\Portfolio\seed_content( true ) ), 'authenticated read-only principal cannot import starter content' );
$request = new WP_REST_Request( 'POST', '/wp/v2/sk_project/' . $fixture );
$request->set_param( 'title', 'Unauthorized change' );
skp_test( 403 === rest_do_request( $request )->get_status(), 'authenticated read-only REST edit rejected' );
wp_set_current_user( 0 );
wp_set_current_user( $administrator );
$_POST = array( 'skp_details_nonce' => wp_create_nonce( 'skp_save_details' ), 'sk_status' => 'in-progress', 'sk_repository' => 'javascript:alert(1)', 'sk_demo' => 'https://user:password@example.invalid/demo' );
\Sidartha\Portfolio\save_meta( $fixture );
skp_test( 'in-progress' === get_post_meta( $fixture, 'sk_status', true ), 'valid nonce plus capability updates metadata' );
skp_test( '' === get_post_meta( $fixture, 'sk_repository', true ) && '' === get_post_meta( $fixture, 'sk_demo', true ), 'unsafe schemes and credential-bearing URLs rejected' );
$fields = \Sidartha\Portfolio\meta_fields();
skp_test( '' === \Sidartha\Portfolio\sanitize_field( '2026-02-30', $fields['sk_milestone']['sk_date'] ), 'invalid calendar date rejected' );
skp_test( '' === \Sidartha\Portfolio\sanitize_field( "2026-10-09\0", $fields['sk_milestone']['sk_date'] ), 'null-byte date rejected without a parser exception' );
skp_test( '2026-10-09' === \Sidartha\Portfolio\sanitize_field( '2026-10-09', $fields['sk_milestone']['sk_date'] ), 'valid milestone date accepted' );
$sanitized = \Sidartha\Portfolio\sanitize_profile( array( 'name' => '<img src=x onerror=alert(1)>Example', 'public_email' => get_option( 'admin_email' ), 'github' => 'javascript:alert(1)', 'photo' => array( 'invalid' ), 'article_category' => array( 'invalid' ) ) );
skp_test( false === strpos( $sanitized['name'], '<' ) && '' === $sanitized['github'], 'profile HTML/script input sanitized' );
skp_test( '' === $sanitized['public_email'], 'administrator email cannot become public contact address' );
skp_test( 0 === $sanitized['photo'] && 0 === $sanitized['article_category'], 'malformed media/category fields rejected' );
$valid_public = \Sidartha\Portfolio\sanitize_profile( array( 'public_email' => 'hello@example.test', 'github' => 'https://example.test/work' ) );
skp_test( 'hello@example.test' === $valid_public['public_email'] && 'https://example.test/work' === $valid_public['github'], 'owner-supplied public contact/link values accepted without sending email' );

$request = new WP_REST_Request( 'POST', '/wp/v2/sk_project/' . $fixture );
$request->set_param( 'meta', array( 'sk_status' => 'invented-status' ) );
skp_test( 400 === rest_do_request( $request )->get_status(), 'REST schema rejects invalid project status' );
$request->set_param( 'meta', array( 'sk_status' => 'in-progress' ) );
skp_test( 200 === rest_do_request( $request )->get_status(), 'authorized native REST editing succeeds' );
update_post_meta( $fixture, 'sk_status', 'completed' );
update_post_meta( $fixture, 'sk_evidence', '' );
$query = new WP_Query( array( 'p' => $fixture, 'post_type' => 'sk_project', 'post_status' => 'draft' ) );
$query->the_post();
ob_start(); skp_project_card(); $card = ob_get_clean();
skp_test( false === strpos( $card, 'status-completed' ) && false !== strpos( $card, 'In Progress' ), 'unverified completion is not presented as Completed' );
wp_reset_postdata();
$filter_args = array( 'p' => $fixture, 'post_type' => 'sk_project', 'post_status' => 'draft' );
$filtered = new WP_Query( array_merge( $filter_args, array( 'meta_query' => skp_project_status_query( 'in-progress' ) ) ) );
skp_test( 1 === $filtered->post_count, 'unverified completion remains discoverable under In Progress' );
$filtered = new WP_Query( array_merge( $filter_args, array( 'meta_query' => skp_project_status_query( 'completed' ) ) ) );
skp_test( 0 === $filtered->post_count, 'unverified completion is excluded from Completed results' );
update_post_meta( $fixture, 'sk_status', 'planned' );
$filtered = new WP_Query( array_merge( $filter_args, array( 'meta_query' => skp_project_status_query( 'planned' ) ) ) );
skp_test( 1 === $filtered->post_count, 'Planned filter discovers planned project content' );
$_POST = array();
skp_test( 'draft' === get_post_status( $fixture ), 'QA fixture remains unpublished; no existing content removed' );
WP_CLI::success( $checks . ' website integration/security checks passed.' );

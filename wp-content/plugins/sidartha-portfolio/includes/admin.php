<?php
namespace Sidartha\Portfolio;

defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', function () {
	register_setting( 'skp_profile_group', 'skp_profile', array( 'type' => 'array', 'sanitize_callback' => __NAMESPACE__ . '\\sanitize_profile', 'show_in_rest' => false ) );
} );
add_action( 'admin_menu', function () {
	add_options_page( __( 'Portfolio profile', 'sidartha-portfolio' ), __( 'Portfolio profile', 'sidartha-portfolio' ), 'manage_options', 'skp-profile', __NAMESPACE__ . '\\settings_page' );
} );
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'settings_page_skp-profile' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'skp-admin', plugins_url( '../assets/admin.js', __FILE__ ), array( 'jquery', 'media-views' ), VERSION, true );
} );

function settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap"><h1><?php esc_html_e( 'Portfolio profile', 'sidartha-portfolio' ); ?></h1>
	<p><?php esc_html_e( 'Public information only. Never enter passwords, private repository URLs, or your administrator email.', 'sidartha-portfolio' ); ?></p>
	<?php settings_errors( 'skp_profile' ); ?>
	<form action="options.php" method="post">
		<?php settings_fields( 'skp_profile_group' ); ?>
		<table class="form-table" role="presentation">
		<?php
		$fields = array( 'name' => 'Public name', 'eyebrow' => 'Introduction label', 'headline' => 'Homepage headline (two lines supported)', 'introduction' => 'Homepage introduction', 'focus_label' => 'Homepage learning section label', 'focus_heading' => 'Homepage learning section heading', 'focus_introduction' => 'Homepage learning section introduction', 'public_email' => 'Public contact email (optional)', 'github' => 'Public GitHub profile URL (optional)', 'linkedin' => 'Public LinkedIn URL (optional)', 'footer' => 'Footer sentence' );
		foreach ( $fields as $key => $label ) : ?>
			<tr><th scope="row"><label for="skp-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th><td>
			<?php if ( in_array( $key, array( 'headline', 'introduction', 'focus_introduction', 'footer' ), true ) ) : ?>
				<textarea class="large-text" rows="3" id="skp-<?php echo esc_attr( $key ); ?>" name="skp_profile[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( profile( $key ) ); ?></textarea>
			<?php else : ?>
				<input class="regular-text" id="skp-<?php echo esc_attr( $key ); ?>" name="skp_profile[<?php echo esc_attr( $key ); ?>]" type="<?php echo esc_attr( 'public_email' === $key ? 'email' : ( in_array( $key, array( 'github', 'linkedin' ), true ) ? 'url' : 'text' ) ); ?>" value="<?php echo esc_attr( profile( $key ) ); ?>">
			<?php endif; ?></td></tr>
		<?php endforeach; ?>
		<tr><th scope="row"><label for="skp-photo">Profile photograph</label></th><td>
			<input type="hidden" id="skp-photo" name="skp_profile[photo]" value="<?php echo esc_attr( profile( 'photo' ) ); ?>">
			<button type="button" class="button" id="skp-select-photo">Choose from media library</button>
			<button type="button" class="button" id="skp-clear-photo">Use monogram</button>
			<p id="skp-photo-status" aria-live="polite"><?php echo profile( 'photo' ) ? esc_html__( 'A photograph is selected. Add meaningful alt text in the media library.', 'sidartha-portfolio' ) : esc_html__( 'The theme will display a monogram. No photograph has been invented.', 'sidartha-portfolio' ); ?></p>
		</td></tr>
		<tr><th scope="row"><label for="skp-article-category">Articles collection</label></th><td>
		<?php wp_dropdown_categories( array( 'name' => 'skp_profile[article_category]', 'id' => 'skp-article-category', 'selected' => profile( 'article_category' ), 'hide_empty' => false, 'show_option_all' => 'All published posts', 'hierarchical' => true ) ); ?>
		<p class="description">Choose a category and its children for the Insights index and homepage. Existing posts remain intact.</p></td></tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<hr><h2>Starter content</h2>
	<p>Create editable pages, learning focuses, an in-progress first project, two article drafts, and a privacy draft. Existing content is never overwritten. On a public installation, all new content starts as drafts for your review.</p>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="skp_starter">
		<?php wp_nonce_field( 'skp_starter' ); ?>
		<?php submit_button( __( 'Create starter drafts', 'sidartha-portfolio' ), 'secondary', 'submit', false ); ?>
	</form></div>
	<?php
}

add_action( 'admin_post_skp_starter', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to create starter content.', 'sidartha-portfolio' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'skp_starter' );
	$result = seed_content( false );
	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 500 ) );
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=page' ) );
	exit;
} );

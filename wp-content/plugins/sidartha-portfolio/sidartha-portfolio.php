<?php
/**
 * Plugin Name: Sidartha Portfolio
 * Description: Portable projects, learning milestones, skills, and public profile settings.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: Sidartha Kumar
 * License: GPL-2.0-or-later
 * Text Domain: sidartha-portfolio
 */

namespace Sidartha\Portfolio;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.0.0';

/** Register native content types. WordPress supplies REST authorization and editing. */
function register_content() {
	$types = array(
		'sk_project'   => array( 'Projects', 'Project', 'dashicons-portfolio', true ),
		'sk_milestone' => array( 'Learning Journey', 'Milestone', 'dashicons-learn-more', false ),
		'sk_skill'     => array( 'Skills & Technologies', 'Skill group', 'dashicons-editor-code', false ),
	);
	foreach ( $types as $type => $labels ) {
		register_post_type(
			$type,
			array(
				'labels'             => array( 'name' => $labels[0], 'singular_name' => $labels[1], 'add_new_item' => 'Add ' . $labels[1], 'edit_item' => 'Edit ' . $labels[1] ),
				'public'             => $labels[3],
				'show_ui'            => true,
				'show_in_rest'       => true,
				'has_archive'        => false,
				'rewrite'            => $labels[3] ? array( 'slug' => 'project', 'with_front' => false ) : false,
				'menu_icon'          => $labels[2],
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'custom-fields', 'page-attributes' ),
			)
		);
	}
	register_taxonomy( 'sk_technology', 'sk_project', array( 'label' => __( 'Technologies', 'sidartha-portfolio' ), 'public' => false, 'show_ui' => true, 'show_in_rest' => true, 'hierarchical' => false, 'rewrite' => false ) );
	foreach ( meta_fields() as $type => $fields ) {
		foreach ( $fields as $key => $field ) {
			$schema = array( 'type' => 'string' );
			if ( 'sk_evidence' === $key ) {
				$schema['context'] = array( 'edit' );
			}
			if ( isset( $field['options'] ) ) {
				$schema['enum'] = array_keys( $field['options'] );
			}
			register_post_meta( $type, $key, array(
				'type'              => 'string',
				'single'            => true,
				'default'           => $field['default'] ?? '',
				'show_in_rest'      => array( 'schema' => $schema ),
				'sanitize_callback' => function ( $value ) use ( $field ) { return sanitize_field( $value, $field ); },
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) { return current_user_can( 'edit_post', $post_id ); },
			) );
		}
	}
}
add_action( 'init', __NAMESPACE__ . '\\register_content' );

function meta_fields() {
	return array(
		'sk_project' => array(
			'sk_status' => array( 'label' => 'Status', 'options' => array( 'planned' => 'Planned', 'in-progress' => 'In Progress', 'completed' => 'Completed' ), 'default' => 'planned' ),
			'sk_repository' => array( 'label' => 'Public source URL (only if supplied)', 'type' => 'url' ),
			'sk_demo' => array( 'label' => 'Public demo URL (only if supplied)', 'type' => 'url' ),
			'sk_evidence' => array( 'label' => 'Verification note — describe evidence before marking Completed', 'type' => 'textarea' ),
		),
		'sk_milestone' => array(
			'sk_date' => array( 'label' => 'Date (YYYY-MM-DD; leave empty if not known)', 'type' => 'date' ),
			'sk_kind' => array( 'label' => 'Kind', 'options' => array( 'learning' => 'Learning', 'building' => 'Building', 'research' => 'Research', 'milestone' => 'Milestone' ), 'default' => 'learning' ),
		),
		'sk_skill' => array(
			'sk_focus' => array( 'label' => 'Stage', 'options' => array( 'learning' => 'Learning focus', 'developing' => 'Developing through practice', 'demonstrated' => 'Demonstrated in linked work' ), 'default' => 'learning' ),
			'sk_topics' => array( 'label' => 'Topics (comma-separated; no proficiency scores)', 'type' => 'textarea' ),
			'sk_work' => array( 'label' => 'Evidence URL for demonstrated work (optional)', 'type' => 'url' ),
		),
	);
}

/** Validate scalar fields; never store executable schemes or malformed dates. */
function sanitize_field( $value, $field ) {
	if ( ! is_string( $value ) ) {
		return $field['default'] ?? '';
	}
	if ( isset( $field['options'] ) ) {
		return isset( $field['options'][ $value ] ) ? $value : $field['default'];
	}
	if ( 'url' === ( $field['type'] ?? '' ) ) {
		$parts = wp_parse_url( $value );
		return is_array( $parts ) && ! empty( $parts['host'] ) && ! isset( $parts['user'] ) && ! isset( $parts['pass'] ) && in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true ) ? esc_url_raw( $value, array( 'http', 'https' ) ) : '';
	}
	if ( 'date' === ( $field['type'] ?? '' ) ) {
		if ( ! preg_match( '/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $value ) ) {
			return '';
		}
		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		return $date && $date->format( 'Y-m-d' ) === $value ? $value : '';
	}
	return substr( sanitize_textarea_field( $value ), 0, 2000 );
}

function add_meta_boxes() {
	foreach ( array_keys( meta_fields() ) as $type ) {
		add_meta_box( 'skp_details', __( 'Portfolio details', 'sidartha-portfolio' ), __NAMESPACE__ . '\\render_meta_box', $type, 'normal' );
	}
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_meta_boxes' );

function render_meta_box( $post ) {
	wp_nonce_field( 'skp_save_details', 'skp_details_nonce' );
	foreach ( meta_fields()[ $post->post_type ] as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		?>
		<p><label for="<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $field['label'] ); ?></strong></label><br>
		<?php if ( isset( $field['options'] ) ) : ?>
			<select id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>">
			<?php foreach ( $field['options'] as $option => $label ) : ?>
				<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $value, $option ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
			</select>
		<?php elseif ( 'textarea' === ( $field['type'] ?? '' ) ) : ?>
			<textarea class="widefat" rows="3" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
		<?php else : ?>
			<input class="widefat" type="<?php echo esc_attr( $field['type'] ?? 'text' ); ?>" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
		<?php endif; ?></p>
		<?php
	}
	if ( 'sk_project' === $post->post_type ) {
		echo '<p>' . esc_html__( 'Only mark a project Completed after verifying the work. An empty verification note will display In Progress on the public site.', 'sidartha-portfolio' ) . '</p>';
	}
}

function save_meta( $post_id ) {
	if ( ! isset( $_POST['skp_details_nonce'] ) || ! is_string( $_POST['skp_details_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['skp_details_nonce'] ) ), 'skp_save_details' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	$fields = meta_fields()[ get_post_type( $post_id ) ] ?? array();
	foreach ( $fields as $key => $field ) {
		if ( isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, sanitize_field( wp_unslash( $_POST[ $key ] ), $field ) );
		}
	}
}
add_action( 'save_post', __NAMESPACE__ . '\\save_meta' );

/** Never expose the administrator address, even if it changes after settings save. */
function profile( $key ) {
	$defaults = array(
		'name' => 'Sidartha Kumar', 'eyebrow' => 'An independent journey · October 2026',
		'headline' => "Thoughtful technology.\nOne project at a time.",
		'introduction' => "I'm Sidartha Kumar, beginning my independent journey in technology. I'm learning to build thoughtful interfaces, useful software, and the systems that connect them.",
		'public_email' => '', 'github' => '', 'linkedin' => '', 'photo' => 0,
		'footer' => 'Learning with curiosity. Building with care.', 'article_category' => 0,
	);
	$options = get_option( 'skp_profile', array() );
	$value = $options[ $key ] ?? $defaults[ $key ] ?? '';
	if ( 'public_email' === $key && 0 === strcasecmp( (string) $value, (string) get_option( 'admin_email' ) ) ) {
		return '';
	}
	return $value;
}

function sanitize_profile( $input ) {
	$output = get_option( 'skp_profile', array() );
	if ( ! is_array( $input ) ) {
		return $output;
	}
	foreach ( array( 'name', 'eyebrow', 'headline', 'introduction', 'footer' ) as $key ) {
		if ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) ) {
			$output[ $key ] = substr( sanitize_textarea_field( $input[ $key ] ), 0, 1200 );
		}
	}
	foreach ( array( 'github', 'linkedin' ) as $key ) {
		$output[ $key ] = sanitize_field( $input[ $key ] ?? '', array( 'type' => 'url' ) );
	}
	$email = isset( $input['public_email'] ) && is_string( $input['public_email'] ) ? sanitize_email( $input['public_email'] ) : '';
	if ( $email && 0 === strcasecmp( $email, (string) get_option( 'admin_email' ) ) ) {
		$email = '';
		add_settings_error( 'skp_profile', 'admin_email', __( 'Use a separate public contact address. The WordPress administrator email cannot be displayed.', 'sidartha-portfolio' ) );
	}
	$output['public_email'] = is_email( $email ) ? $email : '';
	$photo = isset( $input['photo'] ) && is_scalar( $input['photo'] ) ? absint( $input['photo'] ) : 0;
	$output['photo'] = wp_attachment_is_image( $photo ) ? $photo : 0;
	$category = isset( $input['article_category'] ) && is_scalar( $input['article_category'] ) ? absint( $input['article_category'] ) : 0;
	$output['article_category'] = term_exists( $category, 'category' ) ? $category : 0;
	return $output;
}

require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/starter.php';

function activate() {
	register_content();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
// No uninstall handler: content, metadata, and settings are intentionally retained.

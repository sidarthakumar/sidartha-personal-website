<?php
defined( 'ABSPATH' ) || exit;

// WordPress returns a URL only when the policy selected in Settings > Privacy is published.
$privacy_url = get_privacy_policy_url();
if ( ! $privacy_url ) {
	$pages = get_option( 'skp_pages', array() );
	if ( ! empty( $pages['privacy'] ) && 'publish' === get_post_status( $pages['privacy'] ) ) {
		$privacy_url = get_permalink( $pages['privacy'] );
	}
}
?>
</main>
<footer class="site-footer container"><div class="footer-top"><div><a class="footer-name" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( skp_profile( 'name' ) ); ?><span aria-hidden="true">.</span></a><p><?php echo esc_html( skp_profile( 'footer' ) ); ?></p></div><div class="footer-links">
<?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'menu_class' => 'footer-menu', 'depth' => 1, 'fallback_cb' => false ) ); ?>
<?php foreach ( array( 'github' => 'GitHub', 'linkedin' => 'LinkedIn' ) as $key => $label ) : if ( skp_profile( $key ) ) : ?><a href="<?php echo esc_url( skp_profile( $key ) ); ?>" rel="me"><?php echo esc_html( $label ); ?> <?php skp_icon( 'diagonal' ); ?></a><?php endif; endforeach; ?>
<a href="<?php echo esc_url( skp_url( 'contact' ) ); ?>"><?php esc_html_e( 'Get in touch', 'sidartha' ); ?> <?php skp_icon( 'diagonal' ); ?></a></div></div>
<div class="footer-bottom"><p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( skp_profile( 'name' ) ); ?></p><p><?php if ( $privacy_url ) : ?><a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacy Policy', 'sidartha' ); ?></a><?php else : ?><span><?php esc_html_e( 'Privacy notice under review', 'sidartha' ); ?></span><?php endif; ?></p><a href="#main-content"><?php esc_html_e( 'Back to top', 'sidartha' ); ?> ↑</a></div></footer>
<?php wp_footer(); ?></body></html>

<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content"><?php esc_html_e( 'Skip to content', 'sidartha' ); ?></a>
<header class="site-header"><div class="header-inner container">
<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( skp_profile( 'name' ) . ' — Home' ); ?>"><span class="brand-mark" aria-hidden="true">s<span>.</span>k</span><span class="brand-name"><?php echo esc_html( skp_profile( 'name' ) ); ?><small><?php esc_html_e( 'A WORK IN PROGRESS', 'sidartha' ); ?></small></span></a>
<button type="button" class="menu-toggle" aria-expanded="false" aria-controls="primary-navigation"><span><?php esc_html_e( 'Menu', 'sidartha' ); ?></span><span class="menu-lines" aria-hidden="true"></span></button>
<nav id="primary-navigation" class="primary-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'sidartha' ); ?>"><?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'nav-links', 'depth' => 1, 'fallback_cb' => 'skp_nav_fallback' ) ); ?></nav>
</div></header>
<main id="main-content" tabindex="-1">

<?php
/** Template Name: About */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) : the_post(); skp_page_header( 'ABOUT / SIDARTHA KUMAR' ); ?>
<section class="container about-layout section-bottom"><div class="about-aside"><div class="about-initials" aria-hidden="true">sk<span>.</span></div><p class="eyebrow">THE STARTING POINT</p><h2>October 2026</h2><p><?php esc_html_e( 'An independent beginning in technology.', 'sidartha' ); ?></p><a class="text-link" href="<?php echo esc_url( skp_url( 'journey' ) ); ?>"><?php esc_html_e( 'Follow the journey', 'sidartha' ); ?> <?php skp_icon(); ?></a></div><div class="prose prose-large"><?php the_content(); ?></div></section>
<?php endwhile; skp_contact_band(); get_footer(); ?>

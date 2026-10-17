<?php
/** Template Name: Learning Journey */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) : the_post(); skp_page_header( 'LEARNING JOURNEY' ); endwhile; ?>
<section class="container journey-layout section-bottom"><div class="journey-intro"><p class="eyebrow">THE LONG VIEW</p><h2>Stay curious.<br>Keep building.</h2><p><?php esc_html_e( 'A journey begins with a first step. New entries will appear here as the work develops.', 'sidartha' ); ?></p></div><div><h2 class="screen-reader-text">Learning milestones</h2><?php skp_timeline(); ?></div></section>
<?php skp_contact_band(); get_footer(); ?>

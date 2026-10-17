<?php
/** Template Name: Skills & Technologies */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) : the_post(); skp_page_header( 'SKILLS & TECHNOLOGIES' ); endwhile; ?>
<section class="container section-bottom" aria-label="<?php esc_attr_e( 'Learning focuses and developing skills', 'sidartha' ); ?>"><?php skp_skills( -1, 'h2' ); ?><div class="editorial-note"><?php skp_icon( 'book' ); ?><p><?php esc_html_e( 'Evidence comes first. Demonstrated skills link to related work; learning focuses describe the direction ahead.', 'sidartha' ); ?></p></div></section>
<?php skp_contact_band(); get_footer(); ?>

<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<?php while ( have_posts() ) : the_post(); ?>
<article class="container article-layout"><header class="page-heading"><p class="eyebrow"><?php echo esc_html( skp_profile( 'name' ) ); ?></p><h1><?php echo esc_html( get_the_title() ); ?></h1></header><div class="prose prose-large"><?php the_content(); wp_link_pages(); ?></div></article>
<?php endwhile; get_footer(); ?>

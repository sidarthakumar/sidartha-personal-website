<?php
/** Template Name: Projects */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) : the_post(); skp_page_header( 'SELECTED WORK / OPEN NOTEBOOK' ); endwhile;
$search = isset( $_GET['project_q'] ) && is_string( $_GET['project_q'] ) ? substr( sanitize_text_field( wp_unslash( $_GET['project_q'] ) ), 0, 120 ) : '';
$status = isset( $_GET['status'] ) && is_string( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
$statuses = array( 'planned' => 'Planned', 'in-progress' => 'In Progress', 'completed' => 'Completed' );
$status = isset( $statuses[ $status ] ) ? $status : '';
$technology = isset( $_GET['technology'] ) && is_string( $_GET['technology'] ) ? absint( $_GET['technology'] ) : 0;
$page_number = isset( $_GET['project_page'] ) && is_string( $_GET['project_page'] ) ? max( 1, min( 1000, absint( $_GET['project_page'] ) ) ) : 1;
$args = array( 'post_type' => 'sk_project', 'post_status' => 'publish', 'posts_per_page' => 6, 'paged' => $page_number, 's' => $search, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
if ( $status ) { $args['meta_query'] = skp_project_status_query( $status ); }
if ( $technology ) { $args['tax_query'] = array( array( 'taxonomy' => 'sk_technology', 'field' => 'term_id', 'terms' => $technology ) ); }
$projects = new WP_Query( $args );
$terms = taxonomy_exists( 'sk_technology' ) ? get_terms( array( 'taxonomy' => 'sk_technology', 'hide_empty' => true ) ) : array();
?>
<section class="container section-bottom">
<form class="project-filters" method="get" action="<?php echo esc_url( get_permalink() ); ?>" role="search"><div class="search-field"><label class="screen-reader-text" for="project-q">Search projects</label><?php skp_icon( 'search' ); ?><input id="project-q" type="search" name="project_q" value="<?php echo esc_attr( $search ); ?>" placeholder="Search projects…" maxlength="120"></div><div><label class="screen-reader-text" for="project-status">Project status</label><select id="project-status" name="status"><option value="">All statuses</option><?php foreach ( $statuses as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></div><div><label class="screen-reader-text" for="project-tech">Technology</label><select id="project-tech" name="technology"><option value="">All technologies</option><?php if ( ! is_wp_error( $terms ) ) : foreach ( $terms as $term ) : ?><option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $technology, $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option><?php endforeach; endif; ?></select></div><button class="button button-primary" type="submit">Filter <?php skp_icon(); ?></button></form>
<div class="results-line"><p><?php printf( esc_html( _n( '%s project', '%s projects', $projects->found_posts, 'sidartha' ) ), esc_html( number_format_i18n( $projects->found_posts ) ) ); ?></p><?php if ( $search || $status || $technology ) : ?><a href="<?php echo esc_url( get_permalink() ); ?>">Clear filters</a><?php else : ?><span class="small-label">HONEST STATUSES. REAL WORK.</span><?php endif; ?></div>
<div class="projects-grid"><?php while ( $projects->have_posts() ) : $projects->the_post(); skp_project_card( 'h2' ); endwhile; if ( ! $projects->post_count ) : ?><div class="empty-state"><span class="skill-icon"><?php skp_icon( 'search' ); ?></span><h2>No projects match just yet.</h2><p>Try another search or clear the filters to see the work in progress.</p><a class="text-link" href="<?php echo esc_url( get_permalink( get_queried_object_id() ) ); ?>">View all projects <?php skp_icon(); ?></a></div><?php endif; wp_reset_postdata(); ?></div>
<?php if ( $projects->max_num_pages > 1 ) : ?><nav class="pagination" aria-label="Project pages"><?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'project_page', '%#%' ), 'format' => '', 'current' => $page_number, 'total' => $projects->max_num_pages ) ) ); ?></nav><?php endif; ?>
<div class="editorial-note"><?php skp_icon( 'spark' ); ?><p>Only verified work is marked Completed. Public demos and source links appear when they are available to share.</p></div>
</section><?php skp_contact_band(); get_footer(); ?>

<?php
/** Theme presentation helpers. Portable data is owned by Sidartha Portfolio. */
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'sidartha', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 240, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/editor.css' );
	register_nav_menus( array( 'primary' => __( 'Primary navigation', 'sidartha' ), 'footer' => __( 'Footer links', 'sidartha' ) ) );
	add_image_size( 'skp-card', 960, 640, true );
} );
add_action( 'wp_enqueue_scripts', function () {
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'sidartha', get_template_directory_uri() . '/assets/site.css', array(), $version );
	wp_enqueue_script( 'sidartha', get_template_directory_uri() . '/assets/site.js', array(), $version, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

function skp_profile( $key ) {
	if ( function_exists( '\Sidartha\Portfolio\profile' ) ) {
		return \Sidartha\Portfolio\profile( $key );
	}
	$defaults = array( 'name' => get_bloginfo( 'name' ), 'headline' => get_bloginfo( 'name' ), 'introduction' => get_bloginfo( 'description' ), 'footer' => get_bloginfo( 'description' ), 'focus_label' => __( 'Currently exploring', 'sidartha' ), 'focus_heading' => __( 'Learning areas', 'sidartha' ) );
	return $defaults[ $key ] ?? '';
}

function skp_url( $role ) {
	$pages = get_option( 'skp_pages', array() );
	if ( ! empty( $pages[ $role ] ) && 'publish' === get_post_status( $pages[ $role ] ) ) {
		return get_permalink( $pages[ $role ] );
	}
	return home_url( '/' . ( 'home' === $role ? '' : sanitize_title( $role ) . '/' ) );
}

function skp_icon( $name = 'arrow' ) {
	$paths = array(
		'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
		'diagonal' => '<path d="M6 18 18 6M6 6h12v12"/>',
		'code' => '<path d="m8 6-6 6 6 6m8-12 6 6-6 6M14 3l-4 18"/>',
		'layers' => '<path d="m12 3 10 6-10 6L2 9l10-6ZM2 13l10 6 10-6M2 17l10 6 10-6"/>',
		'spark' => '<path d="m12 2 3 7 7 3-7 3-3 7-3-7-7-3 7-3 3-7Z"/>',
		'shield' => '<path d="m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6l9-4Z"/><path d="m8 12 3 3 5-6"/>',
		'search' => '<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>',
		'mail' => '<rect x="2" y="4" width="20" height="16" rx="3"/><path d="m3 6 9 7 9-7"/>',
		'book' => '<path d="M12 5c-3-3-7-3-10-2v16c4-1 7-1 10 2 3-3 6-3 10-2V3c-3-1-7-1-10 2Zm0 0v16"/>',
	);
	// Static, developer-owned SVG paths; no user HTML is interpolated.
	echo '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? $paths['arrow'] ) . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function skp_nav_fallback() {
	echo '<ul class="nav-links">';
	foreach ( array( 'home' => 'Home', 'about' => 'About', 'skills' => 'Skills', 'projects' => 'Projects', 'journey' => 'Journey', 'insights' => 'Insights', 'contact' => 'Contact' ) as $role => $label ) {
		echo '<li><a href="' . esc_url( skp_url( $role ) ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}

function skp_section_heading( $eyebrow, $title, $role = '', $link = '' ) {
	?>
	<div class="section-heading"><div><p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p><h2><?php echo esc_html( $title ); ?></h2></div>
	<?php if ( $role ) : ?><a class="text-link" href="<?php echo esc_url( skp_url( $role ) ); ?>"><?php echo esc_html( $link ); ?> <?php skp_icon(); ?></a><?php endif; ?></div>
	<?php
}

function skp_page_header( $eyebrow ) {
	?>
	<header class="page-heading container"><p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p><h1><?php echo esc_html( get_the_title() ); ?></h1>
	<?php if ( ! is_page_template( 'templates/about.php' ) && ! is_page_template( 'templates/contact.php' ) ) : ?><div class="page-intro prose"><?php the_content(); ?></div><?php endif; ?>
	</header>
	<?php
}

function skp_reading_time( $post_id ) {
	$text = wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $post_id ) ) );
	preg_match_all( '/[\p{L}\p{N}]+(?:[\x{2019}\x{0027}-][\p{L}\p{N}]+)*/u', $text, $words );
	return max( 1, (int) ceil( count( $words[0] ) / 200 ) );
}

/** Make filters agree with the public labels, including newly created metadata. */
function skp_project_status_query( $status ) {
	if ( 'planned' === $status ) {
		return array( 'relation' => 'OR', array( 'key' => 'sk_status', 'value' => 'planned' ), array( 'key' => 'sk_status', 'compare' => 'NOT EXISTS' ), array( 'key' => 'sk_status', 'value' => '' ) );
	}
	$no_evidence = array( 'relation' => 'OR', array( 'key' => 'sk_evidence', 'value' => '' ), array( 'key' => 'sk_evidence', 'compare' => 'NOT EXISTS' ) );
	if ( 'in-progress' === $status ) {
		return array( 'relation' => 'OR', array( 'key' => 'sk_status', 'value' => 'in-progress' ), array( 'relation' => 'AND', array( 'key' => 'sk_status', 'value' => 'completed' ), $no_evidence ) );
	}
	if ( 'completed' === $status ) {
		return array( 'relation' => 'AND', array( 'key' => 'sk_status', 'value' => 'completed' ), array( 'key' => 'sk_evidence', 'value' => '', 'compare' => '!=' ) );
	}
	return array();
}

function skp_project_card( $heading = 'h3' ) {
	$heading = 'h2' === $heading ? 'h2' : 'h3';
	$status = get_post_meta( get_the_ID(), 'sk_status', true );
	$labels = array( 'planned' => 'Planned', 'in-progress' => 'In Progress', 'completed' => 'Completed' );
	if ( ! isset( $labels[ $status ] ) ) {
		$status = 'planned';
	}
	if ( 'completed' === $status && ! trim( get_post_meta( get_the_ID(), 'sk_evidence', true ) ) ) {
		$status = 'in-progress';
	}
	$terms = get_the_terms( get_the_ID(), 'sk_technology' );
	?>
	<article class="project-card">
		<a class="project-visual" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'skp-card', array( 'alt' => '', 'loading' => 'lazy' ) ); else : ?>
			<div class="browser-art"><div class="browser-bar"><i></i><i></i><i></i><span>PERSONAL / PORTFOLIO</span></div><div class="browser-body"><span class="art-label">A SPACE TO BEGIN</span><b>Ideas into<br><em>possibility.</em></b><div class="art-lines"><i></i><i></i></div><span class="art-button">Explore the work &nbsp; ↗</span></div><span class="art-code">&lt;/&gt;</span></div>
		<?php endif; ?>
		<span class="visual-arrow"><?php skp_icon( 'diagonal' ); ?></span></a>
		<div class="project-body"><div class="card-topline"><span class="status status-<?php echo esc_attr( $status ); ?>"><i></i><?php echo esc_html( $labels[ $status ] ); ?></span><span class="small-label"><?php esc_html_e( 'Personal project', 'sidartha' ); ?></span></div>
		<<?php echo esc_attr( $heading ); ?> class="card-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a></<?php echo esc_attr( $heading ); ?>><p><?php echo esc_html( get_the_excerpt() ); ?></p>
		<div class="tags"><?php if ( $terms && ! is_wp_error( $terms ) ) : foreach ( $terms as $term ) : ?><span><?php echo esc_html( $term->name ); ?></span><?php endforeach; endif; ?></div></div>
	</article>
	<?php
}

function skp_article_card( $heading = 'h3' ) {
	$heading = 'h2' === $heading ? 'h2' : 'h3';
	?>
	<article class="article-card"><div class="article-meta"><span><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></span><span><?php printf( esc_html__( '%s min read', 'sidartha' ), esc_html( skp_reading_time( get_the_ID() ) ) ); ?></span></div>
	<?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'skp-card', array( 'alt' => '', 'loading' => 'lazy' ) ); ?></a><?php endif; ?>
	<<?php echo esc_attr( $heading ); ?> class="card-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a></<?php echo esc_attr( $heading ); ?>><p><?php echo esc_html( get_the_excerpt() ); ?></p><a class="text-link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read the note', 'sidartha' ); ?><span class="screen-reader-text">: <?php echo esc_html( get_the_title() ); ?></span> <?php skp_icon(); ?></a></article>
	<?php
}

function skp_skills( $limit = -1, $heading = 'h3' ) {
	$heading = 'h2' === $heading ? 'h2' : 'h3';
	$query = new WP_Query( array( 'post_type' => 'sk_skill', 'post_status' => 'publish', 'posts_per_page' => $limit, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true ) );
	$icons = array( 'code', 'layers', 'spark', 'shield', 'book', 'search' );
	?> <div class="skills-grid"> <?php
	while ( $query->have_posts() ) : $query->the_post();
		$focus = get_post_meta( get_the_ID(), 'sk_focus', true );
		$labels = array( 'learning' => 'Learning focus', 'developing' => 'Developing through practice', 'demonstrated' => 'Demonstrated in linked work' );
		$work = get_post_meta( get_the_ID(), 'sk_work', true );
		if ( 'demonstrated' === $focus && ! $work ) { $focus = 'learning'; }
		?>
		<article class="skill-card"><span class="skill-icon"><?php skp_icon( $icons[ $query->current_post % count( $icons ) ] ); ?></span><span class="small-label"><?php echo esc_html( $labels[ $focus ] ?? $labels['learning'] ); ?></span><<?php echo esc_attr( $heading ); ?> class="card-title"><?php echo esc_html( get_the_title() ); ?></<?php echo esc_attr( $heading ); ?>><p><?php echo esc_html( get_the_excerpt() ); ?></p><div class="tags"><?php foreach ( explode( ',', get_post_meta( get_the_ID(), 'sk_topics', true ) ) as $topic ) : if ( trim( $topic ) ) : ?><span><?php echo esc_html( trim( $topic ) ); ?></span><?php endif; endforeach; ?></div>
		<?php if ( $work ) : ?><a class="text-link" href="<?php echo esc_url( $work ); ?>"><?php esc_html_e( 'Related work', 'sidartha' ); ?> <?php skp_icon(); ?></a><?php endif; ?></article>
		<?php
	endwhile;
	if ( ! $query->post_count ) { echo '<p class="empty-state">' . esc_html__( 'Learning focuses will be shared here as the journey develops.', 'sidartha' ) . '</p>'; }
	wp_reset_postdata();
	echo '</div>';
}

function skp_timeline( $limit = -1 ) {
	$query = new WP_Query( array( 'post_type' => 'sk_milestone', 'post_status' => 'publish', 'posts_per_page' => $limit, 'meta_key' => 'sk_date', 'orderby' => 'meta_value', 'order' => 'DESC', 'no_found_rows' => true ) );
	echo '<ol class="timeline">';
	while ( $query->have_posts() ) : $query->the_post();
		$date = get_post_meta( get_the_ID(), 'sk_date', true );
		?> <li><span class="timeline-dot"></span><div class="timeline-date"><time datetime="<?php echo esc_attr( substr( $date, 0, 7 ) ); ?>"><?php echo esc_html( $date ? wp_date( 'F Y', strtotime( $date . ' UTC' ), new DateTimeZone( 'UTC' ) ) : __( 'Date to be confirmed', 'sidartha' ) ); ?></time><span class="small-label"><?php echo esc_html( ucfirst( get_post_meta( get_the_ID(), 'sk_kind', true ) ) ); ?></span></div><div><h3><?php echo esc_html( get_the_title() ); ?></h3><div class="prose"><?php the_content(); ?></div></div></li> <?php
	endwhile;
	if ( ! $query->post_count ) { echo '<li><p>' . esc_html__( 'New milestones will be recorded here as they happen.', 'sidartha' ) . '</p></li>'; }
	echo '</ol>';
	wp_reset_postdata();
}

function skp_contact_band() {
	?><section class="contact-band container"><div><p class="eyebrow"><?php esc_html_e( 'Always curious', 'sidartha' ); ?></p><h2><?php esc_html_e( 'Let’s make a meaningful connection.', 'sidartha' ); ?></h2><p><?php esc_html_e( 'A question, an idea, or a shared interest in technology.', 'sidartha' ); ?></p></div><a class="button button-primary" href="<?php echo esc_url( skp_url( 'contact' ) ); ?>"><?php esc_html_e( 'Start a conversation', 'sidartha' ); ?> <?php skp_icon( 'diagonal' ); ?></a></section><?php
}

add_action( 'pre_get_posts', function ( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_home() && skp_profile( 'article_category' ) ) {
		$query->set( 'cat', absint( skp_profile( 'article_category' ) ) );
	}
} );

/** Leave SEO metadata ownership to established SEO plugins when present. */
add_action( 'wp_head', function () {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || is_404() || is_search() ) { return; }
	$description = is_front_page() ? skp_profile( 'introduction' ) : ( is_singular() ? get_the_excerpt( get_queried_object_id() ) : '' );
	if ( is_home() && ! is_front_page() ) {
		$posts_page_id = (int) get_option( 'page_for_posts' );
		$posts_page = $posts_page_id > 0 ? get_post( $posts_page_id ) : null;
		$description = '';
		if ( $posts_page instanceof WP_Post && 'page' === $posts_page->post_type && 'publish' === $posts_page->post_status && ! $posts_page->post_password ) {
			$description = trim( wp_strip_all_tags( strip_shortcodes( $posts_page->post_excerpt ) ) );
			if ( '' === $description ) {
				$description = trim( wp_strip_all_tags( strip_shortcodes( $posts_page->post_content ) ) );
			}
		}
		if ( '' === $description ) {
			$description = __( "Explore Sidartha Kumar's insights on software development, AI, cloud technology, and learning through practical projects and ongoing experiments.", 'sidartha' );
		}
	}
	if ( $description ) { echo '<meta name="description" content="' . esc_attr( wp_trim_words( wp_strip_all_tags( $description ), 30, '…' ) ) . '">' . "\n"; }
	if ( is_front_page() ) {
		$data = array( '@context' => 'https://schema.org', '@type' => 'Person', 'name' => skp_profile( 'name' ), 'url' => home_url( '/' ) );
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}, 5 );

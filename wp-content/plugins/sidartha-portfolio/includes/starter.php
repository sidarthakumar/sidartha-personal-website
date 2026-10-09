<?php
namespace Sidartha\Portfolio;

defined( 'ABSPATH' ) || exit;

/** Add content once, preserving every existing/edited record and configuration. */
function seed_content( $publish_local = false ) {
	if ( ! current_user_can( 'manage_options' ) || ( $publish_local && 'local' !== wp_get_environment_type() ) ) {
		return new \WP_Error( 'skp_forbidden', 'Starter publishing is restricted to an authorized local administrator.' );
	}
	$map = get_option( 'skp_starter_map', array() );
	$failed = false;
	$status = $publish_local ? 'publish' : 'draft';
	$create = function ( $key, $data, $meta = array() ) use ( &$map, &$failed ) {
		if ( ! empty( $map[ $key ] ) && get_post( $map[ $key ] ) ) {
			return (int) $map[ $key ];
		}
		$id = wp_insert_post( array_merge( array( 'post_author' => get_current_user_id(), 'comment_status' => 'closed', 'ping_status' => 'closed' ), $data ), true );
		if ( is_wp_error( $id ) ) {
			$failed = true;
			return 0;
		}
		foreach ( $meta as $name => $value ) {
			update_post_meta( $id, $name, $value );
		}
		$map[ $key ] = $id;
		update_option( 'skp_starter_map', $map, false );
		return $id;
	};
	$pages = array(
		'home' => array( 'Home', '<p>A personal space for the work I build, the questions I explore, and the things I learn along the way.</p>', '' ),
		'about' => array( 'A curious mind.<br>A deliberate beginning.', '<!-- wp:heading --><h2 class="wp-block-heading">Hi, I’m Sidartha.</h2><!-- /wp:heading --><!-- wp:paragraph --><p>I’m beginning my independent professional journey in information technology in October 2026. This website is my personal space to document that journey honestly, from the first questions to the work that follows.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>I’m interested in how a good interface, a reliable backend, and thoughtful infrastructure come together to make technology useful. My direction spans web development, Python, AI applications, cloud systems, and secure software. These are areas I am developing, not claims of years of professional experience.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">How I want to work</h2><!-- /wp:heading --><!-- wp:list --><ul class="wp-block-list"><li>Understand the problem before choosing the tools.</li><li>Build small, working things and improve them with evidence.</li><li>Treat security, accessibility, and maintainability as part of the craft.</li><li>Document the decisions, including what still needs work.</li></ul><!-- /wp:list --><!-- wp:heading --><h2 class="wp-block-heading">Looking ahead</h2><!-- /wp:heading --><!-- wp:paragraph --><p>My current goal is to develop a sound foundation, build a portfolio of verifiable work, and learn how software can support real people and real needs. I’ll share progress here as it happens.</p><!-- /wp:paragraph -->', 'templates/about.php' ),
		'skills' => array( 'A broad curiosity.<br>A growing foundation.', '<p>Technology is connected. These are the areas I’m exploring, with an honest distinction between learning goals and work I can demonstrate.</p>', 'templates/skills.php' ),
		'projects' => array( 'Ideas, becoming<br>something useful.', '<p>A working notebook of projects, from the first plan to the details that make them real. Statuses reflect the work as it stands today.</p>', 'templates/projects.php' ),
		'journey' => array( 'The work behind<br>the progress.', '<p>A chronological record of learning, research, and building. An honest account of the process, not just the finished pieces.</p>', 'templates/journey.php' ),
		'insights' => array( 'Notes from<br>the work.', '<p>Thoughts on development, technology, and learning by making. Written from where I am, with room to grow.</p>', '' ),
		'contact' => array( 'Good things start<br>with a conversation.', '<!-- wp:paragraph --><p>I’m open to thoughtful conversations about technology, learning, and useful things we could build. If something here resonates with you, I’d be glad to hear about it.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">A little context helps</h2><!-- /wp:heading --><!-- wp:paragraph --><p>When getting in touch, share who you are, what you have in mind, and why it matters to you. Please don’t send passwords, private keys, or confidential account information.</p><!-- /wp:paragraph -->', 'templates/contact.php' ),
		'privacy' => array( 'Privacy Policy — Draft for owner review', '<!-- wp:paragraph --><p><strong>DRAFT — Requires owner review before publication. This is not a claim of legal compliance.</strong></p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">About this website</h2><!-- /wp:heading --><!-- wp:paragraph --><p>This is Sidartha Kumar’s personal professional website. Before publishing this notice, the owner must confirm the hosting provider, public contact details, applicable legal requirements, and the practices described below.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Information and connections</h2><!-- /wp:heading --><!-- wp:paragraph --><p>The custom theme and companion plugin do not add visitor analytics, advertising, third-party fonts, tracking scripts, or a contact-form database. The optional contact link opens the visitor’s email application; any message is handled by the sender’s and recipient’s email providers. The site does not verify email delivery.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">WordPress and hosting</h2><!-- /wp:heading --><!-- wp:paragraph --><p>WordPress uses cookies for authenticated editors and administrators. Hosting infrastructure may keep access and security logs, including IP addresses. Retention periods, backups, processing locations, and rights-request procedures must be confirmed with the actual hosting and email arrangements. The starter articles close comments; existing WordPress content may have different settings and must be reviewed.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Before launch</h2><!-- /wp:heading --><!-- wp:list --><ul class="wp-block-list"><li>Provide an approved public privacy contact address.</li><li>Confirm retention, log handling, and deletion/request procedures.</li><li>Review all plugins, embeds, comments, analytics, and external services actually enabled.</li><li>Update this notice when practices change and obtain appropriate legal review.</li></ul><!-- /wp:list -->', '' ),
	);
	$page_ids = get_option( 'skp_pages', array() );
	foreach ( $pages as $role => $page ) {
		$title = str_replace( '<br>', ' ', $page[0] );
		$id = $create( 'page_' . $role, array( 'post_type' => 'page', 'post_status' => 'privacy' === $role ? 'draft' : $status, 'post_title' => $title, 'post_name' => $role, 'post_content' => $page[1] ), array( '_wp_page_template' => $page[2], 'skp_display_title' => str_replace( '<br>', "\n", $page[0] ) ) );
		if ( $id ) {
			$page_ids[ $role ] = $id;
		}
	}
	update_option( 'skp_pages', $page_ids, false );
	if ( $failed ) {
		return new \WP_Error( 'skp_partial_seed', 'Some starter pages could not be created. Existing data was preserved; resolve the error and rerun.' );
	}
	$root = term_exists( 'insights', 'category' );
	if ( ! $root ) {
		$root = wp_insert_term( 'Insights', 'category', array( 'slug' => 'insights' ) );
	}
	$root_id = is_array( $root ) ? (int) $root['term_id'] : 0;
	foreach ( array( 'Frontend', 'Backend', 'AI', 'Cloud', 'Security', 'Development', 'SEO', 'Learning' ) as $name ) {
		if ( ! term_exists( sanitize_title( $name ), 'category' ) ) {
			wp_insert_term( $name, 'category', array( 'parent' => $root_id ) );
		}
	}
	$options = get_option( 'skp_profile', array() );
	if ( ! isset( $options['article_category'] ) && $root_id ) {
		$options['article_category'] = $root_id;
		update_option( 'skp_profile', $options, false );
	}
	$skills = array(
		array( 'Interfaces & experiences', 'Frontend development, Responsive applications, Accessibility, Mobile-friendly design', 'Learning how thoughtful structure, clear typography, and accessible interactions make the web easier to use.' ),
		array( 'Applications & APIs', 'Backend development, Full-stack applications, API architecture, Database design', 'Exploring how data moves through applications, how interfaces connect, and how reliable systems are organized.' ),
		array( 'Python, AI & data', 'Python, AI applications, Automation, Data analytics & reporting', 'Developing a foundation for working with data and exploring where automation and AI can be useful.' ),
		array( 'Cloud & secure delivery', 'Cloud infrastructure, Hosting, DevOps, Cybersecurity', 'Studying how software is hosted, deployed, maintained, and protected throughout its lifecycle.' ),
		array( 'Platforms for real needs', 'WordPress, ERP & CRM, SaaS products, Educational technology & LMS', 'Exploring the platforms that help people publish, learn, manage work, and deliver useful services.' ),
		array( 'Research & discovery', 'SEO, Website performance, Technical research, Emerging technologies', 'Learning to ask better questions, measure what matters, and evaluate new tools with care.' ),
	);
	foreach ( $skills as $index => $skill ) {
		$create( 'skill_' . $index, array( 'post_type' => 'sk_skill', 'post_status' => $status, 'post_title' => $skill[0], 'post_content' => '<p>' . esc_html( $skill[2] ) . '</p>', 'post_excerpt' => $skill[2], 'menu_order' => $index ), array( 'sk_focus' => 'learning', 'sk_topics' => $skill[1] ) );
	}
	$project = $create( 'project_website', array( 'post_type' => 'sk_project', 'post_status' => $status, 'post_title' => 'A personal space on the web', 'post_excerpt' => 'My first portfolio platform: a place to share the work, document the learning, and grow with intention.', 'post_content' => '<h2>The idea</h2><p>Build a personal website that presents my beginning in technology honestly and makes future work easy to share.</p><h2>What I’m building</h2><p>A lightweight custom WordPress theme, editable projects and milestones, and a clear space for articles. Security, accessibility, and maintainability are part of the brief from the start.</p><h2>Where it stands</h2><p>This is an in-progress development project. Public launch, owner content review, and real-hosting verification remain separate steps. There are no client outcomes or commercial claims attached to this project.</p>', 'menu_order' => 0 ), array( 'sk_status' => 'in-progress' ) );
	if ( $project && ! get_option( 'skp_project_terms_seeded' ) ) {
		wp_set_object_terms( $project, array( 'WordPress', 'PHP', 'CSS', 'JavaScript' ), 'sk_technology' );
		add_option( 'skp_project_terms_seeded', true, '', false );
	}
	$create( 'milestone_start', array( 'post_type' => 'sk_milestone', 'post_status' => $status, 'post_title' => 'An independent beginning', 'post_content' => '<p>October 2026 marks the start of my independent professional journey in information technology. The first goal: build a foundation, and document it honestly.</p>' ), array( 'sk_date' => '2026-10-01', 'sk_kind' => 'milestone' ) );
	$create( 'milestone_website', array( 'post_type' => 'sk_milestone', 'post_status' => $status, 'post_title' => 'Building a home for the work', 'post_content' => '<p>Starting this personal website as an active project: a space for projects, learning notes, and future articles. A useful first step, with more still to learn.</p>' ), array( 'sk_date' => '2026-10-09', 'sk_kind' => 'building' ) );
	$articles = array(
		array( 'Beginning with curiosity', 'October 2026 is the start of my independent journey in technology. Here is the intention behind this personal space.', '<p>I’m beginning my independent professional journey in the IT industry in October 2026. This website is a starting point: a place to share what I build and make sense of what I learn.</p><h2>A broad field, a deliberate approach</h2><p>I’m interested in frontend and backend development, Python, AI applications, cloud infrastructure, security, and the connections between them. That breadth is a direction for learning, not a claim that I have mastered each subject.</p><h2>What you can expect here</h2><p>I intend to publish projects with clear statuses, notes about the process, and learning milestones as they happen. When a result is incomplete, I’ll say so. When I can demonstrate something, I’ll show the work.</p><h2>One useful step at a time</h2><p>For now, the work begins with this website and the foundation behind it. I’m looking forward to the questions, experiments, and useful things that follow.</p>' ),
		array( 'A portfolio that grows with the work', 'Why this website begins with a working notebook rather than a collection of invented accomplishments.', '<p>A personal portfolio is most useful when it reflects the person behind it. At the beginning of my career, that means being clear about what is in progress, what I’m learning, and what I hope to build.</p><h2>Make room for the process</h2><p>The Projects section uses Planned, In Progress, and Completed statuses. The Learning Journey records milestones along the way. Together, they leave room for work to develop without pretending it is finished.</p><h2>Keep the foundation useful</h2><p>The direction for this site is lightweight WordPress, native content editing, accessible navigation, and a secure development workflow. These are choices for this project, not a claim of expertise or commercial results.</p><h2>Let the evidence lead</h2><p>Future work will earn its place here through things that can be demonstrated. Until then, the honest starting point is enough.</p>' ),
	);
	foreach ( $articles as $index => $article ) {
		$id = $create( 'article_' . $index, array( 'post_type' => 'post', 'post_status' => $status, 'post_title' => $article[0], 'post_excerpt' => $article[1], 'post_content' => $article[2], 'post_category' => $root_id ? array( $root_id ) : array() ) );
	}
	if ( $failed ) {
		return new \WP_Error( 'skp_partial_seed', 'Some starter content could not be created. Existing data was preserved; resolve the error and rerun.' );
	}
	if ( $publish_local && ! get_option( 'skp_local_configured' ) ) {
		update_option( 'blogname', 'Sidartha Kumar' );
		update_option( 'blogdescription', 'Learning with curiosity. Building with care.' );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_ids['home'] );
		update_option( 'page_for_posts', $page_ids['insights'] );
		update_option( 'permalink_structure', '/%postname%/' );
		$menu = wp_create_nav_menu( 'Sidartha primary' );
		if ( ! is_wp_error( $menu ) ) {
			foreach ( array( 'home' => 'Home', 'about' => 'About', 'skills' => 'Skills', 'projects' => 'Projects', 'journey' => 'Journey', 'insights' => 'Insights', 'contact' => 'Contact' ) as $role => $label ) {
				wp_update_nav_menu_item( $menu, 0, array( 'menu-item-title' => $label, 'menu-item-object-id' => $page_ids[ $role ], 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
			}
			$locations = get_theme_mod( 'nav_menu_locations', array() );
			$locations['primary'] = $menu;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
		add_option( 'skp_local_configured', true, '', false );
		flush_rewrite_rules();
	}
	return $map;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'skp seed', function ( $args, $assoc ) {
		$result = seed_content( isset( $assoc['publish-local'] ) );
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}
		\WP_CLI::success( 'Starter content is available; existing records were preserved.' );
	} );
}

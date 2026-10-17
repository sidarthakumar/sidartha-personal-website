<?php
/** Template Name: Contact */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) : the_post(); skp_page_header( 'CONTACT / SAY HELLO' ); ?>
<section class="container contact-layout section-bottom"><div class="prose prose-large"><?php the_content(); ?></div><div class="contact-card"><span class="skill-icon"><?php skp_icon( 'mail' ); ?></span><p class="eyebrow">A DIRECT CONNECTION</p><h2>Let’s keep it personal.</h2>
<?php $email = skp_profile( 'public_email' ); if ( $email && is_email( $email ) ) : ?><a class="email-link" href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?> <?php skp_icon( 'diagonal' ); ?></a><p class="contact-hint">Opens your email app. Sending and delivery are handled by your email provider.</p><?php else : ?><p>Public contact details are being prepared. Please check back soon.</p><p class="contact-hint">No contact form data is collected on this page.</p><?php endif; ?>
<?php foreach ( array( 'github' => 'GitHub', 'linkedin' => 'LinkedIn' ) as $key => $label ) : if ( skp_profile( $key ) ) : ?><a class="text-link" href="<?php echo esc_url( skp_profile( $key ) ); ?>" rel="me"><?php echo esc_html( $label ); ?> <?php skp_icon( 'diagonal' ); ?></a><?php endif; endforeach; ?></div></section>
<?php endwhile; get_footer(); ?>

<?php
/**
 * Single Agent template — doubles as the agent's marketing landing page.
 * Profile, contact CTAs, projects, lead form, social links.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$agent_id = get_the_ID();
	$position = rafah_meta( 'position' );
	$phone    = rafah_meta( 'phone' );
	$whatsapp = rafah_meta( 'whatsapp' );
	$email    = rafah_meta( 'email' );
	$meeting  = rafah_meta( 'meeting_url' );

	$socials = array_filter(
		array(
			'X'         => rafah_meta( 'social_x' ),
			'Instagram' => rafah_meta( 'social_instagram' ),
			'LinkedIn'  => rafah_meta( 'social_linkedin' ),
			'Snapchat'  => rafah_meta( 'social_snapchat' ),
			'TikTok'    => rafah_meta( 'social_tiktok' ),
		)
	);
	?>

	<main class="rafah-page">

	<!-- Agent hero -->
	<section class="rafah-agent-hero">
		<div class="rafah-container rafah-agent-hero__inner">
			<div class="rafah-agent-hero__photo">
				<?php the_post_thumbnail( 'medium_large', array( 'fetchpriority' => 'high' ) ); ?>
			</div>
			<div>
				<h1 class="rafah-agent-hero__name"><?php the_title(); ?></h1>
				<?php if ( $position ) : ?>
					<div class="rafah-agent-hero__position"><?php echo esc_html( $position ); ?></div>
				<?php endif; ?>

				<div class="rafah-agent-hero__meta">
					<?php if ( rafah_meta( 'experience_years' ) ) : ?>
						<span><?php echo esc_html( rafah_text( 'experience' ) . ': ' . rafah_meta( 'experience_years' ) ); ?></span>
					<?php endif; ?>
					<?php if ( rafah_meta( 'license_no' ) ) : ?>
						<span><?php echo esc_html( rafah_text( 'license' ) . ': ' . rafah_meta( 'license_no' ) ); ?></span>
					<?php endif; ?>
					<?php if ( rafah_meta( 'languages' ) ) : ?>
						<span><?php echo esc_html( rafah_text( 'languages' ) . ': ' . rafah_meta( 'languages' ) ); ?></span>
					<?php endif; ?>
					<?php if ( rafah_meta( 'specialties' ) ) : ?>
						<span><?php echo esc_html( rafah_text( 'specialties' ) . ': ' . rafah_meta( 'specialties' ) ); ?></span>
					<?php endif; ?>
				</div>

				<div class="rafah-agent-hero__actions">
					<?php if ( $whatsapp ) : ?>
						<a class="rafah-btn rafah-btn--whatsapp" href="<?php echo esc_url( rafah_whatsapp_url( $whatsapp ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( rafah_text( 'whatsapp' ) ); ?></a>
					<?php endif; ?>
					<?php if ( $phone ) : ?>
						<a class="rafah-btn rafah-btn--primary" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( rafah_text( 'call_now' ) ); ?></a>
					<?php endif; ?>
					<?php if ( $meeting ) : ?>
						<a class="rafah-btn rafah-btn--light" href="<?php echo esc_url( $meeting ); ?>" target="_blank" rel="noopener"><?php echo esc_html( rafah_text( 'schedule_meeting' ) ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<div class="rafah-container rafah-section">
		<div class="rafah-project-layout">
			<main class="rafah-project-main">

				<!-- Biography -->
				<?php if ( get_the_content() ) : ?>
					<section>
						<h2><?php the_title(); ?></h2>
						<div class="rafah-content"><?php the_content(); ?></div>
					</section>
				<?php endif; ?>

				<!-- Agent projects -->
				<?php
				$projects = new WP_Query(
					array(
						'post_type'      => 'project',
						'posts_per_page' => 6,
						'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
							array(
								'key'   => '_rafah_agent_id',
								'value' => $agent_id,
							),
						),
					)
				);
				?>
				<?php if ( $projects->have_posts() ) : ?>
					<section>
						<h2><?php echo esc_html( rafah_text( 'agent_projects' ) ); ?></h2>
						<div class="rafah-grid">
							<?php
							while ( $projects->have_posts() ) {
								$projects->the_post();
								rafah_project_card( get_the_ID() );
							}
							wp_reset_postdata();
							?>
						</div>
					</section>
				<?php endif; ?>

				<!-- Lead form (vanishes gracefully if the form plugin is inactive) -->
				<?php $form = rafah_theme_render_shortcode( rafah_meta( 'form_shortcode', $agent_id ) ); ?>
				<?php if ( $form ) : ?>
					<section id="contact-agent">
						<h2><?php echo esc_html( rafah_text( 'send_message' ) ); ?></h2>
						<?php echo $form; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</section>
				<?php endif; ?>
			</main>

			<!-- Sidebar -->
			<aside class="rafah-project-aside">
				<div class="rafah-aside-card">
					<h3 class="rafah-aside-card__title"><?php echo esc_html( rafah_text( 'contact_us' ) ); ?></h3>
					<p class="rafah-aside-card__sub"><?php echo esc_html( rafah_text( 'interested_sub' ) ); ?></p>

					<?php if ( $whatsapp ) : ?>
						<a class="rafah-btn rafah-btn--whatsapp" href="<?php echo esc_url( rafah_whatsapp_url( $whatsapp ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( rafah_text( 'whatsapp' ) ); ?></a>
					<?php endif; ?>
					<?php if ( $phone ) : ?>
						<a class="rafah-btn rafah-btn--primary" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( rafah_text( 'call_now' ) ); ?></a>
					<?php endif; ?>
					<?php if ( $email ) : ?>
						<a class="rafah-btn rafah-btn--secondary" href="mailto:<?php echo esc_attr( $email ); ?>">Email</a>
					<?php endif; ?>

					<?php if ( $socials ) : ?>
						<div class="rafah-share" style="margin-top:18px">
							<?php foreach ( $socials as $network => $link ) : ?>
								<a class="rafah-icon-btn" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $network ); ?>">
									<strong style="font-size:11px"><?php echo esc_html( mb_substr( $network, 0, 2 ) ); ?></strong>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<div style="margin-top:18px">
						<?php rafah_theme_share_buttons(); ?>
					</div>
				</div>
			</aside>
		</div>
	</div>

	</main>

	<?php
endwhile;

get_footer();

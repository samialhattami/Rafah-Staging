<?php
/**
 * Rafah — Native homepage fallback.
 *
 * Renders automatically when the front page was built with Elementor but
 * Elementor is unavailable (deactivated, broken, mid-update). The hero
 * renders separately via astra_content_before. Business content comes from
 * Rafah Core helpers — every call is guarded so nothing can fatal.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

$rafah_ar = ! function_exists( 'rafah_is_rtl_lang' ) || rafah_is_rtl_lang();
?>
<div class="rafah-home-fallback">

	<?php if ( function_exists( 'rafah_project_card' ) && post_type_exists( 'project' ) ) : ?>
		<?php
		$rafah_fb_projects = get_posts( array(
			'post_type'      => 'project',
			'posts_per_page' => 6,
		) );
		?>
		<?php if ( $rafah_fb_projects ) : ?>
			<section class="rafah-section">
				<div class="rafah-section-head rafah-section-head--center">
					<span class="rafah-eyebrow"><?php echo esc_html( $rafah_ar ? 'مشاريعنا' : 'Our Projects' ); ?></span>
					<h2 class="rafah-section-head__title"><?php echo esc_html( $rafah_ar ? 'أحدث المشاريع' : 'Latest Projects' ); ?></h2>
				</div>
				<div class="rafah-grid">
					<?php foreach ( $rafah_fb_projects as $rafah_fb_project ) : ?>
						<?php rafah_project_card( $rafah_fb_project->ID ); ?>
					<?php endforeach; ?>
				</div>
				<?php if ( get_post_type_archive_link( 'project' ) ) : ?>
					<div class="rafah-load-more-wrap">
						<a class="rafah-btn rafah-btn--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'project' ) ); ?>">
							<?php echo esc_html( function_exists( 'rafah_text' ) ? rafah_text( 'view_all_projects' ) : 'جميع المشاريع' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( function_exists( 'rafah_agent_card' ) && post_type_exists( 'agent' ) ) : ?>
		<?php
		$rafah_fb_agents = get_posts( array(
			'post_type'      => 'agent',
			'posts_per_page' => 4,
		) );
		?>
		<?php if ( $rafah_fb_agents ) : ?>
			<section class="rafah-section" style="padding-top:0">
				<div class="rafah-section-head rafah-section-head--center">
					<h2 class="rafah-section-head__title"><?php echo esc_html( function_exists( 'rafah_text' ) ? rafah_text( 'agents' ) : 'فريق المبيعات' ); ?></h2>
				</div>
				<div class="rafah-grid rafah-grid--agents">
					<?php foreach ( $rafah_fb_agents as $rafah_fb_agent ) : ?>
						<?php rafah_agent_card( $rafah_fb_agent->ID ); ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
	<?php endif; ?>

	<section class="rafah-section" style="padding-top:0">
		<div class="rafah-cta">
			<div class="rafah-cta__overlay"></div>
			<h2 class="rafah-cta__title"><?php echo esc_html( $rafah_ar ? 'ابدأ رحلتك نحو منزل أحلامك' : 'Start your journey home' ); ?></h2>
			<div class="rafah-cta__actions">
				<a class="rafah-btn rafah-btn--primary" href="<?php echo esc_url( rafah_opt( 'header_cta_url', '/contact/' ) ); ?>">
					<?php echo esc_html( rafah_opt( 'header_cta_text', 'تواصل معنا' ) ); ?>
				</a>
			</div>
		</div>
	</section>
</div>

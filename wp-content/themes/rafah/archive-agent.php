<?php
/**
 * Agents archive — premium team listing.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="rafah-page">

<section class="rafah-archive-hero">
	<div class="rafah-container">
		<h1 class="rafah-archive-hero__title"><?php echo esc_html( rafah_text( 'agents' ) ); ?></h1>
		<p class="rafah-archive-hero__text">
			<?php
			echo esc_html( rafah_is_rtl_lang()
				? 'فريق من المستشارين العقاريين المرخصين، جاهزون لمساعدتك في كل خطوة نحو وحدتك الجديدة.'
				: 'A team of licensed real estate advisors, ready to help you every step of the way.' );
			?>
		</p>
	</div>
</section>

<div class="rafah-container rafah-section">
	<div class="rafah-grid rafah-grid--agents">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) {
				the_post();
				rafah_agent_card( get_the_ID() );
			}
			?>
		<?php else : ?>
			<div class="rafah-no-results"><?php echo esc_html( rafah_text( 'no_results' ) ); ?></div>
		<?php endif; ?>
	</div>

	<?php the_posts_pagination(); ?>
</div>

</main>

<?php
get_footer();

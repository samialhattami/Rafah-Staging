<?php
/**
 * Rafah — Hero (single markup source for BOTH the Elementor widget and the
 * native PHP fallback). Args arrive via rafah_theme_hero() / the widget.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

$rafah_hero = $args ?? array();

$rafah_overlay = min( 90, max( 0, (int) ( $rafah_hero['overlay'] ?? 60 ) ) ) / 100;
$rafah_allowed = array( 'em' => array(), 'br' => array(), 'span' => array( 'class' => array() ) );
?>
<section class="rafah-hero rafah-hero--theme">
	<?php if ( ! empty( $rafah_hero['video'] ) ) : ?>
		<video class="rafah-hero__bg" autoplay muted loop playsinline
			<?php echo ! empty( $rafah_hero['image'] ) ? 'poster="' . esc_url( $rafah_hero['image'] ) . '"' : ''; ?>>
			<source src="<?php echo esc_url( $rafah_hero['video'] ); ?>" type="video/mp4">
		</video>
	<?php elseif ( ! empty( $rafah_hero['image'] ) ) : ?>
		<div class="rafah-hero__bg">
			<img src="<?php echo esc_url( $rafah_hero['image'] ); ?>" alt="" fetchpriority="high">
		</div>
	<?php endif; ?>

	<div class="rafah-hero__overlay" style="--rafah-hero-overlay:<?php echo esc_attr( $rafah_overlay ); ?>"></div>

	<div class="rafah-hero__inner">
		<?php if ( ! empty( $rafah_hero['eyebrow'] ) ) : ?>
			<span class="rafah-hero__eyebrow"><?php echo esc_html( $rafah_hero['eyebrow'] ); ?></span>
		<?php endif; ?>

		<h1 class="rafah-hero__title"><?php echo wp_kses( $rafah_hero['title'] ?? '', $rafah_allowed ); ?></h1>

		<?php if ( ! empty( $rafah_hero['text'] ) ) : ?>
			<p class="rafah-hero__text"><?php echo esc_html( $rafah_hero['text'] ); ?></p>
		<?php endif; ?>

		<?php
		// Buttons — the group wrapper is emitted only when at least one button
		// has text, so removing them all leaves no empty actions row.
		if ( function_exists( 'rafah_buttons_html' ) ) {
			echo rafah_buttons_html( $rafah_hero['buttons'] ?? array(), 'rafah-hero__actions' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside helper.
		}
		?>

		<?php
		// Floating stat cards — drop any blank row; hide the whole container
		// when none remain.
		$rafah_stats = array_filter(
			(array) ( $rafah_hero['stats'] ?? array() ),
			static function ( $s ) {
				return '' !== trim( (string) ( $s['value'] ?? '' ) ) || '' !== trim( (string) ( $s['label'] ?? '' ) );
			}
		);
		?>
		<?php if ( $rafah_stats ) : ?>
			<div class="rafah-hero__cards">
				<?php foreach ( $rafah_stats as $rafah_stat ) : ?>
					<div class="rafah-hero__card">
						<span class="rafah-hero__card-value"><?php echo esc_html( $rafah_stat['value'] ?? '' ); ?></span>
						<span class="rafah-hero__card-label"><?php echo esc_html( $rafah_stat['label'] ?? '' ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $rafah_hero['show_scroll'] ) ) : ?>
		<span class="rafah-hero__scroll" aria-hidden="true">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
		</span>
	<?php endif; ?>
</section>

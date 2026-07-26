<?php
/**
 * Project card — the ONE renderer for every project card (grids, archives,
 * AJAX filter results, Elementor Projects Grid).
 *
 * Data always comes from Rafah Core. Presentation (layout + element visibility
 * + CTA label) comes from rafah_theme_card_config(), which merges the Theme
 * Customizer globals with optional per-instance overrides.
 *
 * Expects in scope:
 *   $post_id          int    (falls back to current post)
 *   $rafah_card_args  array  optional per-instance overrides (layout, show_*, button_text)
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $post_id ) ? $post_id : get_the_ID();
$cfg     = function_exists( 'rafah_theme_card_config' )
	? rafah_theme_card_config( isset( $rafah_card_args ) ? (array) $rafah_card_args : array() )
	: array( 'layout' => 'classic', 'button_text' => rafah_text( 'view_project' ), 'show_city' => true, 'show_price' => true, 'show_bedrooms' => true, 'show_area' => true, 'show_units' => true, 'show_status' => true, 'show_featured' => true, 'show_divider' => true );

$price     = rafah_meta( 'price_from', $post_id );
$city      = rafah_term_name( 'city', $post_id );
$distr     = rafah_term_name( 'district', $post_id );
$beds_f    = rafah_meta( 'bedrooms_from', $post_id );
$beds_t    = rafah_meta( 'bedrooms_to', $post_id );
$area_f    = rafah_meta( 'area_from', $post_id );
$units     = rafah_meta( 'units_total', $post_id );
$featured  = rafah_meta( 'featured', $post_id );
$note      = trim( (string) rafah_meta( 'card_note', $post_id ) );
$permalink = get_permalink( $post_id );

// Build meta chips in one pass so hidden/empty items simply don't appear and
// the row rebalances itself (no empty gaps).
$meta_items = array();
if ( $cfg['show_bedrooms'] && $beds_f ) {
	$meta_items[] = $beds_f . ( $beds_t && $beds_t !== $beds_f ? '–' . $beds_t : '' ) . ' ' . rafah_text( 'bedrooms' );
}
if ( $cfg['show_area'] && $area_f ) {
	$meta_items[] = number_format( (float) $area_f ) . '+ ' . rafah_text( 'sqm' );
}
if ( $cfg['show_units'] && $units ) {
	$meta_items[] = $units . ' ' . rafah_text( 'units' );
}
?>
<article class="rafah-card rafah-project-card rafah-card--<?php echo esc_attr( $cfg['layout'] ); ?> rafah-fade-up">
	<a class="rafah-card__media" href="<?php echo esc_url( $permalink ); ?>">
		<?php rafah_project_cover( $post_id, 'rafah-card', 'card' ); ?>
		<?php if ( $cfg['show_featured'] && $featured ) : ?>
			<span class="rafah-badge rafah-badge--featured"><?php echo esc_html( rafah_text( 'featured' ) ); ?></span>
		<?php endif; ?>
		<?php if ( $cfg['show_status'] ) : ?>
			<?php echo rafah_project_status_overlay_html( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
		<?php endif; ?>
	</a>
	<div class="rafah-card__body">
		<?php if ( $cfg['show_city'] && ( $city || $distr ) ) : ?>
			<div class="rafah-card__location"><?php echo esc_html( implode( ' · ', array_filter( array( $city, $distr ) ) ) ); ?></div>
		<?php endif; ?>
		<h3 class="rafah-card__title">
			<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
		</h3>
		<?php if ( $cfg['show_price'] && $price ) : ?>
			<div class="rafah-card__price">
				<span class="rafah-card__price-label"><?php echo esc_html( rafah_text( 'starting_from' ) ); ?></span>
				<span class="rafah-card__price-value"><?php echo esc_html( rafah_price( $price, $post_id ) ); ?></span>
			</div>
		<?php endif; ?>
		<?php if ( $meta_items ) : ?>
			<div class="rafah-card__meta">
				<?php foreach ( $meta_items as $mi ) : ?>
					<span><?php echo esc_html( $mi ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( $cfg['show_divider'] ) : ?>
			<hr class="rafah-card__divider" aria-hidden="true">
		<?php endif; ?>
		<?php if ( '' !== $note ) : ?>
			<p class="rafah-card__note"><?php echo esc_html( $note ); ?></p>
		<?php endif; ?>
		<a class="rafah-btn rafah-btn--ghost rafah-card__cta" href="<?php echo esc_url( $permalink ); ?>">
			<?php echo esc_html( $cfg['button_text'] ); ?>
		</a>
	</div>
</article>

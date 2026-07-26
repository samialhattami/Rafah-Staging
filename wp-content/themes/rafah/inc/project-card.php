<?php
/**
 * Project card configuration resolver.
 *
 * ONE source of truth for how a project card is presented. Every card is
 * rendered by template-parts/project-card.php, which asks this function for
 * its config. The resolver layers three levels, lowest priority first:
 *
 *   1. Hard defaults (below)             — always sane.
 *   2. Theme Customizer globals          — site-wide default look.
 *   3. Per-instance overrides ($args)    — e.g. one Elementor Projects Grid.
 *
 * Business data (price, beds, city…) is NOT here — it always comes from Rafah
 * Core. This resolver only decides layout + which elements are visible + the
 * CTA label, so the same data can be redrawn in any layout without touching
 * the project structure.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Allowed card layouts. Presentation only.
 *
 * @return string[]
 */
function rafah_card_layouts() {
	return array( 'classic', 'image-left', 'image-right', 'overlap' );
}

/**
 * Resolve the final card config.
 *
 * @param array $overrides Per-instance overrides (e.g. from an Elementor widget).
 *                         For show_* keys accept: 'show' | 'hide' | true | false.
 *                         '' / null / 'inherit' means "use the global setting".
 * @return array
 */
function rafah_theme_card_config( $overrides = array() ) {
	$config = array(
		'layout'        => rafah_opt( 'card_layout', 'classic' ),
		'button_text'   => (string) rafah_opt( 'card_button_text', '' ),
		'show_city'     => (bool) rafah_opt( 'card_show_city', true ),
		'show_price'    => (bool) rafah_opt( 'card_show_price', true ),
		'show_bedrooms' => (bool) rafah_opt( 'card_show_bedrooms', true ),
		'show_area'     => (bool) rafah_opt( 'card_show_area', true ),
		'show_units'    => (bool) rafah_opt( 'card_show_units', true ),
		'show_status'   => (bool) rafah_opt( 'card_show_status', true ),
		'show_featured' => (bool) rafah_opt( 'card_show_featured', true ),
		'show_divider'  => (bool) rafah_opt( 'card_show_divider', true ),
	);

	if ( is_array( $overrides ) ) {
		foreach ( $overrides as $key => $value ) {
			if ( ! array_key_exists( $key, $config ) ) {
				continue;
			}
			// Empty / inherit → keep the global value.
			if ( null === $value || '' === $value || 'inherit' === $value ) {
				continue;
			}
			if ( 0 === strpos( $key, 'show_' ) ) {
				$config[ $key ] = ! ( 'hide' === $value || false === $value || '0' === $value || 0 === $value );
			} else {
				$config[ $key ] = $value;
			}
		}
	}

	// Fallbacks / validation.
	if ( '' === trim( (string) $config['button_text'] ) ) {
		$config['button_text'] = rafah_text( 'view_project' );
	}
	if ( ! in_array( $config['layout'], rafah_card_layouts(), true ) ) {
		$config['layout'] = 'classic';
	}

	/**
	 * Filter the resolved project-card config.
	 *
	 * @param array $config    Final config.
	 * @param array $overrides Raw per-instance overrides.
	 */
	return apply_filters( 'rafah_card_config', $config, $overrides );
}

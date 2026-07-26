<?php
/**
 * Rafah Core — Front-end assets.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Assets {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		wp_enqueue_style( 'rafah', RAFAH_CORE_URL . 'assets/css/rafah.css', array(), RAFAH_CORE_VERSION );

		// Leaflet (no API key) + the Projects Map initialiser. Registered only —
		// the Projects Map widget pulls them in on demand via its
		// get_style_depends() / get_script_depends(), so pages without a map
		// load nothing extra.
		wp_register_style( 'leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css', array(), '1.9.4' );
		wp_register_script( 'leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js', array(), '1.9.4', true );
		wp_register_script( 'rafah-map', RAFAH_CORE_URL . 'assets/js/rafah-map.js', array( 'leaflet' ), RAFAH_CORE_VERSION, true );

		// Animation timing comes from Settings → Rafah, exposed as CSS variables.
		wp_add_inline_style(
			'rafah',
			sprintf(
				':root{--rafah-anim-duration:%dms;--rafah-anim-stagger:%dms;}',
				(int) Rafah_Settings::get( 'anim_duration' ),
				(int) Rafah_Settings::get( 'anim_stagger' )
			)
		);

		wp_enqueue_script( 'rafah', RAFAH_CORE_URL . 'assets/js/rafah.js', array(), RAFAH_CORE_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );

		wp_localize_script(
			'rafah',
			'rafahFront',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'rafah_filter' ),
				'lang'    => function_exists( 'pll_current_language' ) ? pll_current_language() : '',
				'anim'    => array(
					'enabled' => (bool) Rafah_Settings::get( 'anim_enabled' ),
					'style'   => (string) Rafah_Settings::get( 'anim_style' ),
					'stagger' => (int) Rafah_Settings::get( 'anim_stagger' ),
				),
				'i18n'    => array(
					'noResults' => rafah_text( 'no_results' ),
					'loadMore'  => rafah_text( 'load_more' ),
					'gallery'   => rafah_text( 'gallery' ),
					'close'     => rafah_text( 'close' ),
					'prev'      => rafah_text( 'prev' ),
					'next'      => rafah_text( 'next' ),
					'zoom'      => rafah_text( 'zoom' ),
				),
			)
		);
	}
}

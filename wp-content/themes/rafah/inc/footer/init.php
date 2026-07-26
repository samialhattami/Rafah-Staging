<?php
/**
 * Rafah Footer module — loader (ISOLATED).
 *
 * The ONLY integration point in the theme bootstrap is a single require of
 * this file from functions.php. Everything footer/back-to-top lives under
 * inc/footer/ and hooks only customize_register, wp_head, and wp_footer.
 * It never touches the homepage, hero, shared renderers, Astra, or Elementor.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'rafah_footer_parse_links' ) ) {
	/**
	 * Parse a Customizer "Label | URL" textarea into a links array.
	 *
	 * @param string $raw One "Label | URL" per line.
	 * @return array<int,array{text:string,url:string}>
	 */
	function rafah_footer_parse_links( $raw ) {
		$links = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( '' !== $parts[0] ) {
				$links[] = array( 'text' => $parts[0], 'url' => $parts[1] ?? '#' );
			}
		}
		return $links;
	}
}

require_once __DIR__ . '/customizer.php';
require_once __DIR__ . '/dynamic-css.php';
require_once __DIR__ . '/back-to-top.php';

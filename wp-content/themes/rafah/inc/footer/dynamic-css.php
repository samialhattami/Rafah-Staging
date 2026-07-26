<?php
/**
 * Rafah Footer module — dynamic CSS (ISOLATED).
 *
 * Outputs a single <style> block, LATE on wp_head, containing footer +
 * Back-to-Top styling from the Customizer. Selectors are strictly scoped to
 * `.rafah-footer*` and `.rafah-backtotop*`. Because it loads after style.css,
 * it overrides the base rules WITHOUT editing style.css. Only emits a rule
 * when its Customizer value is set (or for Back-to-Top, using the existing
 * defaults) so the footer looks identical until you customize it.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', function () {
	$css = '';
	$o   = 'rafah_opt';

	// ---------- Footer (only override when a value is set) ----------
	$bg      = $o( 'footer_bg' );        $bg      && $css .= ".rafah-footer{background:{$bg};}";
	$text    = $o( 'footer_text' );      $text    && $css .= ".rafah-footer,.rafah-footer__desc,.rafah-footer__links li,.rafah-footer__contact li{color:{$text};}";
	$hc      = $o( 'footer_heading_color' ); $hc  && $css .= ".rafah-footer__title,.rafah-footer__sitename{color:{$hc};}";
	$lc      = $o( 'footer_link_color' ); $lc     && $css .= ".rafah-footer__links a,.rafah-footer__contact a{color:{$lc};}";
	$lh      = $o( 'footer_link_hover' ); $lh     && $css .= ".rafah-footer__links a:hover,.rafah-footer__contact a:hover{color:{$lh};}";
	$hs      = (int) $o( 'footer_heading_size' ); $hs > 0 && $css .= ".rafah-footer__title{font-size:{$hs}px;}";
	$bs      = (int) $o( 'footer_body_size' );    $bs > 0 && $css .= ".rafah-footer__desc,.rafah-footer__links li,.rafah-footer__contact li{font-size:{$bs}px;}";
	$hw      = $o( 'footer_heading_weight' ); $hw && $css .= ".rafah-footer__title{font-weight:{$hw};}";
	$pt      = (int) $o( 'footer_padding_top' );    $pt > 0 && $css .= ".rafah-footer__inner{padding-top:{$pt}px;}";
	$pb      = (int) $o( 'footer_padding_bottom' ); $pb > 0 && $css .= ".rafah-footer__inner{padding-bottom:{$pb}px;}";
	$gap     = (int) $o( 'footer_col_gap' );        $gap > 0 && $css .= ".rafah-footer__inner{gap:{$gap}px;}";
	// Logo height. Must beat style.css `.rafah-footer__brand img{max-height:44px}`
	// (a max-height caps a plain height), and un-invert an uploaded footer logo
	// so it shows in its true colours. The site-logo fallback keeps its treatment.
	$logosz  = (int) $o( 'footer_logo_size', 44 );
	if ( $logosz > 0 ) {
		$css .= ".rafah-footer__brand img,.rafah-footer .custom-logo,.rafah-footer__logo img{height:{$logosz}px;max-height:none;width:auto;}";
		$css .= ".rafah-footer__logo img{filter:none;}";
	}

	// ---------- Back-to-Top (full control; defaults preserve current look) ----------
	if ( $o( 'back_to_top', true ) ) {
		$size   = (int) $o( 'btt_size', 46 );
		$radius = (int) $o( 'btt_radius', 50 );
		$offset = (int) $o( 'btt_offset', 26 );
		$bbg    = $o( 'btt_bg', '#bc945d' ) ?: '#bc945d';
		$bcol   = $o( 'btt_color', '#ffffff' ) ?: '#ffffff';
		$bbgh   = $o( 'btt_bg_hover', '#6b5840' ) ?: '#6b5840';
		$bcolh  = $o( 'btt_color_hover', '#ffffff' ) ?: '#ffffff';
		$shadows = array(
			'none' => 'none', 'soft' => '0 8px 20px -8px rgba(43,36,26,.4)',
			'medium' => '0 10px 26px -8px rgba(43,36,26,.5)', 'strong' => '0 16px 34px -10px rgba(43,36,26,.62)',
		);
		$shadow = $shadows[ $o( 'btt_shadow', 'medium' ) ] ?? $shadows['medium'];

		$isize = (int) $o( 'btt_icon_size', 20 );
		$isize = $isize > 0 ? $isize : 20;

		$css .= ".rafah-backtotop{width:{$size}px;height:{$size}px;background:{$bbg};color:{$bcol};border-radius:{$radius}px;bottom:{$offset}px;box-shadow:{$shadow};}";
		$css .= ".rafah-backtotop--start{inset-inline-start:{$offset}px;}.rafah-backtotop--end{inset-inline-end:{$offset}px;}";
		$css .= ".rafah-backtotop:hover{background:{$bbgh};color:{$bcolh};}";
		// Icon sizing/visibility for all three icon types (SVG / Font Awesome / image).
		$css .= ".rafah-backtotop svg,.rafah-backtotop__img{width:{$isize}px;height:{$isize}px;display:block;}";
		$css .= ".rafah-backtotop i{font-size:{$isize}px;line-height:1;}";
		$css .= ".rafah-backtotop__img{object-fit:contain;}";
		if ( $o( 'btt_hide_desktop' ) ) { $css .= "@media(min-width:769px){.rafah-backtotop{display:none!important;}}"; }
		if ( $o( 'btt_hide_mobile' ) )  { $css .= "@media(max-width:768px){.rafah-backtotop{display:none!important;}}"; }
	}

	if ( '' !== $css ) {
		echo "\n<style id=\"rafah-footer-css\">{$css}</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- values sanitized in Customizer; footer-scoped CSS.
	}
}, 100 );

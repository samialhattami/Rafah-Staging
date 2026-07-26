<?php
/**
 * Rafah Footer module — Customizer controls (ISOLATED).
 *
 * Adds footer + Back-to-Top controls to the EXISTING "rafah_footer" Customizer
 * section via its own customize_register hook (priority 20, so the section
 * already exists). It does NOT edit the shared inc/customizer.php. Only NEW
 * settings are registered here; existing ones (footer_description,
 * footer_copyright, social_*, contact_*, back_to_top, btt_position, btt_size,
 * btt_bg, btt_color, btt_anim) are reused as-is.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', function ( $wp_customize ) {

	// The section is registered by the theme's main customizer at priority 10.
	if ( ! $wp_customize->get_section( 'rafah_footer' ) ) {
		$wp_customize->add_section( 'rafah_footer', array(
			'title' => __( 'Footer', 'rafah-theme' ),
			'panel' => 'rafah_theme',
		) );
	}

	$add = function ( $id, $label, $type = 'text', $default = '', $sanitize = 'sanitize_text_field', $extra = array() ) use ( $wp_customize ) {
		$wp_customize->add_setting( 'rafah_' . $id, array(
			'default'           => $default,
			'sanitize_callback' => $sanitize,
			'transport'         => 'refresh',
		) );
		if ( 'image' === $type ) {
			$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'rafah_' . $id, array(
				'label' => $label, 'section' => 'rafah_footer',
			) ) );
		} elseif ( 'color' === $type ) {
			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'rafah_' . $id, array(
				'label' => $label, 'section' => 'rafah_footer',
			) ) );
		} else {
			$wp_customize->add_control( 'rafah_' . $id, array_merge( array(
				'label' => $label, 'section' => 'rafah_footer', 'type' => $type,
			), $extra ) );
		}
	};

	// ---------------- Footer content ----------------
	$add( 'footer_logo', __( 'Footer — logo (optional, overrides site logo)', 'rafah-theme' ), 'image', '', 'esc_url_raw' );
	$add( 'footer_logo_size', __( 'Footer — logo height (px)', 'rafah-theme' ), 'number', 44, 'absint', array( 'input_attrs' => array( 'min' => 16, 'max' => 120 ) ) );

	$add( 'footer_col2_heading', __( 'Column 2 — heading (blank = "روابط سريعة")', 'rafah-theme' ), 'text', '' );
	$add( 'footer_col2_links', __( 'Column 2 — links (one "Label | URL" per line; blank = Footer menu)', 'rafah-theme' ), 'textarea', '', 'sanitize_textarea_field' );
	$add( 'footer_col3_heading', __( 'Column 3 — heading (blank = "المشاريع")', 'rafah-theme' ), 'text', '' );
	$add( 'footer_col3_links', __( 'Column 3 — links (one "Label | URL" per line; blank = latest projects)', 'rafah-theme' ), 'textarea', '', 'sanitize_textarea_field' );
	$add( 'footer_contact_heading', __( 'Contact column — heading (blank = "تواصل معنا")', 'rafah-theme' ), 'text', '' );

	$add( 'social_facebook', __( 'Social — Facebook URL', 'rafah-theme' ), 'url', '', 'esc_url_raw' );
	$add( 'social_youtube', __( 'Social — YouTube URL', 'rafah-theme' ), 'url', '', 'esc_url_raw' );

	// ---------------- Footer appearance ----------------
	$add( 'footer_bg', __( 'Footer — background color', 'rafah-theme' ), 'color', '', 'sanitize_hex_color' );
	$add( 'footer_text', __( 'Footer — text color', 'rafah-theme' ), 'color', '', 'sanitize_hex_color' );
	$add( 'footer_heading_color', __( 'Footer — heading color', 'rafah-theme' ), 'color', '', 'sanitize_hex_color' );
	$add( 'footer_link_color', __( 'Footer — link color', 'rafah-theme' ), 'color', '', 'sanitize_hex_color' );
	$add( 'footer_link_hover', __( 'Footer — link hover color', 'rafah-theme' ), 'color', '', 'sanitize_hex_color' );
	$add( 'footer_heading_size', __( 'Footer — heading size (px)', 'rafah-theme' ), 'number', 0, 'absint', array( 'input_attrs' => array( 'min' => 0, 'max' => 40 ) ) );
	$add( 'footer_body_size', __( 'Footer — text size (px)', 'rafah-theme' ), 'number', 0, 'absint', array( 'input_attrs' => array( 'min' => 0, 'max' => 24 ) ) );
	$add( 'footer_heading_weight', __( 'Footer — heading weight', 'rafah-theme' ), 'select', '', 'sanitize_text_field', array( 'choices' => array( '' => __( 'Default', 'rafah-theme' ), '600' => '600', '700' => '700', '800' => '800' ) ) );
	$add( 'footer_padding_top', __( 'Footer — top padding (px)', 'rafah-theme' ), 'number', 0, 'absint', array( 'input_attrs' => array( 'min' => 0, 'max' => 160 ) ) );
	$add( 'footer_padding_bottom', __( 'Footer — bottom padding (px)', 'rafah-theme' ), 'number', 0, 'absint', array( 'input_attrs' => array( 'min' => 0, 'max' => 160 ) ) );
	$add( 'footer_col_gap', __( 'Footer — column gap (px)', 'rafah-theme' ), 'number', 0, 'absint', array( 'input_attrs' => array( 'min' => 0, 'max' => 120 ) ) );

	// ---------------- Back-to-Top (adds to existing controls) ----------------
	$add( 'btt_icon', __( 'Back to top — icon', 'rafah-theme' ), 'select', 'arrow', 'sanitize_key', array( 'choices' => array( 'arrow' => __( 'Arrow', 'rafah-theme' ), 'chevron' => __( 'Chevron', 'rafah-theme' ), 'double' => __( 'Double chevron', 'rafah-theme' ) ) ) );
	$add( 'btt_bg_hover', __( 'Back to top — background (hover)', 'rafah-theme' ), 'color', '', 'sanitize_hex_color' );
	$add( 'btt_color_hover', __( 'Back to top — icon color (hover)', 'rafah-theme' ), 'color', '', 'sanitize_hex_color' );
	$add( 'btt_radius', __( 'Back to top — corner radius (px; ≥ half size = circle)', 'rafah-theme' ), 'number', 50, 'absint', array( 'input_attrs' => array( 'min' => 0, 'max' => 50 ) ) );
	$add( 'btt_shadow', __( 'Back to top — shadow', 'rafah-theme' ), 'select', 'medium', 'sanitize_key', array( 'choices' => array( 'none' => __( 'None', 'rafah-theme' ), 'soft' => __( 'Soft', 'rafah-theme' ), 'medium' => __( 'Medium', 'rafah-theme' ), 'strong' => __( 'Strong', 'rafah-theme' ) ) ) );
	$add( 'btt_offset', __( 'Back to top — offset from edge (px)', 'rafah-theme' ), 'number', 26, 'absint', array( 'input_attrs' => array( 'min' => 0, 'max' => 120 ) ) );
	$add( 'btt_hide_desktop', __( 'Back to top — hide on desktop', 'rafah-theme' ), 'checkbox', false, 'rest_sanitize_boolean' );
	$add( 'btt_hide_mobile', __( 'Back to top — hide on mobile', 'rafah-theme' ), 'checkbox', false, 'rest_sanitize_boolean' );

	// ---- Back-to-Top ICON (preset SVG / Font Awesome / your own image) ----
	$add( 'btt_icon_source', __( 'Back to top — icon source', 'rafah-theme' ), 'select', 'preset', 'sanitize_key', array( 'choices' => array(
		'preset'      => __( 'Built-in icon (below)', 'rafah-theme' ),
		'fontawesome' => __( 'Font Awesome class', 'rafah-theme' ),
		'image'       => __( 'Upload SVG / PNG', 'rafah-theme' ),
	) ) );
	// (existing 'btt_icon' preset select — arrow/chevron/double — still applies when source = preset)
	$add( 'btt_icon_fa', __( 'Font Awesome class (e.g. fas fa-arrow-up)', 'rafah-theme' ), 'text', '' );
	$add( 'btt_icon_image', __( 'Custom icon image (SVG / PNG)', 'rafah-theme' ), 'image', '', 'esc_url_raw' );
	$add( 'btt_icon_size', __( 'Back to top — icon size (px)', 'rafah-theme' ), 'number', 20, 'absint', array( 'input_attrs' => array( 'min' => 8, 'max' => 48 ) ) );

}, 20 );

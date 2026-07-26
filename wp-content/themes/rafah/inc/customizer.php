<?php
/**
 * Rafah Theme — Customizer: global SETTINGS only (never layout building).
 *
 * Panel "Rafah Theme" → Brand Colors, Header, Footer, Hero Defaults,
 * Contact & Social, Extras. Logo & favicon use WordPress core controls
 * (Site Identity).
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', function ( $wp_customize ) {

	$wp_customize->add_panel( 'rafah_theme', array(
		'title'    => __( 'Rafah Theme', 'rafah-theme' ),
		'priority' => 25,
	) );

	/**
	 * Small helper to register a setting + control in one call.
	 */
	$field = function ( $section, $key, $label, $type = 'text', $default = '', $extra = array() ) use ( $wp_customize ) {
		$wp_customize->add_setting( 'rafah_' . $key, array(
			'default'           => $default,
			'sanitize_callback' => $extra['sanitize'] ?? 'sanitize_text_field',
		) );

		if ( 'color' === $type ) {
			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'rafah_' . $key, array(
				'label'   => $label,
				'section' => $section,
			) ) );
		} elseif ( 'image' === $type ) {
			$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'rafah_' . $key, array(
				'label'   => $label,
				'section' => $section,
			) ) );
		} else {
			$wp_customize->add_control( 'rafah_' . $key, array_merge( array(
				'label'   => $label,
				'section' => $section,
				'type'    => $type,
			), array_intersect_key( $extra, array_flip( array( 'choices', 'description', 'input_attrs' ) ) ) ) );
		}
	};

	// ------------------------------------------------ Site Identity — logo height.
	// Core Site Identity exposes logo WIDTH only; add a height control that
	// appears in the same "Site Identity" section, right under the logo.
	$field( 'title_tagline', 'logo_height', __( 'Logo height (px — 0 = automatic)', 'rafah-theme' ), 'number', 0, array(
		'sanitize'    => 'absint',
		'description' => __( 'Set a fixed logo height. 0 keeps the automatic size. Width scales proportionally.', 'rafah-theme' ),
		'input_attrs' => array( 'min' => 0, 'max' => 200, 'step' => 1 ),
	) );

	// ------------------------------------------------ Brand colors.
	$wp_customize->add_section( 'rafah_colors', array(
		'title' => __( 'Brand Colors', 'rafah-theme' ),
		'panel' => 'rafah_theme',
	) );

	$field( 'rafah_colors', 'color_primary', __( 'Primary (gold)', 'rafah-theme' ), 'color', '#bc945d', array( 'sanitize' => 'sanitize_hex_color' ) );
	$field( 'rafah_colors', 'color_secondary', __( 'Secondary (brown)', 'rafah-theme' ), 'color', '#6b5840', array( 'sanitize' => 'sanitize_hex_color' ) );
	$field( 'rafah_colors', 'color_bg', __( 'Background', 'rafah-theme' ), 'color', '#e4dcd5', array( 'sanitize' => 'sanitize_hex_color' ) );

	// ------------------------------------------------ Project Cards (global defaults).
	// Site-wide default look for project cards. A single renderer draws every
	// card; these settings only change presentation. Any Elementor Projects
	// Grid can override each of these per instance.
	$wp_customize->add_section( 'rafah_cards', array(
		'title'       => __( 'Project Cards', 'rafah-theme' ),
		'panel'       => 'rafah_theme',
		'description' => __( 'Default look for project cards everywhere. Elementor Projects Grid widgets can override these per instance. Hidden or empty elements collapse automatically — no empty gaps.', 'rafah-theme' ),
	) );

	$field( 'rafah_cards', 'card_layout', __( 'Card Layout', 'rafah-theme' ), 'select', 'classic', array(
		'sanitize' => 'sanitize_key',
		'choices'  => array(
			'classic'     => __( 'Classic (image top, content below)', 'rafah-theme' ),
			'image-left'  => __( 'Image Left + Content Right', 'rafah-theme' ),
			'image-right' => __( 'Image Right + Content Left', 'rafah-theme' ),
			'overlap'     => __( 'Premium Overlap (floating card)', 'rafah-theme' ),
		),
	) );

	$field( 'rafah_cards', 'card_button_text', __( 'Button Text', 'rafah-theme' ), 'text', '', array(
		'description' => __( 'Leave blank to use the site-language default (عرض المشروع / View Project).', 'rafah-theme' ),
	) );

	$rafah_card_toggles = array(
		'card_show_city'     => __( 'Show City / Location', 'rafah-theme' ),
		'card_show_price'    => __( 'Show Starting Price', 'rafah-theme' ),
		'card_show_bedrooms' => __( 'Show Bedrooms', 'rafah-theme' ),
		'card_show_area'     => __( 'Show Area', 'rafah-theme' ),
		'card_show_units'    => __( 'Show Number of Units', 'rafah-theme' ),
		'card_show_status'   => __( 'Show Status Overlay (Sold / Coming Soon)', 'rafah-theme' ),
		'card_show_featured' => __( 'Show Featured Badge', 'rafah-theme' ),
		'card_show_divider'  => __( 'Show Divider Line', 'rafah-theme' ),
	);
	foreach ( $rafah_card_toggles as $rafah_ckey => $rafah_clabel ) {
		$field( 'rafah_cards', $rafah_ckey, $rafah_clabel, 'checkbox', true, array( 'sanitize' => 'wp_validate_boolean' ) );
	}

	// ------------------------------------------------ Section Manager (generic).
	// The Theme owns the default layout of each content type's sections. This
	// manager — one panel section per registered content type — lets you
	// reorder (by number, lower first) and show/hide sections, with no
	// Elementor needed. The section registry comes from Rafah Core
	// (Rafah_Sections), so new content types adopt this automatically.
	if ( class_exists( 'Rafah_Sections' ) ) {
		foreach ( Rafah_Sections::types() as $rafah_stype ) {
			$rafah_type_sections = Rafah_Sections::sections( $rafah_stype );
			if ( ! $rafah_type_sections ) {
				continue;
			}
			$rafah_type_labels = Rafah_Sections::labels( $rafah_stype );

			$wp_customize->add_section( 'rafah_sections_' . $rafah_stype, array(
				/* translators: %s: content type name */
				'title'       => sprintf( __( '%s Sections', 'rafah-theme' ), ucwords( str_replace( array( '-', '_' ), ' ', $rafah_stype ) ) ),
				'panel'       => 'rafah_theme',
				'description' => __( 'Reorder and show/hide these sections. Lower "order" numbers appear first. The Theme renders them by default and keeps working even if Elementor is disabled.', 'rafah-theme' ),
			) );

			$rafah_sec_i = 0;
			foreach ( $rafah_type_sections as $rafah_sid => $rafah_tkey ) {
				$rafah_sec_i += 10;
				$rafah_skey   = $rafah_stype . '_' . str_replace( '-', '_', $rafah_sid );
				$rafah_slabel = $rafah_type_labels[ $rafah_sid ] ?? $rafah_sid;

				$field( 'rafah_sections_' . $rafah_stype, 'psec_order_' . $rafah_skey, sprintf( __( '%s — order', 'rafah-theme' ), $rafah_slabel ), 'number', $rafah_sec_i, array( 'sanitize' => 'absint', 'input_attrs' => array( 'min' => 0, 'max' => 999, 'step' => 1 ) ) );
				$field( 'rafah_sections_' . $rafah_stype, 'psec_hide_' . $rafah_skey, sprintf( __( '%s — hide', 'rafah-theme' ), $rafah_slabel ), 'checkbox', false, array( 'sanitize' => 'rest_sanitize_boolean' ) );
				$field( 'rafah_sections_' . $rafah_stype, 'psec_head_' . $rafah_skey, sprintf( __( '%s — heading (blank = default)', 'rafah-theme' ), $rafah_slabel ), 'text', '' );
			}
		}
	}

	// ------------------------------------------------ Header.
	$wp_customize->add_section( 'rafah_header', array(
		'title' => __( 'Header', 'rafah-theme' ),
		'panel' => 'rafah_theme',
	) );

	$field( 'rafah_header', 'header_sticky', __( 'Sticky header', 'rafah-theme' ), 'checkbox', true, array( 'sanitize' => 'rest_sanitize_boolean' ) );
	$field( 'rafah_header', 'header_transparent', __( 'Transparent header on homepage', 'rafah-theme' ), 'checkbox', true, array( 'sanitize' => 'rest_sanitize_boolean' ) );
	$field( 'rafah_header', 'header_cta_text', __( 'CTA button text', 'rafah-theme' ), 'text', 'تواصل معنا' );
	$field( 'rafah_header', 'header_cta_url', __( 'CTA button link', 'rafah-theme' ), 'url', '/contact/', array( 'sanitize' => 'esc_url_raw' ) );
	$field( 'rafah_header', 'header_phone', __( 'Header phone (empty = hidden)', 'rafah-theme' ), 'text', '' );
	$field( 'rafah_header', 'hide_lang_switcher', __( 'Hide the language switcher (English pages stay live & indexable — Polylang, hreflang, sitemap, SEO unaffected)', 'rafah-theme' ), 'checkbox', false, array( 'sanitize' => 'rest_sanitize_boolean' ) );

	// ------------------------------------------------ Footer.
	$wp_customize->add_section( 'rafah_footer', array(
		'title'       => __( 'Footer', 'rafah-theme' ),
		'panel'       => 'rafah_theme',
		'description' => __( 'Columns can be overridden with widgets (Appearance → Widgets → Footer Columns 1–4). Empty columns show smart defaults.', 'rafah-theme' ),
	) );

	$field( 'rafah_footer', 'footer_description', __( 'Company description', 'rafah-theme' ), 'textarea', 'رفاه للتطوير العقاري — نبني مجتمعات سكنية ترتقي بجودة الحياة في المملكة العربية السعودية.', array( 'sanitize' => 'sanitize_textarea_field' ) );
	$field( 'rafah_footer', 'footer_copyright', __( 'Copyright line (%year% = current year)', 'rafah-theme' ), 'text', '© %year% رفاه للتطوير العقاري. جميع الحقوق محفوظة.' );
	$field( 'rafah_footer', 'footer_form_shortcode', __( 'Newsletter form shortcode (optional)', 'rafah-theme' ), 'text', '' );
	$field( 'rafah_footer', 'back_to_top', __( 'Show "Back to top" button', 'rafah-theme' ), 'checkbox', true, array( 'sanitize' => 'rest_sanitize_boolean' ) );
	$field( 'rafah_footer', 'btt_position', __( 'Back to top — position', 'rafah-theme' ), 'select', 'start', array(
		'choices' => array(
			'start' => __( 'Start (right in Arabic, left in English)', 'rafah-theme' ),
			'end'   => __( 'End (left in Arabic, right in English)', 'rafah-theme' ),
		),
	) );
	$field( 'rafah_footer', 'btt_size', __( 'Back to top — size (px)', 'rafah-theme' ), 'number', 46, array( 'sanitize' => 'absint', 'input_attrs' => array( 'min' => 34, 'max' => 72 ) ) );
	$field( 'rafah_footer', 'btt_bg', __( 'Back to top — background', 'rafah-theme' ), 'color', '#bc945d', array( 'sanitize' => 'sanitize_hex_color' ) );
	$field( 'rafah_footer', 'btt_color', __( 'Back to top — arrow color', 'rafah-theme' ), 'color', '#ffffff', array( 'sanitize' => 'sanitize_hex_color' ) );
	$field( 'rafah_footer', 'btt_anim', __( 'Back to top — animation', 'rafah-theme' ), 'select', 'fade-up', array(
		'choices' => array(
			'fade-up' => __( 'Fade + slide up', 'rafah-theme' ),
			'fade'    => __( 'Fade only', 'rafah-theme' ),
			'none'    => __( 'None', 'rafah-theme' ),
		),
	) );

	// ------------------------------------------------ Hero defaults.
	$wp_customize->add_section( 'rafah_hero', array(
		'title'       => __( 'Homepage Hero', 'rafah-theme' ),
		'panel'       => 'rafah_theme',
		'description' => __( 'These are the native hero defaults. When the homepage is built with Elementor, the Rafah Hero widget is used for visual editing instead — these values are the automatic fallback if Elementor is unavailable.', 'rafah-theme' ),
	) );

	$field( 'rafah_hero', 'hero_mode', __( 'Native hero display', 'rafah-theme' ), 'select', 'auto', array(
		'choices' => array(
			'auto'   => __( 'Auto (only when Elementor is not rendering the page)', 'rafah-theme' ),
			'always' => __( 'Always show', 'rafah-theme' ),
			'never'  => __( 'Never show', 'rafah-theme' ),
		),
	) );
	$field( 'rafah_hero', 'hero_image', __( 'Background image', 'rafah-theme' ), 'image', '', array( 'sanitize' => 'esc_url_raw' ) );
	$field( 'rafah_hero', 'hero_video', __( 'Background video URL (mp4, optional)', 'rafah-theme' ), 'url', '', array( 'sanitize' => 'esc_url_raw' ) );
	$field( 'rafah_hero', 'hero_overlay', __( 'Overlay strength (0–90%)', 'rafah-theme' ), 'number', 60, array( 'sanitize' => 'absint', 'input_attrs' => array( 'min' => 0, 'max' => 90 ) ) );
	$field( 'rafah_hero', 'hero_eyebrow', __( 'Eyebrow', 'rafah-theme' ), 'text', 'رفاه للتطوير العقاري' );
	$field( 'rafah_hero', 'hero_title', __( 'Headline (<em> = gold word)', 'rafah-theme' ), 'text', 'نبني <em>مجتمعات</em> تليق بحياتك', array( 'sanitize' => 'wp_kses_post' ) );
	$field( 'rafah_hero', 'hero_text', __( 'Subtitle', 'rafah-theme' ), 'textarea', 'مشاريع سكنية فاخرة في أرقى أحياء المملكة، مصممة بعناية لترتقي بأسلوب حياتك.', array( 'sanitize' => 'sanitize_textarea_field' ) );
	$field( 'rafah_hero', 'hero_btn1_text', __( 'Primary button text', 'rafah-theme' ), 'text', 'استكشف مشاريعنا' );
	$field( 'rafah_hero', 'hero_btn1_url', __( 'Primary button link', 'rafah-theme' ), 'url', '/projects/', array( 'sanitize' => 'esc_url_raw' ) );
	$field( 'rafah_hero', 'hero_btn2_text', __( 'Secondary button text', 'rafah-theme' ), 'text', 'تواصل معنا' );
	$field( 'rafah_hero', 'hero_btn2_url', __( 'Secondary button link', 'rafah-theme' ), 'url', '/contact/', array( 'sanitize' => 'esc_url_raw' ) );
	$field( 'rafah_hero', 'hero_scroll', __( 'Show scroll indicator', 'rafah-theme' ), 'checkbox', true, array( 'sanitize' => 'rest_sanitize_boolean' ) );

	for ( $i = 1; $i <= 3; $i++ ) {
		/* translators: %d: stat number */
		$field( 'rafah_hero', 'hero_stat' . $i . '_value', sprintf( __( 'Floating stat %d — value', 'rafah-theme' ), $i ), 'text', array( 1 => '25+', 2 => '3,500+', 3 => '98%' )[ $i ] );
		/* translators: %d: stat number */
		$field( 'rafah_hero', 'hero_stat' . $i . '_label', sprintf( __( 'Floating stat %d — label', 'rafah-theme' ), $i ), 'text', array( 1 => 'مشروعاً منجزاً', 2 => 'وحدة سكنية', 3 => 'رضا العملاء' )[ $i ] );
	}

	// ------------------------------------------------ Contact & social.
	$wp_customize->add_section( 'rafah_contact', array(
		'title' => __( 'Contact & Social', 'rafah-theme' ),
		'panel' => 'rafah_theme',
	) );

	$field( 'rafah_contact', 'contact_phone', __( 'Phone', 'rafah-theme' ), 'text', '920000000' );
	$field( 'rafah_contact', 'contact_whatsapp', __( 'WhatsApp number', 'rafah-theme' ), 'text', '' );
	$field( 'rafah_contact', 'contact_email', __( 'Email', 'rafah-theme' ), 'text', 'info@rafah.sa', array( 'sanitize' => 'sanitize_email' ) );
	$field( 'rafah_contact', 'contact_address', __( 'Address', 'rafah-theme' ), 'text', 'الرياض، المملكة العربية السعودية' );
	$field( 'rafah_contact', 'contact_maps', __( 'Google Maps link', 'rafah-theme' ), 'url', '', array( 'sanitize' => 'esc_url_raw' ) );

	foreach ( array( 'x' => 'X (Twitter)', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'snapchat' => 'Snapchat', 'tiktok' => 'TikTok' ) as $network => $label ) {
		$field( 'rafah_contact', 'social_' . $network, $label, 'url', '', array( 'sanitize' => 'esc_url_raw' ) );
	}

	// ------------------------------------------------ Extras.
	$wp_customize->add_section( 'rafah_extras', array(
		'title' => __( 'Extras', 'rafah-theme' ),
		'panel' => 'rafah_theme',
	) );

	$field( 'rafah_extras', 'preloader', __( 'Show preloader while the page loads', 'rafah-theme' ), 'checkbox', false, array( 'sanitize' => 'rest_sanitize_boolean' ) );
} );

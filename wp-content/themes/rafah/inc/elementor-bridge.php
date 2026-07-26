<?php
/**
 * Rafah — Elementor template bridge (ISOLATED, opt-in, additive).
 *
 * Lets the theme's PHP templates OPTIONALLY render a chosen Elementor saved
 * template instead of their built-in layout — so a page becomes fully editable
 * in Elementor Free while Rafah Core still supplies the data (via the Project
 * Section widget / [rafah_project_section] shortcode / Projects Grid widget).
 *
 * DEFAULT = 0 (no template) → the PHP template renders its current design,
 * unchanged (zero regression). Never edits Rafah Core, URLs, SEO, or RTL.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a saved Elementor template to HTML, exposing the current project id to
 * Rafah widgets via $GLOBALS['rafah_bridge_project_id'].
 *
 * @param int $template_id elementor_library post id.
 * @param int $project_id  Current project (0 = none).
 * @return string|false HTML on success, false to fall back to the PHP layout.
 */
function rafah_render_elementor_template( $template_id, $project_id = 0 ) {
	$template_id = (int) $template_id;
	if ( ! $template_id || ! did_action( 'elementor/loaded' ) || 'publish' !== get_post_status( $template_id ) ) {
		return false;
	}
	try {
		if ( $project_id ) {
			$GLOBALS['rafah_bridge_project_id'] = (int) $project_id;
		}
		$html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id );
		unset( $GLOBALS['rafah_bridge_project_id'] );
		return ( '' !== trim( (string) $html ) ) ? $html : false;
	} catch ( \Throwable $e ) {
		unset( $GLOBALS['rafah_bridge_project_id'] );
		return false;
	}
}

/** The Elementor template id chosen for a given theme template key (0 = default). */
function rafah_template_bridge_id( $key ) {
	return (int) get_theme_mod( 'rafah_' . $key . '_elementor_template', 0 );
}

/**
 * News & Blog archives have no dedicated theme template (Astra renders them), so
 * we bridge them here — only when a template is actually chosen. Default = 0 →
 * this returns early and Astra's design is untouched.
 */
add_action( 'template_redirect', function () {
	if ( is_admin() ) {
		return;
	}
	if ( is_post_type_archive( 'news' ) ) {
		$key = 'archive_news';
	} elseif ( is_home() && ! is_front_page() ) {
		$key = 'archive_blog';
	} else {
		return;
	}
	$tpl = rafah_template_bridge_id( $key );
	if ( ! $tpl ) {
		return;
	}
	$html = rafah_render_elementor_template( $tpl, 0 );
	if ( false === $html ) {
		return;
	}
	get_header();
	echo '<main class="rafah-page">' . $html . '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor safe markup.
	get_footer();
	exit;
}, 5 );

// Customizer: per-template Elementor picker in a dedicated section.
add_action( 'customize_register', function ( $wp_customize ) {
	$wp_customize->add_section( 'rafah_elementor_templates', array(
		'title'       => __( 'Elementor Templates', 'rafah-theme' ),
		'panel'       => 'rafah_theme',
		'description' => __( 'Optionally render a saved Elementor template for these pages instead of the built-in design. Build the template with the Rafah "Project Section" / "Projects Grid" widgets — data still comes from Rafah Core. Leave "Theme default" to keep the current design.', 'rafah-theme' ),
	) );

	$choices = array( 0 => __( '— Theme default —', 'rafah-theme' ) );
	if ( class_exists( '\Elementor\Plugin' ) ) {
		foreach ( get_posts( array( 'post_type' => 'elementor_library', 'posts_per_page' => 100, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) ) as $tpl ) {
			$choices[ $tpl->ID ] = $tpl->post_title . ' (#' . $tpl->ID . ')';
		}
	}

	foreach ( array(
		'single_project'  => __( 'Single Project layout', 'rafah-theme' ),
		'archive_project' => __( 'Projects Archive layout', 'rafah-theme' ),
		'archive_news'    => __( 'News Archive layout', 'rafah-theme' ),
		'archive_blog'    => __( 'Blog Archive layout', 'rafah-theme' ),
	) as $key => $label ) {
		$wp_customize->add_setting( 'rafah_' . $key . '_elementor_template', array( 'default' => 0, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
		$wp_customize->add_control( 'rafah_' . $key . '_elementor_template', array(
			'label'   => $label,
			'section' => 'rafah_elementor_templates',
			'type'    => 'select',
			'choices' => $choices,
		) );
	}
}, 25 );

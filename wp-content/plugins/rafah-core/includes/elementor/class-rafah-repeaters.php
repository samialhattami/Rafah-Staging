<?php
/**
 * Rafah — reusable Elementor repeater builders.
 *
 * Native Elementor repeaters already give add / delete / duplicate / drag-
 * reorder / collapse for free. This class packages the ones Rafah reuses so
 * every widget gets the SAME first-class repeatable UI with one call, and so
 * "any future repeater" is consistent by construction.
 *
 * Pair these builders with rafah_buttons_html() (helpers.php) on the render
 * side: it returns an empty string when there are no non-empty items, so an
 * empty repeater leaves NO markup and NO empty space.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Repeaters {

	/**
	 * Button style variants (all have matching CSS in rafah.css).
	 *
	 * @return array<string,string>
	 */
	public static function button_variants() {
		return array(
			'primary'   => __( 'Primary', 'rafah' ),
			'secondary' => __( 'Secondary', 'rafah' ),
			'light'     => __( 'Light', 'rafah' ),
			'ghost'     => __( 'Ghost (outline)', 'rafah' ),
			'whatsapp'  => __( 'WhatsApp', 'rafah' ),
		);
	}

	/**
	 * Register a native "Buttons" repeater on a widget.
	 *
	 * Unlimited items, each deletable / duplicable / drag-sortable / collapsible.
	 * Nothing is mandatory: an empty repeater renders nothing (see
	 * rafah_buttons_html()).
	 *
	 * @param \Elementor\Widget_Base $widget The widget instance.
	 * @param string                 $id     Control id (default 'buttons').
	 * @param string|null            $label  Section-less control label.
	 */
	public static function buttons( $widget, $id = 'buttons', $label = null ) {
		$repeater = new \Elementor\Repeater();

		$repeater->add_control( 'text', array(
			'label'       => __( 'Text', 'rafah' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => __( 'Button label', 'rafah' ),
		) );

		$repeater->add_control( 'link', array(
			'label'         => __( 'Link', 'rafah' ),
			'type'          => \Elementor\Controls_Manager::URL,
			'default'       => array( 'url' => '#' ),
			'description'   => __( 'For the WhatsApp variant, enter the phone number here instead of a URL.', 'rafah' ),
			'show_external' => true,
		) );

		$repeater->add_control( 'variant', array(
			'label'   => __( 'Style', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'primary',
			'options' => self::button_variants(),
		) );

		$widget->add_control( $id, array(
			'label'       => $label ?: __( 'Buttons', 'rafah' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => array(),
		) );
	}
}

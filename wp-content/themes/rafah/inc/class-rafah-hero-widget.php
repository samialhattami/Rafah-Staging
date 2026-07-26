<?php
/**
 * Rafah Theme — Hero widget (Elementor editing layer for the theme hero).
 *
 * Lives in the THEME (presentation) and registers through Rafah Core's
 * `rafah_core_widgets` extension point. Same widget name as before
 * (`rafah_hero`) so existing pages keep working. Rendering delegates to
 * the theme's single hero template — Elementor is only the editing UI.
 *
 * This file is loaded only when Elementor is present.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_Hero extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_hero';
	}

	public function get_title() {
		return __( 'Rafah Hero', 'rafah-theme' );
	}

	public function get_icon() {
		return 'eicon-banner';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'rafah-theme' ) ) );

		$this->add_control( 'bg_image', array(
			'label' => __( 'Background Image', 'rafah-theme' ),
			'type'  => \Elementor\Controls_Manager::MEDIA,
		) );

		$this->add_control( 'bg_video', array(
			'label'       => __( 'Background Video URL (mp4, optional)', 'rafah-theme' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'description' => __( 'Overrides the image; the image becomes the poster.', 'rafah-theme' ),
		) );

		$this->add_control( 'overlay', array(
			'label'   => __( 'Overlay Strength (%)', 'rafah-theme' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 60,
			'min'     => 0,
			'max'     => 90,
		) );

		$this->add_control( 'eyebrow', array(
			'label'   => __( 'Eyebrow Text', 'rafah-theme' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'رفاه للتطوير العقاري',
		) );

		$this->add_control( 'title', array(
			'label'       => __( 'Headline', 'rafah-theme' ),
			'type'        => \Elementor\Controls_Manager::TEXTAREA,
			'default'     => 'نبني <em>مجتمعات</em> تليق بحياتك',
			'description' => __( 'Wrap a word in <em></em> to color it gold.', 'rafah-theme' ),
		) );

		$this->add_control( 'text', array(
			'label'   => __( 'Description', 'rafah-theme' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => 'مشاريع سكنية فاخرة في أرقى أحياء المملكة، مصممة بعناية لترتقي بأسلوب حياتك.',
		) );

		// Buttons — native repeater (add unlimited / delete / duplicate / drag /
		// collapse). Empty = no buttons rendered, no empty space. Legacy
		// Primary/Secondary button fields are still honoured as a fallback for
		// heroes built before this control existed (see render()).
		if ( class_exists( 'Rafah_Repeaters' ) ) {
			Rafah_Repeaters::buttons( $this, 'buttons', __( 'Buttons', 'rafah-theme' ) );
		}

		$this->add_control( 'show_scroll', array(
			'label'        => __( 'Scroll Indicator', 'rafah-theme' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$repeater = new \Elementor\Repeater();

		$repeater->add_control( 'value', array(
			'label'   => __( 'Value', 'rafah-theme' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '25+',
		) );

		$repeater->add_control( 'label', array(
			'label'   => __( 'Label', 'rafah-theme' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'مشروعاً منجزاً',
		) );

		$this->add_control( 'stats', array(
			'label'       => __( 'Floating Stat Cards', 'rafah-theme' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'title_field' => '{{{ value }}} — {{{ label }}}',
			'default'     => array(),
		) );

		$this->end_controls_section();

		if ( class_exists( 'Rafah_Style_Controls' ) ) {
			Rafah_Style_Controls::box( $this, 'hero', __( 'Hero', 'rafah' ), '.rafah-hero', array( 'padding' => true, 'pad_sel' => '.rafah-hero__inner', 'min_height' => true, 'align' => true, 'shadow' => false, 'radius' => false ) );
			Rafah_Style_Controls::overlay( $this, '.rafah-hero__overlay' );
			Rafah_Style_Controls::text( $this, 'heroeye', __( 'Eyebrow', 'rafah' ), '.rafah-hero__eyebrow', true );
			Rafah_Style_Controls::text( $this, 'herotitle', __( 'Title', 'rafah' ), '.rafah-hero__title', true );
			Rafah_Style_Controls::text( $this, 'herotext', __( 'Text', 'rafah' ), '.rafah-hero__text', true );
			Rafah_Style_Controls::box( $this, 'herocard', __( 'Stat Card', 'rafah' ), '.rafah-hero__card', array( 'pad_sel' => '.rafah-hero__card' ) );
			Rafah_Style_Controls::text( $this, 'herocardval', __( 'Stat Value', 'rafah' ), '.rafah-hero__card-value' );
			Rafah_Style_Controls::text( $this, 'herocardlab', __( 'Stat Label', 'rafah' ), '.rafah-hero__card-label' );
			Rafah_Style_Controls::button( $this, '.rafah-btn' );
		}

	}

	protected function render() {
		$s = $this->get_settings_for_display();

		// Stat cards — skip any row the editor left blank so an empty card is
		// never rendered; the container itself is hidden when none remain.
		$stats = array();
		foreach ( (array) ( $s['stats'] ?? array() ) as $stat ) {
			$value = (string) ( $stat['value'] ?? '' );
			$label = (string) ( $stat['label'] ?? '' );
			if ( '' === trim( $value ) && '' === trim( $label ) ) {
				continue;
			}
			$stats[] = array( 'value' => $value, 'label' => $label );
		}

		// Buttons from the repeater; fall back to the legacy Primary/Secondary
		// fields for heroes saved before the repeater existed.
		$buttons = array();
		foreach ( (array) ( $s['buttons'] ?? array() ) as $b ) {
			$buttons[] = array(
				'text'    => $b['text'] ?? '',
				'link'    => $b['link'] ?? array(),
				'variant' => $b['variant'] ?? 'primary',
			);
		}
		if ( ! array_filter( array_column( $buttons, 'text' ) ) ) {
			$buttons = array();
			if ( ! empty( $s['btn1_text'] ) ) {
				$buttons[] = array( 'text' => $s['btn1_text'], 'link' => $s['btn1_link'] ?? array(), 'variant' => 'primary' );
			}
			if ( ! empty( $s['btn2_text'] ) ) {
				$buttons[] = array( 'text' => $s['btn2_text'], 'link' => $s['btn2_link'] ?? array(), 'variant' => 'light' );
			}
		}

		rafah_theme_hero( array(
			'image'       => $s['bg_image']['url'] ?? '',
			'video'       => $s['bg_video'] ?? '',
			'overlay'     => $s['overlay'] ?? 60,
			'eyebrow'     => $s['eyebrow'] ?? '',
			'title'       => $s['title'] ?? '',
			'text'        => $s['text'] ?? '',
			'buttons'     => $buttons,
			'show_scroll' => 'yes' === ( $s['show_scroll'] ?? '' ),
			'stats'       => $stats,
		) );
	}
}

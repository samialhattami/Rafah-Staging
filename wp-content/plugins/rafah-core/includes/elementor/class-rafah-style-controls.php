<?php
/**
 * Rafah — shared Elementor Style-tab controls.
 *
 * ONE place that builds every Rafah widget's Style tab using Elementor's
 * NATIVE Group Controls (Typography, Background, Border, Box Shadow …), so
 * each widget feels first-class. Every control is scoped with {{WRAPPER}},
 * so it only styles that one widget instance and never leaks.
 *
 * This layer is PRESENTATION only — it emits CSS via selectors. Data always
 * comes from Rafah Core; the Theme remains the default look. Widgets opt into
 * the sections that match their markup, e.g.:
 *
 *     Rafah_Style_Controls::heading( $this );
 *     Rafah_Style_Controls::box( $this, 'card', __( 'Card', 'rafah' ), '.rafah-card', array( 'pad_sel' => '.rafah-card__body', 'hover' => true ) );
 *     Rafah_Style_Controls::button( $this, '.rafah-btn' );
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Style_Controls {

	/** Open a Style-tab section. */
	private static function open( $w, $id, $label ) {
		$w->start_controls_section( 'rafah_st_' . $id, array(
			'label' => $label,
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );
	}

	/** Dimensions (px/%/em) helper selector for border-radius. */
	private static function radius_css( $prop = 'border-radius' ) {
		return $prop . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};';
	}

	/** Dimensions helper for padding/margin. */
	private static function box_css( $prop ) {
		return $prop . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};';
	}

	/**
	 * Section Heading (eyebrow + title) — alignment, colors, typography, spacing.
	 */
	public static function heading( $w, $title = '.rafah-section-head__title', $eyebrow = '.rafah-eyebrow' ) {
		self::open( $w, 'heading', __( 'Section Heading', 'rafah' ) );

		$w->add_responsive_control( 'rafah_st_head_align', array(
			'label'     => __( 'Alignment', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::CHOOSE,
			'options'   => array(
				'left'   => array( 'title' => __( 'Left', 'rafah' ), 'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => __( 'Center', 'rafah' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => __( 'Right', 'rafah' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .rafah-section-head' => 'text-align: {{VALUE}};' ),
		) );

		$w->add_control( 'rafah_st_eyebrow_color', array(
			'label'     => __( 'Eyebrow Color', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $eyebrow => 'color: {{VALUE}};' ),
		) );
		$w->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'rafah_st_eyebrow_typo',
			'label'    => __( 'Eyebrow Typography', 'rafah' ),
			'selector' => '{{WRAPPER}} ' . $eyebrow,
		) );

		$w->add_control( 'rafah_st_title_color', array(
			'label'     => __( 'Title Color', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} ' . $title => 'color: {{VALUE}};' ),
		) );
		$w->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'rafah_st_title_typo',
			'label'    => __( 'Title Typography', 'rafah' ),
			'selector' => '{{WRAPPER}} ' . $title,
		) );

		$w->add_responsive_control( 'rafah_st_head_gap', array(
			'label'      => __( 'Spacing Below Heading', 'rafah' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 140 ) ),
			'separator'  => 'before',
			'selectors'  => array( '{{WRAPPER}} .rafah-section-head' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
		) );

		$w->end_controls_section();
	}

	/**
	 * Text element — color, typography, optional alignment.
	 */
	public static function text( $w, $id, $label, $sel, $align = false ) {
		self::open( $w, $id, $label );

		$w->add_control( 'rafah_st_' . $id . '_color', array(
			'label'     => __( 'Color', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $sel => 'color: {{VALUE}};' ),
		) );
		$w->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'rafah_st_' . $id . '_typo',
			'selector' => '{{WRAPPER}} ' . $sel,
		) );

		if ( $align ) {
			$w->add_responsive_control( 'rafah_st_' . $id . '_align', array(
				'label'     => __( 'Alignment', 'rafah' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => __( 'Left', 'rafah' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => __( 'Center', 'rafah' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => __( 'Right', 'rafah' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array( '{{WRAPPER}} ' . $sel => 'text-align: {{VALUE}};' ),
			) );
		}

		$w->end_controls_section();
	}

	/**
	 * Box element — background, border, radius, shadow, padding, optional hover
	 * + min-height + alignment. Used for cards, boxes, bars, the map, etc.
	 */
	public static function box( $w, $id, $label, $sel, $opts = array() ) {
		$o = wp_parse_args( $opts, array(
			'bg'         => true,
			'border'     => true,
			'radius'     => true,
			'shadow'     => true,
			'padding'    => true,
			'pad_sel'    => $sel,
			'hover'      => false,
			'align'      => false,
			'min_height' => false,
		) );

		self::open( $w, $id, $label );

		if ( $o['bg'] ) {
			$w->add_group_control( \Elementor\Group_Control_Background::get_type(), array(
				'name'     => 'rafah_st_' . $id . '_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} ' . $sel,
			) );
		}
		if ( $o['border'] ) {
			$w->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
				'name'     => 'rafah_st_' . $id . '_border',
				'selector' => '{{WRAPPER}} ' . $sel,
			) );
		}
		if ( $o['radius'] ) {
			$w->add_responsive_control( 'rafah_st_' . $id . '_radius', array(
				'label'      => __( 'Border Radius', 'rafah' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} ' . $sel => self::radius_css() . 'overflow: hidden;' ),
			) );
		}
		if ( $o['shadow'] ) {
			$w->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
				'name'     => 'rafah_st_' . $id . '_shadow',
				'selector' => '{{WRAPPER}} ' . $sel,
			) );
		}
		if ( $o['padding'] ) {
			$w->add_responsive_control( 'rafah_st_' . $id . '_padding', array(
				'label'      => __( 'Padding', 'rafah' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} ' . $o['pad_sel'] => self::box_css( 'padding' ) ),
			) );
		}
		if ( $o['min_height'] ) {
			$w->add_responsive_control( 'rafah_st_' . $id . '_minh', array(
				'label'      => __( 'Min Height', 'rafah' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 1000 ), 'vh' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array( '{{WRAPPER}} ' . $sel => 'min-height: {{SIZE}}{{UNIT}};' ),
			) );
		}
		if ( $o['align'] ) {
			$w->add_responsive_control( 'rafah_st_' . $id . '_align', array(
				'label'     => __( 'Text Alignment', 'rafah' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => __( 'Left', 'rafah' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => __( 'Center', 'rafah' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => __( 'Right', 'rafah' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array( '{{WRAPPER}} ' . $sel => 'text-align: {{VALUE}};' ),
			) );
		}

		if ( $o['hover'] ) {
			$w->add_control( 'rafah_st_' . $id . '_hover_h', array(
				'label'     => __( 'Hover', 'rafah' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			) );
			$w->add_control( 'rafah_st_' . $id . '_hover_lift', array(
				'label'      => __( 'Hover Lift', 'rafah' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => -40, 'max' => 0 ) ),
				'selectors'  => array( '{{WRAPPER}} ' . $sel . ':hover' => 'transform: translateY({{SIZE}}px);' ),
			) );
			$w->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
				'name'     => 'rafah_st_' . $id . '_hover_shadow',
				'label'    => __( 'Hover Shadow', 'rafah' ),
				'selector' => '{{WRAPPER}} ' . $sel . ':hover',
			) );
		}

		$w->end_controls_section();
	}

	/**
	 * Button — normal/hover tabs with typography, colors, background, border,
	 * radius and padding. $id lets a widget style more than one button type.
	 */
	public static function button( $w, $sel = '.rafah-btn', $id = 'btn' ) {
		self::open( $w, $id, __( 'Button', 'rafah' ) . ( 'btn' === $id ? '' : ' (' . $sel . ')' ) );

		$w->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'rafah_st_' . $id . '_typo',
			'selector' => '{{WRAPPER}} ' . $sel,
		) );
		$w->add_responsive_control( 'rafah_st_' . $id . '_radius', array(
			'label'      => __( 'Border Radius', 'rafah' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} ' . $sel => self::radius_css() ),
		) );
		$w->add_responsive_control( 'rafah_st_' . $id . '_padding', array(
			'label'      => __( 'Padding', 'rafah' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} ' . $sel => self::box_css( 'padding' ) ),
		) );

		$w->start_controls_tabs( 'rafah_st_' . $id . '_tabs' );

		$w->start_controls_tab( 'rafah_st_' . $id . '_normal', array( 'label' => __( 'Normal', 'rafah' ) ) );
		$w->add_control( 'rafah_st_' . $id . '_color', array(
			'label'     => __( 'Text Color', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $sel => 'color: {{VALUE}};' ),
		) );
		$w->add_group_control( \Elementor\Group_Control_Background::get_type(), array(
			'name'     => 'rafah_st_' . $id . '_bg',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} ' . $sel,
		) );
		$w->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'rafah_st_' . $id . '_border',
			'selector' => '{{WRAPPER}} ' . $sel,
		) );
		$w->end_controls_tab();

		$w->start_controls_tab( 'rafah_st_' . $id . '_hover', array( 'label' => __( 'Hover', 'rafah' ) ) );
		$w->add_control( 'rafah_st_' . $id . '_color_h', array(
			'label'     => __( 'Text Color', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $sel . ':hover' => 'color: {{VALUE}};' ),
		) );
		$w->add_group_control( \Elementor\Group_Control_Background::get_type(), array(
			'name'     => 'rafah_st_' . $id . '_bg_h',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} ' . $sel . ':hover',
		) );
		$w->add_control( 'rafah_st_' . $id . '_bordercolor_h', array(
			'label'     => __( 'Border Color', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $sel . ':hover' => 'border-color: {{VALUE}};' ),
		) );
		$w->end_controls_tab();

		$w->end_controls_tabs();

		$w->end_controls_section();
	}

	/**
	 * Badges — base badge + Featured/Status color overrides.
	 */
	public static function badges( $w ) {
		self::open( $w, 'badges', __( 'Badges', 'rafah' ) );

		$w->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'rafah_st_badge_typo',
			'selector' => '{{WRAPPER}} .rafah-badge',
		) );
		$w->add_responsive_control( 'rafah_st_badge_radius', array(
			'label'      => __( 'Border Radius', 'rafah' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .rafah-badge' => self::radius_css() ),
		) );
		$w->add_responsive_control( 'rafah_st_badge_padding', array(
			'label'      => __( 'Padding', 'rafah' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .rafah-badge' => self::box_css( 'padding' ) ),
		) );

		$w->add_control( 'rafah_st_badge_feat_h', array(
			'label'     => __( 'Featured Badge', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		) );
		$w->add_control( 'rafah_st_badge_feat_bg', array(
			'label'     => __( 'Background', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .rafah-badge--featured' => 'background: {{VALUE}};' ),
		) );
		$w->add_control( 'rafah_st_badge_feat_color', array(
			'label'     => __( 'Text', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .rafah-badge--featured' => 'color: {{VALUE}};' ),
		) );

		$w->add_control( 'rafah_st_badge_status_h', array(
			'label'     => __( 'Status Badge', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		) );
		$w->add_control( 'rafah_st_badge_status_bg', array(
			'label'     => __( 'Background', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .rafah-badge--status' => 'background: {{VALUE}};' ),
		) );
		$w->add_control( 'rafah_st_badge_status_color', array(
			'label'     => __( 'Text', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .rafah-badge--status' => 'color: {{VALUE}};' ),
		) );

		$w->end_controls_section();
	}

	/**
	 * Grid / card gaps.
	 */
	public static function grid( $w, $sel = '.rafah-grid', $id = 'grid' ) {
		self::open( $w, $id, __( 'Grid & Gaps', 'rafah' ) );

		$w->add_responsive_control( 'rafah_st_' . $id . '_colgap', array(
			'label'      => __( 'Column Gap', 'rafah' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
			'selectors'  => array( '{{WRAPPER}} ' . $sel => 'column-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$w->add_responsive_control( 'rafah_st_' . $id . '_rowgap', array(
			'label'      => __( 'Row Gap', 'rafah' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
			'selectors'  => array( '{{WRAPPER}} ' . $sel => 'row-gap: {{SIZE}}{{UNIT}};' ),
		) );

		$w->end_controls_section();
	}

	/**
	 * Image — border radius, aspect ratio, object-fit.
	 */
	public static function image( $w, $sel = '.rafah-card__media', $id = 'image' ) {
		self::open( $w, $id, __( 'Image', 'rafah' ) );

		$w->add_responsive_control( 'rafah_st_' . $id . '_radius', array(
			'label'      => __( 'Image Radius', 'rafah' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} ' . $sel        => self::radius_css() . 'overflow: hidden;',
				'{{WRAPPER}} ' . $sel . ' img' => self::radius_css(),
			),
		) );
		$w->add_responsive_control( 'rafah_st_' . $id . '_ratio', array(
			'label'     => __( 'Aspect Ratio', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'options'   => array(
				''        => __( 'Default', 'rafah' ),
				'auto'    => __( 'Auto', 'rafah' ),
				'1 / 1'   => '1:1',
				'4 / 3'   => '4:3',
				'3 / 2'   => '3:2',
				'16 / 10' => '16:10',
				'16 / 9'  => '16:9',
				'3 / 4'   => '3:4 (portrait)',
			),
			'selectors' => array( '{{WRAPPER}} ' . $sel => 'aspect-ratio: {{VALUE}};' ),
		) );
		$w->add_control( 'rafah_st_' . $id . '_fit', array(
			'label'     => __( 'Image Fit', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'options'   => array(
				''        => __( 'Default', 'rafah' ),
				'cover'   => __( 'Cover', 'rafah' ),
				'contain' => __( 'Contain', 'rafah' ),
			),
			'selectors' => array( '{{WRAPPER}} ' . $sel . ' img' => 'object-fit: {{VALUE}};' ),
		) );

		$w->end_controls_section();
	}

	/**
	 * Overlay — background color/gradient + opacity for hero/CTA overlays.
	 */
	public static function overlay( $w, $sel, $id = 'overlay' ) {
		self::open( $w, $id, __( 'Overlay', 'rafah' ) );

		$w->add_group_control( \Elementor\Group_Control_Background::get_type(), array(
			'name'     => 'rafah_st_' . $id . '_bg',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} ' . $sel,
		) );
		$w->add_control( 'rafah_st_' . $id . '_opacity', array(
			'label'      => __( 'Opacity', 'rafah' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
			'selectors'  => array( '{{WRAPPER}} ' . $sel => 'opacity: {{SIZE}};' ),
		) );

		$w->end_controls_section();
	}
}

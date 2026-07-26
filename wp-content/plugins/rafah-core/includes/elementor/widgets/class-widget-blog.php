<?php
/**
 * Rafah — Blog widget (Elementor Free compatible).
 *
 * A clean, responsive grid of equal-sized article cards from WordPress Posts
 * (the Blog) — separate from the News widget (News CPT). Rendering is
 * delegated to rafah_blog_section() (Rafah Core), also used by the
 * [rafah_blog] shortcode — single source of truth.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_Blog extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_blog';
	}

	public function get_title() {
		return __( 'Rafah Blog', 'rafah' );
	}

	public function get_icon() {
		return 'eicon-post-list';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	public function get_keywords() {
		return array( 'blog', 'posts', 'articles', 'rafah', 'مدونة' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'settings', array( 'label' => __( 'Settings', 'rafah' ) ) );

		$this->add_control( 'eyebrow', array(
			'label'   => __( 'Eyebrow', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'مدونة رفاه',
		) );

		$this->add_control( 'title', array(
			'label'   => __( 'Title', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'أحدث المقالات',
		) );

		$this->add_control( 'count', array(
			'label'   => __( 'Number of Posts', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 3,
			'min'     => 1,
			'max'     => 12,
		) );

		$this->add_control( 'category', array(
			'label'   => __( 'Category', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => $this->category_options(),
			'default' => '',
		) );

		$this->add_control( 'show_view_all', array(
			'label'        => __( 'Show "View All" Button', 'rafah' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->end_controls_section();

		Rafah_Style_Controls::heading( $this );
		Rafah_Style_Controls::box( $this, 'card', __( 'Article Card', 'rafah' ), '.rafah-blog-card', array( 'pad_sel' => '.rafah-blog-card__body', 'hover' => true ) );
		Rafah_Style_Controls::text( $this, 'btitle', __( 'Title', 'rafah' ), '.rafah-blog-card__title' );
		Rafah_Style_Controls::text( $this, 'bexc', __( 'Excerpt', 'rafah' ), '.rafah-blog-card__excerpt' );
		Rafah_Style_Controls::text( $this, 'bmore', __( 'Read More', 'rafah' ), '.rafah-blog-card__more' );
		Rafah_Style_Controls::image( $this, '.rafah-blog-card__media' );
		Rafah_Style_Controls::grid( $this, '.rafah-blog__grid' );

	}

	private function category_options() {
		$options = array( '' => __( 'All Categories', 'rafah' ) );

		$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => true ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->slug ] = $term->name;
			}
		}

		return $options;
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		rafah_blog_section( array(
			'eyebrow'       => $s['eyebrow'],
			'title'         => $s['title'],
			'count'         => (int) $s['count'],
			'category'      => $s['category'],
			'show_view_all' => 'yes' === $s['show_view_all'],
		) );
	}
}

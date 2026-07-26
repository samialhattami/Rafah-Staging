<?php
/**
 * Rafah — News widget (Elementor Free compatible).
 *
 * Company announcements from the News CPT: a large feature card with two
 * compact cards below and a "View All News" action. Rendering is delegated to
 * rafah_news_section() (Rafah Core), which is also used by the [rafah_news]
 * shortcode — single source of truth.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_News extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_news';
	}

	public function get_title() {
		return __( 'Rafah News', 'rafah' );
	}

	public function get_icon() {
		return 'eicon-posts-group';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'settings', array( 'label' => __( 'Settings', 'rafah' ) ) );

		$this->add_control( 'eyebrow', array(
			'label'   => __( 'Eyebrow', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'أخبار رفاه',
		) );

		$this->add_control( 'title', array(
			'label'   => __( 'Title', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'آخر أخبار رفاه',
		) );

		$this->add_control( 'count', array(
			'label'       => __( 'Number of Posts', 'rafah' ),
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'default'     => 3,
			'min'         => 1,
			'max'         => 7,
			'description' => __( 'The newest post becomes the large feature; the rest show as compact cards.', 'rafah' ),
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
		Rafah_Style_Controls::box( $this, 'feat', __( 'Feature Card', 'rafah' ), '.rafah-news__feature-card' );
		Rafah_Style_Controls::text( $this, 'ftitle', __( 'Feature Title', 'rafah' ), '.rafah-news__feature-title' );
		Rafah_Style_Controls::box( $this, 'mini', __( 'Mini Item', 'rafah' ), '.rafah-news__mini' );
		Rafah_Style_Controls::text( $this, 'mtitle', __( 'Mini Title', 'rafah' ), '.rafah-news__mini-title' );
		Rafah_Style_Controls::text( $this, 'ndate', __( 'Date', 'rafah' ), '.rafah-news__date' );
		Rafah_Style_Controls::button( $this, '.rafah-btn' );

	}

	private function category_options() {
		$options = array( '' => __( 'All Categories', 'rafah' ) );

		$terms = get_terms( array( 'taxonomy' => 'news_category', 'hide_empty' => true ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->slug ] = $term->name;
			}
		}

		return $options;
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		rafah_news_section( array(
			'eyebrow'       => $s['eyebrow'],
			'title'         => $s['title'],
			'count'         => (int) $s['count'],
			'category'      => $s['category'],
			'show_view_all' => 'yes' === $s['show_view_all'],
		) );
	}
}

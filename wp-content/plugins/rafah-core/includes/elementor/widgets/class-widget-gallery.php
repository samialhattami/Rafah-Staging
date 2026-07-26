<?php
/**
 * Rafah — Project Gallery widget (Elementor Free compatible).
 *
 * Displays the current project's optional Photo Gallery (the `_rafah_gallery`
 * field — separate from the featured image) anywhere on the Single Project
 * page, e.g. before or after the Units Table. If the project has no gallery,
 * the widget renders nothing (no empty space, no errors).
 *
 * Logic/data only — visual styling lives in the theme/plugin CSS
 * (`.rafah-gallery-grid`).
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_Gallery extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_project_gallery';
	}

	public function get_title() {
		return __( 'Project Gallery', 'rafah' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	public function get_keywords() {
		return array( 'gallery', 'project', 'images', 'rafah', 'معرض' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'gallery_section', array( 'label' => __( 'Gallery', 'rafah' ) ) );

		$this->add_control( 'columns', array(
			'label'   => __( 'Columns', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => '3',
			'options' => array(
				'2' => '2',
				'3' => '3',
				'4' => '4',
			),
		) );

		$this->add_control( 'lightbox', array(
			'label'        => __( 'Enable Lightbox', 'rafah' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'heading', array(
			'label'   => __( 'Heading (optional)', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '',
		) );

		$this->end_controls_section();

		Rafah_Style_Controls::text( $this, 'galtitle', __( 'Title', 'rafah' ), '.rafah-project-gallery__title', true );
		Rafah_Style_Controls::image( $this, '.rafah-gallery__item', 'gimg' );
		Rafah_Style_Controls::grid( $this, '.rafah-gallery__track' );

	}

	/**
	 * Gallery attachment IDs for the current project.
	 *
	 * Delegates to Rafah_Gallery::ids() — the single source of truth — so the
	 * parsing logic lives in exactly one place.
	 */
	private function ids() {
		$post_id = get_the_ID();

		if ( ! $post_id || 'project' !== get_post_type( $post_id ) || ! class_exists( 'Rafah_Gallery' ) ) {
			return array();
		}

		return Rafah_Gallery::ids( $post_id );
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$post_id = get_the_ID();
		$has     = $post_id && 'project' === get_post_type( $post_id ) && class_exists( 'Rafah_Gallery' ) && Rafah_Gallery::has_items( $post_id );

		if ( ! $has ) {
			// Nothing to show. Give editors a hint inside Elementor only.
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="rafah-widget-hint">' . esc_html__( 'Add a Photo Gallery to this project to display it here.', 'rafah' ) . '</div>';
			}
			return;
		}

		$cols     = in_array( $s['columns'], array( '2', '3', '4' ), true ) ? $s['columns'] : '3';
		$lightbox = 'yes' === $s['lightbox'];
		?>
		<div class="rafah-project-gallery">
			<?php if ( ! empty( $s['heading'] ) ) : ?>
				<h2 class="rafah-project-gallery__title"><?php echo esc_html( $s['heading'] ); ?></h2>
			<?php endif; ?>
			<?php
			// Single source of truth — the carousel markup lives in Rafah_Gallery,
			// shared with the theme's section rendering.
			Rafah_Gallery::grid( $post_id, $cols, $lightbox );
			?>
		</div>
		<?php
	}
}

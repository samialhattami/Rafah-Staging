<?php
/**
 * Rafah — "Project Section" Elementor widget (Free-compatible, presentation).
 *
 * Renders ANY registered section (from Rafah Core's Rafah_Sections registry)
 * on its own, so a project page can be rebuilt/customised visually in Elementor
 * Free while all data continues to come from Rafah Core. Registry-driven: any
 * future registered section appears in the dropdown automatically — no code.
 * Registered by the theme via the `rafah_core_widgets` filter.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_Project_Section extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_project_section';
	}

	public function get_title() {
		return __( 'Project Section', 'rafah' );
	}

	public function get_icon() {
		return 'eicon-info-box';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	public function get_keywords() {
		return array( 'project', 'section', 'rafah', 'units', 'gallery', 'قسم' );
	}

	private function section_options() {
		$options = array();

		if ( class_exists( 'Rafah_Sections' ) ) {
			foreach ( Rafah_Sections::types() as $type ) {
				$labels = Rafah_Sections::labels( $type );
				$tname  = ucwords( str_replace( array( '-', '_' ), ' ', $type ) );
				foreach ( Rafah_Sections::sections( $type ) as $id => $tkey ) {
					$options[ $type . '::' . $id ] = $tname . ' — ' . ( $labels[ $id ] ?? $id );
				}
			}
		}

		return $options;
	}

	protected function register_controls() {
		$this->start_controls_section( 'settings', array( 'label' => __( 'Section', 'rafah' ) ) );

		$this->add_control( 'section', array(
			'label'   => __( 'Section', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => $this->section_options(),
			'default' => 'project::overview',
		) );

		$this->add_control( 'project_id', array(
			'label'       => __( 'Project ID (blank = current)', 'rafah' ),
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'default'     => '',
			'description' => __( 'Leave blank to use the current project. All data comes from Rafah Core.', 'rafah' ),
		) );

		$this->end_controls_section();

		if ( class_exists( 'Rafah_Style_Controls' ) ) {
			Rafah_Style_Controls::box( $this, 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) );
		}

	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$val = isset( $s['section'] ) ? (string) $s['section'] : '';

		if ( '' === $val || ! function_exists( 'rafah_theme_render_section' ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="rafah-widget-hint">' . esc_html__( 'Choose a project section to display.', 'rafah' ) . '</div>';
			}
			return;
		}

		list( $type, $id ) = array_pad( explode( '::', $val, 2 ), 2, '' );
		// Resolve the project: explicit setting → the bridge's current project
		// (set when an Elementor template renders a single project) → current post.
		$project_id = ! empty( $s['project_id'] )
			? (int) $s['project_id']
			: ( ! empty( $GLOBALS['rafah_bridge_project_id'] ) ? (int) $GLOBALS['rafah_bridge_project_id'] : (int) get_the_ID() );

		echo '<div class="rafah-project-section rafah-project-main">';
		rafah_theme_render_section( sanitize_key( $type ), sanitize_title( $id ), $project_id );
		echo '</div>';
	}
}

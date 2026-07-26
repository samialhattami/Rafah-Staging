<?php
/**
 * Rafah — Projects Map widget (Elementor Free compatible).
 *
 * Interactive map that automatically plots every published project that has
 * map coordinates (_rafah_lat / _rafah_lng). Each project is a marker; the
 * popup shows its image, name and a link. The data is queried live on every
 * render, so the map always reflects the current projects (added / edited /
 * removed) with no manual step.
 *
 * Uses Leaflet + OpenStreetMap (no API key). Leaflet assets are registered by
 * Rafah_Assets and pulled in only when this widget is on the page, via
 * get_style_depends() / get_script_depends().
 *
 * Logic/data only — visual styling lives in the plugin CSS (`.rafah-map`).
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_Projects_Map extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_projects_map';
	}

	public function get_title() {
		return __( 'Projects Map', 'rafah' );
	}

	public function get_icon() {
		return 'eicon-google-maps';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	public function get_keywords() {
		return array( 'map', 'projects', 'markers', 'location', 'rafah', 'خريطة' );
	}

	public function get_style_depends() {
		return array( 'leaflet' );
	}

	public function get_script_depends() {
		return array( 'rafah-map' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'map_section', array( 'label' => __( 'Map', 'rafah' ) ) );

		$this->add_control( 'height', array(
			'label'      => __( 'Map Height', 'rafah' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'vh' ),
			'range'      => array(
				'px' => array( 'min' => 240, 'max' => 900 ),
				'vh' => array( 'min' => 30, 'max' => 100 ),
			),
			'default'    => array( 'unit' => 'px', 'size' => 480 ),
		) );

		$this->end_controls_section();

		Rafah_Style_Controls::box( $this, 'map', __( 'Map', 'rafah' ), '.rafah-map', array( 'padding' => false, 'min_height' => true ) );

	}

	/**
	 * Published projects that have usable coordinates. Queried live so the map
	 * always reflects the current set of projects.
	 *
	 * @return array[] Each: lat, lng, title, url, img, city.
	 */
	public static function points() {
		$query = new WP_Query( array(
			'post_type'      => 'project',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array( 'key' => '_rafah_lat', 'value' => '', 'compare' => '!=' ),
				array( 'key' => '_rafah_lng', 'value' => '', 'compare' => '!=' ),
			),
		) );

		$points = array();

		foreach ( $query->posts as $pid ) {
			$lat = (float) get_post_meta( $pid, '_rafah_lat', true );
			$lng = (float) get_post_meta( $pid, '_rafah_lng', true );

			if ( ! $lat || ! $lng ) {
				continue;
			}

			$points[] = array(
				'lat'   => $lat,
				'lng'   => $lng,
				'title' => get_the_title( $pid ),
				'url'   => get_permalink( $pid ),
				'img'   => get_the_post_thumbnail_url( $pid, 'rafah-card' ) ?: '',
				'city'  => function_exists( 'rafah_term_name' ) ? rafah_term_name( 'city', $pid ) : '',
			);
		}

		return $points;
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$unit   = ! empty( $s['height']['unit'] ) ? $s['height']['unit'] : 'px';
		$size   = isset( $s['height']['size'] ) && '' !== $s['height']['size'] ? (int) $s['height']['size'] : 480;
		$height = $size . $unit;
		$points = self::points();

		if ( ! $points ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="rafah-widget-hint">' . esc_html__( 'No projects have map coordinates yet. Add Latitude and Longitude to your projects to see them here.', 'rafah' ) . '</div>';
			}
			return;
		}
		?>
		<div class="rafah-map"
			data-rafah-map
			data-label-view="<?php echo esc_attr( rafah_text( 'view_project' ) ); ?>"
			style="height:<?php echo esc_attr( $height ); ?>">
			<script type="application/json" class="rafah-map-data"><?php echo wp_json_encode( $points ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
		</div>
		<?php
	}
}

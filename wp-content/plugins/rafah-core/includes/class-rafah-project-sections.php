<?php
/**
 * Rafah Core — generic front-end Section registry.
 *
 * Reusable, TYPE-AWARE registry of the sections that make up a theme template.
 * Today it powers the single project page; any other content type (news,
 * pages, services…) can register its own sections via the `rafah_sections`
 * filter with NO further architectural change. For each content type it
 * declares:
 *   - container : the theme wrapper selector the Section Manager targets
 *   - sections  : id => rafah_text() key (default heading); array order = default order
 *   - labels    : id => human label for the Customizer Section Manager UI
 *
 * The Theme renders sections by default and applies the Customizer order /
 * visibility; everything works with Elementor disabled.
 *
 * (File kept as class-rafah-project-sections.php for update-safety; the class
 * is the generic Rafah_Sections. A Rafah_Project_Sections alias is provided
 * for back-compat.)
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Sections {

	public static function init() {}

	/**
	 * The registry: content type => [ container, sections, labels ].
	 * Register other content types via the `rafah_sections` filter.
	 */
	public static function registry() {
		return apply_filters( 'rafah_sections', array(
			'project' => array(
				'container' => '.rafah-project-main',
				'sections'  => array(
					'hero'            => 'hero',
					'facts'           => 'facts',
					'overview'        => 'overview',
					'project-details' => 'project_details',
					'gallery'         => 'gallery',
					'video'           => 'video',
					'tour'            => 'tour_360',
					'floor-plans'     => 'floor_plans',
					'units'           => 'available',
					'amenities'       => 'amenities',
					'nearby'          => 'nearby',
					'payment'         => 'payment_plans',
					'location'        => 'location',
					'downloads'       => 'downloads',
					'request-info'    => 'request_info',
					'related'         => 'related',
				),
				'labels'    => array(
					'hero'            => 'Hero (cover + title + badges)',
					'facts'           => 'Facts Bar',
					'overview'        => 'Overview',
					'project-details' => 'Project Details',
					'gallery'         => 'Gallery',
					'video'           => 'Video',
					'tour'            => 'Virtual Tour',
					'floor-plans'     => 'Floor Plans',
					'units'           => 'Units',
					'amenities'       => 'Amenities & Features',
					'nearby'          => 'Nearby Places',
					'payment'         => 'Payment Plans',
					'location'        => 'Location / Map',
					'downloads'       => 'Downloads',
					'request-info'    => 'Request Info Form',
					'related'         => 'Related Projects',
				),
			),
		) );
	}

	/** Registered content-type keys (each should match a post type for the frontend gate). */
	public static function types() {
		return array_keys( self::registry() );
	}

	/** id => default heading text-key for a type. */
	public static function sections( $type = 'project' ) {
		$reg = self::registry();
		return apply_filters( 'rafah_sections_' . $type, $reg[ $type ]['sections'] ?? array(), $type );
	}

	/** id => human label for a type (Customizer UI). */
	public static function labels( $type = 'project' ) {
		$reg = self::registry();
		return $reg[ $type ]['labels'] ?? array();
	}

	/** The theme wrapper selector the Section Manager targets for a type. */
	public static function container( $type = 'project' ) {
		$reg = self::registry();
		return $reg[ $type ]['container'] ?? '';
	}
}

// Back-compat alias.
class_alias( 'Rafah_Sections', 'Rafah_Project_Sections' );

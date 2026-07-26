<?php
/**
 * Rafah Core — Polylang compatibility.
 * Makes Projects, Agents, Testimonials and all custom taxonomies translatable.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Polylang {

	public static function init() {
		add_filter( 'pll_get_post_types', array( __CLASS__, 'post_types' ), 10, 2 );
		add_filter( 'pll_get_taxonomies', array( __CLASS__, 'taxonomies' ), 10, 2 );
		add_filter( 'pll_copy_post_metas', array( __CLASS__, 'copy_metas' ) );
	}

	public static function post_types( $post_types, $is_settings ) {
		$post_types['project']     = 'project';
		$post_types['agent']       = 'agent';
		$post_types['testimonial'] = 'testimonial';

		return $post_types;
	}

	public static function taxonomies( $taxonomies, $is_settings ) {
		foreach ( array( 'city', 'district', 'project_type', 'feature', 'amenity' ) as $taxonomy ) {
			$taxonomies[ $taxonomy ] = $taxonomy;
		}

		return $taxonomies;
	}

	/**
	 * Copy Rafah meta fields when creating a translation, so editors only
	 * translate text — prices, galleries, coordinates carry over automatically.
	 */
	public static function copy_metas( $metas ) {
		$keys = array(
			'status', 'show_status_badge', 'custom_badge_text', 'featured', 'completion', 'delivery_date', 'developer', 'consultant', 'contractor',
			'address', 'map_url', 'lat', 'lng',
			'price_from', 'price_to', 'currency', 'payment_plans', 'mortgage_info',
			'area_from', 'area_to', 'unit_types', 'bedrooms_from', 'bedrooms_to',
			'bathrooms_from', 'bathrooms_to', 'parking', 'buildings', 'floors',
			'units_total', 'units_available',
			'gallery', 'gallery_position', 'card_cover', 'hero_cover', 'video_url', 'tour_url', 'brochure',
			'floor_plans', 'nearby', 'downloads',
			'agent_id', 'phone', 'whatsapp', 'form_shortcode',
			'position', 'experience_years', 'license_no', 'languages', 'specialties',
			'email', 'meeting_url',
			'social_x', 'social_instagram', 'social_linkedin', 'social_snapchat', 'social_tiktok',
			'client_role', 'rating', 'project_id',
		);

		foreach ( $keys as $key ) {
			$metas[] = '_rafah_' . $key;
		}

		// Elementor layout data: copying it means a new translation starts as
		// an exact visual copy of the original — editors only translate text.
		// Harmless when Elementor is inactive (keys simply don't exist).
		$metas[] = '_elementor_data';
		$metas[] = '_elementor_edit_mode';
		$metas[] = '_elementor_template_type';
		$metas[] = '_elementor_version';
		$metas[] = '_elementor_page_settings';

		return $metas;
	}
}

<?php
/**
 * Rafah Core — Custom Taxonomies.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Taxonomies {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		/** Filter the taxonomy definitions — add or adjust taxonomies from a companion plugin. */
		$taxonomies = apply_filters( 'rafah_taxonomies', array(
			'city'           => array(
				'label'        => __( 'Cities', 'rafah' ),
				'singular'     => __( 'City', 'rafah' ),
				'hierarchical' => true,
				'slug'         => 'city',
			),
			'district'       => array(
				'label'        => __( 'Districts', 'rafah' ),
				'singular'     => __( 'District', 'rafah' ),
				'hierarchical' => true,
				'slug'         => 'district',
			),
			'project_type'   => array(
				'label'        => __( 'Project Types', 'rafah' ),
				'singular'     => __( 'Type', 'rafah' ),
				'hierarchical' => true,
				'slug'         => 'project-type',
			),
			'feature'        => array(
				'label'        => __( 'Features', 'rafah' ),
				'singular'     => __( 'Feature', 'rafah' ),
				'hierarchical' => false,
				'slug'         => 'feature',
			),
			'amenity'        => array(
				'label'        => __( 'Amenities', 'rafah' ),
				'singular'     => __( 'Amenity', 'rafah' ),
				'hierarchical' => false,
				'slug'         => 'amenity',
			),
		) );

		foreach ( $taxonomies as $taxonomy => $args ) {
			register_taxonomy(
				$taxonomy,
				'project',
				array(
					'labels'            => array(
						'name'          => $args['label'],
						'singular_name' => $args['singular'],
						'menu_name'     => $args['label'],
					),
					'hierarchical'      => $args['hierarchical'],
					'public'            => true,
					'show_admin_column' => in_array( $taxonomy, array( 'city', 'project_type' ), true ),
					'show_in_rest'      => true,
					'rewrite'           => array( 'slug' => $args['slug'], 'with_front' => false ),
				)
			);
		}

		self::seed_default_terms();
	}

	/**
	 * Seed default statuses/types once so the dashboard is ready for editors.
	 */
	private static function seed_default_terms() {
		if ( get_option( 'rafah_terms_seeded' ) ) {
			return;
		}

		$defaults = array(
			'project_type'   => array( 'فلل', 'شقق', 'تاون هاوس', 'أراضٍ', 'تجاري' ),
			'city'           => array( 'الرياض', 'جدة', 'مكة المكرمة', 'الدمام', 'الخبر' ),
		);

		foreach ( $defaults as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				if ( ! term_exists( $term, $taxonomy ) ) {
					wp_insert_term( $term, $taxonomy );
				}
			}
		}

		update_option( 'rafah_terms_seeded', 1 );
	}
}

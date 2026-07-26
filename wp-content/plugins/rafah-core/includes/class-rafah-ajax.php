<?php
/**
 * Rafah Core — AJAX project filtering + shared query builder.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Ajax {

	public static function init() {
		add_action( 'wp_ajax_rafah_filter_projects', array( __CLASS__, 'filter_projects' ) );
		add_action( 'wp_ajax_nopriv_rafah_filter_projects', array( __CLASS__, 'filter_projects' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'archive_status_filter' ) );
	}

	/**
	 * Pre-filter the projects archive by ?status={key} so links like
	 * /projects/?status=sold land on already-filtered results (no flash of
	 * unfiltered content). Status is the `_rafah_status` meta field — the single
	 * source of truth. The status tabs then sync via JS.
	 *
	 * @param WP_Query $query Main query.
	 */
	public static function archive_status_filter( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'project' ) ) {
			return;
		}

		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $status || ! array_key_exists( $status, rafah_project_status_options() ) ) {
			return;
		}

		$meta_query   = (array) $query->get( 'meta_query' );
		$meta_query[] = array(
			'key'   => '_rafah_status',
			'value' => $status,
		);

		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Build a WP_Query args array from filter parameters.
	 * Shared by the AJAX endpoint, the archive template, and the filter widget.
	 *
	 * @param array $params Raw filter params (already unslashed).
	 * @return array
	 */
	public static function build_query_args( $params ) {
		$args = array(
			'post_type'      => 'project',
			'post_status'    => 'publish',
			'posts_per_page' => min( 24, absint( $params['per_page'] ?? 9 ) ?: 9 ),
			'paged'          => max( 1, absint( $params['page'] ?? 1 ) ),
		);

		// Language (Polylang handles this automatically on front-end queries,
		// but AJAX requests need it passed explicitly).
		if ( ! empty( $params['lang'] ) && function_exists( 'pll_languages_list' ) ) {
			$args['lang'] = sanitize_key( $params['lang'] );
		}

		// Search.
		if ( ! empty( $params['s'] ) ) {
			$args['s'] = sanitize_text_field( $params['s'] );
		}

		// Taxonomy filters.
		$tax_query = array();
		foreach ( array( 'city', 'district', 'project_type' ) as $taxonomy ) {
			if ( ! empty( $params[ $taxonomy ] ) ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => sanitize_title( $params[ $taxonomy ] ),
				);
			}
		}
		if ( $tax_query ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query']     = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		// Meta filters.
		$meta_query = array();

		// Project status (the single source of truth — the `_rafah_status` meta).
		if ( ! empty( $params['status'] ) ) {
			$status = sanitize_key( $params['status'] );
			if ( array_key_exists( $status, rafah_project_status_options() ) ) {
				$meta_query[] = array(
					'key'   => '_rafah_status',
					'value' => $status,
				);
			}
		}

		if ( ! empty( $params['max_price'] ) ) {
			$meta_query[] = array(
				'key'     => '_rafah_price_from',
				'value'   => (float) $params['max_price'],
				'type'    => 'NUMERIC',
				'compare' => '<=',
			);
		}

		if ( ! empty( $params['min_price'] ) ) {
			$meta_query[] = array(
				'key'     => '_rafah_price_from',
				'value'   => (float) $params['min_price'],
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		}

		if ( ! empty( $params['bedrooms'] ) ) {
			$meta_query[] = array(
				'key'     => '_rafah_bedrooms_to',
				'value'   => absint( $params['bedrooms'] ),
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		}

		if ( ! empty( $params['min_area'] ) ) {
			$meta_query[] = array(
				'key'     => '_rafah_area_to',
				'value'   => (float) $params['min_area'],
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		}

		if ( ! empty( $params['featured'] ) ) {
			$meta_query[] = array(
				'key'   => '_rafah_featured',
				'value' => '1',
			);
		}

		if ( $meta_query ) {
			$meta_query['relation'] = 'AND';
			$args['meta_query']     = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		// Sorting.
		switch ( $params['sort'] ?? '' ) {
			case 'price_asc':
				$args['meta_key'] = '_rafah_price_from'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'ASC';
				break;
			case 'price_desc':
				$args['meta_key'] = '_rafah_price_from'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}

		return $args;
	}

	/**
	 * AJAX endpoint: returns rendered project cards + pagination info.
	 */
	public static function filter_projects() {
		check_ajax_referer( 'rafah_filter', 'nonce' );

		$params = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in build_query_args().
		$query  = new WP_Query( self::build_query_args( $params ) );

		ob_start();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				rafah_project_card( get_the_ID() );
			}
			wp_reset_postdata();
		}

		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'      => $html,
				'found'     => (int) $query->found_posts,
				'max_pages' => (int) $query->max_num_pages,
				'page'      => (int) ( $params['page'] ?? 1 ),
			)
		);
	}
}

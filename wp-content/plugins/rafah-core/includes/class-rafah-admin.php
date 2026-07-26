<?php
/**
 * Rafah Core — Dashboard experience: admin columns, filters, sorting, duplicate.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Admin {

	public static function init() {
		// Project list columns.
		add_filter( 'manage_project_posts_columns', array( __CLASS__, 'project_columns' ) );
		add_action( 'manage_project_posts_custom_column', array( __CLASS__, 'project_column_content' ), 10, 2 );
		add_filter( 'manage_edit-project_sortable_columns', array( __CLASS__, 'project_sortable' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'handle_sorting_and_filters' ) );

		// Agent list columns.
		add_filter( 'manage_agent_posts_columns', array( __CLASS__, 'agent_columns' ) );
		add_action( 'manage_agent_posts_custom_column', array( __CLASS__, 'agent_column_content' ), 10, 2 );

		// Taxonomy filter dropdowns on the Projects list.
		add_action( 'restrict_manage_posts', array( __CLASS__, 'filter_dropdowns' ) );

		// Duplicate action.
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'admin_action_rafah_duplicate', array( __CLASS__, 'duplicate_post' ) );
		add_action( 'admin_notices', array( __CLASS__, 'duplicate_notice' ) );
	}

	// ------------------------------------------------------------- Columns.

	public static function project_columns( $columns ) {
		$new = array(
			'cb'             => $columns['cb'],
			'rafah_thumb'    => __( 'Image', 'rafah' ),
			'title'          => __( 'Project', 'rafah' ),
			'taxonomy-city'  => __( 'City', 'rafah' ),
			'rafah_status'   => __( 'Status', 'rafah' ),
			'taxonomy-project_type'   => __( 'Type', 'rafah' ),
			'rafah_price'    => __( 'Starting Price', 'rafah' ),
			'rafah_progress' => __( 'Completion', 'rafah' ),
			'rafah_featured' => __( 'Featured', 'rafah' ),
			'date'           => $columns['date'],
		);

		return $new;
	}

	public static function project_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'rafah_thumb':
				echo get_the_post_thumbnail( $post_id, array( 60, 45 ), array( 'style' => 'border-radius:6px;object-fit:cover;' ) );
				break;

			case 'rafah_status':
				$status = rafah_project_status( $post_id );
				$dot    = array( 'available' => '#3a9d5d', 'coming_soon' => '#bc945d', 'sold' => '#9a3b3b' );
				printf(
					'<span style="display:inline-flex;align-items:center;gap:7px"><span style="width:9px;height:9px;border-radius:50%%;background:%s"></span>%s</span>',
					esc_attr( $dot[ $status ] ?? '#c9c2b8' ),
					esc_html( rafah_project_status_label( $status ) )
				);
				break;

			case 'rafah_price':
				$price = get_post_meta( $post_id, '_rafah_price_from', true );
				echo $price ? esc_html( number_format( (float) $price ) ) : '—';
				break;

			case 'rafah_progress':
				$pct = (int) get_post_meta( $post_id, '_rafah_completion', true );
				printf(
					'<div class="rafah-progress" title="%1$d%%"><span style="width:%1$d%%"></span></div><small>%1$d%%</small>',
					$pct
				);
				break;

			case 'rafah_featured':
				echo get_post_meta( $post_id, '_rafah_featured', true )
					? '<span class="dashicons dashicons-star-filled" style="color:#bc945d"></span>'
					: '<span class="dashicons dashicons-star-empty" style="color:#c9c2b8"></span>';
				break;
		}
	}

	public static function project_sortable( $columns ) {
		$columns['rafah_price']    = 'rafah_price';
		$columns['rafah_progress'] = 'rafah_progress';

		return $columns;
	}

	public static function agent_columns( $columns ) {
		return array(
			'cb'          => $columns['cb'],
			'rafah_thumb' => __( 'Photo', 'rafah' ),
			'title'       => __( 'Agent', 'rafah' ),
			'rafah_position' => __( 'Position', 'rafah' ),
			'rafah_phone' => __( 'Phone', 'rafah' ),
			'date'        => $columns['date'],
		);
	}

	public static function agent_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'rafah_thumb':
				echo get_the_post_thumbnail( $post_id, array( 48, 48 ), array( 'style' => 'border-radius:50%;object-fit:cover;' ) );
				break;
			case 'rafah_position':
				echo esc_html( get_post_meta( $post_id, '_rafah_position', true ) ?: '—' );
				break;
			case 'rafah_phone':
				echo esc_html( get_post_meta( $post_id, '_rafah_phone', true ) ?: '—' );
				break;
		}
	}

	// ------------------------------------------------------------- Sorting & filters.

	public static function handle_sorting_and_filters( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'rafah_price' === $orderby ) {
			$query->set( 'meta_key', '_rafah_price_from' );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( 'rafah_progress' === $orderby ) {
			$query->set( 'meta_key', '_rafah_completion' );
			$query->set( 'orderby', 'meta_value_num' );
		}

		// Projects list "Status" filter dropdown (meta-based, not a taxonomy).
		if ( 'project' === $query->get( 'post_type' ) && ! empty( $_GET['rafah_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$status = sanitize_key( wp_unslash( $_GET['rafah_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( array_key_exists( $status, rafah_project_status_options() ) ) {
				$meta_query   = (array) $query->get( 'meta_query' );
				$meta_query[] = array( 'key' => '_rafah_status', 'value' => $status );
				$query->set( 'meta_query', $meta_query );
			}
		}
	}

	public static function filter_dropdowns( $post_type ) {
		if ( 'project' !== $post_type ) {
			return;
		}

		foreach ( array( 'city', 'project_type' ) as $taxonomy ) {
			$tax_obj  = get_taxonomy( $taxonomy );
			$selected = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

			wp_dropdown_categories(
				array(
					'show_option_all' => $tax_obj->labels->name,
					'taxonomy'        => $taxonomy,
					'name'            => $taxonomy,
					'selected'        => $selected,
					'value_field'     => 'slug',
					'hide_empty'      => false,
					'hierarchical'    => true,
				)
			);
		}

		// Project status — the fixed `_rafah_status` meta enum (not a taxonomy).
		$status_selected = isset( $_GET['rafah_status'] ) ? sanitize_key( wp_unslash( $_GET['rafah_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<select name="rafah_status">';
		printf( '<option value="">%s</option>', esc_html( rafah_text( 'all_statuses' ) ) );
		foreach ( rafah_project_status_options() as $status_key => $status_label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $status_key ),
				selected( $status_selected, $status_key, false ),
				esc_html( $status_label )
			);
		}
		echo '</select>';
	}

	// ------------------------------------------------------------- Duplicate.

	public static function row_actions( $actions, $post ) {
		if ( in_array( $post->post_type, array( 'project', 'agent', 'testimonial' ), true ) && current_user_can( 'edit_post', $post->ID ) ) {
			$url = wp_nonce_url(
				admin_url( 'admin.php?action=rafah_duplicate&post=' . $post->ID ),
				'rafah_duplicate_' . $post->ID
			);

			$actions['rafah_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'rafah' ) . '</a>';
		}

		return $actions;
	}

	public static function duplicate_post() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! $post_id || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'rafah_duplicate_' . $post_id ) ) {
			wp_die( esc_html__( 'Invalid duplicate request.', 'rafah' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You are not allowed to duplicate this item.', 'rafah' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( esc_html__( 'Item not found.', 'rafah' ) );
		}

		$new_id = wp_insert_post(
			array(
				'post_title'   => $post->post_title . ' (' . __( 'Copy', 'rafah' ) . ')',
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'post_type'    => $post->post_type,
				'post_status'  => 'draft',
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			wp_die( esc_html( $new_id->get_error_message() ) );
		}

		// Copy taxonomies.
		foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $terms ) ) {
				wp_set_object_terms( $new_id, $terms, $taxonomy );
			}
		}

		// Copy meta (including featured image and all _rafah_ fields).
		$meta = get_post_meta( $post_id );
		foreach ( $meta as $meta_key => $values ) {
			if ( in_array( $meta_key, array( '_edit_lock', '_edit_last' ), true ) ) {
				continue;
			}
			foreach ( $values as $meta_value ) {
				add_post_meta( $new_id, $meta_key, maybe_unserialize( $meta_value ) );
			}
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . $post->post_type . '&rafah_duplicated=1' ) );
		exit;
	}

	public static function duplicate_notice() {
		if ( ! empty( $_GET['rafah_duplicated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Item duplicated as a draft.', 'rafah' ) . '</p></div>';
		}
	}
}

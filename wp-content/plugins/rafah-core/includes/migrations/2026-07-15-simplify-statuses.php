<?php
/**
 * Migration: consolidate project statuses to three — متاحة (Available),
 * قريباً (Upcoming), مباعة (Sold). Maps legacy terms:
 *   جاهز للسكن   → متاحة
 *   قيد الإنشاء  → قريباً
 *   مباع بالكامل → مباعة
 * Reassigns every project, remaps Status-Cover term references, then deletes
 * the old terms. Idempotent — once the old terms are gone it is a no-op.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

return array(
	'id'          => '2026-07-15-simplify-statuses',
	'description' => 'Consolidate project statuses to Available / Upcoming / Sold.',
	'run'         => function () {
		$tax = 'project_status';

		// Ensure the 3 canonical statuses exist.
		$targets = array();
		foreach ( array( 'متاحة', 'قريباً', 'مباعة' ) as $name ) {
			$term = get_term_by( 'name', $name, $tax );
			if ( $term && ! is_wp_error( $term ) ) {
				$targets[ $name ] = (int) $term->term_id;
			} else {
				$res = wp_insert_term( $name, $tax );
				$targets[ $name ] = ! is_wp_error( $res ) ? (int) $res['term_id'] : 0;
			}
		}

		$map = array(
			'جاهز للسكن'   => 'متاحة',
			'قيد الإنشاء'  => 'قريباً',
			'مباع بالكامل' => 'مباعة',
		);

		$id_remap = array(); // old term id => new term id.

		foreach ( $map as $from_name => $to_name ) {
			$from  = get_term_by( 'name', $from_name, $tax );
			$to_id = $targets[ $to_name ] ?? 0;

			if ( ! $from || is_wp_error( $from ) || ! $to_id || (int) $from->term_id === $to_id ) {
				continue;
			}

			$from_id            = (int) $from->term_id;
			$id_remap[ $from_id ] = $to_id;

			$pids = get_posts( array(
				'post_type'   => 'project',
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
				'tax_query'   => array( array( 'taxonomy' => $tax, 'field' => 'term_id', 'terms' => $from_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			) );

			foreach ( $pids as $pid ) {
				wp_set_object_terms( $pid, $to_id, $tax, false );
			}

			wp_delete_term( $from_id, $tax );
		}

		// Remap Status-Cover term references (they store term IDs).
		if ( $id_remap ) {
			$cover_projects = get_posts( array(
				'post_type'   => 'project',
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
				'meta_key'    => '_rafah_status_covers', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			) );

			foreach ( $cover_projects as $pid ) {
				$covers = get_post_meta( $pid, '_rafah_status_covers', true );
				if ( ! is_array( $covers ) ) {
					continue;
				}

				$changed = false;
				foreach ( $covers as &$cover ) {
					$sid = (int) ( $cover['status'] ?? 0 );
					if ( isset( $id_remap[ $sid ] ) ) {
						$cover['status'] = $id_remap[ $sid ];
						$changed         = true;
					}
				}
				unset( $cover );

				if ( $changed ) {
					update_post_meta( $pid, '_rafah_status_covers', $covers );
				}
			}
		}

		flush_rewrite_rules( false );
	},
);

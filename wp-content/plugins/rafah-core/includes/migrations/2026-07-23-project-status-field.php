<?php
/**
 * Migration: move Project Status from the `project_status` taxonomy to the
 * single `_rafah_status` meta field (the new single source of truth), and
 * remove the old Status Covers data.
 *
 * For every project:
 *   1. If `_rafah_status` is not set yet, derive it from the old taxonomy term
 *      (متاحة → available, قريباً → coming_soon, مباعة → sold, plus legacy
 *      names), defaulting to `available` when there is no term.
 *   2. Delete the obsolete `_rafah_status_covers` meta.
 * Finally, delete the `project_status` terms + their relationships so no stale
 * taxonomy data lingers.
 *
 * Idempotent: once statuses are migrated and the terms are gone it is a no-op.
 * The taxonomy is registered temporarily inside this run because Rafah Core no
 * longer registers it, yet the old term relationships still need to be read.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

return array(
	'id'          => '2026-07-23-project-status-field',
	'description' => 'Move project status to the _rafah_status meta field; remove Status Covers + the project_status taxonomy data.',
	'run'         => function () {
		$tax = 'project_status';

		// The old taxonomy is no longer registered — register it temporarily so
		// we can still read the existing term relationships during this request.
		if ( ! taxonomy_exists( $tax ) ) {
			register_taxonomy( $tax, 'project' );
		}

		// Old term name → canonical status key.
		$name_map = array(
			// Current three (post 2026-07-15 consolidation).
			'متاحة'        => 'available',
			'قريباً'       => 'coming_soon',
			'مباعة'        => 'sold',
			// Legacy names, just in case a site never ran the earlier migration.
			'جاهز للسكن'   => 'available',
			'قيد الإنشاء'  => 'coming_soon',
			'مباع بالكامل' => 'sold',
			// English fallbacks.
			'available'    => 'available',
			'coming soon'  => 'coming_soon',
			'sold'         => 'sold',
		);

		$valid = array( 'available', 'coming_soon', 'sold' );

		$project_ids = get_posts( array(
			'post_type'   => 'project',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
		) );

		foreach ( $project_ids as $pid ) {
			$pid = (int) $pid;

			// 1) Derive + set the status meta (only if not already set).
			$existing = get_post_meta( $pid, '_rafah_status', true );
			if ( ! in_array( $existing, $valid, true ) ) {
				$key   = 'available';
				$terms = get_the_terms( $pid, $tax );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$name = trim( (string) $terms[0]->name );
					if ( isset( $name_map[ $name ] ) ) {
						$key = $name_map[ $name ];
					} else {
						$lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $name ) : strtolower( $name );
						$key   = $name_map[ $lower ] ?? 'available';
					}
				}
				update_post_meta( $pid, '_rafah_status', $key );
			}

			// 2) Remove the obsolete Status Covers meta.
			delete_post_meta( $pid, '_rafah_status_covers' );
		}

		// Delete the old taxonomy terms + relationships (clean removal).
		$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				wp_delete_term( (int) $term->term_id, $tax );
			}
		}
	},
);

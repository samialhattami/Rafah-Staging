<?php
/**
 * Rafah Core — Uninstall handler.
 *
 * DATA-PRESERVING BY DESIGN.
 *
 * Deleting the plugin removes plugin files only. All projects, agents,
 * testimonials, taxonomies, and `_rafah_*` meta remain in the database, so
 * reinstalling the plugin restores the site exactly as it was.
 *
 * Full data removal is deliberately opt-in: a site owner must add
 *     define( 'RAFAH_REMOVE_ALL_DATA', true );
 * to wp-config.php BEFORE deleting the plugin. Without that constant, this
 * file only removes small housekeeping options.
 *
 * @package Rafah_Core
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Always safe to remove: housekeeping options.
delete_option( 'rafah_core_flush_rewrites' );

if ( ! defined( 'RAFAH_REMOVE_ALL_DATA' ) || ! RAFAH_REMOVE_ALL_DATA ) {
	return; // Preserve everything else.
}

// ---------------------------------------------------------------- Opt-in wipe.

delete_option( 'rafah_core_version' );
delete_option( 'rafah_terms_seeded' );
delete_option( 'rafah_settings' );

// Units table (opt-in wipe only).
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rafah_units" ); // phpcs:ignore

// Custom post types and taxonomies are NOT registered during uninstall,
// so query the tables directly, then delete through core APIs.
global $wpdb;

// Remove all content of Rafah post types.
$rafah_post_ids = $wpdb->get_col(
	"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ( 'project', 'agent', 'testimonial' )"
);

foreach ( $rafah_post_ids as $rafah_post_id ) {
	wp_delete_post( (int) $rafah_post_id, true );
}

// Remove taxonomy terms.
$rafah_tt_rows = $wpdb->get_results(
	"SELECT term_id, taxonomy FROM {$wpdb->term_taxonomy}
	 WHERE taxonomy IN ( 'city', 'district', 'project_type', 'feature', 'amenity' )"
);

foreach ( $rafah_tt_rows as $rafah_tt_row ) {
	register_taxonomy( $rafah_tt_row->taxonomy, 'post' ); // Temporary registration so wp_delete_term() works.
	wp_delete_term( (int) $rafah_tt_row->term_id, $rafah_tt_row->taxonomy );
}

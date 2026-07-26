<?php
/**
 * Taxonomy archives — project taxonomies reuse the premium projects listing.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

$rafah_project_taxonomies = array( 'city', 'district', 'project_type', 'feature', 'amenity' );

if ( is_tax( $rafah_project_taxonomies ) ) {
	require get_stylesheet_directory() . '/archive-project.php';
	return;
}

// Fallback to Astra's default archive rendering for any other taxonomy.
require get_template_directory() . '/archive.php';

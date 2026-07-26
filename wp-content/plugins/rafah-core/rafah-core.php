<?php
/**
 * Plugin Name:       Rafah Core
 * Plugin URI:        https://rafah.sa
 * Description:       Core platform for Rafah Real Estate — Projects, Agents, Testimonials, advanced filtering, schema, and premium Elementor widgets. Required for rafah.sa.
 * Version:           1.23.3
 * Author:            Rafah
 * Author URI:        https://rafah.sa
 * Text Domain:       rafah
 * Domain Path:       /languages
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * License:           Proprietary
 *
 * ARCHITECTURE
 * ------------
 * Modular, hook-driven, additive-only. Rafah Core never modifies WordPress core,
 * Elementor, Astra, or third-party plugin files — it uses only official WordPress
 * APIs and Elementor's public widget API. See DEVELOPER.md for the module map,
 * extension hooks, and upgrade/migration policy.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'RAFAH_CORE_VERSION', '1.23.3' );
define( 'RAFAH_CORE_FILE', __FILE__ );
define( 'RAFAH_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'RAFAH_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load includes.
 */
require_once RAFAH_CORE_PATH . 'includes/helpers.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-admin-i18n.php';
require_once RAFAH_CORE_PATH . 'includes/fields-config.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-upgrades.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-migrations.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-settings.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-post-types.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-project-sections.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-taxonomies.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-meta-boxes.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-admin.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-schema.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-ajax.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-polylang.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-assets.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-gallery.php';
require_once RAFAH_CORE_PATH . 'includes/class-rafah-editor.php';
require_once RAFAH_CORE_PATH . 'includes/content-sections.php';
require_once RAFAH_CORE_PATH . 'includes/units/class-rafah-units-db.php';
require_once RAFAH_CORE_PATH . 'includes/units/class-rafah-units-columns.php';
require_once RAFAH_CORE_PATH . 'includes/units/class-rafah-units-admin.php';
require_once RAFAH_CORE_PATH . 'includes/units/class-rafah-units-io.php';
require_once RAFAH_CORE_PATH . 'includes/units/class-rafah-units-frontend.php';
require_once RAFAH_CORE_PATH . 'includes/elementor/class-rafah-repeaters.php';
require_once RAFAH_CORE_PATH . 'includes/elementor/class-rafah-style-controls.php';
require_once RAFAH_CORE_PATH . 'includes/elementor/class-rafah-elementor.php';

/**
 * Boot the plugin through the module registry.
 *
 * Every module is a class with a static init() method. Site-specific code
 * (a child theme or companion plugin) can add or remove modules via the
 * `rafah_core_modules` filter without touching this plugin.
 */
function rafah_core_init() {
	$modules = apply_filters(
		'rafah_core_modules',
		array(
			'upgrades'   => 'Rafah_Upgrades',
			'migrations' => 'Rafah_Migrations',
			'admin_i18n' => 'Rafah_Admin_I18n',
			'settings'   => 'Rafah_Settings',
			'post_types' => 'Rafah_Post_Types',
			'sections'   => 'Rafah_Sections',
			'taxonomies' => 'Rafah_Taxonomies',
			'meta_boxes' => 'Rafah_Meta_Boxes',
			'admin'      => 'Rafah_Admin',
			'schema'     => 'Rafah_Schema',
			'ajax'       => 'Rafah_Ajax',
			'polylang'   => 'Rafah_Polylang',
			'assets'     => 'Rafah_Assets',
			'gallery'    => 'Rafah_Gallery',
			'editor'     => 'Rafah_Editor',
			'units_db'       => 'Rafah_Units_DB',
			'units_columns'  => 'Rafah_Units_Columns',
			'units_admin'    => 'Rafah_Units_Admin',
			'units_io'       => 'Rafah_Units_IO',
			'units_frontend' => 'Rafah_Units_Frontend',
			'elementor'  => 'Rafah_Elementor',
		)
	);

	foreach ( $modules as $class ) {
		if ( class_exists( $class ) && is_callable( array( $class, 'init' ) ) ) {
			$class::init();
		}
	}

	/**
	 * Fires after all Rafah Core modules are initialised.
	 * Use this to bootstrap extensions that depend on Rafah Core.
	 */
	do_action( 'rafah_core_loaded' );
}
add_action( 'plugins_loaded', 'rafah_core_init' );

/**
 * Activation: register content types, mark rewrite flush, store install version.
 */
register_activation_hook( __FILE__, function () {
	Rafah_Post_Types::register();
	Rafah_Taxonomies::register();
	flush_rewrite_rules();

	if ( ! get_option( 'rafah_core_version' ) ) {
		add_option( 'rafah_core_version', RAFAH_CORE_VERSION );
	}
} );

/**
 * Deactivation: flush rewrites only. All content and settings are preserved.
 */
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

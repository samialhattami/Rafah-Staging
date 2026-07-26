<?php
/**
 * Rafah Core — Versioned upgrade routines (FROZEN — historical only).
 *
 * ⚠️ Do NOT add new entries to $migrations below. This method-based system is
 * kept ONLY to run the historical 1.1.0–1.3.0 data migrations on any site that
 * still predates them, and to advance the `rafah_core_version` checkpoint.
 *
 * ➡️ ALL NEW data changes go in `includes/migrations/` as dated files handled
 *    by Rafah_Migrations (file-based, self-contained, easy to review). That is
 *    the single canonical migration path going forward. See DEVELOPER.md.
 *
 * POLICY that both systems share (do not break):
 * 1. ADDITIVE ONLY — never delete projects, agents, testimonials, taxonomies,
 *    or any `_rafah_*` post meta.
 * 2. IDEMPOTENT — safe to run twice.
 * 3. Run in order; the stored checkpoint only advances after success.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Upgrades {

	const OPTION = 'rafah_core_version';

	/**
	 * FROZEN historical map of plugin version => migration method.
	 * Do not append — new migrations live in includes/migrations/ (Rafah_Migrations).
	 *
	 * @var array<string,string>
	 */
	private static $migrations = array(
		'1.1.0' => 'migrate_1_1_0',
		'1.2.0' => 'migrate_1_2_0',
		'1.3.0' => 'migrate_1_3_0',
	);

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 5 );
		add_action( 'admin_notices', array( __CLASS__, 'migration_error_notice' ) );
	}

	/**
	 * Run pending migrations when the plugin version changed.
	 *
	 * PRODUCTION SAFETY:
	 * - Plugin settings are snapshotted before anything runs.
	 * - Each migration is wrapped in try/catch: on failure the settings
	 *   snapshot is restored, the stored version is NOT bumped past the
	 *   failure (so the migration retries after a fix), the error is
	 *   logged, an admin notice is shown — and the site stays online.
	 * - Migrations themselves are additive-only by policy (see header).
	 */
	public static function maybe_upgrade() {
		$installed = get_option( self::OPTION, '1.0.0' );

		if ( version_compare( $installed, RAFAH_CORE_VERSION, '>=' ) ) {
			return;
		}

		// Snapshot restorable state before touching anything.
		$snapshot = array(
			'version'  => $installed,
			'settings' => get_option( 'rafah_settings' ),
			'time'     => current_time( 'mysql' ),
		);
		update_option( 'rafah_core_pre_upgrade_backup', $snapshot, false );

		foreach ( self::$migrations as $version => $method ) {
			if ( version_compare( $installed, $version, '<' ) && is_callable( array( __CLASS__, $method ) ) ) {
				try {
					self::$method();
				} catch ( \Throwable $e ) {
					// Roll back settings, keep the site online, log, notify.
					if ( false !== $snapshot['settings'] ) {
						update_option( 'rafah_settings', $snapshot['settings'] );
					}

					update_option( 'rafah_core_migration_error', array(
						'version' => $version,
						'message' => $e->getMessage(),
						'time'    => current_time( 'mysql' ),
					), false );

					error_log( sprintf( '[Rafah Core] Migration %s failed and was rolled back: %s', $version, $e->getMessage() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions

					return; // Stored version stays before the failed step.
				}

				update_option( self::OPTION, $version );
				$installed = $version;
			}
		}

		update_option( self::OPTION, RAFAH_CORE_VERSION );
		delete_option( 'rafah_core_migration_error' );

		// Rewrite rules may depend on CPT changes; flush once, on the next request,
		// after post types have been registered.
		update_option( 'rafah_core_flush_rewrites', 1 );

		/**
		 * Fires after Rafah Core finishes upgrading.
		 *
		 * @param string $installed New version.
		 */
		do_action( 'rafah_core_upgraded', RAFAH_CORE_VERSION );
	}

	/**
	 * Surface a failed migration to administrators.
	 */
	public static function migration_error_notice() {
		$error = get_option( 'rafah_core_migration_error' );

		if ( ! $error || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>Rafah Core:</strong> %s</p></div>',
			esc_html( sprintf(
				/* translators: 1: version, 2: error message */
				__( 'Migration to %1$s failed and was rolled back — the site is running normally on the previous data version. Error: %2$s', 'rafah' ),
				$error['version'],
				$error['message']
			) )
		);
	}

	/**
	 * 1.1.0 — introduce versioned upgrades. No data changes; establishes the
	 * baseline version option for installs upgraded from 1.0.0.
	 */
	private static function migrate_1_1_0() {
		// Intentionally empty: 1.0.x data is fully compatible.
	}

	/**
	 * 1.2.0 — seed default global settings (animations). Additive only:
	 * add_option() never overwrites an existing option.
	 */
	private static function migrate_1_2_0() {
		if ( class_exists( 'Rafah_Settings' ) ) {
			add_option( Rafah_Settings::OPTION, Rafah_Settings::defaults() );
		}
	}

	/**
	 * 1.3.0 — create the units table (dbDelta is idempotent; the table is
	 * never dropped or truncated by any future migration).
	 */
	private static function migrate_1_3_0() {
		if ( class_exists( 'Rafah_Units_DB' ) ) {
			Rafah_Units_DB::install();
		}
	}
}

/**
 * Deferred rewrite flush (runs after CPTs are registered).
 */
add_action( 'wp_loaded', function () {
	if ( get_option( 'rafah_core_flush_rewrites' ) ) {
		delete_option( 'rafah_core_flush_rewrites' );
		flush_rewrite_rules();
	}
} );

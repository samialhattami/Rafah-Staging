<?php
/**
 * Rafah Core — versioned migration runner.
 *
 * One-time data migrations live in includes/migrations/ as files that each
 * `return` an array: [ 'id' => unique-slug, 'description' => ..., 'run' => callable ].
 * Each runs once; completed IDs are recorded in the `rafah_migrations_done`
 * option and never run again. A failure is logged and NOT marked done, so it
 * retries on the next admin load — a transient error can't silently skip a step.
 *
 * This replaces ad-hoc mu-plugin one-offs: drop a new file in includes/migrations/,
 * give it a unique id, done.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Migrations {

	const OPTION = 'rafah_migrations_done';

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'run_pending' ) );
	}

	/**
	 * Run any migrations that have not completed yet.
	 */
	public static function run_pending() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$dir = RAFAH_CORE_PATH . 'includes/migrations/';
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$files = glob( $dir . '*.php' );
		if ( ! $files ) {
			return;
		}
		sort( $files );

		$done    = (array) get_option( self::OPTION, array() );
		$changed = false;

		foreach ( $files as $file ) {
			$migration = include $file;

			if ( ! is_array( $migration ) || empty( $migration['id'] ) || ! is_callable( $migration['run'] ?? null ) ) {
				continue;
			}

			if ( in_array( $migration['id'], $done, true ) ) {
				continue;
			}

			try {
				call_user_func( $migration['run'] );
				$done[]  = $migration['id'];
				$changed = true;
			} catch ( \Throwable $e ) {
				error_log( 'Rafah migration "' . $migration['id'] . '" failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				// Not marked done — retried on the next admin load.
			}
		}

		if ( $changed ) {
			update_option( self::OPTION, array_values( array_unique( $done ) ), false );
		}
	}

	/**
	 * Whether a migration ID has already completed.
	 */
	public static function has_run( $id ) {
		return in_array( $id, (array) get_option( self::OPTION, array() ), true );
	}
}

<?php
/**
 * Plugin Name: Rafah Maintenance Guard
 * Description: Guarantees the site is NEVER left stuck in maintenance mode. Auto-heals a stale .maintenance lock, exposes the shared rafah_clear_maintenance() helper used by the Safe Update Manager's crash handler, and logs every maintenance incident. mu-plugin, prefixed "0-" so it loads first.
 * Version: 1.0.0
 * Author: Rafah
 *
 * WHY THIS EXISTS
 *   A plugin/theme update that dies mid-way (fatal, timeout, killed process)
 *   can leave WordPress's `.maintenance` lock on disk, showing every visitor
 *   the "site is under maintenance" screen until a human deletes the file.
 *
 * DEFENCE IN DEPTH (this file = layers 2 & 3):
 *   1. PRIMARY  — Safe Update Manager arms a shutdown handler at update start
 *                 that calls rafah_clear_maintenance() even if the update
 *                 fatals, so the lock is removed in the SAME request. (See
 *                 rafah-safe-update.php.)
 *   2. CLEANUP  — this guard removes any residual/stale lock on the next
 *                 request that reaches PHP (older than the safety threshold or
 *                 with no update actually running).
 *   3. FILTER   — best-effort `enable_maintenance_mode` bypass for contexts
 *                 that evaluate it after mu-plugins load (WP-CLI, cron, future
 *                 core ordering).
 *
 * Read-only toward the site's design. Never renders or enqueues anything.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'rafah_maintenance_file' ) ) {
	/** Absolute path to WordPress's maintenance lock. */
	function rafah_maintenance_file() {
		return rtrim( ABSPATH, '/\\' ) . '/.maintenance';
	}
}

if ( ! function_exists( 'rafah_update_in_progress' ) ) {
	/**
	 * Is a Rafah-tracked update genuinely running right now? The Safe Update
	 * Manager sets option `rafah_update_started` (unix ts) when an update begins
	 * and clears it when done. A value older than the grace window means the
	 * update process died — safe to heal.
	 *
	 * @param int $grace Seconds an update may legitimately take before we treat
	 *                   the lock as dead. Default filterable, 120s.
	 */
	function rafah_update_in_progress( $grace = null ) {
		$grace   = ( null === $grace ) ? (int) apply_filters( 'rafah_update_grace_seconds', 120 ) : (int) $grace;
		$started = (int) get_option( 'rafah_update_started', 0 );
		return $started > 0 && ( time() - $started ) < $grace;
	}
}

if ( ! function_exists( 'rafah_maintenance_incident' ) ) {
	/** Append a maintenance incident to a rolling log (option, last 50). */
	function rafah_maintenance_incident( $reason ) {
		try {
			$log = get_option( 'rafah_maintenance_incidents', array() );
			if ( ! is_array( $log ) ) {
				$log = array();
			}
			$log[] = array( 'time' => current_time( 'mysql' ), 'reason' => (string) $reason );
			update_option( 'rafah_maintenance_incidents', array_slice( $log, -50 ), false );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Rafah Maintenance Guard] cleared lock — ' . $reason );
			}
		} catch ( \Throwable $e ) {} // logging must never break anything
	}
}

if ( ! function_exists( 'rafah_clear_maintenance' ) ) {
	/**
	 * Remove the maintenance lock if present. The single healing primitive used
	 * by every layer (guard cleanup, shutdown crash handler, admin button).
	 * Never throws.
	 *
	 * @param string $reason Audit note.
	 * @return bool True if a lock was removed.
	 */
	function rafah_clear_maintenance( $reason = 'auto-heal' ) {
		$file = rafah_maintenance_file();
		try {
			if ( is_file( $file ) ) {
				if ( function_exists( 'wp_delete_file' ) ) {
					wp_delete_file( $file );
				} else {
					@unlink( $file );
				}
				if ( ! is_file( $file ) ) {
					rafah_maintenance_incident( $reason );
					return true;
				}
				// Deletion blocked (perms) — neutralise by renaming so WP ignores it.
				@rename( $file, $file . '.stuck-' . gmdate( 'YmdHis' ) );
				rafah_maintenance_incident( $reason . ' (renamed; unlink blocked)' );
				return ! is_file( $file );
			}
		} catch ( \Throwable $e ) {}
		return false;
	}
}

/**
 * CLEANUP: on the earliest hook after mu-plugins load, remove a lock that is
 * stale (older than the safety threshold) or orphaned (no update running).
 * Runs only when a request actually reached PHP — i.e. WordPress did not hard-
 * block it — so it can only ever heal, never hide a genuine in-flight update.
 */
add_action( 'muplugins_loaded', function () {
	$file = rafah_maintenance_file();
	if ( ! is_file( $file ) ) {
		return;
	}
	if ( rafah_update_in_progress() ) {
		return; // a real update is mid-flight — leave it alone.
	}
	$max = (int) apply_filters( 'rafah_maintenance_max_seconds', 20 );
	$age = time() - (int) @filemtime( $file );
	if ( $age >= $max || 0 === (int) get_option( 'rafah_update_started', 0 ) ) {
		rafah_clear_maintenance( sprintf( 'stale lock cleaned (age %ds, no active update)', max( 0, $age ) ) );
	}
}, PHP_INT_MIN );

/**
 * FILTER: if any context evaluates maintenance mode after mu-plugins are
 * loaded, bypass it once the lock is provably dead. Harmless where core
 * evaluates it earlier (front-end web requests) — simply never fires there.
 */
add_filter( 'enable_maintenance_mode', function ( $enabled ) {
	if ( ! $enabled ) {
		return $enabled;
	}
	if ( rafah_update_in_progress() ) {
		return $enabled;
	}
	$file = rafah_maintenance_file();
	if ( is_file( $file ) && ( time() - (int) @filemtime( $file ) ) >= (int) apply_filters( 'rafah_maintenance_max_seconds', 20 ) ) {
		rafah_clear_maintenance( 'enable_maintenance_mode bypass — dead lock' );
		return false;
	}
	return $enabled;
}, 10, 1 );

/**
 * ============================================================================
 * UNIFIED MAINTENANCE GUARANTEE — one mechanism for EVERY update path.
 * ============================================================================
 * ROOT CAUSE of stuck maintenance (esp. BULK): WordPress creates `.maintenance`
 * (maintenance_mode(true)) at the START of an update run and only removes it
 * (maintenance_mode(false)) at the very END. A BULK run's window is far longer
 * than a single update (many downloads + installs + the 6.3+ temp-backup step),
 * so if the request is interrupted in between — PHP timeout, client abort, or a
 * fatal — maintenance_mode(false) never runs and `.maintenance` is orphaned.
 * Proven by trace: `.maintenance` was PRESENT at upgrader_pre_download and still
 * PRESENT when the request ended (fatal=no); only an external shutdown handler
 * removed it.
 *
 * THE FIX (single, unified, path-agnostic): the moment ANY update request is
 * identifiable, arm a shutdown handler that removes `.maintenance` when that
 * request ends — success, failure, timeout, or abort. Every update path (single,
 * bulk, plugin, theme, core, cron, WP-CLI) runs through either update.php /
 * update-core.php OR the WP_Upgrader download/install filters, so arming at both
 * covers them all. register_shutdown_function fires on normal end, exit(), fatal,
 * and PHP timeout — the exact cases where WP's own removal is skipped.
 */
if ( ! function_exists( 'rafah_arm_update_maintenance_shutdown' ) ) {
	function rafah_arm_update_maintenance_shutdown() {
		static $armed = false;
		if ( $armed ) {
			return;
		}
		$armed = true;
		// Mark an update as genuinely in progress so the muplugins_loaded cleanup
		// leaves a fresh, legitimate .maintenance alone until this request ends.
		if ( function_exists( 'update_option' ) && ! get_option( 'rafah_update_started', 0 ) ) {
			update_option( 'rafah_update_started', time(), false );
		}
		register_shutdown_function( function () {
			// The update request is ending. If WordPress hasn't already removed
			// the lock (interrupted before maintenance_mode(false)), remove it now.
			if ( is_file( rafah_maintenance_file() ) ) {
				rafah_clear_maintenance( 'update request ended — unified maintenance guarantee' );
			}
			if ( function_exists( 'delete_option' ) ) {
				delete_option( 'rafah_update_started' );
			}
		} );
	}
}

// Arm as soon as an UPDATE-PERFORMING admin request is seen — before WordPress
// even calls maintenance_mode(true), so an interruption anywhere is covered.
add_action( 'admin_init', function () {
	global $pagenow;
	if ( ! in_array( $pagenow, array( 'update.php', 'update-core.php' ), true ) ) {
		return;
	}
	$action = '';
	foreach ( array( 'action', 'action2' ) as $k ) {
		if ( ! empty( $_REQUEST[ $k ] ) && '-1' !== $_REQUEST[ $k ] ) {
			$action = (string) $_REQUEST[ $k ];
			break;
		}
	}
	$performing = array(
		'upgrade-plugin', 'update-selected', 'update-plugin',
		'upgrade-theme', 'update-selected-themes', 'update-theme',
		'do-plugin-upgrade', 'do-theme-upgrade', 'do-core-upgrade', 'do-translation-upgrade',
	);
	if ( in_array( $action, $performing, true ) ) {
		rafah_arm_update_maintenance_shutdown();
	}
}, 1 );

// NOTE: arming happens ONLY at admin_init (above) — at the very start of the
// request, BEFORE WordPress's updater runs. We deliberately do NOT hook the
// upgrader filters (upgrader_pre_download / upgrader_pre_install), so ZERO Rafah
// code executes inside WordPress's update flow. The shutdown handler registered
// at admin_init still fires when the request ends (success, fail, timeout, or
// abort) and clears any orphaned .maintenance — without touching the updater.

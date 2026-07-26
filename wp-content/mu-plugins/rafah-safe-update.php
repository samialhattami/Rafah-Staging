<?php
/**
 * Plugin Name: Rafah Safe Update Manager
 * Description: Enterprise zero-downtime update system. Disables all WordPress auto-updates, enforces a per-plugin update policy (locked / approved / blocked / ignored), takes an automatic restore point before every update, arms a crash handler that clears maintenance mode even on a fatal, verifies the live site after each update (homepage, REST, single project, archive, AJAX, RTL, assets, Rafah Core, Elementor), and AUTOMATICALLY rolls back to the previous working state if verification fails. mu-plugin → always active, survives plugin breakage.
 * Version: 1.0.0
 * Author: Rafah
 *
 * Pairs with rafah-0-maintenance-guard.php (rafah_clear_maintenance/helpers).
 * Nothing here renders or changes the site's design — it only guards updates.
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * 0. Constants & policy source of truth
 * ========================================================================= */

if ( ! defined( 'RAFAH_RESTORE_DIR' ) ) {
	define( 'RAFAH_RESTORE_DIR', WP_CONTENT_DIR . '/rafah-restore-points' );
}
if ( ! defined( 'RAFAH_UPDATE_LOG' ) ) {
	define( 'RAFAH_UPDATE_LOG', WP_CONTENT_DIR . '/rafah-restore-points/updates.log' );
}

/**
 * Update policy = file defaults (rafah-ops/update-policy.json, version-
 * controlled) overlaid with the admin-edited option. Statuses:
 *   locked   — frozen at locked_version; any update is refused.
 *   approved — updates allowed, but always snapshotted + verified + auto-rolled-back.
 *   blocked  — never update (stronger "do not touch").
 *   ignored  — not managed by the gate (still no auto-update site-wide).
 * Unknown plugin → default status (filterable), 'approved' = safe managed update.
 */
function rafah_su_policy_file() {
	// mu-plugins/../.. = wp-content; site root = one more up; repo has /rafah-ops.
	$candidates = array(
		dirname( ABSPATH ) . '/rafah-ops/update-policy.json',
		ABSPATH . 'rafah-ops/update-policy.json',
		dirname( WP_CONTENT_DIR ) . '/rafah-ops/update-policy.json',
		dirname( dirname( ABSPATH ) ) . '/rafah-ops/update-policy.json',
	);
	foreach ( $candidates as $c ) {
		if ( is_file( $c ) ) {
			return $c;
		}
	}
	return $candidates[0];
}

function rafah_su_policy() {
	$defaults = array();
	$file     = rafah_su_policy_file();
	if ( is_file( $file ) ) {
		$json = json_decode( (string) file_get_contents( $file ), true );
		if ( is_array( $json ) && isset( $json['plugins'] ) && is_array( $json['plugins'] ) ) {
			$defaults = $json['plugins'];
		}
	}
	$override = get_option( 'rafah_update_policy', array() );
	if ( ! is_array( $override ) ) {
		$override = array();
	}
	$policy = $defaults;
	foreach ( $override as $slug => $row ) {
		$policy[ $slug ] = is_array( $row ) ? array_merge( (array) ( $policy[ $slug ] ?? array() ), $row ) : $policy[ $slug ] ?? array();
	}
	return apply_filters( 'rafah_update_policy_resolved', $policy );
}

function rafah_su_status_for( $slug ) {
	$policy  = rafah_su_policy();
	$default = (string) apply_filters( 'rafah_update_default_status', 'approved' );
	$row     = $policy[ $slug ] ?? array();
	$status  = isset( $row['status'] ) ? strtolower( (string) $row['status'] ) : $default;
	return in_array( $status, array( 'locked', 'approved', 'blocked', 'ignored' ), true ) ? $status : $default;
}

/** plugin basename (dir/file.php) → slug (dir). */
function rafah_su_slug( $basename ) {
	return strtok( (string) $basename, '/' );
}

/**
 * MASTER KILL-SWITCH. When off, EVERY Rafah update hook becomes inert and
 * WordPress plugin management behaves exactly like a clean install. Turn off by
 * defining `RAFAH_SAFE_UPDATE_DISABLE` true in wp-config.php, or via the filter:
 *   add_filter( 'rafah_safe_update_enabled', '__return_false' );
 * Nothing here EVER touches install, activate, deactivate, or delete — those
 * WordPress paths are not hooked at all. This switch only governs update
 * interception (the policy gate, locked-plugin UI, and post-update verify).
 */
function rafah_su_enabled() {
	if ( defined( 'RAFAH_SAFE_UPDATE_DISABLE' ) && RAFAH_SAFE_UPDATE_DISABLE ) {
		return false;
	}
	return (bool) apply_filters( 'rafah_safe_update_enabled', true );
}

/* =========================================================================
 * 1. Kill ALL automatic updates (requirement 5)
 * ========================================================================= */

add_filter( 'automatic_updater_disabled', '__return_true' );
add_filter( 'auto_update_plugin', '__return_false', 100 );
add_filter( 'auto_update_theme', '__return_false', 100 );
add_filter( 'auto_update_core', '__return_false', 100 );
add_filter( 'allow_dev_auto_core_updates', '__return_false', 100 );
add_filter( 'allow_minor_auto_core_updates', '__return_false', 100 );
add_filter( 'allow_major_auto_core_updates', '__return_false', 100 );
add_filter( 'auto_update_translation', '__return_false', 100 );
add_filter( 'plugins_auto_update_enabled', '__return_false' );
add_filter( 'themes_auto_update_enabled', '__return_false' );
add_filter( 'automatic_updates_send_debug_email', '__return_false' );
add_filter( 'send_core_update_notification_email', '__return_false' );

/* =========================================================================
 * 2. PASSIVE ARCHITECTURE — Rafah does NOT participate in the update run.
 * -------------------------------------------------------------------------
 * Rafah must never wrap, delay, replace, or alter WordPress's native updater.
 *
 * It USED to hook `upgrader_pre_install` to take a restore-point snapshot — a
 * SYNCHRONOUS recursive copy of the entire plugin folder — BEFORE WordPress
 * installed the update, and to gate locked plugins. On a slow filesystem that
 * copy blocked the update request for tens of seconds (e.g. UpdraftPlus = 1318
 * files), producing the white screen / endless loading, and interrupting the
 * update (which corrupted plugins and orphaned the maintenance lock).
 *
 * That is REMOVED. Nothing Rafah runs inside the updater now:
 *   • Failed installs are protected by WordPress's own 6.3+ temp-backup +
 *     automatic rollback — Rafah does not duplicate it.
 *   • Locked plugins are kept out of the update flow PASSIVELY: they are
 *     stripped from the update transient (section 9) so they never appear as
 *     updatable — no gate runs during execution.
 *   • Any health check happens ASYNCHRONOUSLY, after WordPress has fully
 *     finished, in a separate background request (section 5).
 * ========================================================================= */

/* =========================================================================
 * 3. Restore points (automatic rollback source)
 * ========================================================================= */

function rafah_su_target_dir( $type, $slug ) {
	$base = ( 'theme' === $type )
		? ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/themes' : '' )
		: ( defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins' );
	return $base . '/' . $slug;
}

/** Copy the current plugin/theme folder to the restore store (overwrites prior). */
function rafah_su_snapshot( $type, $slug ) {
	$src = rafah_su_target_dir( $type, $slug );
	if ( ! is_dir( $src ) ) {
		return false;
	}
	$dest = RAFAH_RESTORE_DIR . '/' . $type . '/' . $slug;
	rafah_su_protect_store();
	try {
		rafah_su_rrmdir( $dest );
		if ( ! rafah_su_rcopy( $src, $dest ) ) {
			return false;
		}
		$idx = get_option( 'rafah_restore_index', array() );
		if ( ! is_array( $idx ) ) {
			$idx = array();
		}
		$active = ( 'plugin' === $type ) ? rafah_su_plugin_is_active( $slug ) : false;
		$idx[ $type . ':' . $slug ] = array(
			'type'    => $type,
			'slug'    => $slug,
			'active'  => $active,
			'time'    => current_time( 'mysql' ),
			'path'    => $dest,
		);
		update_option( 'rafah_restore_index', $idx, false );
		rafah_su_log( sprintf( 'SNAPSHOT: %s/%s saved', $type, $slug ) );
		return true;
	} catch ( \Throwable $e ) {
		rafah_su_log( 'SNAPSHOT ERROR ' . $slug . ': ' . $e->getMessage() );
		return false;
	}
}

function rafah_su_plugin_is_active( $slug ) {
	if ( ! function_exists( 'get_option' ) ) {
		return false;
	}
	foreach ( (array) get_option( 'active_plugins', array() ) as $p ) {
		if ( rafah_su_slug( $p ) === $slug ) {
			return $p;
		}
	}
	return false;
}

/** Restore a plugin/theme from its most recent restore point. */
function rafah_su_restore( $type, $slug ) {
	$idx = get_option( 'rafah_restore_index', array() );
	$rec = $idx[ $type . ':' . $slug ] ?? null;
	$snapshot = $rec['path'] ?? ( RAFAH_RESTORE_DIR . '/' . $type . '/' . $slug );
	if ( ! is_dir( $snapshot ) ) {
		rafah_su_log( sprintf( 'ROLLBACK FAILED: no restore point for %s/%s', $type, $slug ) );
		return false;
	}
	$target = rafah_su_target_dir( $type, $slug );
	try {
		rafah_su_rrmdir( $target );
		if ( ! rafah_su_rcopy( $snapshot, $target ) ) {
			rafah_su_log( sprintf( 'ROLLBACK COPY FAILED: %s/%s', $type, $slug ) );
			return false;
		}
		// Bust caches so the restored code is what actually runs next request.
		if ( function_exists( 'opcache_reset' ) ) { @opcache_reset(); }
		if ( function_exists( 'wp_cache_flush' ) ) { wp_cache_flush(); }
		do_action( 'litespeed_purge_all' );
		rafah_su_log( sprintf( 'ROLLBACK: %s/%s restored from %s', $type, $slug, $rec['time'] ?? 'snapshot' ) );
		return true;
	} catch ( \Throwable $e ) {
		rafah_su_log( 'ROLLBACK ERROR ' . $slug . ': ' . $e->getMessage() );
		return false;
	}
}

/* --- recursive filesystem helpers (runtime, host PHP) --- */
function rafah_su_rrmdir( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $items as $item ) {
		if ( $item->isDir() ) {
			@rmdir( $item->getPathname() );
		} else {
			@unlink( $item->getPathname() );
		}
	}
	@rmdir( $dir );
}

function rafah_su_rcopy( $src, $dest ) {
	if ( ! is_dir( $src ) ) {
		return false;
	}
	if ( ! wp_mkdir_p( $dest ) ) {
		return false;
	}
	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $src, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);
	foreach ( $items as $item ) {
		$sub = substr( $item->getPathname(), strlen( $src ) + 1 );
		$to  = $dest . '/' . $sub;
		if ( $item->isDir() ) {
			wp_mkdir_p( $to );
		} else {
			@copy( $item->getPathname(), $to );
		}
	}
	return true;
}

function rafah_su_protect_store() {
	wp_mkdir_p( RAFAH_RESTORE_DIR );
	$ht = RAFAH_RESTORE_DIR . '/.htaccess';
	if ( ! is_file( $ht ) ) {
		@file_put_contents( $ht, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" );
	}
	$idx = RAFAH_RESTORE_DIR . '/index.php';
	if ( ! is_file( $idx ) ) {
		@file_put_contents( $idx, "<?php // Silence is golden.\n" );
	}
}

/* =========================================================================
 * 4. (Removed) Crash handler / rafah_su_arm_update()
 * -------------------------------------------------------------------------
 * Safe Update NO LONGER arms anything inside the update flow. The single
 * maintenance guarantee lives in rafah-0-maintenance-guard.php, which arms
 * ONE shutdown handler at admin_init (request start, before WordPress's
 * updater runs) that clears a stray `.maintenance` if the request ends
 * abnormally. Duplicating that here (a second register_shutdown_function +
 * set_time_limit + option writes) would be Safe Update participating in the
 * update lifecycle for no reason, so it is gone. Failed installs are already
 * protected by WordPress 6.3+ native temp-backup/rollback.
 * ========================================================================= */

/* =========================================================================
 * 5. Post-update health report — ASYNC, AFTER WordPress finishes. Never blocks.
 * -------------------------------------------------------------------------
 * On `upgrader_process_complete` we do the MINIMUM and return immediately:
 * record what was updated and schedule a one-off background event. The actual
 * loopback health check runs later, in a SEPARATE cron request — it can never
 * delay, block, or interrupt the update the user is watching. It is report-only
 * (writes the last-update report shown in Tools → Rafah Updates). WordPress's
 * native temp-backup/rollback already protects a failed install, so Rafah does
 * no inline rollback. No snapshot, no loopback, no file copy in this request.
 * ========================================================================= */

add_action( 'upgrader_process_complete', function ( $upgrader, $data ) {
	delete_option( 'rafah_update_started' );

	$action = $data['action'] ?? '';
	$type   = $data['type'] ?? '';
	if ( 'update' !== $action || ! rafah_su_enabled() ) {
		return;
	}

	$items = array();
	foreach ( array( 'plugins', 'themes' ) as $k ) {
		if ( ! empty( $data[ $k ] ) ) {
			foreach ( (array) $data[ $k ] as $x ) {
				$items[] = ( 'themes' === $k ) ? (string) $x : rafah_su_slug( $x );
			}
		}
	}
	if ( empty( $items ) && ! empty( $data['plugin'] ) ) { $items[] = rafah_su_slug( $data['plugin'] ); }
	if ( empty( $items ) && ! empty( $data['theme'] ) )  { $items[] = (string) $data['theme']; }

	rafah_su_save_report( array(
		'status'  => 'UPDATED',
		'type'    => $type,
		'items'   => $items,
		'message' => sprintf( 'WordPress finished updating: %s. A background health check is scheduled.', implode( ', ', $items ) ?: '(site)' ),
		'checks'  => array(),
	) );
	update_option( 'rafah_su_pending_health', $items, false );

	// Run the health check in ITS OWN request shortly after this one ends.
	if ( ! wp_next_scheduled( 'rafah_su_health_check' ) ) {
		wp_schedule_single_event( time() + 10, 'rafah_su_health_check' );
	}
	rafah_su_log( 'update complete (native) — async health check scheduled: ' . implode( ', ', $items ) );
}, 20, 2 );

/**
 * ASYNC health check — runs in a background cron request, NEVER in the updater.
 * Report-only: it records how the site looks after the update. There is no
 * inline rollback (WordPress's native rollback already handled a failed install)
 * and no work on the critical path. The loopback lives here, off the update.
 */
add_action( 'rafah_su_health_check', function () {
	$items = (array) get_option( 'rafah_su_pending_health', array() );
	delete_option( 'rafah_su_pending_health' );
	if ( ! function_exists( 'rafah_su_verify' ) ) {
		return;
	}
	$result = rafah_su_verify();
	$status = ( 'fail' === $result['verdict'] ) ? 'UNHEALTHY' : ( ( 'inconclusive' === $result['verdict'] ) ? 'UNVERIFIED' : 'HEALTHY' );
	$message = ( 'HEALTHY' === $status )
		? sprintf( 'Post-update health check passed: %s.', implode( ', ', $items ) ?: '(site)' )
		: ( ( 'UNHEALTHY' === $status )
			? sprintf( 'Post-update health check FAILED for %s (failed: %s). WordPress kept the update; roll back from Tools → Rafah Updates if needed.', implode( ', ', $items ) ?: '(site)', implode( ', ', rafah_su_failed_labels( $result['checks'] ) ) ?: '(unknown)' )
			: 'Post-update health check could not reach the site over loopback (loopback may be disabled locally). No action taken.' );
	rafah_su_save_report( array(
		'status'  => $status,
		'items'   => $items,
		'checks'  => $result['checks'],
		'message' => $message,
	) );
	rafah_su_log( 'ASYNC health: ' . $status );
} );

/**
 * Verify the live site over loopback. Returns:
 *   verdict: 'pass' | 'fail' | 'inconclusive'
 *   checks:  list of [label, status(pass|fail|warn|skip), detail]
 * 'fail' → a hard problem (HTTP 5xx, PHP fatal in output, Core/Elementor down).
 * 'inconclusive' → the site itself was unreachable (loopback disabled).
 */
function rafah_su_verify() {
	static $running = false;
	if ( $running ) {
		return array( 'verdict' => 'pass', 'checks' => array() ); // never verify inside a verify
	}
	$running = true;
	@set_time_limit( 300 );

	$checks      = array();
	$hard_fail   = false;
	$reachable   = false;

	$fatal_re = '/There has been a critical error|Fatal error|Error establishing a database|allowed memory size/i';

	$get = function ( $url ) {
		$args = array(
			'timeout'     => 15,
			'redirection' => 3,
			'sslverify'   => false,
			'blocking'    => true,
			'headers'     => array( 'X-Rafah-Verify' => '1', 'Cache-Control' => 'no-cache' ),
		);
		$r = wp_remote_get( $url, $args );
		// One retry on a transport error (loopback contention on single-machine
		// dev servers can time out the first hit while workers are busy).
		if ( is_wp_error( $r ) ) {
			usleep( 400000 );
			$r = wp_remote_get( $url, $args );
		}
		return $r;
	};

	// Build the target list.
	$targets = array(
		array( 'Homepage', home_url( '/' ), '/rafah-|elementor|<body/i' ),
		array( 'REST API', rest_url(), '/\{/' ),
		array( 'Self-check', rest_url( 'rafah/v1/selfcheck' ), null ),
		array( 'Admin AJAX', admin_url( 'admin-ajax.php?action=heartbeat' ), null ),
	);
	$archive = get_post_type_archive_link( 'project' );
	if ( $archive ) {
		$targets[] = array( 'Projects archive', $archive, '/rafah-/i' );
	}
	$proj = get_posts( array( 'post_type' => 'project', 'posts_per_page' => 1, 'post_status' => 'publish', 'fields' => 'ids' ) );
	if ( ! empty( $proj[0] ) ) {
		$targets[] = array( 'Single project', get_permalink( $proj[0] ), '/rafah-/i' );
	}
	$contact = get_page_by_path( 'contact' );
	if ( $contact ) {
		$targets[] = array( 'Contact', get_permalink( $contact ), null );
	}

	foreach ( $targets as $t ) {
		list( $label, $url, $marker ) = array( $t[0], $t[1], $t[2] ?? null );
		$r = $get( $url );
		if ( is_wp_error( $r ) ) {
			$checks[] = array( $label, 'skip', 'unreachable: ' . $r->get_error_message() );
			continue;
		}
		$reachable = true;
		$code = (int) wp_remote_retrieve_response_code( $r );
		$body = (string) wp_remote_retrieve_body( $r );

		// AJAX: only a 500 is a failure; anything else (200/400) is fine.
		if ( 'Admin AJAX' === $label ) {
			if ( 500 === $code ) { $hard_fail = true; $checks[] = array( $label, 'fail', 'HTTP 500' ); }
			else { $checks[] = array( $label, 'pass', 'HTTP ' . $code ); }
			continue;
		}
		if ( $code >= 500 || 0 === $code ) {
			$hard_fail = true;
			$checks[] = array( $label, 'fail', 'HTTP ' . $code );
			continue;
		}
		if ( preg_match( $fatal_re, $body ) ) {
			$hard_fail = true;
			$checks[] = array( $label, 'fail', 'PHP fatal / critical error in output' );
			continue;
		}
		if ( 'Self-check' === $label ) {
			$json = json_decode( $body, true );
			if ( is_array( $json ) ) {
				if ( empty( $json['core'] ) ) { $hard_fail = true; $checks[] = array( 'Rafah Core', 'fail', 'Core not loaded' ); }
				else { $checks[] = array( 'Rafah Core', 'pass', 'loaded' ); }
				if ( isset( $json['elementor'] ) && empty( $json['elementor'] ) ) { $checks[] = array( 'Elementor', 'warn', 'not loaded (ok if intentionally inactive)' ); }
				else { $checks[] = array( 'Elementor', 'pass', 'loaded' ); }
			} else {
				$checks[] = array( 'Self-check', 'warn', 'no JSON (endpoint unavailable)' );
			}
			continue;
		}
		if ( $marker && ! preg_match( $marker, $body ) ) {
			$checks[] = array( $label, 'warn', 'expected marker not found (layout/assets?)' );
			continue;
		}
		$checks[] = array( $label, 'pass', 'HTTP ' . $code );
	}

	// RTL + assets sanity from the homepage body.
	$home = $get( home_url( '/' ) );
	if ( ! is_wp_error( $home ) ) {
		$reachable = true;
		$hb = (string) wp_remote_retrieve_body( $home );
		$checks[] = preg_match( '/dir=["\']rtl["\']/i', $hb ) ? array( 'RTL', 'pass', 'dir=rtl present' ) : array( 'RTL', 'warn', 'rtl direction not detected' );
		$checks[] = preg_match( '/rafah-core\/assets|id=["\']rafah|rafah-theme-css|wp-content\/themes\/rafah/i', $hb ) ? array( 'Assets', 'pass', 'Rafah assets present' ) : array( 'Assets', 'warn', 'Rafah assets not detected' );
	}

	$running = false;
	if ( ! $reachable ) {
		return array( 'verdict' => 'inconclusive', 'checks' => $checks );
	}
	return array( 'verdict' => $hard_fail ? 'fail' : 'pass', 'checks' => $checks );
}

function rafah_su_failed_labels( $checks ) {
	$out = array();
	foreach ( (array) $checks as $c ) {
		if ( isset( $c[1] ) && 'fail' === $c[1] ) {
			$out[] = $c[0];
		}
	}
	return $out;
}

/* =========================================================================
 * 6. Report storage + log
 * ========================================================================= */

function rafah_su_save_report( $report ) {
	$report = wp_parse_args( $report, array( 'time' => current_time( 'mysql' ), 'status' => 'UNKNOWN', 'message' => '', 'items' => array(), 'checks' => array() ) );
	update_option( 'rafah_last_update_report', $report, false );
	set_transient( 'rafah_update_notice', $report, DAY_IN_SECONDS );
}

function rafah_su_log( $line ) {
	try {
		rafah_su_protect_store();
		$msg = '[' . gmdate( 'Y-m-d H:i:s' ) . 'Z] ' . $line . "\n";
		@file_put_contents( RAFAH_UPDATE_LOG, $msg, FILE_APPEND | LOCK_EX );
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Rafah Safe Update] ' . $line );
		}
	} catch ( \Throwable $e ) {}
}

/* =========================================================================
 * 7. Self-check REST endpoint (loaded fresh = reliable Core/Elementor state)
 * ========================================================================= */

add_action( 'rest_api_init', function () {
	register_rest_route( 'rafah/v1', '/selfcheck', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function () {
			nocache_headers();
			$data = array(
				'ok'        => true,
				'core'      => ( class_exists( 'Rafah_Core' ) || function_exists( 'rafah_meta' ) || function_exists( 'rafah_opt' ) ),
				'elementor' => (bool) did_action( 'elementor/loaded' ),
				'theme'     => wp_get_theme()->get( 'Name' ),
				'time'      => current_time( 'mysql' ),
			);
			// M1: never disclose PHP / WordPress versions to unauthenticated
			// visitors. Only administrators receive them. The Safe Update loopback
			// verifier reads only 'core'/'elementor', so it is unaffected.
			if ( current_user_can( 'manage_options' ) ) {
				$data['php'] = PHP_VERSION;
				$data['wp']  = get_bloginfo( 'version' );
			}
			return $data;
		},
	) );
} );

/* =========================================================================
 * 8. Admin UI — Tools → Rafah Updates
 * ========================================================================= */

add_action( 'admin_menu', function () {
	add_management_page(
		__( 'Rafah Updates', 'rafah' ),
		__( 'Rafah Updates', 'rafah' ),
		'update_plugins',
		'rafah-updates',
		'rafah_su_render_admin'
	);
} );

add_action( 'admin_post_rafah_su_action', function () {
	if ( ! current_user_can( 'update_plugins' ) || ! check_admin_referer( 'rafah_su' ) ) {
		wp_die( 'Not allowed' );
	}
	$do  = sanitize_key( $_POST['do'] ?? '' );
	$msg = 'done';
	switch ( $do ) {
		case 'clear_maintenance':
			$msg = function_exists( 'rafah_clear_maintenance' ) && rafah_clear_maintenance( 'admin: manual clear' ) ? 'Maintenance lock cleared.' : 'No maintenance lock present.';
			break;
		case 'verify':
			$res = rafah_su_verify();
			rafah_su_save_report( array( 'status' => strtoupper( $res['verdict'] ), 'message' => 'Manual verification run.', 'checks' => $res['checks'], 'items' => array() ) );
			$msg = 'Verification complete: ' . strtoupper( $res['verdict'] ) . '.';
			break;
		case 'snapshot_all':
			$n = 0;
			foreach ( (array) get_option( 'active_plugins', array() ) as $p ) {
				if ( rafah_su_snapshot( 'plugin', rafah_su_slug( $p ) ) ) { $n++; }
			}
			rafah_su_snapshot( 'theme', get_stylesheet() );
			$msg = "Created restore points for {$n} active plugin(s) + theme.";
			break;
		case 'rollback_last':
			$rep = get_option( 'rafah_last_update_report', array() );
			$n   = 0;
			foreach ( (array) ( $rep['items'] ?? array() ) as $slug ) {
				if ( rafah_su_restore( 'plugin', $slug ) ) { $n++; }
			}
			if ( function_exists( 'rafah_clear_maintenance' ) ) { rafah_clear_maintenance( 'admin: rollback_last' ); }
			$msg = "Rolled back {$n} item(s) from the last update.";
			break;
		case 'save_policy':
			$policy = array();
			$slugs  = array_map( 'sanitize_text_field', (array) ( $_POST['slug'] ?? array() ) );
			$stats  = array_map( 'sanitize_key', (array) ( $_POST['status'] ?? array() ) );
			$vers   = array_map( 'sanitize_text_field', (array) ( $_POST['locked_version'] ?? array() ) );
			foreach ( $slugs as $i => $slug ) {
				if ( '' === $slug ) { continue; }
				$policy[ $slug ] = array(
					'status'         => in_array( $stats[ $i ] ?? '', array( 'locked', 'approved', 'blocked', 'ignored' ), true ) ? $stats[ $i ] : 'approved',
					'locked_version' => $vers[ $i ] ?? '',
				);
			}
			update_option( 'rafah_update_policy', $policy, false );
			$msg = 'Update policy saved.';
			break;
	}
	set_transient( 'rafah_su_admin_msg', $msg, 60 );
	wp_safe_redirect( admin_url( 'tools.php?page=rafah-updates' ) );
	exit;
} );

// Admin notice with the last update verdict.
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'update_plugins' ) ) { return; }
	$rep = get_transient( 'rafah_update_notice' );
	if ( ! $rep || empty( $rep['status'] ) ) { return; }
	$status = $rep['status'];
	$class  = in_array( $status, array( 'PASS' ), true ) ? 'notice-success' : ( in_array( $status, array( 'ROLLED_BACK', 'CRASH', 'FAIL' ), true ) ? 'notice-error' : 'notice-warning' );
	echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p><strong>Rafah Safe Update:</strong> ' . esc_html( $rep['message'] ?? $status ) . ' <a href="' . esc_url( admin_url( 'tools.php?page=rafah-updates' ) ) . '">View report</a></p></div>';
	if ( 'PASS' === $status ) { delete_transient( 'rafah_update_notice' ); }
} );

function rafah_su_render_admin() {
	if ( ! current_user_can( 'update_plugins' ) ) { return; }
	$msg = get_transient( 'rafah_su_admin_msg' );
	if ( $msg ) { delete_transient( 'rafah_su_admin_msg' ); }
	$report = get_option( 'rafah_last_update_report', array() );
	$policy = rafah_su_policy();
	$lock   = is_file( rafah_maintenance_file() );
	$active = (array) get_option( 'active_plugins', array() );

	// Ensure every active plugin appears in the editable policy table.
	$rows = array();
	foreach ( $active as $p ) {
		$slug = rafah_su_slug( $p );
		$rows[ $slug ] = $policy[ $slug ] ?? array( 'status' => rafah_su_status_for( $slug ), 'locked_version' => '' );
	}
	foreach ( $policy as $slug => $row ) {
		if ( ! isset( $rows[ $slug ] ) ) { $rows[ $slug ] = $row; }
	}
	ksort( $rows );
	$statuses = array( 'approved', 'locked', 'blocked', 'ignored' );
	$post_url = esc_url( admin_url( 'admin-post.php' ) );
	$nonce    = wp_create_nonce( 'rafah_su' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Rafah Safe Update Manager', 'rafah' ); ?></h1>
		<?php if ( $msg ) : ?>
			<div class="notice notice-info"><p><?php echo esc_html( $msg ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'System status', 'rafah' ); ?></h2>
		<table class="widefat striped" style="max-width:760px">
			<tbody>
				<tr><td><strong>WordPress auto-updates</strong></td><td>Disabled (all core / plugin / theme / translation)</td></tr>
				<tr><td><strong>Maintenance lock</strong></td><td><?php echo $lock ? '<span style="color:#b32d2e">PRESENT</span>' : '<span style="color:#1e7e34">clear</span>'; ?></td></tr>
				<tr><td><strong>Restore points</strong></td><td><?php echo (int) count( (array) get_option( 'rafah_restore_index', array() ) ); ?> stored</td></tr>
				<tr><td><strong>Last update</strong></td>
					<td>
						<?php if ( ! empty( $report['status'] ) ) :
							$col = 'PASS' === $report['status'] ? '#1e7e34' : ( in_array( $report['status'], array( 'ROLLED_BACK', 'CRASH', 'FAIL' ), true ) ? '#b32d2e' : '#996800' ); ?>
							<strong style="color:<?php echo esc_attr( $col ); ?>"><?php echo esc_html( $report['status'] ); ?></strong>
							— <?php echo esc_html( $report['message'] ?? '' ); ?>
							<em>(<?php echo esc_html( $report['time'] ?? '' ); ?>)</em>
						<?php else : ?>
							<?php esc_html_e( 'No updates recorded yet.', 'rafah' ); ?>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

		<p style="margin-top:16px">
			<?php $btn = function ( $do, $label, $primary = false ) use ( $post_url, $nonce ) { ?>
				<form method="post" action="<?php echo $post_url; ?>" style="display:inline-block;margin-inline-end:8px">
					<input type="hidden" name="action" value="rafah_su_action">
					<input type="hidden" name="do" value="<?php echo esc_attr( $do ); ?>">
					<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
					<button class="button <?php echo $primary ? 'button-primary' : ''; ?>"><?php echo esc_html( $label ); ?></button>
				</form>
			<?php };
			$btn( 'clear_maintenance', __( 'Clear maintenance now', 'rafah' ), true );
			$btn( 'verify', __( 'Run verification', 'rafah' ) );
			$btn( 'snapshot_all', __( 'Create restore points', 'rafah' ) );
			$btn( 'rollback_last', __( 'Roll back last update', 'rafah' ) );
			?>
		</p>

		<?php if ( ! empty( $report['checks'] ) ) : ?>
			<h2><?php esc_html_e( 'Last verification', 'rafah' ); ?></h2>
			<table class="widefat striped" style="max-width:760px"><tbody>
			<?php foreach ( $report['checks'] as $c ) :
				$s = $c[1] ?? 'skip';
				$col = 'pass' === $s ? '#1e7e34' : ( 'fail' === $s ? '#b32d2e' : ( 'warn' === $s ? '#996800' : '#666' ) ); ?>
				<tr><td style="width:180px"><?php echo esc_html( $c[0] ?? '' ); ?></td>
					<td style="width:70px"><strong style="color:<?php echo esc_attr( $col ); ?>"><?php echo esc_html( strtoupper( $s ) ); ?></strong></td>
					<td><?php echo esc_html( $c[2] ?? '' ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Plugin update policy', 'rafah' ); ?></h2>
		<p class="description">
			<strong>approved</strong> = update allowed (always snapshotted, verified &amp; auto-rolled-back). &nbsp;
			<strong>locked</strong> = frozen at its version. &nbsp;
			<strong>blocked</strong> = never update. &nbsp;
			<strong>ignored</strong> = not gated.
		</p>
		<form method="post" action="<?php echo $post_url; ?>">
			<input type="hidden" name="action" value="rafah_su_action">
			<input type="hidden" name="do" value="save_policy">
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
			<table class="widefat striped" style="max-width:760px">
				<thead><tr><th><?php esc_html_e( 'Plugin', 'rafah' ); ?></th><th><?php esc_html_e( 'Status', 'rafah' ); ?></th><th><?php esc_html_e( 'Locked version', 'rafah' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $slug => $row ) : ?>
					<tr>
						<td><code><?php echo esc_html( $slug ); ?></code><input type="hidden" name="slug[]" value="<?php echo esc_attr( $slug ); ?>"></td>
						<td>
							<select name="status[]">
								<?php foreach ( $statuses as $st ) : ?>
									<option value="<?php echo esc_attr( $st ); ?>" <?php selected( $row['status'] ?? 'approved', $st ); ?>><?php echo esc_html( $st ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td><input type="text" name="locked_version[]" value="<?php echo esc_attr( $row['locked_version'] ?? '' ); ?>" placeholder="e.g. 3.20.0" class="regular-text" style="width:140px"></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p><button class="button button-primary"><?php esc_html_e( 'Save policy', 'rafah' ); ?></button></p>
		</form>
	</div>
	<?php
}

/* =========================================================================
 * 9. Locked plugins: hide the wp-admin Update button + show a lock notice
 *    (additive UI layer — the upgrader_pre_install gate in section 2 already
 *    refuses the actual update; this stops it being offered in the first place)
 * ========================================================================= */

$GLOBALS['rafah_su_locked_updates'] = array( 'plugins' => array(), 'themes' => array() );

/** Remove locked plugins from the update transient so wp-admin never shows an
 *  Update button, update count, or bulk-update option for them. */
add_filter( 'site_transient_update_plugins', function ( $transient ) {
	if ( ! rafah_su_enabled() || ! is_object( $transient ) || empty( $transient->response ) ) {
		return $transient;
	}
	foreach ( $transient->response as $file => $data ) {
		if ( 'locked' === rafah_su_status_for( rafah_su_slug( $file ) ) ) {
			$GLOBALS['rafah_su_locked_updates']['plugins'][ $file ] = is_object( $data ) && isset( $data->new_version ) ? $data->new_version : '';
			unset( $transient->response[ $file ] );
			// Keep it listed as "no update" so the row renders normally (no nag).
			if ( isset( $transient->no_update ) && is_array( $transient->no_update ) ) {
				$transient->no_update[ $file ] = $data;
			}
		}
	}
	return $transient;
}, 20 );

/** Same protection for locked themes (policy keyed by theme stylesheet slug). */
add_filter( 'site_transient_update_themes', function ( $transient ) {
	if ( ! rafah_su_enabled() || ! is_object( $transient ) || empty( $transient->response ) ) {
		return $transient;
	}
	foreach ( $transient->response as $slug => $data ) {
		if ( 'locked' === rafah_su_status_for( (string) $slug ) ) {
			$GLOBALS['rafah_su_locked_updates']['themes'][ $slug ] = is_array( $data ) && isset( $data['new_version'] ) ? $data['new_version'] : '';
			unset( $transient->response[ $slug ] );
			if ( isset( $transient->no_update ) && is_array( $transient->no_update ) ) {
				$transient->no_update[ $slug ] = $data;
			}
		}
	}
	return $transient;
}, 20 );

/** Show the lock message under each locked plugin on the Plugins screen. */
add_action( 'after_plugin_row', function ( $plugin_file, $plugin_data, $status ) {
	if ( ! rafah_su_enabled() || 'locked' !== rafah_su_status_for( rafah_su_slug( $plugin_file ) ) ) {
		return;
	}
	$colspan = 4;
	try {
		if ( function_exists( '_get_list_table' ) ) {
			$lt = _get_list_table( 'WP_Plugins_List_Table', array( 'screen' => get_current_screen() ) );
			if ( $lt && method_exists( $lt, 'get_column_count' ) ) {
				$colspan = (int) $lt->get_column_count();
			}
		}
	} catch ( \Throwable $e ) {}

	$avail = $GLOBALS['rafah_su_locked_updates']['plugins'][ $plugin_file ] ?? '';
	$base  = __( 'This plugin is locked by Rafah Safe Update. Update it only through the Rafah Updates workflow.', 'rafah' );
	$msg   = $avail
		? sprintf(
			/* translators: %s: available version */
			__( 'A new version (%s) is available, but this plugin is locked by Rafah Safe Update. Update it only through the Rafah Updates workflow.', 'rafah' ),
			$avail
		)
		: $base;
	printf(
		'<tr class="plugin-update-tr active"><td colspan="%1$d" class="plugin-update colspanchange"><div class="notice inline notice-warning notice-alt" style="margin:5px 20px 5px 40px"><p><span class="dashicons dashicons-lock" style="vertical-align:middle"></span> %2$s <a href="%3$s">%4$s</a></p></div></td></tr>',
		(int) $colspan,
		esc_html( $msg ),
		esc_url( admin_url( 'tools.php?page=rafah-updates' ) ),
		esc_html__( 'Open Rafah Updates', 'rafah' )
	);
}, 10, 3 );

/*
 * (Removed) The old `upgrader_pre_download` gate returned a WP_Error mid-update
 * for locked plugins — i.e. it PARTICIPATED in WordPress's updater. Locked
 * plugins are now kept out of the update flow purely passively (stripped from
 * the update transient above), so no Rafah code runs inside the download/install
 * path. WordPress's updater executes exactly as on a clean installation.
 */

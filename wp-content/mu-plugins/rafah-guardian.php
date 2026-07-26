<?php
/**
 * Plugin Name: Rafah Guardian
 * Description: Integrity guard + event log + SELF-HEALER. Automatically cleans EMPTY leftover plugin folders left behind by filesystem-locked delete/update operations (rename→quarantine, works where rmdir is locked) after every install/update/delete and on plugin screens — no manual cleanup, no reinstall failures. REQUIRED plugins get a hard warning if broken; OPTIONAL plugins never warn permanently (Repair / Ignore). mu-plugin → loads always, survives plugin breakage.
 * Version: 1.3.0
 * Author: Rafah
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'RAFAH_GUARDIAN_QUARANTINE' ) ) {
	define( 'RAFAH_GUARDIAN_QUARANTINE', WP_CONTENT_DIR . '/rafah-guardian-quarantine' );
}

if ( ! function_exists( 'rafah_guardian_required' ) ) {
	/** REQUIRED plugins (slug => bootstrap). Warned if missing OR incomplete. */
	function rafah_guardian_required() {
		return (array) apply_filters( 'rafah_guardian_required', array(
			'elementor'        => 'elementor.php',
			'polylang'         => 'polylang.php',
			'seo-by-rank-math' => 'rank-math.php',
			'rafah-core'       => 'rafah-core.php',
		) );
	}
}

if ( ! function_exists( 'rafah_guardian_optional' ) ) {
	/**
	 * OPTIONAL plugins (slug => bootstrap). A cleanly-absent or empty folder =
	 * "not used" = totally SILENT. Only a folder that is present-but-corrupt
	 * (has files/dirs but the bootstrap is missing) surfaces a dismissible,
	 * actionable notice — never a permanent warning.
	 */
	function rafah_guardian_optional() {
		return (array) apply_filters( 'rafah_guardian_optional', array(
			'fluentform' => 'fluentform.php',
			'latepoint'  => 'latepoint.php',
			'sureforms'  => 'sureforms.php',
		) );
	}
}

if ( ! function_exists( 'rafah_guardian_ignored' ) ) {
	/** Slugs the owner has permanently dismissed. Silent forever until un-ignored. */
	function rafah_guardian_ignored() {
		$opt = get_option( 'rafah_guardian_ignored', array() );
		return (array) apply_filters( 'rafah_guardian_ignored', is_array( $opt ) ? $opt : array() );
	}
}

if ( ! function_exists( 'rafah_guardian_dir_has_entries' ) ) {
	/** True if a directory contains any file OR subdirectory (deep). */
	function rafah_guardian_dir_has_entries( $path ) {
		try {
			return iterator_count( new FilesystemIterator( $path, FilesystemIterator::SKIP_DOTS ) ) > 0;
		} catch ( \Throwable $e ) {
			return false;
		}
	}
}

if ( ! function_exists( 'rafah_guardian_scan' ) ) {
	/**
	 * Return a list of problems. Each: array( slug, kind, msg, repairable ).
	 *   kind = 'required' (hard) | 'optional' (soft, actionable).
	 * Ignored optional slugs are skipped entirely. Read-only, never throws.
	 */
	function rafah_guardian_scan() {
		$problems = array();
		$dir      = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';

		// REQUIRED — missing or incomplete is a real problem.
		foreach ( rafah_guardian_required() as $slug => $boot ) {
			$path = $dir . '/' . $slug;
			if ( ! is_dir( $path ) ) {
				$problems[] = array( 'slug' => $slug, 'kind' => 'required', 'repairable' => false, 'msg' => sprintf( '%s: required plugin folder is missing.', $slug ) );
				continue;
			}
			$boot_path = $path . '/' . $boot;
			if ( ! file_exists( $boot_path ) ) {
				$empty      = ! rafah_guardian_dir_has_entries( $path );
				$problems[] = array( 'slug' => $slug, 'kind' => 'required', 'repairable' => true, 'msg' => sprintf( '%s: INCOMPLETE — bootstrap "%s" missing%s.', $slug, $boot, $empty ? ' and the folder is empty' : '' ) );
			} elseif ( 0 === (int) @filesize( $boot_path ) ) {
				$problems[] = array( 'slug' => $slug, 'kind' => 'required', 'repairable' => false, 'msg' => sprintf( '%s: bootstrap "%s" is zero bytes (corrupt).', $slug, $boot ) );
			}
		}

		// OPTIONAL — silent unless present-but-corrupt AND not ignored.
		$ignored = rafah_guardian_ignored();
		foreach ( rafah_guardian_optional() as $slug => $boot ) {
			if ( in_array( $slug, $ignored, true ) ) {
				continue; // permanently dismissed
			}
			$path = $dir . '/' . $slug;
			if ( ! is_dir( $path ) || file_exists( $path . '/' . $boot ) ) {
				continue; // absent, or actually installed = fine
			}
			if ( rafah_guardian_dir_has_entries( $path ) ) {
				$problems[] = array( 'slug' => $slug, 'kind' => 'optional', 'repairable' => true, 'msg' => sprintf( '%s: leftover folder from a failed/removed install (bootstrap "%s" missing).', $slug, $boot ) );
			}
		}

		return $problems;
	}
}

if ( ! function_exists( 'rafah_guardian_repair' ) ) {
	/**
	 * Repair a corrupt plugin folder by moving it into the quarantine directory
	 * (safe & reversible — nothing is hard-deleted). Frees plugins/<slug> so the
	 * plugin can be reinstalled normally. Returns true on success.
	 */
	function rafah_guardian_repair( $slug ) {
		$slug = sanitize_key( $slug );
		$dir  = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';
		$src  = $dir . '/' . $slug;
		if ( '' === $slug || ! is_dir( $src ) ) {
			return false;
		}
		wp_mkdir_p( RAFAH_GUARDIAN_QUARANTINE );
		if ( ! is_file( RAFAH_GUARDIAN_QUARANTINE . '/index.php' ) ) {
			@file_put_contents( RAFAH_GUARDIAN_QUARANTINE . '/index.php', "<?php // Silence is golden.\n" );
		}
		$dest = RAFAH_GUARDIAN_QUARANTINE . '/' . $slug . '-' . gmdate( 'YmdHis' );
		$ok   = @rename( $src, $dest );
		rafah_guardian_log( 'repair', sprintf( '%s → %s (%s)', $slug, $dest, $ok ? 'ok' : 'FAILED' ) );
		return $ok && ! is_dir( $src );
	}
}

if ( ! function_exists( 'rafah_guardian_log' ) ) {
	/** Append an event to the rolling log (option, last 100) and to debug.log. */
	function rafah_guardian_log( $event, $detail = '' ) {
		try {
			$log = get_option( 'rafah_guardian_log', array() );
			if ( ! is_array( $log ) ) { $log = array(); }
			$log[] = array( 'time' => current_time( 'mysql' ), 'event' => (string) $event, 'detail' => (string) $detail );
			update_option( 'rafah_guardian_log', array_slice( $log, -100 ), false );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( '[Rafah Guardian] %s %s', $event, $detail ) );
			}
		} catch ( \Throwable $e ) {} // logging must never break anything
	}
}

/* ---- SELF-HEALING: empty leftover plugin folders (filesystem-lock safe) ---- */

if ( ! function_exists( 'rafah_guardian_is_empty_leftover' ) ) {
	/** True when $dir contains NO regular files anywhere (only empty subdirs). */
	function rafah_guardian_is_empty_leftover( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return false;
		}
		try {
			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::LEAVES_ONLY
			);
			foreach ( $it as $node ) {
				if ( $node->isFile() ) {
					return false; // a real file exists → NOT a safe auto-clean.
				}
			}
			return true;
		} catch ( \Throwable $e ) {
			return false;
		}
	}
}

if ( ! function_exists( 'rafah_guardian_plugin_slugs' ) ) {
	/** Folder slugs that ARE real installed plugins (have a plugin header). */
	function rafah_guardian_plugin_slugs() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$slugs = array();
		foreach ( array_keys( (array) get_plugins() ) as $file ) {
			if ( false !== strpos( $file, '/' ) ) {
				$slugs[ strtok( $file, '/' ) ] = true;
			}
		}
		return $slugs;
	}
}

if ( ! function_exists( 'rafah_guardian_rmdir_empty_tree' ) ) {
	/**
	 * Remove a directory tree of (possibly nested) EMPTY dirs.
	 *
	 * IMPORTANT (Windows/LocalWP): a live RecursiveDirectoryIterator keeps an OPEN
	 * HANDLE on the directories it is walking, and Windows refuses rmdir on any
	 * directory that has an open handle → "Permission denied". So we COLLECT the
	 * paths first, fully release the iterator, clear the stat cache, and only then
	 * rmdir deepest-first. This lets rmdir actually succeed on Windows.
	 */
	function rafah_guardian_rmdir_empty_tree( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return true;
		}
		$subdirs = array();
		try {
			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $it as $node ) {
				if ( $node->isDir() ) {
					$subdirs[] = $node->getPathname();
				}
			}
		} catch ( \Throwable $e ) {}
		unset( $it, $node ); // release the iterator's directory handles BEFORE rmdir.
		clearstatcache();
		foreach ( $subdirs as $sd ) {
			@rmdir( $sd );
		}
		@rmdir( $dir );
		clearstatcache( true, $dir );
		return ! is_dir( $dir );
	}
}

if ( ! function_exists( 'rafah_guardian_autoheal' ) ) {
	/**
	 * Automatically clean EMPTY leftover plugin folders (a filesystem-locked
	 * delete/update removed the files but not the directory). Safe by design:
	 * ONLY folders with ZERO files are touched; anything with real files is left
	 * untouched (surfaced by the corrupt-folder notice instead). Cleans via
	 * rename→quarantine (succeeds where rmdir is locked), then an rmdir fallback.
	 * Returns array( healed => [], failed => [] ).
	 */
	function rafah_guardian_autoheal() {
		if ( function_exists( 'wp_installing' ) && wp_installing() ) {
			return array( 'healed' => array(), 'failed' => array() );
		}
		$dir    = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';
		$valid  = rafah_guardian_plugin_slugs();
		$healed = array();
		$failed = array();

		foreach ( (array) glob( $dir . '/*', GLOB_ONLYDIR ) as $path ) {
			$slug = basename( $path );
			if ( isset( $valid[ $slug ] ) ) {
				continue; // a real, installed plugin — never touch.
			}
			if ( ! rafah_guardian_is_empty_leftover( $path ) ) {
				continue; // has files → not an empty leftover → do NOT auto-delete.
			}

			wp_mkdir_p( RAFAH_GUARDIAN_QUARANTINE );
			if ( ! is_file( RAFAH_GUARDIAN_QUARANTINE . '/index.php' ) ) {
				@file_put_contents( RAFAH_GUARDIAN_QUARANTINE . '/index.php', "<?php // Silence is golden.\n" );
			}
			$dest = RAFAH_GUARDIAN_QUARANTINE . '/' . $slug . '-empty-' . gmdate( 'YmdHis' );

			if ( ( @rename( $path, $dest ) && ! is_dir( $path ) ) || rafah_guardian_rmdir_empty_tree( $path ) ) {
				$healed[] = $slug;
				rafah_guardian_log( 'autoheal', $slug );
			} else {
				$failed[] = $slug;
				rafah_guardian_log( 'autoheal_failed', $slug );
			}
		}

		if ( $healed ) {
			set_transient( 'rafah_guardian_healed', $healed, 120 );
		}
		if ( $failed ) {
			update_option( 'rafah_guardian_heal_failed', array_values( array_unique( $failed ) ), false );
		} else {
			delete_option( 'rafah_guardian_heal_failed' );
		}
		if ( function_exists( 'rafah_guardian_sweep_quarantine' ) ) {
			rafah_guardian_sweep_quarantine();
		}
		return array( 'healed' => $healed, 'failed' => $failed );
	}
}

if ( ! function_exists( 'rafah_guardian_prune_active_plugins' ) ) {
	/** Drop active_plugins entries whose files no longer exist, so WordPress
	 *  never believes a removed plugin is still installed/active. */
	function rafah_guardian_prune_active_plugins() {
		$dir    = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';
		$active = (array) get_option( 'active_plugins', array() );
		$clean  = array_values( array_filter( $active, function ( $f ) use ( $dir ) {
			return $f && file_exists( $dir . '/' . $f );
		} ) );
		if ( $clean !== $active ) {
			update_option( 'active_plugins', $clean );
			rafah_guardian_log( 'pruned_active', implode( ',', array_values( array_diff( $active, $clean ) ) ) );
		}
	}
}

if ( ! function_exists( 'rafah_guardian_hard_remove_dir' ) ) {
	/**
	 * Remove a plugin directory RELIABLY on filesystems that refuse rmdir
	 * ("Permission denied" on Windows/LocalWP when a handle is held). Proven by
	 * live trace: core deletes the files fine, only the final rmdir of the empty
	 * folder is denied — while rename() succeeds on that same folder. So we delete
	 * files, try rmdir, and if the directory still won't go, RENAME it out to
	 * quarantine. Returns true once plugins/<slug> no longer exists.
	 */
	function rafah_guardian_hard_remove_dir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return true;
		}
		try {
			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $it as $node ) {
				$node->isDir() ? @rmdir( $node->getPathname() ) : @unlink( $node->getPathname() );
			}
		} catch ( \Throwable $e ) {}
		@rmdir( $dir );
		if ( is_dir( $dir ) ) {
			// OS is holding the directory handle → relocate it (rename works).
			wp_mkdir_p( RAFAH_GUARDIAN_QUARANTINE );
			if ( ! is_file( RAFAH_GUARDIAN_QUARANTINE . '/index.php' ) ) {
				@file_put_contents( RAFAH_GUARDIAN_QUARANTINE . '/index.php', "<?php // Silence is golden.\n" );
			}
			@rename( $dir, RAFAH_GUARDIAN_QUARANTINE . '/' . basename( $dir ) . '-del-' . gmdate( 'YmdHis' ) );
		}
		return ! is_dir( $dir );
	}
}

if ( ! function_exists( 'rafah_guardian_remove_or_park_empty' ) ) {
	/**
	 * Remove an EMPTY directory for good. If the OS still refuses rmdir (a
	 * watcher/mount holds the handle — dev-box only), move it into ONE bounded
	 * hidden bucket so the quarantine top level never sprawls; the bucket is
	 * cleared in full the moment the OS releases the handles.
	 */
	function rafah_guardian_remove_or_park_empty( $dir, $pending ) {
		if ( rafah_guardian_rmdir_empty_tree( $dir ) ) {
			return true; // actually gone
		}
		if ( 0 === strpos( $dir, $pending ) ) {
			return false; // already inside the bucket
		}
		wp_mkdir_p( $pending );
		@rename( $dir, $pending . '/' . basename( $dir ) );
		return ! is_dir( $dir );
	}
}

if ( ! function_exists( 'rafah_guardian_sweep_quarantine' ) ) {
	/**
	 * Keep the quarantine transient — it is NEVER permanent storage.
	 *   • EMPTY directories are removed immediately (they hold nothing of value;
	 *     retried each run until the OS releases the lock — well under 24h).
	 *   • Directories that still contain REAL FILES are kept as a recovery point
	 *     only for a retention window (default 30 days, filterable), then their
	 *     files are deleted and the folder removed.
	 * Runs on every plugin-screen load, after each plugin operation, and daily
	 * via cron, so nothing lingers.
	 */
	function rafah_guardian_sweep_quarantine() {
		if ( ! is_dir( RAFAH_GUARDIAN_QUARANTINE ) ) {
			return;
		}
		$now       = time();
		$keep_secs = max( 0, (int) apply_filters( 'rafah_quarantine_retention_days', 30 ) ) * DAY_IN_SECONDS;
		$pending   = RAFAH_GUARDIAN_QUARANTINE . '/.pending-os-unlock';

		// Keep retrying the parked bucket — it clears the instant the OS releases
		// the directory handles (always in production; at session end on a watched
		// dev box). glob() skips this dot-folder, so it is handled only here.
		if ( is_dir( $pending ) ) {
			rafah_guardian_rmdir_empty_tree( $pending );
		}

		foreach ( (array) glob( RAFAH_GUARDIAN_QUARANTINE . '/*', GLOB_ONLYDIR ) as $d ) {
			if ( rafah_guardian_is_empty_leftover( $d ) ) {
				// Empty shell → remove for real; if the OS still holds its handle,
				// park it in ONE bounded bucket so quarantine never sprawls.
				rafah_guardian_remove_or_park_empty( $d, $pending );
				continue;
			}
			// Has real files → keep as a recovery point, but purge past the window.
			if ( ( $now - (int) @filemtime( $d ) ) >= $keep_secs ) {
				try {
					$it = new RecursiveIteratorIterator(
						new RecursiveDirectoryIterator( $d, FilesystemIterator::SKIP_DOTS ),
						RecursiveIteratorIterator::CHILD_FIRST
					);
					foreach ( $it as $n ) {
						$n->isDir() ? @rmdir( $n->getPathname() ) : @unlink( $n->getPathname() );
					}
				} catch ( \Throwable $e ) {}
				rafah_guardian_remove_or_park_empty( $d, $pending );
				rafah_guardian_log( 'quarantine_purged', basename( $d ) );
			}
		}

		// Tidy stray non-folder residue (old trace/log files) once expired.
		foreach ( (array) glob( RAFAH_GUARDIAN_QUARANTINE . '/*' ) as $f ) {
			if ( is_file( $f ) && 'index.php' !== basename( $f ) && ( $now - (int) @filemtime( $f ) ) >= $keep_secs ) {
				@unlink( $f );
			}
		}
	}
}

// Daily cron guarantees the 24h/retention cleanup even with no admin visits.
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'rafah_guardian_daily_sweep' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'rafah_guardian_daily_sweep' );
	}
} );
add_action( 'rafah_guardian_daily_sweep', 'rafah_guardian_sweep_quarantine' );

/**
 * ROOT-CAUSE FIX for "Could not fully remove the plugin …".
 * Fires immediately BEFORE core's $wp_filesystem->delete(). We remove the plugin
 * directory here using the operation the OS honors; core's own delete() then
 * sees the folder already gone and returns TRUE, so WordPress reports SUCCESS.
 * Single-file plugins (no "/") are left to core — their unlink already works.
 */
add_action( 'delete_plugin', function ( $plugin_file ) {
	if ( false === strpos( (string) $plugin_file, '/' ) ) {
		return;
	}
	rafah_guardian_hard_remove_dir( WP_PLUGIN_DIR . '/' . dirname( $plugin_file ) );
}, 5 );

// --- Action handlers (Repair / Ignore) --------------------------------------

add_action( 'admin_post_rafah_guardian_repair', function () {
	if ( ! current_user_can( 'install_plugins' ) || ! check_admin_referer( 'rafah_guardian' ) ) {
		wp_die( 'Not allowed' );
	}
	$slug = sanitize_key( $_POST['slug'] ?? '' );
	$done = rafah_guardian_repair( $slug );
	set_transient( 'rafah_guardian_msg', $done
		? sprintf( 'Repaired "%s": the leftover folder was moved to wp-content/rafah-guardian-quarantine/. You can now install the plugin normally.', $slug )
		: sprintf( 'Could not move "%s" automatically (file permissions). Delete wp-content/plugins/%s manually.', $slug, $slug ), 60 );
	wp_safe_redirect( wp_get_referer() ?: admin_url() );
	exit;
} );

add_action( 'admin_post_rafah_guardian_ignore', function () {
	if ( ! current_user_can( 'install_plugins' ) || ! check_admin_referer( 'rafah_guardian' ) ) {
		wp_die( 'Not allowed' );
	}
	$slug = sanitize_key( $_POST['slug'] ?? '' );
	if ( $slug ) {
		$ignored = (array) get_option( 'rafah_guardian_ignored', array() );
		if ( ! in_array( $slug, $ignored, true ) ) {
			$ignored[] = $slug;
			update_option( 'rafah_guardian_ignored', array_values( array_unique( $ignored ) ), false );
		}
		rafah_guardian_log( 'ignore', $slug );
		set_transient( 'rafah_guardian_msg', sprintf( 'Ignored "%s" — Rafah Guardian will no longer notify you about it.', $slug ), 60 );
	}
	wp_safe_redirect( wp_get_referer() ?: admin_url() );
	exit;
} );

// --- Notices ----------------------------------------------------------------

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'activate_plugins' ) ) { return; }

	if ( $msg = get_transient( 'rafah_guardian_msg' ) ) {
		delete_transient( 'rafah_guardian_msg' );
		echo '<div class="notice notice-success is-dismissible"><p><strong>Rafah Guardian:</strong> ' . esc_html( $msg ) . '</p></div>';
	}

	// Self-heal: report what was auto-cleaned (informational, auto-clears).
	if ( $healed = get_transient( 'rafah_guardian_healed' ) ) {
		delete_transient( 'rafah_guardian_healed' );
		echo '<div class="notice notice-success is-dismissible"><p><strong>Rafah Guardian:</strong> automatically cleaned '
			. (int) count( (array) $healed ) . ' leftover plugin folder(s) from a filesystem-locked operation ('
			. esc_html( implode( ', ', (array) $healed ) ) . '). Nothing to do — reinstalling is unblocked.</p></div>';
	}

	// Only case that ever needs a click: the OS is STILL holding a folder. Offer
	// an automatic retry — never a request to delete folders or pause Cowork.
	$heal_failed = (array) get_option( 'rafah_guardian_heal_failed', array() );
	if ( $heal_failed ) {
		$hpost  = esc_url( admin_url( 'admin-post.php' ) );
		$hnonce = wp_create_nonce( 'rafah_guardian' );
		echo '<div class="notice notice-warning is-dismissible"><p><strong>Rafah Guardian:</strong> the operating system is momentarily holding '
			. esc_html( implode( ', ', $heal_failed ) ) . ' so its empty folder could not be removed yet. This clears itself automatically on the next plugin-screen load — or retry now:</p>';
		echo '<form method="post" action="' . $hpost . '" style="display:inline">'
			. '<input type="hidden" name="action" value="rafah_guardian_retry_heal">'
			. '<input type="hidden" name="_wpnonce" value="' . esc_attr( $hnonce ) . '">'
			. '<button class="button button-primary">' . esc_html__( 'Retry cleanup now', 'rafah' ) . '</button></form></div>';
	}

	try { $problems = rafah_guardian_scan(); } catch ( \Throwable $e ) { return; }
	if ( empty( $problems ) ) { return; }

	$required = array_filter( $problems, fn( $p ) => 'required' === $p['kind'] );
	$optional = array_filter( $problems, fn( $p ) => 'optional' === $p['kind'] );

	// REQUIRED — hard error (unchanged behaviour).
	if ( $required ) {
		echo '<div class="notice notice-error"><p><strong>Rafah Guardian:</strong> a required plugin is broken/incomplete — the site may not render correctly.</p><ul style="list-style:disc;margin-inline-start:22px;">';
		foreach ( $required as $p ) { echo '<li>' . esc_html( $p['msg'] ) . '</li>'; }
		echo '</ul><p>Reinstall the affected plugin(s) or restore a backup/snapshot. Do <strong>not</strong> create placeholder files.</p></div>';
	}

	// OPTIONAL — soft, dismissible, actionable. Never a permanent warning.
	if ( $optional ) {
		$post  = esc_url( admin_url( 'admin-post.php' ) );
		$nonce = wp_create_nonce( 'rafah_guardian' );
		echo '<div class="notice notice-warning is-dismissible"><p><strong>Rafah Guardian:</strong> an optional plugin left a broken folder behind. This does not affect the site — you can repair it (so you can reinstall) or ignore it permanently.</p>';
		foreach ( $optional as $p ) {
			$slug = esc_attr( $p['slug'] );
			echo '<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:6px 0;">';
			echo '<span style="min-width:280px">' . esc_html( $p['msg'] ) . '</span>';
			// Repair
			echo '<form method="post" action="' . $post . '" style="display:inline" onsubmit="return confirm(\'Move the leftover ' . $slug . ' folder to quarantine? It is reversible.\');">';
			echo '<input type="hidden" name="action" value="rafah_guardian_repair"><input type="hidden" name="slug" value="' . $slug . '"><input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
			echo '<button class="button button-primary">' . esc_html__( 'Repair (move folder aside)', 'rafah' ) . '</button></form>';
			// Ignore
			echo '<form method="post" action="' . $post . '" style="display:inline">';
			echo '<input type="hidden" name="action" value="rafah_guardian_ignore"><input type="hidden" name="slug" value="' . $slug . '"><input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
			echo '<button class="button">' . esc_html__( 'Ignore permanently', 'rafah' ) . '</button></form>';
			echo '</div>';
		}
		echo '</div>';
	}
} );

add_action( 'admin_post_rafah_guardian_retry_heal', function () {
	if ( ! current_user_can( 'install_plugins' ) || ! check_admin_referer( 'rafah_guardian' ) ) {
		wp_die( 'Not allowed' );
	}
	$res = rafah_guardian_autoheal();
	set_transient( 'rafah_guardian_msg', empty( $res['failed'] )
		? 'All leftover folders cleaned automatically.'
		: sprintf( 'Still held by the OS: %s. No manual action needed — it will clear itself on the next attempt.', implode( ', ', $res['failed'] ) ), 60 );
	wp_safe_redirect( wp_get_referer() ?: admin_url( 'plugins.php' ) );
	exit;
} );

// --- SELF-HEALING triggers: after every plugin operation + on plugin screens -

// After a delete (fires per plugin inside delete_plugins()).
add_action( 'deleted_plugin', function () {
	rafah_guardian_autoheal();
	rafah_guardian_prune_active_plugins();
}, 20 );

// Proactive cleanup at REQUEST START on the plugin/update screens (admin_init,
// NOT inside the updater). Running here frees any empty-leftover destination
// BEFORE the user clicks install/update, so reinstalls never hit "Destination
// folder already exists" — while zero Guardian code runs inside WordPress's
// install/update execution path.
add_action( 'admin_init', function () {
	global $pagenow;
	if ( in_array( $pagenow, array( 'plugins.php', 'plugin-install.php', 'update.php', 'update-core.php' ), true ) ) {
		rafah_guardian_autoheal();
	}
} );

// --- Event logging (audit trail) --------------------------------------------

add_action( 'activated_plugin',   function ( $plugin ) { rafah_guardian_log( 'plugin_activated', (string) $plugin ); }, 10, 1 );
add_action( 'deactivated_plugin', function ( $plugin ) { rafah_guardian_log( 'plugin_deactivated', (string) $plugin ); }, 10, 1 );
add_action( 'switch_theme',       function ( $name )   { rafah_guardian_log( 'theme_switched', (string) $name ); }, 10, 1 );
// Audit trail only — a single option write, no filesystem scan, no healing.
// We deliberately do NOT run rafah_guardian_scan() here: that walks the plugin
// directory and would be Guardian doing work inside WordPress's update request.
// Health is (re)assessed passively on the next plugins-screen load (admin_init
// autoheal, above) and by Safe Update's async cron health check — never in-line
// with the updater.
add_action( 'upgrader_process_complete', function ( $u, $data ) {
	$type   = isset( $data['type'] ) ? (string) $data['type'] : 'unknown';
	$action = isset( $data['action'] ) ? (string) $data['action'] : 'unknown';
	rafah_guardian_log( 'upgrade', sprintf( 'type=%s action=%s (health check deferred)', $type, $action ) );
}, 10, 2 );

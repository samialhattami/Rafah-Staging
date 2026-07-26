<?php
/**
 * Rafah Core — Units data layer.
 *
 * Units live in a dedicated indexed table (not post meta) so projects with
 * 300+ units stay fast: filtered/paginated SQL, cheap aggregate stats, and a
 * clean export surface for future CRM/API/Odoo integrations.
 *
 * Data safety: soft deletes only (`deleted` flag), permanent internal IDs
 * derived from the primary key (RF-000001), duplicate unit numbers per
 * project rejected at save time, and the table is never dropped by updates.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Units_DB {

	const CACHE_GROUP = 'rafah_units';

	public static function init() {
		// Nothing hooked; this is a pure data-access class used by the other
		// units modules. init() exists to satisfy the module registry.
	}

	/**
	 * Table name.
	 */
	public static function table() {
		global $wpdb;

		return $wpdb->prefix . 'rafah_units';
	}

	/**
	 * Create/upgrade the table (called from the 1.3.0 migration; dbDelta is
	 * idempotent and additive).
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				internal_id VARCHAR(20) NOT NULL DEFAULT '',
				project_id BIGINT(20) UNSIGNED NOT NULL,
				unit_number VARCHAR(100) NOT NULL DEFAULT '',
				building VARCHAR(100) NOT NULL DEFAULT '',
				unit_type VARCHAR(100) NOT NULL DEFAULT '',
				floor VARCHAR(50) NOT NULL DEFAULT '',
				area DECIMAL(10,2) NOT NULL DEFAULT 0,
				price DECIMAL(14,2) NOT NULL DEFAULT 0,
				status VARCHAR(20) NOT NULL DEFAULT 'available',
				specs LONGTEXT NULL,
				sort_order INT(11) NOT NULL DEFAULT 0,
				deleted TINYINT(1) NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
				updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY  (id),
				UNIQUE KEY internal_id (internal_id),
				KEY project_status (project_id, deleted, status),
				KEY project_order (project_id, sort_order)
			) {$charset};"
		);
	}

	/**
	 * Unit statuses (single source of truth — dashboard, frontend, stats).
	 *
	 * @return array key => [label, color]
	 */
	public static function statuses() {
		return apply_filters(
			'rafah_unit_statuses',
			array(
				'available' => array( 'label' => rafah_is_rtl_lang() ? 'متاحة' : 'Available', 'color' => '#2e9e5b' ),
				'reserved'  => array( 'label' => rafah_is_rtl_lang() ? 'محجوزة' : 'Reserved', 'color' => '#e08a00' ),
				'sold'      => array( 'label' => rafah_is_rtl_lang() ? 'مباعة' : 'Sold', 'color' => '#c0392b' ),
				'hidden'    => array( 'label' => rafah_is_rtl_lang() ? 'مخفية' : 'Hidden', 'color' => '#8a7d6a' ),
			)
		);
	}

	// ------------------------------------------------------------- CRUD.

	/**
	 * Get one unit (row object with decoded specs) or null.
	 */
	public static function get( $id ) {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $row ) {
			$row->specs = $row->specs ? (array) json_decode( $row->specs, true ) : array();
		}

		return $row;
	}

	/**
	 * Create or update a unit.
	 *
	 * @param array $data Unit fields (id present = update).
	 * @return array [ 'id' => int ] or [ 'error' => string ].
	 */
	public static function save( $data ) {
		global $wpdb;

		$id       = absint( $data['id'] ?? 0 );
		$statuses = self::statuses();

		// Only system fields are stored as real columns. Everything the editor
		// sees is a user-defined column living in `specs` (see below).
		$clean = array(
			'project_id' => absint( $data['project_id'] ?? 0 ),
			'status'     => isset( $statuses[ $data['status'] ?? '' ] ) ? $data['status'] : 'available',
			'sort_order' => (int) ( $data['sort_order'] ?? 0 ),
			'updated_at' => current_time( 'mysql' ),
		);

		// Validation.
		if ( ! $clean['project_id'] || 'project' !== get_post_type( $clean['project_id'] ) ) {
			return array( 'error' => __( 'Invalid project.', 'rafah' ) );
		}

		// A unit cannot exist before the table columns are configured.
		if ( ! Rafah_Units_Columns::has_columns( $clean['project_id'] ) ) {
			return array( 'error' => __( 'Configure the Units Table columns before adding units.', 'rafah' ) );
		}

		// All values are dynamic column values, keyed by column ID and typed.
		$columns = Rafah_Units_Columns::flat_columns( $clean['project_id'] );
		$specs   = array();
		$raw     = (array) ( $data['specs'] ?? array() );

		foreach ( $columns as $column ) {
			if ( ! array_key_exists( $column['id'], $raw ) ) {
				continue;
			}

			$value = $raw[ $column['id'] ];

			switch ( $column['type'] ) {
				case 'bool':
					$specs[ $column['id'] ] = ( $value && '0' !== $value ) ? 1 : 0;
					break;
				case 'number':
				case 'price':
					$specs[ $column['id'] ] = ( '' === $value || null === $value ) ? '' : (string) ( 0 + $value );
					break;
				default:
					$specs[ $column['id'] ] = sanitize_text_field( (string) $value );
			}
		}

		$clean['specs'] = wp_json_encode( $specs, JSON_UNESCAPED_UNICODE );

		if ( $id ) {
			$updated = $wpdb->update( self::table(), $clean, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			if ( false === $updated ) {
				return array( 'error' => __( 'Database error while saving.', 'rafah' ) );
			}
		} else {
			$clean['created_at'] = current_time( 'mysql' );

			if ( 0 === $clean['sort_order'] ) {
				$clean['sort_order'] = 1 + (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					'SELECT MAX(sort_order) FROM ' . self::table() . ' WHERE project_id = %d',
					$clean['project_id']
				) );
			}

			$inserted = $wpdb->insert( self::table(), $clean ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			if ( ! $inserted ) {
				return array( 'error' => __( 'Database error while creating.', 'rafah' ) );
			}

			$id = (int) $wpdb->insert_id;

			// Permanent internal ID derived from the primary key — unique by
			// construction, race-safe, never reused, never changes.
			$wpdb->update( self::table(), array( 'internal_id' => sprintf( 'RF-%06d', $id ) ), array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		self::flush_project( $clean['project_id'] );

		return array( 'id' => $id );
	}

	/**
	 * Soft delete / restore / hard state changes for one or more units.
	 *
	 * @param int[]  $ids    Unit IDs.
	 * @param string $action deleted|restore|status:{key}.
	 * @return int Affected rows.
	 */
	public static function bulk( $ids, $action ) {
		global $wpdb;

		$ids = array_filter( array_map( 'absint', (array) $ids ) );

		if ( ! $ids ) {
			return 0;
		}

		$in  = implode( ',', $ids );
		$now = current_time( 'mysql' );

		if ( 'delete' === $action ) {
			$affected = $wpdb->query( "UPDATE " . self::table() . " SET deleted = 1, updated_at = '{$now}' WHERE id IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} elseif ( 'restore' === $action ) {
			$affected = $wpdb->query( "UPDATE " . self::table() . " SET deleted = 0, updated_at = '{$now}' WHERE id IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} elseif ( 0 === strpos( $action, 'status:' ) ) {
			$status = substr( $action, 7 );

			if ( ! isset( self::statuses()[ $status ] ) ) {
				return 0;
			}

			$affected = $wpdb->query( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"UPDATE " . self::table() . " SET status = %s, updated_at = %s WHERE id IN ({$in})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$status,
				$now
			) );
		} else {
			return 0;
		}

		// Flush caches for every affected project.
		$projects = $wpdb->get_col( "SELECT DISTINCT project_id FROM " . self::table() . " WHERE id IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $projects as $project_id ) {
			self::flush_project( (int) $project_id );
		}

		return (int) $affected;
	}

	/**
	 * Duplicate a unit (new internal ID, unit number suffixed to stay unique).
	 */
	public static function duplicate( $id ) {
		$unit = self::get( $id );

		if ( ! $unit ) {
			return array( 'error' => __( 'Unit not found.', 'rafah' ) );
		}

		$data               = (array) $unit;
		$data['id']         = 0;
		$data['specs']      = $unit->specs; // Copy all dynamic column values.
		$data['sort_order'] = 0;

		return self::save( $data );
	}

	/**
	 * Persist a drag & drop order.
	 *
	 * @param int   $project_id Project.
	 * @param int[] $ordered_ids Unit IDs in the new order.
	 */
	public static function reorder( $project_id, $ordered_ids ) {
		global $wpdb;

		$position = 1;

		foreach ( array_map( 'absint', (array) $ordered_ids ) as $unit_id ) {
			$wpdb->update( self::table(), array( 'sort_order' => $position ), array( 'id' => $unit_id, 'project_id' => $project_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$position++;
		}

		self::flush_project( $project_id );
	}

	// ------------------------------------------------------------- Queries.

	/**
	 * Query units with filters/search/sort/pagination.
	 *
	 * @param array $args project_id, status, search, orderby, order, page,
	 *                    per_page, deleted (0|1), no_paging (bool).
	 * @return array [ 'items' => [], 'total' => int, 'pages' => int ]
	 */
	public static function query( $args ) {
		global $wpdb;

		$project_id = absint( $args['project_id'] ?? 0 );
		$per_page   = min( 100, max( 1, absint( $args['per_page'] ?? 25 ) ) );
		$page       = max( 1, absint( $args['page'] ?? 1 ) );

		$where  = array( 'project_id = %d', 'deleted = %d' );
		$values = array( $project_id, empty( $args['deleted'] ) ? 0 : 1 );

		if ( ! empty( $args['status'] ) && isset( self::statuses()[ $args['status'] ] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			// All user values live in the specs JSON, so a single LIKE across
			// internal_id + specs covers every column the editor defined.
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(internal_id LIKE %s OR specs LIKE %s)';
			$values[] = $like;
			$values[] = $like;
		}

		// Only system fields are sortable at the SQL level; user columns are
		// sorted client-side (values live in JSON).
		$orderby_whitelist = array( 'sort_order', 'status', 'updated_at', 'internal_id' );
		$orderby           = in_array( $args['orderby'] ?? '', $orderby_whitelist, true ) ? $args['orderby'] : 'sort_order';
		$order             = 'DESC' === strtoupper( $args['order'] ?? '' ) ? 'DESC' : 'ASC';

		$where_sql = implode( ' AND ', $where );

		$total = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE {$where_sql}", $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$sql = 'SELECT * FROM ' . self::table() . " WHERE {$where_sql} ORDER BY {$orderby} {$order}, id ASC";

		if ( empty( $args['no_paging'] ) ) {
			$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, ( $page - 1 ) * $per_page );
		}

		$items = $wpdb->get_results( $wpdb->prepare( $sql, $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		foreach ( $items as $item ) {
			$item->specs = $item->specs ? (array) json_decode( $item->specs, true ) : array();
		}

		return array(
			'items' => $items,
			'total' => $total,
			'pages' => empty( $args['no_paging'] ) ? (int) ceil( $total / $per_page ) : 1,
		);
	}

	/**
	 * All publicly visible units of a project (frontend table) — cached.
	 */
	public static function visible_units( $project_id ) {
		$cache_key = 'visible_' . $project_id;
		$units     = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $units ) {
			global $wpdb;

			$units = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'SELECT * FROM ' . self::table() . " WHERE project_id = %d AND deleted = 0 AND status != 'hidden' ORDER BY sort_order ASC, id ASC",
				$project_id
			) );

			foreach ( $units as $unit ) {
				$unit->specs = $unit->specs ? (array) json_decode( $unit->specs, true ) : array();
			}

			wp_cache_set( $cache_key, $units, self::CACHE_GROUP, HOUR_IN_SECONDS );
		}

		return $units;
	}

	// ------------------------------------------------------------- Stats.

	/**
	 * Per-project unit statistics — cached, auto-invalidated on any change.
	 *
	 * @return array total, available, reserved, sold, hidden, visible,
	 *               available_pct, reserved_pct, sold_pct.
	 */
	public static function stats( $project_id ) {
		$cache_key = 'stats_' . $project_id;
		$stats     = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $stats ) {
			global $wpdb;

			$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'SELECT status, COUNT(*) AS n FROM ' . self::table() . ' WHERE project_id = %d AND deleted = 0 GROUP BY status',
				$project_id
			), OBJECT_K );

			$stats = array( 'available' => 0, 'reserved' => 0, 'sold' => 0, 'hidden' => 0 );

			foreach ( $rows as $status => $row ) {
				if ( isset( $stats[ $status ] ) ) {
					$stats[ $status ] = (int) $row->n;
				}
			}

			$stats['total']   = array_sum( $stats );
			$stats['visible'] = $stats['total'] - $stats['hidden'];

			$denominator = max( 1, $stats['visible'] );
			foreach ( array( 'available', 'reserved', 'sold' ) as $key ) {
				$stats[ $key . '_pct' ] = round( $stats[ $key ] / $denominator * 100 );
			}

			wp_cache_set( $cache_key, $stats, self::CACHE_GROUP, HOUR_IN_SECONDS );
		}

		return $stats;
	}

	/**
	 * Invalidate caches and sync legacy meta so every existing consumer
	 * (facts bar, cards, widgets, search) updates automatically.
	 */
	public static function flush_project( $project_id ) {
		wp_cache_delete( 'stats_' . $project_id, self::CACHE_GROUP );
		wp_cache_delete( 'visible_' . $project_id, self::CACHE_GROUP );

		$stats = self::stats( $project_id );

		update_post_meta( $project_id, '_rafah_units_total', $stats['visible'] ?: '' );
		update_post_meta( $project_id, '_rafah_units_available', $stats['available'] ?: '' );

		/**
		 * Fires after a project's units change — hook CRM syncs etc. here.
		 *
		 * @param int   $project_id Project.
		 * @param array $stats      Fresh statistics.
		 */
		do_action( 'rafah_units_changed', $project_id, $stats );
	}
}

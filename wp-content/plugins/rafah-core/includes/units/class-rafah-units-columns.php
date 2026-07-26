<?php
/**
 * Rafah Core — Dynamic column builder for the units table.
 *
 * Every project defines its own extra columns, organised into groups that
 * render as grouped headers (e.g. "Special Features" spanning Parking,
 * Balcony, Roof…). Stored as JSON in post meta `_rafah_unit_columns`.
 *
 * Column IDs are permanent random keys — renaming a column only changes its
 * label, so existing unit data is never orphaned.
 *
 * Structure:
 * [
 *   { "id": "g_ab12", "label": "مميزات خاصة", "columns": [
 *       { "id": "c_9f3k", "label": "موقف سيارة", "type": "bool" },
 *       { "id": "c_77aq", "label": "مساحة الحديقة", "type": "text" }
 *   ] }
 * ]
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Units_Columns {

	const META_KEY = '_rafah_unit_columns';

	public static function init() {}

	/**
	 * Column groups for a project (validated).
	 *
	 * There are NO default columns: a new project starts with an empty table
	 * and the editor defines every column themselves (add / rename / delete /
	 * reorder / group). This keeps the model suitable for any project type —
	 * apartments, villas, offices, land — with only the system fields
	 * (internal ID, status, order) locked.
	 */
	public static function groups( $project_id ) {
		$groups = get_post_meta( $project_id, self::META_KEY, true );

		if ( ! is_array( $groups ) ) {
			$groups = array();
		}

		return apply_filters( 'rafah_unit_columns', $groups, $project_id );
	}

	/**
	 * Whether a project has at least one usable (named) column configured.
	 * Add Unit is blocked until this is true.
	 */
	public static function has_columns( $project_id ) {
		foreach ( self::flat_columns( $project_id ) as $column ) {
			if ( '' !== trim( (string) ( $column['label'] ?? '' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Flat list of dynamic columns (across all groups).
	 *
	 * @return array[] Each: id, label, type, group_label.
	 */
	public static function flat_columns( $project_id ) {
		$flat = array();

		foreach ( self::groups( $project_id ) as $group ) {
			foreach ( (array) ( $group['columns'] ?? array() ) as $column ) {
				$column['group_label'] = $group['label'] ?? '';
				$flat[]                = $column;
			}
		}

		return $flat;
	}

	/**
	 * Sanitize + persist a column configuration.
	 *
	 * @param int   $project_id Project.
	 * @param array $raw        Raw groups payload from the builder UI.
	 * @return array Clean groups as saved.
	 */
	public static function save( $project_id, $raw ) {
		$clean = self::sanitize_groups( $raw );

		update_post_meta( $project_id, self::META_KEY, $clean );
		Rafah_Units_DB::flush_project( $project_id );

		return $clean;
	}

	/**
	 * Sanitize a raw groups payload into clean, storable groups. Reused by
	 * project save, templates, and structure copy.
	 *
	 * Version safety: existing permanent column IDs are preserved, so renaming
	 * a column changes only its label — stored unit values (keyed by ID) are
	 * never lost. Unnamed columns are dropped.
	 */
	public static function sanitize_groups( $raw ) {
		$clean = array();

		foreach ( (array) $raw as $group ) {
			$columns = array();

			foreach ( (array) ( $group['columns'] ?? array() ) as $column ) {
				$label = sanitize_text_field( $column['label'] ?? '' );

				if ( '' === $label ) {
					continue;
				}

				$columns[] = array(
					'id'    => self::sanitize_key_id( $column['id'] ?? '', 'c' ),
					'label' => $label,
					'type'  => in_array( $column['type'] ?? '', array( 'text', 'bool', 'number', 'price' ), true ) ? $column['type'] : 'text',
				);
			}

			$clean[] = array(
				'id'      => self::sanitize_key_id( $group['id'] ?? '', 'g' ),
				'label'   => sanitize_text_field( $group['label'] ?? '' ),
				'columns' => $columns,
			);
		}

		return $clean;
	}

	/**
	 * Case-insensitive duplicate column labels across all groups.
	 *
	 * @return string[] The labels that appear more than once.
	 */
	public static function duplicate_labels( $raw ) {
		$seen  = array();
		$dupes = array();

		foreach ( (array) $raw as $group ) {
			foreach ( (array) ( $group['columns'] ?? array() ) as $column ) {
				$label = trim( (string) ( $column['label'] ?? '' ) );

				if ( '' === $label ) {
					continue;
				}

				$key = function_exists( 'mb_strtolower' ) ? mb_strtolower( $label ) : strtolower( $label );

				if ( isset( $seen[ $key ] ) ) {
					$dupes[ $label ] = true;
				} else {
					$seen[ $key ] = true;
				}
			}
		}

		return array_keys( $dupes );
	}

	/**
	 * Of the given column IDs, which still hold data in at least one unit.
	 * Used to warn before deleting a column that contains values.
	 *
	 * @return string[] Column IDs that have data.
	 */
	public static function columns_with_data( $project_id, $ids ) {
		$ids = array_values( array_filter( (array) $ids ) );

		if ( ! $ids ) {
			return array();
		}

		global $wpdb;

		$rows = $wpdb->get_col( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'SELECT specs FROM ' . Rafah_Units_DB::table() . ' WHERE project_id = %d AND deleted = 0',
			$project_id
		) );

		$found = array();

		foreach ( $rows as $json ) {
			$specs = $json ? (array) json_decode( $json, true ) : array();

			foreach ( $ids as $id ) {
				if ( isset( $specs[ $id ] ) && '' !== (string) $specs[ $id ] && '0' !== (string) $specs[ $id ] ) {
					$found[ $id ] = true;
				}
			}
		}

		return array_keys( $found );
	}

	// ---------------------------------------------------------- Reusable templates.

	const TEMPLATES_OPTION = 'rafah_unit_column_templates';

	/**
	 * All saved column templates (site-wide, reusable layouts).
	 *
	 * @return array[] Each: id, name, groups.
	 */
	public static function templates() {
		$templates = get_option( self::TEMPLATES_OPTION, array() );

		return is_array( $templates ) ? $templates : array();
	}

	/**
	 * Save the given layout as a named template (replacing one with the same
	 * name, case-insensitive). Non-autoloaded option.
	 *
	 * @return array[] The updated templates list.
	 */
	public static function save_template( $name, $raw ) {
		$name = sanitize_text_field( $name );

		if ( '' === $name ) {
			return self::templates();
		}

		$templates = self::templates();
		$groups    = self::sanitize_groups( $raw );
		$lc        = function_exists( 'mb_strtolower' ) ? mb_strtolower( $name ) : strtolower( $name );
		$replaced  = false;

		foreach ( $templates as &$tpl ) {
			$existing = function_exists( 'mb_strtolower' ) ? mb_strtolower( $tpl['name'] ?? '' ) : strtolower( $tpl['name'] ?? '' );
			if ( $existing === $lc ) {
				$tpl['groups'] = $groups;
				$replaced      = true;
				break;
			}
		}
		unset( $tpl );

		if ( ! $replaced ) {
			$templates[] = array(
				'id'     => self::sanitize_key_id( '', 't' ),
				'name'   => $name,
				'groups' => $groups,
			);
		}

		update_option( self::TEMPLATES_OPTION, $templates, false );

		return $templates;
	}

	/**
	 * Delete a template by ID. Returns the updated list.
	 */
	public static function delete_template( $id ) {
		$id = sanitize_text_field( $id );

		$templates = array_values( array_filter( self::templates(), function ( $tpl ) use ( $id ) {
			return ( $tpl['id'] ?? '' ) !== $id;
		} ) );

		update_option( self::TEMPLATES_OPTION, $templates, false );

		return $templates;
	}

	/**
	 * Groups stored in a template.
	 */
	public static function template_groups( $id ) {
		foreach ( self::templates() as $tpl ) {
			if ( ( $tpl['id'] ?? '' ) === $id ) {
				return $tpl['groups'] ?? array();
			}
		}

		return array();
	}

	/**
	 * Other projects that already have a configured units table — used by the
	 * "Copy another project" structure picker.
	 *
	 * @return array[] Each: id, title.
	 */
	public static function source_projects( $exclude_id = 0 ) {
		$ids = get_posts( array(
			'post_type'      => 'project',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'numberposts'    => 300,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'exclude'        => array( (int) $exclude_id ),
			'fields'         => 'ids',
			'meta_query'     => array( array( 'key' => self::META_KEY, 'compare' => 'EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'suppress_filters' => false,
		) );

		$out = array();

		foreach ( $ids as $pid ) {
			if ( self::has_columns( $pid ) ) {
				$out[] = array( 'id' => (int) $pid, 'title' => get_the_title( $pid ) );
			}
		}

		return $out;
	}

	/**
	 * Keep an existing permanent ID or mint a new one.
	 */
	private static function sanitize_key_id( $id, $prefix ) {
		$id = preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $id ) );

		return $id ?: $prefix . '_' . substr( md5( uniqid( '', true ) ), 0, 6 );
	}
}

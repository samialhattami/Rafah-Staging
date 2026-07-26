<?php
/**
 * Rafah Core — Units import/export.
 *
 * Export: CSV (UTF-8 BOM so Excel renders Arabic correctly) and native XLSX
 * (minimal OOXML writer, no external libraries).
 *
 * Import: CSV with a strict two-step flow — validate (dry run, nothing
 * written) then apply. Rows with an Internal ID update that unit; rows
 * without one create new units. Invalid rows are reported and skipped;
 * nothing is ever overwritten silently.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Units_IO {

	public static function init() {
		add_action( 'admin_post_rafah_units_export', array( __CLASS__, 'export' ) );
		add_action( 'wp_ajax_rafah_units_import', array( __CLASS__, 'import' ) );
	}

	/**
	 * Column plan: system fields (internal ID, status, order) + the project's
	 * user-defined columns. All unit values are dynamic columns.
	 */
	private static function columns( $project_id ) {
		$fixed = array(
			'internal_id' => 'Internal ID',
			'status'      => 'Status',
			'sort_order'  => 'Order',
		);

		$dynamic = array();
		foreach ( Rafah_Units_Columns::flat_columns( $project_id ) as $column ) {
			$dynamic[ 'spec:' . $column['id'] ] = $column['label'];
		}

		return array_merge( $fixed, $dynamic );
	}

	private static function row_values( $unit, $columns ) {
		$row = array();

		foreach ( array_keys( $columns ) as $key ) {
			if ( 0 === strpos( $key, 'spec:' ) ) {
				$row[] = (string) ( $unit->specs[ substr( $key, 5 ) ] ?? '' );
			} else {
				$row[] = (string) $unit->{$key};
			}
		}

		return $row;
	}

	// ------------------------------------------------------------- Export.

	public static function export() {
		$project_id = absint( $_GET['project_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! wp_verify_nonce( sanitize_key( $_GET['nonce'] ?? '' ), 'rafah_units' ) || ! current_user_can( 'edit_post', $project_id ) ) {
			wp_die( esc_html__( 'Not allowed.', 'rafah' ) );
		}

		$format  = 'xlsx' === ( $_GET['format'] ?? '' ) ? 'xlsx' : 'csv'; // phpcs:ignore WordPress.Security.NonceVerification
		$columns = self::columns( $project_id );
		$units   = Rafah_Units_DB::query( array( 'project_id' => $project_id, 'no_paging' => true ) )['items'];
		$name    = 'rafah-units-' . $project_id . '-' . gmdate( 'Ymd' );

		if ( 'csv' === $format ) {
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $name . '.csv"' );

			$out = fopen( 'php://output', 'w' );
			fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM for Excel.
			fputcsv( $out, array_values( $columns ) );

			foreach ( $units as $unit ) {
				fputcsv( $out, self::row_values( $unit, $columns ) );
			}

			fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			exit;
		}

		// XLSX.
		$rows   = array( array_values( $columns ) );
		foreach ( $units as $unit ) {
			$rows[] = self::row_values( $unit, $columns );
		}

		$file = self::build_xlsx( $rows );

		if ( ! $file ) {
			wp_die( esc_html__( 'Could not build the Excel file (ZipArchive unavailable). Use CSV export instead.', 'rafah' ) );
		}

		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . $name . '.xlsx"' );
		header( 'Content-Length: ' . filesize( $file ) );
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/**
	 * Minimal OOXML spreadsheet writer (inline strings).
	 *
	 * @param array $rows Rows of string cells.
	 * @return string|false Temp file path.
	 */
	private static function build_xlsx( $rows ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return false;
		}

		$cell_ref = function ( $col ) {
			$ref = '';
			$col++;
			while ( $col > 0 ) {
				$mod = ( $col - 1 ) % 26;
				$ref = chr( 65 + $mod ) . $ref;
				$col = (int) ( ( $col - $mod ) / 26 );
			}
			return $ref;
		};

		$sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

		foreach ( $rows as $r => $row ) {
			$sheet .= '<row r="' . ( $r + 1 ) . '">';
			foreach ( $row as $c => $value ) {
				$ref = $cell_ref( $c ) . ( $r + 1 );
				if ( '' !== $value && is_numeric( $value ) && strlen( (string) $value ) < 15 && 'RF-' !== substr( (string) $value, 0, 3 ) ) {
					$sheet .= '<c r="' . $ref . '"><v>' . $value . '</v></c>';
				} else {
					$sheet .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . htmlspecialchars( (string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' ) . '</t></is></c>';
				}
			}
			$sheet .= '</row>';
		}

		$sheet .= '</sheetData></worksheet>';

		$file = wp_tempnam( 'rafah-units.xlsx' );
		$zip  = new ZipArchive();

		if ( true !== $zip->open( $file, ZipArchive::OVERWRITE ) ) {
			return false;
		}

		$zip->addFromString( '[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>' );
		$zip->addFromString( '_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>' );
		$zip->addFromString( 'xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Units" sheetId="1" r:id="rId1"/></sheets></workbook>' );
		$zip->addFromString( 'xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>' );
		$zip->addFromString( 'xl/worksheets/sheet1.xml', $sheet );
		$zip->close();

		return $file;
	}

	// ------------------------------------------------------------- Import.

	public static function import() {
		check_ajax_referer( 'rafah_units', 'nonce' );

		$project_id = absint( $_POST['project_id'] ?? 0 );

		if ( ! $project_id || ! current_user_can( 'edit_post', $project_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'rafah' ) ), 403 );
		}

		$apply = 'apply' === sanitize_key( $_POST['mode'] ?? 'validate' );

		if ( empty( $_FILES['file']['tmp_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'rafah' ) ) );
		}

		$handle = fopen( $_FILES['file']['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.Security.ValidatedSanitizedInput

		if ( ! $handle ) {
			wp_send_json_error( array( 'message' => __( 'Could not read the file.', 'rafah' ) ) );
		}

		// Header row → map to keys via the export column plan (labels or keys accepted).
		$columns = self::columns( $project_id );
		$header  = fgetcsv( $handle );

		if ( ! $header ) {
			wp_send_json_error( array( 'message' => __( 'The file is empty or not a valid CSV.', 'rafah' ) ) );
		}

		$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $header[0] ); // Strip BOM.

		$label_to_key = array_change_key_case( array_flip( array_map( 'strtolower', $columns ) ) );
		$map          = array();

		foreach ( $header as $i => $label ) {
			$label = strtolower( trim( (string) $label ) );
			if ( isset( $columns[ $label ] ) ) {
				$map[ $i ] = $label; // Header used raw keys.
			} elseif ( isset( $label_to_key[ $label ] ) ) {
				$map[ $i ] = $label_to_key[ $label ]; // Header used labels.
			}
		}

		// At least one recognised data column must be present.
		$has_data_col = false;
		foreach ( $map as $key ) {
			if ( 0 === strpos( $key, 'spec:' ) ) {
				$has_data_col = true;
				break;
			}
		}

		if ( ! $has_data_col ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			wp_send_json_error( array( 'message' => __( 'No matching table columns found in the file. Configure the Units Table and export first to get the correct format.', 'rafah' ) ) );
		}

		// Existing internal IDs for this project.
		global $wpdb;
		$existing = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'SELECT id, internal_id FROM ' . Rafah_Units_DB::table() . ' WHERE project_id = %d',
			$project_id
		), OBJECT_K );

		$by_internal = array();
		foreach ( $existing as $row ) {
			if ( $row->internal_id ) {
				$by_internal[ $row->internal_id ] = (int) $row->id;
			}
		}

		$statuses = array_keys( Rafah_Units_DB::statuses() );
		$status_labels = array();
		foreach ( Rafah_Units_DB::statuses() as $key => $def ) {
			$status_labels[ mb_strtolower( $def['label'] ) ] = $key;
		}

		$create = 0;
		$update = 0;
		$errors = array();
		$line   = 1;

		while ( ( $cells = fgetcsv( $handle ) ) !== false ) {
			$line++;

			if ( 1 === count( $cells ) && '' === trim( (string) $cells[0] ) ) {
				continue; // Blank line.
			}

			$data = array( 'project_id' => $project_id, 'specs' => array() );

			foreach ( $map as $i => $key ) {
				$value = trim( (string) ( $cells[ $i ] ?? '' ) );

				if ( 0 === strpos( $key, 'spec:' ) ) {
					$data['specs'][ substr( $key, 5 ) ] = $value;
				} else {
					$data[ $key ] = $value;
				}
			}

			// Status: accept keys or localized labels.
			if ( ! empty( $data['status'] ) && ! in_array( $data['status'], $statuses, true ) ) {
				$normalized = mb_strtolower( $data['status'] );
				if ( isset( $status_labels[ $normalized ] ) ) {
					$data['status'] = $status_labels[ $normalized ];
				} else {
					/* translators: 1: line number, 2: status value */
					$errors[] = sprintf( __( 'Line %1$d: unknown status "%2$s".', 'rafah' ), $line, $data['status'] );
					continue;
				}
			}

			// Internal ID → update; unknown internal ID is an error (never guess).
			$internal = strtoupper( trim( $data['internal_id'] ?? '' ) );
			unset( $data['internal_id'] );

			if ( $internal ) {
				if ( ! isset( $by_internal[ $internal ] ) ) {
					/* translators: 1: line number, 2: internal id */
					$errors[] = sprintf( __( 'Line %1$d: Internal ID %2$s does not exist in this project.', 'rafah' ), $line, $internal );
					continue;
				}
				$data['id'] = $by_internal[ $internal ];
			}

			if ( $apply ) {
				$result = Rafah_Units_DB::save( $data );

				if ( isset( $result['error'] ) ) {
					/* translators: 1: line number, 2: error */
					$errors[] = sprintf( __( 'Line %1$d: %2$s', 'rafah' ), $line, $result['error'] );
					continue;
				}
			}

			if ( $internal ) {
				$update++;
			} else {
				$create++;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		wp_send_json_success( array(
			'create' => $create,
			'update' => $update,
			'errors' => array_slice( $errors, 0, 50 ),
			'stats'  => Rafah_Units_DB::stats( $project_id ),
		) );
	}
}

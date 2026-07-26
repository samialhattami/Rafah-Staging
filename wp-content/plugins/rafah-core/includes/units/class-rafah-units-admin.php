<?php
/**
 * Rafah Core — Units Manager dashboard (CRM-style, AJAX-driven).
 *
 * Renders the Units Manager meta box on the project edit screen and exposes
 * the admin AJAX API. All persistence is instant (no page save needed), all
 * mutations are nonce + capability checked.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Units_Admin {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_box' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );

		foreach ( array( 'list', 'save', 'bulk', 'duplicate', 'reorder', 'columns_save', 'columns_sources', 'columns_load', 'template_save', 'template_delete' ) as $action ) {
			add_action( 'wp_ajax_rafah_units_' . $action, array( __CLASS__, 'ajax_' . $action ) );
		}
	}

	public static function register_box() {
		add_meta_box(
			'rafah_units_manager',
			__( 'Units Manager', 'rafah' ),
			array( __CLASS__, 'render' ),
			'project',
			'normal',
			'high'
		);
	}

	public static function assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'project' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'rafah-units-admin', RAFAH_CORE_URL . 'assets/css/units-admin.css', array(), RAFAH_CORE_VERSION );
		wp_enqueue_script( 'rafah-units-admin', RAFAH_CORE_URL . 'assets/js/units-admin.js', array( 'jquery', 'jquery-ui-sortable' ), RAFAH_CORE_VERSION, true );

		$post_id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification

		wp_localize_script(
			'rafah-units-admin',
			'rafahUnits',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'rafah_units' ),
				'projectId' => $post_id,
				'statuses'  => Rafah_Units_DB::statuses(),
				'columns'   => $post_id ? Rafah_Units_Columns::groups( $post_id ) : array(),
				'hasColumns' => $post_id ? Rafah_Units_Columns::has_columns( $post_id ) : false,
				'exportUrl' => admin_url( 'admin-post.php' ),
				'i18n'      => array(
					'addUnit'       => __( 'Add Unit', 'rafah' ),
					'editUnit'      => __( 'Edit Unit', 'rafah' ),
					'confirmDelete' => __( 'Move selected unit(s) to trash? They can be restored later.', 'rafah' ),
					'noUnits'       => __( 'No units yet. Click "Add Unit" or import a CSV file to get started.', 'rafah' ),
					'noResults'     => __( 'No units match your filters.', 'rafah' ),
					'saved'         => __( 'Saved', 'rafah' ),
					'error'         => __( 'Something went wrong', 'rafah' ),
					'restore'       => __( 'Restore', 'rafah' ),
					'edit'          => __( 'Edit', 'rafah' ),
					'duplicate'     => __( 'Duplicate', 'rafah' ),
					'trash'         => __( 'Trash', 'rafah' ),
					'yes'           => __( 'Yes', 'rafah' ),
					'group'         => __( 'Group', 'rafah' ),
					'column'        => __( 'Column', 'rafah' ),
					'confirmColumnDelete' => __( 'Remove this column? Unit data stays stored and returns if you re-add a column with the same key.', 'rafah' ),
					'search'        => __( 'Search units…', 'rafah' ),
					'showTrash'     => __( 'Show trashed', 'rafah' ),
					'bulkActions'   => __( 'Bulk actions', 'rafah' ),
					'apply'         => __( 'Apply', 'rafah' ),
					'setStatus'     => __( 'Set status:', 'rafah' ),
					'status'        => __( 'Status', 'rafah' ),
					'internalId'    => __( 'ID', 'rafah' ),
					'allStatuses'   => __( 'All statuses', 'rafah' ),
					'save'          => __( 'Save Unit', 'rafah' ),
					'saveAdd'       => __( 'Save & Add Another', 'rafah' ),
					'needColumnsTitle' => __( 'Configure the Units Table first', 'rafah' ),
					'needColumnsText'  => __( 'You must configure the Units Table before adding units. Define your columns (e.g. Unit No., Area, Price, Rooms…), then come back to add units.', 'rafah' ),
					'openColumns'   => __( 'Open Columns Manager', 'rafah' ),
					'addColumn'     => __( 'Add Column', 'rafah' ),
					'addGroup'      => __( 'Add Group', 'rafah' ),
					'saveColumns'   => __( 'Save Columns', 'rafah' ),
					'columnName'    => __( 'Column name', 'rafah' ),
					'columnsIntro'  => __( 'Build your units table: add columns, rename, reorder (drag ⋮⋮), and group them. Types — Text, Number, Price, Yes/No. Then save and start adding units.', 'rafah' ),
					'startFrom'     => __( 'Start from:', 'rafah' ),
					'copyProject'   => __( 'Copy another project…', 'rafah' ),
					'applyTemplate' => __( 'Apply a template…', 'rafah' ),
					'saveAsTemplate' => __( 'Save as template', 'rafah' ),
					'templateNamePrompt' => __( 'Template name (e.g. Apartments, Villas, Commercial, Land):', 'rafah' ),
					'templateSaved' => __( 'Template saved', 'rafah' ),
					'structureLoaded' => __( 'Structure loaded — review it, then click Save Columns.', 'rafah' ),
					'confirmReplaceStructure' => __( 'Replace the current columns with this structure? Unsaved changes will be lost.', 'rafah' ),
					'duplicateColumnMsg' => __( 'Column names must be unique. Please rename the duplicates.', 'rafah' ),
					'needColumnsToSave' => __( 'Add at least one column before saving a template.', 'rafah' ),
					'colDataConfirm' => __( 'These columns still contain data: %s. Delete them anyway?', 'rafah' ),
					'addFirstUnit'  => __( 'Start by adding your first unit.', 'rafah' ),
					'typeText'      => __( 'Text', 'rafah' ),
					'typeNumber'    => __( 'Number', 'rafah' ),
					'typePrice'     => __( 'Price', 'rafah' ),
					'typeBool'      => __( 'Yes / No', 'rafah' ),
					'exportCsv'     => __( 'Export CSV', 'rafah' ),
					'exportXlsx'    => __( 'Export Excel (XLSX)', 'rafah' ),
					'validate'      => __( 'Step 1 — Validate file', 'rafah' ),
					'import'        => __( 'Step 2 — Import', 'rafah' ),
					'willCreate'    => __( 'New units to create', 'rafah' ),
					'willUpdate'    => __( 'Existing units to update (matched by Internal ID)', 'rafah' ),
					'errorRows'     => __( 'Rows with errors (will be skipped)', 'rafah' ),
					'importDone'    => __( 'Import completed successfully.', 'rafah' ),
					'importHelp'    => __( 'Upload a CSV file (UTF-8). Export first to get the exact column format. Rows with an Internal ID update that unit; rows without one create new units. Nothing is written until you click Import.', 'rafah' ),
					'units'         => __( 'units', 'rafah' ),
				),
			)
		);
	}

	/**
	 * Meta box shell — everything inside is rendered by units-admin.js.
	 */
	public static function render( $post ) {
		// A project must be saved before its units table can be configured —
		// units are keyed to the project ID, which does not exist yet on a
		// brand-new (auto-draft) post.
		if ( 'auto-draft' === $post->post_status ) {
			echo '<div class="rafah-units-savefirst"><p><strong>' . esc_html__( 'Save the project first.', 'rafah' ) . '</strong></p><p>' . esc_html__( 'Publish or save this project as a draft, then configure the Units Table and start adding units.', 'rafah' ) . '</p></div>';
			return;
		}

		$stats = Rafah_Units_DB::stats( $post->ID );
		?>
		<div id="rafah-units-app" data-project="<?php echo esc_attr( $post->ID ); ?>">
			<div class="rafah-units-stats">
				<?php
				foreach ( array(
					'total'     => __( 'Total Units', 'rafah' ),
					'available' => __( 'Available', 'rafah' ),
					'reserved'  => __( 'Reserved', 'rafah' ),
					'sold'      => __( 'Sold', 'rafah' ),
					'hidden'    => __( 'Hidden', 'rafah' ),
				) as $key => $label ) :
					?>
					<div class="rafah-units-stat rafah-units-stat--<?php echo esc_attr( $key ); ?>">
						<span class="rafah-units-stat__value" data-stat="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $stats[ $key ] ); ?></span>
						<span class="rafah-units-stat__label"><?php echo esc_html( $label ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<nav class="rafah-units-tabs">
				<button type="button" class="rafah-units-tab is-active" data-tab="units"><?php esc_html_e( 'Units', 'rafah' ); ?></button>
				<button type="button" class="rafah-units-tab" data-tab="columns"><?php esc_html_e( 'Table Columns', 'rafah' ); ?></button>
				<button type="button" class="rafah-units-tab" data-tab="io"><?php esc_html_e( 'Import / Export', 'rafah' ); ?></button>
			</nav>

			<div class="rafah-units-panel is-active" data-panel="units"></div>
			<div class="rafah-units-panel" data-panel="columns"></div>
			<div class="rafah-units-panel" data-panel="io"></div>
		</div>
		<?php
	}

	// ------------------------------------------------------------- AJAX.

	/**
	 * Shared request guard. Returns the validated project ID or sends error.
	 */
	private static function guard() {
		check_ajax_referer( 'rafah_units', 'nonce' );

		$project_id = absint( $_REQUEST['project_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $project_id || ! current_user_can( 'edit_post', $project_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'rafah' ) ), 403 );
		}

		return $project_id;
	}

	public static function ajax_list() {
		$project_id = self::guard();

		$result = Rafah_Units_DB::query( array(
			'project_id' => $project_id,
			'page'       => absint( $_REQUEST['page'] ?? 1 ),
			'per_page'   => absint( $_REQUEST['per_page'] ?? 25 ),
			'status'     => sanitize_key( $_REQUEST['status'] ?? '' ),
			'search'     => sanitize_text_field( wp_unslash( $_REQUEST['search'] ?? '' ) ),
			'orderby'    => sanitize_key( $_REQUEST['orderby'] ?? 'sort_order' ),
			'order'      => sanitize_key( $_REQUEST['order'] ?? 'asc' ),
			'deleted'    => absint( $_REQUEST['deleted'] ?? 0 ),
		) );

		$result['stats'] = Rafah_Units_DB::stats( $project_id );

		wp_send_json_success( $result );
	}

	public static function ajax_save() {
		$project_id = self::guard();

		$payload               = json_decode( wp_unslash( $_POST['unit'] ?? '{}' ), true );
		$payload['project_id'] = $project_id;

		$result = Rafah_Units_DB::save( (array) $payload );

		if ( isset( $result['error'] ) ) {
			wp_send_json_error( array( 'message' => $result['error'] ) );
		}

		wp_send_json_success( array( 'id' => $result['id'], 'stats' => Rafah_Units_DB::stats( $project_id ) ) );
	}

	public static function ajax_bulk() {
		$project_id = self::guard();

		$ids    = array_map( 'absint', (array) ( $_POST['ids'] ?? array() ) );
		$action = sanitize_text_field( wp_unslash( $_POST['bulk_action'] ?? '' ) );

		// Only allow units belonging to this project.
		global $wpdb;
		$in  = implode( ',', array_filter( $ids ) ) ?: '0';
		$ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . Rafah_Units_DB::table() . " WHERE id IN ({$in}) AND project_id = %d", $project_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$affected = Rafah_Units_DB::bulk( $ids, $action );

		wp_send_json_success( array( 'affected' => $affected, 'stats' => Rafah_Units_DB::stats( $project_id ) ) );
	}

	public static function ajax_duplicate() {
		$project_id = self::guard();
		$result     = Rafah_Units_DB::duplicate( absint( $_POST['id'] ?? 0 ) );

		if ( isset( $result['error'] ) ) {
			wp_send_json_error( array( 'message' => $result['error'] ) );
		}

		wp_send_json_success( array( 'id' => $result['id'], 'stats' => Rafah_Units_DB::stats( $project_id ) ) );
	}

	public static function ajax_reorder() {
		$project_id = self::guard();

		Rafah_Units_DB::reorder( $project_id, (array) ( $_POST['ids'] ?? array() ) );

		wp_send_json_success();
	}

	public static function ajax_columns_save() {
		$project_id = self::guard();

		$groups  = (array) json_decode( wp_unslash( $_POST['groups'] ?? '[]' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$confirm = ! empty( $_POST['confirm'] );

		// Validation: reject duplicate column names.
		$dupes = Rafah_Units_Columns::duplicate_labels( $groups );
		if ( $dupes ) {
			/* translators: %s: comma-separated column names */
			wp_send_json_error( array( 'message' => sprintf( __( 'Duplicate column name: %s', 'rafah' ), implode( '، ', $dupes ) ) ) );
		}

		// Detect removed columns that still hold data (confirm before losing).
		$old = array();
		foreach ( Rafah_Units_Columns::flat_columns( $project_id ) as $col ) {
			$old[ $col['id'] ] = $col['label'];
		}

		$new = array();
		foreach ( Rafah_Units_Columns::sanitize_groups( $groups ) as $group ) {
			foreach ( $group['columns'] as $col ) {
				$new[ $col['id'] ] = true;
			}
		}

		$removed   = array_diff( array_keys( $old ), array_keys( $new ) );
		$with_data = Rafah_Units_Columns::columns_with_data( $project_id, $removed );

		if ( $with_data && ! $confirm ) {
			$labels = array_map( function ( $id ) use ( $old ) {
				return $old[ $id ] ?? $id;
			}, $with_data );

			wp_send_json_success( array( 'needs_confirm' => true, 'columns' => array_values( $labels ) ) );
		}

		$clean = Rafah_Units_Columns::save( $project_id, $groups );

		wp_send_json_success( array( 'groups' => $clean ) );
	}

	/**
	 * Sources for the "start from" picker: other projects with a configured
	 * table + saved templates.
	 */
	public static function ajax_columns_sources() {
		$project_id = self::guard();

		wp_send_json_success( array(
			'projects'  => Rafah_Units_Columns::source_projects( $project_id ),
			'templates' => self::template_list(),
		) );
	}

	/**
	 * Load a structure (from another project or a template) into the builder —
	 * NOT saved until the editor clicks Save Columns.
	 */
	public static function ajax_columns_load() {
		$project_id = self::guard();

		$type = sanitize_key( $_POST['type'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$ref  = sanitize_text_field( wp_unslash( $_POST['ref'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( 'project' === $type ) {
			$groups = Rafah_Units_Columns::groups( absint( $ref ) );
		} elseif ( 'template' === $type ) {
			$groups = Rafah_Units_Columns::template_groups( $ref );
		} else {
			$groups = array();
		}

		wp_send_json_success( array( 'groups' => Rafah_Units_Columns::sanitize_groups( $groups ) ) );
	}

	/**
	 * Save the current layout as a reusable named template.
	 */
	public static function ajax_template_save() {
		self::guard();

		$name   = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$groups = (array) json_decode( wp_unslash( $_POST['groups'] ?? '[]' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( '' === $name ) {
			wp_send_json_error( array( 'message' => __( 'Template name is required.', 'rafah' ) ) );
		}

		Rafah_Units_Columns::save_template( $name, $groups );

		wp_send_json_success( array( 'templates' => self::template_list() ) );
	}

	public static function ajax_template_delete() {
		self::guard();

		$id = sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		Rafah_Units_Columns::delete_template( $id );

		wp_send_json_success( array( 'templates' => self::template_list() ) );
	}

	/**
	 * Templates trimmed to id + name for the UI.
	 */
	private static function template_list() {
		return array_map( function ( $tpl ) {
			return array( 'id' => $tpl['id'] ?? '', 'name' => $tpl['name'] ?? '' );
		}, Rafah_Units_Columns::templates() );
	}
}

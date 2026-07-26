/**
 * Rafah — Units Manager admin app.
 *
 * Workflow: Project saved → configure Table Columns → add Units.
 * Every displayed column is user-defined (add / rename / delete / reorder /
 * group). The only locked fields are the system ones: internal ID, status,
 * order. Add Unit is blocked until at least one column exists, and the Add
 * Unit form is generated purely from those columns — no fixed/default fields.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.rafahUnits;

	if ( ! cfg || ! document.getElementById( 'rafah-units-app' ) ) {
		return;
	}

	var app = document.getElementById( 'rafah-units-app' );
	var state = {
		page: 1,
		perPage: 25,
		status: '',
		search: '',
		orderby: 'sort_order',
		order: 'asc',
		deleted: 0,
		items: [],
		pages: 1,
		total: 0
	};
	var columns = cfg.columns || [];
	var searchTimer;

	// ------------------------------------------------------------ Helpers.

	function esc( value ) {
		var div = document.createElement( 'div' );
		div.textContent = ( value === null || value === undefined ) ? '' : String( value );
		return div.innerHTML;
	}

	function post( action, data ) {
		var body = new FormData();
		body.append( 'action', 'rafah_units_' + action );
		body.append( 'nonce', cfg.nonce );
		body.append( 'project_id', cfg.projectId );
		Object.keys( data || {} ).forEach( function ( key ) {
			if ( Array.isArray( data[ key ] ) ) {
				data[ key ].forEach( function ( v ) { body.append( key + '[]', v ); } );
			} else {
				body.append( key, data[ key ] );
			}
		} );
		return fetch( cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } );
	}

	function toast( message, isError ) {
		var el = document.createElement( 'div' );
		el.className = 'rafah-units-toast' + ( isError ? ' is-error' : '' );
		el.textContent = message;
		document.body.appendChild( el );
		setTimeout( function () { el.classList.add( 'is-visible' ); }, 10 );
		setTimeout( function () { el.classList.remove( 'is-visible' ); setTimeout( function () { el.remove(); }, 300 ); }, 2200 );
	}

	function updateStats( stats ) {
		if ( ! stats ) { return; }
		Object.keys( stats ).forEach( function ( key ) {
			var el = app.querySelector( '[data-stat="' + key + '"]' );
			if ( el ) { el.textContent = stats[ key ]; }
		} );
	}

	// Flat list of named columns across all groups (unnamed = skipped).
	function flatColumns() {
		var flat = [];
		columns.forEach( function ( group ) {
			( group.columns || [] ).forEach( function ( col ) {
				if ( col && String( col.label || '' ).trim() !== '' ) { flat.push( col ); }
			} );
		} );
		return flat;
	}

	function hasColumns() {
		return flatColumns().length > 0;
	}

	function fmt( num ) {
		var n = parseFloat( num );
		return isNaN( n ) ? '' : n.toLocaleString( undefined, { maximumFractionDigits: 2 } );
	}

	function cellDisplay( col, value ) {
		if ( col.type === 'bool' ) { return ( value && value !== '0' ) ? '✓' : '—'; }
		if ( ( col.type === 'number' || col.type === 'price' ) && value !== '' && value !== undefined && value !== null ) { return fmt( value ); }
		return esc( value || '' );
	}

	function goToColumns() {
		var tab = app.querySelector( '.rafah-units-tab[data-tab="columns"]' );
		if ( tab ) { tab.click(); }
	}

	function goToUnits() {
		var tab = app.querySelector( '.rafah-units-tab[data-tab="units"]' );
		if ( tab ) { tab.click(); }
	}

	// ------------------------------------------------------------ Tabs.

	app.querySelectorAll( '.rafah-units-tab' ).forEach( function ( tab ) {
		tab.addEventListener( 'click', function () {
			app.querySelectorAll( '.rafah-units-tab' ).forEach( function ( t ) { t.classList.remove( 'is-active' ); } );
			app.querySelectorAll( '.rafah-units-panel' ).forEach( function ( p ) { p.classList.remove( 'is-active' ); } );
			tab.classList.add( 'is-active' );
			app.querySelector( '[data-panel="' + tab.dataset.tab + '"]' ).classList.add( 'is-active' );
		} );
	} );

	// ============================================================ UNITS TAB.

	var unitsPanel = app.querySelector( '[data-panel="units"]' );

	function renderUnitsShell() {
		var statusOptions = '<option value="">' + esc( cfg.i18n.allStatuses ) + '</option>';
		Object.keys( cfg.statuses ).forEach( function ( key ) {
			statusOptions += '<option value="' + key + '">' + esc( cfg.statuses[ key ].label ) + '</option>';
		} );

		unitsPanel.innerHTML =
			'<div class="rafah-units-toolbar">' +
				'<button type="button" class="button button-primary" data-act="add">+ ' + esc( cfg.i18n.addUnit ) + '</button>' +
				'<input type="search" class="rafah-units-search" placeholder="' + esc( cfg.i18n.search ) + '">' +
				'<select class="rafah-units-filter-status">' + statusOptions + '</select>' +
				'<label class="rafah-units-show-deleted"><input type="checkbox"> ' + esc( cfg.i18n.showTrash ) + '</label>' +
				'<span class="rafah-units-spacer"></span>' +
				'<select class="rafah-units-bulk">' +
					'<option value="">' + esc( cfg.i18n.bulkActions ) + '</option>' +
					Object.keys( cfg.statuses ).map( function ( key ) {
						return '<option value="status:' + key + '">' + esc( cfg.i18n.setStatus + ' ' + cfg.statuses[ key ].label ) + '</option>';
					} ).join( '' ) +
					'<option value="delete">' + esc( cfg.i18n.trash ) + '</option>' +
					'<option value="restore">' + esc( cfg.i18n.restore ) + '</option>' +
				'</select>' +
				'<button type="button" class="button" data-act="apply-bulk">' + esc( cfg.i18n.apply ) + '</button>' +
			'</div>' +
			'<div class="rafah-units-tablewrap"><table class="rafah-units-table"><thead></thead><tbody></tbody></table></div>' +
			'<div class="rafah-units-footer"><span class="rafah-units-count"></span><span class="rafah-units-pager"></span></div>';

		// Toolbar events.
		unitsPanel.querySelector( '[data-act="add"]' ).addEventListener( 'click', function () { openEditor( null ); } );
		unitsPanel.querySelector( '.rafah-units-search' ).addEventListener( 'input', function ( e ) {
			clearTimeout( searchTimer );
			searchTimer = setTimeout( function () { state.search = e.target.value; state.page = 1; load(); }, 350 );
		} );
		unitsPanel.querySelector( '.rafah-units-filter-status' ).addEventListener( 'change', function ( e ) {
			state.status = e.target.value; state.page = 1; load();
		} );
		unitsPanel.querySelector( '.rafah-units-show-deleted input' ).addEventListener( 'change', function ( e ) {
			state.deleted = e.target.checked ? 1 : 0; state.page = 1; load();
		} );
		unitsPanel.querySelector( '[data-act="apply-bulk"]' ).addEventListener( 'click', applyBulk );
	}

	function headerCell( label, key ) {
		var arrow = state.orderby === key ? ( state.order === 'asc' ? ' ▲' : ' ▼' ) : '';
		return '<th data-sort="' + key + '" class="is-sortable">' + esc( label ) + arrow + '</th>';
	}

	function renderTable() {
		var thead = unitsPanel.querySelector( 'thead' );
		var tbody = unitsPanel.querySelector( 'tbody' );
		var dynamic = flatColumns();
		var totalCols = 5 + dynamic.length; // check, drag, ID, [dynamic], status, actions

		// No columns yet → prompt to configure the table (no units can exist).
		if ( ! dynamic.length ) {
			thead.innerHTML = '';
			tbody.innerHTML = '<tr><td class="rafah-units-empty rafah-units-needcols">' +
				'<strong>' + esc( cfg.i18n.needColumnsTitle ) + '</strong><span>' + esc( cfg.i18n.needColumnsText ) + '</span>' +
				'<button type="button" class="button button-primary" data-act="go-columns">' + esc( cfg.i18n.openColumns ) + '</button></td></tr>';
			var goBtn = tbody.querySelector( '[data-act="go-columns"]' );
			if ( goBtn ) { goBtn.addEventListener( 'click', goToColumns ); }
			unitsPanel.querySelector( '.rafah-units-count' ).textContent = '';
			unitsPanel.querySelector( '.rafah-units-pager' ).innerHTML = '';
			return;
		}

		thead.innerHTML = '<tr>' +
			'<th class="col-check"><input type="checkbox" class="rafah-units-checkall"></th>' +
			'<th class="col-drag"></th>' +
			'<th>' + esc( cfg.i18n.internalId ) + '</th>' +
			dynamic.map( function ( col ) { return '<th class="col-dyn">' + esc( col.label ) + '</th>'; } ).join( '' ) +
			'<th>' + esc( cfg.i18n.status ) + '</th>' +
			'<th class="col-actions"></th>' +
		'</tr>';

		if ( ! state.items.length ) {
			if ( state.search || state.status || state.deleted ) {
				tbody.innerHTML = '<tr><td colspan="' + totalCols + '" class="rafah-units-empty">' + esc( cfg.i18n.noResults ) + '</td></tr>';
			} else {
				tbody.innerHTML = '<tr><td colspan="' + totalCols + '" class="rafah-units-empty rafah-units-firstunit"><span>' + esc( cfg.i18n.addFirstUnit ) + '</span>' +
					'<button type="button" class="button button-primary" data-act="first-unit">+ ' + esc( cfg.i18n.addUnit ) + '</button></td></tr>';
				var fb = tbody.querySelector( '[data-act="first-unit"]' );
				if ( fb ) { fb.addEventListener( 'click', function () { openEditor( null ); } ); }
			}
			unitsPanel.querySelector( '.rafah-units-count' ).textContent = state.total;
			unitsPanel.querySelector( '.rafah-units-pager' ).innerHTML = '';
			return;
		}

		tbody.innerHTML = state.items.map( function ( unit ) {
			var status = cfg.statuses[ unit.status ] || { label: unit.status, color: '#999' };
			var statusSelect = '<select class="rafah-units-status" data-id="' + unit.id + '" style="border-color:' + status.color + ';color:' + status.color + '">' +
				Object.keys( cfg.statuses ).map( function ( key ) {
					return '<option value="' + key + '"' + ( key === unit.status ? ' selected' : '' ) + '>' + esc( cfg.statuses[ key ].label ) + '</option>';
				} ).join( '' ) + '</select>';

			var actions = state.deleted
				? '<button type="button" class="button-link" data-act="restore" data-id="' + unit.id + '">↩ ' + esc( cfg.i18n.restore ) + '</button>'
				: '<button type="button" class="button-link" data-act="edit" data-id="' + unit.id + '">' + esc( cfg.i18n.edit ) + '</button> · ' +
				  '<button type="button" class="button-link" data-act="duplicate" data-id="' + unit.id + '">' + esc( cfg.i18n.duplicate ) + '</button> · ' +
				  '<button type="button" class="button-link rafah-units-del" data-act="delete" data-id="' + unit.id + '">' + esc( cfg.i18n.trash ) + '</button>';

			return '<tr data-id="' + unit.id + '" class="status-' + esc( unit.status ) + ( state.deleted ? ' is-deleted' : '' ) + '" style="box-shadow:inset 4px 0 0 ' + status.color + '">' +
				'<td class="col-check"><input type="checkbox" class="rafah-units-check" value="' + unit.id + '"></td>' +
				'<td class="col-drag"><span class="rafah-units-handle">⋮⋮</span></td>' +
				'<td class="col-internal">' + esc( unit.internal_id ) + '</td>' +
				dynamic.map( function ( col ) {
					var value = ( unit.specs || {} )[ col.id ];
					return '<td class="col-dyn">' + cellDisplay( col, value ) + '</td>';
				} ).join( '' ) +
				'<td class="col-status">' + statusSelect + '</td>' +
				'<td class="col-actions">' + actions + '</td>' +
			'</tr>';
		} ).join( '' );

		// Row events.
		tbody.querySelectorAll( '[data-act]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var id = parseInt( btn.dataset.id, 10 );
				if ( btn.dataset.act === 'edit' ) { openEditor( state.items.find( function ( u ) { return +u.id === id; } ) ); }
				if ( btn.dataset.act === 'duplicate' ) { post( 'duplicate', { id: id } ).then( afterMutation ); }
				if ( btn.dataset.act === 'delete' && window.confirm( cfg.i18n.confirmDelete ) ) { post( 'bulk', { ids: [ id ], bulk_action: 'delete' } ).then( afterMutation ); }
				if ( btn.dataset.act === 'restore' ) { post( 'bulk', { ids: [ id ], bulk_action: 'restore' } ).then( afterMutation ); }
			} );
		} );

		// Quick status edit.
		tbody.querySelectorAll( '.rafah-units-status' ).forEach( function ( select ) {
			select.addEventListener( 'change', function () {
				post( 'bulk', { ids: [ select.dataset.id ], bulk_action: 'status:' + select.value } ).then( afterMutation );
			} );
		} );

		// Sorting (system columns only — ID column header).
		thead.querySelectorAll( '.is-sortable' ).forEach( function ( th ) {
			th.addEventListener( 'click', function () {
				var key = th.dataset.sort;
				state.order = ( state.orderby === key && state.order === 'asc' ) ? 'desc' : 'asc';
				state.orderby = key;
				load();
			} );
		} );

		// Check all.
		var checkAll = thead.querySelector( '.rafah-units-checkall' );
		if ( checkAll ) {
			checkAll.addEventListener( 'change', function () {
				tbody.querySelectorAll( '.rafah-units-check' ).forEach( function ( cb ) { cb.checked = checkAll.checked; } );
			} );
		}

		// Drag & drop ordering (only meaningful with default sort).
		if ( state.orderby === 'sort_order' && ! state.deleted && $.fn.sortable ) {
			$( tbody ).sortable( {
				handle: '.rafah-units-handle',
				helper: function ( e, tr ) { return tr; },
				update: function () {
					var ids = [].map.call( tbody.querySelectorAll( 'tr[data-id]' ), function ( tr ) { return tr.dataset.id; } );
					post( 'reorder', { ids: ids } ).then( function () { toast( cfg.i18n.saved ); } );
				}
			} );
		}

		// Footer.
		unitsPanel.querySelector( '.rafah-units-count' ).textContent = state.total;
		var pager = unitsPanel.querySelector( '.rafah-units-pager' );
		pager.innerHTML = '';
		for ( var p = 1; p <= state.pages; p++ ) {
			pager.innerHTML += '<button type="button" class="button' + ( p === state.page ? ' button-primary' : '' ) + '" data-page="' + p + '">' + p + '</button>';
		}
		pager.querySelectorAll( 'button' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () { state.page = parseInt( btn.dataset.page, 10 ); load(); } );
		} );
	}

	function load() {
		post( 'list', {
			page: state.page, per_page: state.perPage, status: state.status,
			search: state.search, orderby: state.orderby, order: state.order, deleted: state.deleted
		} ).then( function ( res ) {
			if ( ! res.success ) { toast( cfg.i18n.error, true ); return; }
			state.items = res.data.items;
			state.pages = res.data.pages;
			state.total = res.data.total;
			updateStats( res.data.stats );
			renderTable();
		} );
	}

	function afterMutation( res ) {
		if ( res && ! res.success ) { toast( ( res.data && res.data.message ) || cfg.i18n.error, true ); return; }
		if ( res && res.data ) { updateStats( res.data.stats ); }
		toast( cfg.i18n.saved );
		load();
	}

	function applyBulk() {
		var action = unitsPanel.querySelector( '.rafah-units-bulk' ).value;
		var ids = [].map.call( unitsPanel.querySelectorAll( '.rafah-units-check:checked' ), function ( cb ) { return cb.value; } );
		if ( ! action || ! ids.length ) { return; }
		if ( action === 'delete' && ! window.confirm( cfg.i18n.confirmDelete ) ) { return; }
		post( 'bulk', { ids: ids, bulk_action: action } ).then( afterMutation );
	}

	// ------------------------------------------------------------ Add Unit gate.

	function showColumnsGate() {
		closeEditor();
		var html =
			'<div class="rafah-units-panel-overlay"></div>' +
			'<aside class="rafah-units-editor rafah-units-gate" dir="auto">' +
				'<header><strong>' + esc( cfg.i18n.needColumnsTitle ) + '</strong>' +
					'<button type="button" class="rafah-units-editor-close">×</button></header>' +
				'<div class="rafah-units-editor-body"><p>' + esc( cfg.i18n.needColumnsText ) + '</p></div>' +
				'<footer><button type="button" class="button button-primary rafah-units-gate-open">' + esc( cfg.i18n.openColumns ) + '</button></footer>' +
			'</aside>';
		var wrap = document.createElement( 'div' );
		wrap.id = 'rafah-units-editor-wrap';
		wrap.innerHTML = html;
		document.body.appendChild( wrap );
		wrap.querySelector( '.rafah-units-editor-close' ).addEventListener( 'click', closeEditor );
		wrap.querySelector( '.rafah-units-panel-overlay' ).addEventListener( 'click', closeEditor );
		wrap.querySelector( '.rafah-units-gate-open' ).addEventListener( 'click', function () { closeEditor(); goToColumns(); } );
		setTimeout( function () { wrap.querySelector( '.rafah-units-editor' ).classList.add( 'is-open' ); }, 10 );
	}

	// ------------------------------------------------------------ Editor panel.

	function openEditor( unit ) {
		// Adding a unit requires a configured table.
		if ( ! unit && ! hasColumns() ) { showColumnsGate(); return; }

		closeEditor();

		unit = unit || { id: 0, status: 'available', specs: {} };

		var html =
			'<div class="rafah-units-panel-overlay"></div>' +
			'<aside class="rafah-units-editor" dir="auto">' +
				'<header><strong>' + esc( unit.id ? cfg.i18n.editUnit : cfg.i18n.addUnit ) + '</strong>' +
					( unit.internal_id ? '<code>' + esc( unit.internal_id ) + '</code>' : '' ) +
					'<button type="button" class="rafah-units-editor-close">×</button></header>' +
				'<div class="rafah-units-editor-body">' +
					// Only user-defined columns — no fixed/default fields.
					columns.map( function ( group ) {
						var namedCols = ( group.columns || [] ).filter( function ( col ) {
							return col && String( col.label || '' ).trim() !== '';
						} );
						if ( ! namedCols.length ) { return ''; }
						var inner = namedCols.map( function ( col ) {
							var value = ( unit.specs || {} )[ col.id ];
							if ( value === undefined || value === null ) { value = ''; }
							if ( col.type === 'bool' ) {
								return '<label class="rafah-units-field is-bool"><input type="checkbox" name="spec:' + col.id + '"' + ( value && value !== '0' ? ' checked' : '' ) + '><span>' + esc( col.label ) + '</span></label>';
							}
							var inputType = ( col.type === 'number' || col.type === 'price' ) ? 'number' : 'text';
							return '<label class="rafah-units-field"><span>' + esc( col.label ) + '</span><input type="' + inputType + '" step="any" name="spec:' + col.id + '" value="' + esc( value ) + '"></label>';
						} ).join( '' );
						var legend = String( group.label || '' ).trim();
						return '<fieldset class="rafah-units-group">' + ( legend ? '<legend>' + esc( legend ) + '</legend>' : '' ) + inner + '</fieldset>';
					} ).join( '' ) +
					// System field: status.
					'<label class="rafah-units-field"><span>' + esc( cfg.i18n.status ) + '</span><select name="status">' +
						Object.keys( cfg.statuses ).map( function ( key ) {
							return '<option value="' + key + '"' + ( key === unit.status ? ' selected' : '' ) + '>' + esc( cfg.statuses[ key ].label ) + '</option>';
						} ).join( '' ) + '</select></label>' +
				'</div>' +
				'<footer>' +
					'<button type="button" class="button button-primary rafah-units-editor-save">' + esc( cfg.i18n.save ) + '</button>' +
					( unit.id ? '' : '<button type="button" class="button rafah-units-editor-save-add">' + esc( cfg.i18n.saveAdd ) + '</button>' ) +
				'</footer>' +
			'</aside>';

		var wrap = document.createElement( 'div' );
		wrap.id = 'rafah-units-editor-wrap';
		wrap.innerHTML = html;
		document.body.appendChild( wrap );

		function saveUnit( addAnother ) {
			var body = wrap.querySelector( '.rafah-units-editor-body' );
			var payload = { id: unit.id, specs: {} };
			body.querySelectorAll( '[name]' ).forEach( function ( input ) {
				var name = input.getAttribute( 'name' );
				var value = input.type === 'checkbox' ? ( input.checked ? 1 : 0 ) : input.value;
				if ( name.indexOf( 'spec:' ) === 0 ) { payload.specs[ name.slice( 5 ) ] = value; } else { payload[ name ] = value; }
			} );
			post( 'save', { unit: JSON.stringify( payload ) } ).then( function ( res ) {
				if ( ! res.success ) { toast( ( res.data && res.data.message ) || cfg.i18n.error, true ); return; }
				if ( res && res.data ) { updateStats( res.data.stats ); }
				toast( cfg.i18n.saved );
				load();
				if ( addAnother ) { openEditor( null ); } else { closeEditor(); }
			} );
		}

		wrap.querySelector( '.rafah-units-editor-close' ).addEventListener( 'click', closeEditor );
		wrap.querySelector( '.rafah-units-panel-overlay' ).addEventListener( 'click', closeEditor );
		wrap.querySelector( '.rafah-units-editor-save' ).addEventListener( 'click', function () { saveUnit( false ); } );
		var saveAddBtn = wrap.querySelector( '.rafah-units-editor-save-add' );
		if ( saveAddBtn ) { saveAddBtn.addEventListener( 'click', function () { saveUnit( true ); } ); }

		setTimeout( function () { wrap.querySelector( '.rafah-units-editor' ).classList.add( 'is-open' ); }, 10 );
	}

	function closeEditor() {
		var wrap = document.getElementById( 'rafah-units-editor-wrap' );
		if ( wrap ) { wrap.remove(); }
	}

	// ============================================================ COLUMNS TAB.

	var columnsPanel = app.querySelector( '[data-panel="columns"]' );

	var sources = { projects: [], templates: [] };

	function loadSources() {
		post( 'columns_sources', {} ).then( function ( res ) {
			if ( res && res.success ) { sources = res.data; var host = columnsPanel.querySelector( '.rafah-units-colsource' ); if ( host ) { populateSources( host ); } }
		} );
	}

	function renderSourceBar() {
		return '<div class="rafah-units-colsource">' +
			'<span class="rafah-units-colsource__label">' + esc( cfg.i18n.startFrom ) + '</span>' +
			'<select class="rafah-units-copyproject"><option value="">' + esc( cfg.i18n.copyProject ) + '</option></select>' +
			'<select class="rafah-units-applytemplate"><option value="">' + esc( cfg.i18n.applyTemplate ) + '</option></select>' +
		'</div>';
	}

	function populateSources( host ) {
		var proj = host.querySelector( '.rafah-units-copyproject' );
		var tpl = host.querySelector( '.rafah-units-applytemplate' );
		if ( proj ) { proj.innerHTML = '<option value="">' + esc( cfg.i18n.copyProject ) + '</option>' + ( sources.projects || [] ).map( function ( pr ) { return '<option value="' + pr.id + '">' + esc( pr.title ) + '</option>'; } ).join( '' ); }
		if ( tpl ) { tpl.innerHTML = '<option value="">' + esc( cfg.i18n.applyTemplate ) + '</option>' + ( sources.templates || [] ).map( function ( t ) { return '<option value="' + esc( t.id ) + '">' + esc( t.name ) + '</option>'; } ).join( '' ); }
	}

	function bindSourceBar() {
		var host = columnsPanel.querySelector( '.rafah-units-colsource' );
		if ( ! host ) { return; }
		populateSources( host );
		var proj = host.querySelector( '.rafah-units-copyproject' );
		var tpl = host.querySelector( '.rafah-units-applytemplate' );
		if ( proj ) { proj.addEventListener( 'change', function () { if ( proj.value ) { applySource( 'project', proj.value ); proj.value = ''; } } ); }
		if ( tpl ) { tpl.addEventListener( 'change', function () { if ( tpl.value ) { applySource( 'template', tpl.value ); tpl.value = ''; } } ); }
	}

	function applySource( type, ref ) {
		if ( hasColumns() && ! window.confirm( cfg.i18n.confirmReplaceStructure ) ) { return; }
		post( 'columns_load', { type: type, ref: ref } ).then( function ( res ) {
			if ( ! res.success ) { toast( cfg.i18n.error, true ); return; }
			columns = res.data.groups || [];
			renderColumns();
			toast( cfg.i18n.structureLoaded );
		} );
	}

	function saveAsTemplate() {
		syncColumnsFromDom();
		if ( ! hasColumns() ) { toast( cfg.i18n.needColumnsToSave, true ); return; }
		var name = window.prompt( cfg.i18n.templateNamePrompt, '' );
		if ( ! name ) { return; }
		post( 'template_save', { name: name, groups: JSON.stringify( columns ) } ).then( function ( res ) {
			if ( ! res.success ) { toast( ( res.data && res.data.message ) || cfg.i18n.error, true ); return; }
			sources.templates = res.data.templates || [];
			var host = columnsPanel.querySelector( '.rafah-units-colsource' );
			if ( host ) { populateSources( host ); }
			toast( cfg.i18n.templateSaved );
		} );
	}

	function typeOptions( selected ) {
		var opts = [
			[ 'text', cfg.i18n.typeText ],
			[ 'number', cfg.i18n.typeNumber ],
			[ 'price', cfg.i18n.typePrice ],
			[ 'bool', cfg.i18n.typeBool ]
		];
		return opts.map( function ( o ) {
			return '<option value="' + o[ 0 ] + '"' + ( selected === o[ 0 ] ? ' selected' : '' ) + '>' + esc( o[ 1 ] ) + '</option>';
		} ).join( '' );
	}

	function renderColumns() {
		columnsPanel.innerHTML =
			'<p class="rafah-units-columns-intro">' + esc( cfg.i18n.columnsIntro ) + '</p>' +
			renderSourceBar() +
			'<div class="rafah-units-columns">' +
				columns.map( function ( group, gi ) {
					return '<div class="rafah-units-colgroup" data-gi="' + gi + '">' +
						'<div class="rafah-units-colgroup-head">' +
							'<span class="rafah-units-handle">⋮⋮</span>' +
							'<input type="text" value="' + esc( group.label ) + '" data-edit="group-label" placeholder="' + esc( cfg.i18n.group ) + '">' +
							'<button type="button" class="button-link rafah-units-del" data-act="del-group">×</button>' +
						'</div>' +
						'<ul class="rafah-units-collist">' +
							( group.columns || [] ).map( function ( col, ci ) {
								return '<li data-ci="' + ci + '"><span class="rafah-units-handle">⋮⋮</span>' +
									'<input type="text" value="' + esc( col.label ) + '" data-edit="col-label" placeholder="' + esc( cfg.i18n.columnName ) + '">' +
									'<select data-edit="col-type">' + typeOptions( col.type ) + '</select>' +
									'<button type="button" class="button-link rafah-units-del" data-act="del-col">×</button></li>';
							} ).join( '' ) +
						'</ul>' +
						'<button type="button" class="button" data-act="add-col">+ ' + esc( cfg.i18n.addColumn ) + '</button>' +
					'</div>';
				} ).join( '' ) +
			'</div>' +
			'<p class="rafah-units-columns-actions">' +
				'<button type="button" class="button" data-act="add-group">+ ' + esc( cfg.i18n.addGroup ) + '</button> ' +
				'<button type="button" class="button" data-act="save-template">' + esc( cfg.i18n.saveAsTemplate ) + '</button> ' +
				'<button type="button" class="button button-primary" data-act="save-columns">' + esc( cfg.i18n.saveColumns ) + '</button></p>';

		columnsPanel.querySelectorAll( '[data-act]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var groupEl = btn.closest( '.rafah-units-colgroup' );
				var gi = groupEl ? parseInt( groupEl.dataset.gi, 10 ) : -1;

				if ( btn.dataset.act === 'add-group' ) { syncColumnsFromDom(); columns.push( { id: '', label: '', columns: [] } ); renderColumns(); }
				if ( btn.dataset.act === 'del-group' && window.confirm( cfg.i18n.confirmColumnDelete ) ) { syncColumnsFromDom(); columns.splice( gi, 1 ); renderColumns(); }
				if ( btn.dataset.act === 'add-col' ) { syncColumnsFromDom(); columns[ gi ].columns.push( { id: '', label: '', type: 'text' } ); renderColumns(); }
				if ( btn.dataset.act === 'del-col' && window.confirm( cfg.i18n.confirmColumnDelete ) ) {
					var ci = parseInt( btn.closest( 'li' ).dataset.ci, 10 );
					syncColumnsFromDom(); columns[ gi ].columns.splice( ci, 1 ); renderColumns();
				}
				if ( btn.dataset.act === 'save-columns' ) { saveColumns(); }
				if ( btn.dataset.act === 'save-template' ) { saveAsTemplate(); }
			} );
		} );

		if ( $.fn.sortable ) {
			$( columnsPanel.querySelectorAll( '.rafah-units-collist' ) ).sortable( { connectWith: '.rafah-units-collist', handle: '.rafah-units-handle', items: '> li', stop: function () { syncColumnsFromDom(); renderColumns(); } } );
			$( columnsPanel.querySelector( '.rafah-units-columns' ) ).sortable( { handle: '.rafah-units-colgroup-head .rafah-units-handle', items: '> .rafah-units-colgroup', stop: function () { syncColumnsFromDom(); renderColumns(); } } );
		}

		bindSourceBar();
	}

	// Read the current column config straight from the DOM (before any
	// re-render or save) so nothing typed is lost.
	function syncColumnsFromDom() {
		var next = [];
		columnsPanel.querySelectorAll( '.rafah-units-colgroup' ).forEach( function ( groupEl ) {
			var original = columns[ parseInt( groupEl.dataset.gi, 10 ) ] || { columns: [] };
			var group = { id: original.id || '', label: groupEl.querySelector( '[data-edit="group-label"]' ).value, columns: [] };
			groupEl.querySelectorAll( '.rafah-units-collist > li' ).forEach( function ( li ) {
				var oc = ( original.columns || [] )[ parseInt( li.dataset.ci, 10 ) ] || {};
				group.columns.push( { id: oc.id || '', label: li.querySelector( '[data-edit="col-label"]' ).value, type: li.querySelector( '[data-edit="col-type"]' ).value } );
			} );
			next.push( group );
		} );
		columns = next;
	}

	function saveColumns( confirmRemove ) {
		syncColumnsFromDom();

		// Validation: block duplicate column names (case-insensitive).
		var seen = {}, dup = false;
		flatColumns().forEach( function ( c ) {
			var k = String( c.label || '' ).trim().toLowerCase();
			if ( ! k ) { return; }
			if ( seen[ k ] ) { dup = true; } else { seen[ k ] = true; }
		} );
		if ( dup ) { toast( cfg.i18n.duplicateColumnMsg, true ); return; }

		var payload = { groups: JSON.stringify( columns ) };
		if ( confirmRemove ) { payload.confirm = 1; }

		post( 'columns_save', payload ).then( function ( res ) {
			if ( ! res.success ) { toast( ( res.data && res.data.message ) || cfg.i18n.error, true ); return; }
			if ( res.data && res.data.needs_confirm ) {
				var msg = cfg.i18n.colDataConfirm.replace( '%s', ( res.data.columns || [] ).join( '، ' ) );
				if ( window.confirm( msg ) ) { saveColumns( true ); }
				return;
			}
			columns = res.data.groups || [];
			cfg.hasColumns = hasColumns();
			toast( cfg.i18n.saved );
			renderColumns();
			load();
			goToUnits(); // UX: jump to Units so the editor can start adding.
		} );
	}

	// ============================================================ IMPORT/EXPORT TAB.

	var ioPanel = app.querySelector( '[data-panel="io"]' );

	function renderIo() {
		var base = cfg.exportUrl + '?action=rafah_units_export&project_id=' + cfg.projectId + '&nonce=' + cfg.nonce;

		ioPanel.innerHTML =
			'<div class="rafah-units-io">' +
				'<p>' +
					'<a class="button" href="' + base + '&format=csv">' + esc( cfg.i18n.exportCsv ) + '</a> ' +
					'<a class="button" href="' + base + '&format=xlsx">' + esc( cfg.i18n.exportXlsx ) + '</a>' +
				'</p><hr>' +
				'<p class="description">' + esc( cfg.i18n.importHelp ) + '</p>' +
				'<p><input type="file" class="rafah-units-import-file" accept=".csv"> ' +
				'<button type="button" class="button" data-act="validate">' + esc( cfg.i18n.validate ) + '</button> ' +
				'<button type="button" class="button button-primary" data-act="apply" disabled>' + esc( cfg.i18n.import ) + '</button></p>' +
				'<div class="rafah-units-import-report"></div>' +
			'</div>';

		var fileInput = ioPanel.querySelector( '.rafah-units-import-file' );
		var applyBtn = ioPanel.querySelector( '[data-act="apply"]' );
		var report = ioPanel.querySelector( '.rafah-units-import-report' );

		function send( mode ) {
			if ( ! fileInput.files.length ) { return; }
			var body = new FormData();
			body.append( 'action', 'rafah_units_import' );
			body.append( 'nonce', cfg.nonce );
			body.append( 'project_id', cfg.projectId );
			body.append( 'mode', mode );
			body.append( 'file', fileInput.files[ 0 ] );
			report.innerHTML = '…';
			fetch( cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( ! res.success ) { report.innerHTML = '<p class="rafah-units-io-error">' + esc( res.data.message || cfg.i18n.error ) + '</p>'; applyBtn.disabled = true; return; }
					var d = res.data;
					report.innerHTML =
						'<table class="widefat striped"><tbody>' +
							'<tr><td>' + esc( cfg.i18n.willCreate ) + '</td><td><strong>' + d.create + '</strong></td></tr>' +
							'<tr><td>' + esc( cfg.i18n.willUpdate ) + '</td><td><strong>' + d.update + '</strong></td></tr>' +
							'<tr><td>' + esc( cfg.i18n.errorRows ) + '</td><td><strong>' + d.errors.length + '</strong></td></tr>' +
						'</tbody></table>' +
						( d.errors.length ? '<ul class="rafah-units-io-errors">' + d.errors.map( function ( e ) { return '<li>' + esc( e ) + '</li>'; } ).join( '' ) + '</ul>' : '' ) +
						( mode === 'apply' ? '<p><strong>' + esc( cfg.i18n.importDone ) + '</strong></p>' : '' );
					applyBtn.disabled = mode === 'apply' || d.errors.length > 0 && d.create + d.update === 0;
					if ( mode === 'apply' ) { updateStats( d.stats ); load(); }
				} );
		}

		ioPanel.querySelector( '[data-act="validate"]' ).addEventListener( 'click', function () { send( 'validate' ); } );
		applyBtn.addEventListener( 'click', function () { send( 'apply' ); } );
		fileInput.addEventListener( 'change', function () { applyBtn.disabled = true; report.innerHTML = ''; } );
	}

	// ============================================================ Boot.

	renderUnitsShell();
	renderColumns();
	loadSources();
	renderIo();
	load();
} )( jQuery );

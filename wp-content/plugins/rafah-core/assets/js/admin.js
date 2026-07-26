/**
 * Rafah Core — Admin meta box behaviour (tabs, media pickers, gallery, repeaters).
 */
( function ( $ ) {
	'use strict';

	// ------------------------------------------------------------- Tabs.
	$( document ).on( 'click', '.rafah-tab', function () {
		var $box = $( this ).closest( '.rafah-metabox' ),
			tab = $( this ).data( 'tab' );

		$box.find( '.rafah-tab' ).removeClass( 'is-active' );
		$( this ).addClass( 'is-active' );
		$box.find( '.rafah-panel' ).removeClass( 'is-active' );
		$box.find( '.rafah-panel[data-panel="' + tab + '"]' ).addClass( 'is-active' );
	} );

	// ------------------------------------------------------------- Single media / file picker.
	$( document ).on( 'click', '.rafah-media__pick', function ( e ) {
		e.preventDefault();

		var $wrap = $( this ).closest( '.rafah-media' ),
			isImage = 'image' === $wrap.data( 'type' ),
			frame = wp.media( {
				title: isImage ? rafahAdmin.chooseImage : rafahAdmin.chooseFile,
				multiple: false,
				library: isImage ? { type: 'image' } : {}
			} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();

			$wrap.find( 'input[type="hidden"]' ).val( attachment.id );

			if ( isImage ) {
				var thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
				$wrap.find( '.rafah-media__preview' ).html( '<img src="' + thumb + '" alt="">' );
			} else {
				$wrap.find( '.rafah-media__filename' ).text( attachment.filename );
			}

			$wrap.find( '.rafah-media__clear' ).removeAttr( 'hidden' );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.rafah-media__clear', function ( e ) {
		e.preventDefault();

		var $wrap = $( this ).closest( '.rafah-media' );

		$wrap.find( 'input[type="hidden"]' ).val( '' );
		$wrap.find( '.rafah-media__preview' ).empty();
		$wrap.find( '.rafah-media__filename' ).empty();
		$( this ).attr( 'hidden', true );
	} );

	// ------------------------------------------------------------- Gallery.
	function syncGallery( $gallery ) {
		var ids = [];

		$gallery.find( '.rafah-gallery__item' ).each( function () {
			ids.push( $( this ).data( 'id' ) );
		} );

		$gallery.find( '> input[type="hidden"]' ).val( ids.join( ',' ) );
	}

	$( document ).on( 'click', '.rafah-gallery__add', function ( e ) {
		e.preventDefault();

		var $gallery = $( this ).closest( '.rafah-gallery' ),
			frame = wp.media( {
				title: rafahAdmin.chooseImages,
				multiple: 'add',
				library: { type: 'image' }
			} );

		frame.on( 'select', function () {
			frame.state().get( 'selection' ).each( function ( attachment ) {
				var data = attachment.toJSON(),
					thumb = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;

				if ( $gallery.find( '.rafah-gallery__item[data-id="' + data.id + '"]' ).length ) {
					return;
				}

				$gallery.find( '.rafah-gallery__list' ).append(
					'<li class="rafah-gallery__item" data-id="' + data.id + '">' +
						'<img src="' + thumb + '" alt="">' +
						'<button type="button" class="rafah-gallery__remove" aria-label="' + rafahAdmin.remove + '">&times;</button>' +
					'</li>'
				);
			} );

			syncGallery( $gallery );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.rafah-gallery__remove', function () {
		var $gallery = $( this ).closest( '.rafah-gallery' );

		$( this ).closest( '.rafah-gallery__item' ).remove();
		syncGallery( $gallery );
	} );

	// ------------------------------------------------------------- Repeaters.
	$( document ).on( 'click', '.rafah-repeater__add', function () {
		var $repeater = $( this ).closest( '.rafah-repeater' ),
			template = $repeater.find( '> .rafah-repeater__template' ).html(),
			index = Date.now(); // Unique index; PHP re-indexes on save.

		$repeater.find( '> .rafah-repeater__rows' ).append( template.replace( /__i__/g, index ) );
	} );

	$( document ).on( 'click', '.rafah-repeater__remove', function () {
		if ( window.confirm( rafahAdmin.confirmRow ) ) {
			$( this ).closest( '.rafah-repeater__row' ).remove();
		}
	} );

	// ------------------------------------------------------------- Sortables.
	$( function () {
		$( '.rafah-gallery__list' ).sortable( {
			update: function () {
				syncGallery( $( this ).closest( '.rafah-gallery' ) );
			}
		} );

		$( '.rafah-repeater__rows' ).sortable( {
			handle: '.rafah-repeater__handle'
		} );
	} );
} )( jQuery );

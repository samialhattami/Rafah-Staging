/**
 * Rafah — Projects Map (Leaflet). Reads points from an inline JSON script
 * inside each [data-rafah-map] container and plots on-brand markers with
 * image + name + link popups. Fits the view to all markers.
 */
( function () {
	'use strict';

	function escapeHtml( value ) {
		var d = document.createElement( 'div' );
		d.textContent = ( value === null || value === undefined ) ? '' : String( value );
		return d.innerHTML;
	}

	function pinIcon() {
		return L.divIcon( {
			className: 'rafah-map-pin',
			html: '<svg viewBox="0 0 24 24" width="30" height="30" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C7.6 2 4 5.6 4 10c0 5.4 7 11.6 7.3 11.9.4.3.9.3 1.3 0C13 21.6 20 15.4 20 10c0-4.4-3.6-8-8-8z" fill="#bc945d" stroke="#fff" stroke-width="1.5"/><circle cx="12" cy="10" r="3" fill="#fff"/></svg>',
			iconSize: [ 30, 30 ],
			iconAnchor: [ 15, 28 ],
			popupAnchor: [ 0, -26 ]
		} );
	}

	function popupHtml( p, viewLabel ) {
		var url = escapeHtml( p.url );
		return '<div class="rafah-map-popup">' +
			( p.img ? '<a class="rafah-map-popup__img" href="' + url + '"><img src="' + escapeHtml( p.img ) + '" alt="" loading="lazy"></a>' : '' ) +
			'<div class="rafah-map-popup__body">' +
				( p.city ? '<span class="rafah-map-popup__city">' + escapeHtml( p.city ) + '</span>' : '' ) +
				'<a class="rafah-map-popup__title" href="' + url + '">' + escapeHtml( p.title ) + '</a>' +
				'<a class="rafah-map-popup__link" href="' + url + '">' + escapeHtml( viewLabel ) + '</a>' +
			'</div>' +
		'</div>';
	}

	function init( el ) {
		if ( ! el || el.dataset.rafahMapReady || typeof L === 'undefined' ) { return; }

		var dataEl = el.querySelector( '.rafah-map-data' );
		var points = [];
		try { points = JSON.parse( ( dataEl && dataEl.textContent ) || '[]' ); } catch ( e ) { points = []; }
		if ( ! points.length ) { return; }

		el.dataset.rafahMapReady = '1';

		var viewLabel = el.getAttribute( 'data-label-view' ) || 'View';
		var map = L.map( el, { scrollWheelZoom: false } );

		L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: '&copy; OpenStreetMap'
		} ).addTo( map );

		var bounds = [];
		points.forEach( function ( p ) {
			if ( ! p || ! p.lat || ! p.lng ) { return; }
			var marker = L.marker( [ p.lat, p.lng ], { icon: pinIcon() } ).addTo( map );
			marker.bindPopup( popupHtml( p, viewLabel ), { minWidth: 200, maxWidth: 260 } );
			bounds.push( [ p.lat, p.lng ] );
		} );

		if ( bounds.length === 1 ) {
			map.setView( bounds[ 0 ], 13 );
		} else if ( bounds.length ) {
			map.fitBounds( bounds, { padding: [ 40, 40 ] } );
		}

		// Only grab the scroll wheel once the user interacts with the map.
		map.on( 'focus', function () { map.scrollWheelZoom.enable(); } );
		map.on( 'blur', function () { map.scrollWheelZoom.disable(); } );
		setTimeout( function () { map.invalidateSize(); }, 200 );
	}

	function boot( scope ) {
		( scope || document ).querySelectorAll( '[data-rafah-map]' ).forEach( init );
	}

	if ( document.readyState !== 'loading' ) { boot(); } else {
		document.addEventListener( 'DOMContentLoaded', function () { boot(); } );
	}

	// Re-initialise inside the Elementor editor when the widget is added/edited.
	if ( window.jQuery ) {
		jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( window.elementorFrontend && elementorFrontend.hooks ) {
				elementorFrontend.hooks.addAction( 'frontend/element_ready/rafah_projects_map.default', function ( $el ) {
					var node = $el[ 0 ] ? $el[ 0 ].querySelector( '[data-rafah-map]' ) : null;
					init( node );
				} );
			}
		} );
	}
} )();

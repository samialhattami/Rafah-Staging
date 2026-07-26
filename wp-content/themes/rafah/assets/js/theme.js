/**
 * Rafah Theme — header behavior, offcanvas, back-to-top, preloader.
 * Vanilla JS, no dependencies. Presentation only.
 */
( function () {
	'use strict';

	var header = document.getElementById( 'rafah-header' );

	// ------------------------------------------------ Sticky / scroll effects.
	if ( header ) {
		var lastState = null;

		var onScroll = function () {
			var scrolled = window.scrollY > 40;

			if ( scrolled !== lastState ) {
				header.classList.toggle( 'is-scrolled', scrolled );
				document.body.classList.toggle( 'rafah-scrolled', scrolled );
				lastState = scrolled;
			}
		};

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		onScroll();
	}

	// ------------------------------------------------ Offcanvas menu.
	var offcanvas = document.getElementById( 'rafah-offcanvas' );
	var overlay = document.querySelector( '.rafah-offcanvas-overlay' );
	var burger = document.querySelector( '[data-rafah-burger]' );

	function toggleOffcanvas( open ) {
		if ( ! offcanvas ) {
			return;
		}

		offcanvas.classList.toggle( 'is-open', open );
		offcanvas.setAttribute( 'aria-hidden', open ? 'false' : 'true' );
		document.body.classList.toggle( 'rafah-offcanvas-open', open );

		if ( overlay ) {
			overlay.hidden = ! open;
		}

		if ( burger ) {
			burger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			burger.classList.toggle( 'is-active', open );
		}

		if ( open ) {
			var firstLink = offcanvas.querySelector( 'a' );
			if ( firstLink ) {
				firstLink.focus();
			}
		}
	}

	if ( burger ) {
		burger.addEventListener( 'click', function () {
			toggleOffcanvas( ! offcanvas.classList.contains( 'is-open' ) );
		} );
	}

	document.querySelectorAll( '[data-rafah-offcanvas-close]' ).forEach( function ( el ) {
		el.addEventListener( 'click', function () {
			toggleOffcanvas( false );
		} );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key && offcanvas && offcanvas.classList.contains( 'is-open' ) ) {
			toggleOffcanvas( false );
			if ( burger ) {
				burger.focus();
			}
		}
	} );

	// ------------------------------------------------ Back to top.
	var backToTop = document.querySelector( '[data-rafah-backtotop]' );

	if ( backToTop ) {
		window.addEventListener( 'scroll', function () {
			backToTop.classList.toggle( 'is-visible', window.scrollY >= 500 );
		}, { passive: true } );

		backToTop.addEventListener( 'click', function () {
			window.scrollTo( { top: 0, behavior: 'smooth' } );
		} );
	}

	// ------------------------------------------------ Preloader.
	var preloader = document.querySelector( '[data-rafah-preloader]' );

	if ( preloader ) {
		var hide = function () {
			preloader.classList.add( 'is-done' );
			setTimeout( function () {
				preloader.remove();
			}, 400 );
		};

		if ( 'complete' === document.readyState ) {
			hide();
		} else {
			window.addEventListener( 'load', hide );
			setTimeout( hide, 3500 ); // Never trap the visitor.
		}
	}
} )();

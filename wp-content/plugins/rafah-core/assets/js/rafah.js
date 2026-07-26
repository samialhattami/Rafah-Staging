/**
 * Rafah — Front-end behaviour.
 * AJAX project filters + status tabs, scroll-reveal animation engine,
 * animated counters. Vanilla JS, zero dependencies.
 */
( function () {
	'use strict';

	var config = window.rafahFront || {};

	// ============================================================ Animations.
	//
	// Fully controlled from Settings → Rafah (enable/disable, style, duration,
	// stagger). The hidden state only exists when <body> carries .rafah-anim,
	// so with animations off — or if this script never runs — the site renders
	// exactly the same with everything visible.

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var animEnabled = !! ( config.anim && config.anim.enabled ) &&
		! reduceMotion &&
		document.body.classList.contains( 'rafah-anim' ) &&
		'IntersectionObserver' in window;

	// Elements that receive reveal animations. Grids/lists are staggered.
	var REVEAL_TARGETS = [
		'.rafah-fade-up',
		'.rafah-section-head',
		'.rafah-card',
		'.rafah-agent-card',
		'.rafah-stat',
		'.rafah-testimonial',
		'.rafah-faq__item',
		'.rafah-facts-bar',
		'.rafah-plan',
		'.rafah-payment',
		'.rafah-nearby__group',
		'.rafah-news__feature',
		'.rafah-news__mini',
		'.rafah-project-main > section',
		'.rafah-cta'
	].join( ',' );

	// Elements that should barely move (large blocks — avoid jumpiness).
	var SOFT_TARGETS = '.rafah-facts-bar, .rafah-news__feature, .rafah-cta, .rafah-project-main > section';

	var revealObserver = animEnabled
		? new IntersectionObserver( function ( entries, observer ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					observer.unobserve( entry.target );
				}
			} );
		}, { threshold: 0.12, rootMargin: '0px 0px -4% 0px' } )
		: null;

	function initReveals( root ) {
		if ( ! animEnabled ) {
			return;
		}

		var stagger = ( config.anim && config.anim.stagger ) || 0;
		var groups = new Map();

		( root || document ).querySelectorAll( REVEAL_TARGETS ).forEach( function ( el ) {
			if ( el.classList.contains( 'rafah-reveal' ) || el.classList.contains( 'is-visible' ) ) {
				return;
			}

			el.classList.add( 'rafah-reveal' );

			if ( el.matches( SOFT_TARGETS ) ) {
				el.classList.add( 'rafah-reveal--soft' );
			}

			// Stagger siblings inside the same parent (max 8 steps so late
			// items in long grids don't wait forever).
			if ( stagger > 0 ) {
				var parent = el.parentElement;
				var index = groups.get( parent ) || 0;
				el.style.setProperty( '--rafah-anim-delay', Math.min( index, 8 ) * stagger + 'ms' );
				groups.set( parent, index + 1 );
			}

			revealObserver.observe( el );
		} );
	}

	// ============================================================ AJAX filters.

	document.querySelectorAll( '.rafah-filter' ).forEach( function ( filter ) {
		var targetSel = filter.getAttribute( 'data-target' ),
			results = targetSel ? document.querySelector( targetSel ) : null,
			countEl = filter.querySelector( '.rafah-filter__count' ),
			loadMoreWrap = results ? results.parentElement.querySelector( '.rafah-load-more-wrap' ) : null,
			loadMoreBtn = loadMoreWrap ? loadMoreWrap.querySelector( 'button' ) : null,
			currentPage = 1,
			maxPages = 1,
			controller = null,
			debounceTimer;

		if ( ! results ) {
			return;
		}

		function collectParams() {
			var params = new FormData();

			params.append( 'action', 'rafah_filter_projects' );
			params.append( 'nonce', config.nonce );
			params.append( 'lang', config.lang || '' );
			params.append( 'per_page', filter.getAttribute( 'data-per-page' ) || 9 );

			filter.querySelectorAll( '[data-filter]' ).forEach( function ( field ) {
				if ( field.value ) {
					params.append( field.getAttribute( 'data-filter' ), field.value );
				}
			} );

			return params;
		}

		function fetchProjects( page, append ) {
			var params = collectParams();

			params.append( 'page', page );
			results.classList.add( 'is-loading' );

			// Abort any in-flight request so rapid tab clicks can never
			// resolve out of order and show stale results.
			if ( controller ) {
				controller.abort();
			}
			controller = new AbortController();

			fetch( config.ajaxUrl, { method: 'POST', body: params, signal: controller.signal } )
				.then( function ( res ) { return res.json(); } )
				.then( function ( res ) {
					if ( ! res.success ) {
						results.classList.remove( 'is-loading' );
						return;
					}

					var html = res.data.html || '<div class="rafah-no-results">' + config.i18n.noResults + '</div>';

					if ( append ) {
						results.insertAdjacentHTML( 'beforeend', res.data.html );
					} else {
						results.innerHTML = html;
					}

					currentPage = res.data.page;
					maxPages = res.data.max_pages;

					if ( countEl ) {
						countEl.textContent = countEl.getAttribute( 'data-label' ).replace( '%d', res.data.found );
					}

					if ( loadMoreWrap ) {
						loadMoreWrap.style.display = currentPage < maxPages ? '' : 'none';
					}

					initReveals( results );
					results.classList.remove( 'is-loading' );
				} )
				.catch( function ( err ) {
					if ( 'AbortError' !== err.name ) {
						results.classList.remove( 'is-loading' );
					}
				} );
		}

		filter.addEventListener( 'change', function ( e ) {
			if ( e.target.matches( '[data-filter]' ) ) {
				syncTabsFromSelect();
				fetchProjects( 1, false );
			}
		} );

		filter.addEventListener( 'input', function ( e ) {
			if ( e.target.matches( 'input[data-filter]' ) ) {
				clearTimeout( debounceTimer );
				debounceTimer = setTimeout( function () {
					fetchProjects( 1, false );
				}, 450 );
			}
		} );

		filter.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			fetchProjects( 1, false );
		} );

		var reset = filter.querySelector( '.rafah-filter__reset' );
		if ( reset ) {
			reset.addEventListener( 'click', function () {
				filter.querySelectorAll( '[data-filter]' ).forEach( function ( field ) {
					field.value = '';
				} );
				syncTabsFromSelect();
				fetchProjects( 1, false );
			} );
		}

		if ( loadMoreBtn ) {
			loadMoreBtn.addEventListener( 'click', function () {
				fetchProjects( currentPage + 1, true );
			} );
		}

		// ------------------------------------------------ Status tabs.
		// Tabs live outside the form but drive the status select. Status is now a
		// fixed enum (available|coming_soon|sold), so the tab data-status, the
		// select value and the ?status= URL param are all the SAME canonical key
		// — no slug decoding needed.

		var statusSelect = filter.querySelector( '[data-filter="status"]' );
		var tabs = document.querySelectorAll( '.rafah-status-tabs .rafah-status-tab' );

		function syncTabsFromSelect() {
			if ( ! statusSelect || ! tabs.length ) {
				return;
			}

			tabs.forEach( function ( tab ) {
				tab.classList.toggle( 'is-active', tab.getAttribute( 'data-status' ) === statusSelect.value );
			} );
		}

		if ( tabs.length && statusSelect ) {
			tabs.forEach( function ( tab ) {
				tab.addEventListener( 'click', function () {
					var status = tab.getAttribute( 'data-status' ) || '';

					statusSelect.value = status;
					syncTabsFromSelect();
					fetchProjects( 1, false );

					// Keep the URL shareable.
					var url = new URL( window.location.href );
					if ( status ) {
						url.searchParams.set( 'status', status );
					} else {
						url.searchParams.delete( 'status' );
					}
					window.history.replaceState( {}, '', url );
				} );
			} );

			// Apply ?status= from the URL on load (server pre-filtered the
			// initial results; this syncs the UI controls to match).
			var urlStatus = new URL( window.location.href ).searchParams.get( 'status' );
			if ( urlStatus ) {
				statusSelect.value = urlStatus;
			}
			syncTabsFromSelect();
		}
	} );

	// ============================================================ Counters.

	function animateCounter( el ) {
		var target = parseFloat( el.getAttribute( 'data-count' ) ) || 0,
			duration = 1800,
			start = performance.now(),
			isInt = target % 1 === 0;

		function tick( now ) {
			var progress = Math.min( ( now - start ) / duration, 1 ),
				eased = 1 - Math.pow( 1 - progress, 3 ),
				value = target * eased;

			el.textContent = ( isInt ? Math.round( value ) : value.toFixed( 1 ) ).toLocaleString();

			if ( progress < 1 ) {
				requestAnimationFrame( tick );
			}
		}

		requestAnimationFrame( tick );
	}

	var counterObserver = ( 'IntersectionObserver' in window ) && ! reduceMotion
		? new IntersectionObserver( function ( entries, observer ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					animateCounter( entry.target );
					observer.unobserve( entry.target );
				}
			} );
		}, { threshold: 0.4 } )
		: null;

	document.querySelectorAll( '[data-count]' ).forEach( function ( el ) {
		if ( counterObserver ) {
			counterObserver.observe( el );
		} else {
			el.textContent = parseFloat( el.getAttribute( 'data-count' ) ).toLocaleString();
		}
	} );

	// ============================================================ Units table.
	// Client-side search / status filter / numeric sorting for the project
	// units comparison table. Even 300+ rows are trivial in the DOM.

	document.querySelectorAll( '[data-rafah-units]' ).forEach( function ( widget ) {
		var tbody = widget.querySelector( 'tbody' );
		var rows = Array.prototype.slice.call( tbody.querySelectorAll( 'tr' ) );
		var search = widget.querySelector( '[data-usearch]' );
		var statusSel = widget.querySelector( '[data-ustatus]' );
		var noneMsg = widget.querySelector( '.rafah-units-front__none' );
		var sortState = { key: '', dir: 1 };

		function applyFilters() {
			var term = ( search.value || '' ).toLowerCase().trim();
			var status = statusSel.value;
			var visible = 0;

			rows.forEach( function ( row ) {
				var haystack = row.getAttribute( 'data-search' ) || '';
				var show = ( ! term || haystack.indexOf( term ) !== -1 ) &&
					( ! status || row.getAttribute( 'data-status' ) === status );
				row.style.display = show ? '' : 'none';
				if ( show ) { visible++; }
			} );

			if ( noneMsg ) { noneMsg.hidden = visible > 0; }
		}

		search.addEventListener( 'input', applyFilters );
		statusSel.addEventListener( 'change', applyFilters );

		widget.querySelectorAll( '[data-usort]' ).forEach( function ( th ) {
			th.addEventListener( 'click', function () {
				var key = th.getAttribute( 'data-usort' );
				sortState.dir = sortState.key === key ? -sortState.dir : 1;
				sortState.key = key;

				rows.sort( function ( a, b ) {
					return ( parseFloat( a.getAttribute( 'data-' + key ) ) - parseFloat( b.getAttribute( 'data-' + key ) ) ) * sortState.dir;
				} );

				rows.forEach( function ( row ) { tbody.appendChild( row ); } );
			} );
		} );
	} );

	// ============================================================ Project gallery + lightbox.
	//
	// Responsive horizontal carousel; clicking any item opens a shared, built-
	// once lightbox (images + videos) with prev/next, ESC + arrow keys, swipe,
	// on-demand (lazy) full-image loading, and click-to-zoom. Zero dependencies.

	function galText( key, fallback ) {
		return ( config.i18n && config.i18n[ key ] ) ? config.i18n[ key ] : fallback;
	}

	var lightbox = null; // singleton controller

	function svg( paths, extra ) {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" ' +
			'stroke-linecap="round" stroke-linejoin="round"' + ( extra || '' ) + '>' + paths + '</svg>';
	}

	function buildLightbox() {
		if ( lightbox ) { return lightbox; }

		var root = document.createElement( 'div' );
		root.className = 'rafah-lightbox';
		root.setAttribute( 'role', 'dialog' );
		root.setAttribute( 'aria-modal', 'true' );
		root.setAttribute( 'aria-label', galText( 'gallery', 'Gallery' ) );
		root.innerHTML =
			'<div class="rafah-lightbox__counter"></div>' +
			'<button type="button" class="rafah-lightbox__btn rafah-lightbox__close" aria-label="' + galText( 'close', 'Close' ) + '">' + svg( '<path d="M6 6l12 12M18 6L6 18"/>' ) + '</button>' +
			'<button type="button" class="rafah-lightbox__btn rafah-lightbox__zoom" aria-label="' + galText( 'zoom', 'Zoom' ) + '">' + svg( '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3M11 8v6M8 11h6"/>', ' stroke-width="2"' ) + '</button>' +
			'<button type="button" class="rafah-lightbox__btn rafah-lightbox__nav rafah-lightbox__nav--prev" aria-label="' + galText( 'prev', 'Previous' ) + '">' + svg( '<path d="M15 18l-6-6 6-6"/>' ) + '</button>' +
			'<button type="button" class="rafah-lightbox__btn rafah-lightbox__nav rafah-lightbox__nav--next" aria-label="' + galText( 'next', 'Next' ) + '">' + svg( '<path d="M9 6l6 6-6 6"/>' ) + '</button>' +
			'<div class="rafah-lightbox__stage"><div class="rafah-lightbox__media"></div><figcaption class="rafah-lightbox__caption"></figcaption></div>';
		document.body.appendChild( root );

		var state   = { items: [], index: 0, lastFocus: null };
		var media   = root.querySelector( '.rafah-lightbox__media' );
		var caption = root.querySelector( '.rafah-lightbox__caption' );
		var counter = root.querySelector( '.rafah-lightbox__counter' );
		var stage   = root.querySelector( '.rafah-lightbox__stage' );
		var zoomBtn = root.querySelector( '.rafah-lightbox__zoom' );
		var prevBtn = root.querySelector( '.rafah-lightbox__nav--prev' );
		var nextBtn = root.querySelector( '.rafah-lightbox__nav--next' );

		function stopVideo() {
			var f = media.querySelector( 'iframe' );
			if ( f ) { f.src = 'about:blank'; }
		}

		function render() {
			var item = state.items[ state.index ];
			if ( ! item ) { return; }
			root.classList.remove( 'is-zoomed' );
			stopVideo();
			media.innerHTML = '';

			if ( 'video' === item.type && item.embed ) {
				var wrap = document.createElement( 'div' );
				wrap.className = 'rafah-lightbox__video';
				var frame = document.createElement( 'iframe' );
				frame.setAttribute( 'allow', 'autoplay; fullscreen; picture-in-picture' );
				frame.setAttribute( 'allowfullscreen', '' );
				frame.src = item.embed;
				wrap.appendChild( frame );
				media.appendChild( wrap );
				zoomBtn.hidden = true;
			} else {
				var img = document.createElement( 'img' );
				img.src = item.full;            // lazy: the full image loads only now
				img.alt = item.caption || '';
				img.style.transformOrigin = 'center';
				media.appendChild( img );
				zoomBtn.hidden = false;
			}

			caption.textContent = item.caption || '';
			caption.hidden = ! item.caption;
			counter.textContent = ( state.index + 1 ) + ' / ' + state.items.length;
			var multi = state.items.length > 1;
			prevBtn.hidden = ! multi;
			nextBtn.hidden = ! multi;
		}

		function go( dir ) {
			var n = state.items.length;
			if ( ! n ) { return; }
			state.index = ( state.index + dir + n ) % n;
			render();
		}

		function open( items, index ) {
			state.items = items;
			state.index = index || 0;
			state.lastFocus = document.activeElement;
			render();
			root.classList.add( 'is-open' );
			document.body.classList.add( 'rafah-lightbox-open' );
			root.querySelector( '.rafah-lightbox__close' ).focus();
		}

		function close() {
			root.classList.remove( 'is-open', 'is-zoomed' );
			document.body.classList.remove( 'rafah-lightbox-open' );
			stopVideo();
			media.innerHTML = '';
			if ( state.lastFocus && state.lastFocus.focus ) { state.lastFocus.focus(); }
		}

		root.querySelector( '.rafah-lightbox__close' ).addEventListener( 'click', close );
		prevBtn.addEventListener( 'click', function () { go( -1 ); } );
		nextBtn.addEventListener( 'click', function () { go( 1 ); } );
		zoomBtn.addEventListener( 'click', function () { root.classList.toggle( 'is-zoomed' ); } );

		// Click the dim backdrop (root or empty stage area) to close.
		root.addEventListener( 'click', function ( e ) {
			if ( e.target === root || e.target === stage ) { close(); }
		} );

		// Click the image toggles zoom; moving the cursor pans while zoomed.
		media.addEventListener( 'click', function ( e ) {
			if ( 'IMG' === e.target.tagName ) { root.classList.toggle( 'is-zoomed' ); }
		} );
		media.addEventListener( 'mousemove', function ( e ) {
			if ( ! root.classList.contains( 'is-zoomed' ) ) { return; }
			var img = media.querySelector( 'img' );
			if ( ! img ) { return; }
			var r = img.getBoundingClientRect();
			img.style.transformOrigin = ( ( e.clientX - r.left ) / r.width * 100 ) + '% ' + ( ( e.clientY - r.top ) / r.height * 100 ) + '%';
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( ! root.classList.contains( 'is-open' ) ) { return; }
			if ( 'Escape' === e.key ) { close(); }
			else if ( 'ArrowRight' === e.key ) { go( 1 ); }
			else if ( 'ArrowLeft' === e.key ) { go( -1 ); }
		} );

		var tx = 0, ty = 0;
		root.addEventListener( 'touchstart', function ( e ) { tx = e.touches[0].clientX; ty = e.touches[0].clientY; }, { passive: true } );
		root.addEventListener( 'touchend', function ( e ) {
			if ( root.classList.contains( 'is-zoomed' ) ) { return; }
			var dx = e.changedTouches[0].clientX - tx;
			var dy = e.changedTouches[0].clientY - ty;
			if ( Math.abs( dx ) > 45 && Math.abs( dx ) > Math.abs( dy ) ) { go( dx < 0 ? 1 : -1 ); }
		}, { passive: true } );

		lightbox = { open: open };
		return lightbox;
	}

	function currentSlide( viewport, slides ) {
		var vc = viewport.getBoundingClientRect();
		var center = vc.left + vc.width / 2;
		var best = 0, bestD = Infinity;
		slides.forEach( function ( s, i ) {
			var r = s.getBoundingClientRect();
			var d = Math.abs( ( r.left + r.width / 2 ) - center );
			if ( d < bestD ) { bestD = d; best = i; }
		} );
		return best;
	}

	function initGalleries( root ) {
		( root || document ).querySelectorAll( '[data-rafah-gallery]' ).forEach( function ( gallery ) {
			if ( gallery.__rafahGallery ) { return; }
			gallery.__rafahGallery = true;

			var viewport = gallery.querySelector( '.rafah-gallery__viewport' );
			var slides   = [].slice.call( gallery.querySelectorAll( '.rafah-gallery__slide' ) );
			var prev     = gallery.querySelector( '.rafah-gallery__nav--prev' );
			var next     = gallery.querySelector( '.rafah-gallery__nav--next' );

			function scrollTo( i ) {
				i = Math.max( 0, Math.min( slides.length - 1, i ) );
				if ( slides[ i ] ) { slides[ i ].scrollIntoView( { behavior: 'smooth', inline: 'center', block: 'nearest' } ); }
			}
			if ( prev ) { prev.addEventListener( 'click', function () { scrollTo( currentSlide( viewport, slides ) - 1 ); } ); }
			if ( next ) { next.addEventListener( 'click', function () { scrollTo( currentSlide( viewport, slides ) + 1 ); } ); }

			if ( gallery.hasAttribute( 'data-rafah-lightbox' ) ) {
				var buttons = [].slice.call( gallery.querySelectorAll( '.rafah-gallery__item' ) );
				var items = buttons.map( function ( btn ) {
					return {
						type:    btn.getAttribute( 'data-type' ) || 'image',
						full:    btn.getAttribute( 'data-full' ) || '',
						embed:   btn.getAttribute( 'data-embed' ) || '',
						caption: btn.getAttribute( 'data-caption' ) || ''
					};
				} );
				buttons.forEach( function ( btn, i ) {
					btn.addEventListener( 'click', function () { buildLightbox().open( items, i ); } );
				} );
			}
		} );
	}

	// ============================================================ Boot.

	initReveals( document );
	initGalleries( document );
} )();

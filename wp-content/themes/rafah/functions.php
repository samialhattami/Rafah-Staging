<?php
/**
 * Rafah child theme — presentation layer.
 *
 * ARCHITECTURE (final):
 * - Rafah Core plugin  = business logic only (CPTs, units, AJAX, SEO, widgets).
 * - Rafah theme        = ALL presentation: header, footer, hero, templates,
 *                        styling, animations. Native PHP — zero builder
 *                        dependency.
 * - Elementor          = optional visual editing layer for page CONTENT and
 *                        the hero. If Elementor is disabled/broken, native
 *                        PHP fallbacks render automatically. No blank pages.
 * - Customizer         = global SETTINGS only (brand, contact, behavior) —
 *                        never a layout builder.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

define( 'RAFAH_THEME_VERSION', '2.2.0' );

require_once get_stylesheet_directory() . '/inc/customizer.php';
require_once get_stylesheet_directory() . '/inc/project-card.php';
require_once get_stylesheet_directory() . '/inc/single-project-sections.php';
require_once get_stylesheet_directory() . '/inc/footer/init.php'; // Isolated footer + Back-to-Top module (Customizer-only). See FOOTER-ISOLATION.md.
require_once get_stylesheet_directory() . '/inc/elementor-bridge.php'; // Opt-in Elementor template bridge (default = current PHP design).

/**
 * Theme option helper (Customizer theme_mods, rafah_ prefix).
 */
function rafah_opt( $key, $default = '' ) {
	return get_theme_mod( 'rafah_' . $key, $default );
}

/**
 * Is Elementor active AND was this post built with it?
 * Never assumes the plugin exists — core fail-safe check.
 */
function rafah_is_elementor_page( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();

	return did_action( 'elementor/loaded' )
		&& $post_id
		&& 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true )
		&& get_post_meta( $post_id, '_elementor_data', true );
}

// ---------------------------------------------------------------- Setup.

add_action( 'after_setup_theme', function () {
	register_nav_menus( array(
		'footer' => __( 'Footer Links', 'rafah-theme' ),
	) );

	add_image_size( 'rafah-card', 640, 400, true );
	add_image_size( 'rafah-hero', 1920, 1080, true );
} );

/**
 * Disable Astra's own scroll-to-top — the theme ships exactly ONE
 * back-to-top button, fully controlled from the Customizer.
 */
add_filter( 'astra_get_option_scroll-to-top-enable', '__return_false' );

/**
 * Replace Astra's header & footer with the Rafah native ones.
 *
 * Astra registers several markup callbacks (legacy + header-builder +
 * mobile + transparent variants) on these hooks — clearing the whole hook
 * at runtime is the version-proof way to take full ownership.
 */
add_action( 'wp', function () {
	if ( is_admin() ) {
		return;
	}

	remove_all_actions( 'astra_header' );
	remove_all_actions( 'astra_footer' );

	add_action( 'astra_header', function () {
		get_template_part( 'template-parts/site', 'header' );
	} );

	add_action( 'astra_footer', function () {
		get_template_part( 'template-parts/site', 'footer' );
	} );
} );

// ---------------------------------------------------------------- Assets.

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'rafah-fonts',
		'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap',
		array(),
		null
	);

	wp_enqueue_style( 'rafah-theme', get_stylesheet_uri(), array( 'astra-theme-css' ), RAFAH_THEME_VERSION );

	// Brand colors + back-to-top settings from the Customizer → CSS variables.
	wp_add_inline_style( 'rafah-theme', sprintf(
		':root{--rafah-primary:%s;--rafah-secondary:%s;--rafah-bg:%s;--rafah-btt-size:%dpx;--rafah-btt-bg:%s;--rafah-btt-color:%s;}body{background-color:%s;}#ast-scroll-top{display:none!important;}',
		sanitize_hex_color( rafah_opt( 'color_primary', '#bc945d' ) ),
		sanitize_hex_color( rafah_opt( 'color_secondary', '#6b5840' ) ),
		sanitize_hex_color( rafah_opt( 'color_bg', '#e4dcd5' ) ),
		(int) rafah_opt( 'btt_size', 46 ),
		sanitize_hex_color( rafah_opt( 'btt_bg', '#bc945d' ) ),
		sanitize_hex_color( rafah_opt( 'btt_color', '#ffffff' ) ),
		sanitize_hex_color( rafah_opt( 'color_bg', '#e4dcd5' ) )
	) );

	// Logo height (Customizer → Site Identity). 0 = automatic; width scales.
	$rafah_logo_h = (int) rafah_opt( 'logo_height', 0 );
	if ( $rafah_logo_h > 0 ) {
		wp_add_inline_style( 'rafah-theme', sprintf(
			'.rafah-header__brand img,.rafah-header__brand .custom-logo{height:%1$dpx!important;width:auto!important;max-height:none!important;}.rafah-offcanvas__head img{height:%1$dpx!important;width:auto!important;}',
			$rafah_logo_h
		) );
	}

	wp_enqueue_script( 'rafah-theme', get_stylesheet_directory_uri() . '/assets/js/theme.js', array(), RAFAH_THEME_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}, 15 );

add_filter( 'wp_resource_hints', function ( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => true );
	}

	return $urls;
}, 10, 2 );

// ---------------------------------------------------------------- Body classes.

add_filter( 'body_class', function ( $classes ) {
	if ( rafah_opt( 'header_sticky', true ) ) {
		$classes[] = 'rafah-has-sticky-header';
	}

	if ( is_front_page() && rafah_opt( 'header_transparent', true ) ) {
		$classes[] = 'rafah-header-transparent';
	}

	return $classes;
} );

// ---------------------------------------------------------------- Widget areas.

add_action( 'widgets_init', function () {
	for ( $i = 1; $i <= 4; $i++ ) {
		register_sidebar( array(
			'id'            => 'rafah-footer-' . $i,
			/* translators: %d: column number */
			'name'          => sprintf( __( 'Footer Column %d', 'rafah-theme' ), $i ),
			'description'   => __( 'Leave empty to show the smart default content for this column.', 'rafah-theme' ),
			'before_widget' => '<div class="rafah-footer__widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="rafah-footer__title">',
			'after_title'   => '</h4>',
		) );
	}
} );

// ---------------------------------------------------------------- Hero.

/**
 * Single source of truth for hero markup — used by BOTH the Elementor widget
 * (visual editing) and the native PHP fallback (Customizer defaults).
 *
 * @param array $args See defaults below.
 */
function rafah_theme_hero( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'image'       => rafah_opt( 'hero_image' ),
		'video'       => rafah_opt( 'hero_video' ),
		'overlay'     => rafah_opt( 'hero_overlay', 60 ),
		'eyebrow'     => rafah_opt( 'hero_eyebrow', 'رفاه للتطوير العقاري' ),
		'title'       => rafah_opt( 'hero_title', 'نبني <em>مجتمعات</em> تليق بحياتك' ),
		'text'        => rafah_opt( 'hero_text', 'مشاريع سكنية فاخرة في أرقى أحياء المملكة، مصممة بعناية لترتقي بأسلوب حياتك.' ),
		'btn1_text'   => rafah_opt( 'hero_btn1_text', 'استكشف مشاريعنا' ),
		'btn1_url'    => rafah_opt( 'hero_btn1_url', '/projects/' ),
		'btn2_text'   => rafah_opt( 'hero_btn2_text', 'تواصل معنا' ),
		'btn2_url'    => rafah_opt( 'hero_btn2_url', '/contact/' ),
		'show_scroll' => rafah_opt( 'hero_scroll', true ),
		'stats'       => null, // null = read the 3 Customizer stats.
	) );

	if ( null === $args['stats'] ) {
		$args['stats'] = array();

		for ( $i = 1; $i <= 3; $i++ ) {
			$value = rafah_opt( 'hero_stat' . $i . '_value' );
			if ( '' !== $value ) {
				$args['stats'][] = array(
					'value' => $value,
					'label' => rafah_opt( 'hero_stat' . $i . '_label' ),
				);
			}
		}
	}

	// Normalise buttons: the Elementor widget passes a `buttons` array; the
	// pure-PHP / Customizer hero builds it from the legacy btn1/btn2 fields.
	// Either way the template only ever sees a `buttons` array, and an empty
	// one renders nothing.
	if ( ! isset( $args['buttons'] ) || null === $args['buttons'] ) {
		$args['buttons'] = array();
		if ( ! empty( $args['btn1_text'] ) ) {
			$args['buttons'][] = array( 'text' => $args['btn1_text'], 'link' => array( 'url' => $args['btn1_url'] ?? '#' ), 'variant' => 'primary' );
		}
		if ( ! empty( $args['btn2_text'] ) ) {
			$args['buttons'][] = array( 'text' => $args['btn2_text'], 'link' => array( 'url' => $args['btn2_url'] ?? '#' ), 'variant' => 'light' );
		}
	}

	get_template_part( 'template-parts/hero', null, $args );
}

/**
 * Register the hero Elementor widget FROM THE THEME through the plugin's
 * public extension point. The widget is presentation → it lives here; the
 * plugin never controls the hero. Runs only when Elementor is present.
 */
add_filter( 'rafah_core_widgets', function ( $widgets ) {
	if ( class_exists( '\Elementor\Widget_Base' ) ) {
		require_once get_stylesheet_directory() . '/inc/class-rafah-hero-widget.php';
		$widgets['theme-hero'] = 'Rafah_Widget_Hero';

		require_once get_stylesheet_directory() . '/inc/class-rafah-project-section-widget.php';
		$widgets['theme-project-section'] = 'Rafah_Widget_Project_Section';

		// Phase 2 — discrete per-section widgets (Hero, Facts, Overview, Payment,
		// Units, Downloads, Amenities, Related, …), each with full native style
		// controls. Data from Rafah Core; theme design is the default fallback.
		require_once get_stylesheet_directory() . '/inc/class-rafah-section-widgets.php';
		if ( class_exists( 'Rafah_Section_Widgets' ) ) {
			foreach ( Rafah_Section_Widgets::classes() as $key => $class ) {
				$widgets[ 'theme-' . $key ] = $class;
			}
		}
	}

	return $widgets;
} );

/**
 * Native hero fallback on the front page.
 * Modes: auto (show when Elementor isn't rendering the page), always, never.
 */
add_action( 'astra_content_before', function () {
	if ( ! is_front_page() || is_admin() ) {
		return;
	}

	$mode = rafah_opt( 'hero_mode', 'auto' );

	if ( 'never' === $mode ) {
		return;
	}

	if ( 'always' === $mode || ! rafah_is_elementor_page( get_queried_object_id() ) ) {
		rafah_theme_hero();
	}
} );

/**
 * Fail-safe homepage: if the front page was built with Elementor but
 * Elementor is unavailable, its content is empty — render the native
 * fallback sections instead. The hero renders separately (above).
 */
add_filter( 'the_content', function ( $content ) {
	if ( is_front_page()
		&& in_the_loop()
		&& ! did_action( 'elementor/loaded' )
		&& get_post_meta( get_the_ID(), '_elementor_data', true )
	) {
		ob_start();
		get_template_part( 'template-parts/home', 'fallback' );

		return ob_get_clean();
	}

	return $content;
}, 5 );

// ---------------------------------------------------------------- Fail-safe helpers.

/**
 * Render a shortcode only when its tag is actually registered — a form
 * section must vanish gracefully if Fluent Forms is deactivated, never
 * print raw shortcode text to visitors.
 */
function rafah_theme_render_shortcode( $shortcode ) {
	if ( ! $shortcode ) {
		return '';
	}

	if ( preg_match( '/\[\s*([a-zA-Z0-9_-]+)/', $shortcode, $match ) && ! shortcode_exists( $match[1] ) ) {
		return '';
	}

	return do_shortcode( $shortcode );
}

/**
 * Resilient nav renderer: assigned location → first existing menu →
 * styled page list. The site always has a working navigation.
 */
function rafah_theme_nav( $location, $menu_class, $depth = 3 ) {
	$args = array(
		'container'   => false,
		'menu_class'  => $menu_class,
		'depth'       => $depth,
		'fallback_cb' => false,
	);

	if ( has_nav_menu( $location ) ) {
		$args['theme_location'] = $location;
	} else {
		$menus = wp_get_nav_menus();

		if ( $menus && ! is_wp_error( $menus ) ) {
			$args['menu'] = $menus[0]->term_id;
		} else {
			wp_page_menu( array( 'menu_class' => $menu_class ) );
			return;
		}
	}

	wp_nav_menu( $args );
}

/**
 * Polylang language switcher — renders nothing when Polylang is inactive.
 */
function rafah_theme_language_switcher() {
	if ( ! function_exists( 'pll_the_languages' ) ) {
		return;
	}

	// Optional: hide the visual switcher only. Polylang stays active — English
	// URLs, hreflang tags, the sitemap and indexing are generated by Polylang
	// independently of this switcher, so SEO is unaffected.
	if ( rafah_opt( 'hide_lang_switcher' ) ) {
		return;
	}

	$languages = pll_the_languages( array( 'raw' => 1, 'hide_if_empty' => 0 ) );

	if ( ! $languages || count( $languages ) < 2 ) {
		return;
	}
	?>
	<nav class="rafah-langs" aria-label="Languages">
		<?php foreach ( $languages as $language ) : ?>
			<a href="<?php echo esc_url( $language['url'] ); ?>"
				class="rafah-langs__item<?php echo $language['current_lang'] ? ' is-current' : ''; ?>"
				<?php echo $language['current_lang'] ? 'aria-current="true"' : ''; ?>>
				<?php echo esc_html( strtoupper( $language['slug'] ) ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php
}

// ---------------------------------------------------------------- Existing helpers.

/**
 * Open Graph fallback when Rank Math is not active.
 */
add_action( 'wp_head', function () {
	if ( defined( 'RANK_MATH_VERSION' ) || ! is_singular( array( 'project', 'agent' ) ) ) {
		return;
	}

	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( get_the_title() ) );
	printf( '<meta property="og:type" content="website">' . "\n" );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( get_permalink() ) );

	if ( has_post_thumbnail() ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( get_the_post_thumbnail_url( null, 'large' ) ) );
	}

	if ( has_excerpt() ) {
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( get_the_excerpt() ) ) );
	}
}, 6 );

/**
 * Visual breadcrumbs for project/agent pages (schema comes from Rafah Core).
 */
function rafah_theme_breadcrumbs() {
	if ( ! is_singular( array( 'project', 'agent' ) ) || ! function_exists( 'rafah_text' ) ) {
		return;
	}

	$archive = get_post_type_archive_link( get_post_type() );
	$label   = 'project' === get_post_type() ? rafah_text( 'projects' ) : rafah_text( 'agents' );
	?>
	<nav class="rafah-breadcrumbs" aria-label="Breadcrumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( rafah_text( 'home' ) ); ?></a>
		<span class="sep">/</span>
		<a href="<?php echo esc_url( $archive ); ?>"><?php echo esc_html( $label ); ?></a>
		<span class="sep">/</span>
		<span><?php the_title(); ?></span>
	</nav>
	<?php
}

/**
 * Share buttons for a post.
 */
function rafah_theme_share_buttons( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$url     = rawurlencode( get_permalink( $post_id ) );
	$title   = rawurlencode( get_the_title( $post_id ) );
	?>
	<div class="rafah-share">
		<a class="rafah-icon-btn" href="https://x.com/intent/tweet?url=<?php echo $url; // phpcs:ignore ?>&text=<?php echo $title; // phpcs:ignore ?>" target="_blank" rel="noopener" aria-label="X">
			<svg viewBox="0 0 24 24"><path d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3l-4.9-6.4L6.4 22H3.3l7.3-8.3L1.5 2H8l4.4 5.9L18.9 2zm-1.1 18h1.7L7.1 3.9H5.3L17.8 20z"/></svg>
		</a>
		<a class="rafah-icon-btn rafah-icon-btn--whatsapp" href="https://wa.me/?text=<?php echo $title . '%20' . $url; // phpcs:ignore ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
			<svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm5.1 14.1c-.2.6-1.2 1.1-1.7 1.2-.5.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.5-2.6-1.1-4.3-3.7-4.4-3.9-.1-.2-1-1.4-1-2.6s.6-1.8.9-2.1c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .5.4l.7 1.8c.1.2.1.4 0 .5l-.4.6-.2.3c-.1.1-.2.2-.1.4.1.2.6 1 1.3 1.7.9.8 1.7 1.1 2 1.2.2.1.4.1.5-.1l.7-.8c.2-.2.3-.2.5-.1l1.8.8c.2.1.4.2.4.3.1.1.1.7-.1 1.3z"/></svg>
		</a>
		<a class="rafah-icon-btn" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $url; // phpcs:ignore ?>" target="_blank" rel="noopener" aria-label="LinkedIn">
			<svg viewBox="0 0 24 24"><path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1s2.48 1.12 2.48 2.5zM.5 8h4V24h-4V8zm7.5 0h3.8v2.2h.1c.5-1 1.8-2.2 3.8-2.2 4 0 4.8 2.6 4.8 6.1V24h-4v-8.5c0-2-.1-4.7-2.9-4.7-2.9 0-3.3 2.2-3.3 4.5V24H8V8z"/></svg>
		</a>
	</div>
	<?php
}

/**
 * Convert a YouTube/Vimeo URL to an embeddable iframe src.
 */
function rafah_theme_embed_url( $url ) {
	if ( preg_match( '~youtu\.be/([\w-]+)~', $url, $m ) || preg_match( '~youtube\.com/watch\?v=([\w-]+)~', $url, $m ) ) {
		return 'https://www.youtube-nocookie.com/embed/' . $m[1];
	}

	if ( preg_match( '~vimeo\.com/(\d+)~', $url, $m ) ) {
		return 'https://player.vimeo.com/video/' . $m[1];
	}

	return $url;
}

/**
 * Hidden build credit.
 *
 * Printed as the first line inside <head> on the homepage — invisible on the
 * rendered page, but visible to anyone who opens View Source / Inspect.
 * Priority 0 puts it at the very top of the head output. (We intentionally
 * do NOT print before <!DOCTYPE>, which would throw browsers into quirks mode
 * and break the layout.)
 */
add_action( 'wp_head', function () {
	if ( is_front_page() || is_home() ) {
		echo "<!-- Developed By Sami AL-Hattami -->\n";
	}
}, 0 );

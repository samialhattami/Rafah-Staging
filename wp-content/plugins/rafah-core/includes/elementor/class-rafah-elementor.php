<?php
/**
 * Rafah Core — Elementor integration (works with Elementor FREE).
 * Registers a "Rafah" widget category and premium custom widgets.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Elementor {

	public static function init() {
		// Make every public, editor-enabled content type editable with Elementor
		// (generic — projects, news, posts, pages, etc.). The Theme remains the
		// default renderer and everything works with Elementor disabled.
		add_action( 'admin_init', array( __CLASS__, 'ensure_cpt_support' ) );

		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'elementor/loaded', array( __CLASS__, 'hooks' ) );
			return;
		}

		self::hooks();
	}

	/**
	 * Union every public, editor-enabled post type into Elementor's supported
	 * list so "Edit with Elementor" is available on them like pages/posts.
	 * Only ADDS (never removes the editor's own choices); writes only when the
	 * list actually changes. Customise via the `rafah_elementor_post_types`
	 * filter (e.g. to exclude a type).
	 */
	public static function ensure_cpt_support() {
		$current = get_option( 'elementor_cpt_support' );
		if ( ! is_array( $current ) ) {
			$current = array( 'page', 'post' );
		}

		$wanted = array( 'page', 'post' );
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $pt ) {
			if ( post_type_supports( $pt->name, 'editor' ) ) {
				$wanted[] = $pt->name;
			}
		}

		/** Filter the post types Rafah makes Elementor-editable. */
		$wanted = (array) apply_filters( 'rafah_elementor_post_types', array_values( array_unique( $wanted ) ) );

		$merged = array_values( array_unique( array_merge( $current, $wanted ) ) );
		sort( $merged );

		$cur = $current;
		sort( $cur );

		if ( $merged !== $cur ) {
			update_option( 'elementor_cpt_support', $merged );
		}
	}

	public static function hooks() {
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'widgets' ) );

		// Elementor PRO is strictly ADDITIVE. Its integration (Dynamic Tags,
		// Query providers for Loop Grid, Theme Builder locations) loads only when
		// Pro is active, in its own guarded bootstrap. If Pro is absent, expired,
		// or deactivated, none of it runs and the theme's PHP templates + these
		// Free widgets keep the site fully working. Pro never becomes a hard
		// dependency. See RAFAH-ELEMENTOR-ARCHITECTURE.md.
		if ( self::has_pro() ) {
			$pro = RAFAH_CORE_PATH . 'includes/elementor/class-rafah-elementor-pro.php';
			if ( file_exists( $pro ) ) {
				require_once $pro;
				if ( class_exists( 'Rafah_Elementor_Pro' ) ) {
					Rafah_Elementor_Pro::init();
				}
			}
		}
	}

	/**
	 * Capability layer — the single source of truth for what Elementor features
	 * are available. Everything Pro-specific must be gated behind has_pro() so
	 * the site degrades gracefully to Free + PHP fallbacks.
	 */

	/** Is Elementor (Free or Pro) active and loaded? */
	public static function has_elementor() {
		return (bool) did_action( 'elementor/loaded' );
	}

	/** Is Elementor PRO active? Used to gate every Pro-only registration. */
	public static function has_pro() {
		return did_action( 'elementor_pro/init' ) || class_exists( '\ElementorPro\Plugin' );
	}

	/** Running on Elementor Free (Elementor present, Pro absent)? */
	public static function is_free() {
		return self::has_elementor() && ! self::has_pro();
	}

	public static function category( $elements_manager ) {
		$elements_manager->add_category(
			'rafah',
			array(
				'title' => __( 'Rafah', 'rafah' ),
				'icon'  => 'fa fa-building',
			)
		);
	}

	public static function widgets( $widgets_manager ) {
		/**
		 * Filter the widget list — register additional Rafah widgets from an
		 * extension or the theme. Note: the Hero widget is PRESENTATION and
		 * is registered by the Rafah theme through this filter — the plugin
		 * never controls the site hero, header, or footer.
		 */
		$widgets = apply_filters( 'rafah_core_widgets', array(
			'projects-grid'  => 'Rafah_Widget_Projects_Grid',
			'project-filter' => 'Rafah_Widget_Project_Filter',
			'stats'          => 'Rafah_Widget_Stats',
			'agents-grid'    => 'Rafah_Widget_Agents_Grid',
			'testimonials'   => 'Rafah_Widget_Testimonials',
			'faq'            => 'Rafah_Widget_FAQ',
			'cta'            => 'Rafah_Widget_CTA',
			'news'           => 'Rafah_Widget_News',
			'blog'           => 'Rafah_Widget_Blog',
			'gallery'        => 'Rafah_Widget_Gallery',
			'projects-map'   => 'Rafah_Widget_Projects_Map',
		) );

		foreach ( $widgets as $file => $class ) {
			$path = RAFAH_CORE_PATH . 'includes/elementor/widgets/class-widget-' . $file . '.php';

			if ( file_exists( $path ) ) {
				require_once $path;
			}

			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	}
}

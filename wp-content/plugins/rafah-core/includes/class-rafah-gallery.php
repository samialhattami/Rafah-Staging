<?php
/**
 * Rafah Core — Project Gallery placement + rendering.
 *
 * All gallery logic lives here (Core). The theme only decides WHERE the gallery
 * section appears (via the Section Manager order); the markup is a single source
 * of truth shared with the Project Gallery Elementor widget.
 *
 * The gallery renders as a responsive horizontal carousel; clicking any item
 * opens a dependency-free lightbox (see assets/js/rafah.js + rafah.css). Project
 * videos (the `video_url` field) are included as gallery items automatically.
 *
 * Data structures are unchanged: images come from the `_rafah_gallery` field
 * (attachment IDs) and videos from `_rafah_video_url`. This module only READS
 * them — no field, query, or DB change.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Gallery {

	public static function init() {
		// The gallery is a registered project SECTION, rendered by the theme in
		// the Section Manager order (via Rafah_Gallery::render_section()).
	}

	/**
	 * Gallery attachment IDs for a project.
	 */
	public static function ids( $project_id ) {
		return array_filter( array_map( 'absint', explode( ',', (string) rafah_meta( 'gallery', $project_id ) ) ) );
	}

	/**
	 * Project video URLs (currently the single `video_url` field). Returns an
	 * array so more sources can be added later without touching callers.
	 */
	public static function videos( $project_id ) {
		$url = trim( (string) rafah_meta( 'video_url', $project_id ) );

		return $url ? array( $url ) : array();
	}

	/**
	 * Whether there is anything to show (at least one image or video).
	 */
	public static function has_items( $project_id ) {
		return (bool) ( self::ids( $project_id ) || self::videos( $project_id ) );
	}

	/**
	 * The chosen position: before | after | hidden (default: before).
	 */
	public static function position( $project_id ) {
		$pos = rafah_meta( 'gallery_position', $project_id );

		return in_array( $pos, array( 'before', 'after', 'hidden' ), true ) ? $pos : 'before';
	}

	public static function render_before( $project_id ) {
		if ( 'before' === self::position( $project_id ) ) {
			self::render_section( $project_id );
		}
	}

	public static function render_after( $project_id ) {
		if ( 'after' === self::position( $project_id ) ) {
			self::render_section( $project_id );
		}
	}

	/**
	 * Section wrapper used on the single project page. Renders nothing when the
	 * project has no images and no videos.
	 */
	public static function render_section( $project_id ) {
		if ( ! self::has_items( $project_id ) ) {
			return;
		}
		?>
		<section id="gallery" class="rafah-project-gallery-section">
			<h2><?php echo esc_html( rafah_text( 'gallery' ) ); ?></h2>
			<?php self::grid( $project_id, '3', true ); ?>
		</section>
		<?php
	}

	/**
	 * Convert a YouTube/Vimeo watch URL into an embeddable player URL. Self-
	 * contained (no theme dependency) so the gallery works with any theme.
	 *
	 * @param string $url      Raw video URL.
	 * @param bool   $autoplay Add autoplay (used inside the lightbox).
	 * @return string Embed URL, or '' if unrecognised.
	 */
	public static function embed_url( $url, $autoplay = false ) {
		$id = '';

		if ( preg_match( '~youtu\.be/([\w-]+)~', $url, $m ) || preg_match( '~youtube\.com/(?:watch\?v=|embed/)([\w-]+)~', $url, $m ) ) {
			$src = 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&playsinline=1';
			return $autoplay ? $src . '&autoplay=1' : $src;
		}

		if ( preg_match( '~vimeo\.com/(\d+)~', $url, $m ) ) {
			$src = 'https://player.vimeo.com/video/' . $m[1];
			return $autoplay ? $src . '?autoplay=1' : $src;
		}

		return $url; // Allow any other direct embeddable URL as-is.
	}

	/**
	 * A poster image URL for a video item: the YouTube thumbnail when available,
	 * otherwise the project's first gallery image, otherwise ''.
	 *
	 * @param string $url        Video URL.
	 * @param int    $project_id Project (for the fallback image).
	 * @return string
	 */
	public static function video_thumb( $url, $project_id ) {
		if ( preg_match( '~youtu\.be/([\w-]+)~', $url, $m ) || preg_match( '~youtube\.com/(?:watch\?v=|embed/)([\w-]+)~', $url, $m ) ) {
			return 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg';
		}

		$ids = self::ids( $project_id );
		if ( $ids ) {
			return (string) wp_get_attachment_image_url( reset( $ids ), 'rafah-card' );
		}

		return '';
	}

	/**
	 * The gallery carousel markup — shared by the single-page section and the
	 * Elementor widget. Renders nothing when the project has no items.
	 *
	 * @param int    $project_id Project.
	 * @param string $cols       Desktop slides in view: 2 | 3 | 4.
	 * @param bool   $lightbox   When true (default) items open the lightbox.
	 */
	public static function grid( $project_id, $cols = '3', $lightbox = true ) {
		$ids    = self::ids( $project_id );
		$videos = self::videos( $project_id );

		if ( ! $ids && ! $videos ) {
			return;
		}

		$title = get_the_title( $project_id );

		self::carousel_open( $cols, $lightbox );
		$i = 0;

		foreach ( $ids as $img_id ) :
			$full = wp_get_attachment_image_url( $img_id, 'full' );
			$cap  = trim( (string) wp_get_attachment_caption( $img_id ) );
			if ( '' === $cap ) {
				$cap = $title;
			}
			?>
			<li class="rafah-gallery__slide">
				<button type="button" class="rafah-gallery__item" data-index="<?php echo esc_attr( $i ); ?>" data-type="image" data-full="<?php echo esc_url( $full ); ?>" data-caption="<?php echo esc_attr( $cap ); ?>">
					<?php echo wp_get_attachment_image( $img_id, 'rafah-card', false, array( 'loading' => 'lazy', 'alt' => $cap ) ); ?>
				</button>
			</li>
			<?php
			$i++;
		endforeach;

		foreach ( $videos as $video ) :
			$poster = self::video_thumb( $video, $project_id );
			?>
			<li class="rafah-gallery__slide">
				<button type="button" class="rafah-gallery__item rafah-gallery__item--video" data-index="<?php echo esc_attr( $i ); ?>" data-type="video" data-embed="<?php echo esc_url( self::embed_url( $video, true ) ); ?>" data-caption="<?php echo esc_attr( $title ); ?>">
					<?php if ( $poster ) : ?>
						<img src="<?php echo esc_url( $poster ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
					<?php else : ?>
						<span class="rafah-gallery__videobg" aria-hidden="true"></span>
					<?php endif; ?>
					<span class="rafah-gallery__play" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
					</span>
				</button>
			</li>
			<?php
			$i++;
		endforeach;

		self::carousel_close( $i > 1 );
	}

	/**
	 * Open the shared carousel scaffold (wrapper + viewport + track). Reused by
	 * the gallery AND any other section that wants the same slider (e.g. Floor
	 * Plans) — so the slider component, arrows, CSS and JS live in ONE place.
	 * Emit `.rafah-gallery__slide` children, then call carousel_close().
	 *
	 * @param string $cols        Slides in view on desktop: 2 | 3 | 4.
	 * @param bool   $lightbox    Add the lightbox hook (image galleries only).
	 * @param string $extra_class Optional extra class on the wrapper.
	 */
	public static function carousel_open( $cols = '3', $lightbox = false, $extra_class = '' ) {
		$cols    = in_array( (string) $cols, array( '2', '3', '4' ), true ) ? (string) $cols : '3';
		$classes = 'rafah-gallery rafah-gallery--cols-' . $cols . ( $extra_class ? ' ' . $extra_class : '' );

		echo '<div class="' . esc_attr( $classes ) . '" data-rafah-gallery' . ( $lightbox ? ' data-rafah-lightbox="1"' : '' ) . '>';
		echo '<div class="rafah-gallery__viewport"><ul class="rafah-gallery__track">';
	}

	/**
	 * Close the carousel scaffold, adding the shared prev/next arrows when there
	 * is more than one slide.
	 *
	 * @param bool $show_nav Whether to render the navigation arrows.
	 */
	public static function carousel_close( $show_nav = true ) {
		echo '</ul></div>';
		if ( $show_nav ) {
			self::nav_buttons();
		}
		echo '</div>';
	}

	/**
	 * The shared carousel navigation arrows — one definition, used everywhere.
	 */
	public static function nav_buttons() {
		?>
		<button type="button" class="rafah-gallery__nav rafah-gallery__nav--prev" aria-label="<?php echo esc_attr( rafah_text( 'prev' ) ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
		</button>
		<button type="button" class="rafah-gallery__nav rafah-gallery__nav--next" aria-label="<?php echo esc_attr( rafah_text( 'next' ) ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
		</button>
		<?php
	}
}

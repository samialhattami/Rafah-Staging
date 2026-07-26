<?php
/**
 * Rafah Core — News & Blog homepage section renderers + shortcodes.
 *
 * Single source of truth for the two editorial homepage sections. Both the
 * Elementor widgets (Rafah News / Rafah Blog) and the shortcodes
 * ([rafah_news] / [rafah_blog]) render through these functions, so the markup
 * never diverges and the sections can be placed with or without Elementor.
 *
 * News  = News CPT (company announcements) — large feature + two mini cards.
 * Blog  = WordPress Posts (articles) — three equal cards.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the News section (News CPT). Echoes markup; nothing if there is no news.
 *
 * @param array $args eyebrow, title, count, category (news_category slug), show_view_all.
 */
function rafah_news_section( $args = array() ) {
	$ar   = rafah_is_rtl_lang();
	$args = wp_parse_args( $args, array(
		'eyebrow'       => rafah_text( 'news' ),
		'title'         => $ar ? 'آخر أخبار رفاه' : 'Latest News',
		'count'         => 3,
		'category'      => '',
		'show_view_all' => true,
	) );

	$query_args = array(
		'post_type'      => 'news',
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, (int) $args['count'] ),
	);

	if ( ! empty( $args['category'] ) ) {
		$query_args['tax_query'] = array( array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'taxonomy' => 'news_category',
			'field'    => 'slug',
			'terms'    => $args['category'],
		) );
	}

	$query = new WP_Query( $query_args );

	if ( ! $query->have_posts() ) {
		return;
	}

	$posts   = $query->posts;
	$feature = array_shift( $posts );
	?>
	<div class="rafah-news">
		<?php if ( $args['title'] || $args['eyebrow'] ) : ?>
			<div class="rafah-section-head rafah-section-head--center">
				<?php if ( $args['eyebrow'] ) : ?>
					<span class="rafah-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></span>
				<?php endif; ?>
				<?php if ( $args['title'] ) : ?>
					<h2 class="rafah-section-head__title"><?php echo esc_html( $args['title'] ); ?></h2>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<article class="rafah-news__feature">
			<a class="rafah-news__feature-media" href="<?php echo esc_url( get_permalink( $feature ) ); ?>" tabindex="-1" aria-hidden="true">
				<?php
				if ( has_post_thumbnail( $feature ) ) {
					echo get_the_post_thumbnail( $feature, 'large', array( 'loading' => 'lazy' ) );
				}
				?>
			</a>
			<div class="rafah-news__feature-card">
				<?php
				$terms = get_the_terms( $feature->ID, 'news_category' );
				$chip  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : rafah_text( 'news' );
				?>
				<span class="rafah-news__chip"><?php echo esc_html( $chip ); ?></span>
				<h3 class="rafah-news__feature-title">
					<a href="<?php echo esc_url( get_permalink( $feature ) ); ?>"><?php echo esc_html( get_the_title( $feature ) ); ?></a>
				</h3>
				<p class="rafah-news__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $feature ), 26 ) ); ?></p>
				<span class="rafah-news__date">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
					<?php echo esc_html( get_the_date( '', $feature ) ); ?>
				</span>
				<div>
					<a class="rafah-btn rafah-btn--primary" href="<?php echo esc_url( get_permalink( $feature ) ); ?>">
						<?php echo esc_html( rafah_text( 'read_more' ) ); ?>
					</a>
				</div>
			</div>
		</article>

		<?php if ( $posts ) : ?>
			<div class="rafah-news__grid">
				<?php foreach ( $posts as $post_item ) : ?>
					<a class="rafah-news__mini" href="<?php echo esc_url( get_permalink( $post_item ) ); ?>">
						<span class="rafah-news__mini-thumb">
							<?php
							if ( has_post_thumbnail( $post_item ) ) {
								echo get_the_post_thumbnail( $post_item, 'thumbnail', array( 'loading' => 'lazy' ) );
							}
							?>
						</span>
						<span>
							<span class="rafah-news__mini-date"><?php echo esc_html( get_the_date( '', $post_item ) ); ?></span>
							<h4 class="rafah-news__mini-title"><?php echo esc_html( get_the_title( $post_item ) ); ?></h4>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $args['show_view_all'] ) : ?>
			<div class="rafah-news__footer">
				<a class="rafah-btn rafah-btn--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'news' ) ?: home_url( '/news/' ) ); ?>">
					<?php echo esc_html( rafah_text( 'view_all_news' ) ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
	<?php
	wp_reset_postdata();
}

/**
 * Render the Blog section (WordPress Posts). Echoes markup; nothing if empty.
 *
 * @param array $args eyebrow, title, count, category (category slug), show_view_all.
 */
function rafah_blog_section( $args = array() ) {
	$ar   = rafah_is_rtl_lang();
	$args = wp_parse_args( $args, array(
		'eyebrow'       => rafah_text( 'blog' ),
		'title'         => $ar ? 'أحدث المقالات' : 'Latest Articles',
		'count'         => 3,
		'category'      => '',
		'show_view_all' => true,
	) );

	$query_args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, (int) $args['count'] ),
		'ignore_sticky_posts' => true,
	);

	if ( ! empty( $args['category'] ) ) {
		$query_args['category_name'] = $args['category'];
	}

	$query = new WP_Query( $query_args );

	if ( ! $query->have_posts() ) {
		return;
	}
	?>
	<div class="rafah-blog">
		<?php if ( $args['title'] || $args['eyebrow'] ) : ?>
			<div class="rafah-section-head rafah-section-head--center">
				<?php if ( $args['eyebrow'] ) : ?>
					<span class="rafah-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></span>
				<?php endif; ?>
				<?php if ( $args['title'] ) : ?>
					<h2 class="rafah-section-head__title"><?php echo esc_html( $args['title'] ); ?></h2>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="rafah-blog__grid">
			<?php
			while ( $query->have_posts() ) {
				$query->the_post();
				rafah_blog_card( get_the_ID() );
			}
			wp_reset_postdata();
			?>
		</div>

		<?php if ( $args['show_view_all'] ) : ?>
			<div class="rafah-blog__footer">
				<a class="rafah-btn rafah-btn--primary" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/' ) ); ?>">
					<?php echo esc_html( rafah_text( 'view_all_articles' ) ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
	<?php
	wp_reset_postdata();
}

/**
 * Shortcodes — place either section anywhere (Elementor Shortcode/Text widget,
 * page content, etc.) without depending on the Elementor widget.
 *
 * [rafah_news count="3" category="" title="" eyebrow="" button="yes"]
 * [rafah_blog count="3" category="" title="" eyebrow="" button="yes"]
 */
add_shortcode( 'rafah_news', function ( $atts ) {
	$a = shortcode_atts( array( 'count' => 3, 'category' => '', 'title' => '', 'eyebrow' => '', 'button' => 'yes' ), $atts, 'rafah_news' );

	$args = array(
		'count'         => (int) $a['count'],
		'category'      => sanitize_title( $a['category'] ),
		'show_view_all' => 'no' !== $a['button'],
	);
	if ( '' !== $a['title'] ) {
		$args['title'] = $a['title'];
	}
	if ( '' !== $a['eyebrow'] ) {
		$args['eyebrow'] = $a['eyebrow'];
	}

	ob_start();
	rafah_news_section( $args );
	return ob_get_clean();
} );

add_shortcode( 'rafah_blog', function ( $atts ) {
	$a = shortcode_atts( array( 'count' => 3, 'category' => '', 'title' => '', 'eyebrow' => '', 'button' => 'yes' ), $atts, 'rafah_blog' );

	$args = array(
		'count'         => (int) $a['count'],
		'category'      => sanitize_title( $a['category'] ),
		'show_view_all' => 'no' !== $a['button'],
	);
	if ( '' !== $a['title'] ) {
		$args['title'] = $a['title'];
	}
	if ( '' !== $a['eyebrow'] ) {
		$args['eyebrow'] = $a['eyebrow'];
	}

	ob_start();
	rafah_blog_section( $args );
	return ob_get_clean();
} );

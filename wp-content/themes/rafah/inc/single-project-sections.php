<?php
/**
 * Rafah Theme — single-project section ordering & headings.
 *
 * Renders the single-project sections in the ACTUAL sorted PHP order from the
 * Section Manager, so the final DOM order is correct for SEO, accessibility,
 * anchor links, JavaScript and future extensions. Per-section heading overrides
 * are applied; an empty custom heading falls back to the default label.
 *
 * The section HTML itself lives untouched in single-project.php; that block is
 * output-buffered and this pass re-emits the <section> blocks in order (drops
 * hidden ones, keeps any unknown ones). Works with Elementor disabled.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ordered, visible section ids for a content type (from the Section Manager).
 *
 * @return string[]
 */
function rafah_theme_section_order( $type = 'project' ) {
	if ( ! class_exists( 'Rafah_Sections' ) ) {
		return array();
	}

	$order = array();
	$i     = 0;

	foreach ( Rafah_Sections::sections( $type ) as $id => $tkey ) {
		$i   += 10;
		$skey = $type . '_' . str_replace( '-', '_', $id );

		if ( rafah_opt( 'psec_hide_' . $skey, false ) ) {
			continue;
		}

		$order[ $id ] = (int) rafah_opt( 'psec_order_' . $skey, $i );
	}

	asort( $order );

	return array_keys( $order );
}

/**
 * Custom heading for a section, falling back to the default when empty.
 */
function rafah_theme_section_heading( $type, $id, $default ) {
	$skey   = $type . '_' . str_replace( '-', '_', $id );
	$custom = trim( (string) rafah_opt( 'psec_head_' . $skey, '' ) );

	return '' !== $custom ? $custom : $default;
}

/**
 * Re-emit buffered <section> blocks in the Section Manager order, dropping
 * hidden sections and applying heading overrides. Unknown sections (not in the
 * registry) are kept so content is never lost.
 *
 * @deprecated 1.18.0 Superseded by true PHP-order rendering — single-project.php
 *             now loops rafah_theme_section_order()/rafah_theme_render_section()
 *             directly. Retained only for backward compatibility with any
 *             extension that still buffers-and-reorders; safe to remove once no
 *             external caller relies on it.
 *
 * @param string $html Buffered main-content HTML.
 * @param string $type Content type key (e.g. 'project').
 * @return string
 */
function rafah_theme_reorder_sections( $html, $type = 'project', $project_id = 0 ) {
	if ( ! class_exists( 'Rafah_Sections' ) || false === strpos( $html, '<section' ) ) {
		return $html;
	}

	$blocks = array();
	if ( preg_match_all( '/<section\b[^>]*\bid="([^"]+)"[^>]*>.*?<\/section>/s', $html, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $m ) {
			$blocks[ $m[1] ] = $m[0];
		}
	}

	if ( ! $blocks ) {
		return $html;
	}

	$registry_ids = array_keys( Rafah_Sections::sections( $type ) );
	$out          = '';

	foreach ( rafah_theme_section_order( $type ) as $id ) {
		if ( empty( $blocks[ $id ] ) ) {
			continue;
		}

		$block = $blocks[ $id ];
		unset( $blocks[ $id ] );

		$skey   = $type . '_' . str_replace( '-', '_', $id );
		$custom = trim( (string) rafah_opt( 'psec_head_' . $skey, '' ) );

		if ( '' !== $custom ) {
			$block = preg_replace( '/(<h2\b[^>]*>).*?(<\/h2>)/s', '$1' . esc_html( $custom ) . '$2', $block, 1 );
		}

		$out .= $block;
	}

	// Remaining blocks: drop hidden registered sections, keep unknown ones.
	foreach ( $blocks as $bid => $block ) {
		if ( in_array( $bid, $registry_ids, true ) ) {
			continue;
		}
		$out .= $block;
	}

	return $out;
}

/**
 * Generic section renderer dispatcher. Built-in 'project' sections are rendered
 * by the theme; other content types / extensions can render their own sections
 * on the `rafah_render_section` action. Data always comes from Rafah Core.
 *
 * @param string $type       Content type key (e.g. 'project').
 * @param string $id         Section id.
 * @param int    $project_id Post ID (defaults to the current post).
 */
function rafah_theme_render_section( $type, $id, $project_id = 0 ) {
	$project_id = $project_id ? (int) $project_id : (int) get_the_ID();

	if ( 'project' === $type ) {
		rafah_theme_render_project_section( $id, $project_id );
	}

	/**
	 * Render a section for any content type (extensions echo markup here).
	 *
	 * @param string $type       Content type.
	 * @param string $id         Section id.
	 * @param int    $project_id Post ID.
	 */
	do_action( 'rafah_render_section', $type, $id, $project_id );
}

/**
 * Render a single project section by id. One source of truth — used by the
 * single-project template loop AND the Rafah Project Section widget/shortcode.
 * Every section renders nothing when it has no data. Data is read explicitly
 * from $project_id so it works in any context (single page, Elementor, etc.).
 */
function rafah_theme_render_project_section( $id, $project_id ) {
	$keys    = class_exists( 'Rafah_Sections' ) ? Rafah_Sections::sections( 'project' ) : array();
	$heading = rafah_theme_section_heading( 'project', $id, isset( $keys[ $id ] ) ? rafah_text( $keys[ $id ] ) : '' );

	switch ( $id ) {

		case 'hero':
			$h_status = rafah_project_status_label( $project_id );
			$h_type   = rafah_term_name( 'project_type', $project_id );
			$h_city   = rafah_term_name( 'city', $project_id );
			$h_dist   = rafah_term_name( 'district', $project_id );
			$h_sub    = rafah_meta( 'subtitle', $project_id );
			?>
			<section class="rafah-project-hero">
				<div class="rafah-project-hero__bg"><?php rafah_project_cover( $project_id, 'rafah-hero', 'hero', array( 'fetchpriority' => 'high' ) ); ?></div>
				<div class="rafah-project-hero__overlay"></div>
				<div class="rafah-project-hero__inner">
					<?php if ( function_exists( 'rafah_theme_breadcrumbs' ) ) { rafah_theme_breadcrumbs(); } ?>
					<div class="rafah-project-hero__badges">
						<?php if ( rafah_meta( 'featured', $project_id ) ) : ?><span class="rafah-badge rafah-badge--featured"><?php echo esc_html( rafah_text( 'featured' ) ); ?></span><?php endif; ?>
						<?php if ( $h_status ) : ?><span class="rafah-badge rafah-badge--status"><?php echo esc_html( $h_status ); ?></span><?php endif; ?>
						<?php if ( $h_type ) : ?><span class="rafah-badge rafah-badge--status"><?php echo esc_html( $h_type ); ?></span><?php endif; ?>
					</div>
					<h1 class="rafah-project-hero__title"><?php echo esc_html( get_the_title( $project_id ) ); ?></h1>
					<?php if ( $h_sub ) : ?><p class="rafah-project-hero__subtitle"><?php echo esc_html( $h_sub ); ?></p><?php endif; ?>
					<?php if ( $h_city || $h_dist ) : ?><div class="rafah-project-hero__location">📍 <?php echo esc_html( implode( ' · ', array_filter( array( $h_city, $h_dist ) ) ) ); ?></div><?php endif; ?>
				</div>
			</section>
			<?php
			break;

		case 'facts':
			$f_price = rafah_meta( 'price_from', $project_id );
			$f_af    = rafah_meta( 'area_from', $project_id ); $f_at = rafah_meta( 'area_to', $project_id );
			$f_bf    = rafah_meta( 'bedrooms_from', $project_id ); $f_bt = rafah_meta( 'bedrooms_to', $project_id );
			$f_hf    = rafah_meta( 'bathrooms_from', $project_id ); $f_ht = rafah_meta( 'bathrooms_to', $project_id );
			$facts   = array_filter(
				array(
					array( rafah_text( 'starting_from' ), $f_price ? rafah_price( $f_price, $project_id ) : '', true ),
					array( rafah_text( 'area' ), $f_af ? number_format( (float) $f_af ) . ( $f_at ? ' – ' . number_format( (float) $f_at ) : '' ) . ' ' . rafah_text( 'sqm' ) : '', false ),
					array( rafah_text( 'bedrooms' ), $f_bf ? $f_bf . ( $f_bt && $f_bt !== $f_bf ? ' – ' . $f_bt : '' ) : '', false ),
					array( rafah_text( 'bathrooms' ), $f_hf ? $f_hf . ( $f_ht && $f_ht !== $f_hf ? ' – ' . $f_ht : '' ) : '', false ),
					array( rafah_text( 'parking' ), rafah_meta( 'parking', $project_id ), false ),
					array( rafah_text( 'unit_types' ), rafah_meta( 'unit_types', $project_id ), false ),
				),
				fn( $f ) => '' !== (string) $f[1]
			);
			if ( ! $facts ) {
				return;
			}
			?>
			<div class="rafah-facts-bar">
				<?php foreach ( $facts as $fact ) : ?>
					<div class="rafah-fact">
						<div class="rafah-fact__label"><?php echo esc_html( $fact[0] ); ?></div>
						<div class="rafah-fact__value<?php echo $fact[2] ? ' rafah-fact__value--gold' : ''; ?>"><?php echo esc_html( $fact[1] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
			break;

		case 'related':
			$r_city = wp_get_post_terms( $project_id, 'city', array( 'fields' => 'ids' ) );
			$r_q    = new WP_Query( array(
				'post_type'      => 'project',
				'posts_per_page' => 3,
				'post__not_in'   => array( $project_id ),
				'tax_query'      => ! is_wp_error( $r_city ) && $r_city ? array( array( 'taxonomy' => 'city', 'field' => 'term_id', 'terms' => $r_city ) ) : array(), // phpcs:ignore WordPress.DB.SlowDBQuery
			) );
			if ( ! $r_q->have_posts() ) {
				return;
			}
			?>
			<section class="rafah-section" style="padding-top:0">
				<div class="rafah-section-head rafah-section-head--center">
					<h2 class="rafah-section-head__title"><?php echo esc_html( $heading ?: ( function_exists( 'rafah_is_rtl_lang' ) && ! rafah_is_rtl_lang() ? 'Related Projects' : 'مشاريع ذات صلة' ) ); ?></h2>
				</div>
				<div class="rafah-grid">
					<?php
					while ( $r_q->have_posts() ) :
						$r_q->the_post();
						rafah_project_card( get_the_ID() );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</section>
			<?php
			break;

		case 'overview':
			$content = get_post_field( 'post_content', $project_id );
			if ( '' === trim( wp_strip_all_tags( (string) $content ) ) ) {
				return;
			}
			?>
			<section id="overview">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<div class="rafah-content"><?php echo apply_filters( 'the_content', $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			</section>
			<?php
			break;

		case 'project-details':
			$completion = rafah_meta( 'completion', $project_id );
			$details    = array_filter(
				array(
					array( rafah_text( 'developer' ), rafah_meta( 'developer', $project_id ) ),
					array( rafah_text( 'consultant' ), rafah_meta( 'consultant', $project_id ) ),
					array( rafah_text( 'contractor' ), rafah_meta( 'contractor', $project_id ) ),
					array( rafah_text( 'buildings' ), rafah_meta( 'buildings', $project_id ) ),
					array( rafah_text( 'floors' ), rafah_meta( 'floors', $project_id ) ),
					array( rafah_text( 'delivery' ), rafah_meta( 'delivery_date', $project_id ) ),
					array( rafah_text( 'units' ), rafah_meta( 'units_total', $project_id ) ),
				),
				fn( $d ) => '' !== (string) $d[1]
			);
			if ( ! $details && '' === (string) $completion ) {
				return;
			}
			?>
			<section id="project-details">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<?php if ( '' !== (string) $completion ) : ?>
					<div class="rafah-completion">
						<span><?php echo esc_html( rafah_text( 'completion' ) ); ?></span>
						<div class="rafah-completion__track"><div class="rafah-completion__fill" style="width:<?php echo esc_attr( (int) $completion ); ?>%"></div></div>
						<span class="rafah-completion__pct"><?php echo esc_html( (int) $completion ); ?>%</span>
					</div>
				<?php endif; ?>
				<?php if ( $details ) : ?>
					<div class="rafah-details-grid">
						<?php foreach ( $details as $detail ) : ?>
							<div class="rafah-detail">
								<div class="rafah-detail__label"><?php echo esc_html( $detail[0] ); ?></div>
								<div class="rafah-detail__value"><?php echo esc_html( $detail[1] ); ?></div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
			<?php
			break;

		case 'gallery':
			if ( ! class_exists( 'Rafah_Gallery' ) || 'hidden' === Rafah_Gallery::position( $project_id ) || ! Rafah_Gallery::has_items( $project_id ) ) {
				return;
			}
			?>
			<section id="gallery" class="rafah-project-gallery-section">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<?php Rafah_Gallery::grid( $project_id, '3', true ); ?>
			</section>
			<?php
			break;

		case 'video':
			$video = rafah_meta( 'video_url', $project_id );
			if ( ! $video ) {
				return;
			}
			?>
			<section id="video">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<div class="rafah-embed">
					<iframe src="<?php echo esc_url( rafah_theme_embed_url( $video ) ); ?>" title="<?php echo esc_attr( get_the_title( $project_id ) ); ?>" loading="lazy" allowfullscreen></iframe>
				</div>
			</section>
			<?php
			break;

		case 'tour':
			$tour = rafah_meta( 'tour_url', $project_id );
			if ( ! $tour ) {
				return;
			}
			?>
			<section id="tour">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<div class="rafah-embed">
					<iframe src="<?php echo esc_url( $tour ); ?>" title="360" loading="lazy" allowfullscreen></iframe>
				</div>
				<div class="rafah-tour-cta">
					<a class="rafah-btn rafah-btn--ghost" href="<?php echo esc_url( $tour ); ?>" target="_blank" rel="noopener">
						<?php echo esc_html( rafah_text( 'open_tour' ) ); ?>
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 3h7v7M21 3l-9 9M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>
					</a>
				</div>
			</section>
			<?php
			break;

		case 'floor-plans':
			$plans = rafah_meta( 'floor_plans', $project_id );
			if ( ! is_array( $plans ) || ! $plans ) {
				return;
			}
			?>
			<section id="floor-plans">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<?php
				// Reuse the SAME slider component as the Project Gallery (wrapper +
				// arrows + CSS + JS). Cards are unchanged — each is just placed in a
				// carousel slide. No lightbox: floor plans keep their PDF/link cards.
				Rafah_Gallery::carousel_open( '2', false, 'rafah-plans-carousel' );
				foreach ( $plans as $plan ) : ?>
					<li class="rafah-gallery__slide">
						<div class="rafah-plan">
							<?php if ( ! empty( $plan['image'] ) ) : ?>
								<a href="<?php echo esc_url( wp_get_attachment_image_url( (int) $plan['image'], 'full' ) ); ?>" target="_blank" rel="noopener">
									<?php echo wp_get_attachment_image( (int) $plan['image'], 'rafah-card', false, array( 'loading' => 'lazy' ) ); ?>
								</a>
							<?php endif; ?>
							<div class="rafah-plan__body">
								<h3 class="rafah-plan__title"><?php echo esc_html( $plan['title'] ?? '' ); ?></h3>
								<div class="rafah-plan__meta">
									<?php if ( ! empty( $plan['area'] ) ) : ?>
										<span><?php echo esc_html( number_format( (float) $plan['area'] ) . ' ' . rafah_text( 'sqm' ) ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $plan['bedrooms'] ) ) : ?>
										<span><?php echo esc_html( $plan['bedrooms'] . ' ' . rafah_text( 'bedrooms' ) ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $plan['price'] ) ) : ?>
										<span class="rafah-plan__price"><?php echo esc_html( rafah_price( $plan['price'] ) ); ?></span>
									<?php endif; ?>
								</div>
								<?php if ( ! empty( $plan['pdf'] ) ) : ?>
									<a class="rafah-btn rafah-btn--ghost" style="padding:9px 20px;font-size:13px" href="<?php echo esc_url( wp_get_attachment_url( (int) $plan['pdf'] ) ); ?>" target="_blank" rel="noopener">
										<?php echo esc_html( rafah_text( 'download' ) ); ?> PDF
									</a>
								<?php endif; ?>
							</div>
						</div>
					</li>
				<?php endforeach;
				Rafah_Gallery::carousel_close( count( $plans ) > 1 );
				?>
			</section>
			<?php
			break;

		case 'units':
			do_action( 'rafah_before_units_table', $project_id );
			if ( function_exists( 'rafah_units_count' ) && ( rafah_units_count( $project_id )['visible'] ?? 0 ) > 0 ) :
				?>
				<section id="units">
					<h2><?php echo esc_html( $heading ); ?></h2>
					<?php rafah_units_stats_strip( $project_id ); ?>
					<?php rafah_units_table( $project_id ); ?>
				</section>
				<?php
			endif;
			do_action( 'rafah_after_units_table', $project_id );
			break;

		case 'amenities':
			$features  = get_the_terms( $project_id, 'feature' );
			$amenities = get_the_terms( $project_id, 'amenity' );
			if ( ! ( ( $features && ! is_wp_error( $features ) ) || ( $amenities && ! is_wp_error( $amenities ) ) ) ) {
				return;
			}
			?>
			<section id="amenities">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<ul class="rafah-chips">
					<?php foreach ( array_merge( $features && ! is_wp_error( $features ) ? $features : array(), $amenities && ! is_wp_error( $amenities ) ? $amenities : array() ) as $term ) : ?>
						<li><?php echo esc_html( $term->name ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
			<?php
			break;

		case 'nearby':
			$nearby = rafah_meta( 'nearby', $project_id );
			if ( ! is_array( $nearby ) || ! $nearby ) {
				return;
			}
			$groups = array();
			foreach ( $nearby as $place ) {
				$groups[ $place['category'] ?? 'other' ][] = $place;
			}
			?>
			<section id="nearby">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<div class="rafah-nearby">
					<?php foreach ( $groups as $category => $places ) : ?>
						<div class="rafah-nearby__group">
							<h3 class="rafah-nearby__group-title"><?php echo esc_html( rafah_text( $category ) ); ?></h3>
							<ul>
								<?php foreach ( $places as $place ) : ?>
									<li>
										<span><?php echo esc_html( $place['name'] ?? '' ); ?></span>
										<span class="rafah-nearby__distance"><?php echo esc_html( $place['distance'] ?? '' ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
			<?php
			break;

		case 'payment':
			$payments = rafah_meta( 'payment_plans', $project_id );
			if ( ! is_array( $payments ) || ! $payments ) {
				return;
			}
			?>
			<section id="payment">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<div class="rafah-payments">
					<?php foreach ( $payments as $plan ) : ?>
						<div class="rafah-payment">
							<?php if ( '' !== (string) ( $plan['down_payment'] ?? '' ) ) : ?>
								<div class="rafah-payment__down"><?php echo esc_html( (float) $plan['down_payment'] ); ?>%<small><?php echo esc_html( rafah_is_rtl_lang() ? 'دفعة أولى' : 'Down payment' ); ?></small></div>
							<?php endif; ?>
							<div class="rafah-payment__title"><?php echo esc_html( $plan['title'] ?? '' ); ?></div>
							<div class="rafah-payment__desc"><?php echo esc_html( $plan['description'] ?? '' ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php $mortgage = rafah_meta( 'mortgage_info', $project_id ); ?>
				<?php if ( $mortgage ) : ?>
					<p class="rafah-content" style="margin-top:20px"><strong><?php echo esc_html( rafah_text( 'mortgage' ) ); ?>:</strong> <?php echo esc_html( $mortgage ); ?></p>
				<?php endif; ?>
			</section>
			<?php
			break;

		case 'location':
			$lat     = rafah_meta( 'lat', $project_id );
			$lng     = rafah_meta( 'lng', $project_id );
			$map_url = rafah_meta( 'map_url', $project_id );
			$address = rafah_meta( 'address', $project_id );
			if ( ! $lat || ! $lng ) {
				return;
			}
			?>
			<section id="location">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<?php if ( $address ) : ?>
					<p class="rafah-content"><strong><?php echo esc_html( rafah_text( 'address' ) ); ?>:</strong> <?php echo esc_html( $address ); ?></p>
				<?php endif; ?>
				<div class="rafah-embed">
					<iframe src="https://maps.google.com/maps?q=<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lng ); ?>&z=15&output=embed" title="<?php echo esc_attr( rafah_text( 'location' ) ); ?>" loading="lazy"></iframe>
				</div>
				<?php if ( $map_url ) : ?>
					<p style="margin-top:14px"><a class="rafah-btn rafah-btn--ghost" href="<?php echo esc_url( $map_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( rafah_text( 'view_on_map' ) ); ?></a></p>
				<?php endif; ?>
			</section>
			<?php
			break;

		case 'downloads':
			$downloads = is_array( rafah_meta( 'downloads', $project_id ) ) ? rafah_meta( 'downloads', $project_id ) : array();
			$brochure  = rafah_meta( 'brochure', $project_id );
			if ( ! $downloads && ! $brochure ) {
				return;
			}
			?>
			<section id="downloads">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<div class="rafah-downloads">
					<?php if ( $brochure ) : ?>
						<a class="rafah-download" href="<?php echo esc_url( wp_get_attachment_url( (int) $brochure ) ); ?>" target="_blank" rel="noopener">
							<span><?php echo esc_html( rafah_text( 'brochure' ) ); ?></span>
							<span>⬇</span>
						</a>
					<?php endif; ?>
					<?php foreach ( $downloads as $file ) : ?>
						<?php if ( ! empty( $file['file'] ) ) : ?>
							<a class="rafah-download" href="<?php echo esc_url( wp_get_attachment_url( (int) $file['file'] ) ); ?>" target="_blank" rel="noopener">
								<span><?php echo esc_html( $file['title'] ?: basename( (string) get_attached_file( (int) $file['file'] ) ) ); ?></span>
								<span>⬇</span>
							</a>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</section>
			<?php
			break;

		case 'request-info':
			$form = rafah_theme_render_shortcode( rafah_meta( 'form_shortcode', $project_id ) );
			if ( ! $form ) {
				return;
			}
			?>
			<section id="request-info">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<?php echo $form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</section>
			<?php
			break;
	}
}

/**
 * Shortcode: [rafah_project_section id="units" type="project" project=""]
 * Renders any registered section anywhere (Elementor Shortcode/Text element,
 * page content, etc.). Data comes from Rafah Core; presentation from the theme.
 */
add_shortcode( 'rafah_project_section', function ( $atts ) {
	$a = shortcode_atts(
		array( 'id' => '', 'type' => 'project', 'project' => 0 ),
		$atts,
		'rafah_project_section'
	);

	if ( '' === $a['id'] || ! function_exists( 'rafah_theme_render_section' ) ) {
		return '';
	}

	$project_id = $a['project'] ? (int) $a['project'] : (int) get_the_ID();

	ob_start();
	echo '<div class="rafah-project-section rafah-project-main">';
	rafah_theme_render_section( sanitize_key( $a['type'] ), sanitize_title( $a['id'] ), $project_id );
	echo '</div>';
	return ob_get_clean();
} );

/**
 * Agent mini-card for a project — SINGLE SOURCE OF TRUTH.
 * Used by the sidebar renderer AND the "Project · Agent" Elementor widget.
 * Renders nothing when the project has no published agent. Data from Rafah Core.
 */
function rafah_theme_project_agent_card( $project_id ) {
	$agent_id = (int) rafah_meta( 'agent_id', $project_id );
	if ( ! $agent_id || 'publish' !== get_post_status( $agent_id ) ) {
		return;
	}
	?>
	<div class="rafah-agent-mini">
		<?php echo get_the_post_thumbnail( $agent_id, 'thumbnail', array( 'loading' => 'lazy' ) ); ?>
		<div>
			<div class="rafah-agent-mini__name"><a href="<?php echo esc_url( get_permalink( $agent_id ) ); ?>"><?php echo esc_html( get_the_title( $agent_id ) ); ?></a></div>
			<div class="rafah-agent-mini__position"><?php echo esc_html( rafah_meta( 'position', $agent_id ) ); ?></div>
		</div>
	</div>
	<?php
}

/**
 * Project sidebar inner content — price block + "interested" card (agent,
 * WhatsApp, call, book-viewing, share). SINGLE SOURCE OF TRUTH: rendered by
 * single-project.php (inside <aside class="rafah-project-aside">) AND by the
 * "Project · Sidebar Card" Elementor widget. Data from Rafah Core; markup here.
 */
function rafah_theme_project_sidebar( $project_id ) {
	$price_from = rafah_meta( 'price_from', $project_id );
	$price_to   = rafah_meta( 'price_to', $project_id );
	$phone      = rafah_meta( 'phone', $project_id );
	$whatsapp   = rafah_meta( 'whatsapp', $project_id );
	$title      = get_the_title( $project_id );
	?>
	<?php if ( $price_from ) : ?>
		<div class="rafah-aside-price">
			<div class="rafah-aside-price__label"><?php echo esc_html( $price_to ? rafah_text( 'price_range' ) : rafah_text( 'starting_from' ) ); ?></div>
			<div class="rafah-aside-price__value">
				<?php echo esc_html( rafah_price( $price_from, $project_id ) . ( $price_to ? ' – ' . rafah_price( $price_to, $project_id ) : '' ) ); ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="rafah-aside-card">
		<h3 class="rafah-aside-card__title"><?php echo esc_html( rafah_text( 'interested' ) ); ?></h3>
		<p class="rafah-aside-card__sub"><?php echo esc_html( rafah_text( 'interested_sub' ) ); ?></p>

		<?php rafah_theme_project_agent_card( $project_id ); ?>

		<?php if ( $whatsapp ) : ?>
			<a class="rafah-btn rafah-btn--whatsapp" href="<?php echo esc_url( rafah_whatsapp_url( $whatsapp, $title ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( rafah_text( 'whatsapp' ) ); ?></a>
		<?php endif; ?>
		<?php if ( $phone ) : ?>
			<a class="rafah-btn rafah-btn--primary" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( rafah_text( 'call_now' ) ); ?></a>
		<?php endif; ?>
		<a class="rafah-btn rafah-btn--secondary" href="#request-info"><?php echo esc_html( rafah_text( 'book_viewing' ) ); ?></a>

		<div style="margin-top:18px">
			<?php if ( function_exists( 'rafah_theme_share_buttons' ) ) { rafah_theme_share_buttons(); } ?>
		</div>
	</div>
	<?php
}

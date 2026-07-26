<?php
/**
 * Single Project template — premium real estate project page.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$project_id = get_the_ID();
	$subtitle   = rafah_meta( 'subtitle' );
	$city       = rafah_term_name( 'city' );
	$district   = rafah_term_name( 'district' );
	$status     = rafah_project_status_label( $project_id );
	$type       = rafah_term_name( 'project_type' );
	$price_from = rafah_meta( 'price_from' );
	$price_to   = rafah_meta( 'price_to' );
	$completion = rafah_meta( 'completion' );
	$phone      = rafah_meta( 'phone' );
	$whatsapp   = rafah_meta( 'whatsapp' );
	$agent_id   = (int) rafah_meta( 'agent_id' );

	// Elementor bridge (opt-in): if a Single Project template is chosen in the
	// Customizer, render it (Rafah Core still supplies the data via widgets) and
	// skip the built-in layout. Default = 0 → the PHP layout below is unchanged.
	$rafah_sp_tpl  = function_exists( 'rafah_template_bridge_id' ) ? rafah_template_bridge_id( 'single_project' ) : 0;
	$rafah_sp_html = $rafah_sp_tpl ? rafah_render_elementor_template( $rafah_sp_tpl, $project_id ) : false;
	if ( false !== $rafah_sp_html ) {
		echo '<main class="rafah-page rafah-project-main">' . $rafah_sp_html . '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor safe markup.
		continue;
	}
	?>

	<main class="rafah-page">

	<!-- Hero -->
	<section class="rafah-project-hero">
		<div class="rafah-project-hero__bg"><?php rafah_project_cover( $project_id, 'rafah-hero', 'hero', array( 'fetchpriority' => 'high' ) ); ?></div>
		<div class="rafah-project-hero__overlay"></div>
		<div class="rafah-project-hero__inner">
			<?php rafah_theme_breadcrumbs(); ?>
			<div class="rafah-project-hero__badges">
				<?php if ( rafah_meta( 'featured' ) ) : ?>
					<span class="rafah-badge rafah-badge--featured"><?php echo esc_html( rafah_text( 'featured' ) ); ?></span>
				<?php endif; ?>
				<?php if ( $status ) : ?>
					<span class="rafah-badge rafah-badge--status"><?php echo esc_html( $status ); ?></span>
				<?php endif; ?>
				<?php if ( $type ) : ?>
					<span class="rafah-badge rafah-badge--status"><?php echo esc_html( $type ); ?></span>
				<?php endif; ?>
			</div>
			<h1 class="rafah-project-hero__title"><?php the_title(); ?></h1>
			<?php if ( $subtitle ) : ?>
				<p class="rafah-project-hero__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
			<?php if ( $city || $district ) : ?>
				<div class="rafah-project-hero__location">📍 <?php echo esc_html( implode( ' · ', array_filter( array( $city, $district ) ) ) ); ?></div>
			<?php endif; ?>
		</div>
	</section>

	<div class="rafah-container">

		<!-- Buyer summary — essentials only. Every field is optional; empty
		     values are dropped so the bar auto-rebalances with no gaps. -->
		<?php
		$area_from = rafah_meta( 'area_from' );
		$area_to   = rafah_meta( 'area_to' );
		$beds_from = rafah_meta( 'bedrooms_from' );
		$beds_to   = rafah_meta( 'bedrooms_to' );
		$bath_from = rafah_meta( 'bathrooms_from' );
		$bath_to   = rafah_meta( 'bathrooms_to' );

		$summary = array_filter(
			array(
				array( rafah_text( 'starting_from' ), $price_from ? rafah_price( $price_from ) : '', true ),
				array( rafah_text( 'area' ), $area_from ? number_format( (float) $area_from ) . ( $area_to ? ' – ' . number_format( (float) $area_to ) : '' ) . ' ' . rafah_text( 'sqm' ) : '', false ),
				array( rafah_text( 'bedrooms' ), $beds_from ? $beds_from . ( $beds_to && $beds_to !== $beds_from ? ' – ' . $beds_to : '' ) : '', false ),
				array( rafah_text( 'bathrooms' ), $bath_from ? $bath_from . ( $bath_to && $bath_to !== $bath_from ? ' – ' . $bath_to : '' ) : '', false ),
				array( rafah_text( 'parking' ), rafah_meta( 'parking' ), false ),
				array( rafah_text( 'unit_types' ), rafah_meta( 'unit_types' ), false ),
			),
			fn( $f ) => '' !== (string) $f[1]
		);
		?>
		<?php if ( $summary ) : ?>
			<div class="rafah-facts-bar">
				<?php foreach ( $summary as $fact ) : ?>
					<div class="rafah-fact">
						<div class="rafah-fact__label"><?php echo esc_html( $fact[0] ); ?></div>
						<div class="rafah-fact__value<?php echo $fact[2] ? ' rafah-fact__value--gold' : ''; ?>"><?php echo esc_html( $fact[1] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="rafah-project-layout rafah-section">
			<main class="rafah-project-main">
				<?php
				// Render each section by id in the Section Manager order — true
				// PHP/DOM order (correct for SEO, a11y, anchors, JS). The same
				// renderer powers the Rafah Project Section widget/shortcode.
				//
				// hero / facts / related are STRUCTURAL: this template renders them
				// explicitly (hero + facts above, related below), so they must be
				// skipped here or they would render twice. They remain in the Core
				// registry purely so they're available as draggable widgets when a
				// project is rebuilt via the Elementor bridge (which uses its own
				// render path and never reaches this loop).
				$rafah_structural = array( 'hero', 'facts', 'related' );
				foreach ( rafah_theme_section_order( 'project' ) as $rafah_sec ) {
					if ( in_array( $rafah_sec, $rafah_structural, true ) ) {
						continue;
					}
					rafah_theme_render_section( 'project', $rafah_sec, $project_id );
				}
				?>
			</main>

			<!-- Sidebar (single source of truth — same renderer as the Sidebar widget) -->
			<aside class="rafah-project-aside">
				<?php rafah_theme_project_sidebar( $project_id ); ?>
			</aside>
		</div>

		<!-- Related projects -->
		<?php
		$city_terms = wp_get_post_terms( $project_id, 'city', array( 'fields' => 'ids' ) );
		$related    = new WP_Query(
			array(
				'post_type'      => 'project',
				'posts_per_page' => 3,
				'post__not_in'   => array( $project_id ),
				'tax_query'      => ! is_wp_error( $city_terms ) && $city_terms ? array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy' => 'city',
						'field'    => 'term_id',
						'terms'    => $city_terms,
					),
				) : array(),
			)
		);
		?>
		<?php if ( $related->have_posts() ) : ?>
			<section class="rafah-section" style="padding-top:0">
				<div class="rafah-section-head rafah-section-head--center">
					<h2 class="rafah-section-head__title"><?php echo esc_html( function_exists( 'rafah_is_rtl_lang' ) && ! rafah_is_rtl_lang() ? 'Related Projects' : 'مشاريع ذات صلة' ); ?></h2>
				</div>
				<div class="rafah-grid">
					<?php
					while ( $related->have_posts() ) :
						$related->the_post();
						rafah_project_card( get_the_ID() );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</section>
		<?php endif; ?>

	</div><!-- .rafah-container -->
	</main>

	<?php
endwhile;

get_footer();

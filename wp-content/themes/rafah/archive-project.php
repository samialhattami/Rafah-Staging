<?php
/**
 * Projects archive — filterable premium listing.
 * Also used for city/district/status/type taxonomy archives via taxonomy templates.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

// Elementor bridge (opt-in): render a chosen Projects Archive template instead
// of the built-in listing. Only on the post-type archive — taxonomy archives
// keep their PHP design. Default = 0 → the layout below is unchanged.
$rafah_pa_tpl = ( function_exists( 'rafah_template_bridge_id' ) && is_post_type_archive( 'project' ) )
	? rafah_template_bridge_id( 'archive_project' ) : 0;
$rafah_pa_html = $rafah_pa_tpl ? rafah_render_elementor_template( $rafah_pa_tpl, 0 ) : false;
if ( false !== $rafah_pa_html ) {
	echo '<main class="rafah-page">' . $rafah_pa_html . '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor safe markup.
	get_footer();
	return;
}
?>

<main class="rafah-page">

<section class="rafah-archive-hero">
	<div class="rafah-container">
		<h1 class="rafah-archive-hero__title">
			<?php echo is_tax() ? esc_html( single_term_title( '', false ) ) : esc_html( rafah_text( 'projects' ) ); ?>
		</h1>
		<p class="rafah-archive-hero__text">
			<?php
			echo is_tax() && term_description()
				? wp_kses_post( wp_strip_all_tags( term_description() ) )
				: esc_html( rafah_is_rtl_lang()
					? 'اكتشف محفظة مشاريع رفاه السكنية في أرقى أحياء المملكة — جودة بناء استثنائية وتصاميم عصرية.'
					: 'Discover Rafah\'s portfolio of residential projects across Saudi Arabia — exceptional build quality and modern design.' );
			?>
		</p>
	</div>
</section>

<div class="rafah-container rafah-section">

	<!-- Status tabs -->
	<?php if ( function_exists( 'rafah_status_tabs' ) && ! is_tax() ) { rafah_status_tabs(); } ?>

	<!-- Filter bar -->
	<form class="rafah-filter" data-target="#rafah-archive-results" data-per-page="9" role="search">
		<div class="rafah-filter__fields">
			<input type="search" data-filter="s" placeholder="<?php echo esc_attr( rafah_text( 'search_placeholder' ) ); ?>" aria-label="<?php echo esc_attr( rafah_text( 'search' ) ); ?>">

			<?php
				// City + District taxonomy selects.
				foreach ( array(
					'city'     => rafah_text( 'all_cities' ),
					'district' => rafah_text( 'all_districts' ),
				) as $taxonomy => $placeholder ) :
					$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) );
					if ( is_wp_error( $terms ) || ! $terms ) {
						continue;
					}
					$current = is_tax( $taxonomy ) ? get_queried_object()->slug : '';
					?>
					<select data-filter="<?php echo esc_attr( $taxonomy ); ?>" aria-label="<?php echo esc_attr( $placeholder ); ?>">
						<option value=""><?php echo esc_html( $placeholder ); ?></option>
						<?php foreach ( $terms as $term ) : ?>
							<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endforeach; ?>

				<?php
				// Project status — the fixed `_rafah_status` enum. The status tabs
				// above drive this select (and vice-versa) via data-filter="status".
				$rafah_status_current = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				if ( ! array_key_exists( $rafah_status_current, rafah_project_status_options() ) ) {
					$rafah_status_current = '';
				}
				?>
				<select data-filter="status" aria-label="<?php echo esc_attr( rafah_text( 'all_statuses' ) ); ?>">
					<option value=""><?php echo esc_html( rafah_text( 'all_statuses' ) ); ?></option>
					<?php foreach ( rafah_project_status_options() as $rafah_skey => $rafah_slabel ) : ?>
						<option value="<?php echo esc_attr( $rafah_skey ); ?>" <?php selected( $rafah_status_current, $rafah_skey ); ?>><?php echo esc_html( $rafah_slabel ); ?></option>
					<?php endforeach; ?>
				</select>

				<?php
				// Project type taxonomy select.
				$rafah_type_terms = get_terms( array( 'taxonomy' => 'project_type', 'hide_empty' => true ) );
				if ( ! is_wp_error( $rafah_type_terms ) && $rafah_type_terms ) :
					$rafah_type_current = is_tax( 'project_type' ) ? get_queried_object()->slug : '';
					?>
					<select data-filter="project_type" aria-label="<?php echo esc_attr( rafah_text( 'all_types' ) ); ?>">
						<option value=""><?php echo esc_html( rafah_text( 'all_types' ) ); ?></option>
						<?php foreach ( $rafah_type_terms as $term ) : ?>
							<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $rafah_type_current, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>

			<select data-filter="bedrooms" aria-label="<?php echo esc_attr( rafah_text( 'bedrooms' ) ); ?>">
				<option value=""><?php echo esc_html( rafah_text( 'any_bedrooms' ) ); ?></option>
				<?php for ( $i = 1; $i <= 7; $i++ ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i . '+' ); ?></option>
				<?php endfor; ?>
			</select>

			<input type="number" data-filter="max_price" min="0" step="50000" placeholder="<?php echo esc_attr( rafah_text( 'max_price' ) ); ?>" aria-label="<?php echo esc_attr( rafah_text( 'max_price' ) ); ?>">

			<select data-filter="sort" aria-label="Sort">
				<option value="newest"><?php echo esc_html( rafah_text( 'sort_newest' ) ); ?></option>
				<option value="price_asc"><?php echo esc_html( rafah_text( 'sort_price_asc' ) ); ?></option>
				<option value="price_desc"><?php echo esc_html( rafah_text( 'sort_price_desc' ) ); ?></option>
			</select>

			<div class="rafah-filter__actions">
				<button type="button" class="rafah-filter__reset"><?php echo esc_html( rafah_text( 'reset_filters' ) ); ?></button>
			</div>
		</div>
		<p class="rafah-filter__count" data-label="<?php echo esc_attr( rafah_is_rtl_lang() ? '%d مشروع' : '%d projects' ); ?>"></p>
	</form>

	<!-- Results -->
	<div id="rafah-archive-results" class="rafah-grid rafah-results">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) {
				the_post();
				rafah_project_card( get_the_ID() );
			}
			?>
		<?php else : ?>
			<div class="rafah-no-results"><?php echo esc_html( rafah_text( 'no_results' ) ); ?></div>
		<?php endif; ?>
	</div>

	<div class="rafah-load-more-wrap" <?php echo $wp_query->max_num_pages > 1 ? '' : 'style="display:none"'; ?>>
		<button type="button" class="rafah-btn rafah-btn--ghost"><?php echo esc_html( rafah_text( 'load_more' ) ); ?></button>
	</div>
</div>

</main>

<?php
get_footer();

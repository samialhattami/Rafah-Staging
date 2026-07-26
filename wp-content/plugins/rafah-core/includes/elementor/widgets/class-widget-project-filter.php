<?php
/**
 * Rafah — Project Filter widget: filter bar + AJAX results grid + load more.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_Project_Filter extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_project_filter';
	}

	public function get_title() {
		return __( 'Rafah Project Filter', 'rafah' );
	}

	public function get_icon() {
		return 'eicon-search-results';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'settings', array( 'label' => __( 'Settings', 'rafah' ) ) );

		$this->add_control( 'per_page', array(
			'label'   => __( 'Projects per Page', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 9,
			'min'     => 3,
			'max'     => 24,
		) );

		foreach ( array(
			'show_search'   => __( 'Show Search Field', 'rafah' ),
			'show_city'     => __( 'Show City Filter', 'rafah' ),
			'show_district' => __( 'Show District Filter', 'rafah' ),
			'show_status'   => __( 'Show Status Filter', 'rafah' ),
			'show_type'     => __( 'Show Type Filter', 'rafah' ),
			'show_bedrooms' => __( 'Show Bedrooms Filter', 'rafah' ),
			'show_price'    => __( 'Show Max Price Filter', 'rafah' ),
			'show_sort'     => __( 'Show Sorting', 'rafah' ),
		) as $key => $label ) {
			$this->add_control( $key, array(
				'label'        => $label,
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			) );
		}

		$this->end_controls_section();

		Rafah_Style_Controls::box( $this, 'filter', __( 'Filter Bar', 'rafah' ), '.rafah-filter' );
		Rafah_Style_Controls::button( $this, '.rafah-btn' );
		Rafah_Style_Controls::grid( $this, '.rafah-results', 'resgrid' );

	}

	private function tax_select( $taxonomy, $filter_key, $placeholder ) {
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) );

		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}
		?>
		<select data-filter="<?php echo esc_attr( $filter_key ); ?>" aria-label="<?php echo esc_attr( $placeholder ); ?>">
			<option value=""><?php echo esc_html( $placeholder ); ?></option>
			<?php foreach ( $terms as $term ) : ?>
				<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Project status select — driven by the fixed `_rafah_status` enum, not a
	 * taxonomy. Sends the `status` filter key (a canonical status key).
	 *
	 * @param string $placeholder "All Statuses" label.
	 */
	private function status_select( $placeholder ) {
		?>
		<select data-filter="status" aria-label="<?php echo esc_attr( $placeholder ); ?>">
			<option value=""><?php echo esc_html( $placeholder ); ?></option>
			<?php foreach ( rafah_project_status_options() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	protected function render() {
		$s  = $this->get_settings_for_display();
		$id = 'rafah-results-' . $this->get_id();

		// Initial (non-JS / first paint) results.
		$query = new WP_Query( Rafah_Ajax::build_query_args( array( 'per_page' => (int) $s['per_page'] ) ) );
		?>
		<form class="rafah-filter" data-target="#<?php echo esc_attr( $id ); ?>" data-per-page="<?php echo esc_attr( (int) $s['per_page'] ); ?>" role="search">
			<div class="rafah-filter__fields">
				<?php if ( 'yes' === $s['show_search'] ) : ?>
					<input type="search" data-filter="s" placeholder="<?php echo esc_attr( rafah_text( 'search_placeholder' ) ); ?>" aria-label="<?php echo esc_attr( rafah_text( 'search' ) ); ?>">
				<?php endif; ?>

				<?php if ( 'yes' === $s['show_city'] ) {
					$this->tax_select( 'city', 'city', rafah_text( 'all_cities' ) );
				} ?>

				<?php if ( 'yes' === $s['show_district'] ) {
					$this->tax_select( 'district', 'district', rafah_text( 'all_districts' ) );
				} ?>

				<?php if ( 'yes' === $s['show_status'] ) {
					$this->status_select( rafah_text( 'all_statuses' ) );
				} ?>

				<?php if ( 'yes' === $s['show_type'] ) {
					$this->tax_select( 'project_type', 'project_type', rafah_text( 'all_types' ) );
				} ?>

				<?php if ( 'yes' === $s['show_bedrooms'] ) : ?>
					<select data-filter="bedrooms" aria-label="<?php echo esc_attr( rafah_text( 'bedrooms' ) ); ?>">
						<option value=""><?php echo esc_html( rafah_text( 'any_bedrooms' ) ); ?></option>
						<?php for ( $i = 1; $i <= 7; $i++ ) : ?>
							<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i . '+' ); ?></option>
						<?php endfor; ?>
					</select>
				<?php endif; ?>

				<?php if ( 'yes' === $s['show_price'] ) : ?>
					<input type="number" data-filter="max_price" min="0" step="50000" placeholder="<?php echo esc_attr( rafah_text( 'max_price' ) ); ?>" aria-label="<?php echo esc_attr( rafah_text( 'max_price' ) ); ?>">
				<?php endif; ?>

				<?php if ( 'yes' === $s['show_sort'] ) : ?>
					<select data-filter="sort" aria-label="<?php esc_attr_e( 'Sort', 'rafah' ); ?>">
						<option value="newest"><?php echo esc_html( rafah_text( 'sort_newest' ) ); ?></option>
						<option value="price_asc"><?php echo esc_html( rafah_text( 'sort_price_asc' ) ); ?></option>
						<option value="price_desc"><?php echo esc_html( rafah_text( 'sort_price_desc' ) ); ?></option>
					</select>
				<?php endif; ?>

				<div class="rafah-filter__actions">
					<button type="button" class="rafah-filter__reset"><?php echo esc_html( rafah_text( 'reset_filters' ) ); ?></button>
				</div>
			</div>
			<p class="rafah-filter__count" data-label="<?php echo esc_attr( rafah_is_rtl_lang() ? '%d مشروع' : '%d projects' ); ?>"></p>
		</form>

		<div class="rafah-results-wrap">
			<div id="<?php echo esc_attr( $id ); ?>" class="rafah-grid rafah-results">
				<?php
				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
						rafah_project_card( get_the_ID() );
					}
					wp_reset_postdata();
				} else {
					echo '<div class="rafah-no-results">' . esc_html( rafah_text( 'no_results' ) ) . '</div>';
				}
				?>
			</div>
			<div class="rafah-load-more-wrap" <?php echo $query->max_num_pages > 1 ? '' : 'style="display:none"'; ?>>
				<button type="button" class="rafah-btn rafah-btn--ghost"><?php echo esc_html( rafah_text( 'load_more' ) ); ?></button>
			</div>
		</div>
		<?php
	}
}

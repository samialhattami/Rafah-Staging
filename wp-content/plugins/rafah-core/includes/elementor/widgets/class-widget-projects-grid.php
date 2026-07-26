<?php
/**
 * Rafah — Projects Grid widget (dynamic, works with Elementor Free).
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_Projects_Grid extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_projects_grid';
	}

	public function get_title() {
		return __( 'Rafah Projects Grid', 'rafah' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'query', array( 'label' => __( 'Query', 'rafah' ) ) );

		$this->add_control( 'count', array(
			'label'   => __( 'Number of Projects', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 6,
			'min'     => 1,
			'max'     => 24,
		) );

		$this->add_control( 'status', array(
			'label'   => __( 'Project Status', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => array( '' => __( 'All', 'rafah' ) ) + rafah_project_status_options(),
			'default' => '',
		) );

		$this->add_control( 'city', array(
			'label'   => __( 'City', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => $this->term_options( 'city' ),
			'default' => '',
		) );

		$this->add_control( 'featured_only', array(
			'label'        => __( 'Featured Only', 'rafah' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'heading_section', array( 'label' => __( 'Section Heading', 'rafah' ) ) );

		$this->add_control( 'eyebrow', array(
			'label'   => __( 'Eyebrow', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'مشاريعنا',
		) );

		$this->add_control( 'title', array(
			'label'   => __( 'Title', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'اكتشف مشاريع رفاه',
		) );

		$this->add_control( 'show_all_link', array(
			'label'        => __( 'Show "All Projects" Button', 'rafah' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->end_controls_section();

		// ------------------------------------------------------------ Card.
		// Per-instance overrides for the shared card renderer. Empty = inherit
		// the Theme Customizer > Project Cards global. Data is never affected.
		$this->start_controls_section( 'card', array( 'label' => __( 'Card', 'rafah' ) ) );

		$this->add_control( 'card_layout', array(
			'label'   => __( 'Card Layout', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => '',
			'options' => array(
				''            => __( 'Default (Theme setting)', 'rafah' ),
				'classic'     => __( 'Classic (image top)', 'rafah' ),
				'image-left'  => __( 'Image Left + Content Right', 'rafah' ),
				'image-right' => __( 'Image Right + Content Left', 'rafah' ),
				'overlap'     => __( 'Premium Overlap', 'rafah' ),
			),
		) );

		$this->add_control( 'card_button_text', array(
			'label'       => __( 'Button Text', 'rafah' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => __( 'Default (Theme / site language)', 'rafah' ),
		) );

		$this->add_control( 'card_visibility_heading', array(
			'label'     => __( 'Elements', 'rafah' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$rafah_vis = array(
			'card_show_city'     => __( 'City / Location', 'rafah' ),
			'card_show_price'    => __( 'Starting Price', 'rafah' ),
			'card_show_bedrooms' => __( 'Bedrooms', 'rafah' ),
			'card_show_area'     => __( 'Area', 'rafah' ),
			'card_show_units'    => __( 'Number of Units', 'rafah' ),
			'card_show_status'   => __( 'Status Overlay', 'rafah' ),
			'card_show_featured' => __( 'Featured Badge', 'rafah' ),
			'card_show_divider'  => __( 'Divider Line', 'rafah' ),
		);
		foreach ( $rafah_vis as $rafah_vkey => $rafah_vlabel ) {
			$this->add_control( $rafah_vkey, array(
				'label'   => $rafah_vlabel,
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''     => __( 'Default', 'rafah' ),
					'show' => __( 'Show', 'rafah' ),
					'hide' => __( 'Hide', 'rafah' ),
				),
			) );
		}

		$this->end_controls_section();

		Rafah_Style_Controls::heading( $this );
		Rafah_Style_Controls::box( $this, 'card', __( 'Card', 'rafah' ), '.rafah-card', array( 'pad_sel' => '.rafah-card__body', 'hover' => true ) );
		Rafah_Style_Controls::text( $this, 'ctitle', __( 'Card Title', 'rafah' ), '.rafah-card__title a' );
		Rafah_Style_Controls::text( $this, 'cloc', __( 'Card Location', 'rafah' ), '.rafah-card__location' );
		Rafah_Style_Controls::text( $this, 'cprice', __( 'Card Price', 'rafah' ), '.rafah-card__price-value' );
		Rafah_Style_Controls::text( $this, 'cmeta', __( 'Card Meta', 'rafah' ), '.rafah-card__meta' );
		Rafah_Style_Controls::text( $this, 'cnote', __( 'Card Note', 'rafah' ), '.rafah-card__note' );
		Rafah_Style_Controls::badges( $this );
		Rafah_Style_Controls::button( $this, '.rafah-btn' );
		Rafah_Style_Controls::image( $this, '.rafah-card__media' );
		Rafah_Style_Controls::grid( $this, '.rafah-grid' );

	}

	/**
	 * Build per-instance card overrides from this widget's settings.
	 *
	 * @param array $s Settings for display.
	 * @return array
	 */
	private function card_args( $s ) {
		$args = array(
			'layout'      => isset( $s['card_layout'] ) ? $s['card_layout'] : '',
			'button_text' => isset( $s['card_button_text'] ) ? $s['card_button_text'] : '',
		);
		foreach ( array( 'city', 'price', 'bedrooms', 'area', 'units', 'status', 'featured', 'divider' ) as $el ) {
			$key                   = 'card_show_' . $el;
			$args[ 'show_' . $el ] = isset( $s[ $key ] ) ? $s[ $key ] : '';
		}

		return $args;
	}

	private function term_options( $taxonomy ) {
		$options = array( '' => __( 'All', 'rafah' ) );

		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->slug ] = $term->name;
			}
		}

		return $options;
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$params = array(
			'per_page' => (int) $s['count'],
			'sort'     => 'newest',
		);

		if ( ! empty( $s['status'] ) ) {
			$params['status'] = $s['status'];
		}
		if ( ! empty( $s['city'] ) ) {
			$params['city'] = $s['city'];
		}
		if ( 'yes' === $s['featured_only'] ) {
			$params['featured'] = 1;
		}

		$card_args = $this->card_args( $s );

		$query = new WP_Query( Rafah_Ajax::build_query_args( $params ) );
		?>
		<div class="rafah-projects-section">
			<?php if ( $s['title'] || $s['eyebrow'] ) : ?>
				<div class="rafah-section-head rafah-section-head--center">
					<?php if ( $s['eyebrow'] ) : ?>
						<span class="rafah-eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></span>
					<?php endif; ?>
					<?php if ( $s['title'] ) : ?>
						<h2 class="rafah-section-head__title"><?php echo esc_html( $s['title'] ); ?></h2>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="rafah-grid">
				<?php
				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
						rafah_project_card( get_the_ID(), $card_args );
					}
					wp_reset_postdata();
				} else {
					echo '<div class="rafah-no-results">' . esc_html( rafah_text( 'no_results' ) ) . '</div>';
				}
				?>
			</div>

			<?php if ( 'yes' === $s['show_all_link'] ) : ?>
				<?php
				// Deep-link to the projects page with this widget's status
				// filter already applied (tabs + results arrive pre-filtered).
				$archive_url = get_post_type_archive_link( 'project' );
				if ( ! empty( $s['status'] ) ) {
					$archive_url = add_query_arg( 'status', rawurlencode( $s['status'] ), $archive_url );
				}
				?>
				<div class="rafah-load-more-wrap">
					<a class="rafah-btn rafah-btn--ghost" href="<?php echo esc_url( $archive_url ); ?>">
						<?php echo esc_html( rafah_text( 'view_all_projects' ) ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

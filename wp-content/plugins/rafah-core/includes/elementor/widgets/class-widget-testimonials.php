<?php
/**
 * Rafah — Testimonials widget (pulls from the Testimonials CPT).
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_Testimonials extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_testimonials';
	}

	public function get_title() {
		return __( 'Rafah Testimonials', 'rafah' );
	}

	public function get_icon() {
		return 'eicon-testimonial-carousel';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'settings', array( 'label' => __( 'Settings', 'rafah' ) ) );

		$this->add_control( 'count', array(
			'label'   => __( 'Number of Testimonials', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 3,
			'min'     => 1,
			'max'     => 12,
		) );

		$this->add_control( 'eyebrow', array(
			'label'   => __( 'Eyebrow', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'آراء عملائنا',
		) );

		$this->add_control( 'title', array(
			'label'   => __( 'Title', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'ثقة تمتد لسنوات',
		) );

		$this->end_controls_section();

		Rafah_Style_Controls::heading( $this );
		Rafah_Style_Controls::box( $this, 'card', __( 'Testimonial', 'rafah' ), '.rafah-testimonial', array( 'hover' => true ) );
		Rafah_Style_Controls::text( $this, 'ttext', __( 'Quote Text', 'rafah' ), '.rafah-testimonial__text' );
		Rafah_Style_Controls::text( $this, 'tname', __( 'Author Name', 'rafah' ), '.rafah-testimonial__name' );
		Rafah_Style_Controls::text( $this, 'trole', __( 'Author Role', 'rafah' ), '.rafah-testimonial__role' );
		Rafah_Style_Controls::text( $this, 'tstars', __( 'Stars', 'rafah' ), '.rafah-testimonial__stars' );
		Rafah_Style_Controls::image( $this, '.rafah-testimonial__avatar' );
		Rafah_Style_Controls::grid( $this, '.rafah-testimonials' );

	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$query = new WP_Query(
			array(
				'post_type'      => 'testimonial',
				'post_status'    => 'publish',
				'posts_per_page' => (int) $s['count'],
			)
		);

		if ( ! $query->have_posts() ) {
			return;
		}
		?>
		<div class="rafah-testimonials-section">
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

			<div class="rafah-testimonials">
				<?php
				while ( $query->have_posts() ) {
					$query->the_post();
					$rating = (int) ( rafah_meta( 'rating' ) ?: 5 );
					?>
					<figure class="rafah-testimonial rafah-fade-up">
						<span class="rafah-testimonial__quote-mark" aria-hidden="true">“</span>
						<span class="rafah-testimonial__stars" aria-label="<?php echo esc_attr( $rating . '/5' ); ?>"><?php echo esc_html( str_repeat( '★', $rating ) ); ?></span>
						<blockquote class="rafah-testimonial__text"><?php echo esc_html( wp_strip_all_tags( get_the_content() ) ); ?></blockquote>
						<figcaption class="rafah-testimonial__author">
							<?php if ( has_post_thumbnail() ) : ?>
								<span class="rafah-testimonial__avatar"><?php the_post_thumbnail( 'thumbnail', array( 'loading' => 'lazy' ) ); ?></span>
							<?php endif; ?>
							<span>
								<span class="rafah-testimonial__name"><?php the_title(); ?></span><br>
								<span class="rafah-testimonial__role"><?php echo esc_html( rafah_meta( 'client_role' ) ); ?></span>
							</span>
						</figcaption>
					</figure>
					<?php
				}
				wp_reset_postdata();
				?>
			</div>
		</div>
		<?php
	}
}

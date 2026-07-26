<?php
/**
 * Rafah — Agents Grid widget.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_Agents_Grid extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_agents_grid';
	}

	public function get_title() {
		return __( 'Rafah Agents', 'rafah' );
	}

	public function get_icon() {
		return 'eicon-person';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'settings', array( 'label' => __( 'Settings', 'rafah' ) ) );

		$this->add_control( 'count', array(
			'label'   => __( 'Number of Agents', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 4,
			'min'     => 1,
			'max'     => 20,
		) );

		$this->add_control( 'eyebrow', array(
			'label'   => __( 'Eyebrow', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'فريقنا',
		) );

		$this->add_control( 'title', array(
			'label'   => __( 'Title', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'مستشارونا العقاريون',
		) );

		$this->end_controls_section();

		Rafah_Style_Controls::heading( $this );
		Rafah_Style_Controls::box( $this, 'card', __( 'Agent Card', 'rafah' ), '.rafah-agent-card', array( 'pad_sel' => '.rafah-agent-card__body', 'hover' => true, 'align' => true ) );
		Rafah_Style_Controls::text( $this, 'aname', __( 'Name', 'rafah' ), '.rafah-agent-card__name' );
		Rafah_Style_Controls::text( $this, 'apos', __( 'Position', 'rafah' ), '.rafah-agent-card__position' );
		Rafah_Style_Controls::image( $this, '.rafah-agent-card__photo' );
		Rafah_Style_Controls::button( $this, '.rafah-btn' );
		Rafah_Style_Controls::grid( $this, '.rafah-grid' );

	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$agents = new WP_Query(
			array(
				'post_type'      => 'agent',
				'post_status'    => 'publish',
				'posts_per_page' => (int) $s['count'],
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			)
		);

		if ( ! $agents->have_posts() ) {
			return;
		}
		?>
		<div class="rafah-agents-section">
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

			<div class="rafah-grid rafah-grid--agents">
				<?php
				while ( $agents->have_posts() ) {
					$agents->the_post();
					rafah_agent_card( get_the_ID() );
				}
				wp_reset_postdata();
				?>
			</div>
		</div>
		<?php
	}
}

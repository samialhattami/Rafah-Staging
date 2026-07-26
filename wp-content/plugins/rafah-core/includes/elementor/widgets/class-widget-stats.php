<?php
/**
 * Rafah — Animated Statistics widget.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_Stats extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_stats';
	}

	public function get_title() {
		return __( 'Rafah Statistics', 'rafah' );
	}

	public function get_icon() {
		return 'eicon-counter';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Statistics', 'rafah' ) ) );

		$repeater = new \Elementor\Repeater();

		$repeater->add_control( 'value', array(
			'label'   => __( 'Number', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 25,
		) );

		$repeater->add_control( 'suffix', array(
			'label'   => __( 'Suffix', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '+',
		) );

		$repeater->add_control( 'label', array(
			'label'   => __( 'Label', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'مشروعاً منجزاً',
		) );

		$this->add_control( 'stats', array(
			'label'       => __( 'Items', 'rafah' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'title_field' => '{{{ value }}}{{{ suffix }}} — {{{ label }}}',
			'default'     => array(
				array( 'value' => 25, 'suffix' => '+', 'label' => 'مشروعاً منجزاً' ),
				array( 'value' => 3500, 'suffix' => '+', 'label' => 'وحدة سكنية' ),
				array( 'value' => 15, 'suffix' => '+', 'label' => 'عاماً من الخبرة' ),
				array( 'value' => 98, 'suffix' => '%', 'label' => 'رضا العملاء' ),
			),
		) );

		$this->end_controls_section();

		Rafah_Style_Controls::box( $this, 'stat', __( 'Stat Box', 'rafah' ), '.rafah-stat', array( 'hover' => true, 'align' => true ) );
		Rafah_Style_Controls::text( $this, 'sval', __( 'Value', 'rafah' ), '.rafah-stat__value' );
		Rafah_Style_Controls::text( $this, 'ssuf', __( 'Suffix', 'rafah' ), '.rafah-stat__suffix' );
		Rafah_Style_Controls::text( $this, 'slab', __( 'Label', 'rafah' ), '.rafah-stat__label' );
		Rafah_Style_Controls::grid( $this, '.rafah-stats' );

	}

	protected function render() {
		$s = $this->get_settings_for_display();

		// Skip rows with no number and no label; hide the whole section when
		// none remain (no empty grid).
		$stats = array_filter(
			(array) ( $s['stats'] ?? array() ),
			static function ( $stat ) {
				return '' !== trim( (string) ( $stat['value'] ?? '' ) ) || '' !== trim( (string) ( $stat['label'] ?? '' ) );
			}
		);

		if ( ! $stats ) {
			return;
		}
		?>
		<div class="rafah-stats">
			<?php foreach ( $stats as $stat ) : ?>
				<div class="rafah-stat rafah-fade-up">
					<div class="rafah-stat__value">
						<span data-count="<?php echo esc_attr( $stat['value'] ); ?>">0</span>
						<?php if ( ! empty( $stat['suffix'] ) ) : ?>
							<span class="rafah-stat__suffix"><?php echo esc_html( $stat['suffix'] ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $stat['label'] ) ) : ?>
						<div class="rafah-stat__label"><?php echo esc_html( $stat['label'] ); ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}

<?php
/**
 * Rafah — FAQ widget with automatic FAQPage schema.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_FAQ extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_faq';
	}

	public function get_title() {
		return __( 'Rafah FAQ', 'rafah' );
	}

	public function get_icon() {
		return 'eicon-help-o';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Questions', 'rafah' ) ) );

		$repeater = new \Elementor\Repeater();

		$repeater->add_control( 'question', array(
			'label'   => __( 'Question', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'كيف يمكنني حجز وحدة في أحد المشاريع؟',
		) );

		$repeater->add_control( 'answer', array(
			'label'   => __( 'Answer', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => 'يمكنك حجز وحدتك من خلال التواصل مع فريق المبيعات عبر الواتساب أو تعبئة نموذج طلب المعلومات في صفحة المشروع.',
		) );

		$this->add_control( 'faqs', array(
			'label'       => __( 'FAQ Items', 'rafah' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'title_field' => '{{{ question }}}',
			'default'     => array(
				array(),
			),
		) );

		$this->add_control( 'enable_schema', array(
			'label'        => __( 'Output FAQ Schema (SEO)', 'rafah' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->end_controls_section();

		Rafah_Style_Controls::box( $this, 'faqitem', __( 'FAQ Item', 'rafah' ), '.rafah-faq__item' );
		Rafah_Style_Controls::text( $this, 'faqq', __( 'Question', 'rafah' ), '.rafah-faq__question' );
		Rafah_Style_Controls::text( $this, 'faqa', __( 'Answer', 'rafah' ), '.rafah-faq__answer' );

	}

	protected function render() {
		$s = $this->get_settings_for_display();

		// Skip rows with no question; hide the section when none remain.
		$faqs = array_values( array_filter(
			(array) ( $s['faqs'] ?? array() ),
			static function ( $faq ) {
				return '' !== trim( (string) ( $faq['question'] ?? '' ) );
			}
		) );

		if ( ! $faqs ) {
			return;
		}
		?>
		<div class="rafah-faq">
			<?php foreach ( $faqs as $i => $faq ) : ?>
				<details class="rafah-faq__item rafah-fade-up" <?php echo 0 === $i ? 'open' : ''; ?>>
					<summary class="rafah-faq__question"><?php echo esc_html( $faq['question'] ); ?></summary>
					<div class="rafah-faq__answer"><?php echo wp_kses_post( wpautop( $faq['answer'] ) ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
		<?php
		if ( 'yes' === $s['enable_schema'] && ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$entities = array();

			foreach ( $faqs as $faq ) {
				$entities[] = array(
					'@type'          => 'Question',
					'name'           => wp_strip_all_tags( $faq['question'] ),
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => wp_strip_all_tags( $faq['answer'] ),
					),
				);
			}

			printf(
				'<script type="application/ld+json">%s</script>',
				wp_json_encode(
					array(
						'@context'   => 'https://schema.org',
						'@type'      => 'FAQPage',
						'mainEntity' => $entities,
					),
					JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
				)
			);
		}
	}
}

<?php
/**
 * Rafah — CTA Section widget.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Widget_CTA extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rafah_cta';
	}

	public function get_title() {
		return __( 'Rafah CTA', 'rafah' );
	}

	public function get_icon() {
		return 'eicon-call-to-action';
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'rafah' ) ) );

		$this->add_control( 'bg_image', array(
			'label' => __( 'Background Image (optional)', 'rafah' ),
			'type'  => \Elementor\Controls_Manager::MEDIA,
		) );

		$this->add_control( 'title', array(
			'label'   => __( 'Title', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'ابدأ رحلتك نحو منزل أحلامك',
		) );

		$this->add_control( 'text', array(
			'label'   => __( 'Text', 'rafah' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => 'تواصل مع فريقنا اليوم واحصل على استشارة مجانية حول أفضل الفرص السكنية والاستثمارية.',
		) );

		// Buttons / contact actions — native repeater (add unlimited / delete /
		// duplicate / drag / collapse). Use the WhatsApp variant and put the
		// phone number in the Link field. Empty = no actions row rendered.
		Rafah_Repeaters::buttons( $this, 'buttons', __( 'Buttons & Actions', 'rafah' ) );

		$this->end_controls_section();

		Rafah_Style_Controls::box( $this, 'cta', __( 'CTA Box', 'rafah' ), '.rafah-cta', array( 'align' => true ) );
		Rafah_Style_Controls::overlay( $this, '.rafah-cta__overlay' );
		Rafah_Style_Controls::text( $this, 'ctatitle', __( 'Title', 'rafah' ), '.rafah-cta__title', true );
		Rafah_Style_Controls::text( $this, 'ctatext', __( 'Text', 'rafah' ), '.rafah-cta__text', true );
		Rafah_Style_Controls::button( $this, '.rafah-btn--primary', 'btnp' );
		Rafah_Style_Controls::button( $this, '.rafah-btn--whatsapp', 'btnw' );

	}

	protected function render() {
		$s = $this->get_settings_for_display();

		// Buttons from the repeater; fall back to the legacy button + WhatsApp
		// fields for CTAs saved before the repeater existed.
		$buttons = (array) ( $s['buttons'] ?? array() );
		if ( ! array_filter( array_column( $buttons, 'text' ) ) ) {
			$buttons = array();
			if ( ! empty( $s['btn_text'] ) ) {
				$buttons[] = array( 'text' => $s['btn_text'], 'link' => $s['btn_link'] ?? array(), 'variant' => 'primary' );
			}
			if ( ! empty( $s['whatsapp'] ) ) {
				$buttons[] = array( 'text' => rafah_text( 'whatsapp' ), 'link' => array( 'url' => $s['whatsapp'] ), 'variant' => 'whatsapp' );
			}
		}
		?>
		<section class="rafah-cta">
			<?php if ( ! empty( $s['bg_image']['url'] ) ) : ?>
				<div class="rafah-cta__bg"><img src="<?php echo esc_url( $s['bg_image']['url'] ); ?>" alt="" loading="lazy"></div>
			<?php endif; ?>
			<div class="rafah-cta__overlay"></div>
			<?php if ( ! empty( $s['title'] ) ) : ?>
				<h2 class="rafah-cta__title"><?php echo esc_html( $s['title'] ); ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $s['text'] ) ) : ?>
				<p class="rafah-cta__text"><?php echo esc_html( $s['text'] ); ?></p>
			<?php endif; ?>
			<?php
			// Empty repeater → helper returns '' → no actions row, no empty gap.
			echo rafah_buttons_html( $buttons, 'rafah-cta__actions' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside helper.
			?>
		</section>
		<?php
	}
}

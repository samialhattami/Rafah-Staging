<?php
/**
 * Rafah Footer module — Back-to-Top button (ISOLATED).
 *
 * Rendered on wp_footer, independent of the footer. Sizing / colours / shadow /
 * radius / offset / icon-size / visibility come from dynamic-css.php; this file
 * emits the markup + the chosen icon (built-in SVG, a Font Awesome class, or an
 * uploaded SVG/PNG). Fully controlled from Customizer → Footer.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

// Load Font Awesome only when the button actually uses an FA icon.
add_action( 'wp_enqueue_scripts', function () {
	if ( rafah_opt( 'back_to_top', true )
		&& 'fontawesome' === rafah_opt( 'btt_icon_source', 'preset' )
		&& rafah_opt( 'btt_icon_fa' ) ) {
		wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', array(), '6.5.2' );
	}
} );

add_action( 'wp_footer', function () {
	if ( ! rafah_opt( 'back_to_top', true ) ) {
		return;
	}
	if ( rafah_opt( 'btt_hide_desktop' ) && rafah_opt( 'btt_hide_mobile' ) ) {
		return; // hidden everywhere
	}

	$source = rafah_opt( 'btt_icon_source', 'preset' );

	if ( 'image' === $source && rafah_opt( 'btt_icon_image' ) ) {
		$icon_html = '<img class="rafah-backtotop__img" src="' . esc_url( rafah_opt( 'btt_icon_image' ) ) . '" alt="" aria-hidden="true">';
	} elseif ( 'fontawesome' === $source && rafah_opt( 'btt_icon_fa' ) ) {
		$icon_html = '<i class="' . esc_attr( rafah_opt( 'btt_icon_fa' ) ) . '" aria-hidden="true"></i>';
	} else {
		$icons = array(
			'arrow'   => '<path d="M12 19V5M5 12l7-7 7 7"/>',
			'chevron' => '<path d="M6 15l6-6 6 6"/>',
			'double'  => '<path d="M6 17l6-6 6 6M6 11l6-6 6 6"/>',
		);
		$path      = $icons[ rafah_opt( 'btt_icon', 'arrow' ) ] ?? $icons['arrow'];
		$icon_html = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
	}
	?>
	<button type="button"
		class="rafah-backtotop rafah-backtotop--<?php echo esc_attr( rafah_opt( 'btt_position', 'start' ) ); ?> rafah-backtotop--anim-<?php echo esc_attr( rafah_opt( 'btt_anim', 'fade-up' ) ); ?>"
		data-rafah-backtotop aria-label="<?php esc_attr_e( 'Back to top', 'rafah-theme' ); ?>">
		<?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped per-branch above. ?>
	</button>
	<?php
} );

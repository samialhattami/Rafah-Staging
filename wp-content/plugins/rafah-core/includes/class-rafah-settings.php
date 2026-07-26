<?php
/**
 * Rafah Core — Global settings (Settings → Rafah).
 *
 * Currently powers the site-wide animation system. Designed to grow:
 * add fields to fields() and defaults to defaults() — sanitize() and the
 * renderer pick them up automatically.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Settings {

	const OPTION = 'rafah_settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'admin_body_class' ) );
	}

	/**
	 * Default values — used on fresh installs and as fallbacks.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'anim_enabled'  => 1,
			'anim_style'    => 'fade-up',
			'anim_duration' => 700,
			'anim_stagger'  => 90,
			'unit_highlight_sold_admin' => 1,
			'unit_highlight_sold_front' => 0,
		);
	}

	/**
	 * Get one setting with fallback to defaults.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$settings = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );

		return $settings[ $key ] ?? null;
	}

	/**
	 * Animation style choices.
	 *
	 * @return array
	 */
	public static function styles() {
		return array(
			'fade-up'  => __( 'Fade Up (recommended)', 'rafah' ),
			'fade-in'  => __( 'Fade In', 'rafah' ),
			'slide-up' => __( 'Slide Up', 'rafah' ),
			'scale'    => __( 'Scale', 'rafah' ),
		);
	}

	// ------------------------------------------------------------- Front-end.

	/**
	 * Gate all animation CSS behind body classes so that disabling animations
	 * leaves the site pixel-identical (no hidden elements, no layout shifts).
	 */
	public static function body_class( $classes ) {
		if ( self::get( 'anim_enabled' ) ) {
			$classes[] = 'rafah-anim';
			$classes[] = 'rafah-anim--' . sanitize_html_class( self::get( 'anim_style' ) );
		}

		// Front-end unit status-row highlight — independent, default OFF. Only
		// added to the public pages when "Highlight Sold Units on Frontend" is on.
		if ( self::get( 'unit_highlight_sold_front' ) ) {
			$classes[] = 'rafah-unit-hl';
		}

		return $classes;
	}

	/**
	 * Admin Units Manager row highlight — independent, default ON. Tints rows by
	 * status (sold = light red, reserved = subtle, available = none) when
	 * "Highlight Sold Units in Admin" is on. Pure CSS gate — the DOM never changes.
	 */
	public static function admin_body_class( $classes ) {
		if ( self::get( 'unit_highlight_sold_admin' ) ) {
			$classes .= ' rafah-unit-hl';
		}

		return $classes;
	}

	// ------------------------------------------------------------- Admin.

	public static function menu() {
		add_options_page(
			__( 'Rafah Settings', 'rafah' ),
			__( 'Rafah', 'rafah' ),
			'manage_options',
			'rafah-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register() {
		register_setting(
			'rafah_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	public static function sanitize( $input ) {
		$input = (array) $input;

		return array(
			'anim_enabled'  => empty( $input['anim_enabled'] ) ? 0 : 1,
			'anim_style'    => array_key_exists( $input['anim_style'] ?? '', self::styles() ) ? $input['anim_style'] : 'fade-up',
			'anim_duration' => min( 3000, max( 100, absint( $input['anim_duration'] ?? 700 ) ) ),
			'anim_stagger'  => min( 500, max( 0, absint( $input['anim_stagger'] ?? 90 ) ) ),
			'unit_highlight_sold_admin' => empty( $input['unit_highlight_sold_admin'] ) ? 0 : 1,
			'unit_highlight_sold_front' => empty( $input['unit_highlight_sold_front'] ) ? 0 : 1,
		);
	}

	public static function render_page() {
		$settings = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Rafah Settings', 'rafah' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'rafah_settings_group' ); ?>

				<h2><?php esc_html_e( 'Scroll Animations', 'rafah' ); ?></h2>
				<p class="description" style="max-width:640px">
					<?php esc_html_e( 'Premium reveal-on-scroll animations across the whole site. When disabled, the site looks exactly the same — content is simply always visible. Visitors with "reduce motion" enabled in their device settings never see animations, regardless of these options.', 'rafah' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Animations', 'rafah' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[anim_enabled]" value="1" <?php checked( $settings['anim_enabled'], 1 ); ?>>
								<?php esc_html_e( 'Enable reveal-on-scroll animations site-wide', 'rafah' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rafah-anim-style"><?php esc_html_e( 'Animation Style', 'rafah' ); ?></label></th>
						<td>
							<select id="rafah-anim-style" name="<?php echo esc_attr( self::OPTION ); ?>[anim_style]">
								<?php foreach ( self::styles() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['anim_style'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rafah-anim-duration"><?php esc_html_e( 'Duration (ms)', 'rafah' ); ?></label></th>
						<td>
							<input id="rafah-anim-duration" type="number" min="100" max="3000" step="50" name="<?php echo esc_attr( self::OPTION ); ?>[anim_duration]" value="<?php echo esc_attr( $settings['anim_duration'] ); ?>" class="small-text">
							<p class="description"><?php esc_html_e( 'How long each element takes to appear. Default: 700.', 'rafah' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rafah-anim-stagger"><?php esc_html_e( 'Stagger Delay (ms)', 'rafah' ); ?></label></th>
						<td>
							<input id="rafah-anim-stagger" type="number" min="0" max="500" step="10" name="<?php echo esc_attr( self::OPTION ); ?>[anim_stagger]" value="<?php echo esc_attr( $settings['anim_stagger'] ); ?>" class="small-text">
							<p class="description"><?php esc_html_e( 'Delay between sibling cards in the same grid (creates the elegant cascade). Default: 90. Set 0 to disable staggering.', 'rafah' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 style="margin-top:34px"><?php esc_html_e( 'Units Manager', 'rafah' ); ?></h2>
				<p class="description" style="max-width:640px">
					<?php esc_html_e( 'Sold unit rows are tinted light red and reserved rows get a subtle highlight (available rows stay normal). The two options below are completely independent.', 'rafah' ); ?>
				</p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Highlight Sold Units in Admin', 'rafah' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[unit_highlight_sold_admin]" value="1" <?php checked( $settings['unit_highlight_sold_admin'] ?? 1, 1 ); ?>>
								<?php esc_html_e( 'Highlight rows inside the WordPress Units Manager only.', 'rafah' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Highlight Sold Units on Frontend', 'rafah' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[unit_highlight_sold_front]" value="1" <?php checked( $settings['unit_highlight_sold_front'] ?? 0, 1 ); ?>>
								<?php esc_html_e( 'Highlight rows in the public units tables/widgets only.', 'rafah' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

<?php
/**
 * Rafah — Site header (native PHP; no builder dependency).
 * Logo · primary menu (mega-menu ready) · language switcher · phone ·
 * CTA · mobile offcanvas. Behavior flags come from the Customizer.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

$rafah_cta_text = rafah_opt( 'header_cta_text', 'تواصل معنا' );
$rafah_cta_url  = rafah_opt( 'header_cta_url', '/contact/' );
$rafah_phone    = rafah_opt( 'header_phone', '' );
?>
<header id="rafah-header" class="rafah-header" role="banner">
	<div class="rafah-header__inner">

		<div class="rafah-header__brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="rafah-header__sitename" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php bloginfo( 'name' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<nav class="rafah-nav" aria-label="<?php esc_attr_e( 'Primary', 'rafah-theme' ); ?>">
			<?php rafah_theme_nav( 'primary', 'rafah-nav__list' ); ?>
		</nav>

		<div class="rafah-header__actions">
			<?php rafah_theme_language_switcher(); ?>

			<?php if ( $rafah_phone ) : ?>
				<a class="rafah-header__phone" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $rafah_phone ) ); ?>">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.6.1.3 0 .7-.2 1l-2.3 2.2z"/></svg>
					<span><?php echo esc_html( $rafah_phone ); ?></span>
				</a>
			<?php endif; ?>

			<?php if ( $rafah_cta_text ) : ?>
				<a class="rafah-btn rafah-btn--primary rafah-header__cta" href="<?php echo esc_url( $rafah_cta_url ); ?>">
					<?php echo esc_html( $rafah_cta_text ); ?>
				</a>
			<?php endif; ?>

			<button type="button" class="rafah-header__burger" aria-label="<?php esc_attr_e( 'Menu', 'rafah-theme' ); ?>" aria-expanded="false" aria-controls="rafah-offcanvas" data-rafah-burger>
				<span></span><span></span><span></span>
			</button>
		</div>
	</div>
</header>

<!-- Mobile offcanvas -->
<div class="rafah-offcanvas-overlay" data-rafah-offcanvas-close hidden></div>
<aside id="rafah-offcanvas" class="rafah-offcanvas" aria-hidden="true">
	<div class="rafah-offcanvas__head">
		<?php if ( has_custom_logo() ) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<strong><?php bloginfo( 'name' ); ?></strong>
		<?php endif; ?>
		<button type="button" class="rafah-offcanvas__close" aria-label="<?php esc_attr_e( 'Close', 'rafah-theme' ); ?>" data-rafah-offcanvas-close>×</button>
	</div>

	<nav class="rafah-offcanvas__nav" aria-label="<?php esc_attr_e( 'Mobile', 'rafah-theme' ); ?>">
		<?php rafah_theme_nav( 'primary', 'rafah-offcanvas__list', 2 ); ?>
	</nav>

	<div class="rafah-offcanvas__footer">
		<?php rafah_theme_language_switcher(); ?>

		<?php if ( $rafah_cta_text ) : ?>
			<a class="rafah-btn rafah-btn--primary" href="<?php echo esc_url( $rafah_cta_url ); ?>"><?php echo esc_html( $rafah_cta_text ); ?></a>
		<?php endif; ?>

		<?php $rafah_whatsapp = rafah_opt( 'contact_whatsapp' ); ?>
		<?php if ( $rafah_whatsapp && function_exists( 'rafah_whatsapp_url' ) ) : ?>
			<a class="rafah-btn rafah-btn--whatsapp" href="<?php echo esc_url( rafah_whatsapp_url( $rafah_whatsapp ) ); ?>" target="_blank" rel="noopener">WhatsApp</a>
		<?php endif; ?>
	</div>
</aside>

<?php if ( rafah_opt( 'preloader', false ) ) : ?>
	<div class="rafah-preloader" data-rafah-preloader aria-hidden="true"><span class="rafah-preloader__logo">رفاه</span></div>
<?php endif; ?>

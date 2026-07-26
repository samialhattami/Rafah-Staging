<?php
/**
 * Rafah — Site footer (native PHP; no builder dependency).
 * Four columns with smart defaults; each value is Customizer-editable and each
 * column can be overridden by a widget area (Appearance → Widgets). Colors,
 * typography and spacing come from the footer module's dynamic CSS. Output is
 * IDENTICAL to the previous stable footer when no new setting is customized.
 *
 * Back-to-Top is NOT here — it renders independently via inc/footer/back-to-top.php
 * on wp_footer. This file is loaded ONLY on astra_footer (footer scope).
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

$rafah_socials = array_filter( array(
	'x'         => rafah_opt( 'social_x' ),
	'instagram' => rafah_opt( 'social_instagram' ),
	'linkedin'  => rafah_opt( 'social_linkedin' ),
	'snapchat'  => rafah_opt( 'social_snapchat' ),
	'tiktok'    => rafah_opt( 'social_tiktok' ),
	'facebook'  => rafah_opt( 'social_facebook' ),
	'youtube'   => rafah_opt( 'social_youtube' ),
) );

$rafah_footer_logo = rafah_opt( 'footer_logo' );
$rafah_col2_links  = function_exists( 'rafah_footer_parse_links' ) ? rafah_footer_parse_links( rafah_opt( 'footer_col2_links' ) ) : array();
$rafah_col3_links  = function_exists( 'rafah_footer_parse_links' ) ? rafah_footer_parse_links( rafah_opt( 'footer_col3_links' ) ) : array();
?>
<footer class="rafah-footer" role="contentinfo">
	<div class="rafah-footer__inner">

		<!-- Column 1: brand -->
		<div class="rafah-footer__col">
			<?php if ( is_active_sidebar( 'rafah-footer-1' ) ) : ?>
				<?php dynamic_sidebar( 'rafah-footer-1' ); ?>
			<?php else : ?>
				<div class="rafah-footer__brand">
					<?php if ( $rafah_footer_logo ) : ?>
						<span class="rafah-footer__logo"><img src="<?php echo esc_url( $rafah_footer_logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></span>
					<?php elseif ( has_custom_logo() ) : ?>
						<?php the_custom_logo(); ?>
					<?php else : ?>
						<strong class="rafah-footer__sitename"><?php bloginfo( 'name' ); ?></strong>
					<?php endif; ?>
				</div>
				<p class="rafah-footer__desc"><?php echo esc_html( rafah_opt( 'footer_description', 'رفاه للتطوير العقاري — نبني مجتمعات سكنية ترتقي بجودة الحياة في المملكة العربية السعودية.' ) ); ?></p>
				<?php if ( $rafah_socials ) : ?>
					<div class="rafah-footer__social">
						<?php foreach ( $rafah_socials as $rafah_network => $rafah_link ) : ?>
							<a class="rafah-icon-btn" href="<?php echo esc_url( $rafah_link ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $rafah_network ); ?>">
								<strong><?php echo esc_html( strtoupper( mb_substr( $rafah_network, 0, 1 ) ) ); ?></strong>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<!-- Column 2: quick links (Customizer links override the Footer menu) -->
		<div class="rafah-footer__col">
			<?php if ( is_active_sidebar( 'rafah-footer-2' ) ) : ?>
				<?php dynamic_sidebar( 'rafah-footer-2' ); ?>
			<?php else : ?>
				<h4 class="rafah-footer__title"><?php echo esc_html( rafah_opt( 'footer_col2_heading' ) ?: ( function_exists( 'rafah_is_rtl_lang' ) && ! rafah_is_rtl_lang() ? 'Quick Links' : 'روابط سريعة' ) ); ?></h4>
				<?php if ( $rafah_col2_links ) : ?>
					<ul class="rafah-footer__links">
						<?php foreach ( $rafah_col2_links as $rafah_l ) : ?>
							<li><a href="<?php echo esc_url( $rafah_l['url'] ); ?>"><?php echo esc_html( $rafah_l['text'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<?php rafah_theme_nav( 'footer', 'rafah-footer__links', 1 ); ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<!-- Column 3: latest projects (Customizer links override) -->
		<div class="rafah-footer__col">
			<?php if ( is_active_sidebar( 'rafah-footer-3' ) ) : ?>
				<?php dynamic_sidebar( 'rafah-footer-3' ); ?>
			<?php elseif ( $rafah_col3_links ) : ?>
				<h4 class="rafah-footer__title"><?php echo esc_html( rafah_opt( 'footer_col3_heading' ) ?: ( function_exists( 'rafah_text' ) ? rafah_text( 'projects' ) : 'المشاريع' ) ); ?></h4>
				<ul class="rafah-footer__links">
					<?php foreach ( $rafah_col3_links as $rafah_l ) : ?>
						<li><a href="<?php echo esc_url( $rafah_l['url'] ); ?>"><?php echo esc_html( $rafah_l['text'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<?php
				// Rafah Core may be deactivated — degrade to nothing, never fatal.
				// Fall back to default-language projects when the current
				// language has no translated projects yet.
				$rafah_footer_projects = post_type_exists( 'project' ) ? get_posts( array(
					'post_type'      => 'project',
					'posts_per_page' => 4,
				) ) : array();

				if ( ! $rafah_footer_projects && post_type_exists( 'project' ) && function_exists( 'pll_default_language' ) ) {
					$rafah_footer_projects = get_posts( array(
						'post_type'      => 'project',
						'posts_per_page' => 4,
						'lang'           => pll_default_language(),
					) );
				}
				?>
				<?php if ( $rafah_footer_projects ) : ?>
					<h4 class="rafah-footer__title"><?php echo esc_html( rafah_opt( 'footer_col3_heading' ) ?: ( function_exists( 'rafah_text' ) ? rafah_text( 'projects' ) : 'المشاريع' ) ); ?></h4>
					<ul class="rafah-footer__links">
						<?php foreach ( $rafah_footer_projects as $rafah_fp ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $rafah_fp ) ); ?>"><?php echo esc_html( get_the_title( $rafah_fp ) ); ?></a></li>
						<?php endforeach; ?>
						<?php if ( get_post_type_archive_link( 'project' ) ) : ?>
							<li><a class="is-more" href="<?php echo esc_url( get_post_type_archive_link( 'project' ) ); ?>"><?php echo esc_html( function_exists( 'rafah_text' ) ? rafah_text( 'view_all_projects' ) : 'جميع المشاريع' ); ?></a></li>
						<?php endif; ?>
					</ul>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<!-- Column 4: contact + newsletter -->
		<div class="rafah-footer__col">
			<?php if ( is_active_sidebar( 'rafah-footer-4' ) ) : ?>
				<?php dynamic_sidebar( 'rafah-footer-4' ); ?>
			<?php else : ?>
				<h4 class="rafah-footer__title"><?php echo esc_html( rafah_opt( 'footer_contact_heading' ) ?: ( function_exists( 'rafah_text' ) ? rafah_text( 'contact_us' ) : 'تواصل معنا' ) ); ?></h4>
				<?php
				// Read once WITH defaults — conditions and output must agree.
				$rafah_c_phone   = rafah_opt( 'contact_phone', '920000000' );
				$rafah_c_email   = rafah_opt( 'contact_email', 'info@rafah.sa' );
				$rafah_c_address = rafah_opt( 'contact_address', 'الرياض، المملكة العربية السعودية' );
				$rafah_c_maps    = rafah_opt( 'contact_maps' );
				?>
				<ul class="rafah-footer__contact">
					<?php if ( $rafah_c_phone ) : ?>
						<li><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $rafah_c_phone ) ); ?>"><?php echo esc_html( $rafah_c_phone ); ?></a></li>
					<?php endif; ?>
					<?php if ( $rafah_c_email ) : ?>
						<li><a href="mailto:<?php echo esc_attr( $rafah_c_email ); ?>"><?php echo esc_html( $rafah_c_email ); ?></a></li>
					<?php endif; ?>
					<?php if ( $rafah_c_address ) : ?>
						<li>
							<?php if ( $rafah_c_maps ) : ?>
								<a href="<?php echo esc_url( $rafah_c_maps ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $rafah_c_address ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $rafah_c_address ); ?>
							<?php endif; ?>
						</li>
					<?php endif; ?>
					<?php if ( rafah_opt( 'contact_whatsapp' ) && function_exists( 'rafah_whatsapp_url' ) ) : ?>
						<li><a href="<?php echo esc_url( rafah_whatsapp_url( rafah_opt( 'contact_whatsapp' ) ) ); ?>" target="_blank" rel="noopener">WhatsApp</a></li>
					<?php endif; ?>
				</ul>
				<?php
				$rafah_newsletter = rafah_theme_render_shortcode( rafah_opt( 'footer_form_shortcode' ) );
				if ( $rafah_newsletter ) {
					echo '<div class="rafah-footer__newsletter">' . $rafah_newsletter . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			<?php endif; ?>
		</div>
	</div>

	<div class="rafah-footer__bottom">
		<div class="rafah-footer__bottom-inner">
			<span><?php echo esc_html( str_replace( '%year%', gmdate( 'Y' ), rafah_opt( 'footer_copyright', '© %year% رفاه للتطوير العقاري. جميع الحقوق محفوظة.' ) ) ); ?></span>
		</div>
	</div>
</footer>

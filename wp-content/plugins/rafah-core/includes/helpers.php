<?php
/**
 * Rafah Core — helper functions.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bilingual UI strings for the front end.
 * Arabic is the default; English is served when the locale is not Arabic.
 * Works out of the box with Polylang (which switches the locale per language).
 *
 * @param string $key String key.
 * @return string
 */
function rafah_text( $key ) {
	static $strings = null;

	if ( null === $strings ) {
		$strings = array(
			'starting_from'      => array( 'ar' => 'يبدأ من', 'en' => 'Starting from' ),
			'sar'                => array( 'ar' => 'ريال', 'en' => 'SAR' ),
			'view_project'       => array( 'ar' => 'اكتشف المشروع', 'en' => 'View Project' ),
			'view_all_projects'  => array( 'ar' => 'جميع المشاريع', 'en' => 'All Projects' ),
			'bedrooms'           => array( 'ar' => 'غرف النوم', 'en' => 'Bedrooms' ),
			'bathrooms'          => array( 'ar' => 'دورات المياه', 'en' => 'Bathrooms' ),
			'area'               => array( 'ar' => 'المساحة', 'en' => 'Area' ),
			'sqm'                => array( 'ar' => 'م²', 'en' => 'm²' ),
			'units'              => array( 'ar' => 'الوحدات', 'en' => 'Units' ),
			'floors'             => array( 'ar' => 'الأدوار', 'en' => 'Floors' ),
			'buildings'          => array( 'ar' => 'المباني', 'en' => 'Buildings' ),
			'parking'            => array( 'ar' => 'مواقف السيارات', 'en' => 'Parking' ),
			'delivery'           => array( 'ar' => 'موعد التسليم', 'en' => 'Delivery' ),
			'completion'         => array( 'ar' => 'نسبة الإنجاز', 'en' => 'Completion' ),
			'developer'          => array( 'ar' => 'المطوّر', 'en' => 'Developer' ),
			'consultant'         => array( 'ar' => 'الاستشاري', 'en' => 'Consultant' ),
			'contractor'         => array( 'ar' => 'شركة المقاولات', 'en' => 'Contractor' ),
			'location'           => array( 'ar' => 'الموقع', 'en' => 'Location' ),
			'city'               => array( 'ar' => 'المدينة', 'en' => 'City' ),
			'district'           => array( 'ar' => 'الحي', 'en' => 'District' ),
			'status'             => array( 'ar' => 'حالة المشروع', 'en' => 'Status' ),
				'status_available'   => array( 'ar' => 'متاحة', 'en' => 'Available' ),
				'status_coming_soon' => array( 'ar' => 'قريباً', 'en' => 'Coming Soon' ),
				'status_sold'        => array( 'ar' => 'مباعة', 'en' => 'Sold' ),
			'type'               => array( 'ar' => 'نوع المشروع', 'en' => 'Type' ),
			'price'              => array( 'ar' => 'السعر', 'en' => 'Price' ),
			'price_range'        => array( 'ar' => 'نطاق السعر', 'en' => 'Price Range' ),
			'overview'           => array( 'ar' => 'نبذة عن المشروع', 'en' => 'Overview' ),
			'gallery'            => array( 'ar' => 'معرض الصور', 'en' => 'Gallery' ),
				'close'              => array( 'ar' => 'إغلاق', 'en' => 'Close' ),
				'prev'               => array( 'ar' => 'السابق', 'en' => 'Previous' ),
				'next'               => array( 'ar' => 'التالي', 'en' => 'Next' ),
				'zoom'               => array( 'ar' => 'تكبير', 'en' => 'Zoom' ),
			'video'              => array( 'ar' => 'فيديو المشروع', 'en' => 'Video' ),
			'tour_360'           => array( 'ar' => 'جولة افتراضية 360°', 'en' => '360° Virtual Tour' ),
			'floor_plans'        => array( 'ar' => 'المخططات', 'en' => 'Floor Plans' ),
			'features'           => array( 'ar' => 'المميزات', 'en' => 'Features' ),
			'amenities'          => array( 'ar' => 'الخدمات والمرافق', 'en' => 'Amenities' ),
			'nearby'             => array( 'ar' => 'الأماكن القريبة', 'en' => 'Nearby Places' ),
			'payment_plans'      => array( 'ar' => 'خطط السداد', 'en' => 'Payment Plans' ),
			'mortgage'           => array( 'ar' => 'التمويل العقاري', 'en' => 'Mortgage' ),
			'downloads'          => array( 'ar' => 'الملفات والتحميلات', 'en' => 'Downloads' ),
			'brochure'           => array( 'ar' => 'الكتيّب التعريفي', 'en' => 'Brochure' ),
			'download'           => array( 'ar' => 'تحميل', 'en' => 'Download' ),
			'related_projects'   => array( 'ar' => 'مشاريع مشابهة', 'en' => 'Related Projects' ),
			'contact_us'         => array( 'ar' => 'تواصل معنا', 'en' => 'Contact Us' ),
			'call_now'           => array( 'ar' => 'اتصل الآن', 'en' => 'Call Now' ),
			'whatsapp'           => array( 'ar' => 'واتساب', 'en' => 'WhatsApp' ),
			'book_viewing'       => array( 'ar' => 'احجز معاينة', 'en' => 'Book a Viewing' ),
			'request_info'       => array( 'ar' => 'اطلب المعلومات', 'en' => 'Request Information' ),
			'share'              => array( 'ar' => 'مشاركة', 'en' => 'Share' ),
			'agent'              => array( 'ar' => 'المستشار العقاري', 'en' => 'Real Estate Agent' ),
			'agents'             => array( 'ar' => 'فريق المبيعات', 'en' => 'Our Agents' ),
			'position'           => array( 'ar' => 'المسمى الوظيفي', 'en' => 'Position' ),
			'languages'          => array( 'ar' => 'اللغات', 'en' => 'Languages' ),
			'specialties'        => array( 'ar' => 'التخصصات', 'en' => 'Specialties' ),
			'experience'         => array( 'ar' => 'سنوات الخبرة', 'en' => 'Years of Experience' ),
			'license'            => array( 'ar' => 'رخصة فال', 'en' => 'FAL License' ),
			'agent_projects'     => array( 'ar' => 'مشاريع المستشار', 'en' => 'Agent Projects' ),
			'send_message'       => array( 'ar' => 'أرسل رسالة', 'en' => 'Send a Message' ),
			'schedule_meeting'   => array( 'ar' => 'حدد موعداً', 'en' => 'Schedule a Meeting' ),
			'filter_projects'    => array( 'ar' => 'تصفية المشاريع', 'en' => 'Filter Projects' ),
			'all_cities'         => array( 'ar' => 'جميع المدن', 'en' => 'All Cities' ),
			'all_districts'      => array( 'ar' => 'جميع الأحياء', 'en' => 'All Districts' ),
			'all_statuses'       => array( 'ar' => 'جميع الحالات', 'en' => 'All Statuses' ),
			'all_types'          => array( 'ar' => 'جميع الأنواع', 'en' => 'All Types' ),
			'any_bedrooms'       => array( 'ar' => 'أي عدد غرف', 'en' => 'Any Bedrooms' ),
			'max_price'          => array( 'ar' => 'الحد الأعلى للسعر', 'en' => 'Max Price' ),
			'search'             => array( 'ar' => 'بحث', 'en' => 'Search' ),
			'search_placeholder' => array( 'ar' => 'ابحث عن مشروع…', 'en' => 'Search projects…' ),
			'no_results'         => array( 'ar' => 'لا توجد مشاريع مطابقة لبحثك حالياً.', 'en' => 'No projects match your search right now.' ),
			'reset_filters'      => array( 'ar' => 'إعادة تعيين', 'en' => 'Reset' ),
			'sort_newest'        => array( 'ar' => 'الأحدث', 'en' => 'Newest' ),
			'sort_price_asc'     => array( 'ar' => 'السعر: من الأقل', 'en' => 'Price: Low to High' ),
			'sort_price_desc'    => array( 'ar' => 'السعر: من الأعلى', 'en' => 'Price: High to Low' ),
			'featured'           => array( 'ar' => 'مميز', 'en' => 'Featured' ),
			'load_more'          => array( 'ar' => 'عرض المزيد', 'en' => 'Load More' ),
			'home'               => array( 'ar' => 'الرئيسية', 'en' => 'Home' ),
			'projects'           => array( 'ar' => 'المشاريع', 'en' => 'Projects' ),
			'key_facts'          => array( 'ar' => 'معلومات المشروع', 'en' => 'Project Facts' ),
			'available'          => array( 'ar' => 'الوحدات المتاحة', 'en' => 'Available Units' ),
			'unit_types'         => array( 'ar' => 'أنواع الوحدات', 'en' => 'Unit Types' ),
			'view_on_map'        => array( 'ar' => 'عرض على الخريطة', 'en' => 'View on Map' ),
			'address'            => array( 'ar' => 'العنوان', 'en' => 'Address' ),
			'interested'         => array( 'ar' => 'مهتم بهذا المشروع؟', 'en' => 'Interested in this project?' ),
			'interested_sub'     => array( 'ar' => 'فريقنا جاهز للإجابة على استفساراتك ومساعدتك في اختيار وحدتك.', 'en' => 'Our team is ready to answer your questions and help you choose your unit.' ),
			'school'             => array( 'ar' => 'مدارس', 'en' => 'Schools' ),
			'hospital'           => array( 'ar' => 'مستشفيات', 'en' => 'Hospitals' ),
			'mosque'             => array( 'ar' => 'مساجد', 'en' => 'Mosques' ),
			'shopping'           => array( 'ar' => 'تسوق', 'en' => 'Shopping' ),
			'transport'          => array( 'ar' => 'مواصلات', 'en' => 'Transportation' ),
			'other'              => array( 'ar' => 'أخرى', 'en' => 'Other' ),
			'min'                => array( 'ar' => 'دقيقة', 'en' => 'min' ),
			'km'                 => array( 'ar' => 'كم', 'en' => 'km' ),
			'read_more'          => array( 'ar' => 'اقرأ المزيد', 'en' => 'Read More' ),
			'project_details'    => array( 'ar' => 'تفاصيل المشروع', 'en' => 'Project Details' ),
			'open_tour'          => array( 'ar' => 'افتح الجولة الافتراضية', 'en' => 'Open Virtual Tour' ),
			'bathrooms_range'    => array( 'ar' => 'دورات المياه', 'en' => 'Bathrooms' ),
			'view_all_news'      => array( 'ar' => 'عرض جميع الأخبار', 'en' => 'View All News' ),
			'view_all_articles'  => array( 'ar' => 'عرض جميع المقالات', 'en' => 'View All Articles' ),
			'news'               => array( 'ar' => 'أخبار رفاه', 'en' => 'Rafah News' ),
			'blog'               => array( 'ar' => 'المدونة', 'en' => 'Blog' ),
			'articles'           => array( 'ar' => 'المقالات', 'en' => 'Articles' ),
		);
	}

	$lang = rafah_is_rtl_lang() ? 'ar' : 'en';

	return isset( $strings[ $key ] ) ? $strings[ $key ][ $lang ] : $key;
}

/**
 * Whether the current language is Arabic.
 *
 * In the dashboard (admin screens and our own admin AJAX) this follows the
 * employee's dashboard language, so every admin label matches whatever
 * language they set for their WordPress admin. On the front end it follows
 * the Polylang page language, so each visitor sees the page's own language.
 *
 * @return bool
 */
function rafah_is_rtl_lang() {
	if ( rafah_is_admin_context() ) {
		return rafah_dashboard_is_ar();
	}

	if ( function_exists( 'pll_current_language' ) ) {
		$lang = pll_current_language();
		if ( $lang ) {
			return 'ar' === $lang;
		}
	}

	return str_starts_with( get_locale(), 'ar' );
}

/**
 * Whether we are in a dashboard (admin) context that should follow the
 * employee's dashboard language rather than a visitor's page language.
 *
 * True on admin screens and during our own Units Manager AJAX calls, but
 * NOT during front-end AJAX (e.g. the project filter), so a visitor's
 * language is never overridden by an employee's dashboard preference.
 *
 * @return bool
 */
function rafah_is_admin_context() {
	if ( ! is_admin() ) {
		return false;
	}

	if ( ! wp_doing_ajax() ) {
		return true;
	}

	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

	return str_starts_with( $action, 'rafah_units_' );
}

/**
 * Whether the current employee's dashboard language is Arabic.
 *
 * @return bool
 */
function rafah_dashboard_is_ar() {
	return str_starts_with( get_user_locale(), 'ar' );
}

/**
 * Get project/agent meta with the rafah prefix.
 *
 * @param string   $key     Field key without prefix.
 * @param int|null $post_id Post ID.
 * @return mixed
 */
function rafah_meta( $key, $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	return get_post_meta( $post_id, '_rafah_' . $key, true );
}

/**
 * Format a price with thousands separators and currency label.
 *
 * @param int|float|string $amount   Amount.
 * @param int|null         $post_id  Post ID (for currency).
 * @return string
 */
function rafah_price( $amount, $post_id = null ) {
	if ( '' === $amount || null === $amount ) {
		return '';
	}

	$currency = rafah_meta( 'currency', $post_id ) ?: 'SAR';
	$labels   = array(
		'SAR' => rafah_text( 'sar' ),
		'USD' => rafah_is_rtl_lang() ? 'دولار' : 'USD',
		'AED' => rafah_is_rtl_lang() ? 'درهم' : 'AED',
	);
	$label    = $labels[ $currency ] ?? $currency;

	return number_format( (float) $amount ) . ' ' . $label;
}

/**
 * Build a wa.me link from a phone number.
 *
 * @param string $number Phone number in any format.
 * @param string $text   Optional prefilled message.
 * @return string
 */
function rafah_whatsapp_url( $number, $text = '' ) {
	$digits = preg_replace( '/[^0-9]/', '', (string) $number );

	if ( str_starts_with( $digits, '05' ) ) {
		$digits = '966' . substr( $digits, 1 );
	} elseif ( str_starts_with( $digits, '5' ) && strlen( $digits ) === 9 ) {
		$digits = '966' . $digits;
	}

	$url = 'https://wa.me/' . $digits;

	if ( $text ) {
		$url .= '?text=' . rawurlencode( $text );
	}

	return $url;
}

/**
 * Render a group of buttons from a native Elementor "Buttons" repeater
 * (see Rafah_Repeaters::buttons()).
 *
 * Single source of truth for button-group markup across widgets. Items whose
 * text is empty are skipped, and if NO buttons remain the function returns an
 * empty string — so the wrapper (and any empty space) disappears entirely.
 *
 * Each item: [ 'text' => string, 'link' => (Elementor URL array|string),
 *              'variant' => 'primary|secondary|light|ghost|whatsapp' ].
 * The 'whatsapp' variant treats the link value as a phone number.
 *
 * @param array  $buttons    Repeater rows.
 * @param string $wrap_class Wrapper class (e.g. 'rafah-hero__actions').
 * @return string
 */
function rafah_buttons_html( $buttons, $wrap_class = 'rafah-btn-group' ) {
	$items = array();

	foreach ( (array) $buttons as $button ) {
		if ( '' !== trim( (string) ( $button['text'] ?? '' ) ) ) {
			$items[] = $button;
		}
	}

	if ( ! $items ) {
		return '';
	}

	$html = '<div class="' . esc_attr( $wrap_class ) . '">';

	foreach ( $items as $button ) {
		$variant  = sanitize_html_class( $button['variant'] ?? 'primary' );
		$link     = $button['link'] ?? array();
		$raw      = is_array( $link ) ? ( $link['url'] ?? '' ) : (string) $link;
		$external = is_array( $link ) && ! empty( $link['is_external'] );
		$nofollow = is_array( $link ) && ! empty( $link['nofollow'] );

		if ( 'whatsapp' === $variant ) {
			$href     = rafah_whatsapp_url( $raw );
			$external = true;
		} else {
			$href = '' !== $raw ? $raw : '#';
		}

		$rel   = array();
		$attrs = 'href="' . esc_url( $href ) . '"';
		if ( $external ) {
			$attrs .= ' target="_blank"';
			$rel[]  = 'noopener';
		}
		if ( $nofollow ) {
			$rel[] = 'nofollow';
		}
		if ( $rel ) {
			$attrs .= ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"';
		}

		$html .= '<a class="rafah-btn rafah-btn--' . esc_attr( $variant ) . '" ' . $attrs . '>' . esc_html( $button['text'] ) . '</a>';
	}

	return $html . '</div>';
}

/**
 * Get the first term name of a taxonomy for a post.
 *
 * @param string   $taxonomy Taxonomy.
 * @param int|null $post_id  Post ID.
 * @return string
 */
function rafah_term_name( $taxonomy, $post_id = null ) {
	$terms = get_the_terms( $post_id ?: get_the_ID(), $taxonomy );

	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
}

/**
 * The canonical project statuses — the single source of truth.
 *
 * Status is a controlled meta field (`_rafah_status`), NOT a taxonomy: the set
 * is a fixed enum the whole platform relies on (overlays, filters, queries), so
 * it must never drift or be renamed by editors. Keys are stable/ASCII; labels
 * follow the current language.
 *
 * @return array key => localized label. Order defines display order.
 */
function rafah_project_status_options() {
	return array(
		'available'   => rafah_text( 'status_available' ),
		'coming_soon' => rafah_text( 'status_coming_soon' ),
		'sold'        => rafah_text( 'status_sold' ),
	);
}

/**
 * A project's canonical status key. Defaults to 'available' when unset or
 * invalid, so every project always has a well-defined status.
 *
 * @param int|null $post_id Project ID.
 * @return string One of: available | coming_soon | sold.
 */
function rafah_project_status( $post_id = null ) {
	$value = (string) rafah_meta( 'status', $post_id );

	return array_key_exists( $value, rafah_project_status_options() ) ? $value : 'available';
}

/**
 * A project's status label in the current language.
 *
 * @param int|string|null $post_id_or_key Project ID, or a status key directly.
 * @return string
 */
function rafah_project_status_label( $post_id_or_key = null ) {
	$options = rafah_project_status_options();
	$key     = ( is_string( $post_id_or_key ) && isset( $options[ $post_id_or_key ] ) )
		? $post_id_or_key
		: rafah_project_status( is_numeric( $post_id_or_key ) ? (int) $post_id_or_key : null );

	return $options[ $key ] ?? $options['available'];
}

/**
 * Responsive status overlay markup for a project card cover.
 *
 * Sold → a "SOLD" overlay; Coming Soon → a "COMING SOON" overlay; Available →
 * nothing. The overlay sits ON the normal card cover — it never replaces or
 * changes the image. Pure CSS (see .rafah-status-overlay), so it scales cleanly
 * on every card size and layout.
 *
 * @param int|null $post_id Project ID.
 * @return string HTML, or '' for Available.
 */
function rafah_project_status_overlay_html( $post_id = null ) {
	$status = rafah_project_status( $post_id );

	if ( 'sold' !== $status && 'coming_soon' !== $status ) {
		return '';
	}

	// Per-project "Show Status Badge" toggle (default ON). Only an explicit '0'
	// (the editor unticked it) hides the overlay; unset/legacy projects show it.
	if ( '0' === (string) rafah_meta( 'show_status_badge', $post_id ) ) {
		return '';
	}

	// Optional per-project custom text overrides the default status label.
	$custom = trim( (string) rafah_meta( 'custom_badge_text', $post_id ) );
	$text   = ( '' !== $custom ) ? $custom : rafah_project_status_label( $status );

	$modifier = ( 'coming_soon' === $status ) ? 'coming-soon' : 'sold';

	return sprintf(
		'<span class="rafah-status-overlay rafah-status-overlay--%1$s" aria-hidden="true"><span class="rafah-status-overlay__text">%2$s</span></span>',
		esc_attr( $modifier ),
		esc_html( $text )
	);
}

/**
 * Render the project status filter tabs (used on the projects archive).
 * The active tab is derived from the ?status= URL parameter (a canonical key).
 */
function rafah_status_tabs() {
	$statuses = rafah_project_status_options();
	$current  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! array_key_exists( $current, $statuses ) ) {
		$current = '';
	}
	?>
	<div class="rafah-status-tabs" role="tablist" aria-label="<?php echo esc_attr( rafah_text( 'status' ) ); ?>">
		<button type="button" class="rafah-status-tab<?php echo '' === $current ? ' is-active' : ''; ?>" data-status="" role="tab">
			<?php echo esc_html( rafah_text( 'all_statuses' ) ); ?>
		</button>
		<?php foreach ( $statuses as $key => $label ) : ?>
			<button type="button" class="rafah-status-tab<?php echo $key === $current ? ' is-active' : ''; ?>" data-status="<?php echo esc_attr( $key ); ?>" role="tab">
				<?php echo esc_html( $label ); ?>
			</button>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Render an agent card (shared by the Agents widget and the agents archive).
 *
 * @param int $post_id Agent ID.
 */
function rafah_agent_card( $post_id ) {
	// Theme override (WooCommerce-style): themes may ship their own
	// template-parts/agent-card.php to fully own this markup.
	$template = locate_template( 'template-parts/agent-card.php' );

	if ( $template ) {
		include $template;
		return;
	}

	$phone    = rafah_meta( 'phone', $post_id );
	$whatsapp = rafah_meta( 'whatsapp', $post_id );
	$email    = rafah_meta( 'email', $post_id );
	?>
	<article class="rafah-agent-card rafah-fade-up">
		<a class="rafah-agent-card__photo" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
			<?php echo get_the_post_thumbnail( $post_id, 'large', array( 'loading' => 'lazy' ) ); ?>
		</a>
		<div class="rafah-agent-card__body">
			<h3 class="rafah-agent-card__name">
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
			</h3>
			<div class="rafah-agent-card__position"><?php echo esc_html( rafah_meta( 'position', $post_id ) ); ?></div>
			<div class="rafah-agent-card__actions">
				<?php if ( $phone ) : ?>
					<a class="rafah-icon-btn" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>" aria-label="<?php echo esc_attr( rafah_text( 'call_now' ) ); ?>">
						<svg viewBox="0 0 24 24"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.6.1.3 0 .7-.2 1l-2.3 2.2z"/></svg>
					</a>
				<?php endif; ?>
				<?php if ( $whatsapp ) : ?>
					<a class="rafah-icon-btn rafah-icon-btn--whatsapp" href="<?php echo esc_url( rafah_whatsapp_url( $whatsapp ) ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( rafah_text( 'whatsapp' ) ); ?>">
						<svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm5.1 14.1c-.2.6-1.2 1.1-1.7 1.2-.5.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.5-2.6-1.1-4.3-3.7-4.4-3.9-.1-.2-1-1.4-1-2.6s.6-1.8.9-2.1c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .5.4l.7 1.8c.1.2.1.4 0 .5l-.4.6-.2.3c-.1.1-.2.2-.1.4.1.2.6 1 1.3 1.7.9.8 1.7 1.1 2 1.2.2.1.4.1.5-.1l.7-.8c.2-.2.3-.2.5-.1l1.8.8c.2.1.4.2.4.3.1.1.1.7-.1 1.3z"/></svg>
					</a>
				<?php endif; ?>
				<?php if ( $email ) : ?>
					<a class="rafah-icon-btn" href="mailto:<?php echo esc_attr( $email ); ?>" aria-label="Email">
						<svg viewBox="0 0 24 24"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Render a project card (shared by widgets, archives, and AJAX).
 *
 * @param int   $post_id Project ID.
 * @param array $args    Optional per-instance presentation overrides (layout,
 *                       show_*, button_text). Passed straight to the theme
 *                       card template as $rafah_card_args. Data is unaffected.
 */
function rafah_project_card( $post_id, $args = array() ) {
	$rafah_card_args = is_array( $args ) ? $args : array();
	$template        = locate_template( 'template-parts/project-card.php' );

	if ( $template ) {
		include $template;
		return;
	}

	// Fallback card if the Rafah theme is not active.
	$price = rafah_meta( 'price_from', $post_id );
	?>
	<article class="rafah-card rafah-project-card">
		<a class="rafah-card__media" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
			<?php echo get_the_post_thumbnail( $post_id, 'large', array( 'loading' => 'lazy' ) ); ?>
			<?php if ( rafah_meta( 'featured', $post_id ) ) : ?>
				<span class="rafah-badge rafah-badge--featured"><?php echo esc_html( rafah_text( 'featured' ) ); ?></span>
			<?php endif; ?>
			<?php echo rafah_project_status_overlay_html( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
		</a>
		<div class="rafah-card__body">
			<div class="rafah-card__location">
				<?php echo esc_html( trim( rafah_term_name( 'city', $post_id ) . ' · ' . rafah_term_name( 'district', $post_id ), ' ·' ) ); ?>
			</div>
			<h3 class="rafah-card__title">
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
			</h3>
			<?php if ( $price ) : ?>
				<div class="rafah-card__price">
					<span class="rafah-card__price-label"><?php echo esc_html( rafah_text( 'starting_from' ) ); ?></span>
					<span class="rafah-card__price-value"><?php echo esc_html( rafah_price( $price, $post_id ) ); ?></span>
				</div>
			<?php endif; ?>
			<div class="rafah-card__meta">
				<?php
				$beds = rafah_meta( 'bedrooms_from', $post_id );
				$area = rafah_meta( 'area_from', $post_id );
				if ( $beds ) {
					echo '<span>' . esc_html( $beds . ( rafah_meta( 'bedrooms_to', $post_id ) ? '–' . rafah_meta( 'bedrooms_to', $post_id ) : '' ) . ' ' . rafah_text( 'bedrooms' ) ) . '</span>';
				}
				if ( $area ) {
					echo '<span>' . esc_html( number_format( (float) $area ) . '+ ' . rafah_text( 'sqm' ) ) . '</span>';
				}
				?>
			</div>
			<a class="rafah-btn rafah-btn--ghost rafah-card__cta" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
				<?php echo esc_html( rafah_text( 'view_project' ) ); ?>
			</a>
		</div>
	</article>
	<?php
}

/**
 * Render a blog (WordPress Post) article card. Shared by the Rafah Blog
 * widget and any archive. Presentation lives in the theme's
 * template-parts/blog-card.php when present (WooCommerce-style override);
 * otherwise a minimal fallback is used so it still works with the theme off.
 *
 * @param int $post_id Post ID.
 */
function rafah_blog_card( $post_id ) {
	$template = locate_template( 'template-parts/blog-card.php' );

	if ( $template ) {
		include $template;
		return;
	}

	$cats = get_the_category( $post_id );
	$cat  = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
	?>
	<article class="rafah-blog-card">
		<a class="rafah-blog-card__media" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
			<?php echo get_the_post_thumbnail( $post_id, 'rafah-card', array( 'loading' => 'lazy' ) ); ?>
		</a>
		<div class="rafah-blog-card__body">
			<?php if ( $cat ) : ?><span class="rafah-blog-card__cat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
			<h3 class="rafah-blog-card__title"><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>
			<p class="rafah-blog-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), 20 ) ); ?></p>
			<div class="rafah-blog-card__foot">
				<span class="rafah-blog-card__date"><?php echo esc_html( get_the_date( '', $post_id ) ); ?></span>
				<a class="rafah-blog-card__more" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( rafah_text( 'read_more' ) ); ?></a>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Project cover attachment ID for a given render context.
 *
 * The image NEVER depends on project status. Cards/archive/related use the Card
 * Cover then the Featured Image; the single-project hero uses the Hero Cover
 * then the Featured Image. Status is shown as an overlay on top of the image,
 * not by swapping the image.
 *
 * @param int    $project_id Project.
 * @param string $context    'hero' uses the Hero Cover; anything else uses the
 *                           Card Cover. Both fall back to the Featured Image.
 * @return int Attachment ID, or 0 if none.
 */
function rafah_project_cover_id( $project_id, $context = 'card' ) {
	if ( 'hero' === $context ) {
		// Hero:  Hero Cover → Featured Image → placeholder.
		$hero = (int) rafah_meta( 'hero_cover', $project_id );
		if ( $hero ) {
			return $hero;
		}
	} else {
		// Cards/archive/related/sliders:  Card Cover → Featured Image → placeholder.
		$card = (int) rafah_meta( 'card_cover', $project_id );
		if ( $card ) {
			return $card;
		}
	}

	return (int) get_post_thumbnail_id( $project_id );
}

/**
 * Echo a project cover image, or a clean branded placeholder when there is no
 * image at all — so a missing cover never leaves an empty/broken area.
 *
 * @param int    $project_id Project.
 * @param string $size       Image size.
 * @param string $context    'hero' | 'card' | …
 * @param array  $attr       Extra <img> attributes.
 */
function rafah_project_cover( $project_id, $size = 'rafah-card', $context = 'card', $attr = array() ) {
	$id = rafah_project_cover_id( $project_id, $context );

	if ( $id ) {
		$attr = (array) $attr;
		// A high-priority (LCP) image must NOT be lazy-loaded — passing both
		// loading="lazy" and fetchpriority="high" triggers a WordPress
		// "called incorrectly" notice. Force eager loading in that case.
		$defaults = array( 'loading' => ( isset( $attr['fetchpriority'] ) && 'high' === $attr['fetchpriority'] ) ? 'eager' : 'lazy' );
		echo wp_get_attachment_image( $id, $size, false, array_merge( $defaults, $attr ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}

	echo rafah_project_cover_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Branded placeholder markup shown when a project has no cover image.
 *
 * @return string
 */
function rafah_project_cover_placeholder() {
	return '<span class="rafah-cover-ph" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V8l7-4 7 4v13"/><path d="M9 21v-5h6v5"/><path d="M9 11h.01M15 11h.01"/></svg></span>';
}

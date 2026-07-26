<?php
/**
 * Rafah Core — Field definitions for tabbed meta boxes.
 *
 * Field types supported by the renderer:
 * text, textarea, number, select, checkbox, date, url, tel, email,
 * media (single image), gallery (multiple images), file (any attachment),
 * post_select (dropdown of a post type), repeater (with sub-fields).
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Project field tabs.
 *
 * @return array
 */
function rafah_project_fields() {
	/**
	 * Filter the project field tabs. Add tabs/fields from a companion plugin
	 * or child theme — never edit this file on a live site.
	 *
	 * @param array $tabs Tab definitions.
	 */
	return apply_filters( 'rafah_project_fields', rafah_project_fields_config() );
}

/**
 * Raw (unfiltered) project field config.
 *
 * @return array
 */
function rafah_project_fields_config() {
	return array(
		'general'    => array(
			'label'  => __( 'General', 'rafah' ),
			'icon'   => 'dashicons-admin-home',
			'fields' => array(
				array( 'key' => 'subtitle', 'label' => __( 'Subtitle', 'rafah' ), 'type' => 'text', 'desc' => __( 'Short tagline shown under the project name.', 'rafah' ) ),
				array( 'key' => 'card_note', 'label' => __( 'Project Card Note', 'rafah' ), 'type' => 'text', 'desc' => __( 'Optional short line shown above the button on the project card (e.g. "Limited units left" / "وحدات محدودة"). Leave blank to hide.', 'rafah' ) ),
				array( 'key' => 'featured', 'label' => __( 'Featured Project', 'rafah' ), 'type' => 'checkbox', 'desc' => __( 'Featured projects appear first and get a badge.', 'rafah' ) ),
				array(
					'key'     => 'status',
					'label'   => __( 'Project Status', 'rafah' ),
					'type'    => 'select',
					'default' => 'available',
					'options' => rafah_project_status_options(),
					'desc'    => __( 'The single source of truth for status everywhere (cards, archive, filters, related projects). "Sold" and "Coming Soon" show an overlay on the card; "Available" shows none. It never changes the cover image.', 'rafah' ),
				),
				array(
					'key'             => 'show_status_badge',
					'label'           => __( 'Show Status Badge', 'rafah' ),
					'type'            => 'checkbox',
					'default'         => '1',
					'store_unchecked' => true,
					'desc'            => __( 'Show the status overlay (Sold / Coming Soon) on this project\'s card. On by default. Available never shows an overlay.', 'rafah' ),
				),
				array(
					'key'   => 'custom_badge_text',
					'label' => __( 'Custom Badge Text', 'rafah' ),
					'type'  => 'text',
					'desc'  => __( 'Optional. Overrides the overlay text. Leave blank to use the default status label (Sold / Coming Soon).', 'rafah' ),
				),
				array( 'key' => 'completion', 'label' => __( 'Completion %', 'rafah' ), 'type' => 'number', 'min' => 0, 'max' => 100 ),
				array( 'key' => 'delivery_date', 'label' => __( 'Delivery Date', 'rafah' ), 'type' => 'text', 'desc' => __( 'Example: Q4 2026 / الربع الرابع 2026', 'rafah' ) ),
				array( 'key' => 'developer', 'label' => __( 'Developer', 'rafah' ), 'type' => 'text' ),
				array( 'key' => 'consultant', 'label' => __( 'Consultant', 'rafah' ), 'type' => 'text' ),
				array( 'key' => 'contractor', 'label' => __( 'Construction Company', 'rafah' ), 'type' => 'text' ),
			),
		),
		'location'   => array(
			'label'  => __( 'Location', 'rafah' ),
			'icon'   => 'dashicons-location',
			'fields' => array(
				array( 'key' => 'address', 'label' => __( 'Address', 'rafah' ), 'type' => 'text' ),
				array( 'key' => 'map_url', 'label' => __( 'Google Maps Link', 'rafah' ), 'type' => 'url', 'desc' => __( 'Share link from Google Maps.', 'rafah' ) ),
				array( 'key' => 'lat', 'label' => __( 'Latitude', 'rafah' ), 'type' => 'text', 'desc' => __( 'Example: 24.7136', 'rafah' ) ),
				array( 'key' => 'lng', 'label' => __( 'Longitude', 'rafah' ), 'type' => 'text', 'desc' => __( 'Example: 46.6753', 'rafah' ) ),
			),
		),
		'pricing'    => array(
			'label'  => __( 'Pricing', 'rafah' ),
			'icon'   => 'dashicons-money-alt',
			'fields' => array(
				array( 'key' => 'price_from', 'label' => __( 'Starting Price', 'rafah' ), 'type' => 'number', 'min' => 0 ),
				array( 'key' => 'price_to', 'label' => __( 'Maximum Price', 'rafah' ), 'type' => 'number', 'min' => 0 ),
				array(
					'key' => 'currency', 'label' => __( 'Currency', 'rafah' ), 'type' => 'select', 'default' => 'SAR',
					'options' => array( 'SAR' => __( 'SAR — Saudi Riyal', 'rafah' ), 'USD' => 'USD', 'AED' => 'AED' ),
				),
				array(
					'key' => 'payment_plans', 'label' => __( 'Payment Plans', 'rafah' ), 'type' => 'repeater',
					'button' => __( 'Add Payment Plan', 'rafah' ),
					'sub_fields' => array(
						array( 'key' => 'title', 'label' => __( 'Plan Name', 'rafah' ), 'type' => 'text' ),
						array( 'key' => 'down_payment', 'label' => __( 'Down Payment %', 'rafah' ), 'type' => 'number', 'min' => 0, 'max' => 100 ),
						array( 'key' => 'description', 'label' => __( 'Details', 'rafah' ), 'type' => 'textarea' ),
					),
				),
				array( 'key' => 'mortgage_info', 'label' => __( 'Mortgage Information', 'rafah' ), 'type' => 'textarea', 'desc' => __( 'Bank financing options, supported banks, REDF eligibility…', 'rafah' ) ),
			),
		),
		'specs'      => array(
			'label'  => __( 'Specifications', 'rafah' ),
			'icon'   => 'dashicons-editor-table',
			'fields' => array(
				array( 'key' => 'area_from', 'label' => __( 'Min Unit Size (m²)', 'rafah' ), 'type' => 'number', 'min' => 0 ),
				array( 'key' => 'area_to', 'label' => __( 'Max Unit Size (m²)', 'rafah' ), 'type' => 'number', 'min' => 0 ),
				array( 'key' => 'unit_types', 'label' => __( 'Unit Types', 'rafah' ), 'type' => 'text', 'desc' => __( 'Example: 3BR Villa, 4BR Villa, Duplex', 'rafah' ) ),
				array( 'key' => 'bedrooms_from', 'label' => __( 'Bedrooms (min)', 'rafah' ), 'type' => 'number', 'min' => 0 ),
				array( 'key' => 'bedrooms_to', 'label' => __( 'Bedrooms (max)', 'rafah' ), 'type' => 'number', 'min' => 0 ),
				array( 'key' => 'bathrooms_from', 'label' => __( 'Bathrooms (min)', 'rafah' ), 'type' => 'number', 'min' => 0 ),
				array( 'key' => 'bathrooms_to', 'label' => __( 'Bathrooms (max)', 'rafah' ), 'type' => 'number', 'min' => 0 ),
				array( 'key' => 'parking', 'label' => __( 'Parking Spaces per Unit', 'rafah' ), 'type' => 'number', 'min' => 0 ),
				array( 'key' => 'buildings', 'label' => __( 'Number of Buildings', 'rafah' ), 'type' => 'number', 'min' => 0 ),
				array( 'key' => 'floors', 'label' => __( 'Number of Floors', 'rafah' ), 'type' => 'number', 'min' => 0 ),
				// Note: Total/Available unit counts are now calculated
				// automatically by the Units Manager and kept in sync.
			),
		),
		'media'      => array(
			'label'  => __( 'Media', 'rafah' ),
			'icon'   => 'dashicons-format-gallery',
			'fields' => array(
				array( 'key' => 'card_cover', 'label' => __( 'Project Card Cover', 'rafah' ), 'type' => 'media', 'desc' => __( 'Optional. Shown on cards, archive, related projects and sliders. Falls back to the Featured Image, then a placeholder.', 'rafah' ) ),
				array( 'key' => 'hero_cover', 'label' => __( 'Project Hero Cover', 'rafah' ), 'type' => 'media', 'desc' => __( 'Optional. Shown on the single-project hero only. Falls back to the Featured Image, then a placeholder.', 'rafah' ) ),
				array( 'key' => 'gallery', 'label' => __( 'Photo Gallery', 'rafah' ), 'type' => 'gallery' ),
				array(
					'key'     => 'gallery_position',
					'label'   => __( 'Gallery Position', 'rafah' ),
					'type'    => 'select',
					'default' => 'before',
					'desc'    => __( 'Where the Photo Gallery appears on the single project page (rendered by the theme placement hooks).', 'rafah' ),
					'options' => array(
						'before' => __( 'Before Units Table', 'rafah' ),
						'after'  => __( 'After Units Table', 'rafah' ),
						'hidden' => __( 'Hidden', 'rafah' ),
					),
				),
				array( 'key' => 'video_url', 'label' => __( 'Video URL', 'rafah' ), 'type' => 'url', 'desc' => __( 'YouTube or Vimeo link.', 'rafah' ) ),
				array( 'key' => 'tour_url', 'label' => __( '360° Tour URL', 'rafah' ), 'type' => 'url', 'desc' => __( 'Matterport, Kuula, or similar embed link.', 'rafah' ) ),
				array( 'key' => 'brochure', 'label' => __( 'PDF Brochure', 'rafah' ), 'type' => 'file' ),
			),
		),
		'floorplans' => array(
			'label'  => __( 'Floor Plans', 'rafah' ),
			'icon'   => 'dashicons-layout',
			'fields' => array(
				array(
					'key' => 'floor_plans', 'label' => __( 'Floor Plans', 'rafah' ), 'type' => 'repeater',
					'button' => __( 'Add Floor Plan', 'rafah' ),
					'sub_fields' => array(
						array( 'key' => 'title', 'label' => __( 'Plan Name', 'rafah' ), 'type' => 'text' ),
						array( 'key' => 'image', 'label' => __( 'Plan Image', 'rafah' ), 'type' => 'media' ),
						array( 'key' => 'area', 'label' => __( 'Area (m²)', 'rafah' ), 'type' => 'number', 'min' => 0 ),
						array( 'key' => 'bedrooms', 'label' => __( 'Bedrooms', 'rafah' ), 'type' => 'number', 'min' => 0 ),
						array( 'key' => 'price', 'label' => __( 'Price', 'rafah' ), 'type' => 'number', 'min' => 0 ),
						array( 'key' => 'pdf', 'label' => __( 'PDF File', 'rafah' ), 'type' => 'file' ),
					),
				),
			),
		),
		'nearby'     => array(
			'label'  => __( 'Nearby Places', 'rafah' ),
			'icon'   => 'dashicons-location-alt',
			'fields' => array(
				array(
					'key' => 'nearby', 'label' => __( 'Nearby Places', 'rafah' ), 'type' => 'repeater',
					'button' => __( 'Add Place', 'rafah' ),
					'sub_fields' => array(
						array(
							'key' => 'category', 'label' => __( 'Category', 'rafah' ), 'type' => 'select',
							'options' => array(
								'school'    => __( 'School', 'rafah' ),
								'hospital'  => __( 'Hospital', 'rafah' ),
								'mosque'    => __( 'Mosque', 'rafah' ),
								'shopping'  => __( 'Shopping', 'rafah' ),
								'transport' => __( 'Transportation', 'rafah' ),
								'other'     => __( 'Other', 'rafah' ),
							),
						),
						array( 'key' => 'name', 'label' => __( 'Place Name', 'rafah' ), 'type' => 'text' ),
						array( 'key' => 'distance', 'label' => __( 'Distance / Time', 'rafah' ), 'type' => 'text', 'desc' => __( 'Example: 5 دقائق / 2 كم', 'rafah' ) ),
					),
				),
			),
		),
		'downloads'  => array(
			'label'  => __( 'Downloads', 'rafah' ),
			'icon'   => 'dashicons-download',
			'fields' => array(
				array(
					'key' => 'downloads', 'label' => __( 'Downloadable Files', 'rafah' ), 'type' => 'repeater',
					'button' => __( 'Add File', 'rafah' ),
					'sub_fields' => array(
						array( 'key' => 'title', 'label' => __( 'File Title', 'rafah' ), 'type' => 'text' ),
						array( 'key' => 'file', 'label' => __( 'File', 'rafah' ), 'type' => 'file' ),
					),
				),
			),
		),
		'contact'    => array(
			'label'  => __( 'Contact & Agent', 'rafah' ),
			'icon'   => 'dashicons-phone',
			'fields' => array(
				array( 'key' => 'agent_id', 'label' => __( 'Assigned Agent', 'rafah' ), 'type' => 'post_select', 'post_type' => 'agent' ),
				array( 'key' => 'phone', 'label' => __( 'Sales Phone', 'rafah' ), 'type' => 'tel', 'desc' => __( 'Example: 920000000 or 0555555555', 'rafah' ) ),
				array( 'key' => 'whatsapp', 'label' => __( 'WhatsApp Number', 'rafah' ), 'type' => 'tel' ),
				array( 'key' => 'form_shortcode', 'label' => __( 'Lead Form Shortcode', 'rafah' ), 'type' => 'text', 'desc' => __( 'Paste a Fluent Forms shortcode, e.g. [fluentform id="1"]. Leave empty to hide the form.', 'rafah' ) ),
			),
		),
	);
}

/**
 * Agent field tabs.
 *
 * @return array
 */
function rafah_agent_fields() {
	/** Documented in rafah_project_fields(). */
	return apply_filters( 'rafah_agent_fields', rafah_agent_fields_config() );
}

/**
 * Raw (unfiltered) agent field config.
 *
 * @return array
 */
function rafah_agent_fields_config() {
	return array(
		'profile' => array(
			'label'  => __( 'Profile', 'rafah' ),
			'icon'   => 'dashicons-id',
			'fields' => array(
				array( 'key' => 'position', 'label' => __( 'Position / Title', 'rafah' ), 'type' => 'text', 'desc' => __( 'Example: مستشار مبيعات أول', 'rafah' ) ),
				array( 'key' => 'experience_years', 'label' => __( 'Years of Experience', 'rafah' ), 'type' => 'number', 'min' => 0 ),
				array( 'key' => 'license_no', 'label' => __( 'FAL License Number', 'rafah' ), 'type' => 'text', 'desc' => __( 'REGA / فال license — builds trust with buyers.', 'rafah' ) ),
				array( 'key' => 'languages', 'label' => __( 'Languages', 'rafah' ), 'type' => 'text', 'desc' => __( 'Example: العربية، English', 'rafah' ) ),
				array( 'key' => 'specialties', 'label' => __( 'Specialties', 'rafah' ), 'type' => 'text', 'desc' => __( 'Example: فلل شمال الرياض، الاستثمار السكني', 'rafah' ) ),
			),
		),
		'contact' => array(
			'label'  => __( 'Contact', 'rafah' ),
			'icon'   => 'dashicons-phone',
			'fields' => array(
				array( 'key' => 'phone', 'label' => __( 'Phone', 'rafah' ), 'type' => 'tel' ),
				array( 'key' => 'whatsapp', 'label' => __( 'WhatsApp', 'rafah' ), 'type' => 'tel' ),
				array( 'key' => 'email', 'label' => __( 'Email', 'rafah' ), 'type' => 'email' ),
				array( 'key' => 'meeting_url', 'label' => __( 'Schedule Meeting URL', 'rafah' ), 'type' => 'url', 'desc' => __( 'Calendly / LatePoint booking link.', 'rafah' ) ),
				array( 'key' => 'form_shortcode', 'label' => __( 'Lead Form Shortcode', 'rafah' ), 'type' => 'text', 'desc' => __( 'Fluent Forms shortcode for this agent\'s landing page.', 'rafah' ) ),
			),
		),
		'social'  => array(
			'label'  => __( 'Social Links', 'rafah' ),
			'icon'   => 'dashicons-share',
			'fields' => array(
				array( 'key' => 'social_x', 'label' => 'X (Twitter)', 'type' => 'url' ),
				array( 'key' => 'social_instagram', 'label' => 'Instagram', 'type' => 'url' ),
				array( 'key' => 'social_linkedin', 'label' => 'LinkedIn', 'type' => 'url' ),
				array( 'key' => 'social_snapchat', 'label' => 'Snapchat', 'type' => 'url' ),
				array( 'key' => 'social_tiktok', 'label' => 'TikTok', 'type' => 'url' ),
			),
		),
	);
}

/**
 * Testimonial fields (single tab).
 *
 * @return array
 */
function rafah_testimonial_fields() {
	/** Documented in rafah_project_fields(). */
	return apply_filters( 'rafah_testimonial_fields', rafah_testimonial_fields_config() );
}

/**
 * Raw (unfiltered) testimonial field config.
 *
 * @return array
 */
function rafah_testimonial_fields_config() {
	return array(
		'details' => array(
			'label'  => __( 'Details', 'rafah' ),
			'icon'   => 'dashicons-format-quote',
			'fields' => array(
				array( 'key' => 'client_role', 'label' => __( 'Client Role / Description', 'rafah' ), 'type' => 'text', 'desc' => __( 'Example: مالك وحدة في مشروع رفاه ريزيدنس', 'rafah' ) ),
				array(
					'key' => 'rating', 'label' => __( 'Rating', 'rafah' ), 'type' => 'select', 'default' => '5',
					'options' => array( '5' => '★★★★★', '4' => '★★★★', '3' => '★★★' ),
				),
				array( 'key' => 'project_id', 'label' => __( 'Related Project', 'rafah' ), 'type' => 'post_select', 'post_type' => 'project' ),
			),
		),
	);
}

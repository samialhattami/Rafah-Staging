<?php
/**
 * Rafah Core — Structured data (JSON-LD).
 * Organization, Residence/RealEstateListing for projects, RealEstateAgent for agents,
 * BreadcrumbList everywhere. Designed to complement (not duplicate) Rank Math.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Schema {

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'output' ), 5 );
	}

	/**
	 * Is a dedicated SEO plugin (Rank Math / Yoast) active?
	 *
	 * Those plugins emit site-wide Organization + BreadcrumbList schema. When
	 * one is present we skip Rafah's copies of those to avoid duplicate graphs,
	 * and keep only Rafah's domain-specific listing schema (Project / Agent),
	 * which they don't produce.
	 *
	 * @return bool
	 */
	private static function seo_plugin_active() {
		return defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' );
	}

	public static function output() {
		/**
		 * Master switch for all Rafah-emitted schema.
		 *
		 * @param bool $enabled Default true.
		 */
		if ( ! apply_filters( 'rafah_schema_enabled', true ) ) {
			return;
		}

		/**
		 * Whether an SEO plugin owns Organization + Breadcrumb schema. When true
		 * Rafah skips those pieces to avoid duplication. Override to force Rafah
		 * to emit them regardless.
		 *
		 * @param bool $owned Detected from Rank Math / Yoast.
		 */
		$seo_owns_org = apply_filters( 'rafah_schema_seo_owns_org_breadcrumb', self::seo_plugin_active() );

		$graph = array();

		if ( ! $seo_owns_org ) {
			$graph[] = self::organization();
		}

		if ( is_singular( 'project' ) ) {
			$graph[] = self::project( get_the_ID() );
			if ( ! $seo_owns_org ) {
				$graph[] = self::breadcrumbs(
					array(
						array( rafah_text( 'home' ), home_url( '/' ) ),
						array( rafah_text( 'projects' ), get_post_type_archive_link( 'project' ) ),
						array( get_the_title(), get_permalink() ),
					)
				);
			}
		} elseif ( is_singular( 'agent' ) ) {
			$graph[] = self::agent( get_the_ID() );
			if ( ! $seo_owns_org ) {
				$graph[] = self::breadcrumbs(
					array(
						array( rafah_text( 'home' ), home_url( '/' ) ),
						array( rafah_text( 'agents' ), get_post_type_archive_link( 'agent' ) ),
						array( get_the_title(), get_permalink() ),
					)
				);
			}
		} elseif ( is_post_type_archive( 'project' ) && ! $seo_owns_org ) {
			$graph[] = self::breadcrumbs(
				array(
					array( rafah_text( 'home' ), home_url( '/' ) ),
					array( rafah_text( 'projects' ), get_post_type_archive_link( 'project' ) ),
				)
			);
		}

		/**
		 * Filter the final schema @graph before output.
		 *
		 * @param array $graph Array of schema.org node arrays.
		 */
		$graph = array_filter( (array) apply_filters( 'rafah_schema_graph', $graph ) );

		if ( empty( $graph ) ) {
			return;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode(
				array(
					'@context' => 'https://schema.org',
					'@graph'   => array_values( $graph ),
				),
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			)
		);
	}

	private static function organization() {
		$logo = get_theme_mod( 'custom_logo' ) ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : '';

		return array(
			'@type'  => 'RealEstateAgent',
			'@id'    => home_url( '/#organization' ),
			'name'   => get_bloginfo( 'name' ),
			'url'    => home_url( '/' ),
			'logo'   => $logo,
			'areaServed' => array(
				'@type' => 'Country',
				'name'  => 'Saudi Arabia',
			),
		);
	}

	private static function project( $post_id ) {
		$price_from = rafah_meta( 'price_from', $post_id );
		$price_to   = rafah_meta( 'price_to', $post_id );
		$lat        = rafah_meta( 'lat', $post_id );
		$lng        = rafah_meta( 'lng', $post_id );

		$schema = array(
			'@type'       => 'Residence',
			'@id'         => get_permalink( $post_id ) . '#residence',
			'name'        => get_the_title( $post_id ),
			'url'         => get_permalink( $post_id ),
			'description' => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
			'image'       => get_the_post_thumbnail_url( $post_id, 'full' ),
			'address'     => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => rafah_meta( 'address', $post_id ),
				'addressLocality' => rafah_term_name( 'city', $post_id ),
				'addressRegion'   => rafah_term_name( 'district', $post_id ),
				'addressCountry'  => 'SA',
			),
		);

		if ( $lat && $lng ) {
			$schema['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $lat,
				'longitude' => (float) $lng,
			);
		}

		$area_from = rafah_meta( 'area_from', $post_id );
		if ( $area_from ) {
			$schema['floorSize'] = array(
				'@type'    => 'QuantitativeValue',
				'value'    => (float) $area_from,
				'unitCode' => 'MTK',
			);
		}

		$beds = rafah_meta( 'bedrooms_from', $post_id );
		if ( $beds ) {
			$schema['numberOfRooms'] = (int) $beds;
		}

		if ( $price_from ) {
			$offer = array(
				'@type'         => 'Offer',
				'price'         => (float) $price_from,
				'priceCurrency' => rafah_meta( 'currency', $post_id ) ?: 'SAR',
				'availability'  => 'https://schema.org/InStock',
				'url'           => get_permalink( $post_id ),
			);

			if ( $price_to ) {
				$offer['priceSpecification'] = array(
					'@type'         => 'PriceSpecification',
					'minPrice'      => (float) $price_from,
					'maxPrice'      => (float) $price_to,
					'priceCurrency' => rafah_meta( 'currency', $post_id ) ?: 'SAR',
				);
			}

			$schema['offers'] = $offer;
		}

		return $schema;
	}

	private static function agent( $post_id ) {
		$schema = array(
			'@type'    => 'Person',
			'@id'      => get_permalink( $post_id ) . '#agent',
			'name'     => get_the_title( $post_id ),
			'url'      => get_permalink( $post_id ),
			'image'    => get_the_post_thumbnail_url( $post_id, 'large' ),
			'jobTitle' => rafah_meta( 'position', $post_id ),
			'worksFor' => array( '@id' => home_url( '/#organization' ) ),
		);

		$phone = rafah_meta( 'phone', $post_id );
		if ( $phone ) {
			$schema['telephone'] = $phone;
		}

		$email = rafah_meta( 'email', $post_id );
		if ( $email ) {
			$schema['email'] = $email;
		}

		$same_as = array_filter(
			array(
				rafah_meta( 'social_x', $post_id ),
				rafah_meta( 'social_instagram', $post_id ),
				rafah_meta( 'social_linkedin', $post_id ),
				rafah_meta( 'social_snapchat', $post_id ),
				rafah_meta( 'social_tiktok', $post_id ),
			)
		);

		if ( $same_as ) {
			$schema['sameAs'] = array_values( $same_as );
		}

		return $schema;
	}

	private static function breadcrumbs( $items ) {
		$list = array();

		foreach ( $items as $i => $item ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $item[0],
				'item'     => $item[1],
			);
		}

		return array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $list,
		);
	}
}

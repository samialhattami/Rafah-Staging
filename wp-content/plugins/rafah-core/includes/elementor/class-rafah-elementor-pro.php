<?php
/**
 * Rafah Core — Elementor PRO integration (ADDITIVE, guarded).
 *
 * This file is loaded ONLY when Elementor Pro is active (see
 * Rafah_Elementor::hooks() → Rafah_Elementor::has_pro()). It is the single home
 * for every Pro-only capability:
 *
 *   • Dynamic Tags     — bind Rafah project fields to native Elementor widgets
 *                        (Heading, Text, Button, Image, Icon…).           [Phase 4]
 *   • Query Providers  — power Elementor Pro Loop Grid with Rafah queries
 *                        (projects, featured, latest, related, units).    [Phase 3]
 *   • Theme Locations  — let Pro Theme Builder own header/footer/single/
 *                        archive templates, with the theme as fallback.   [Phase 5]
 *
 * GRACEFUL DEGRADATION CONTRACT
 *   Nothing here is required for the site to work. If Pro is absent, expired, or
 *   deactivated this file never loads, so none of these hooks are registered and
 *   the site runs on Elementor Free widgets + the theme's PHP templates. Pro
 *   only ever ENHANCES. Do not move any Free-capable feature into this file.
 *
 * Each registration is additionally self-guarded (interface/class_exists) so a
 * future Pro API change can only disable that one feature, never fatal the site.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Elementor_Pro {

	/** Wired from Rafah_Elementor::hooks() only when Elementor Pro is active. */
	public static function init() {
		// Phase 3 — Loop Grid query providers.
		add_action( 'elementor/query/rafah_projects', array( __CLASS__, 'query_projects' ) );
		add_action( 'elementor/query/rafah_featured', array( __CLASS__, 'query_featured' ) );
		add_action( 'elementor/query/rafah_latest', array( __CLASS__, 'query_latest' ) );
		add_action( 'elementor/query/rafah_related', array( __CLASS__, 'query_related' ) );
		add_action( 'elementor/query/rafah_units', array( __CLASS__, 'query_units' ) );

		// Phase 4 — Dynamic Tags for Rafah project fields.
		add_action( 'elementor/dynamic_tags/register', array( __CLASS__, 'register_dynamic_tags' ) );

		// Phase 5 — Theme Builder locations (theme remains the fallback).
		add_action( 'elementor/theme/register_locations', array( __CLASS__, 'register_locations' ) );
	}

	/* --------------------------------------------------------------------- *
	 * Phase 3 — Query providers (filled in Phase 3).
	 * Each receives a WP_Query and sets Rafah query vars. Empty for now so a
	 * Loop Grid pointed at a Rafah query simply returns the default results
	 * until Phase 3 lands — never an error.
	 * --------------------------------------------------------------------- */
	public static function query_projects( $query ) {}
	public static function query_featured( $query ) {}
	public static function query_latest( $query ) {}
	public static function query_related( $query ) {}
	public static function query_units( $query ) {}

	/* --------------------------------------------------------------------- *
	 * Phase 4 — Dynamic Tags (filled in Phase 4).
	 * Will register one tag per Rafah field via $dynamic_tags->register(), each
	 * in the "Rafah" group, typed (text/number/url/image/media). No-op until then.
	 * --------------------------------------------------------------------- */
	public static function register_dynamic_tags( $dynamic_tags ) {}

	/* --------------------------------------------------------------------- *
	 * Phase 5 — Theme Builder locations (filled in Phase 5).
	 * Will register header/footer/single/archive locations so Pro Theme Builder
	 * can own them, gated by a Customizer "Header/Footer source" switch, with the
	 * theme's PHP render as the guaranteed fallback. No-op until then.
	 * --------------------------------------------------------------------- */
	public static function register_locations( $manager ) {}
}

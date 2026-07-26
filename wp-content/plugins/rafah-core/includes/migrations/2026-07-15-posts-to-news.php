<?php
/**
 * Migration: convert legacy published blog Posts (company announcements) into
 * the News CPT so they appear in the News section. Idempotent — converts only
 * the published `post` items that still exist; once none remain it is a no-op.
 * Respects the original one-off guard (`rafah_posts_to_news_v1`).
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

return array(
	'id'          => '2026-07-15-posts-to-news',
	'description' => 'Convert existing published Posts to the News CPT.',
	'run'         => function () {
		if ( get_option( 'rafah_posts_to_news_v1' ) ) {
			return; // Already handled by the earlier one-off.
		}

		$ids = get_posts( array(
			'post_type'   => 'post',
			'post_status' => array( 'publish' ),
			'numberposts' => -1,
			'fields'      => 'ids',
		) );

		foreach ( $ids as $pid ) {
			set_post_type( $pid, 'news' );
		}

		update_option( 'rafah_posts_to_news_v1', 1 );
		flush_rewrite_rules( false );
	},
);

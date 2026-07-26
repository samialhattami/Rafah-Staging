<?php
/**
 * Rafah Core — Editor experience.
 *
 * Gives News and Blog (Posts) a clean, standard WordPress editing screen —
 * the built-in Classic editor (title, media-enabled content/description,
 * featured image, excerpt, taxonomy + SEO meta boxes) — instead of the block
 * editor with its third-party promo panels. No extra plugin: this simply
 * opts these post types out of the block editor, and WordPress core falls
 * back to the Classic editor it still ships.
 *
 * Reversible/filterable: change the list via `rafah_classic_editor_post_types`.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Editor {

	public static function init() {
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'classic_editor' ), 100, 2 );
	}

	/**
	 * Force the Classic editor for the chosen post types.
	 *
	 * @param bool   $use_block Whether to use the block editor.
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public static function classic_editor( $use_block, $post_type ) {
		$types = apply_filters( 'rafah_classic_editor_post_types', array( 'news', 'post' ) );

		return in_array( $post_type, (array) $types, true ) ? false : $use_block;
	}
}

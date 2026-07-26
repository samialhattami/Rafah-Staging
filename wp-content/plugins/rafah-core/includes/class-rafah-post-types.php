<?php
/**
 * Rafah Core — Custom Post Types.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Post_Types {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		// -------------------------------------------------- Projects.
		register_post_type(
			'project',
			/** Filter Project CPT args (e.g. change the archive slug) without editing the plugin. */
			apply_filters( 'rafah_project_cpt_args', array(
				'labels'       => array(
					'name'               => __( 'Projects', 'rafah' ),
					'singular_name'      => __( 'Project', 'rafah' ),
					'add_new'            => __( 'Add New Project', 'rafah' ),
					'add_new_item'       => __( 'Add New Project', 'rafah' ),
					'edit_item'          => __( 'Edit Project', 'rafah' ),
					'new_item'           => __( 'New Project', 'rafah' ),
					'view_item'          => __( 'View Project', 'rafah' ),
					'search_items'       => __( 'Search Projects', 'rafah' ),
					'not_found'          => __( 'No projects found', 'rafah' ),
					'not_found_in_trash' => __( 'No projects found in Trash', 'rafah' ),
					'all_items'          => __( 'All Projects', 'rafah' ),
					'menu_name'          => __( 'Projects', 'rafah' ),
				),
				'public'       => true,
				'menu_position' => 5,
				'menu_icon'    => 'dashicons-building',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'projects', 'with_front' => false ),
				'show_in_rest' => true,
			) )
		);

		// -------------------------------------------------- Agents.
		register_post_type(
			'agent',
			apply_filters( 'rafah_agent_cpt_args', array(
				'labels'       => array(
					'name'               => __( 'Agents', 'rafah' ),
					'singular_name'      => __( 'Agent', 'rafah' ),
					'add_new'            => __( 'Add New Agent', 'rafah' ),
					'add_new_item'       => __( 'Add New Agent', 'rafah' ),
					'edit_item'          => __( 'Edit Agent', 'rafah' ),
					'view_item'          => __( 'View Agent', 'rafah' ),
					'search_items'       => __( 'Search Agents', 'rafah' ),
					'not_found'          => __( 'No agents found', 'rafah' ),
					'all_items'          => __( 'All Agents', 'rafah' ),
					'menu_name'          => __( 'Agents', 'rafah' ),
				),
				'public'       => true,
				'menu_position' => 6,
				'menu_icon'    => 'dashicons-groups',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'agents', 'with_front' => false ),
				'show_in_rest' => true,
			) )
		);

		// -------------------------------------------------- Testimonials.
		register_post_type(
			'testimonial',
			apply_filters( 'rafah_testimonial_cpt_args', array(
				'labels'       => array(
					'name'          => __( 'Testimonials', 'rafah' ),
					'singular_name' => __( 'Testimonial', 'rafah' ),
					'add_new_item'  => __( 'Add New Testimonial', 'rafah' ),
					'edit_item'     => __( 'Edit Testimonial', 'rafah' ),
					'menu_name'     => __( 'Testimonials', 'rafah' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'menu_position'       => 7,
				'menu_icon'           => 'dashicons-format-quote',
				'supports'            => array( 'title', 'editor', 'thumbnail' ),
				'exclude_from_search' => true,
				'show_in_rest'        => true,
			) )
		);

		// -------------------------------------------------- News (company announcements).
		// Separate from the Blog (WordPress Posts): News = press releases,
		// partnerships, exhibitions, achievements, project milestones.
		register_post_type(
			'news',
			apply_filters( 'rafah_news_cpt_args', array(
				'labels'       => array(
					'name'               => __( 'News', 'rafah' ),
					'singular_name'      => __( 'News Item', 'rafah' ),
					'add_new'            => __( 'Add News', 'rafah' ),
					'add_new_item'       => __( 'Add News Item', 'rafah' ),
					'edit_item'          => __( 'Edit News Item', 'rafah' ),
					'new_item'           => __( 'New News Item', 'rafah' ),
					'view_item'          => __( 'View News Item', 'rafah' ),
					'search_items'       => __( 'Search News', 'rafah' ),
					'not_found'          => __( 'No news found', 'rafah' ),
					'not_found_in_trash' => __( 'No news found in Trash', 'rafah' ),
					'all_items'          => __( 'All News', 'rafah' ),
					'menu_name'          => __( 'News', 'rafah' ),
				),
				'public'        => true,
				'menu_position' => 8,
				'menu_icon'     => 'dashicons-megaphone',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
				'has_archive'   => true,
				'rewrite'       => array( 'slug' => 'news', 'with_front' => false ),
				'show_in_rest'  => true,
			) )
		);

		register_taxonomy(
			'news_category',
			'news',
			apply_filters( 'rafah_news_category_args', array(
				'labels'            => array(
					'name'          => __( 'News Categories', 'rafah' ),
					'singular_name' => __( 'News Category', 'rafah' ),
					'menu_name'     => __( 'Categories', 'rafah' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'news-category', 'with_front' => false ),
			) )
		);

		self::maybe_flush_news();
	}

	/**
	 * Flush rewrite rules once after the News CPT is introduced, so the
	 * /news/ archive works without a manual Settings → Permalinks re-save.
	 * Gated by an option so it runs a single time after the update.
	 */
	private static function maybe_flush_news() {
		if ( get_option( 'rafah_news_rewrite_v1' ) ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( 'rafah_news_rewrite_v1', 1 );
	}
}

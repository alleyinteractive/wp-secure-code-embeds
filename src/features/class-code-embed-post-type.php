<?php
/**
 * Code_Embed_Post_Type class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds\Features;

use Alley\WP\Secure_Code_Embeds\Code_Embeds_REST_Controller;
use Alley\WP\Types\Feature;
use WP_Block_Editor_Context;
use WP_Post;

/**
 * Registers the private post type that stores code embed entries, and the meta
 * that holds each entry's head and body code.
 *
 * Every capability for the post type, including the per-post `edit_post`,
 * `read_post`, and `delete_post` checks, maps to the single
 * `manage_secure_code_embeds` capability. {@see Authoring_Capability} decides
 * who has it.
 */
final class Code_Embed_Post_Type implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'init', $this->register_post_type( ... ) );
		add_action( 'init', $this->register_meta( ... ) );
		add_filter( 'allowed_block_types_all', $this->filter_allowed_block_types( ... ), 10, 2 );
		add_filter( '_wp_post_revision_fields', $this->filter_revision_fields( ... ), 10, 2 );
	}

	/**
	 * Register the post type.
	 */
	public function register_post_type(): void {
		register_post_type(
			'secure_code_embed',
			[
				'labels'                => [
					'name'                     => __( 'Code Embeds', 'wp-secure-code-embeds' ),
					'singular_name'            => __( 'Code Embed', 'wp-secure-code-embeds' ),
					'add_new'                  => __( 'Add New Code Embed', 'wp-secure-code-embeds' ),
					'add_new_item'             => __( 'Add New Code Embed', 'wp-secure-code-embeds' ),
					'edit_item'                => __( 'Edit Code Embed', 'wp-secure-code-embeds' ),
					'new_item'                 => __( 'New Code Embed', 'wp-secure-code-embeds' ),
					'view_item'                => __( 'View Code Embed', 'wp-secure-code-embeds' ),
					'view_items'               => __( 'View Code Embeds', 'wp-secure-code-embeds' ),
					'search_items'             => __( 'Search Code Embeds', 'wp-secure-code-embeds' ),
					'not_found'                => __( 'No code embeds found.', 'wp-secure-code-embeds' ),
					'not_found_in_trash'       => __( 'No code embeds found in Trash.', 'wp-secure-code-embeds' ),
					'all_items'                => __( 'All Code Embeds', 'wp-secure-code-embeds' ),
					'archives'                 => __( 'Code Embed Archives', 'wp-secure-code-embeds' ),
					'attributes'               => __( 'Code Embed Attributes', 'wp-secure-code-embeds' ),
					'filter_items_list'        => __( 'Filter code embeds list', 'wp-secure-code-embeds' ),
					'items_list_navigation'    => __( 'Code embeds list navigation', 'wp-secure-code-embeds' ),
					'items_list'               => __( 'Code embeds list', 'wp-secure-code-embeds' ),
					'item_published'           => __( 'Code embed published.', 'wp-secure-code-embeds' ),
					'item_published_privately' => __( 'Code embed published privately.', 'wp-secure-code-embeds' ),
					'item_reverted_to_draft'   => __( 'Code embed reverted to draft.', 'wp-secure-code-embeds' ),
					'item_scheduled'           => __( 'Code embed scheduled.', 'wp-secure-code-embeds' ),
					'item_updated'             => __( 'Code embed updated.', 'wp-secure-code-embeds' ),
					'item_trashed'             => __( 'Code embed trashed.', 'wp-secure-code-embeds' ),
					'menu_name'                => __( 'Code Embeds', 'wp-secure-code-embeds' ),
				],
				'description'           => __( 'Raw HTML, CSS, and JavaScript that administrators author and editors place on pages.', 'wp-secure-code-embeds' ),
				'public'                => false,
				'publicly_queryable'    => false,
				'exclude_from_search'   => true,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'show_in_nav_menus'     => false,
				'show_in_admin_bar'     => false,
				'show_in_rest'          => true,
				'rest_base'             => 'secure-code-embeds',
				'rest_controller_class' => Code_Embeds_REST_Controller::class,
				'menu_icon'             => 'dashicons-editor-code',
				'hierarchical'          => false,
				'has_archive'           => false,
				'rewrite'               => false,
				'query_var'             => false,
				'delete_with_user'      => false,
				'supports'              => [ 'title', 'editor', 'revisions', 'custom-fields' ],
				'map_meta_cap'          => false,
				'capabilities'          => [
					'read'                   => 'read',
					'edit_post'              => 'manage_secure_code_embeds',
					'read_post'              => 'manage_secure_code_embeds',
					'delete_post'            => 'manage_secure_code_embeds',
					'create_posts'           => 'manage_secure_code_embeds',
					'edit_posts'             => 'manage_secure_code_embeds',
					'edit_others_posts'      => 'manage_secure_code_embeds',
					'edit_private_posts'     => 'manage_secure_code_embeds',
					'edit_published_posts'   => 'manage_secure_code_embeds',
					'publish_posts'          => 'manage_secure_code_embeds',
					'read_private_posts'     => 'manage_secure_code_embeds',
					'delete_posts'           => 'manage_secure_code_embeds',
					'delete_others_posts'    => 'manage_secure_code_embeds',
					'delete_private_posts'   => 'manage_secure_code_embeds',
					'delete_published_posts' => 'manage_secure_code_embeds',
				],
				'template'              => [
					[ 'wp-secure-code-embeds/code-embed-fields' ],
				],
				'template_lock'         => 'all',
			]
		);
	}

	/**
	 * Register the meta that holds each entry's head and body code.
	 *
	 * The code is stored exactly as written. It is only exposed over REST in
	 * the `edit` context, which requires the authoring capability.
	 */
	public function register_meta(): void {
		$meta = [
			'secure_code_embed_head' => [
				'label'       => __( 'Head content', 'wp-secure-code-embeds' ),
				'description' => __( 'Raw code output in the document head of pages that include this code embed. Not sanitized or escaped.', 'wp-secure-code-embeds' ),
			],
			'secure_code_embed_body' => [
				'label'       => __( 'Body content', 'wp-secure-code-embeds' ),
				'description' => __( 'Raw code output where the Code Embed block is placed. Not sanitized or escaped.', 'wp-secure-code-embeds' ),
			],
		];

		foreach ( $meta as $key => $args ) {
			register_post_meta(
				'secure_code_embed',
				$key,
				[
					...$args,
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'revisions_enabled' => true,
					'show_in_rest'      => [
						'schema' => [
							'context' => [ 'edit' ],
						],
					],
				],
			);
		}
	}

	/**
	 * Allow only the fields block when editing an entry.
	 *
	 * @param mixed $allowed_block_types Allowed block types, or true for all.
	 * @param mixed $context             Block editor context.
	 */
	public function filter_allowed_block_types( mixed $allowed_block_types, mixed $context ): mixed {
		if (
			$context instanceof WP_Block_Editor_Context
			&& $context->post instanceof WP_Post
			&& 'secure_code_embed' === $context->post->post_type
		) {
			return [ 'wp-secure-code-embeds/code-embed-fields' ];
		}

		return $allowed_block_types;
	}

	/**
	 * Show the head and body code on the revisions screen.
	 *
	 * Core stores and restores revisioned meta on its own. This only adds the
	 * meta to the comparison screen, using the labels it was registered with,
	 * so changes can be reviewed before restoring.
	 *
	 * @param mixed $fields Revision fields, keyed by field name.
	 * @param mixed $post   The post being revisioned, as an object or array.
	 */
	public function filter_revision_fields( mixed $fields, mixed $post = null ): mixed {
		if ( ! is_array( $fields ) ) {
			return $fields;
		}

		$post_type = match ( true ) {
			$post instanceof WP_Post => $post->post_type,
			is_array( $post )        => $post['post_type'] ?? '',
			default                  => '',
		};

		foreach ( get_registered_meta_keys( 'post', 'secure_code_embed' ) as $key => $args ) {
			if ( empty( $args['revisions_enabled'] ) || ! is_string( $args['label'] ?? null ) ) {
				continue;
			}

			// Core stores the filtered fields in a static variable that is reused
			// for later posts, so remove the fields for other post types.
			if ( 'secure_code_embed' === $post_type ) {
				$fields[ $key ] = $args['label'];
			} else {
				unset( $fields[ $key ] );
			}
		}

		return $fields;
	}
}

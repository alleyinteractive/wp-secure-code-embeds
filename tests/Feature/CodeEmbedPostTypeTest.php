<?php
/**
 * Secure Code Embeds Tests: Code Embed Post Type
 *
 * @package wp-secure-code-embeds
 */

namespace Alley\WP\Secure_Code_Embeds\Tests\Feature;

use Alley\WP\Secure_Code_Embeds\Code_Embed;
use Alley\WP\Secure_Code_Embeds\Tests\TestCase;
use WP_Block_Editor_Context;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Tests for the private post type, its capabilities, its REST exposure, and
 * meta revisions.
 */
class CodeEmbedPostTypeTest extends TestCase {
	/**
	 * Create a code embed entry.
	 *
	 * @param array<string, mixed> $args Post arguments.
	 * @param string               $head Head code.
	 * @param string               $body Body code.
	 */
	protected function create_embed( array $args = [], string $head = '<style>.x{}</style>', string $body = '<div class="x"></div>' ): int {
		return static::factory()->post->create(
			array_merge(
				[
					'post_type'   => Code_Embed::POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => 'Contest',
					'meta_input'  => [
						Code_Embed::HEAD_META_KEY => $head,
						Code_Embed::BODY_META_KEY => $body,
					],
				],
				$args,
			)
		);
	}

	/**
	 * Dispatch a REST request.
	 *
	 * @param string               $method HTTP method.
	 * @param string               $route  Route.
	 * @param array<string, mixed> $params Parameters.
	 */
	protected function rest( string $method, string $route, array $params = [] ): WP_REST_Response {
		$request = new WP_REST_Request( $method, $route );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_do_request( $request );
	}

	public function test_post_type_is_private(): void {
		$post_type = get_post_type_object( Code_Embed::POST_TYPE );

		$this->assertNotNull( $post_type );
		$this->assertFalse( $post_type->public );
		$this->assertFalse( $post_type->publicly_queryable );
		$this->assertTrue( $post_type->exclude_from_search );
		$this->assertFalse( $post_type->has_archive );
		$this->assertFalse( $post_type->rewrite );
		$this->assertFalse( $post_type->query_var );
		$this->assertFalse( $post_type->show_in_nav_menus );
		$this->assertTrue( $post_type->show_ui );
		$this->assertTrue( $post_type->show_in_rest );
		$this->assertTrue( post_type_supports( Code_Embed::POST_TYPE, 'revisions' ) );
	}

	public function test_editor_template_is_locked_to_the_fields_block(): void {
		$post_type = get_post_type_object( Code_Embed::POST_TYPE );

		$this->assertSame( [ [ Code_Embed::FIELDS_BLOCK ] ], $post_type->template );
		$this->assertSame( 'all', $post_type->template_lock );

		$context = new WP_Block_Editor_Context( [ 'post' => get_post( $this->create_embed() ) ] );
		$this->assertSame( [ Code_Embed::FIELDS_BLOCK ], get_allowed_block_types( $context ) );

		$page_context = new WP_Block_Editor_Context( [ 'post' => static::factory()->page->create_and_get() ] );
		$this->assertTrue( get_allowed_block_types( $page_context ) );
	}

	public function test_administrator_can_author_entries(): void {
		$embed_id = $this->create_embed();
		$this->acting_as( 'administrator' );

		$this->assertTrue( current_user_can( Code_Embed::CAPABILITY ) );
		$this->assertTrue( current_user_can( get_post_type_object( Code_Embed::POST_TYPE )->cap->create_posts ) );
		$this->assertTrue( current_user_can( 'edit_post', $embed_id ) );
		$this->assertTrue( current_user_can( 'delete_post', $embed_id ) );
	}

	/**
	 * Roles below administrator cannot author entries.
	 *
	 * @param string $role Role name.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'non_authoring_roles' )]
	public function test_other_roles_cannot_author_entries( string $role ): void {
		$this->acting_as( $role );
		$embed_id = $this->create_embed( [ 'post_author' => get_current_user_id() ] );

		$this->assertFalse( current_user_can( Code_Embed::CAPABILITY ) );
		$this->assertFalse( current_user_can( get_post_type_object( Code_Embed::POST_TYPE )->cap->create_posts ) );
		$this->assertFalse( current_user_can( 'edit_post', $embed_id ) );
		$this->assertFalse( current_user_can( 'delete_post', $embed_id ) );
		$this->assertFalse( current_user_can( 'edit_post_meta', $embed_id, Code_Embed::BODY_META_KEY ) );
	}

	/**
	 * Roles that cannot author entries.
	 *
	 * @return array<string, array{string}>
	 */
	public static function non_authoring_roles(): array {
		return [
			'editor'      => [ 'editor' ],
			'author'      => [ 'author' ],
			'contributor' => [ 'contributor' ],
			'subscriber'  => [ 'subscriber' ],
		];
	}

	public function test_role_can_explicitly_deny_the_capability(): void {
		$role = get_role( 'administrator' );
		$role->add_cap( Code_Embed::CAPABILITY, false );

		try {
			$this->acting_as( 'administrator' );
			$this->assertFalse( current_user_can( Code_Embed::CAPABILITY ) );
		} finally {
			$role->remove_cap( Code_Embed::CAPABILITY );
		}
	}

	public function test_page_editors_can_list_and_read_published_entries(): void {
		$embed_id = $this->create_embed();
		$this->acting_as( 'editor' );

		$list = $this->rest( 'GET', '/wp/v2/' . Code_Embed::REST_BASE );
		$this->assertSame( 200, $list->get_status() );
		$this->assertContains( $embed_id, wp_list_pluck( (array) $list->get_data(), 'id' ) );

		$single = $this->rest( 'GET', '/wp/v2/' . Code_Embed::REST_BASE . "/{$embed_id}" );
		$this->assertSame( 200, $single->get_status() );

		$data = (array) $single->get_data();
		$this->assertSame( 'publish', $data['status'] );
		$this->assertArrayNotHasKey( Code_Embed::BODY_META_KEY, (array) ( $data['meta'] ?? [] ), 'Code should not be exposed in the view context.' );
	}

	public function test_page_editors_cannot_read_unpublished_entries(): void {
		$embed_id = $this->create_embed( [ 'post_status' => 'draft' ] );
		$this->acting_as( 'editor' );

		$response = $this->rest( 'GET', '/wp/v2/' . Code_Embed::REST_BASE . "/{$embed_id}" );
		$this->assertGreaterThanOrEqual( 400, $response->get_status() );
	}

	public function test_page_editors_cannot_write_entries(): void {
		$embed_id = $this->create_embed();
		$this->acting_as( 'editor' );

		$create = $this->rest( 'POST', '/wp/v2/' . Code_Embed::REST_BASE, [ 'title' => 'New' ] );
		$this->assertSame( 403, $create->get_status() );

		$update = $this->rest(
			'POST',
			'/wp/v2/' . Code_Embed::REST_BASE . "/{$embed_id}",
			[ 'meta' => [ Code_Embed::BODY_META_KEY => '<script>alert(1)</script>' ] ],
		);
		$this->assertSame( 403, $update->get_status() );
		$this->assertSame( '<div class="x"></div>', get_post_meta( $embed_id, Code_Embed::BODY_META_KEY, true ) );

		$delete = $this->rest( 'DELETE', '/wp/v2/' . Code_Embed::REST_BASE . "/{$embed_id}" );
		$this->assertSame( 403, $delete->get_status() );
	}

	/**
	 * Users who cannot edit pages cannot read entries.
	 *
	 * @param string|null $role Role name, or null for a logged-out user.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'roles_without_read_access' )]
	public function test_users_who_cannot_edit_pages_cannot_read_entries( ?string $role ): void {
		$embed_id = $this->create_embed();

		if ( $role ) {
			$this->acting_as( $role );
		}

		$this->assertGreaterThanOrEqual( 400, $this->rest( 'GET', '/wp/v2/' . Code_Embed::REST_BASE )->get_status() );
		$this->assertGreaterThanOrEqual( 400, $this->rest( 'GET', '/wp/v2/' . Code_Embed::REST_BASE . "/{$embed_id}" )->get_status() );
	}

	/**
	 * Users without read access.
	 *
	 * @return array<string, array{string|null}>
	 */
	public static function roles_without_read_access(): array {
		return [
			'logged out'  => [ null ],
			'subscriber'  => [ 'subscriber' ],
			'contributor' => [ 'contributor' ],
			'author'      => [ 'author' ],
		];
	}

	public function test_administrators_can_save_raw_code_without_sanitization(): void {
		$embed_id = $this->create_embed();
		$this->acting_as( 'administrator' );

		$code     = '<script>document.title = "a < b && c";</script><iframe src="https://example.com"></iframe>';
		$response = $this->rest(
			'POST',
			'/wp/v2/' . Code_Embed::REST_BASE . "/{$embed_id}",
			[ 'meta' => [ Code_Embed::BODY_META_KEY => $code ] ],
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $code, get_post_meta( $embed_id, Code_Embed::BODY_META_KEY, true ) );

		$edit = $this->rest( 'GET', '/wp/v2/' . Code_Embed::REST_BASE . "/{$embed_id}", [ 'context' => 'edit' ] );
		$this->assertSame( $code, ( (array) $edit->get_data() )['meta'][ Code_Embed::BODY_META_KEY ] ?? null );
	}

	public function test_meta_changes_are_revisioned_and_can_be_restored(): void {
		$this->acting_as( 'administrator' );
		$embed_id = $this->create_embed( [], '<style>.v1{}</style>', '<p>v1</p>' );
		$route    = '/wp/v2/' . Code_Embed::REST_BASE . "/{$embed_id}";

		$this->rest( 'POST', $route, [ 'meta' => [ Code_Embed::BODY_META_KEY => '<p>v2</p>' ] ] );
		$this->rest( 'POST', $route, [ 'meta' => [ Code_Embed::BODY_META_KEY => '<p>v3</p>' ] ] );

		$revisions = array_values( wp_get_post_revisions( $embed_id, [ 'order' => 'ASC' ] ) );
		$bodies    = array_map(
			fn ( $revision ) => get_post_meta( $revision->ID, Code_Embed::BODY_META_KEY, true ),
			$revisions,
		);

		$this->assertContains( '<p>v2</p>', $bodies );
		$this->assertContains( '<p>v3</p>', $bodies );

		$v2 = $revisions[ array_search( '<p>v2</p>', $bodies, true ) ];
		wp_restore_post_revision( $v2->ID );

		$this->assertSame( '<p>v2</p>', get_post_meta( $embed_id, Code_Embed::BODY_META_KEY, true ) );
		$this->assertSame( '<style>.v1{}</style>', get_post_meta( $embed_id, Code_Embed::HEAD_META_KEY, true ) );
	}

	public function test_revision_screen_compares_head_and_body_code(): void {
		$fields = _wp_post_revision_fields( get_post( $this->create_embed() ) );

		$this->assertArrayHasKey( Code_Embed::HEAD_META_KEY, $fields );
		$this->assertArrayHasKey( Code_Embed::BODY_META_KEY, $fields );

		$page_fields = _wp_post_revision_fields( static::factory()->page->create_and_get() );
		$this->assertArrayNotHasKey( Code_Embed::BODY_META_KEY, $page_fields );
	}
}

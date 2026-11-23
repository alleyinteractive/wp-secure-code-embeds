<?php
/**
 * Secure Code Embeds Tests: Code Embed Output
 *
 * @package wp-secure-code-embeds
 */

namespace Alley\WP\Secure_Code_Embeds\Tests\Feature;

use Alley\WP\Secure_Code_Embeds\Code_Embed;
use Alley\WP\Secure_Code_Embeds\Features\Code_Embed_Block_Settings;
use Alley\WP\Secure_Code_Embeds\Features\Inject_Head_Content;
use Alley\WP\Secure_Code_Embeds\Tests\TestCase;
use WP_Block_Type_Registry;

/**
 * Tests for the picker block and head injection on the front end.
 */
class CodeEmbedOutputTest extends TestCase {
	/**
	 * Create a code embed entry.
	 *
	 * @param string $name   Unique name used in the head and body code.
	 * @param string $status Post status.
	 */
	protected function create_embed( string $name, string $status = 'publish' ): int {
		return static::factory()->post->create(
			[
				'post_type'   => Code_Embed::POST_TYPE,
				'post_status' => $status,
				'post_title'  => $name,
				'meta_input'  => [
					Code_Embed::HEAD_META_KEY => "<style id=\"{$name}-head\">.{$name}{color:red}</style>",
					Code_Embed::BODY_META_KEY => "<div class=\"{$name}\"><script>var {$name} = 1 && 2;</script></div>",
				],
			]
		);
	}

	/**
	 * Get the markup for a picker block.
	 *
	 * @param int $embed_id Entry ID.
	 */
	protected function picker( int $embed_id ): string {
		return sprintf( '<!-- wp:wp-secure-code-embeds/code-embed {"embedId":%d} /-->', $embed_id );
	}

	/**
	 * Print the head content for a post, as if it were the current page.
	 *
	 * @param int $post_id Post ID.
	 */
	protected function head_for( int $post_id ): string {
		$this->go_to( get_permalink( $post_id ) );

		ob_start();
		new Inject_Head_Content()->print_head_content();

		return (string) ob_get_clean();
	}

	public function test_head_content_is_output_for_each_entry_once(): void {
		$first  = $this->create_embed( 'first' );
		$second = $this->create_embed( 'second' );
		$page   = static::factory()->page->create(
			[
				'post_content' => '<!-- wp:paragraph --><p>Intro</p><!-- /wp:paragraph -->'
					. $this->picker( $first )
					. $this->picker( $second )
					. $this->picker( $first ),
			]
		);

		$head = $this->head_for( $page );

		$this->assertSame( 1, substr_count( $head, 'id="first-head"' ) );
		$this->assertSame( 1, substr_count( $head, 'id="second-head"' ) );
		$this->assertLessThan( strpos( $head, 'second-head' ), strpos( $head, 'first-head' ) );
	}

	public function test_head_content_includes_nested_blocks_and_synced_patterns(): void {
		$in_group   = $this->create_embed( 'ingroup' );
		$in_column  = $this->create_embed( 'incolumn' );
		$in_pattern = $this->create_embed( 'inpattern' );

		$pattern = static::factory()->post->create(
			[
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:group --><div class="wp-block-group">' . $this->picker( $in_pattern ) . '</div><!-- /wp:group -->',
			]
		);

		// A pattern that references itself must not loop forever.
		$looping_pattern = static::factory()->post->create(
			[
				'post_type'   => 'wp_block',
				'post_status' => 'publish',
			]
		);
		wp_update_post(
			[
				'ID'           => $looping_pattern,
				'post_content' => sprintf( '<!-- wp:block {"ref":%d} /-->', $looping_pattern ),
			]
		);

		$page = static::factory()->page->create(
			[
				'post_content' => '<!-- wp:group --><div class="wp-block-group">' . $this->picker( $in_group ) . '</div><!-- /wp:group -->'
					. '<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column">'
					. $this->picker( $in_column )
					. '</div><!-- /wp:column --></div><!-- /wp:columns -->'
					. sprintf( '<!-- wp:block {"ref":%d} /-->', $pattern )
					. sprintf( '<!-- wp:block {"ref":%d} /-->', $looping_pattern ),
			]
		);

		$head = $this->head_for( $page );

		$this->assertStringContainsString( 'id="ingroup-head"', $head );
		$this->assertStringContainsString( 'id="incolumn-head"', $head );
		$this->assertStringContainsString( 'id="inpattern-head"', $head );
	}

	/**
	 * Head content is not output for unpublished or deleted entries.
	 *
	 * @param string $status Entry status.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'unavailable_statuses' )]
	public function test_unavailable_entries_output_nothing( string $status ): void {
		$embed_id = $this->create_embed( 'gone', 'deleted' === $status ? 'publish' : $status );

		if ( 'deleted' === $status ) {
			wp_delete_post( $embed_id, true );
		}

		$page = static::factory()->page->create( [ 'post_content' => $this->picker( $embed_id ) ] );

		$this->assertSame( '', $this->head_for( $page ) );
		$this->assertSame( '', trim( do_blocks( $this->picker( $embed_id ) ) ) );
	}

	/**
	 * Unavailable entry statuses.
	 *
	 * @return array<string, array{string}>
	 */
	public static function unavailable_statuses(): array {
		return [
			'draft'   => [ 'draft' ],
			'pending' => [ 'pending' ],
			'private' => [ 'private' ],
			'trash'   => [ 'trash' ],
			'deleted' => [ 'deleted' ],
		];
	}

	public function test_nothing_is_output_on_post_types_that_do_not_allow_embeds(): void {
		$embed_id = $this->create_embed( 'onpost' );
		$post     = static::factory()->post->create( [ 'post_content' => $this->picker( $embed_id ) ] );

		$this->assertSame( '', $this->head_for( $post ) );
		$this->assertSame( '', Code_Embed::get_block_body( [ 'embedId' => $embed_id ], [ 'postType' => 'post' ] ) );
	}

	public function test_allowed_post_types_can_be_filtered(): void {
		add_filter( 'wp_secure_code_embeds_allowed_post_types', fn (): array => [ 'page', 'post' ] );

		$embed_id = $this->create_embed( 'filtered' );
		$post     = static::factory()->post->create( [ 'post_content' => $this->picker( $embed_id ) ] );

		$this->assertStringContainsString( 'id="filtered-head"', $this->head_for( $post ) );
		$this->assertStringContainsString( 'class="filtered"', Code_Embed::get_block_body( [ 'embedId' => $embed_id ], [ 'postType' => 'post' ] ) );
	}

	public function test_head_content_is_not_output_for_password_protected_pages(): void {
		$embed_id = $this->create_embed( 'protected' );
		$page     = static::factory()->page->create(
			[
				'post_content'  => $this->picker( $embed_id ),
				'post_password' => 'secret',
			]
		);

		$this->assertSame( '', $this->head_for( $page ) );
	}

	public function test_block_renders_body_unescaped(): void {
		if ( ! WP_Block_Type_Registry::get_instance()->is_registered( Code_Embed::PICKER_BLOCK ) ) {
			$this->markTestSkipped( 'Blocks are not built. Run `npm run build` first.' );
		}

		$embed_id = $this->create_embed( 'rendered' );
		$page     = static::factory()->page->create( [ 'post_content' => $this->picker( $embed_id ) . $this->picker( $embed_id ) ] );

		$this->go_to( get_permalink( $page ) );
		$html = do_blocks( get_post( $page )->post_content );

		$this->assertSame( 2, substr_count( $html, '<div class="rendered"><script>var rendered = 1 && 2;</script></div>' ) );
		$this->assertStringContainsString( 'wp-block-wp-secure-code-embeds-code-embed', $html );
	}

	public function test_page_request_outputs_head_and_body_code(): void {
		if ( ! WP_Block_Type_Registry::get_instance()->is_registered( Code_Embed::PICKER_BLOCK ) ) {
			$this->markTestSkipped( 'Blocks are not built. Run `npm run build` first.' );
		}

		$embed_id = $this->create_embed( 'fullpage' );
		$page     = static::factory()->page->create(
			[
				'post_content' => '<!-- wp:paragraph --><p>Above</p><!-- /wp:paragraph -->'
					. $this->picker( $embed_id )
					. '<!-- wp:paragraph --><p>Below</p><!-- /wp:paragraph -->',
			]
		);

		$response = $this->get( get_permalink( $page ) );
		$response->assertOk();

		$html = $response->get_content();
		$head = substr( $html, 0, (int) strpos( $html, '</head>' ) );

		$this->assertStringContainsString( '<style id="fullpage-head">.fullpage{color:red}</style>', $head );
		$this->assertStringContainsString( '<script>var fullpage = 1 && 2;</script>', $html );
		$this->assertMatchesRegularExpression( '/Above.*class="fullpage".*Below/s', $html );
	}

	public function test_picker_can_only_be_inserted_in_allowed_post_types(): void {
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';

		set_current_screen( 'page' );
		$this->assertTrue( new Code_Embed_Block_Settings()->get_settings()['canInsert'] );

		set_current_screen( 'post' );
		$this->assertFalse( new Code_Embed_Block_Settings()->get_settings()['canInsert'] );

		set_current_screen( 'front' );
	}
}

import { registerBlockType, type Block } from '@wordpress/blocks';
import { select } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';

import edit from './edit';
import metadata from './block.json';
import { getAllowedPostTypes } from './settings';

/* @ts-expect-error Provided types are inaccurate to the actual plugin API. */
registerBlockType(metadata, { edit });

/**
 * Only allow the block to be inserted in post types that allow code embeds.
 *
 * The check uses the post currently open in the editor, so it stays correct
 * after navigating to another post, template, or pattern within the editor.
 * The block stays registered everywhere so existing blocks keep their editor
 * placeholder, and it renders nothing on the front end outside allowed post
 * types.
 */
addFilter(
  'blockEditor.__unstableCanInsertBlockType',
  'wp-secure-code-embeds/code-embed/allowed-post-types',
  (canInsert: boolean, blockType: Block) => {
    if (!canInsert || blockType.name !== metadata.name) {
      return canInsert;
    }

    // Select the editor store by name so the script does not depend on
    // `wp-editor`, which must not load in the widgets editor.
    const postType = select('core/editor')?.getCurrentPostType();

    return typeof postType === 'string' && getAllowedPostTypes().includes(postType);
  },
);

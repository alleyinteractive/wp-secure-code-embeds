import { registerBlockType } from '@wordpress/blocks';

import edit from './edit';
import metadata from './block.json';
import { getSettings } from './settings';

/**
 * The block is registered everywhere so existing blocks keep their editor
 * placeholder, but it can only be inserted in post types that allow it.
 */
/* @ts-expect-error Provided types are inaccurate to the actual plugin API. */
registerBlockType(metadata, {
  edit,
  supports: {
    ...metadata.supports,
    inserter: getSettings().canInsert,
  },
});

import { useBlockProps } from '@wordpress/block-editor';
import { Notice, TextareaControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

import './index.scss';

const HEAD_META_KEY = 'secure_code_embed_head';
const BODY_META_KEY = 'secure_code_embed_body';

interface EditProps {
  context: {
    postType?: string;
  };
}

type CodeEmbedMeta = Record<string, unknown>;

/**
 * The wp-secure-code-embeds/code-embed-fields block edit function.
 *
 * Edits the entry's head and body code, which are saved to post meta. The
 * block itself saves nothing to post content.
 *
 * @return {WPElement} Element to render.
 */
export default function Edit({ context: { postType = 'secure_code_embed' } }: EditProps) {
  const [rawMeta, setMeta] = useEntityProp('postType', postType, 'meta');
  const meta = rawMeta as CodeEmbedMeta | undefined;

  const getValue = (key: string): string => {
    const value = meta?.[key];
    return typeof value === 'string' ? value : '';
  };

  const setValue = (key: string, value: string) => setMeta({ ...meta, [key]: value });

  return (
    <div {...useBlockProps()}>
      <Notice status="warning" isDismissible={false}>
        {__(
          'Code saved here is output on the site exactly as written. It is not sanitized or escaped. Only save code you have reviewed and trust.',
          'wp-secure-code-embeds',
        )}
      </Notice>
      <TextareaControl
        __nextHasNoMarginBottom
        help={__(
          'Output in the document <head> of every page that includes this code embed, once per page. Use it for CSS, web fonts, and scripts that must load before the page body.',
          'wp-secure-code-embeds',
        )}
        label={__('Head content', 'wp-secure-code-embeds')}
        onChange={(value: string) => setValue(HEAD_META_KEY, value)}
        rows={12}
        spellCheck={false}
        value={getValue(HEAD_META_KEY)}
      />
      <TextareaControl
        __nextHasNoMarginBottom
        help={__(
          'Output wherever the Code Embed block is placed on a page.',
          'wp-secure-code-embeds',
        )}
        label={__('Body content', 'wp-secure-code-embeds')}
        onChange={(value: string) => setValue(BODY_META_KEY, value)}
        rows={24}
        spellCheck={false}
        value={getValue(BODY_META_KEY)}
      />
    </div>
  );
}

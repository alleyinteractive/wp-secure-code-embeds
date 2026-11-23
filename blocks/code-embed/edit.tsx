import { PostPicker } from '@alleyinteractive/block-editor-tools';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
  ExternalLink,
  Notice,
  PanelBody,
  Placeholder,
  Spinner,
} from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import type { ComponentProps } from 'react';

import { getSettings } from './settings';

import './index.scss';

interface EditProps {
  attributes: {
    embedId: number;
  };
  setAttributes: (newAttributes: Partial<EditProps['attributes']>) => void;
}

interface CodeEmbedRecord {
  id: number;
  status: string;
  title: {
    rendered: string;
  };
}

type PreviewPost = ReturnType<NonNullable<ComponentProps<typeof PostPicker>['previewLookup']>>;

const { adminUrl, postType, restBase } = getSettings();
const QUERY = { context: 'view' };

/**
 * Get the title of an entry for display.
 *
 * @param {object} post An entry from the REST API.
 * @return {string} Decoded title, or a fallback for untitled entries.
 */
function getTitle(post: object): string {
  const title = decodeEntities((post as CodeEmbedRecord)?.title?.rendered ?? '');
  return title || __('(no title)', 'wp-secure-code-embeds');
}

/**
 * Render an entry in the post picker's selected-item preview.
 *
 * @param {object} post An entry from the REST API.
 * @return {WPElement} Element to render.
 */
function renderPreview(post: object) {
  return <strong>{getTitle(post)}</strong>;
}

/**
 * Render an entry in the post picker's search results.
 *
 * @param {object} post An entry from the REST API.
 * @return {WPElement} Element to render.
 */
function renderSearchResult(post: object) {
  return <span>{getTitle(post)}</span>;
}

/**
 * The wp-secure-code-embeds/code-embed block edit function.
 *
 * The entry's code is not rendered in the editor. The block shows a
 * placeholder with the entry title instead.
 *
 * @return {WPElement} Element to render.
 */
export default function Edit({
  attributes: {
    embedId,
  },
  setAttributes,
}: EditProps) {
  const { embed, hasResolved, canEdit } = useSelect((select) => {
    if (!embedId) {
      return { embed: undefined, hasResolved: true, canEdit: false };
    }

    const {
      canUser,
      getEntityRecord,
      hasFinishedResolution,
    } = select(coreStore);

    return {
      embed: getEntityRecord('postType', postType, embedId, QUERY) as CodeEmbedRecord | undefined,
      hasResolved: hasFinishedResolution('getEntityRecord', ['postType', postType, embedId, QUERY]),
      canEdit: !!canUser('update', { kind: 'postType', name: postType, id: embedId }),
    };
  }, [embedId]);

  const isAvailable = !!embed && embed.status === 'publish';
  const editUrl = addQueryArgs(`${adminUrl}post.php`, { post: embedId, action: 'edit' });

  const picker = (
    <PostPicker
      allowedTypes={[postType]}
      modalFormat="list"
      modalTitle={__('Select a code embed', 'wp-secure-code-embeds')}
      onReset={() => setAttributes({ embedId: 0 })}
      onUpdate={(id: number) => setAttributes({ embedId: id })}
      // Return null while loading and undefined when the entry can't be loaded.
      previewLookup={() => (
        (embed ?? (hasResolved ? undefined : null)) as unknown as PreviewPost
      )}
      previewRender={renderPreview}
      searchEndpoint={`/wp/v2/${restBase}`}
      searchRender={renderSearchResult}
      value={embedId}
    />
  );

  let content;

  if (!embedId) {
    content = (
      <>
        <p>{__('Select a code embed to place on this page.', 'wp-secure-code-embeds')}</p>
        {picker}
      </>
    );
  } else if (!hasResolved) {
    content = <Spinner />;
  } else {
    content = (
      <>
        {embed ? (
          <p className="wp-secure-code-embeds__title">{getTitle(embed)}</p>
        ) : null}
        {!isAvailable ? (
          <Notice status="warning" isDismissible={false}>
            {__(
              'The selected code embed has been deleted, trashed, or unpublished. Nothing will render on the front end. Select another code embed or remove this block.',
              'wp-secure-code-embeds',
            )}
          </Notice>
        ) : null}
        {canEdit ? (
          <ExternalLink href={editUrl}>
            {__('Edit code embed', 'wp-secure-code-embeds')}
          </ExternalLink>
        ) : null}
        <p className="wp-secure-code-embeds__note">
          {__(
            'Code embeds do not render in the editor. The content will appear on the front end and in previews.',
            'wp-secure-code-embeds',
          )}
        </p>
      </>
    );
  }

  return (
    <>
      <div {...useBlockProps()}>
        <Placeholder
          icon="editor-code"
          label={__('Code Embed', 'wp-secure-code-embeds')}
        >
          {content}
        </Placeholder>
      </div>
      <InspectorControls>
        <PanelBody title={__('Code Embed', 'wp-secure-code-embeds')}>
          {picker}
        </PanelBody>
      </InspectorControls>
    </>
  );
}

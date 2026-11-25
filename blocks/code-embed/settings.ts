export interface CodeEmbedSettings {
  allowedPostTypes: string[];
}

declare global {
  interface Window {
    wpSecureCodeEmbeds?: Partial<CodeEmbedSettings>;
  }
}

/**
 * Get the post types where code embeds can be placed, as passed by the server.
 *
 * @return {string[]} Post type slugs, or none if the server passed nothing.
 */
export function getAllowedPostTypes(): string[] {
  const postTypes = window.wpSecureCodeEmbeds?.allowedPostTypes;

  return Array.isArray(postTypes) ? postTypes : [];
}

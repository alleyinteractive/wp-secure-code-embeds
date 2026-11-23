export interface CodeEmbedSettings {
  adminUrl: string;
  canInsert: boolean;
  postType: string;
  restBase: string;
}

declare global {
  interface Window {
    wpSecureCodeEmbeds?: Partial<CodeEmbedSettings>;
  }
}

/**
 * Get the settings that the server passes to the editor script.
 *
 * @return {CodeEmbedSettings} Settings, with defaults for anything missing.
 */
export function getSettings(): CodeEmbedSettings {
  return {
    adminUrl: '/wp-admin/',
    canInsert: false,
    postType: 'secure_code_embed',
    restBase: 'secure-code-embeds',
    ...window.wpSecureCodeEmbeds,
  };
}

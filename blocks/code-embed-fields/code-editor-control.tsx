import { BaseControl, TextareaControl } from '@wordpress/components';
import { useEffect, useId, useRef } from '@wordpress/element';
import type { CSSProperties } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * The parts of a CodeMirror 5 editor this control uses.
 */
interface CodeMirrorEditor {
  getValue(): string;
  setValue(value: string): void;
  getInputField(): HTMLTextAreaElement;
  on(event: 'change', handler: (editor: CodeMirrorEditor) => void): void;
  toTextArea(): void;
}

type CodeEditorSettings = Record<string, unknown>;

declare global {
  interface Window {
    wp?: {
      codeEditor?: {
        initialize(
          textarea: HTMLTextAreaElement,
          settings: CodeEditorSettings,
        ): { codemirror: CodeMirrorEditor };
      };
    };
    wpSecureCodeEmbedsCodeEditor?: CodeEditorSettings | false;
  }
}

interface CodeEditorControlProps {
  help: string;
  label: string;
  minLines: number;
  onChange: (value: string) => void;
  value: string;
}

/**
 * Get the code editor settings from WordPress.
 *
 * @return Settings, or null if the code editor isn't available or the user
 *         turned off syntax highlighting in their profile.
 */
function getCodeEditorSettings(): CodeEditorSettings | null {
  const settings = window.wpSecureCodeEmbedsCodeEditor;

  if (!settings || !window.wp?.codeEditor) {
    return null;
  }

  return settings;
}

/**
 * A code field that uses the code editor that ships with WordPress
 * (CodeMirror), with syntax highlighting, line numbers, and linting. Falls
 * back to a textarea when the code editor isn't available.
 */
export default function CodeEditorControl({
  help,
  label,
  minLines,
  onChange,
  value,
}: CodeEditorControlProps) {
  const id = `wp-secure-code-embeds-code-editor-${useId()}`;
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const editorRef = useRef<CodeMirrorEditor | null>(null);
  const onChangeRef = useRef(onChange);
  const settings = getCodeEditorSettings();

  onChangeRef.current = onChange;

  // Start the code editor on the textarea.
  useEffect(() => {
    const textarea = textareaRef.current;

    if (!settings || !textarea || !window.wp?.codeEditor) {
      return undefined;
    }

    const { codemirror } = window.wp.codeEditor.initialize(textarea, {
      ...settings,
      codemirror: {
        ...(settings.codemirror as Record<string, unknown> | undefined),
        // Grow with the content instead of scrolling inside a fixed height.
        viewportMargin: Infinity,
      },
    });

    const input = codemirror.getInputField();
    input.setAttribute('aria-labelledby', `${id}-label`);
    input.setAttribute('aria-describedby', `${id}-help`);

    codemirror.on('change', (editor) => onChangeRef.current(editor.getValue()));
    editorRef.current = codemirror;

    return () => {
      editorRef.current = null;
      codemirror.toTextArea();
    };
    // The settings come from the page and don't change while it's open.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Keep the editor in sync with changes made elsewhere, such as undo.
  useEffect(() => {
    const editor = editorRef.current;

    if (editor && editor.getValue() !== value) {
      editor.setValue(value);
    }
  }, [value]);

  if (!settings) {
    return (
      <TextareaControl
        __nextHasNoMarginBottom
        help={help}
        label={label}
        onChange={onChange}
        rows={minLines}
        spellCheck={false}
        value={value}
      />
    );
  }

  return (
    <BaseControl
      __nextHasNoMarginBottom
      className="wp-secure-code-embeds-code-editor"
      help={(
        <span id={`${id}-help`}>
          {help}
          {' '}
          {__(
            'In the editing area, the Tab key enters a tab character. To move away from this area, press the Esc key followed by the Tab key.',
            'wp-secure-code-embeds',
          )}
        </span>
      )}
      id={id}
    >
      <BaseControl.VisualLabel>
        <span id={`${id}-label`}>{label}</span>
      </BaseControl.VisualLabel>
      <div
        className="wp-secure-code-embeds-code-editor__editor"
        style={{ '--wp-secure-code-embeds-min-lines': minLines } as CSSProperties}
      >
        <textarea defaultValue={value} id={id} ref={textareaRef} />
      </div>
    </BaseControl>
  );
}

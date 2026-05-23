import EasyMDE from 'easymde'
import 'easymde/dist/easymde.min.css'
import '../css/simplemde.min.css'

/*
 * v2.16 — Content editor.
 *
 * The editor used to expose every EasyMDE button — code block, table
 * builder, side-by-side preview, Markdown guide — which is confusing
 * for non-technical users (the public ticket form, customer profile,
 * etc.). This version:
 *
 *   • Speaks French everywhere, removing the words "Markdown",
 *     "WYSIWYG" and "Preview" that appeared in the original placeholder
 *     / tooltips.
 *   • Distinguishes two modes via `data-editor-mode="public|admin"` on
 *     the <textarea>. Public mode drops the technical buttons (code,
 *     table, side-by-side, guide); admin mode keeps them so staff
 *     can still author rich KB articles.
 *   • Replaces the "Markdown supported" placeholder with neutral
 *     "Écrivez votre message…".
 *   • Adds aria-labels on every toolbar button for screen reader
 *     parity with the v2.16 mobile-accessibility pass.
 */

const FRENCH_TITLES = {
    bold: 'Gras',
    italic: 'Italique',
    strikethrough: 'Barré',
    'heading-1': 'Titre principal',
    'heading-2': 'Sous-titre',
    'heading-3': 'Section',
    quote: 'Citation',
    'unordered-list': 'Liste à puces',
    'ordered-list': 'Liste numérotée',
    link: 'Insérer un lien',
    image: 'Insérer une image',
    table: 'Insérer un tableau',
    code: 'Bloc de code',
    'horizontal-rule': 'Séparateur',
    preview: 'Prévisualisation',
    'side-by-side': 'Vue partagée',
    fullscreen: 'Plein écran',
    guide: 'Aide à la mise en forme',
}

function buildToolbar(mode) {
    // Both modes keep the formatting essentials. Public mode drops the
    // tools that need explaining: code blocks, table builder, the
    // side-by-side preview (covered by the "Preview" button alone)
    // and the Markdown guide (which only makes sense if you know what
    // Markdown is).
    if (mode === 'admin') {
        return [
            'bold', 'italic', 'strikethrough', '|',
            'heading-1', 'heading-2', 'heading-3', '|',
            'quote', 'unordered-list', 'ordered-list', '|',
            'link', 'image', 'table', 'code', 'horizontal-rule', '|',
            'preview', 'side-by-side', 'fullscreen', '|',
            'guide',
        ]
    }

    // public — friendly subset for customers / guests / authors who
    // don't speak Markdown.
    return [
        'bold', 'italic', '|',
        'heading-2', 'heading-3', '|',
        'unordered-list', 'ordered-list', '|',
        'link', 'image', 'horizontal-rule', '|',
        'preview', 'fullscreen',
    ]
}

function applyFrenchTitles(mde) {
    // EasyMDE doesn't expose a public i18n hook — we patch the rendered
    // toolbar's <button> titles after mount. Idempotent, fast, no
    // observer needed because the toolbar is built synchronously.
    const buttons = mde.gui?.toolbar?.querySelectorAll('button[title]') ?? []
    buttons.forEach((btn) => {
        const action = btn.dataset.action || btn.title.toLowerCase()
        // Try the data-action attribute first, fall back to the title's
        // English text matched to our dictionary.
        const friendly = FRENCH_TITLES[action]
            ?? FRENCH_TITLES[btn.title.toLowerCase().replace(/\s+/g, '-')]
        if (friendly) {
            btn.title = friendly
            btn.setAttribute('aria-label', friendly)
        }
    })
}

document.addEventListener('DOMContentLoaded', function () {
    const editors = document.querySelectorAll('.editor')

    editors.forEach((editor, index) => {
        const mode = editor.dataset.editorMode === 'admin' ? 'admin' : 'public'
        const uniqueId = `editor-${editor.name || index}`

        const mde = new EasyMDE({
            element: editor,
            spellChecker: false,
            status: false,                    // hides the "lines/words/cursor" technical strip
            minHeight: '220px',
            maxHeight: '520px',
            tabSize: 2,
            indentWithTabs: false,
            forceSync: true,
            autosave: {
                enabled: true,
                uniqueId,
                delay: 1200,
            },
            renderingConfig: {
                codeSyntaxHighlighting: false,
                singleLineBreaks: true,
            },
            toolbar: buildToolbar(mode),
            initialValue: editor.value,
            autofocus: false,
            placeholder: editor.placeholder || 'Écrivez votre message…',
        })

        applyFrenchTitles(mde)

        if (editor.hasAttribute('data-autofocus')) {
            mde.codemirror.focus()
        }
    })
})

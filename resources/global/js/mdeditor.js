import EasyMDE from 'easymde'
import 'easymde/dist/easymde.min.css'
import '../css/simplemde.min.css'

document.addEventListener('DOMContentLoaded', function () {
    const editors = document.querySelectorAll('.editor')

    editors.forEach((editor, index) => {
        const uniqueId = `helpdesk-editor-${editor.name || index}`
        const simpleMDE = new EasyMDE({
            element: editor,
            spellChecker: false,
            status: ['autosave', 'lines', 'words', 'cursor'],
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
            toolbar: [
                'bold', 'italic', 'strikethrough', '|',
                'heading-1', 'heading-2', 'heading-3', '|',
                'quote', 'unordered-list', 'ordered-list', '|',
                'link', 'image', 'table', 'code', 'horizontal-rule', '|',
                'preview', 'side-by-side', 'fullscreen', '|',
                'guide',
            ],
            initialValue: editor.value,
            autofocus: false,
            placeholder: 'Rédigez votre réponse en Markdown…',
        })

        if (editor.hasAttribute('data-autofocus')) {
            simpleMDE.codemirror.focus()
        }
    })
})

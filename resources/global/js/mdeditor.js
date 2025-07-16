import SimpleMDE from 'easymde'
import 'easymde/dist/easymde.min.css'
import '../css/simplemde.min.css'
document.addEventListener('DOMContentLoaded', function () {
    const editors = document.querySelectorAll('.editor')
    editors.forEach(editor => {
        const simpleMDE = new SimpleMDE({
            element: editor,
            spellChecker: false,
            status: false,
            initialValue: editor.value,
            autofocus: true,
        })
        simpleMDE.codemirror.focus();
    });
})

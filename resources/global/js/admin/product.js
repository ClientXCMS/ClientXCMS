import * as monaco from 'monaco-editor';

let editorMode = 'editor';
const toggleBtn = document.getElementById('toggle-btn');

toggleBtn.addEventListener('click', () => {
    toggleEditor();
})
function toggleEditor() {
    const editor = document.getElementById('monaco-editor');
    const description = document.getElementsByClassName('editor-description')[0];
    const toggleBtn = document.getElementById('toggle-btn');

    if (editorMode === 'html') {
        editorMode = 'editor';

        editor.style.display = 'none';
        description.style.display = 'block';

        toggleBtn.innerHTML = 'HTML';
    } else {
        editorMode = 'html';

        editor.style.display = 'block';
        description.style.display = 'none';

        toggleBtn.innerHTML = 'Editor';
    }
}
// Désactivation des workers Monaco car la librairie Monaco-worker ne fonctionne pas (dommage)
window.MonacoEnvironment = {
    getWorker: () => new Proxy({}, {
        get: () => () => {}
    })
};
const editor = monaco.editor.create(document.getElementById('monaco-editor'), {
    value: window.product.value,
    language: 'html',
    theme: window.product.theme,
    automaticLayout: true,
    minimap: { enabled: false },
    wordWrap: 'on',
    lineNumbers: 'on'
});
document.getElementById("product-form").addEventListener("submit", function (event) {

    if (editorMode === 'html') {
        document.querySelector("textarea[name='description']").innerHTML = editor.getValue();
    } else {
        document.querySelector("textarea[name='description']").innerHTML = document.querySelector('#editor_value-description').innerHTML;
    }
});

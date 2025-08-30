import * as monaco from 'monaco-editor';

// Désactivation des workers Monaco car la librairie Monaco-worker ne fonctionne pas (dommage)
window.MonacoEnvironment = {
    getWorker: () => new Proxy({}, {
        get: () => () => {}
    })
};
const editor = monaco.editor.create(document.getElementById('monaco-editor'), {
    value: window.sections.value || '',
    language: 'html',
    theme: window.sections.theme || 'vs-dark',
    automaticLayout: true,
    minimap: { enabled: false },
    wordWrap: 'on',
    lineNumbers: 'on'
});

document.getElementById("section-form").addEventListener("submit", function (event) {
    document.querySelector("input[name='content']").value = editor.getValue();
});

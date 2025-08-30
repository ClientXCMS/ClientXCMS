import * as monaco from 'monaco-editor';


document.addEventListener("DOMContentLoaded", function () {
    const typeSelect = document.getElementById("type");
    const sections = {
        "page": document.getElementById("page-type"),
        "html": document.getElementById("html-type"),
        "redirect": document.getElementById("redirect-type"),
        "pdf": document.getElementById("pdf-type")
    };

    function toggleSections() {
        const selectedType = typeSelect.value;
        for (const [key, section] of Object.entries(sections)) {
            if (key === selectedType) {
                section.style.display = "flex";
            } else {
                section.style.display = "none";
            }
        }
    }
    toggleSections();
    typeSelect.addEventListener("change", toggleSections);
    // Désactivation des workers Monaco car la librairie Monaco-worker ne fonctionne pas (dommage)
    window.MonacoEnvironment = {
        getWorker: () => new Proxy({}, {
            get: () => () => {}
        })
    };
    const editor = monaco.editor.create(document.getElementById('monaco-editor'), {
        value: window.page.value || '',
        language: 'html',
        theme: window.page.theme || 'vs-dark',
        automaticLayout: true,
        minimap: { enabled: false },
        wordWrap: 'on',
        lineNumbers: 'on'
    });

    document.getElementById("page-form").addEventListener("submit", function (event) {
        document.querySelector("input[name='html']").value = editor.getValue();
    });
});

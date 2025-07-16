import Quill from "quill";
const toolbarOptions = [
    ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
    ['blockquote', 'code-block'],
    ['link', 'image', 'video', 'formula'],
    [{ 'header': 1 }, { 'header': 2 }],               // custom button values
    [{ 'list': 'ordered'}, { 'list': 'bullet' }, { 'list': 'check' }],
    [{ 'script': 'sub'}, { 'script': 'super' }],      // superscript/subscript
    [{ 'indent': '-1'}, { 'indent': '+1' }],          // outdent/indent
    [{ 'direction': 'rtl' }],                         // text direction
    [{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown
    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
    [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
    [{ 'font': [] }],
    [{ 'align': [] }],
    ['clean']                                         // remove formatting button
];


const editors = document.querySelectorAll("[id^='editor-']");

editors.forEach(editor => {
    const quill = new Quill(editor, {
        modules: {
            toolbar: toolbarOptions,
        },
        theme: "snow",
    });
    const name = editor.getAttribute("id").replace("editor-", "");
    quill.on("text-change", function(delta, oldDelta, source) {
        document.getElementById("editor_value-" + name).innerHTML = quill.root.innerHTML;
    });
})

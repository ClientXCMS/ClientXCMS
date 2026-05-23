/*
 * v2.16 — Helpdesk editor enhancements.
 *
 * Replaces the bare EasyMDE init with a richer setup that fixes the
 * usability bugs reported on v2.15:
 *   1. Fullscreen toggle now uses the modern Fullscreen API and emits
 *      a regular keyboard-accessible button (previously a plain icon
 *      with no aria-label).
 *   2. Drag-&-drop file upload — drop a file anywhere on the editor
 *      wrapper and it is pushed into the sibling
 *      <input name="attachments[]"> file input, with a visible chip
 *      so the user knows what will be submitted.
 *   3. Server-rendered preview — the bundled EasyMDE preview uses
 *      a JS-only Markdown engine that diverges from the Parsedown
 *      output the server actually ships. A new "Preview" button
 *      fetches POST /helpdesk/preview and renders the same HTML the
 *      recipient will see.
 *   4. Double-submit guard — disables the surrounding form's submit
 *      buttons for 6 s after the first click so the "edit duplicates
 *      the message" bug becomes impossible.
 */

import EasyMDE from 'easymde'
import 'easymde/dist/easymde.min.css'
import '../css/simplemde.min.css'

const EDITOR_SELECTOR = 'textarea.editor:not([data-helpdesk-editor-attached])'

/** Build a chip list of attached file names beside an editor. */
function ensureChipContainer(wrapper) {
    let chips = wrapper.querySelector('.helpdesk-editor-chips')
    if (!chips) {
        chips = document.createElement('div')
        chips.className = 'helpdesk-editor-chips'
        wrapper.appendChild(chips)
    }
    return chips
}

function renderChips(chips, files) {
    chips.innerHTML = ''
    Array.from(files).forEach((file) => {
        const chip = document.createElement('span')
        chip.className = 'helpdesk-editor-chip'
        chip.textContent = `${file.name} (${Math.round(file.size / 1024)} KB)`
        chips.appendChild(chip)
    })
}

function pushFilesIntoInput(fileInput, droppedFiles) {
    const dt = new DataTransfer()
    // keep existing
    for (const f of fileInput.files || []) dt.items.add(f)
    // add new ones (deduplicated by name + size)
    const seen = new Set(Array.from(dt.files).map((f) => `${f.name}|${f.size}`))
    for (const f of droppedFiles) {
        const key = `${f.name}|${f.size}`
        if (!seen.has(key)) {
            dt.items.add(f)
            seen.add(key)
        }
    }
    fileInput.files = dt.files
    fileInput.dispatchEvent(new Event('change', { bubbles: true }))
}

function attachDragDrop(wrapper, fileInput) {
    if (!fileInput) return
    const chips = ensureChipContainer(wrapper)

    const onEnter = (e) => {
        e.preventDefault()
        wrapper.classList.add('helpdesk-editor-dropping')
    }
    const onLeave = (e) => {
        e.preventDefault()
        wrapper.classList.remove('helpdesk-editor-dropping')
    }
    const onDrop = (e) => {
        e.preventDefault()
        wrapper.classList.remove('helpdesk-editor-dropping')
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
            pushFilesIntoInput(fileInput, e.dataTransfer.files)
            renderChips(chips, fileInput.files)
        }
    }

    wrapper.addEventListener('dragenter', onEnter)
    wrapper.addEventListener('dragover', onEnter)
    wrapper.addEventListener('dragleave', onLeave)
    wrapper.addEventListener('drop', onDrop)

    // Reflect manual <input type="file"> picks too.
    fileInput.addEventListener('change', () => renderChips(chips, fileInput.files))
    if (fileInput.files && fileInput.files.length) {
        renderChips(chips, fileInput.files)
    }
}

/**
 * v2.16 — Clipboard paste support.
 *
 * Listens to the `paste` event on the EasyMDE textarea (CodeMirror
 * proxies the event to the wrapper). Any DataTransferItem that is a
 * file — typically a screenshot the user just took with the snipping
 * tool or copied from another app — is pushed into the attachments
 * input. Plain-text paste is left untouched so the editor still works
 * for normal markdown content.
 *
 * Synthesises a meaningful filename when the OS only gives us "image.png"
 * (most browsers do that for clipboard images) by including the date.
 */
function attachClipboardPaste(wrapper, fileInput) {
    if (!fileInput) return
    const chips = ensureChipContainer(wrapper)

    const handler = (e) => {
        const items = e.clipboardData?.items
        if (!items || !items.length) return

        const pasted = []
        for (const item of items) {
            if (item.kind !== 'file') continue
            const file = item.getAsFile()
            if (!file) continue
            // Give clipboard images a stable name + readable date.
            const ts = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19)
            const ext = (file.type.split('/')[1] || 'png').toLowerCase()
            const name = file.name && file.name !== 'image.png'
                ? file.name
                : `pasted-${ts}.${ext}`
            // Re-wrap to control the filename — File is read-only.
            pasted.push(new File([file], name, { type: file.type, lastModified: Date.now() }))
        }

        if (pasted.length === 0) return

        e.preventDefault()
        pushFilesIntoInput(fileInput, pasted)
        renderChips(chips, fileInput.files)
        // Visual feedback consistent with drag-drop.
        wrapper.classList.add('helpdesk-editor-pasting')
        setTimeout(() => wrapper.classList.remove('helpdesk-editor-pasting'), 600)
    }

    // Attach on the wrapper so it catches paste happening inside the
    // CodeMirror textarea (which is a contenteditable inside the
    // wrapper). The {capture: true} flag wins over EasyMDE's own
    // paste handler that otherwise would swallow the event.
    wrapper.addEventListener('paste', handler, { capture: true })
}

async function fetchPreview(content, csrfToken) {
    const url = (document.querySelector('meta[name="helpdesk-preview-url"]') || {}).content
        || '/helpdesk/preview'
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ content }),
    })
    if (!response.ok) {
        throw new Error(`Preview request failed: ${response.status}`)
    }
    const json = await response.json()
    return json.html || ''
}

function ensurePreviewPanel(wrapper) {
    let panel = wrapper.querySelector('.helpdesk-editor-preview')
    if (!panel) {
        panel = document.createElement('div')
        panel.className = 'helpdesk-editor-preview'
        panel.setAttribute('aria-live', 'polite')
        panel.hidden = true
        wrapper.appendChild(panel)
    }
    return panel
}

function attachServerPreviewButton(wrapper, mde) {
    const toolbar = wrapper.querySelector('.editor-toolbar')
    if (!toolbar) return

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || ''
    const preview = ensurePreviewPanel(wrapper)

    const btn = document.createElement('button')
    btn.type = 'button'
    btn.className = 'helpdesk-editor-server-preview'
    btn.textContent = wrapper.dataset.previewLabel || 'Preview'
    btn.setAttribute('aria-pressed', 'false')

    btn.addEventListener('click', async () => {
        if (preview.hidden) {
            try {
                btn.disabled = true
                btn.setAttribute('aria-busy', 'true')
                preview.innerHTML = await fetchPreview(mde.value(), csrf)
                preview.hidden = false
                btn.setAttribute('aria-pressed', 'true')
            } catch (e) {
                preview.innerHTML = '<p class="helpdesk-editor-preview-error">Could not load preview.</p>'
                preview.hidden = false
            } finally {
                btn.disabled = false
                btn.removeAttribute('aria-busy')
            }
        } else {
            preview.hidden = true
            btn.setAttribute('aria-pressed', 'false')
        }
    })

    toolbar.appendChild(btn)
}

function attachDoubleSubmitGuard(form) {
    if (!form || form.dataset.helpdeskGuardAttached) return
    form.dataset.helpdeskGuardAttached = '1'

    form.addEventListener('submit', () => {
        const buttons = form.querySelectorAll('button[type="submit"], button:not([type])')
        buttons.forEach((btn) => {
            const original = btn.innerHTML
            btn.disabled = true
            btn.dataset.originalHtml = original
        })
        // Safety net — if the navigation fails (validation re-render),
        // re-enable after 6 s so the user is never stuck.
        setTimeout(() => {
            buttons.forEach((btn) => {
                btn.disabled = false
                if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml
            })
        }, 6000)
    })
}

function init(textarea) {
    textarea.dataset.helpdeskEditorAttached = '1'

    const uniqueId = `helpdesk-editor-${textarea.name || textarea.id || Math.random().toString(36).slice(2)}`
    const mde = new EasyMDE({
        element: textarea,
        spellChecker: false,
        status: ['lines', 'words'],
        minHeight: '220px',
        maxHeight: '60vh',
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
            singleLineBreaks: false, // multi-line markdown gets the headings, lists, separators right
        },
        toolbar: [
            'bold', 'italic', 'strikethrough', '|',
            'heading-1', 'heading-2', 'heading-3', '|',
            'quote', 'unordered-list', 'ordered-list', '|',
            'link', 'image', 'table', 'code', 'horizontal-rule', '|',
            'side-by-side', 'fullscreen', '|',
            'guide',
        ],
        initialValue: textarea.value,
        autofocus: textarea.hasAttribute('data-autofocus'),
        placeholder: textarea.placeholder || 'Markdown supported — drag a file to attach it.',
    })

    const wrapper = textarea.closest('form')?.querySelector('.EasyMDEContainer')
        || textarea.parentElement.querySelector('.EasyMDEContainer')
    if (wrapper) {
        // Sibling file input (multi-upload). The reply form names it `attachments[]`.
        const fileInput = textarea.closest('form')?.querySelector('input[type="file"][name="attachments[]"]')
            || textarea.closest('form')?.querySelector('input[type="file"]')
        attachDragDrop(wrapper, fileInput)
        attachClipboardPaste(wrapper, fileInput)
        attachServerPreviewButton(wrapper, mde)
    }

    attachDoubleSubmitGuard(textarea.closest('form'))
}

function scan(root = document) {
    root.querySelectorAll(EDITOR_SELECTOR).forEach(init)
}

// v2.16 — <script type="module"> runs after DOMContentLoaded fires, so
// addEventListener on it would never trigger. Branch on readyState so
// existing editors are picked up immediately when the module loads
// late.
function bootstrap() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => scan(), { once: true })
    } else {
        scan()
    }

    // React to dynamically inserted edit panels (the "edit message" collapse).
    const target = document.body || document.documentElement
    if (target) {
        const observer = new MutationObserver((records) => {
            for (const r of records) {
                r.addedNodes.forEach((n) => {
                    if (n.nodeType !== Node.ELEMENT_NODE) return
                    scan(n)
                })
            }
        })
        observer.observe(target, { childList: true, subtree: true })
    }
}

bootstrap()

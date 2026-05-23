/*
 * v2.16 — Bulk-actions UI driver.
 *
 * Mounts on a table/list block decorated with:
 *   <div data-bulk-root data-bulk-action-url="/admin/customers/bulk">
 *     <input type="checkbox" data-bulk-master>            // select-all toggle
 *     <input type="checkbox" data-bulk-id="123">          // per-row
 *     <div data-bulk-bar>                                 // floating action bar
 *         <span data-bulk-count>0</span>
 *         <button data-bulk-action="delete">Delete</button>
 *         <button data-bulk-action="suspend">Suspend</button>
 *     </div>
 *   </div>
 *
 * On click of any [data-bulk-action], a POST is sent to data-bulk-action-url
 * with body { action, ids: [...] }. Bound automatically — no per-page wiring
 * needed beyond the data-* attributes.
 */

function scan(root) {
    if (root.dataset.bulkAttached === '1') return
    root.dataset.bulkAttached = '1'

    const master = root.querySelector('[data-bulk-master]')
    const bar = root.querySelector('[data-bulk-bar]')
    const counter = root.querySelector('[data-bulk-count]')
    const url = root.dataset.bulkActionUrl

    const checkboxes = () => Array.from(root.querySelectorAll('[data-bulk-id]'))
    const selectedIds = () => checkboxes().filter((c) => c.checked).map((c) => c.dataset.bulkId)

    function syncBar() {
        const ids = selectedIds()
        const count = ids.length
        if (counter) counter.textContent = String(count)
        if (bar) {
            bar.classList.toggle('bulk-bar-visible', count > 0)
        }
        if (master) {
            const all = checkboxes()
            master.checked = all.length > 0 && all.every((c) => c.checked)
            master.indeterminate = !master.checked && all.some((c) => c.checked)
        }
    }

    if (master) {
        master.addEventListener('change', () => {
            checkboxes().forEach((c) => { c.checked = master.checked })
            syncBar()
        })
    }

    checkboxes().forEach((c) => c.addEventListener('change', syncBar))

    root.querySelectorAll('[data-bulk-action]').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault()
            const action = btn.dataset.bulkAction
            const ids = selectedIds()
            if (ids.length === 0) return

            const label = btn.dataset.bulkConfirm
                || `Apply "${action}" to ${ids.length} item(s)?`
            if (!window.confirm(label)) return

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            const formData = new FormData()
            formData.append('action', action)
            ids.forEach((id) => formData.append('ids[]', id))

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                    },
                    body: formData,
                })
                const payload = await response.json().catch(() => ({}))
                if (!response.ok) {
                    alert(payload.message || `Bulk action failed (HTTP ${response.status}).`)
                    return
                }
                // Reload to reflect the new state — the simplest UX that always
                // matches whatever the controller did (status change, deletion,
                // dispatched job, …).
                window.location.reload()
            } catch (err) {
                alert('Bulk action failed: ' + err.message)
            }
        })
    })

    syncBar()
}

function scanAll() {
    document.querySelectorAll('[data-bulk-root]').forEach(scan)
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scanAll, { once: true })
} else {
    scanAll()
}

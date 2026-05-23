/*
 * v2.16 — Live service status polling.
 *
 * Mount the markup with:
 *   <div data-service-live data-status-url="{{ route('front.services.status', $service) }}">…</div>
 *
 * Inside, any element with `data-service-field="X"` is updated when
 * the polled JSON has a `X` key. The poller honours the Page
 * Visibility API (no requests when the tab is hidden), backs off on
 * errors, and only fires on pages that actually mount the widget.
 */

const POLL_INTERVAL_MS = 15000
const BACKOFF_MAX_MS = 5 * 60 * 1000

const formatters = {
    days_to_renewal: (n) => {
        if (n == null) return '—'
        if (n < 0) return `${Math.abs(n)} day(s) overdue`
        return `${n} day(s)`
    },
    last_check: (iso) => (iso ? new Date(iso).toLocaleString() : '—'),
}

function applySnapshot(root, snapshot) {
    root.querySelectorAll('[data-service-field]').forEach((node) => {
        const key = node.dataset.serviceField
        if (!Object.prototype.hasOwnProperty.call(snapshot, key)) return
        const raw = snapshot[key]
        const value = formatters[key] ? formatters[key](raw) : raw
        if (node.tagName === 'INPUT' || node.tagName === 'TEXTAREA') {
            node.value = value ?? ''
        } else {
            node.textContent = value ?? ''
        }
        node.classList.remove('service-live-stale')
    })

    // Render the usage estimate table when present.
    const usageHost = root.querySelector('[data-service-usage]')
    if (usageHost && Array.isArray(snapshot.usage_estimate)) {
        usageHost.innerHTML = snapshot.usage_estimate.map((row) => `
            <tr>
                <th scope="row" class="text-left pr-2 py-1">${escapeHtml(row.label)}</th>
                <td class="px-2 py-1 text-right">${formatNum(row.peak)} ${escapeHtml(row.unit || '')}</td>
                <td class="px-2 py-1 text-right text-gray-500">${formatNum(row.included_quantity)}</td>
                <td class="px-2 py-1 text-right">${formatNum(row.charge)} ${escapeHtml(row.currency || '')}</td>
            </tr>
        `).join('') || '<tr><td colspan="4" class="text-gray-500 py-2 text-center">No usage yet this month.</td></tr>'
    }

    // Status pill colouring (relies on CSS classes
    // ".service-live-status-{open,suspended,expired,…}")
    const statusPill = root.querySelector('[data-service-live-pill]')
    if (statusPill && snapshot.state) {
        statusPill.dataset.state = snapshot.state
    }
}

function formatNum(n) {
    if (n == null || isNaN(n)) return '0'
    const s = Number(n).toFixed(4)
    return s.replace(/0+$/, '').replace(/\.$/, '')
}

function escapeHtml(s) {
    const div = document.createElement('div')
    div.textContent = s ?? ''
    return div.innerHTML
}

function startWidget(root) {
    const url = root.dataset.statusUrl
    if (!url) return
    let backoff = POLL_INTERVAL_MS
    let timer = null

    const tick = async () => {
        // Pause polling when the tab is hidden — saves bandwidth and
        // CPU on the customer side, and traffic on ours.
        if (document.hidden) {
            timer = setTimeout(tick, POLL_INTERVAL_MS)
            return
        }
        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
            if (!response.ok) throw new Error(`HTTP ${response.status}`)
            const snapshot = await response.json()
            applySnapshot(root, snapshot)
            backoff = POLL_INTERVAL_MS
        } catch (e) {
            // Mark every live field as stale, exponential backoff up
            // to BACKOFF_MAX_MS so a flaky network doesn't hammer us.
            root.querySelectorAll('[data-service-field]').forEach((n) => n.classList.add('service-live-stale'))
            backoff = Math.min(backoff * 2, BACKOFF_MAX_MS)
        } finally {
            timer = setTimeout(tick, backoff)
        }
    }
    tick()

    // Visibility — when the tab comes back, refresh immediately.
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && timer) {
            clearTimeout(timer)
            tick()
        }
    })
}

function scan() {
    document.querySelectorAll('[data-service-live]:not([data-service-live-attached])').forEach((root) => {
        root.dataset.serviceLiveAttached = '1'
        startWidget(root)
    })
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => scan(), { once: true })
} else {
    scan()
}

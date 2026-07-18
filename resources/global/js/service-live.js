const POLL_INTERVAL_MS = 15000
const BACKOFF_MAX_MS = 5 * 60 * 1000

function applySnapshot(root, snapshot) {
    for (const field of ['panel_html', 'status_badge_html']) {
        const node = root.querySelector(`[data-service-field="${field}"]`)
        if (node && typeof snapshot[field] === 'string') {
            node.innerHTML = snapshot[field]
            node.classList.remove('service-live-stale')
        }
    }
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
            root.querySelectorAll('[data-service-field="panel_html"], [data-service-field="status_badge_html"]')
                .forEach((node) => node.classList.add('service-live-stale'))
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

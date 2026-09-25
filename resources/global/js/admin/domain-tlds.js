function init() {
    document.querySelectorAll('[data-domain-defaults]').forEach(root => {
        let index = Math.max(-1, ...[...root.querySelectorAll('[name^="default_dns_records["]')].map(el => Number(el.name.match(/\[(\d+)\]/)?.[1] ?? -1))) + 1;
        let nameserverIndex = Math.max(-1, ...[...root.querySelectorAll('[name^="default_nameservers["]')].map(el => Number(el.name.match(/\[(\d+)\]/)?.[1] ?? -1))) + 1;
        const priorities = () => root.querySelectorAll('[data-dns-record-list] [data-dns-row]').forEach(row => {
            const input = row.querySelector('[data-dns-priority]');
            if (input) { input.disabled = row.querySelector('select').value !== 'MX'; input.required = !input.disabled; }
        });
        root.addEventListener('change', priorities);
        root.addEventListener('click', event => {
            if (event.target.closest('[data-remove-row]')) event.target.closest('[data-dns-row]').remove();
            if (event.target.closest('[data-add-nameserver]')) {
                const template = document.createElement('template');
                template.innerHTML = root.querySelector('[data-nameserver-template]').innerHTML.replaceAll('__INDEX__', String(nameserverIndex++));
                root.querySelector('[data-nameserver-list]').append(template.content.cloneNode(true));
            }
            if (event.target.closest('[data-add-dns]')) {
                const template = document.createElement('template');
                template.innerHTML = root.querySelector('[data-dns-template]').innerHTML.replaceAll('__INDEX__', String(index++));
                root.querySelector('[data-dns-record-list]').append(template.content.cloneNode(true)); priorities();
            }
        }); priorities();
    });
    document.querySelectorAll('[data-catalog-start]').forEach(form => {
        const select = form.elements.registrar, servers = form.elements.server_id;
        const update = () => { [...servers.options].forEach(option => { if (option.value) option.disabled = option.dataset.registrar !== select.value; }); if (servers.selectedOptions[0]?.disabled) servers.value = ''; };
        select.addEventListener('change', update); update();
    });
    document.querySelectorAll('[data-target-list]').forEach(root => {
        root.querySelector('[data-filter-targets]').addEventListener('input', event => root.querySelectorAll('[data-target]').forEach(label => { label.hidden = !label.dataset.target.includes(event.target.value.toLowerCase()); }));
        root.querySelector('[data-select-visible]').addEventListener('change', event => root.querySelectorAll('[data-target]').forEach(label => { if (!label.hidden) label.querySelector('input').checked = event.target.checked; }));
    });
    document.querySelectorAll('[data-catalog-selection]').forEach(form => {
        const key = 'domain-catalog-' + form.dataset.catalogSelection;
        let selected = new Set();
        try { selected = new Set(JSON.parse(sessionStorage.getItem(key) || '[]')); } catch {}
        const inputs = [...form.querySelectorAll('[data-catalog-tld]')];
        const update = () => {
            const container = form.querySelector('[data-selected-inputs]'); container.replaceChildren();
            selected.forEach(extension => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'selected[]'; input.value = extension; container.append(input); });
            form.querySelector('[data-selection-count]').textContent = String(selected.size);
            try { sessionStorage.setItem(key, JSON.stringify([...selected])); } catch {}
        };
        inputs.forEach(input => { input.checked = selected.has(input.value); input.addEventListener('change', () => { input.checked ? selected.add(input.value) : selected.delete(input.value); update(); }); });
        form.querySelector('[data-select-page]').addEventListener('change', event => { inputs.forEach(input => { input.checked = event.target.checked; input.checked ? selected.add(input.value) : selected.delete(input.value); }); update(); }); update();
    });
    document.querySelectorAll('[data-domain-apply]').forEach(form => form.addEventListener('submit', () => { form.querySelector('button[type="submit"], button:not([type])').disabled = true; }));
    if (document.querySelector('[data-catalog-poll]')) window.setTimeout(() => window.location.reload(), 5000);
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();

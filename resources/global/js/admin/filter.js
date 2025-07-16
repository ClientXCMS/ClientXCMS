import {HSOverlay} from "preline";

function getUrlParams() {
    let params = {};
    let queryString = window.location.search.slice(1);
    let pairs = queryString.split('&');

    if (pairs[0] === '') {
        return params;
    }
    pairs.forEach(function(pair) {
        let [key, value] = pair.split('=');
        value = decodeURIComponent(value);
        if (!params[key]) {
            params[key] = [];
        }
        params[key].push(...value.split(','));
    });
    return params;
}

function debounce(callback, delay){
    let timer;
    return function(){
        let args = arguments;
        let context = this;
        clearTimeout(timer);
        timer = setTimeout(function(){
            callback.apply(context, args);
        }, delay)
    }
}
function buildQueryString(params) {
    return Object.keys(params).map(function(key) {
        return key + '=' + params[key].join(',');
    }).join('&');
}

document.querySelectorAll('.filter-checkbox').forEach(function(el) {
    el.addEventListener('change', function() {
        let redirect = el.hasAttribute('data-redirect') ? el.getAttribute('data-redirect') : location.pathname;
        let params = getUrlParams();

        if (this.value === 'all') {
            location.href = redirect;
            return;
        }

        let key = 'filter[' + this.dataset.key + ']';
        if (this.checked) {
            if (!params[key]) {
                params[key] = [];
            }
            if (!params[key].includes(this.value)) {
                params[key].push(this.value);
            }
        } else {
            if (params[key]) {
                params[key] = params[key].filter(value => value !== this.value);
                if (params[key].length === 0) {
                    delete params[key];
                }
            }
        }
        debounce(function(){
            let queryString = buildQueryString(params);
            location.href = redirect + (queryString ? '?' + queryString : '');
        }, 500)();
    });
});

const searchForm = document.querySelector("#searchForm");
if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
        e.preventDefault();
        let params = getUrlParams();
        let queryString = this.querySelector('input[type="text"]').value;
        let type = this.querySelector('select').value;
        Object.keys(params).forEach(function (key) {
            if (key.startsWith('filter[') && key.includes(localStorage.getItem('type'))) {
                delete params[key];
            }
        });
        localStorage.setItem('type', type);
        params['filter[' + type + ']'] = [queryString];
        let updatedQueryString = buildQueryString(params);
        location.href = location.pathname + '?' + updatedQueryString;
    })
}
const checkboxAll = document.querySelector('#checkbox-all');
if (checkboxAll) {
    document.querySelector('#checkbox-all').addEventListener('change', function () {
        const self = this;
        let checkboxes = this.closest('table').querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = self.checked;
        });
    });
}
const massActionSelect = document.querySelector('#mass_action_select');
const massActionForm = document.querySelector('#mass_action_form');
if (massActionSelect) {
    massActionSelect.addEventListener('change', function() {
        let action = this.value
        let checkboxes = document.querySelector('#mass_action_table').querySelectorAll('input[type="checkbox"]:checked');
        let ids = [];
        let names = [];
        if (action === 'action') return;
        checkboxes.forEach(function(checkbox) {
            if (checkbox.dataset.id) {
                ids.push(checkbox.dataset.id);
                names.push(checkbox.dataset.name);
            }
        });
        const mass_actions_list = document.querySelector('#mass_actions_list');
        mass_actions_list.innerHTML = '';
        for (let i = 0; i < ids.length; i++) {
            let li = document.createElement('li');
            li.innerHTML = names[i] + ' # ' + ids[i];
            mass_actions_list.appendChild(li);
        }
        document.querySelector('#mass_action_ids').value = ids.join(',');
        document.querySelector('#mass_action_action').value = action;
        document.querySelector('#mass_action_overlay_title').innerHTML = this.options[this.selectedIndex].text;
        const MassActionInput = massActionForm.querySelector("input[name='input']");
        const MassActionLabel = massActionForm.querySelector("label");
        if (this.options[this.selectedIndex].dataset.question) {
            MassActionLabel.innerHTML = this.options[this.selectedIndex].dataset.question;
            MassActionInput.type = 'text';
            console.log('text');
        } else {
            MassActionLabel.innerHTML = '';
            MassActionInput.type = 'hidden';
            console.log('hidden');
        }
        HSOverlay.open(document.querySelector('#mass_action_btn'));
    });
}

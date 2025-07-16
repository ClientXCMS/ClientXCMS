import TomSelect  from "tom-select/base";
const selects = document.querySelectorAll('.tom-select');

document.addEventListener("DOMContentLoaded", function () {
    selects.forEach(function (select) {
        new TomSelect(select, {
            valueField: 'id',
            labelField: 'title',
            searchField: 'title',
            placeholder: select.dataset.placeholder,
            load: function(query, callback) {
                if (!query.length) return callback();
                fetch(select.dataset.apiurl + "?q=" + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(json => {
                        callback(json.data);
                    }).catch(() => {
                    callback();
                });
            },
            render: {
                option: function(item, escape) {
                    return `
                    <div>
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">${escape(item.title)}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">${escape(item.description || '')}</div>
                    </div>
                `;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.title)}</div>`;
                }
            }
        });
    })

});


import Sortable from 'sortablejs';
document.querySelectorAll('.sortable-list').forEach(function (el) {
    console.log(el);
    Sortable.create(el, {
        animation: 150,
        group: {
            name: 'items',
            put: function (to, sortable, drag) {
                return !drag.classList.contains('sortable-parent');
            },
        },
    });
});

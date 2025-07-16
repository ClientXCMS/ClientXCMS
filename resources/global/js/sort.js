import Sortable from 'sortablejs';
const SortableMixin = Base =>
    class extends Base {
        connectedCallback() {
            this.saveButton = document.querySelector(this.dataset.button);
            if (this.saveButton != null) {
                this.saveButton.addEventListener('click', this.save.bind(this));
            }
            this.saveUrl = this.dataset.url;
            if (this.dataset.form !== undefined) {
                this.form = document.querySelector(this.dataset.form);
            }
            this.initSortable();
            console.log("init", this.tagName);
        }

        initSortable() {
            this.sortable = Sortable.create(this, {
                animation: 150,
                group: {
                    name: 'item',
                    put: function (to, sortable, drag) {
                        return !drag.classList.contains('sortable-parent');
                    },
                },
                onEnd: (evt) => {
                    if (evt.from.tagName == 'OL'){
                        evt.item.childNodes[1].classList.remove('ml-4');
                    }
                }
            });
        }

        serialize(sortableEl = this.sortable.el) {
            return [].slice.call(sortableEl.children)
                .filter(child => child instanceof HTMLElement)
                .map(child => {
                    if (child.classList.contains('sortable-parent')) {
                        const childrens = child.querySelector('ul, ol');
                        if (childrens !== null && childrens.children.length > 0) {
                            return {
                                id: child.id,
                                children: Array.from(childrens.children).map(child => child.id)
                            };
                        }
                    }
                    return child.id;
                });
        }

        save() {
            if (this.form !== undefined) {
                return;
            }
            const data = new FormData(this.form);
            data.append('items', JSON.stringify(this.serialize()));

            this.saveButton.disabled = true;
            fetch(this.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ items: this.serialize() })
            })
                .then(response => response.json())
                .then(data => {
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                })
                .finally(() => {
                    this.saveButton.disabled = false;
                });
        }
    };

class SortList extends SortableMixin(HTMLUListElement) {}
customElements.define("sort-list", SortList, { extends: 'ul' });

class SortList2 extends SortableMixin(HTMLOListElement) {}
customElements.define("sort-list2", SortList2, { extends: 'ol' });

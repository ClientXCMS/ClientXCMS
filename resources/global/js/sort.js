import Sortable from 'sortablejs';
const SortableMixin = Base =>
    class extends Base {
        connectedCallback() {
            this.saveButton = document.querySelector(this.dataset.button);
            if (this.saveButton != null) {
                this.saveButton.addEventListener('click', this.save.bind(this));
            }
            this.saveUrl = this.dataset.url;
            this.autosave = this.dataset.autosave !== undefined;
            if (this.dataset.form !== undefined) {
                this.form = document.querySelector(this.dataset.form);
            }
            this.initSortable();
            console.log("init", this.tagName);
        }

        initSortable() {
            const options = {
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
                    if (this.autosave) {
                        this.save();
                    }
                }
            };

            // Support optional drag handle via data-handle attribute
            if (this.dataset.handle) {
                options.handle = this.dataset.handle;
            }

            this.sortable = Sortable.create(this, options);
        }

        serializeNode(node) {
            const nested = node.querySelector(':scope > ul, :scope > ol');
            if (node.classList.contains('sortable-parent') && nested !== null && nested.children.length > 0) {
                return {
                    id: node.id,
                    children: Array.from(nested.children)
                        .filter(child => child instanceof HTMLElement)
                        .map(child => this.serializeNode(child))
                };
            }
            return node.id;
        }

        serialize(sortableEl = this.sortable.el) {
            return [].slice.call(sortableEl.children)
                .filter(child => child instanceof HTMLElement)
                .map(child => this.serializeNode(child));
        }

        save() {
            if (this.form !== undefined) {
                return;
            }

            if (this.saveButton) {
                this.saveButton.disabled = true;
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch(this.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
                },
                body: JSON.stringify({ items: this.serialize() })
            })
                .then(response => response.json())
                .then(data => {
                    if (!this.autosave) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                })
                .finally(() => {
                    if (this.saveButton) {
                        this.saveButton.disabled = false;
                    }
                });
        }
    };

class SortList extends SortableMixin(HTMLUListElement) {}
customElements.define("sort-list", SortList, { extends: 'ul' });

class SortList2 extends SortableMixin(HTMLOListElement) {}
customElements.define("sort-list2", SortList2, { extends: 'ol' });

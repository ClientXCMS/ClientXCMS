<?php
/*
 * This file is part of the CLIENTXCMS project.
 * Social networks management with inline editing and auto-save.
 *
 * Year: 2026
 */
?>

@extends('admin.settings.sidebar')
@section('title', __($translatePrefix . '.title'))

@section('setting')
    <div class="card">
        <div class="card-heading">
            <div>
                <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
                    {{ __($translatePrefix . '.title') }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __($translatePrefix . '.subheading') }}
                </p>
            </div>
            <div class="flex gap-2">
                <span id="saveStatus" class="text-sm text-gray-500 dark:text-gray-400 self-center hidden">
                    <i class="bi bi-check-circle text-green-500"></i> Saved
                </span>
                <button type="button" class="btn btn-secondary text-sm" onclick="addSocialNetwork()">
                    <i class="bi bi-plus-lg mr-1"></i>{{ __('admin.create') }}
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert text-red-700 bg-red-100 mt-2" role="alert">
                @foreach ($errors->all() as $error)
                    {!! $error !!}<br/>
                @endforeach
            </div>
        @endif

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 mb-4">
            <p class="text-sm text-blue-700 dark:text-blue-300 flex items-start gap-2">
                <i class="bi bi-info-circle mt-0.5"></i>
                <span>Modifiez directement les reseaux sociaux. Les modifications sont sauvegardees automatiquement. Utilisez les icones Bootstrap Icons (ex: bi bi-facebook).</span>
            </p>
        </div>

        <div id="social-networks-container" class="space-y-3">
            @forelse ($items as $item)
            <div class="social-item p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center gap-2" data-id="{{ $item->id }}">
                <span class="item-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="bi bi-grip-vertical"></i>
                </span>
                <span class="item-number inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-bold">{{ $loop->iteration }}</span>
                <div class="item-icon-preview w-8 h-8 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <i class="{{ $item->icon }} text-lg"></i>
                </div>
                <input type="text" value="{{ $item->icon }}"
                       class="item-icon input-text text-sm py-1.5 w-32"
                       placeholder="bi bi-facebook"
                       onchange="markChanged(this)"
                       oninput="updateIconPreview(this)">
                <input type="text" value="{{ $item->name }}"
                       class="item-name input-text text-sm py-1.5 w-32"
                       placeholder="{{ __('global.name') }}"
                       onchange="markChanged(this)">
                <input type="text" value="{{ $item->url }}"
                       class="item-url input-text text-sm py-1.5 flex-1"
                       placeholder="https://..."
                       onchange="markChanged(this)">
                <div class="flex items-center gap-1 ml-auto">
                    <button type="button" onclick="moveSocialNetwork(this, -1)" class="btn-move-up p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed" title="Monter">
                        <i class="bi bi-chevron-up"></i>
                    </button>
                    <button type="button" onclick="moveSocialNetwork(this, 1)" class="btn-move-down p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed" title="Descendre">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <button type="button" onclick="deleteSocialNetwork(this)" class="btn-delete p-1.5 text-red-400 hover:text-red-600" title="Supprimer">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            @empty
            <div id="empty-state" class="text-center py-8 text-gray-500 dark:text-gray-400">
                <i class="bi bi-share text-4xl mb-2"></i>
                <p>Aucun reseau social configure.</p>
                <p class="text-sm">Cliquez sur "{{ __('admin.create') }}" pour ajouter un reseau social.</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Template for new social network --}}
    <template id="social-item-template">
        <div class="social-item p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center gap-2" data-id="">
            <span class="item-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="bi bi-grip-vertical"></i>
            </span>
            <span class="item-number inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-bold">0</span>
            <div class="item-icon-preview w-8 h-8 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg">
                <i class="bi bi-share text-lg"></i>
            </div>
            <input type="text" value=""
                   class="item-icon input-text text-sm py-1.5 w-32"
                   placeholder="bi bi-facebook"
                   onchange="markChanged(this)"
                   oninput="updateIconPreview(this)">
            <input type="text" value=""
                   class="item-name input-text text-sm py-1.5 w-32"
                   placeholder="{{ __('global.name') }}"
                   onchange="markChanged(this)">
            <input type="text" value=""
                   class="item-url input-text text-sm py-1.5 flex-1"
                   placeholder="https://..."
                   onchange="markChanged(this)">
            <div class="flex items-center gap-1 ml-auto">
                <button type="button" onclick="moveSocialNetwork(this, -1)" class="btn-move-up p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed" title="Monter">
                    <i class="bi bi-chevron-up"></i>
                </button>
                <button type="button" onclick="moveSocialNetwork(this, 1)" class="btn-move-down p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed" title="Descendre">
                    <i class="bi bi-chevron-down"></i>
                </button>
                <button type="button" onclick="deleteSocialNetwork(this)" class="btn-delete p-1.5 text-red-400 hover:text-red-600" title="Supprimer">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </template>
@endsection

@section('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';
    const baseUrl = '{{ url("admin/personalization/socials") }}';
    let saveTimeout = null;

    function showSaveStatus(message = 'Saved', isError = false) {
        const status = document.getElementById('saveStatus');
        status.innerHTML = isError
            ? '<i class="bi bi-exclamation-circle text-red-500"></i> ' + message
            : '<i class="bi bi-check-circle text-green-500"></i> ' + message;
        status.classList.remove('hidden');
        setTimeout(() => status.classList.add('hidden'), 2000);
    }

    function updateIconPreview(input) {
        const item = input.closest('.social-item');
        const preview = item.querySelector('.item-icon-preview i');
        preview.className = input.value + ' text-lg';
    }

    function updateNumbers() {
        const items = document.querySelectorAll('#social-networks-container .social-item');
        items.forEach((item, index) => {
            const number = item.querySelector('.item-number');
            if (number) number.textContent = index + 1;
        });

        // Hide empty state if items exist
        const emptyState = document.getElementById('empty-state');
        if (emptyState) {
            emptyState.style.display = items.length > 0 ? 'none' : 'block';
        }
    }

    function markChanged(input) {
        const item = input.closest('.social-item');
        const id = item.dataset.id;

        if (!id) return; // New item not yet saved

        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            saveSocialNetwork(item);
        }, 500);
    }

    async function saveSocialNetwork(item) {
        const id = item.dataset.id;
        if (!id) return;

        const data = {
            icon: item.querySelector('.item-icon').value,
            name: item.querySelector('.item-name').value,
            url: item.querySelector('.item-url').value,
        };

        try {
            const response = await fetch(`${baseUrl}/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });

            if (response.ok) {
                showSaveStatus();
            } else {
                showSaveStatus('Error', true);
            }
        } catch (error) {
            showSaveStatus('Error', true);
            console.error('Save error:', error);
        }
    }

    async function addSocialNetwork() {
        const template = document.getElementById('social-item-template');
        const container = document.getElementById('social-networks-container');
        const clone = template.content.cloneNode(true);
        const item = clone.querySelector('.social-item');

        // Hide empty state
        const emptyState = document.getElementById('empty-state');
        if (emptyState) emptyState.style.display = 'none';

        container.appendChild(clone);
        updateNumbers();

        // Focus on the icon input
        const newItem = container.lastElementChild;
        newItem.querySelector('.item-icon').focus();

        // Create in database
        try {
            const response = await fetch(baseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    icon: 'bi bi-share',
                    name: 'New',
                    url: '#',
                }),
            });

            if (response.ok) {
                const result = await response.json();
                newItem.dataset.id = result.id;
                showSaveStatus('Added');
            } else {
                showSaveStatus('Error', true);
            }
        } catch (error) {
            showSaveStatus('Error', true);
            console.error('Add error:', error);
        }
    }

    async function deleteSocialNetwork(button) {
        const item = button.closest('.social-item');
        const id = item.dataset.id;

        if (!confirm('Supprimer ce reseau social ?')) return;

        if (id) {
            try {
                const response = await fetch(`${baseUrl}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    showSaveStatus('Error', true);
                    return;
                }
            } catch (error) {
                showSaveStatus('Error', true);
                console.error('Delete error:', error);
                return;
            }
        }

        item.remove();
        updateNumbers();
        showSaveStatus('Deleted');
    }

    function moveSocialNetwork(button, direction) {
        const item = button.closest('.social-item');
        const container = document.getElementById('social-networks-container');
        const items = Array.from(container.querySelectorAll('.social-item'));
        const index = items.indexOf(item);

        if (direction === -1 && index > 0) {
            container.insertBefore(item, items[index - 1]);
        } else if (direction === 1 && index < items.length - 1) {
            container.insertBefore(items[index + 1], item);
        }

        updateNumbers();
        saveOrder();
    }

    async function saveOrder() {
        const items = document.querySelectorAll('#social-networks-container .social-item');
        const order = Array.from(items).map(item => item.dataset.id).filter(id => id);

        try {
            const response = await fetch(`${baseUrl}/sort`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ items: order }),
            });

            if (response.ok) {
                showSaveStatus('Order saved');
            }
        } catch (error) {
            console.error('Sort error:', error);
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        updateNumbers();
    });
</script>
@endsection

@extends('layouts.vertical', ['title' => 'Sale Requirements', 'subTitle' => 'Administrator'])

@section('css')
    <style>
        .req-admin-tabs .nav-link {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .6rem 1rem;
            border-radius: 10px;
            font-weight: 500;
        }

        .req-admin-tabs .nav-link iconify-icon {
            font-size: 20px;
        }

        .req-admin-tabs .nav-link .badge {
            font-weight: 600;
        }

        .req-admin-tabs .nav-link:not(.active) .badge {
            background: var(--bs-secondary-bg) !important;
            color: var(--bs-secondary-color) !important;
        }

        .req-admin-group {
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
            background: var(--bs-body-bg);
            overflow: hidden;
        }

        .req-admin-group-header {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .6rem .75rem;
            background: var(--bs-tertiary-bg);
            border-bottom: 1px solid var(--bs-border-color);
        }

        .req-admin-option {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .45rem .75rem;
            border-bottom: 1px solid var(--bs-border-color-translucent);
            background: var(--bs-body-bg);
        }

        .req-admin-option:last-child {
            border-bottom: 0;
        }

        .req-admin-option:hover {
            background: var(--bs-tertiary-bg);
        }

        .req-admin-option.is-hidden .text-truncate:first-child {
            color: var(--bs-secondary-color);
            text-decoration: line-through;
        }

        .req-admin-handle {
            display: inline-flex;
            color: var(--bs-secondary-color);
            font-size: 18px;
            cursor: grab;
        }

        .req-admin-handle:active {
            cursor: grabbing;
        }

        .sortable-ghost {
            opacity: .4;
            background: var(--bs-primary-bg-subtle) !important;
        }

        .req-admin-preview {
            position: sticky;
            top: 90px;
            padding: 1rem;
            border-radius: 12px;
            background: var(--bs-tertiary-bg);
        }

        .min-w-0 {
            min-width: 0;
        }

        .js-field-panel.is-loading {
            opacity: .6;
            pointer-events: none;
        }

        .modal-sentence-preview {
            padding: .6rem .75rem;
            font-size: .8125rem;
            border: 1px dashed var(--bs-border-color);
            border-radius: 8px;
            background: var(--bs-tertiary-bg);
        }
    </style>
    @include('sales.partials.requirement-picker-assets')
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1">Sale Requirements</h4>
                            <p class="text-muted mb-0">Manage the options staff pick for Timing, Experience, Benefits and
                                Qualification on the sale form. Changes apply to the form immediately.</p>
                        </div>
                        @can('sale-create')
                            <a href="{{ route('sales.create') }}" class="btn btn-outline-primary" target="_blank" rel="noopener">
                                <iconify-icon icon="solar:square-top-down-linear" class="align-middle me-1"></iconify-icon>Open sale form
                            </a>
                        @endcan
                    </div>

                    <ul class="nav nav-pills req-admin-tabs gap-2 mt-3" role="tablist">
                        @foreach ($fields as $field)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="req-tab-{{ $field->id }}"
                                    data-bs-toggle="pill" data-bs-target="#req-pane-{{ $field->id }}" type="button" role="tab"
                                    aria-controls="req-pane-{{ $field->id }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    <iconify-icon icon="{{ $field->icon ?: 'solar:checklist-minimalistic-bold-duotone' }}" class="js-tab-icon"></iconify-icon>
                                    <span class="js-tab-label">{{ $field->label }}</span>
                                    <span class="badge rounded-pill bg-light text-primary js-tab-count">
                                        {{ $field->groups->flatMap->options->where('is_active', true)->count() }}
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-content">
        @foreach ($fields as $field)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="req-pane-{{ $field->id }}" role="tabpanel"
                aria-labelledby="req-tab-{{ $field->id }}">
                <div class="js-field-panel" data-field-id="{{ $field->id }}">
                    @include('sale-requirements.partials.field-panel', ['field' => $field])
                </div>
            </div>
        @endforeach
    </div>
@endsection

@section('modal')
    {{-- Group modal --}}
    <div class="modal fade" id="groupModal" tabindex="-1" aria-labelledby="groupModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" id="groupForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="groupModalTitle">Add group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="group_title">Group title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="group_title" name="title" maxlength="100"
                            placeholder="e.g. Shift pattern" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="group_is_single" name="is_single" value="1">
                        <label class="form-check-label" for="group_is_single">Pick one only</label>
                    </div>
                    <div class="form-text">A pick-one group (like "Minimum experience") is placed first in the sentence.
                        Only one group per field can be pick-one.</div>

                    {{-- Only for multi-select groups --}}
                    <div id="groupLeadFields" class="mt-3 pt-3 border-top">
                        <div class="mb-3">
                            <label class="form-label" for="group_prefix">Group prefix</label>
                            <input type="text" class="form-control" id="group_prefix" name="prefix" maxlength="150"
                                placeholder="e.g. double shift">
                            <div class="form-text">Placed before this group's selected chips. Leave empty to list the chips
                                together with the other groups.</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="group_has_quantity"
                                name="has_quantity" value="1">
                            <label class="form-check-label" for="group_has_quantity">Ask for a number</label>
                        </div>
                        <div class="form-text">Adds a number box on the sale form, placed before the prefix (e.g. <b>3</b> double shift).
                            A group with a number and no chips works too (e.g. <b>48</b> hours in week).</div>
                        <div class="modal-sentence-preview mt-3">
                            <span class="text-muted">Reads as:</span> <span id="groupSentencePreview"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save group</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Option modal --}}
    <div class="modal fade" id="optionModal" tabindex="-1" aria-labelledby="optionModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" id="optionForm" novalidate>
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="optionModalTitle">Add option</h5>
                        <p class="text-muted fs-13 mb-0" id="optionModalGroup"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="option_label">Chip label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="option_label" name="label" maxlength="150"
                            placeholder="e.g. Night Shifts" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div id="optionSingleFields">
                        <div class="mb-3">
                            <label class="form-label" for="option_text">Sentence wording</label>
                            <input type="text" class="form-control" id="option_text" name="text" maxlength="255"
                                placeholder="e.g. Minimum 1 year">
                            <div class="form-text">Leave empty to use the chip label.</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="option_connector">Connector override</label>
                                <input type="text" class="form-control" id="option_connector" name="connector" maxlength="100"
                                    placeholder="e.g. ; experience in">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="option_suffix">Suffix</label>
                                <input type="text" class="form-control" id="option_suffix" name="suffix" maxlength="150"
                                    placeholder="e.g. is an advantage">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="modal-sentence-preview">
                            <span class="text-muted">Reads as:</span> <span id="optionSentencePreview"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save option</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}">
    <script src="{{ asset('js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2@11.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const groupModal = new bootstrap.Modal('#groupModal');
            const optionModal = new bootstrap.Modal('#optionModal');
            const groupForm = document.getElementById('groupForm');
            const optionForm = document.getElementById('optionForm');
            let activePanel = null; // panel the open modal will update
            let modalUrl = null;
            let modalMethod = 'POST';

            async function send(url, method, body = {}) {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: method === 'GET' ? undefined : JSON.stringify(body)
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw data;
                }
                return data;
            }

            function clearErrors(form) {
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
            }

            function showErrors(form, error) {
                clearErrors(form);
                const errors = error?.errors || {};
                let shown = false;
                Object.entries(errors).forEach(([name, messages]) => {
                    const input = form.querySelector(`[name="${name}"]`);
                    const feedback = input?.closest('.mb-3, [class*="col-"], .input-group')?.querySelector('.invalid-feedback');
                    if (input && feedback) {
                        input.classList.add('is-invalid');
                        feedback.textContent = messages.join(' ');
                        shown = true;
                    }
                });
                if (!shown) toastr.error(error?.message || 'Something went wrong. Please try again.');
            }

            function formValues(form) {
                const values = {};
                form.querySelectorAll('input[name]').forEach(input => {
                    values[input.name] = input.type === 'checkbox' ? (input.checked ? 1 : 0) : input.value;
                });
                return values;
            }

            function renderPanel(panel, html) {
                panel.innerHTML = html;
                initPanel(panel);

                // Keep the tab label, icon and option count in sync
                const meta = panel.querySelector('[data-panel-label]');
                const tab = document.getElementById(`req-tab-${panel.dataset.fieldId}`);
                if (meta && tab) {
                    tab.querySelector('.js-tab-label').textContent = meta.dataset.panelLabel;
                    tab.querySelector('.js-tab-count').textContent = meta.dataset.panelCount;
                    tab.querySelector('.js-tab-icon').setAttribute('icon', meta.dataset.panelIcon);
                }
            }

            async function run(panel, url, method, body, form = null) {
                panel.classList.add('is-loading');
                try {
                    const data = await send(url, method, body);
                    renderPanel(panel, data.html);
                    toastr.success(data.message);
                    return true;
                } catch (error) {
                    if (form) {
                        showErrors(form, error);
                    } else {
                        toastr.error(error?.message || 'Something went wrong. Please try again.');
                    }
                    return false;
                } finally {
                    panel.classList.remove('is-loading');
                }
            }

            function confirmDelete(title, text, confirmText = 'Yes, delete') {
                return Swal.fire({
                    title,
                    text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    confirmButtonColor: '#d33'
                }).then(result => result.isConfirmed);
            }

            function initPanel(panel) {
                SaleRequirements.init();

                const groupList = panel.querySelector('.js-sortable-groups');
                if (groupList) {
                    Sortable.create(groupList, {
                        handle: '.js-group-handle',
                        animation: 150,
                        onEnd: () => run(panel, groupList.dataset.reorderUrl, 'POST', {
                            type: 'groups',
                            ids: Array.from(groupList.children).map(el => el.dataset.id)
                        })
                    });
                }

                panel.querySelectorAll('.js-sortable-options').forEach(list => {
                    Sortable.create(list, {
                        handle: '.js-option-handle',
                        animation: 150,
                        filter: '.js-empty',
                        onEnd: () => run(panel, groupList.dataset.reorderUrl, 'POST', {
                            type: 'options',
                            group_id: list.dataset.groupId,
                            ids: Array.from(list.querySelectorAll('[data-id]')).map(el => el.dataset.id)
                        })
                    });
                });
            }

            // Sentence preview in the option modal for pick-one options
            function updateOptionPreview() {
                const label = optionForm.label.value.trim() || 'Option';
                const text = optionForm.text.value.trim() || label;
                const connector = optionForm.connector.value.trim() || (activePanel?.querySelector('[name="connector"]')?.value.trim() || '');
                const suffix = optionForm.suffix.value.trim();
                const glue = part => !part ? '' : (/^[;,.:]/.test(part) ? part : ' ' + part);
                document.getElementById('optionSentencePreview').textContent =
                    `${text}${glue(connector)} Care Home and Dementia Care${glue(suffix)}.`;
            }

            function openOptionModal(button, option = null) {
                const single = button.dataset.single === '1';
                activePanel = button.closest('.js-field-panel');
                modalUrl = button.dataset.url;
                modalMethod = option ? 'PUT' : 'POST';

                optionForm.reset();
                clearErrors(optionForm);
                document.getElementById('optionModalTitle').textContent = option ? 'Edit option' : 'Add option';
                document.getElementById('optionModalGroup').textContent = `Group: ${button.dataset.groupTitle}`;
                document.getElementById('optionSingleFields').classList.toggle('d-none', !single);

                if (option) {
                    optionForm.label.value = option.label || '';
                    optionForm.text.value = option.text || '';
                    optionForm.connector.value = option.connector || '';
                    optionForm.suffix.value = option.suffix || '';
                }
                updateOptionPreview();
                optionModal.show();
            }

            function openGroupModal(button, group = null) {
                activePanel = button.closest('.js-field-panel');
                modalUrl = button.dataset.url;
                modalMethod = group ? 'PUT' : 'POST';

                groupForm.reset();
                clearErrors(groupForm);
                document.getElementById('groupModalTitle').textContent = group ? 'Edit group' : 'Add group';
                if (group) {
                    groupForm.title.value = group.title;
                    groupForm.is_single.checked = !!group.is_single;
                    document.getElementById('group_prefix').value = group.prefix || '';
                    document.getElementById('group_has_quantity').checked = !!group.has_quantity;
                }
                updateGroupFields();
                groupModal.show();
            }

            // Group prefix and number only apply to multi-select groups
            function updateGroupFields() {
                const single = document.getElementById('group_is_single').checked;
                const prefix = document.getElementById('group_prefix').value.trim();
                const quantity = document.getElementById('group_has_quantity').checked;
                document.getElementById('groupLeadFields').classList.toggle('d-none', single);
                document.getElementById('groupSentencePreview').textContent = prefix || quantity ?
                    [quantity ? '3' : '', prefix, '10 am to 11:30 pm'].filter(Boolean).join(' ') :
                    'Day Shifts, Night Shifts and Weekends (listed with the other groups)';
            }
            groupForm.addEventListener('input', updateGroupFields);
            groupForm.addEventListener('change', updateGroupFields);

            document.getElementById('groupModal').addEventListener('shown.bs.modal', () => groupForm.title.focus());
            document.getElementById('optionModal').addEventListener('shown.bs.modal', () => optionForm.label.focus());
            optionForm.addEventListener('input', updateOptionPreview);

            groupForm.addEventListener('submit', async e => {
                e.preventDefault();
                if (await run(activePanel, modalUrl, modalMethod, formValues(groupForm), groupForm)) groupModal.hide();
            });

            optionForm.addEventListener('submit', async e => {
                e.preventDefault();
                if (await run(activePanel, modalUrl, modalMethod, formValues(optionForm), optionForm)) optionModal.hide();
            });

            // Field settings forms, actions and toggles inside panels (delegated: panels are re-rendered)
            document.addEventListener('submit', e => {
                const form = e.target.closest('.js-field-form');
                if (!form) return;
                e.preventDefault();
                run(form.closest('.js-field-panel'), form.dataset.url, 'PUT', formValues(form), form);
            });

            document.addEventListener('input', e => {
                if (!e.target.matches('.js-icon-input')) return;
                const icon = e.target.value.trim() || 'solar:checklist-minimalistic-bold-duotone';
                e.target.closest('.input-group').querySelector('.js-icon-preview iconify-icon').setAttribute('icon', icon);
            });

            document.addEventListener('change', e => {
                if (!e.target.matches('.js-toggle-option')) return;
                run(e.target.closest('.js-field-panel'), e.target.dataset.url, 'PATCH');
            });

            document.addEventListener('click', async e => {
                const button = e.target.closest('[data-action]');
                if (!button) return;
                const panel = button.closest('.js-field-panel');

                switch (button.dataset.action) {
                    case 'add-group':
                        openGroupModal(button);
                        break;
                    case 'edit-group':
                        openGroupModal(button, JSON.parse(button.dataset.group));
                        break;
                    case 'add-option':
                        openOptionModal(button);
                        break;
                    case 'edit-option':
                        openOptionModal(button, JSON.parse(button.dataset.option));
                        break;
                    case 'delete-group': {
                        const count = Number(button.dataset.count);
                        const detail = count ? `Its ${count} option${count === 1 ? '' : 's'} will be deleted too.` : '';
                        if (await confirmDelete(`Delete "${button.dataset.label}"?`, detail)) {
                            run(panel, button.dataset.url, 'DELETE');
                        }
                        break;
                    }
                    case 'delete-option':
                        if (await confirmDelete(`Delete "${button.dataset.label}"?`,
                                'Sales already saved keep their text. To keep it for later, hide it instead.')) {
                            run(panel, button.dataset.url, 'DELETE');
                        }
                        break;
                    case 'restore-field':
                        if (await confirmDelete(`Restore ${button.dataset.label} defaults?`,
                                'All groups and options for this field will be replaced with the original defaults.', 'Yes, restore')) {
                            run(panel, button.dataset.url, 'POST');
                        }
                        break;
                }
            });

            document.querySelectorAll('.js-field-panel').forEach(initPanel);
        });
    </script>
@endsection

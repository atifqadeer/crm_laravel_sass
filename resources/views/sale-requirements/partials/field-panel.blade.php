{{--
    Admin panel for one sale requirement field. Re-rendered by SaleRequirementController after every change.

    @param \Horsefly\SaleRequirementField $field  with groups.options loaded
--}}
@php
    $allOptions = $field->groups->flatMap->options;
    $hasDefaults = array_key_exists($field->key, config('sale_requirements', []));
@endphp
<span class="d-none" data-panel-label="{{ $field->label }}" data-panel-count="{{ $allOptions->where('is_active', true)->count() }}"
    data-panel-icon="{{ $field->icon ?: 'solar:checklist-minimalistic-bold-duotone' }}"></span>

<div class="row g-3">
    <div class="col-xxl-7 col-xl-7">
        {{-- Field settings --}}
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1">
                    <h5 class="card-title mb-0">Field settings</h5>
                    <p class="text-muted fs-13 mb-0">Saved into the <code>sales.{{ $field->key }}</code> column as one sentence.</p>
                </div>
                @if ($hasDefaults)
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="restore-field"
                        data-url="{{ route('sale-requirements.fields.restore', $field) }}" data-label="{{ $field->label }}">
                        <iconify-icon icon="solar:restart-linear" class="align-middle me-1"></iconify-icon>Restore defaults
                    </button>
                @endif
            </div>
            <div class="card-body">
                <form class="js-field-form" data-url="{{ route('sale-requirements.fields.update', $field) }}" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="label_{{ $field->id }}">Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="label_{{ $field->id }}" name="label"
                                value="{{ $field->label }}" maxlength="100" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="icon_{{ $field->id }}">Icon</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text js-icon-preview">
                                    <iconify-icon icon="{{ $field->icon ?: 'solar:checklist-minimalistic-bold-duotone' }}" class="fs-18"></iconify-icon>
                                </span>
                                <input type="text" class="form-control js-icon-input" id="icon_{{ $field->id }}" name="icon"
                                    value="{{ $field->icon }}" maxlength="100" placeholder="solar:clock-circle-bold-duotone">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="hint_{{ $field->id }}">Hint</label>
                            <input type="text" class="form-control" id="hint_{{ $field->id }}" name="hint"
                                value="{{ $field->hint }}" maxlength="255" placeholder="Short help shown under the label">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="prefix_{{ $field->id }}">Prefix statement</label>
                            <input type="text" class="form-control" id="prefix_{{ $field->id }}" name="prefix"
                                value="{{ $field->prefix }}" maxlength="255" placeholder="e.g. Benefits include:">
                            <div class="form-text">Starts the saved sentence. Shown read-only on the sale form; end it with ":" so older sales still match after a change.</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="connector_{{ $field->id }}">Pick-one connector</label>
                            <input type="text" class="form-control" id="connector_{{ $field->id }}" name="connector"
                                value="{{ $field->connector }}" maxlength="100" placeholder="e.g. in">
                            <div class="form-text">Joins the pick-one choice to the other options: "Minimum 1 year <b>in</b> Care Home".</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex flex-wrap gap-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_required_{{ $field->id }}"
                                        name="is_required" value="1" @checked($field->is_required)>
                                    <label class="form-check-label" for="is_required_{{ $field->id }}">Required on the sale form</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="show_hours_{{ $field->id }}"
                                        name="show_hours" value="1" @checked($field->show_hours)>
                                    <label class="form-check-label" for="show_hours_{{ $field->id }}">Show "hours per week" input</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <iconify-icon icon="solar:diskette-linear" class="align-middle me-1"></iconify-icon>Save settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Groups & options --}}
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1">
                    <h5 class="card-title mb-0">Options</h5>
                    <p class="text-muted fs-13 mb-0">Drag <iconify-icon icon="solar:hamburger-menu-linear" class="align-middle"></iconify-icon>
                        to reorder. Hidden options stay saved but are not offered on the sale form.</p>
                </div>
                <button type="button" class="btn btn-sm btn-primary" data-action="add-group"
                    data-url="{{ route('sale-requirements.groups.store', $field) }}">
                    <iconify-icon icon="solar:add-circle-linear" class="align-middle me-1"></iconify-icon>Add group
                </button>
            </div>
            <div class="card-body">
                @if ($field->groups->isEmpty())
                    <div class="text-center text-muted py-4">
                        <iconify-icon icon="solar:box-minimalistic-linear" class="fs-32 d-block mx-auto mb-2"></iconify-icon>
                        No groups yet. Add a group, then add options to it.
                    </div>
                @endif

                <div class="js-sortable-groups d-flex flex-column gap-3"
                    data-reorder-url="{{ route('sale-requirements.fields.reorder', $field) }}">
                    @foreach ($field->groups as $group)
                        <div class="req-admin-group" data-id="{{ $group->id }}">
                            <div class="req-admin-group-header">
                                <span class="req-admin-handle js-group-handle" title="Drag to reorder group">
                                    <iconify-icon icon="solar:hamburger-menu-linear"></iconify-icon>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <span class="fw-semibold">{{ $group->title }}</span>
                                    @if ($group->is_single)
                                        <span class="badge bg-info-subtle text-info ms-1">Pick one</span>
                                    @endif
                                    @if ($group->has_quantity || filled($group->prefix))
                                        <span class="badge bg-primary-subtle text-primary ms-1" title="Placed before this group's selected chips">
                                            Reads: "{{ trim(($group->has_quantity ? '[number] ' : '') . $group->prefix) }} …"
                                        </span>
                                    @endif
                                    <span class="text-muted fs-12 ms-1">
                                        {{ $group->options->where('is_active', true)->count() }} of {{ $group->options->count() }} shown
                                    </span>
                                </div>
                                <button type="button" class="btn btn-sm btn-soft-primary" data-action="add-option"
                                    data-url="{{ route('sale-requirements.options.store', $group) }}"
                                    data-single="{{ $group->is_single ? 1 : 0 }}" data-group-title="{{ $group->title }}">
                                    <iconify-icon icon="solar:add-circle-linear" class="align-middle"></iconify-icon>
                                    <span class="d-none d-sm-inline">Option</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-light" title="Edit group" data-action="edit-group"
                                    data-url="{{ route('sale-requirements.groups.update', $group) }}"
                                    data-group="{{ json_encode(['title' => $group->title, 'is_single' => $group->is_single, 'prefix' => $group->prefix, 'has_quantity' => $group->has_quantity]) }}">
                                    <iconify-icon icon="solar:pen-linear"></iconify-icon>
                                </button>
                                <button type="button" class="btn btn-sm btn-light text-danger" title="Delete group" data-action="delete-group"
                                    data-url="{{ route('sale-requirements.groups.destroy', $group) }}" data-label="{{ $group->title }}"
                                    data-count="{{ $group->options->count() }}">
                                    <iconify-icon icon="solar:trash-bin-minimalistic-linear"></iconify-icon>
                                </button>
                            </div>

                            <ul class="list-unstyled mb-0 js-sortable-options" data-group-id="{{ $group->id }}">
                                @forelse ($group->options as $option)
                                    <li class="req-admin-option {{ $option->is_active ? '' : 'is-hidden' }}" data-id="{{ $option->id }}">
                                        <span class="req-admin-handle js-option-handle" title="Drag to reorder">
                                            <iconify-icon icon="solar:hamburger-menu-linear"></iconify-icon>
                                        </span>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-truncate">{{ $option->label }}</div>
                                            @if ($group->is_single && ($option->text || $option->connector || $option->suffix))
                                                <div class="text-muted fs-12 text-truncate">
                                                    Reads: "{{ $option->text ?: $option->label }}{{ $option->connector ? ' ' . $option->connector . ' …' : '' }}{{ $option->suffix ? ' ' . $option->suffix : '' }}"
                                                </div>
                                            @endif
                                        </div>
                                        <div class="form-check form-switch mb-0" title="{{ $option->is_active ? 'Shown on sale form' : 'Hidden from sale form' }}">
                                            <input class="form-check-input js-toggle-option" type="checkbox" role="switch"
                                                aria-label="Show {{ $option->label }} on the sale form"
                                                data-url="{{ route('sale-requirements.options.toggle', $option) }}" @checked($option->is_active)>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-link text-muted p-1" title="Edit option" data-action="edit-option"
                                            data-url="{{ route('sale-requirements.options.update', $option) }}"
                                            data-single="{{ $group->is_single ? 1 : 0 }}" data-group-title="{{ $group->title }}"
                                            data-option="{{ json_encode($option->only(['label', 'text', 'connector', 'suffix'])) }}">
                                            <iconify-icon icon="solar:pen-linear" class="fs-16"></iconify-icon>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-1" title="Delete option" data-action="delete-option"
                                            data-url="{{ route('sale-requirements.options.destroy', $option) }}" data-label="{{ $option->label }}">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-linear" class="fs-16"></iconify-icon>
                                        </button>
                                    </li>
                                @empty
                                    <li class="text-muted fs-13 px-2 py-2 js-empty">No options in this group yet.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Live preview using the real sale form picker --}}
    <div class="col-xxl-5 col-xl-5">
        <div class="req-admin-preview">
            <div class="d-flex align-items-center gap-2 mb-2">
                <iconify-icon icon="solar:eye-bold-duotone" class="fs-20 text-primary"></iconify-icon>
                <h5 class="mb-0">Preview on the sale form</h5>
            </div>
            <p class="text-muted fs-13">Try it: select options to see the sentence that will be saved.</p>
            @include('sales.partials.requirement-picker', ['key' => $field->key, 'field' => $field->toFormArray()])
        </div>
    </div>
</div>

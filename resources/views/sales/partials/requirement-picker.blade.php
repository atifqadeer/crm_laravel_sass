{{--
    Chip picker for a sale requirement field (timing, experience, benefits, qualification).
    Selected chips are composed by JS into the hidden "{{ $key }}" input as "<prefix> A, B and C."
    Chip inputs have no name, so only the composed value is submitted.

    @param string      $key    sales column name
    @param array       $field  entry from \Horsefly\SaleRequirementField::forForm()
    @param string|null $value  saved value to pre-select (edit form); text that can't be matched is kept as-is
--}}
<div class="mb-3 h-100">
    <div class="req-card" data-req-field="{{ $key }}" data-connector="{{ $field['connector'] ?? '' }}"
        @if (filled($value ?? null)) data-initial="{{ $value }}" @endif>
        <div class="d-flex align-items-start gap-2 mb-3">
            <span class="req-card-icon"><iconify-icon icon="{{ $field['icon'] }}"></iconify-icon></span>
            <div class="flex-grow-1 min-w-0">
                <h5 class="mb-0 fs-15">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)
                        <span class="text-danger">*</span>
                    @endif
                </h5>
                <p class="text-muted fs-12 mb-0">{{ $field['hint'] }}</p>
            </div>
            <span class="badge rounded-pill bg-primary-subtle text-primary req-count d-none"></span>
            <button type="button" class="btn btn-link btn-sm p-0 text-muted req-clear" title="Clear selection">
                <iconify-icon icon="solar:restart-linear" class="fs-18"></iconify-icon>
            </button>
        </div>

        {{-- Prefix is managed in Administrator > Sale Requirements, not per sale --}}
        <input type="hidden" class="req-prefix" value="{{ $field['prefix'] }}">
        @if (filled($field['prefix']))
            <div class="req-prefix-display" title="Set in Administrator > Sale Requirements">
                <iconify-icon icon="solar:text-field-linear" class="fs-16 flex-shrink-0"></iconify-icon>
                <span class="text-muted">Starts with</span>
                <span class="fw-semibold text-truncate">{{ $field['prefix'] }}</span>
                <iconify-icon icon="solar:lock-keyhole-minimalistic-linear" class="ms-auto text-muted flex-shrink-0"></iconify-icon>
            </div>
        @endif

        @if (!empty($field['lead']))
            <div class="req-group-title">{{ $field['lead']['title'] }} <span class="text-lowercase fw-normal">(pick one)</span></div>
            <div class="req-chips">
                @foreach ($field['lead']['options'] as $option)
                    <input type="checkbox" class="btn-check req-lead" id="{{ $key }}_lead_{{ $loop->index }}"
                        value="{{ $option['text'] }}" autocomplete="off"
                        @isset($option['connector']) data-connector="{{ $option['connector'] }}" @endisset
                        @isset($option['suffix']) data-suffix="{{ $option['suffix'] }}" @endisset>
                    <label class="req-chip" for="{{ $key }}_lead_{{ $loop->index }}">
                        <iconify-icon icon="solar:check-circle-bold" class="req-chip-tick"></iconify-icon>{{ $option['label'] }}
                    </label>
                @endforeach
            </div>
        @endif

        @foreach ($field['groups'] as $group)
            {{-- A group with a prefix and/or number reads as its own part: "[number] [prefix] A and B" --}}
            <div class="req-group" data-prefix="{{ $group['prefix'] ?? '' }}"
                data-quantity="{{ !empty($group['quantity']) ? 1 : 0 }}">
                <div class="req-group-title">{{ $group['title'] }}</div>
                @if (!empty($group['quantity']) || filled($group['prefix'] ?? null))
                    <div class="req-group-lead">
                        @if (!empty($group['quantity']))
                            <input type="number" class="form-control form-control-sm req-qty" min="0" step="any"
                                placeholder="No." aria-label="{{ $group['title'] }} number">
                        @endif
                        @if (filled($group['prefix'] ?? null))
                            <span class="req-group-prefix">{{ $group['prefix'] }}</span>
                        @endif
                        @if (!empty($group['options']))
                            <iconify-icon icon="solar:arrow-right-linear" class="text-muted"></iconify-icon>
                        @endif
                    </div>
                @endif
                @if (!empty($group['options']))
                    <div class="req-chips">
                        @foreach ($group['options'] as $option)
                            @php($optionId = $key . '_g' . $loop->parent->index . '_' . $loop->index)
                            <input type="checkbox" class="btn-check req-option" id="{{ $optionId }}" value="{{ $option }}"
                                autocomplete="off">
                            <label class="req-chip" for="{{ $optionId }}">
                                <iconify-icon icon="solar:check-circle-bold" class="req-chip-tick"></iconify-icon>{{ $option }}
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        <div class="req-group-title">Other</div>
        <div class="req-chips req-custom-list mb-2"></div>
        <div class="row g-2">
            <div class="{{ !empty($field['hours']) ? 'col-sm-7' : 'col-12' }}">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control req-custom-input" maxlength="80"
                        placeholder="Add your own and press Enter" aria-label="Add custom {{ strtolower($field['label']) }}">
                    <button type="button" class="btn btn-outline-primary req-custom-add">
                        <iconify-icon icon="solar:add-circle-linear" class="align-middle"></iconify-icon> Add
                    </button>
                </div>
            </div>
            @if (!empty($field['hours']))
                <div class="col-sm-5">
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control req-hours" min="1" max="84" step="0.5"
                            placeholder="e.g. 37.5" aria-label="Hours per week">
                        <span class="input-group-text">hrs / week</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="req-preview is-empty">
            <iconify-icon icon="solar:eye-linear" class="fs-16 flex-shrink-0 mt-1"></iconify-icon>
            <span class="req-preview-text">Nothing selected yet — this is how it will appear on the job portal.</span>
        </div>

        <input type="hidden" class="req-value" id="{{ $key }}" name="{{ $key }}"
            data-required="{{ ($field['required'] ?? false) ? '1' : '0' }}">
        <div class="invalid-feedback">Please select at least one {{ strtolower($field['label']) }} option</div>
    </div>
</div>

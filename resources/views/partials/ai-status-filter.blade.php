{{--
    AI Status filter dropdown (applicants list, category-wise resources, sale radius page).
    The selection lives in window.aiStatusFilter; partials/ai-call-history.blade.php
    keeps it up to date and fires "ai-status-filter:changed" on document.
--}}
@if (\App\Support\AiCallStatus::enabled())
    <div class="dropdown d-inline">
        <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="aiStatusFilterButton"
            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Filter by AI agent call status">
            <i class="ri-robot-2-line me-1"></i> <span id="showFilterAiStatus">All AI Status</span>
        </button>
        <div class="dropdown-menu p-2" aria-labelledby="aiStatusFilterButton" style="min-width: 220px;">
            <div class="d-flex justify-content-end px-1 mb-1">
                <a href="#" class="ai-status-filter-clear text-danger small fw-semibold">Clear</a>
            </div>
            @foreach (\App\Support\AiCallStatus::filterOptions() as $value => $label)
                <div class="form-check">
                    <input class="form-check-input ai-status-filter-check" type="checkbox" value="{{ $value }}"
                        id="ai_status_{{ $value }}">
                    <label class="form-check-label" for="ai_status_{{ $value }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
    </div>
@endif

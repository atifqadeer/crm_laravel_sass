{{--
    AI agent call history modal + AI Status filter wiring.
    Include once per page, after jQuery. Badges come from App\Support\AiCallStatus::badge().
    Pages listen for: $(document).on('ai-status-filter:changed', () => table.ajax.reload());
--}}
@if (\App\Support\AiCallStatus::enabled())
    <style>
        #aiCallHistoryModal .ai-call {
            border-left: 3px solid var(--bs-border-color);
            padding: .5rem .75rem;
            margin-bottom: .75rem;
        }

        #aiCallHistoryModal .ai-transcript {
            max-height: 240px;
            overflow: auto;
            white-space: pre-wrap;
            background: var(--bs-tertiary-bg);
            border-radius: .375rem;
            padding: .5rem;
            font-size: .8rem;
        }
    </style>

    <div class="modal fade" id="aiCallHistoryModal" tabindex="-1" aria-labelledby="aiCallHistoryLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aiCallHistoryLabel"><i class="ri-robot-2-line me-1"></i> AI agent calls</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="aiCallHistoryBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const HISTORY_URL = @json(url('/ai-calls'));
            const labels = @json(\App\Support\AiCallStatus::filterOptions());

            function esc(value) {
                return String(value ?? '').replace(/[&<>"']/g, c => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                } [c]));
            }

            function safeUrl(url) {
                return /^https?:\/\//i.test(url || '') ? url : null;
            }

            // ── AI Status filter ────────────────────────────────
            window.aiStatusFilter = [];

            function syncAiFilter() {
                window.aiStatusFilter = $('.ai-status-filter-check:checked').map(function() {
                    return this.value;
                }).get();
                const n = window.aiStatusFilter.length;
                $('#showFilterAiStatus').text(
                    n === 0 ? 'All AI Status' : (n === 1 ? labels[window.aiStatusFilter[0]] : n + ' AI Statuses')
                );
                $(document).trigger('ai-status-filter:changed', [window.aiStatusFilter]);
            }

            $(document).on('change', '.ai-status-filter-check', syncAiFilter);
            $(document).on('click', '.ai-status-filter-clear', function(e) {
                e.preventDefault();
                $('.ai-status-filter-check').prop('checked', false);
                syncAiFilter();
            });

            // ── History modal ──────────────────────────────────
            function renderCalls(res) {
                if (!res.calls || !res.calls.length) {
                    return '<p class="text-muted mb-0">The AI agent has not called this applicant' +
                        (res.sale_id ? ' about this job' : '') + ' yet.</p>';
                }
                return res.calls.map(call => {
                    const job = call.job ?
                        `#${esc(call.job.id)} ${esc(call.job.title || '')} — ${esc(call.job.office || '')} / ${esc(call.job.unit || '')} (${esc(call.job.postcode)})` :
                        'Job removed';
                    const minutes = call.duration_seconds != null ?
                        `${Math.floor(call.duration_seconds / 60)}m ${call.duration_seconds % 60}s` : '';
                    const rec = safeUrl(call.recording_url);
                    return `<div class="ai-call">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div><span class="badge ${esc(call.badge_class)}">${esc(call.status_label)}</span>
                                <span class="small text-muted ms-1">${esc(call.called_at)}${minutes ? ' · ' + minutes : ''}</span></div>
                            <div class="small text-muted">Call ID ${esc(call.call_id)}</div>
                        </div>
                        <div class="small mt-1"><strong>Job:</strong> ${job}</div>
                        ${call.callback_at ? `<div class="small text-info"><strong>Call back:</strong> ${esc(call.callback_at)}</div>` : ''}
                        ${call.summary ? `<div class="mt-1">${esc(call.summary)}</div>` : ''}
                        ${rec ? `<div class="small mt-1"><a href="${esc(rec)}" target="_blank" rel="noopener noreferrer"><i class="ri-volume-up-line"></i> Recording</a></div>` : ''}
                        ${call.transcript ? `<details class="mt-1"><summary class="small">Transcript</summary><div class="ai-transcript mt-1">${esc(call.transcript)}</div></details>` : ''}
                    </div>`;
                }).join('');
            }

            $(document).on('click', '.ai-status-badge', function(e) {
                e.preventDefault();
                const applicantId = $(this).data('applicant-id');
                const saleId = $(this).data('sale-id');
                const $body = $('#aiCallHistoryBody').html(
                    '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>'
                );
                $('#aiCallHistoryLabel').html('<i class="ri-robot-2-line me-1"></i> AI agent calls' +
                    (saleId ? ' for this job' : ''));
                bootstrap.Modal.getOrCreateInstance(document.getElementById('aiCallHistoryModal')).show();

                $.getJSON(`${HISTORY_URL}/${encodeURIComponent(applicantId)}`, saleId ? { sale_id: saleId } : {})
                    .done(res => $body.html(renderCalls(res)))
                    .fail(() => $body.html('<p class="text-danger mb-0">Could not load the call history.</p>'));
            });
        })();
    </script>
@endif

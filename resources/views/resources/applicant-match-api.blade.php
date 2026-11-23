@extends('layouts.vertical', ['title' => 'Applicant Match API', 'subTitle' => 'Administrator'])
@section('css')
    <style>
        .sale-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--bs-border-color);
            border-radius: .375rem;
        }

        .sale-list label {
            display: flex;
            gap: .5rem;
            align-items: flex-start;
            padding: .4rem .75rem;
            margin: 0;
            cursor: pointer;
            border-bottom: 1px solid var(--bs-border-color-translucent);
        }

        .sale-list label:hover {
            background: var(--bs-tertiary-bg);
        }

        .sale-list .sale-meta {
            font-size: .78rem;
            color: var(--bs-secondary-color);
        }

        .sale-chip {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            margin: 0 .25rem .25rem 0;
        }

        .sale-chip .remove-chip {
            cursor: pointer;
        }

        pre.json-view,
        pre.code-view {
            max-height: 520px;
            overflow: auto;
            background: var(--bs-tertiary-bg);
            border-radius: .375rem;
            padding: .75rem;
            font-size: .8rem;
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
        }

        #resultsTable td {
            vertical-align: top;
            font-size: .82rem;
        }

        #resultsTable td.long-text {
            max-width: 280px;
            white-space: normal;
        }

        .clamp {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
@endsection
@section('content')
    <ul class="nav nav-tabs mb-3" id="pageTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="previewTabBtn" data-bs-toggle="tab" data-bs-target="#pagePreview"
                type="button" role="tab" aria-controls="pagePreview" aria-selected="true">
                <i class="ri-table-line me-1"></i> Preview
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="apiTabBtn" data-bs-toggle="tab" data-bs-target="#pageApiReference"
                type="button" role="tab" aria-controls="pageApiReference" aria-selected="false">
                <i class="ri-code-s-slash-line me-1"></i> API Reference
            </button>
        </li>
    </ul>
    <div class="tab-content">
    <div class="tab-pane fade show active" id="pagePreview" role="tabpanel" aria-labelledby="previewTabBtn">
    <div class="row">
        {{-- ── What API 1 is serving right now ──────────────────────── --}}
        <div class="col-12">
            <div class="card border-start border-4" id="publishedCard">
                <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div id="publishedInfo"></div>
                    <button type="button" class="btn btn-sm btn-outline-danger d-none" id="unpublishBtn">
                        <i class="ri-stop-circle-line me-1"></i> Stop publishing
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Filters ─────────────────────────────────────────────── --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Select sales and radius</h5>
                    <p class="text-muted mb-0 small">Only active sales (open, not on hold) with a location are listed.</p>
                </div>
                <div class="card-body">
                  <div class="row g-4">
                   <div class="col-lg-7">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0 fw-semibold">Sales <span class="badge bg-primary" id="selectedCount">0</span>
                            <span class="text-muted small fw-normal">/ max {{ $maxSales }}</span></label>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" id="selectVisible">Select visible</button>
                            <button type="button" class="btn btn-outline-secondary" id="clearSales">Clear</button>
                        </div>
                    </div>
                    <input type="text" id="saleSearch" class="form-control form-control-sm mb-2"
                        placeholder="Search by id, title, office, unit or postcode…">
                    <div class="sale-list" id="saleList">
                        @forelse ($sales as $sale)
                            @php
                                $label = trim(($sale->job_title ?? 'Untitled') . ' — ' . ($sale->office_name ?? '-') . ' / ' . ($sale->unit_name ?? '-'));
                                $search = strtolower($sale->id . ' ' . $label . ' ' . $sale->sale_postcode . ' ' . $sale->job_category . ' ' . $sale->job_type);
                            @endphp
                            <label data-search="{{ $search }}">
                                <input type="checkbox" class="form-check-input sale-check mt-1" value="{{ $sale->id }}"
                                    data-label="#{{ $sale->id }} {{ $sale->job_title ?? 'Untitled' }} ({{ strtoupper($sale->sale_postcode) }})">
                                <span>
                                    <span class="fw-semibold">#{{ $sale->id }}</span> {{ $label }}
                                    <span class="d-block sale-meta">
                                        {{ strtoupper($sale->sale_postcode) }} · {{ ucwords($sale->job_category ?? '-') }} · {{ ucwords(str_replace('-', ' ', $sale->job_type ?? '-')) }}
                                    </span>
                                </span>
                            </label>
                        @empty
                            <div class="p-3 text-muted">No active sales with a location.</div>
                        @endforelse
                        <div class="p-3 text-muted d-none" id="saleListEmpty">No sales match your search.</div>
                    </div>
                    <div class="mt-2" id="selectedChips"></div>
                   </div>

                   <div class="col-lg-5">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="radiusInput">Radius</label>
                            <input type="number" class="form-control" id="radiusInput" min="0.1" step="0.1" value="15"
                                list="radiusPresets" placeholder="Type or pick">
                            <datalist id="radiusPresets">
                                <option value="5"></option>
                                <option value="10"></option>
                                <option value="15"></option>
                                <option value="20"></option>
                                <option value="25"></option>
                                <option value="30"></option>
                                <option value="50"></option>
                            </datalist>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="unitSelect">Unit</label>
                            <select class="form-select" id="unitSelect">
                                <option value="km" selected>Kilometres</option>
                                <option value="miles">Miles</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="limitInput">API send limit</label>
                            <input type="number" class="form-control" id="limitInput" min="1" max="{{ $maxLimit }}" step="1"
                                value="{{ $defaultLimit }}" list="limitPresets" placeholder="Max records">
                            <datalist id="limitPresets">
                                <option value="100"></option>
                                <option value="250"></option>
                                <option value="500"></option>
                                <option value="1000"></option>
                                <option value="2500"></option>
                                <option value="5000"></option>
                                <option value="10000"></option>
                            </datalist>
                            <div class="form-text">Most records API 1 sends in total (max {{ number_format($maxLimit) }}).</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="formatSelect">Text fields</label>
                            <select class="form-select" id="formatSelect">
                                <option value="text" selected>Plain text</option>
                                <option value="html">HTML (as stored)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="matchCategory" checked>
                                <label class="form-check-label" for="matchCategory">Same job category as the sale</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-outline-primary flex-fill" id="fetchBtn">
                            <i class="ri-search-line me-1"></i> Fetch matching applicants
                        </button>
                        <button type="button" class="btn btn-success flex-fill" id="publishBtn">
                            <i class="ri-broadcast-line me-1"></i> Publish to API
                        </button>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Fetch shows a preview here only. Publish makes this selection what
                        API 1 sends; the other side never needs the sale ids.</p>
                   </div>
                  </div>
                </div>
            </div>
        </div>

        {{-- ── Results ─────────────────────────────────────────────── --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="card-title mb-0">API response</h5>
                        <p class="text-muted mb-0 small" id="resultSummary">Pick sales and a radius, then fetch.</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <ul class="nav nav-pills nav-sm" role="tablist">
                            <li class="nav-item"><button class="nav-link active py-1 px-2" data-bs-toggle="tab"
                                    data-bs-target="#tabTable" type="button">Table</button></li>
                            <li class="nav-item"><button class="nav-link py-1 px-2" data-bs-toggle="tab"
                                    data-bs-target="#tabJson" type="button">JSON</button></li>
                        </ul>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="downloadJson" disabled
                            title="Download the current table page as JSON"><i class="ri-download-2-line"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="perSaleSummary" class="mb-2"></div>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tabTable">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0 w-100" id="resultsTable">
                                    <thead class="table-light">
                                        <tr>
                                            @foreach ($fields as $field)
                                                <th class="text-nowrap">{{ $field }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tabJson">
                            <p class="small text-muted">The response for the table page shown, exactly as API 1 would page it.</p>
                            <pre class="json-view" id="jsonView">{}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    {{-- ── API reference tab ─────────────────────────────────────── --}}
    <div class="tab-pane fade" id="pageApiReference" role="tabpanel" aria-labelledby="apiTabBtn">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">API 1 · Job matches <span class="badge bg-success align-middle">GET</span></h5>
                    <p class="text-muted mb-0 small">Sends out the applicants for the selection published on the Preview tab. The caller doesn't send sale ids or a radius.</p>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <p class="mb-2"><span class="badge bg-success">GET</span>
                                <code>{{ url('/api/applicants/job-matches') }}</code></p>
                            <h6>Authentication (both APIs)</h6>
                            <p class="small mb-2">Send the API key stored in Settings as <code>api_key_ai_automation</code>
                                (its own key, separate from the open-sales feed) in one of:
                                <code>Authorization: Bearer &lt;token&gt;</code>, <code>X-API-Token: &lt;token&gt;</code> or
                                <code>api_token</code>. Limited to 60 requests per minute.</p>

                            <h6 class="mt-3">How it works</h6>
                            <ol class="small mb-2">
                                <li>On the Preview tab, choose the sales, radius, category match, text format and the
                                    <strong>API send limit</strong> (the most records API 1 sends in total).</li>
                                <li>Press <strong>Publish to API</strong>. From then on API 1 sends applicants for that selection.</li>
                                <li>Publish again to change it, or <strong>Stop publishing</strong> to make API 1 return no data.</li>
                            </ol>
                            <p class="small mb-2">The data is worked out at the moment of each call, so new applicants or
                                applicants who become ineligible are picked up without republishing. A published sale that is
                                later closed or put on hold is skipped automatically.</p>

                            <h6 class="mt-3">Query parameters</h6>
                            <table class="table table-sm small">
                                <thead class="table-light">
                                    <tr><th>Name</th><th>Required</th><th>Description</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>page</code></td><td>No</td><td>Page number, default 1.</td></tr>
                                    <tr><td><code>per_page</code></td><td>No</td><td>Records per page, default 100, max {{ $maxPerPage }}. Paging never goes past the send limit set in the CRM: <code>meta.total</code> is the number of records that will be sent.</td></tr>
                                </tbody>
                            </table>
                            <p class="small text-muted mb-0">Anything else sent (for example <code>sale_ids</code> or <code>radius</code>) is ignored. Read
                                pages until <code>meta.has_more</code> is <code>false</code>. If nothing is published,
                                <code>data</code> is empty and <code>message</code> says so.</p>

                            <h6 class="mt-3">Who is included</h6>
                            <ul class="small mb-0">
                                <li>Applicants that are active, not blocked, not deleted and have a location.</li>
                                <li>Not paid (<code>paid_status</code> isn't <code>close</code> and not in CRM paid).</li>
                                <li>No active rejection in <code>crm_rejected_cv</code> (<code>status = 1</code>).</li>
                                <li>Within the radius of a published sale. One row per applicant per sale, so an applicant near 3 sales appears 3 times.</li>
                                <li>Order: the nearest applicant of every sale first, then the second nearest of every sale, and so on.
                                    So when the send limit cuts the list short, every sale still gets its closest applicants.</li>
                                <li>Sales: open (<code>status = 1</code>), not on hold, not deleted.</li>
                                <li><strong>Not called again needlessly</strong> (from the calls reported to API 3):
                                    <ul>
                                        <li>Never again, for any job, after <em>Do Not Call</em> or <em>Wrong Number</em>.</li>
                                        <li>Not again for the same job after <em>Interested</em>, <em>Not Interested</em> or <em>Not Looking</em>.</li>
                                        <li><em>Callback</em>: sent again once the callback time has passed.</li>
                                        <li><em>No Answer</em>, <em>Busy</em>, <em>Voicemail</em>, <em>Call Failed</em>: sent again after
                                            {{ \App\Support\AiCallStatus::RETRY_AFTER_HOURS }} hours, at most
                                            {{ \App\Support\AiCallStatus::MAX_RETRY_ATTEMPTS }} tries per job.</li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <h6>Fields</h6>
                            <table class="table table-sm small">
                                <thead class="table-light">
                                    <tr><th>Field</th><th>Source</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>applicant_id</code></td><td>applicants.id</td></tr>
                                    <tr><td><code>applicant_name</code></td><td>applicants.applicant_name</td></tr>
                                    <tr><td><code>phone</code></td><td>applicants.applicant_phone</td></tr>
                                    <tr><td><code>email</code></td><td>applicants.applicant_email</td></tr>
                                    <tr><td><code>job_id</code></td><td>sales.id</td></tr>
                                    <tr><td><code>job_title</code></td><td>job_titles.name (sales.job_title_id)</td></tr>
                                    <tr><td><code>employer</code></td><td>offices.office_name (sales.office_id)</td></tr>
                                    <tr><td><code>location</code></td><td>units.unit_name (sales.unit_id)</td></tr>
                                    <tr><td><code>salary</code></td><td>sales.salary</td></tr>
                                    <tr><td><code>benefits</code></td><td>sales.benefits</td></tr>
                                    <tr><td><code>qualifications</code></td><td>sales.qualification</td></tr>
                                    <tr><td><code>experience</code></td><td>sales.experience</td></tr>
                                    <tr><td><code>job_type</code></td><td>sales.job_type</td></tr>
                                    <tr><td><code>working_hours</code></td><td>sales.timing</td></tr>
                                    <tr><td><code>job_description</code></td><td>sales.job_description</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0">Example request</h6>
                                <button type="button" class="btn btn-sm btn-link copy-btn" data-copy="#curlGet">Copy</button>
                            </div>
                            <pre class="code-view mb-3" id="curlGet"></pre>
                            <h6 class="mb-1">Example response</h6>
<pre class="code-view">{
  "success": true,
  "fields": ["applicant_id", "applicant_name", "phone", "email", "job_id", "job_title", ...],
  "data": [
    {"applicant_id": 64528, "applicant_name": "Jane Smith", "phone": "07700900123", "email": "jane@example.com",
     "job_id": 17034, "job_title": "RGN", "employer": "...", "location": "...", "salary": "...", ...}
  ],
  "meta": {"total": 500, "count": 100, "page": 1, "per_page": 100, "last_page": 5, "has_more": true,
           "published_at": "2026-11-20T10:15:00+00:00", "generated_at": "2026-11-20T11:02:41+00:00"}
}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">API 2 · Update applicants <span class="badge bg-warning text-dark align-middle">POST</span></h5>
                    <p class="text-muted mb-0 small">Receives changes back and updates applicant records by <code>applicant_id</code>.</p>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <p class="mb-2"><span class="badge bg-warning text-dark">POST</span>
                                <code>{{ url('/api/applicants/update') }}</code></p>
                            <h6>Body (JSON)</h6>
                            <p class="small mb-2">Send one record, or up to {{ $maxUpdateRecords }} records in an
                                <code>applicants</code> array. Each record needs <code>applicant_id</code> and at least one
                                field to change. Fields you leave out stay as they are; unknown fields are ignored.</p>
                            <table class="table table-sm small">
                                <thead class="table-light">
                                    <tr><th>Field</th><th>Updates</th><th>Rules</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>applicant_id</code></td><td>(finds the record)</td><td>Required. An existing, not deleted applicant.</td></tr>
                                    <tr><td><code>applicant_name</code></td><td>applicants.applicant_name</td><td>Text, max 255.</td></tr>
                                    <tr><td><code>phone</code></td><td>applicants.applicant_phone</td><td>Digits only are kept, max 11. Must not be another applicant's phone, secondary phone or landline.</td></tr>
                                    <tr><td><code>email</code></td><td>applicants.applicant_email</td><td>Valid email, max 255. Must not be another applicant's email.</td></tr>
                                </tbody>
                            </table>
                            <h6 class="mt-3">Response</h6>
                            <ul class="small mb-0">
                                <li>One result per record, in the order sent: <code>updated</code> (with the fields that
                                    <code>changed</code>), <code>unchanged</code>, <code>not_found</code> or <code>invalid</code>
                                    (with <code>errors</code>).</li>
                                <li>Each record is saved on its own, so one bad record doesn't stop the others.</li>
                                <li>One record: HTTP 200, 404 or 422. A batch: always HTTP 200, check <code>summary</code>
                                    and each result.</li>
                                <li>Every change is written to the applicant's audit history.</li>
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0">Example: one applicant</h6>
                                <button type="button" class="btn btn-sm btn-link copy-btn" data-copy="#curlUpdateOne">Copy</button>
                            </div>
<pre class="code-view mb-3" id="curlUpdateOne">curl -X POST "{{ url('/api/applicants/update') }}" \
  -H "Authorization: Bearer &lt;api_key_ai_automation&gt;" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"applicant_id": 12345, "phone": "07700900123"}'</pre>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0">Example: several applicants</h6>
                                <button type="button" class="btn btn-sm btn-link copy-btn" data-copy="#curlUpdateMany">Copy</button>
                            </div>
<pre class="code-view mb-3" id="curlUpdateMany">curl -X POST "{{ url('/api/applicants/update') }}" \
  -H "Authorization: Bearer &lt;api_key_ai_automation&gt;" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"applicants": [
        {"applicant_id": 12345, "email": "new.address@example.com"},
        {"applicant_id": 67890, "applicant_name": "Jane Smith", "phone": "07700900456"}
      ]}'</pre>
                            <h6 class="mb-1">Example response</h6>
<pre class="code-view">{
  "success": false,
  "summary": {"received": 2, "updated": 1, "unchanged": 0, "not_found": 0, "invalid": 1},
  "results": [
    {"index": 0, "applicant_id": 12345, "status": "updated", "changed": ["email"]},
    {"index": 1, "applicant_id": 67890, "status": "invalid",
     "errors": {"phone": ["This phone number is already used by another applicant."]}}
  ]
}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">API 3 · Call outcome <span class="badge bg-warning text-dark align-middle">POST</span></h5>
                    <p class="text-muted mb-0 small">The AI agent reports every call. Each call is stored in <code>ai_agent_calls</code>
                        and its status shows on the Applicants list, Category Wise Resources and the sale's applicants page.</p>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <p class="mb-2"><span class="badge bg-warning text-dark">POST</span>
                                <code>{{ url('/api/applicants/call-outcome') }}</code></p>
                            <p class="small mb-2">Same key as APIs 1 and 2 (<code>api_key_ai_automation</code>). Send one call,
                                or up to {{ \App\Services\AiCallOutcomeService::MAX_RECORDS }} in a <code>calls</code> array.
                                Limited to 300 requests per minute.</p>
                            <table class="table table-sm small">
                                <thead class="table-light">
                                    <tr><th>Field</th><th>Required</th><th>Description</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>call_id</code></td><td>Yes</td><td>The agent's own id for the call. Sending the same id again updates that call (for example to add the transcript later), so retries never create duplicates.</td></tr>
                                    <tr><td><code>applicant_id</code></td><td>Yes</td><td>From API 1.</td></tr>
                                    <tr><td><code>job_id</code></td><td>Yes</td><td>From API 1 (the sale id).</td></tr>
                                    <tr><td><code>call_status</code></td><td>Yes</td><td><code>{{ implode('</code>, <code>', \App\Support\AiCallStatus::CALL_STATUSES) }}</code></td></tr>
                                    <tr><td><code>outcome</code></td><td>If answered</td><td><code>{{ implode('</code>, <code>', \App\Support\AiCallStatus::OUTCOMES) }}</code>. Only when <code>call_status</code> is <code>answered</code>.</td></tr>
                                    <tr><td><code>callback_at</code></td><td>If callback</td><td>When to call back, e.g. <code>2026-11-21T14:00:00Z</code>.</td></tr>
                                    <tr><td><code>called_at</code></td><td>No</td><td>When the call happened. Default: when it's received.</td></tr>
                                    <tr><td><code>duration_seconds</code></td><td>No</td><td>Call length.</td></tr>
                                    <tr><td><code>summary</code></td><td>No</td><td>Short summary for the recruiter.</td></tr>
                                    <tr><td><code>transcript</code></td><td>No</td><td>Full transcript.</td></tr>
                                    <tr><td><code>recording_url</code></td><td>No</td><td>Link to the recording (http/https).</td></tr>
                                </tbody>
                            </table>
                            <p class="small text-muted mb-0">Any other fields are kept too: the whole request is saved with the call.</p>
                        </div>
                        <div class="col-lg-6">
                            <h6>Status shown in the CRM</h6>
                            <p class="small mb-2">The outcome when the call was answered, otherwise the call status. Click a badge
                                to see every call, with summary, transcript and recording.</p>
                            <div class="mb-3">
                                @foreach (\App\Support\AiCallStatus::STATUSES as [$label, $classes])
                                    <span class="badge {{ $classes }} me-1 mb-1">{{ $label }}</span>
                                @endforeach
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0">Example</h6>
                                <button type="button" class="btn btn-sm btn-link copy-btn" data-copy="#curlCallOutcome">Copy</button>
                            </div>
<pre class="code-view mb-3" id="curlCallOutcome">curl -X POST "{{ url('/api/applicants/call-outcome') }}" \
  -H "Authorization: Bearer &lt;api_key_ai_automation&gt;" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"call_id": "call-8f2a", "applicant_id": 64528, "job_id": 17034,
       "call_status": "answered", "outcome": "callback",
       "callback_at": "2026-11-21T14:00:00Z", "duration_seconds": 184,
       "summary": "Interested, wants a call tomorrow after 2pm"}'</pre>
                            <h6 class="mb-1">Example response</h6>
<pre class="code-view">{
  "success": true,
  "summary": {"received": 1, "created": 1, "updated": 0, "invalid": 0},
  "results": [{"index": 0, "call_id": "call-8f2a", "status": "created", "id": 1, "ai_status": "callback"}]
}</pre>
                            <p class="small text-muted mb-0">One call: HTTP 201 when stored, 200 when updated, 422 when invalid.
                                A batch: always HTTP 200; check <code>summary</code> and each result.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
@endsection
@section('script')
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/jquery.dataTables.min.css') }}">
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}">
    <script src="{{ asset('js/toastr.min.js') }}"></script>
    <script>
        $(function() {
            const MAX_SALES = {{ $maxSales }};
            const FIELDS = @json($fields);
            const LONG_FIELDS = ['benefits', 'qualifications', 'experience', 'working_hours', 'job_description'];
            const PREVIEW_URL = @json(route('administrator.applicantMatchApi.preview'));
            const API_URL = @json(url('/api/applicants/job-matches'));
            let lastResponse = null;

            // ── Sales picker ─────────────────────────────────────
            function selectedSales() {
                return $('.sale-check:checked').map(function() {
                    return {
                        id: parseInt(this.value, 10),
                        label: $(this).data('label')
                    };
                }).get();
            }

            function refreshSelection() {
                const sales = selectedSales();
                $('#selectedCount').text(sales.length).toggleClass('bg-danger', sales.length > MAX_SALES);
                $('#selectedChips').html(sales.map(s =>
                    `<span class="badge bg-light text-dark border sale-chip">${escapeHtml(s.label)}
                        <i class="ri-close-line remove-chip" data-id="${s.id}"></i></span>`
                ).join(''));
                refreshExamples();
            }

            $('#saleList').on('change', '.sale-check', function() {
                if (this.checked && selectedSales().length > MAX_SALES) {
                    this.checked = false;
                    toastr.warning(`You can select at most ${MAX_SALES} sales.`);
                }
                refreshSelection();
            });

            $('#selectedChips').on('click', '.remove-chip', function() {
                $(`.sale-check[value="${$(this).data('id')}"]`).prop('checked', false);
                refreshSelection();
            });

            $('#saleSearch').on('input', function() {
                const terms = this.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
                let visible = 0;
                $('#saleList label').each(function() {
                    const text = $(this).data('search') + '';
                    const show = terms.every(t => text.includes(t));
                    $(this).toggleClass('d-none', !show);
                    if (show) visible++;
                });
                $('#saleListEmpty').toggleClass('d-none', visible > 0);
            });

            $('#selectVisible').on('click', function() {
                let room = MAX_SALES - selectedSales().length;
                $('#saleList label:not(.d-none) .sale-check:not(:checked)').each(function() {
                    if (room <= 0) return false;
                    this.checked = true;
                    room--;
                });
                if (room <= 0) toastr.info(`Selection is capped at ${MAX_SALES} sales.`);
                refreshSelection();
            });

            $('#clearSales').on('click', function() {
                $('.sale-check').prop('checked', false);
                refreshSelection();
            });

            // ── Request ──────────────────────────────────────────
            function buildParams() {
                return {
                    sale_ids: selectedSales().map(s => s.id),
                    radius: parseFloat($('#radiusInput').val()),
                    unit: $('#unitSelect').val(),
                    match_category: $('#matchCategory').is(':checked'),
                    format: $('#formatSelect').val(),
                    limit: parseInt($('#limitInput').val(), 10)
                };
            }

            function checkParams(params) {
                if (!params.sale_ids.length) return 'Select at least one sale.';
                if (!(params.radius > 0)) return 'Enter a radius greater than 0.';
                if (!(params.limit >= 1 && params.limit <= MAX_LIMIT)) {
                    return `Enter an API send limit between 1 and ${MAX_LIMIT.toLocaleString()}.`;
                }
                return null;
            }

            function firstError(xhr, fallback) {
                const res = xhr.responseJSON || {};
                return (res.errors ? Object.values(res.errors)[0][0] : null) || res.message || fallback;
            }

            // ── Preview table (server-side DataTable) ────────────
            // The table's own page length only changes what is shown here;
            // the API send limit above caps what API 1 hands out in total.
            const MAX_LIMIT = {{ $maxLimit }};
            let previewParams = null;
            let resultsTable = null;
            $.fn.dataTable.ext.errMode = 'none';

            function setFetching(on) {
                $('#fetchBtn').prop('disabled', on).html(on ?
                    '<span class="spinner-border spinner-border-sm me-1"></span> Fetching…' :
                    '<i class="ri-search-line me-1"></i> Fetch matching applicants');
            }

            function renderCell(value, type, field) {
                if (type !== 'display') return value;
                const val = value === null || value === undefined ? '' : String(value);
                if (LONG_FIELDS.includes(field)) {
                    const plain = escapeHtml(stripHtml(val));
                    return `<div class="clamp" title="${plain}">${plain}</div>`;
                }
                return escapeHtml(val);
            }

            function initResultsTable() {
                resultsTable = $('#resultsTable').DataTable({
                    serverSide: true,
                    processing: true,
                    searching: false,
                    ordering: false,
                    pageLength: 25,
                    lengthMenu: [10, 25, 50, 100],
                    dom: '<"d-flex justify-content-between align-items-center mb-2"li>rt<"d-flex justify-content-between align-items-center mt-2"ip>',
                    ajax: {
                        url: PREVIEW_URL,
                        type: 'POST',
                        contentType: 'application/json',
                        headers: csrfHeaders(),
                        data: d => JSON.stringify(Object.assign({}, previewParams, {
                            draw: d.draw,
                            start: d.start,
                            length: d.length
                        })),
                        dataSrc: function(json) {
                            lastResponse = json;
                            renderMeta(json);
                            setFetching(false);
                            return json.data;
                        },
                        error: function(xhr) {
                            setFetching(false);
                            $('#resultsTable_processing').hide();
                            toastr.error(firstError(xhr, 'Request failed.'));
                        }
                    },
                    columns: FIELDS.map(f => ({
                        data: f,
                        className: LONG_FIELDS.includes(f) ? 'long-text' : 'text-nowrap',
                        render: (value, type) => renderCell(value, type, f)
                    })),
                    language: {
                        emptyTable: 'No applicants match these sales and radius.',
                        info: 'Showing _START_ to _END_ of _TOTAL_ records the API sends',
                        infoEmpty: 'No records'
                    }
                });
            }

            $('#fetchBtn').on('click', function() {
                const params = buildParams();
                const problem = checkParams(params);
                if (problem) return toastr.error(problem);

                previewParams = params;
                setFetching(true);
                if (resultsTable) {
                    resultsTable.ajax.reload(); // back to page 1 with the new selection
                } else {
                    initResultsTable();
                }
            });

            // ── Publish: what API 1 serves ──────────────────────
            const PUBLISH_URL = @json(route('administrator.applicantMatchApi.publish'));
            const UNPUBLISH_URL = @json(route('administrator.applicantMatchApi.unpublish'));
            let published = @json($published);

            function renderPublished() {
                const $card = $('#publishedCard').removeClass('border-success border-secondary');
                if (!published) {
                    $card.addClass('border-secondary');
                    $('#publishedInfo').html(
                        `<div class="fw-semibold"><i class="ri-checkbox-blank-circle-line text-secondary me-1"></i> Nothing published</div>
                         <div class="small text-muted">API 1 returns no data. Choose sales and a radius below, then press Publish to API.</div>`
                    );
                    $('#unpublishBtn').addClass('d-none');
                    return;
                }

                const labels = published.sale_ids.map(id => {
                    const label = $(`.sale-check[value="${id}"]`).data('label');
                    return label ?
                        `<span class="badge bg-light text-dark border me-1 mb-1">${escapeHtml(label)}</span>` :
                        `<span class="badge bg-warning-subtle text-warning me-1 mb-1" title="No longer active: skipped by the API">#${id} (inactive)</span>`;
                }).join('');
                const when = published.published_at ? new Date(published.published_at).toLocaleString() : '';

                $card.addClass('border-success');
                $('#publishedInfo').html(
                    `<div class="fw-semibold"><i class="ri-checkbox-blank-circle-fill text-success me-1"></i> Live in API 1:
                        ${published.sale_ids.length} sale(s) within ${published.radius} ${escapeHtml(published.unit)}
                        · ${published.match_category ? 'same category' : 'any category'}
                        · ${published.format === 'html' ? 'HTML' : 'plain'} text
                        · max ${(published.limit || {{ $defaultLimit }}).toLocaleString()} records</div>
                     <div class="small text-muted mb-1">Published ${escapeHtml(when)}${published.published_by ? ' by ' + escapeHtml(published.published_by) : ''}</div>
                     <div>${labels}</div>`
                );
                $('#unpublishBtn').removeClass('d-none');
            }

            function applySelectionToForm(sel) {
                $('.sale-check').prop('checked', false);
                sel.sale_ids.forEach(id => $(`.sale-check[value="${id}"]`).prop('checked', true));
                $('#radiusInput').val(sel.radius);
                $('#unitSelect').val(sel.unit || 'km');
                $('#matchCategory').prop('checked', sel.match_category !== false);
                $('#formatSelect').val(sel.format || 'text');
                $('#limitInput').val(sel.limit || {{ $defaultLimit }});
                refreshSelection();
            }

            function csrfHeaders() {
                return {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                };
            }

            $('#publishBtn').on('click', function() {
                const params = buildParams();
                const problem = checkParams(params);
                if (problem) return toastr.error(problem);
                if (!confirm(`Publish ${params.sale_ids.length} sale(s) within ${params.radius} ${params.unit}, sending at most ${params.limit.toLocaleString()} records?\nAPI 1 will start sending these applicants straight away.`)) return;

                const $btn = $(this).prop('disabled', true);
                $.ajax({
                    url: PUBLISH_URL,
                    method: 'POST',
                    contentType: 'application/json',
                    dataType: 'json',
                    headers: csrfHeaders(),
                    data: JSON.stringify(params)
                }).done(function(res) {
                    published = res.selection;
                    renderPublished();
                    toastr.success(res.message);
                }).fail(function(xhr) {
                    toastr.error(firstError(xhr, 'Could not publish.'));
                }).always(() => $btn.prop('disabled', false));
            });

            $('#unpublishBtn').on('click', function() {
                if (!confirm('Stop publishing? API 1 will return no data until you publish again.')) return;
                const $btn = $(this).prop('disabled', true);
                $.ajax({
                    url: UNPUBLISH_URL,
                    method: 'DELETE',
                    dataType: 'json',
                    headers: csrfHeaders()
                }).done(function(res) {
                    published = null;
                    renderPublished();
                    toastr.success(res.message);
                }).fail(() => toastr.error('Could not stop publishing.'))
                    .always(() => $btn.prop('disabled', false));
            });

            // ── Summary above the table ─────────────────────────
            function renderMeta(res) {
                const m = res.meta;
                const capped = m.total_matches > m.total;
                $('#resultSummary').html(
                    `${m.total_matches.toLocaleString()} match${m.total_matches === 1 ? '' : 'es'} within ${m.radius} ${escapeHtml(m.unit)}` +
                    ` · <strong>API sends ${m.total.toLocaleString()}</strong>` +
                    (capped ? ` (limit ${m.limit.toLocaleString()}, nearest first across all sales)` : '')
                );

                const perSale = m.applicants_per_sale || {};
                let chips = m.sale_ids.map(id =>
                    `<span class="badge bg-primary-subtle text-primary me-1 mb-1">#${id}: ${(perSale[id] || 0).toLocaleString()}</span>`
                ).join('');
                if (m.ignored_sale_ids.length) {
                    chips += `<span class="badge bg-warning-subtle text-warning me-1 mb-1">Skipped (not active): ${m.ignored_sale_ids.map(id => '#' + id).join(', ')}</span>`;
                }
                $('#perSaleSummary').html(chips);

                // JSON tab: the response as API 1 would page it (no DataTables keys).
                const apiView = Object.assign({}, res);
                delete apiView.draw;
                delete apiView.recordsTotal;
                delete apiView.recordsFiltered;
                $('#jsonView').text(JSON.stringify(apiView, null, 2));
                $('#downloadJson').prop('disabled', false);
            }

            $('#downloadJson').on('click', function() {
                if (!lastResponse) return;
                const blob = new Blob([JSON.stringify(lastResponse, null, 2)], {
                    type: 'application/json'
                });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = `applicant-job-matches-page-${lastResponse.meta.page}.json`;
                a.click();
                URL.revokeObjectURL(a.href);
            });

            // ── API examples (token is never printed) ───────────
            function refreshExamples() {
                $('#curlGet').text(
                    `curl "${API_URL}?page=1&per_page=100" \\\n  -H "Authorization: Bearer <api_key_ai_automation>" \\\n  -H "Accept: application/json"`
                );
            }

            $('.copy-btn').on('click', function() {
                const text = $($(this).data('copy')).text();
                navigator.clipboard.writeText(text).then(
                    () => toastr.success('Copied.'),
                    () => toastr.error('Could not copy.')
                );
            });

            // ── Helpers ──────────────────────────────────────────
            function escapeHtml(str) {
                return String(str).replace(/[&<>"']/g, c => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [c]));
            }

            function stripHtml(str) {
                // DOMParser documents are inert: no scripts or onerror handlers run.
                const doc = new DOMParser().parseFromString(str, 'text/html');
                return (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
            }

            // Start from what is live, so editing + republishing is one step.
            if (published) {
                applySelectionToForm(published);
            } else {
                refreshSelection();
            }
            renderPublished();
        });
    </script>
@endsection

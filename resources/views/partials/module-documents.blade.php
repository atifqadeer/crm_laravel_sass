{{--
    Attachments field for the head office / unit create and edit forms.

    @param \Illuminate\Support\Collection|null $documents   Current (not deleted) documents, edit forms only.
    @param string|null                         $deleteRoute Route name taking {id}, e.g. 'head-offices.documents.delete'.
--}}
@php
    $documents = $documents ?? collect();
    $deleteRoute = $deleteRoute ?? null;
    $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png'];
@endphp

<div class="col-lg-12">
    <div class="mb-3 module-documents"
        data-delete-url="{{ $deleteRoute ? route($deleteRoute, ['id' => '__ID__']) : '' }}"
        data-allowed="{{ implode(',', $allowedExtensions) }}" data-max-files="10" data-max-mb="10">
        <label for="attachments" class="form-label">Attachments</label>
        <input type="file" class="form-control" id="attachments" name="attachments[]" multiple
            accept="{{ '.' . implode(',.', $allowedExtensions) }}">
        <div class="form-text">PDF, Word, Excel, CSV, TXT, JPG or PNG. Up to 10 files, 10 MB each.</div>
        <div class="invalid-feedback" data-attachments-error></div>

        @if ($deleteRoute)
            <div class="mt-3">
                <label class="form-label mb-2">Attached documents</label>
                <ul class="list-group" data-documents-list>
                    @forelse ($documents as $document)
                        <li class="list-group-item d-flex align-items-center gap-3" data-document-row>
                            <iconify-icon icon="solar:file-text-bold" class="fs-24 text-primary flex-shrink-0"></iconify-icon>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <a href="{{ $document->url }}" target="_blank" rel="noopener"
                                    class="fw-medium text-break">{{ $document->document_name }}</a>
                                <div class="small text-muted">
                                    {{ strtoupper($document->document_extension ?? '') }}
                                    · {{ $document->formatted_size }}
                                    · Uploaded {{ $document->created_at?->format('d M Y, h:i A') }}
                                    @if ($document->user)
                                        by {{ $document->user->name }}
                                    @endif
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0"
                                data-delete-document="{{ $document->id }}"
                                data-document-name="{{ $document->document_name }}">
                                <iconify-icon icon="solar:trash-bin-trash-bold" class="align-middle"></iconify-icon>
                                Delete
                            </button>
                        </li>
                    @empty
                        <li class="list-group-item text-muted" data-documents-empty>No documents attached.</li>
                    @endforelse
                </ul>
            </div>
        @endif
    </div>
</div>

@if ($deleteRoute)
    <div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-labelledby="deleteDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteDocumentModalLabel">Delete document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        Delete <strong data-delete-document-name></strong>?
                        It is kept for 3 days, then removed permanently along with the file.
                    </p>
                    <label for="deleteDocumentReason" class="form-label">Reason</label>
                    {{-- No name attribute: this must not be posted with the surrounding form. --}}
                    <textarea class="form-control" id="deleteDocumentReason" rows="3" maxlength="1000"
                        placeholder="Why is this document being deleted?"></textarea>
                    <div class="invalid-feedback">Please enter a reason.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="deleteDocumentConfirm">Delete</button>
                </div>
            </div>
        </div>
    </div>
@endif

<script>
    (function() {
        const wrapper = document.currentScript.closest('form')?.querySelector('.module-documents')
            || document.querySelector('.module-documents');
        if (!wrapper || wrapper.dataset.ready) {
            return;
        }
        wrapper.dataset.ready = '1';

        const notify = (type, message) => window.toastr ? window.toastr[type](message) : alert(message);

        // Check type, size and count before upload so a bad file never reaches the server.
        const input = wrapper.querySelector('input[type="file"]');
        const errorBox = wrapper.querySelector('[data-attachments-error]');
        const allowed = wrapper.dataset.allowed.split(',');
        const maxFiles = parseInt(wrapper.dataset.maxFiles, 10);
        const maxBytes = parseInt(wrapper.dataset.maxMb, 10) * 1024 * 1024;

        input.addEventListener('change', function() {
            const problems = [];
            const files = Array.from(input.files);

            if (files.length > maxFiles) {
                problems.push(`Choose up to ${maxFiles} files at a time.`);
            }
            files.forEach(file => {
                const ext = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';
                if (!allowed.includes(ext)) {
                    problems.push(`${file.name}: this file type isn't allowed.`);
                } else if (file.size > maxBytes) {
                    problems.push(`${file.name}: larger than ${wrapper.dataset.maxMb} MB.`);
                }
            });

            errorBox.textContent = problems.join(' ');
            input.classList.toggle('is-invalid', problems.length > 0);
            if (problems.length) {
                input.value = '';
            }
        });

        // Delete with a reason (soft delete; purged after 3 days).
        const deleteUrl = wrapper.dataset.deleteUrl;
        const modalEl = document.getElementById('deleteDocumentModal');
        if (!deleteUrl || !modalEl) {
            return;
        }
        // The partial sits inside the form/card; Bootstrap modals belong directly under <body>.
        document.body.appendChild(modalEl);

        const reasonEl = document.getElementById('deleteDocumentReason');
        const confirmBtn = document.getElementById('deleteDocumentConfirm');
        const nameEl = modalEl.querySelector('[data-delete-document-name]');
        let pending = null;

        wrapper.addEventListener('click', function(event) {
            const button = event.target.closest('[data-delete-document]');
            if (!button) {
                return;
            }
            pending = {
                id: button.dataset.deleteDocument,
                row: button.closest('[data-document-row]')
            };
            nameEl.textContent = button.dataset.documentName;
            reasonEl.value = '';
            reasonEl.classList.remove('is-invalid');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        confirmBtn.addEventListener('click', function() {
            const reason = reasonEl.value.trim();
            if (!pending) {
                return;
            }
            if (!reason) {
                reasonEl.classList.add('is-invalid');
                reasonEl.focus();
                return;
            }

            confirmBtn.disabled = true;

            fetch(deleteUrl.replace('__ID__', encodeURIComponent(pending.id)), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ delete_reason: reason })
                })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || !data.success) {
                        const message = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                        notify('error', message || 'Could not delete the document.');
                        return;
                    }

                    const list = pending.row.parentElement;
                    pending.row.remove();
                    if (!list.querySelector('[data-document-row]')) {
                        list.innerHTML = '<li class="list-group-item text-muted" data-documents-empty>No documents attached.</li>';
                    }

                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    notify('success', data.message);
                    pending = null;
                })
                .catch(() => notify('error', 'An unexpected error occurred. Please try again.'))
                .finally(() => {
                    confirmBtn.disabled = false;
                });
        });
    })();
</script>

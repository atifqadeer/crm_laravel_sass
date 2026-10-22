{{--
    "Show Documents" modal for the head office / unit list pages.
    Any element with data-documents-url (and optional data-documents-title) opens it;
    the URL returns { success, documents: [{ name, url, extension, size, uploaded_at, uploaded_by }] }.
--}}
<div class="modal fade" id="documentsViewerModal" tabindex="-1" aria-labelledby="documentsViewerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentsViewerModalLabel">Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" data-documents-body></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const modalEl = document.getElementById('documentsViewerModal');
        if (!modalEl || modalEl.dataset.ready) {
            return;
        }
        modalEl.dataset.ready = '1';

        const titleEl = modalEl.querySelector('.modal-title');
        const bodyEl = modalEl.querySelector('[data-documents-body]');
        let requestId = 0;

        const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, ch => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[ch]));

        const message = (text, cls = 'text-muted') =>
            `<p class="${cls} text-center my-4 mb-0">${escapeHtml(text)}</p>`;

        function render(documents) {
            if (!documents.length) {
                bodyEl.innerHTML = message('No documents attached.');
                return;
            }

            bodyEl.innerHTML = '<div class="list-group">' + documents.map(doc => {
                const meta = [doc.extension, doc.size, doc.uploaded_at ? 'Uploaded ' + doc.uploaded_at : '',
                    doc.uploaded_by ? 'by ' + doc.uploaded_by : ''].filter(Boolean).join(' · ');

                return `<a href="${escapeHtml(doc.url)}" target="_blank" rel="noopener"
                            class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                            <iconify-icon icon="solar:file-text-bold" class="fs-24 text-primary flex-shrink-0"></iconify-icon>
                            <span class="flex-grow-1" style="min-width: 0;">
                                <span class="d-block fw-medium text-break">${escapeHtml(doc.name)}</span>
                                <span class="d-block small text-muted">${escapeHtml(meta)}</span>
                            </span>
                            <iconify-icon icon="solar:square-arrow-right-up-linear" class="fs-20 text-muted flex-shrink-0"
                                title="Open in a new tab"></iconify-icon>
                        </a>`;
            }).join('') + '</div>';
        }

        document.addEventListener('click', function(event) {
            const trigger = event.target.closest('[data-documents-url]');
            if (!trigger) {
                return;
            }
            event.preventDefault();

            const thisRequest = ++requestId;
            const title = trigger.dataset.documentsTitle;
            titleEl.textContent = title ? 'Documents: ' + title : 'Documents';
            bodyEl.innerHTML = '<div class="text-center my-4"><div class="spinner-border text-primary" role="status">' +
                '<span class="visually-hidden">Loading...</span></div></div>';
            bootstrap.Modal.getOrCreateInstance(modalEl).show();

            fetch(trigger.dataset.documentsUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (thisRequest !== requestId) {
                        return; // a newer click replaced this one
                    }
                    if (!ok || !data.success) {
                        bodyEl.innerHTML = message(data.message || 'Could not load the documents.', 'text-danger');
                        return;
                    }
                    render(data.documents || []);
                })
                .catch(() => {
                    if (thisRequest === requestId) {
                        bodyEl.innerHTML = message('Could not load the documents. Please try again.', 'text-danger');
                    }
                });
        });
    })();
</script>

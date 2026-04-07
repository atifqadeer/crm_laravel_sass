@props([
    'html'        => '',      // raw Summernote HTML
    'id'          => '',      // unique id for the modal, e.g. "qualification-42"
    'label'       => 'View', // modal title
    'limit'       => 300,    // preview character limit; 0 = modal-only (no inline link)
])

@php
    use Illuminate\Support\Str;

    $fullHtml = $html ?? '';

    // 1. Strip inline styles from every tag & remove bare <span> wrappers
    $cleaned = preg_replace('/ style="[^"]*"/i', '', $fullHtml);
    $cleaned = preg_replace('/<\/?span[^>]*>/i', '', $cleaned);

    // 2. Replace block / line-break tags with a newline
    $cleaned = preg_replace('/<\/?(p|div|li|br|ul|ol|tr|td|table|h[1-6])[^>]*>/i', "\n", $cleaned);

    // 3. Strip all remaining tags except basic inline formatting
    $cleaned = strip_tags($cleaned, '<b><strong><i><em><u>');

    // 4. Decode HTML entities (&nbsp; → space, etc.)
    $cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // 5. Collapse excess blank lines / spaces
    $cleaned = preg_replace("/[ \t]+/", ' ', $cleaned);
    $cleaned = preg_replace("/[\r\n]{3,}/", "\n\n", $cleaned);
    $cleaned = trim($cleaned);

    // 6. Build the short preview (only used when limit > 0)
    $preview   = (int)$limit > 0 ? Str::limit($cleaned, (int)$limit, '…') : '';
    $shortHtml = nl2br(e($preview));

    // 7. If there's no real content, fall back to a dash (only for inline mode)
    $isEmpty   = ($cleaned === '' || $cleaned === null);
    $modalOnly = ((int)$limit === 0);  // external trigger handles the button
@endphp

@if($isEmpty && !$modalOnly)
    <span class="text-muted">—</span>
@elseif(!$isEmpty)
    @if(!$modalOnly)
        {{-- Inline preview link (click opens modal) --}}
        <a href="#" data-bs-toggle="modal" data-bs-target="#{{ $id }}"
           class="summernote-preview-link text-body-secondary"
           style="text-decoration:none; cursor:pointer;">
            {!! $shortHtml !!}
        </a>
    @endif

    {{-- Full-content modal --}}
    <div class="modal fade" id="{{ $id }}" tabindex="-1"
         aria-labelledby="{{ $id }}-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $id }}-label">{{ $label }}</h5>
                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body summernote-modal-body">
                    {!! $fullHtml !!}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark"
                            data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endif


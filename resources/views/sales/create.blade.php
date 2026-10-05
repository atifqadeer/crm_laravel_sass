@extends('layouts.vertical', ['title' => 'Create Sale', 'subTitle' => 'Sales'])

@section('css')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @include('sales.partials.sale-form-assets')
@endsection
@section('content')
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <form id="createSaleForm" action="{{ route('sales.store') }}" method="POST" class="needs-validation" novalidate
                enctype="multipart/form-data">
                @csrf

                {{-- Job details --}}
                <div class="card sale-section">
                    <div class="card-header">
                        <span class="sale-section-icon"><iconify-icon icon="solar:case-round-bold-duotone"></iconify-icon></span>
                        <div>
                            <h4 class="card-title mb-0">Job Details</h4>
                            <p class="text-muted fs-13 mb-0">What role is being recruited for and where it came from.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="job_category" class="form-label">Job Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="job_category" name="job_category_id" required>
                                        <option value="">Choose a Job Category</option>
                                        @foreach ($jobCategories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ old('job_category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a job category</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="job_type" class="form-label">Job Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="job_type" name="job_type" required>
                                        <option value="">Choose a Job Type</option>
                                        <option value="specialist" {{ old('job_type') == 'specialist' ? 'selected' : '' }}>
                                            Specialist</option>
                                        <option value="regular" {{ old('job_type') == 'regular' ? 'selected' : '' }}>Regular
                                        </option>
                                    </select>
                                    <div class="invalid-feedback">Please select a job type</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="job_title" class="form-label">Job Title <span class="text-danger">*</span></label>
                                    <select id="job_title" name="job_title_id" class="form-select">
                                        <option value="">Choose a Job Title</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a job title</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="job_source_id" class="form-label">Job Source <span class="text-danger">*</span></label>
                                    <select class="form-select" id="job_source_id" name="job_source_id" required>
                                        <option value="">Choose a Job Source</option>
                                        @forelse($jobSources as $source)
                                            <option value="{{ $source->id }}"
                                                {{ old('job_source_id') == $source->id ? 'selected' : '' }}>
                                                {{ $source->name }}
                                            </option>
                                        @empty
                                            <option value="">No data found</option>
                                        @endforelse
                                    </select>
                                    <div class="invalid-feedback">Please select a job source</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Location & position --}}
                <div class="card sale-section">
                    <div class="card-header">
                        <span class="sale-section-icon"><iconify-icon icon="solar:map-point-bold-duotone"></iconify-icon></span>
                        <div>
                            <h4 class="card-title mb-0">Location & Position</h4>
                            <p class="text-muted fs-13 mb-0">Where the role is based, the contract type and pay.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4 col-md-4 col-sm-12">
                                <div class="mb-3">
                                    <label for="office_id" class="form-label">Head Office <span class="text-danger">*</span></label>
                                    <select class="form-select" id="office_id" name="office_id" required>
                                        <option value="">Choose a Head Office</option>
                                        @foreach ($offices as $office)
                                            <option value="{{ $office->id }}"
                                                {{ old('office_id') == $office->id ? 'selected' : '' }}>
                                                {{ $office->office_name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a head office</div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-12">
                                <div class="mb-3">
                                    <label for="unit_id" class="form-label">Unit <span class="text-danger">*</span></label>
                                    <select class="form-select" id="unit_id" name="unit_id" required>
                                        <option value="">Choose a Unit</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a unit</div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-12">
                                <div class="mb-3">
                                    <label for="sale_postcode" class="form-label">Postcode <span class="text-danger">*</span></label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text"><iconify-icon icon="solar:map-point-linear"></iconify-icon></span>
                                        <input type="text" id="sale_postcode" class="form-control"
                                            value="{{ old('sale_postcode') }}" name="sale_postcode"
                                            placeholder="e.g. SW1A 1AA" required minlength="2" maxlength="8">
                                        <div class="invalid-feedback">Please provide a postcode</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="position_type" class="form-label">Position Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="position_type" name="position_type" required>
                                        <option value="">Choose a Type</option>
                                        <option value="full time"
                                            {{ old('position_type') == 'full time' ? 'selected' : '' }}>Full Time</option>
                                        <option value="part time"
                                            {{ old('position_type') == 'part time' ? 'selected' : '' }}>Part Time</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a position type</div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12 col-sm-12">
                                @include('sales.partials.salary-field')
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="cv_limit" class="form-label">CV Limit <span class="text-danger">*</span></label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text"><iconify-icon icon="solar:document-text-linear"></iconify-icon></span>
                                        <input type="number" id="cv_limit" class="form-control" name="cv_limit" min="1"
                                            value="{{ old('cv_limit') }}" placeholder="e.g. 5" required>
                                        <div class="invalid-feedback">Please provide cv limit</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Requirements: timing, experience, benefits, qualification --}}
                <div class="card sale-section">
                    <div class="card-header">
                        <span class="sale-section-icon"><iconify-icon icon="solar:checklist-minimalistic-bold-duotone"></iconify-icon></span>
                        <div>
                            <h4 class="card-title mb-0">Requirements & Benefits</h4>
                            <p class="text-muted fs-13 mb-0">Tap to select. Each section is saved as one clean sentence
                                that starts with its prefix, ready for the job portal.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($requirementFields as $key => $field)
                                <div class="col-xl-6">
                                    @include('sales.partials.requirement-picker', ['key' => $key, 'field' => $field])
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Description & notes --}}
                <div class="card sale-section">
                    <div class="card-header">
                        <span class="sale-section-icon"><iconify-icon icon="solar:notes-bold-duotone"></iconify-icon></span>
                        <div>
                            <h4 class="card-title mb-0">Description & Notes</h4>
                            <p class="text-muted fs-13 mb-0">Full job description for candidates and internal notes.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label for="job_description" class="form-label">Job Description</label>
                                    <textarea id="job_description" name="job_description" class="form-control summernote">{{ old('job_description') }}</textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-0">
                                    <label for="sale_notes" class="form-label">Notes <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="sale_notes" name="sale_notes" rows="3" placeholder="Internal notes about this sale" required>{{ old('sale_notes') }}</textarea>
                                    <div class="invalid-feedback">Please provide notes</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Documents --}}
                <div class="card sale-section">
                    <div class="card-header">
                        <span class="sale-section-icon"><iconify-icon icon="solar:upload-square-bold-duotone"></iconify-icon></span>
                        <div>
                            <h4 class="card-title mb-0">Upload Documents</h4>
                            <p class="text-muted fs-13 mb-0">Optional supporting files for this sale.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Dropzone -->
                        <div id="salesAttachmentDropzone" class="dropzone dz-clickable">
                            <div class="dz-message needsclick">
                                <i class="h1 ri-upload-cloud-2-line"></i>
                                <h3>Drop files here or click to upload.</h3>
                                <span class="text-muted fs-13">
                                    Allowed file types: docx, doc, pdf, txt (Max 10MB)
                                </span>
                            </div>
                        </div>

                        <!-- Hidden preview template -->
                        <div id="dz-preview-template" style="display:none;">
                            <li class="mt-2 dz-preview dz-file-preview">
                                <div class="border rounded">
                                    <div class="d-flex p-2">
                                        <div class="flex-shrink-0 me-3">
                                            <div
                                                class="avatar-sm bg-light rounded d-flex align-items-center justify-content-center">
                                                <div data-dz-thumbnail class="dz-iconify">
                                                    <iconify-icon icon="solar:file-bold"
                                                        class="fs-32 text-secondary"></iconify-icon>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="pt-1">
                                                <h5 class="fs-14 mb-1" data-dz-name></h5>
                                                <p class="fs-13 text-muted mb-0" data-dz-size></p>
                                                <strong class="error text-danger" data-dz-errormessage></strong>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 ms-3">
                                            <button data-dz-remove class="btn btn-sm btn-transparent text-danger"
                                                title="Remove file">
                                                <iconify-icon icon="solar:trash-bin-minimalistic-bold"
                                                    class="text-danger fs-24"></iconify-icon>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </div>

                        <ul class="list-unstyled mb-0" id="dropzone-preview"></ul>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card sale-form-actions">
                    <div class="card-body py-2">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <span class="text-muted fs-13"><span class="text-danger">*</span> Required fields</span>
                            <div class="d-flex gap-2">
                                <a href="{{ route('sales.list') }}" class="btn btn-light px-4">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <iconify-icon icon="solar:diskette-linear" class="align-middle me-1"></iconify-icon>Save Sale</button>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
@endsection
@section('script')
    <!-- jQuery CDN -->
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="{{ asset('css/jquery.dataTables.min.css') }}">

    <!-- DataTables JS -->
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}">

    <!-- SweetAlert2 CDN -->
    <script src="{{ asset('js/sweetalert2@11.js') }}"></script>

    <!-- Toastr JS -->
    <script src="{{ asset('js/toastr.min.js') }}"></script>

    <!-- Moment JS -->
    <script src="{{ asset('js/moment.min.js') }}"></script>

    <!-- Daterangepicker CSS/JS -->
    <link rel="stylesheet" href="{{ asset('css/daterangepicker.css') }}" />
    <script src="{{ asset('js/daterangepicker.min.js') }}"></script>

    <!-- Summernote CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">

    <!-- Summernote JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 on all select elements
            $('select.form-select').not('.salary-period').select2({
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true,
                width: '100%'
            });

            $('.summernote').summernote({
                height: 200,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture']],
                    ['view', []]
                ]
            });
        });

        // Form validation
        (function() {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('createSaleForm');
            if (!form) return;

            SaleRequirements.init();
            SalaryField.init();

            const submitLabel = form.querySelector('button[type="submit"]').innerHTML;

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!SalaryField.validate()) {
                    document.getElementById('salaryPicker').scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    toastr.error('Please provide the salary amount.');
                    return;
                }

                if (!SaleRequirements.validate()) {
                    toastr.error('Please complete the requirements & benefits section.');
                    return;
                }

                // Submit button loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

                // Gather form data
                const formData = new FormData(form);

                // Add Dropzone file data to FormData safely
                const dropzoneEl = document.querySelector("#salesAttachmentDropzone");
                if (dropzoneEl && dropzoneEl.dropzone) {
                    const dropzoneFiles = dropzoneEl.dropzone.getAcceptedFiles();
                    dropzoneFiles.forEach(function(file) {
                        formData.append('attachments[]', file);
                    });
                }

                fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            toastr.success(data.message);
                            form.reset();
                            form.classList.remove('was-validated');
                            window.location.reload();
                        } else {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = submitLabel;

                            // Handle validation errors
                            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove(
                                'is-invalid'));
                            form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent =
                                '');

                            if (data.errors) {
                                Object.entries(data.errors).forEach(([field, messages]) => {
                                    const input = form.querySelector(`[name="${field}"]`);
                                    const feedback = input?.closest('.mb-3')?.querySelector(
                                        '.invalid-feedback');
                                    if (input && feedback) {
                                        input.classList.add('is-invalid');
                                        feedback.textContent = messages.join(' ');
                                    }
                                });
                            } else {
                                toastr.error(data.message || 'Submission failed.');
                            }
                        }
                    })
                    .catch(error => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = submitLabel;
                        toastr.error('An unexpected error occurred. Please try again.');
                        console.error('Error:', error);
                    });
            });

            // Postcode formatting
            const postcodeInput = document.getElementById('sale_postcode');
            if (postcodeInput) {
                postcodeInput.addEventListener('input', function(e) {
                    const cursorPos = this.selectionStart;
                    let rawValue = this.value.replace(/[^a-z0-9\s]/gi, '');
                    let formattedValue = rawValue.length > 8 ? rawValue.substring(0, 8) : rawValue;
                    this.value = formattedValue.toUpperCase();
                    const newCursorPos = Math.min(cursorPos, this.value.length);
                    this.setSelectionRange(newCursorPos, newCursorPos);
                });
            }
        });

        $(document).ready(function() {
            const jobTitle = $('#job_title');
            const jobCategory = $('#job_category');
            const jobType = $('#job_type');

            function fetchJobTitles() {
                const categoryId = jobCategory.val();
                const type = jobType.val();

                if (categoryId && type) {
                    fetch(`/getJobTitlesByCategory?job_category_id=${categoryId}&job_type=${type}`)
                        .then(response => response.json())
                        .then(data => {
                            jobTitle.empty().append('<option value="">Choose a Job Title</option>');
                            data.forEach(title => {
                                const option = new Option(title.name.toUpperCase(), title.id, false,
                                    false);
                                jobTitle.append(option);
                            });
                            // Trigger select2 update
                            jobTitle.trigger('change.select2');
                        })
                        .catch(error => {
                            console.error('Error fetching job titles:', error);
                        });
                }
            }

            jobCategory.on('change', fetchJobTitles);
            jobType.on('change', fetchJobTitles);
        });

        $(document).ready(function() {
            const office_id = $('#office_id');
            const unit_id = $('#unit_id');

            function fetchOfficeUnits() {
                const OfficeId = office_id.val();

                if (OfficeId) {
                    fetch(`/getOfficeUnits?office_id=${OfficeId}`)
                        .then(response => response.json())
                        .then(data => {
                            unit_id.empty().append('<option value="">Choose a Unit</option>');
                            data.forEach(unit => {
                                const option = new Option(unit.unit_name, unit.id, false, false);
                                unit_id.append(option);
                            });
                            // Trigger select2 update
                            unit_id.trigger('change.select2');
                        })
                        .catch(error => {
                            console.error('Error fetching units:', error);
                        });
                }
            }

            office_id.on('change', fetchOfficeUnits);
        });
    </script>
@endsection
@section('script-bottom')
    @vite(['resources/js/components/form-fileupload.js'])
@endsection

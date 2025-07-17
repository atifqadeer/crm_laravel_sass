@extends('layouts.vertical', ['title' => 'Edit Sale', 'subTitle' => 'Sales'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('content')
@php
$offices = \Horsefly\Office::where('status', 1)->select('id','office_name')->get();
$jobCategories = \Horsefly\JobCategory::where('is_active', 1)->get();
$jobTitles = \Horsefly\JobTitle::where('is_active', 1)->get();

$sale_id = request()->query('id');
$sale = \Horsefly\Sale::with('documents')->find($sale_id);

@endphp

<div class="row">
    <div class="col-xl-12 col-lg-12">
        <form id="editSaleForm" action="{{ route('sales.update') }}" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="sale_id" value="{{ $sale_id }}">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Sale Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="job_category" class="form-label">Job Category</label>
                                <select class="form-select" id="job_category" name="job_category_id" required>
                                    <option value="">Choose a Job Category</option>
                                    @foreach($jobCategories as $category)
                                        <option value="{{ $category->id }}" {{ old('job_category_id', $sale->job_category_id == $category->id ? 'selected':'') }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Please select a job category</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="job_type" class="form-label">Job Type</label>
                                <select class="form-select" id="job_type" name="job_type" required>
                                    <option value="">Choose a Job Type</option>
                                    <option value="specialist" {{ old('job_type', $sale->job_type == "specialist" ? 'selected':'') }}>Specialist</option>
                                    <option value="regular" {{ old('job_type', $sale->job_type == "regular" ? 'selected':'') }}>Regular</option>
                                </select>
                                <div class="invalid-feedback">Please select a job type</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="job_title" class="form-label">Job Title</label>
                                <select id="job_title" name="job_title_id" class="form-select">
                                    <option value="">Choose a Job Title</option>
                                </select>
                                
                                <div class="invalid-feedback">Please select a job title</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="mb-3">
                                <label for="office_id" class="form-label">Head Office</label>
                                <select class="form-select" id="office_id" name="office_id" required>
                                    <option value="">Choose a Head Office</option>
                                    @foreach($offices as $office)
                                        <option value="{{ $office->id }}" {{ old('office_id', $sale->office_id  == $office->id ? 'selected':'') }}>{{ $office->office_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Please select a head office</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="mb-3">
                                <label for="unit_id" class="form-label">Units</label>
                                <select class="form-select" id="unit_id" name="unit_id" required>
                                    <option value="">Choose a Unit</option>
                                </select>
                                <div class="invalid-feedback">Please select a unit</div>
                            </div>
                        </div>
                       <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="mb-3">
                                <label for="sale_postcode" class="form-label">PostCode</label>
                                <input type="text" id="sale_postcode" class="form-control" value="{{ old('sale_postcode', $sale->sale_postcode) }}" 
                                name="sale_postcode" placeholder="Enter PostCode" required minlength="2" maxlength="8">
                                <div class="invalid-feedback">Please provide a postcode</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="mb-3">
                                <label for="cv_limit" class="form-label">CV Limit</label>
                                <input type="number" id="cv_limit" class="form-control" name="cv_limit" 
                                value="{{ old('cv_limit', $sale->cv_limit) }}" placeholder="Enter Limit">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="mb-3">
                                <label for="position_type" class="form-label">Position Type</label>
                                <select class="form-select" id="position_type" name="position_type" required>
                                    <option value="">Choose a Type</option>
                                    <option value="full time" {{ old('position_type', $sale->position_type == 'full time' ? 'selected' : '') }}>Full Time</option>
                                    <option value="part time" {{ old('position_type', $sale->position_type == 'part time' ? 'selected' : '') }}>Part Time</option>
                                </select>
                                <div class="invalid-feedback">Please select a position type</div>
                            </div>
                        </div>
                         <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="mb-3">
                                <label for="salary" class="form-label">Salary</label>
                                <input type="text" id="salary" class="form-control" name="salary" 
                                value="{{ old('salary', $sale->salary) }}" placeholder="Enter Salary" required>
                                 <div class="invalid-feedback">Please enter salary</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="timing" class="form-label">Timing</label>
                                    <textarea class="form-control" id="timing" name="timing" rows="3" placeholder="Enter Timing" required>{{ old('timing', $sale->timing) }}</textarea>
                                    <div class="invalid-feedback">Please provide timing</div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="experience" class="form-label">Experience</label>
                                    <textarea class="form-control" id="experience" name="experience" rows="3" placeholder="Enter Experience">{{ old('experience', $sale->experience) }}</textarea>
                                    <div class="invalid-feedback">Please provide experience</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="benefits" class="form-label">Benefits</label>
                                    <textarea class="form-control" id="benefits" name="benefits" rows="3" placeholder="Enter Benefits" required>{{ old('benefits', $sale->benefits) }}</textarea>
                                    <div class="invalid-feedback">Please provide benefits</div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="qualification" class="form-label">Qualification</label>
                                    <textarea class="form-control" id="qualification" name="qualification" rows="3" placeholder="Enter Qualification" required>{{ old('qualification', $sale->qualification) }}</textarea>
                                    <div class="invalid-feedback">Please provide qualification</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label for="sale_notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="sale_notes" name="sale_notes" rows="3" placeholder="Enter Notes" required>{{ old('sale_notes') }}</textarea>
                                <div class="invalid-feedback">Please provide notes</div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                           <div class="mb-3">
                                <label for="job_description" class="form-label">Job Description</label>
                                <textarea id="job_description" name="job_description" class="form-control summernote">{{ old('job_description', $sale->job_description) }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <!--   <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Upload Documents</h4>
                            </div>
                            
                            <div id="applicantCvDropzone" class="dropzone">
                                <div class="dz-message needsclick">
                                    <i class="h1 ri-upload-cloud-2-line"></i>
                                    <h3>Drop files here or click to upload.</h3>
                                    <span class="text-muted fs-13">
                                        Allowed file types: docx, doc, csv, pdf (Max 5MB)
                                    </span>
                                </div>
                            </div>
                        
                            <div class="p-3" id="regularFileInput" style="display: none;">
                                <label class="form-label">Or select file manually:</label>
                                {{-- <input type="file" class="form-control" name="applicant_cv" id="applicant_cv"> --}}
                            </div>
                        
                            <div class="text-center p-2">
                                <button type="button" class="btn btn-sm btn-link" id="toggleUploadMethod">
                                    Switch to manual file selection
                                </button>
                            </div>
                        
                            <ul class="list-unstyled mb-0" id="dropzone-preview">
                                <li class="mt-2" id="dropzone-preview-list">
                                    <div class="border rounded">
                                        <div class="d-flex p-2">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-sm bg-light rounded">
                                                    <img data-dz-thumbnail class="img-fluid rounded d-block" src="#" alt="Dropzone-Image" />
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="pt-1">
                                                    <h5 class="fs-14 mb-1" data-dz-name>&nbsp;</h5>
                                                    <p class="fs-13 text-muted mb-0" data-dz-size></p>
                                                    <strong class="error text-danger" data-dz-errormessage></strong>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0 ms-3">
                                                <button data-dz-remove class="btn btn-sm btn-transparent text-danger">
                                                    <iconify-icon icon="solar:trash-bin-trash-bold" class="align-middle fs-24"></iconify-icon>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div> -->
                        <div class="form-group">
                            <label for="attachment">Attachment</label>
                            <input type="file" class="form-control" name="attachments[]" id="attachment" multiple>
                            <small class="text-muted">Allowed file types: docx, doc, csv, pdf (Max 5MB)</small>
                        </div>
                        @if($sale->documents->isNotEmpty())
                            <div class="col-lg-12">
                                <div class="mt-3">
                                    <label for="sale_documents" class="form-label">Already Attached Files</label>
                                    <ul class="list-group">
                                        @foreach($sale->documents as $document)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <a href="{{ asset('storage/' . $document->document_path) }}" target="_blank">{{ $document->document_name }}</a>
                                                <div>
                                                    <span class="badge bg-info rounded-pill">{{ $document->created_at->format('d M Y') }}</span>
                                                    <button type="button" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Delete File" class="btn bg-transparent btn-sm ms-2 remove-document-btn" data-document-id="{{ $document->id }}">
                                                        <iconify-icon icon="solar:trash-bin-trash-bold" class="align-middle fs-24 text-danger"></iconify-icon>
                                                    </button>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="mb-3 rounded">
                <div class="row justify-content-end g-2">
                    
                    <div class="col-lg-2">
                        <a href="{{ route('units.list') }}" class="btn btn-dark w-100">Cancel</a>
                    </div>
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">
                            Update</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
@section('script')
    <!-- jQuery CDN (make sure this is loaded before DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables CSS (for styling the table) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

    <!-- DataTables JS (for the table functionality) -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Summernote JS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script>
    $(document).ready(function () {
        $('.summernote').summernote({
            height: 300,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough', 'superscript', 'subscript']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen']]
            ]
        });
    });
</script>
<script>
    // Form validation
    (function () {
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
        // Handle form submission
        const form = document.getElementById('editSaleForm');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

            // Collect form data
            const formData = new FormData(form);
          
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
                    window.location.href = data.redirect;
                } else {
                    // Handle validation errors
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Save';
                    
                    if (data.errors) {
                        // Clear previous errors
                        form.querySelectorAll('.is-invalid').forEach(el => {
                            el.classList.remove('is-invalid');
                        });
                        form.querySelectorAll('.invalid-feedback').forEach(el => {
                            el.textContent = '';
                        });

                        // Display new errors
                        Object.entries(data.errors).forEach(([field, messages]) => {
                            const input = form.querySelector(`[name="${field}"]`);
                            const feedback = input?.closest('.mb-3')?.querySelector('.invalid-feedback');
                            
                            if (input && feedback) {
                                input.classList.add('is-invalid');
                                feedback.textContent = messages.join(' ');
                            }
                        });
                    } else {
                        if (data.errors) {
                            let errorMessages = Object.values(data.errors).flat().join('\n');
                            toastr.error('Validation Errors:\n' + errorMessages);
                        } else {
                            toastr.error(data.message);
                        }
                    }
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save';
                toastr.error('An unexpected error occurred. Please try again.');
                console.error('Error:', error);
            });
        });

        // Postcode formatting
        document.getElementById('sale_postcode').addEventListener('input', function(e) {
            const cursorPos = this.selectionStart;
            let rawValue = this.value.replace(/[^a-z0-9\s]/gi, '');
            
            let formattedValue = rawValue.length > 8 
                ? rawValue.substring(0, 8) 
                : rawValue;
            
            this.value = formattedValue.toUpperCase();
            
            const newCursorPos = Math.min(cursorPos, this.value.length);
            this.setSelectionRange(newCursorPos, newCursorPos);
        });
    });

    // to fetch units by head office id
    document.addEventListener('DOMContentLoaded', function() {
        const office_id = document.getElementById('office_id');
        const unit_id = document.getElementById('unit_id');

        if (!office_id || !unit_id) {
            console.warn("One or more elements are missing: office_id, unit_id.");
            return;
        }

        function fetchOfficeUnits() {
            const officeId = office_id.value;

            if (officeId) {
                fetch(`/getOfficeUnits?office_id=${officeId}`)
                    .then(response => response.json())
                    .then(data => {
                        unit_id.innerHTML = '<option value="">Choose a Unit</option>'; // Reset the options

                        // Populate the unit dropdown
                        data.forEach(unit => {
                            const option = document.createElement('option');
                            option.value = unit.id;
                            option.textContent = unit.unit_name;
                            unit_id.appendChild(option);
                        });

                        // Pre-select the unit if one is already selected
                        const selectedUnitId = unit_id.getAttribute('data-selected-unit-id');
                        if (selectedUnitId) {
                            const selectedOption = unit_id.querySelector(`option[value="${selectedUnitId}"]`);
                            if (selectedOption) {
                                selectedOption.selected = true;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching units:', error);
                    });
            }
        }

        // Listen for changes to the office ID to fetch units
        office_id.addEventListener('change', fetchOfficeUnits);

        // Set the selected unit ID from the backend (if any)
        const selectedUnitId = '{{ old("unit_id", $sale->unit_id) }}';  // Make sure this value is correctly passed from the backend
        unit_id.setAttribute('data-selected-unit-id', selectedUnitId);

        // Optionally, call fetchOfficeUnits on page load if an office is already selected
        if (office_id.value) {
            fetchOfficeUnits();
        }
    });

    // to fetch job titles by job category and type
    document.addEventListener('DOMContentLoaded', function() {
        const jobTitle = document.getElementById('job_title');
        const jobCategory = document.getElementById('job_category');
        const jobType = document.getElementById('job_type');

        if (!jobTitle || !jobCategory || !jobType) {
            console.warn("One or more elements are missing: job_title, job_category, or job_type.");
            return;
        }

        function fetchJobTitles() {
            const categoryId = jobCategory.value;
            const type = jobType.value;

            if (categoryId && type) {
                fetch(`/getJobTitlesByCategory?job_category_id=${categoryId}&job_type=${type}`)
                    .then(response => response.json())
                    .then(data => {
                        jobTitle.innerHTML = '<option value="">Choose a Job Title</option>';
                        data.forEach(title => {
                            const option = document.createElement('option');
                            option.value = title.id;
                            option.textContent = title.name.toUpperCase();
                            jobTitle.appendChild(option);
                        });

                        // Pre-select the job title if one is already selected
                        const selectedJobTitleId = jobTitle.getAttribute('data-selected-job-title-id');
                        if (selectedJobTitleId) {
                            const selectedOption = jobTitle.querySelector(`option[value="${selectedJobTitleId}"]`);
                            if (selectedOption) {
                                selectedOption.selected = true;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching job titles:', error);
                    });
            }
        }

        // Add event listeners to dynamically load job titles when category/type change
        jobCategory.addEventListener('change', fetchJobTitles);
        jobType.addEventListener('change', fetchJobTitles);

        // Pre-select the job title on page load
        fetchJobTitles();

        // Set the selected job title ID for use after fetching job titles
        const selectedJobTitleId = '{{ old("job_title_id", $sale->job_title_id) }}';
        jobTitle.setAttribute('data-selected-job-title-id', selectedJobTitleId);
    });

    // delete file
    document.addEventListener('DOMContentLoaded', function () {
        const removeButtons = document.querySelectorAll('.remove-document-btn');

        removeButtons.forEach(button => {
            button.addEventListener('click', function () {
                const documentId = this.getAttribute('data-document-id');
                const listItem = this.closest('li');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This file will be permanently deleted. Are you sure you want to continue?',
                    icon: 'warning',
                    showCancelButton: true,
                     customClass: {
                        confirmButton: 'btn bg-danger text-white me-2 mt-2',
                        cancelButton: 'btn btn-secondary mt-2'
                    },
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`{{ route('sales.remove_document', ['id' => '__DOCUMENT_ID__']) }}`.replace('__DOCUMENT_ID__', documentId), {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                toastr.success(data.message);
                                listItem.remove();
                            } else {
                                toastr.error(data.message || 'Failed to remove the document.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            toastr.error('An unexpected error occurred.');
                        });
                    }
                });
            });
        });
    });

</script>
@endsection
@section('script-bottom')
    @vite(['resources/js/components/form-fileupload.js'])
    @vite(['resources/js/components/extended-sweetalert.js'])
@endsection
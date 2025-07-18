@extends('layouts.vertical', ['title' => 'Create Sale', 'subTitle' => 'Sales'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('content')
@php
$offices = \Horsefly\Office::where('status', 1)->select('id','office_name')->get();
$units = \Horsefly\Unit::where('status', 1)->select('id','unit_name')->get();

$jobCategories = \Horsefly\JobCategory::where('is_active', 1)->get();
$jobTitles = \Horsefly\JobTitle::where('is_active', 1)->get();

@endphp
<div class="row">
    <div class="col-xl-12 col-lg-12">
        <form id="createSaleForm" action="{{ route('sales.store') }}" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Unit Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="job_category" class="form-label">Job Category</label>
                                <select class="form-select" id="job_category" name="job_category_id" required>
                                    <option value="">Choose a Job Category</option>
                                    @foreach($jobCategories as $category)
                                        <option value="{{ $category->id }}" {{ old('job_category_id') == $category->id ? 'selected':'' }}>{{ $category->name }}</option>
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
                                    <option value="specialist" {{ old('job_type' == "specialist" ? 'selected':'') }}>Specialist</option>
                                    <option value="regular" {{ old('job_type') == "regular" ? 'selected':'' }}>Regular</option>
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
                                        <option value="{{ $office->id }}" {{ old('office_id') == $office->id ? 'selected':'' }}>{{ $office->office_name }}</option>
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
                                <input type="text" id="sale_postcode" class="form-control" value="{{ old('sale_postcode') }}" 
                                name="sale_postcode" placeholder="Enter PostCode" required minlength="2" maxlength="8">
                                <div class="invalid-feedback">Please provide a postcode</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="mb-3">
                                <label for="cv_limit" class="form-label">CV Limit</label>
                                <input type="number" id="cv_limit" class="form-control" name="cv_limit" 
                                value="{{ old('cv_limit') }}" placeholder="Enter Limit">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="mb-3">
                                <label for="position_type" class="form-label">Position Type</label>
                                <select class="form-select" id="position_type" name="position_type" required>
                                    <option value="">Choose a Type</option>
                                    <option value="full time" {{ old('position_type') == 'full time' ? 'selected' : '' }}>Full Time</option>
                                    <option value="part time" {{ old('position_type') == 'part time' ? 'selected' : '' }}>Part Time</option>
                                </select>
                                <div class="invalid-feedback">Please select a position type</div>
                            </div>
                        </div>
                         <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="mb-3">
                                <label for="salary" class="form-label">Salary</label>
                                <input type="text" id="salary" class="form-control" name="salary" 
                                value="{{ old('salary') }}" placeholder="Enter Salary" required>
                                 <div class="invalid-feedback">Please enter salary</div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="timing" class="form-label">Timing</label>
                                <textarea class="form-control summernotee" id="timing" name="timing" rows="3" placeholder="Enter Timing" required>{{ old('timing') }}</textarea>
                                <div class="invalid-feedback">Please provide timing</div>
                            </div>
                        </div>
                            <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="experience" class="form-label">Experience</label>
                                <textarea class="form-control summernotee" id="experience" name="experience" rows="3" placeholder="Enter Experience">{{ old('experience') }}</textarea>
                                <div class="invalid-feedback">Please provide experience</div>
                            </div>
                        </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="benefits" class="form-label">Benefits</label>
                                    <textarea class="form-control summernotee" id="benefits" name="benefits" rows="3" placeholder="Enter Benefits" required>{{ old('benefits') }}</textarea>
                                    <div class="invalid-feedback">Please provide benefits</div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="qualification" class="form-label">Qualification</label>
                                    <textarea class="form-control summernotee" id="qualification" name="qualification" rows="3" placeholder="Enter Qualification" required>{{ old('qualification') }}</textarea>
                                    <div class="invalid-feedback">Please provide qualification</div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                            <div class="mb-3">
                                    <label for="job_description" class="form-label">Job Description</label>
                                    <textarea id="job_description" name="job_description" class="form-control summernote">{{ old('job_description') }}</textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <div class="form-group">
                                        <label for="attachment">Attachment</label>
                                        <input type="file" class="form-control" name="attachments[]" id="attachment" multiple>
                                        <small class="text-muted">Allowed file types: docx, doc, csv, pdf (Max 5MB)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="sale_notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="sale_notes" name="sale_notes" rows="3" placeholder="Enter Notes" required>{{ old('sale_notes') }}</textarea>
                                    <div class="invalid-feedback">Please provide notes</div>
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
                       
                    </div>
                </div>
            </div>
            <div class="mb-3 rounded">
                <div class="row justify-content-end g-2">
                   
                    <div class="col-lg-2">
                        <a href="{{ route('sales.list') }}" class="btn btn-dark w-100">Cancel</a>
                    </div>
                     <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">
                            Save</button>
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

    <!-- Summernote CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">

    <!-- Summernote JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script>
    $(document).ready(function () {
        $('.summernotee').summernote({
            height: 100,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough', 'superscript', 'subscript']],
                ['fontsize', ['fontsize']],
                ['color', []],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', []],
                ['view', []]
            ]
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
        const form = document.getElementById('createSaleForm');
        if (!form) return;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Submit button loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

            // Gather form data
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
                    form.reset();
                    form.classList.remove('was-validated');
                    window.location.reload();
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Save';

                    // Handle validation errors
                    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                    form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

                    if (data.errors) {
                        Object.entries(data.errors).forEach(([field, messages]) => {
                            const input = form.querySelector(`[name="${field}"]`);
                            const feedback = input?.closest('.mb-3')?.querySelector('.invalid-feedback');
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
                submitBtn.innerHTML = 'Save';
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
                    })
                    .catch(error => {
                        console.error('Error fetching job titles:', error);
                        // toastr.error('Failed to fetch job titles.');
                    });
            }
        }

        jobCategory.addEventListener('change', fetchJobTitles);
        jobType.addEventListener('change', fetchJobTitles);
    });
   
    document.addEventListener('DOMContentLoaded', function() {
        const office_id = document.getElementById('office_id');
        const unit_id = document.getElementById('unit_id');

        if (!office_id) {
            console.warn("The element is missing: Office ID.");
            return;
        }

        function fetchOfficeUnits() {
            const OfficeId = office_id.value;

            if (OfficeId) {
                fetch(`/getOfficeUnits?office_id=${OfficeId}`)
                    .then(response => response.json())
                    .then(data => {
                        unit_id.innerHTML = '<option value="">Choose a Unit</option>';
                        data.forEach(title => {
                            const option = document.createElement('option');
                            option.value = title.id;
                            option.textContent = title.unit_name;
                            unit_id.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching units:', error);
                    });
            }
        }

        office_id.addEventListener('change', fetchOfficeUnits);
    });

    // Fetch data and populate dropdown
    function fetchDataAndPopulateDropdown(url, dropdownId) {
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            const dropdown = document.getElementById(dropdownId);
            if (dropdown && data.success && Array.isArray(data.items)) {
                dropdown.innerHTML = '<option value="">Choose an option</option>';
                data.items.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    dropdown.appendChild(option);
                });
            } else {
                console.error('Invalid data format or dropdown not found');
            }
        })
        .catch(error => {
            console.error('Error fetching data:', error);
        });
    }

</script>
@endsection
@section('script-bottom')
    @vite(['resources/js/components/form-fileupload.js'])
@endsection
@extends('layouts.vertical', ['title' => 'Edit Applicant', 'subTitle' => 'Home'])

@section('css')
@vite(['node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('content')
@php
$jobCategories = \Horsefly\JobCategory::where('is_active', 1)->orderBy('name', 'asc')->get();
$jobSources = \Horsefly\JobSource::where('is_active', 1)->orderBy('name', 'asc')->get();

$applicant_id = request()->query('id');
$applicant = \Horsefly\Applicant::find($applicant_id);
@endphp

<div class="row">
    <div class="col-xl-12 col-lg-12">
        <form id="editApplicantForm" action="{{ route('applicants.update') }}" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="applicant_id" value="{{ $applicant_id }}">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Applicant Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="job_category" class="form-label">Job Category</label>
                                <select class="form-select" id="job_category" name="job_category_id" required>
                                    <option value="">Choose a Job Category</option>
                                    @foreach($jobCategories as $category)
                                        <option value="{{ $category->id }}" {{ old('job_category_id', $applicant->job_category_id == $category->id ? 'selected':'') }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Please select a job category</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="job_type" class="form-label">Job Type</label>
                                <select class="form-select" id="job_type" name="job_type" required>
                                    <option value="">Choose a Job Type</option>
                                    <option value="specialist" {{ old('job_type', $applicant->job_type == 'specialist' ? 'selected':'') }}>Specialist</option>
                                    <option value="regular" {{ old('job_type', $applicant->job_type == 'regular' ? 'selected':'') }}>Regular</option>
                                </select>
                                <div class="invalid-feedback">Please select a job type</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="job_title" class="form-label">Job Title</label>
                                <select class="form-select" id="job_title" name="job_title_id" required>
                                    <option value="">Choose a Job Title</option>
                                </select>
                                <div class="invalid-feedback">Please select a job title</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="job_source" class="form-label">Job Source</label>
                                <select class="form-select" id="job_source" name="job_source_id" required>
                                    <option value="">Choose a Job Source</option>
                                    @foreach($jobSources as $source)
                                        <option value="{{ $source->id }}" {{ old('job_source_id', $applicant->job_source_id == $source->id ? 'selected':'') }} >{{ $source->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Please select a job source</div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3">
                            <div class="mb-3">
                                <label for="applicant_name" class="form-label">Name</label>
                                <input type="text" id="applicant_name" class="form-control" name="applicant_name" 
                                value="{{ old('applicant_name', $applicant->applicant_name) }}" @cannot('applicant-edit-name') readonly @endcannot placeholder="Full Name" required>
                                <div class="invalid-feedback">Please provide a name</div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Choose Gender</option>
                                    <option value="m" {{ old('gender', $applicant->gender == 'm' ? 'selected':'') }}>Male</option>
                                    <option value="f" {{ old('gender', $applicant->gender == 'f' ? 'selected':'') }}>Female</option>
                                </select>
                                <div class="invalid-feedback">Please provide gender</div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="mb-3">
                                <label for="applicant_email_primary" class="form-label">Email <small class="text-info">(Primary)</small></label>
                                <input type="email" id="applicant_email_primary" class="form-control" name="applicant_email" 
                                value="{{ old('applicant_email', $applicant->applicant_email) }}" @cannot('applicant-edit-email') readonly @endcannot placeholder="Enter Email" required>
                                <div class="invalid-feedback">Please provide a valid email</div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="mb-3">
                                <label for="applicant_email_secondary" class="form-label">Email <small class="text-info">(Secondary)</small></label>
                                <input type="email" id="applicant_email_secondary" class="form-control" @cannot('applicant-edit-email') readonly @endcannot name="applicant_email_secondary" 
                                value="{{ old('applicant_email_secondary', $applicant->applicant_email_secondary) }}" placeholder="Enter Email">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label for="applicant_postcode" class="form-label">PostCode <small class="text-info">(If postcode is not available then use current or last workplace postcode)</small></label>
                                <input type="text" id="applicant_postcode" class="form-control" @cannot('applicant-edit-postcode') readonly @endcannot value="{{ old('applicant_postcode', $applicant->applicant_postcode) }}" 
                                name="applicant_postcode" placeholder="Enter PostCode" required>
                                <div class="invalid-feedback">Please provide a postcode</div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label for="applicant_phone" class="form-label">Phone</label>
                                <input type="tel" id="applicant_phone" class="form-control" name="applicant_phone" 
                                value="{{ old('applicant_phone', $applicant->applicant_phone) }}"  @cannot('applicant-edit-phone') readonly @endcannot placeholder="Enter Phone Number" required>
                                <div class="invalid-feedback">Please provide a phone number</div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label for="applicant_landline" class="form-label">Landline</label>
                                <input type="tel" id="applicant_landline" class="form-control" @cannot('applicant-edit-phone') readonly @endcannot value="{{ old('applicant_landline', $applicant->applicant_landline) }}" name="applicant_landline" placeholder="Enter Landline Number">
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="applicant_experience" class="form-label">Experience <small class="text-info">(Optional)</small></label>
                                <textarea class="form-control" id="applicant_experience" name="applicant_experience" rows="3" placeholder="Enter Experience">{{ old('applicant_experience', $applicant->applicant_experience) }}</textarea>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="applicant_notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="applicant_notes" name="applicant_notes" rows="3" placeholder="Enter Notes" required>{{ old('applicant_notes') }}</textarea>
                                <div class="invalid-feedback">Please provide notes</div>
                            </div>
                        </div>
                        <div class="col-lg-12" id="nurseToggleContainer" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label" for="nurse_option_yes">Have Nursing Home Experience?</label>
                                <p class="text-muted">Please indicate if the applicant has prior experience working in a nursing home.</p>
                                <small class="text-info">This information helps us better understand the applicant's background.</small>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="have_nursing_home_experience" id="nurse_option_yes" value="1" required
                                        {{ old('have_nursing_home_experience', $applicant->have_nursing_home_experience) == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="nurse_option_yes">Yes</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="have_nursing_home_experience" id="nurse_option_no" value="0" required
                                        {{ old('have_nursing_home_experience', $applicant->have_nursing_home_experience) == '0' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="nurse_option_no">No</label>
                                </div>

                                <div class="invalid-feedback">Please provide a nursing option</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!--- <div class="card">
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
            </div>-->
            <div class="form-group">
                <label for="applicant_cv">Upload CV</label>
                <input type="file" class="form-control" name="applicant_cv" id="applicant_cv" accept=".pdf,.doc,.docx">
                <small class="text-muted">Allowed file types: docx, doc, csv, pdf (Max 5MB)</small>
            </div> 
            
            <div class="mb-3 rounded">
                <div class="row justify-content-end g-2">
                    <div class="col-lg-2">
                        <a href="{{ route('applicants.list') }}" class="btn btn-dark w-100">Cancel</a>
                    </div>
                     <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">Update</button>
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
        // Initialize Dropzone
        Dropzone.autoDiscover = false;
        const myDropzone = new Dropzone("#applicantCvDropzone", {
            url: "/dummy-url",
            paramName: "applicant_cv",
            maxFiles: 1,
            maxFilesize: 5,
            acceptedFiles: '.docx,.doc,.csv,.pdf',
            addRemoveLinks: true,
            autoProcessQueue: false,
            previewsContainer: "#dropzone-preview",
            previewTemplate: document.querySelector('#dropzone-preview-list').innerHTML,
            init: function() {
                this.on("addedfile", function(file) {
                    // Sync with regular file input when file is added to Dropzone
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    document.getElementById('applicant_cv').files = dataTransfer.files;
                    
                    // Hide regular input when using Dropzone
                    document.getElementById('regularFileInput').style.display = 'none';
                });
                
                this.on("removedfile", function(file) {
                    // Clear regular input when file is removed from Dropzone
                    document.getElementById('applicant_cv').value = '';
                    document.getElementById('regularFileInput').style.display = 'block';
                });
            }
        });

        // Toggle between upload methods
        document.getElementById('toggleUploadMethod').addEventListener('click', function() {
            const dropzone = document.getElementById('applicantCvDropzone');
            const regularInput = document.getElementById('regularFileInput');
            
            if (dropzone.style.display === 'none') {
                // Switch to Dropzone
                dropzone.style.display = 'block';
                regularInput.style.display = 'none';
                this.textContent = 'Switch to manual file selection';
            } else {
                // Switch to regular input
                dropzone.style.display = 'none';
                regularInput.style.display = 'block';
                this.textContent = 'Switch to drag & drop';
                
                // Clear any Dropzone files
                myDropzone.removeAllFiles(true);
            }
        });

        // Handle regular file input changes
        document.getElementById('applicant_cv').addEventListener('change', function() {
            if (this.files.length > 0) {
                // Add file to Dropzone if using regular input
                myDropzone.removeAllFiles(true);
                myDropzone.addFile(this.files[0]);
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Handle form submission
        const form = document.getElementById('editApplicantForm');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

            // Collect form data
            const formData = new FormData(form);
            
            // Add any additional data
            formData.append('job_title_id', document.getElementById('job_title').value);
          
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
                            alert('Validation Errors:\n' + errorMessages);
                        } else {
                            alert(data.message);
                        }
                    }
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save';
                alert('An unexpected error occurred. Please try again.');
                console.error('Error:', error);
            });
        });

        // Postcode formatting
        document.getElementById('applicant_postcode').addEventListener('input', function(e) {
            const cursorPos = this.selectionStart;
            let rawValue = this.value.replace(/[^a-z0-9\s]/gi, '');
            
            let formattedValue = rawValue.length > 8 
                ? rawValue.substring(0, 8) 
                : rawValue;
            
            this.value = formattedValue.toUpperCase();
            
            const newCursorPos = Math.min(cursorPos, this.value.length);
            this.setSelectionRange(newCursorPos, newCursorPos);
        });

        // Phone number formatting
        ['applicant_phone', 'applicant_landline'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9+]/g, '');
                if (this.value.startsWith('+')) return;
                if (this.value.length > 5) {
                    this.value = this.value.replace(/(\d{5})(\d+)/, '$1 $2');
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const jobTitle = document.getElementById('job_title');
        const jobCategory = document.getElementById('job_category');
        const jobType = document.getElementById('job_type');

        if (!jobTitle || !jobCategory || !jobType) {
            console.warn("One or more elements are missing: job_title, job_category, or job_type.");
            return;
        }

        // Set the selected job title ID for use after fetching job titles
        const selectedJobTitleId = '{{ old("job_title_id", $applicant->job_title_id) }}';
        jobTitle.setAttribute('data-selected-job-title-id', selectedJobTitleId);

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
    });

    document.addEventListener('DOMContentLoaded', function () {
        const jobCategorySelect = document.getElementById('job_category');
        const nurseToggleContainer = document.getElementById('nurseToggleContainer');

        function toggleNurseContainer() {
            const selectedValue = parseInt(jobCategorySelect.value);

            if (selectedValue === 1) {
                nurseToggleContainer.style.display = 'block';
            } else {
                nurseToggleContainer.style.display = 'none';

                // Optionally clear radio buttons when hiding
                document.querySelectorAll('input[name="have_nursing_home_experience"]').forEach(input => {
                    input.checked = false;
                });
            }
        }

        jobCategorySelect.addEventListener('change', toggleNurseContainer);

        // Call on page load to apply initial visibility
        toggleNurseContainer();
    });
</script>
@endsection
@section('script-bottom')
@vite(['resources/js/components/form-fileupload.js'])
@endsection
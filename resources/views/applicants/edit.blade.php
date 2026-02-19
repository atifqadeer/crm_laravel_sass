@extends('layouts.vertical', ['title' => 'Edit Applicant', 'subTitle' => 'Home'])

@section('css')
@vite(['node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-lg-12">
        <form id="editApplicantForm" action="{{ route('applicants.update') }}" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="applicant_id" value="{{ $applicant->id }}">
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
                                    <option value="u" {{ old('gender', $applicant->gender == 'u' ? 'selected':'') }}>Unknown</option>
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
                                <div class="invalid-feedback">Please provide a valid email secondary</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <div class="mb-3">
                                <label for="applicant_postcode" class="form-label">PostCode <small class="text-info">(If unavailable, use the last workplace postcode.)</small></label>
                                <input type="text" id="applicant_postcode" class="form-control" @cannot('applicant-edit-postcode') readonly @endcannot value="{{ old('applicant_postcode', $applicant->applicant_postcode) }}" 
                                name="applicant_postcode" placeholder="Enter PostCode" required>
                                <div class="invalid-feedback">Please provide a postcode</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <div class="mb-3">
                                <label for="applicant_phone" class="form-label">Phone <small class="text-info">(Primary)</small></label>
                                <input type="tel" id="applicant_phone" class="form-control" name="applicant_phone" 
                                value="{{ old('applicant_phone', $applicant->applicant_phone) }}"  @cannot('applicant-edit-phone') readonly @endcannot placeholder="Enter Phone Number" required>
                                <div class="invalid-feedback">Please provide a phone number</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <div class="mb-3">
                                <label for="applicant_phone_secondary" class="form-label">Phone <small class="text-info">(Secondary)</small></label>
                                <input type="tel" id="applicant_phone_secondary" class="form-control" name="applicant_phone_secondary" 
                                value="{{ old('applicant_phone_secondary', $applicant->applicant_phone_secondary) }}"  @cannot('applicant-edit-phone') readonly @endcannot placeholder="Enter Phone Number" required>
                                <div class="invalid-feedback">Please provide a phone number Secondary</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <div class="mb-3">
                                <label for="applicant_landline" class="form-label">Landline</label>
                                <input type="tel" id="applicant_landline" class="form-control" @cannot('applicant-edit-landline') readonly @endcannot value="{{ old('applicant_landline', $applicant->applicant_landline) }}" name="applicant_landline" placeholder="Enter Landline Number">
                                <div class="invalid-feedback">Please provide a landline number</div>
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
                    
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Upload Documents</h4>
                        </div>
                    
                        <!-- Dropzone -->
                        <div id="applicantCvDropzone" class="dropzone dz-clickable">
                            <div class="dz-message needsclick">
                                <i class="h1 ri-upload-cloud-2-line"></i>
                                <h3>Drop files here or click to upload.</h3>
                                <span class="text-muted fs-13">
                                    Allowed file types: docx, doc, csv, pdf (Max 10MB)
                                </span>
                            </div>
                        </div>

                        <!-- Hidden preview template -->
                        <div id="dz-preview-template" style="display:none;">
                            <li class="mt-2 dz-preview dz-file-preview">
                                <div class="border rounded">
                                    <div class="d-flex p-2">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar-sm bg-light rounded d-flex align-items-center justify-content-center">
                                                <div data-dz-thumbnail class="dz-iconify">
                                                    <iconify-icon icon="solar:file-bold" class="fs-32 text-secondary"></iconify-icon>
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
                                            <button data-dz-remove class="btn btn-sm btn-transparent text-danger" title="Remove file">
                                                <iconify-icon icon="solar:trash-bin-minimalistic-bold" class="text-danger fs-24"></iconify-icon>
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </li>
                        </div>

                        <ul class="list-unstyled mb-0" id="dropzone-preview"></ul>

                    </div>
                    {{-- <div class="form-group">
                        <label for="applicant_cv">Upload CV</label>
                        <input type="file" class="form-control" name="applicant_cv" id="applicant_cv" accept=".pdf,.doc,.docx,.txt">
                        <small class="text-muted">Allowed file types: docx, doc, csv, pdf, txt (Max 5MB)</small>
                    </div>  --}}
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
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
@section('script')
    <!-- jQuery CDN (make sure this is loaded before DataTables) -->
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>

    <!-- DataTables CSS (for styling the table) -->
    <link rel="stylesheet" href="{{ asset('css/jquery.dataTables.min.css')}}">

    <!-- DataTables JS (for the table functionality) -->
    <script src="{{ asset('js/jquery.dataTables.min.js')}}"></script>
    
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}">

    <!-- SweetAlert2 CDN -->
    <script src="{{ asset('js/sweetalert2@11.js')}}"></script>

    <!-- Toastr JS -->
    <script src="{{ asset('js/toastr.min.js')}}"></script>

    <!-- Moment JS -->
    <script src="{{ asset('js/moment.min.js')}}"></script>

    <!-- Summernote CSS -->
    <link rel="stylesheet" href="{{ asset('css/summernote-lite.min.css')}}">

    <!-- Summernote JS -->
    <script src="{{ asset('js/summernote-lite.min.js')}}"></script>
    
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
        const form = document.getElementById('editApplicantForm');
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Remove all spaces from phone fields before submission
            ['applicant_phone', 'applicant_phone_secondary', 'applicant_landline'].forEach(id => {
                const input = form.querySelector(`[name="${id}"]`);
                if (input) {
                    input.value = input.value.replace(/\s+/g, '');
                }
            });
            
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

            // Collect form data
            const formData = new FormData(form);

            // Add Dropzone file data to FormData
            const dropzoneFiles = Dropzone.instances[0].getAcceptedFiles();  // Assuming Dropzone instance is properly initialized
            dropzoneFiles.forEach(function(file) {
                formData.append('applicant_cv', file);
            });
            
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
        ['applicant_phone', 'applicant_landline', 'applicant_phone_secondary'].forEach(id => {
            const input = document.getElementById(id);
            if (!input) return;

            input.addEventListener('input', function () {
                let value = this.value.trim();

                // 1️⃣ Allow only digits and '+'
                value = value.replace(/[^0-9+]/g, '');

                // 2️⃣ Convert +44 to 0
                if (value.startsWith('+44')) {
                    value = '0' + value.slice(3);
                }

                // 3️⃣ Remove remaining '+'
                value = value.replace(/\+/g, '');

                // 4️⃣ If empty OR only "0", keep it empty
                if (value === '' || value === '0') {
                    this.value = '';
                    return;
                }

                // 5️⃣ If digits exist and doesn't start with 0, add 0
                if (!value.startsWith('0')) {
                    value = '0' + value;
                }

                // 6️⃣ Limit to 11 digits
                if (value.length > 11) {
                    value = value.slice(0, 11);
                }

                this.value = value;
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
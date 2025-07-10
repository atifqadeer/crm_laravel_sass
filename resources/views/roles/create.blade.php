@extends('layouts.vertical', ['title' => 'Create Role', 'subTitle' => 'Home'])

@section('css')
@vite(['node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection
@section('content')
@php
    $permissions = \DB::table('permissions')->get();
    $groupedPermissions = collect($permissions)->groupBy(function ($permission) {
        return explode('-', $permission->name)[0]; // group by prefix before hyphen
    });

@endphp
<div class="row">
    <div class="col-xl-12 col-lg-12">
        <form id="createRoleForm" action="{{ route('roles.store') }}" method="POST" class="needs-validation" novalidate>
            @csrf
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"></h4>
                    <a href="{{ route('permissions.list') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Create Permission
                    </a>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="role_name" class="form-label">Role Name</label>
                        <input type="text" id="role_name" class="form-control" name="name" value="{{ old('name') }}" placeholder="Enter Role Name" required>
                        <div class="invalid-feedback">Please provide a role name</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Permissions</label>
                        <div class="border rounded py-3 px-2" style="max-height: 500px; overflow-y: auto;">
                            @forelse($groupedPermissions as $group => $groupPermissions)
                                <div class="mb-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input group-parent" type="checkbox" id="group_{{ $group }}">
                                        <label class="form-check-label fw-bold text-uppercase" for="group_{{ $group }}">
                                            {{ ucfirst($group) }}
                                        </label>
                                    </div>

                                    <div class="row ms-3 mt-2">
                                        @foreach($groupPermissions as $permission)
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input perm-checkbox group-{{ $group }}" type="checkbox"
                                                        name="permissions[]"
                                                        value="{{ $permission->name }}"
                                                        id="perm_{{ $permission->id }}"
                                                        {{ (is_array(old('permissions')) && in_array($permission->name, old('permissions'))) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                        {{ ucfirst(Str::after($permission->name, '-')) }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <hr>
                                </div>
                            @empty
                                <div class="text-muted">No permissions available.</div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
            <div class="mb-3 rounded">
                <div class="row justify-content-end g-2">
                    <div class="col-lg-2">
                        <a href="{{ route('roles.list') }}" class="btn btn-dark w-100">Cancel</a>
                    </div>
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">
                            Save
                        </button>
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
        const jobTitle = document.getElementById('job_title');
        const jobCategory = document.getElementById('job_category');
        const jobType = document.getElementById('job_type');

        if (!jobTitle || !jobCategory || !jobType) {
            console.warn("One or more elements are missing: job_title, job_category, or job_type.");
            return;
        }

        function fetchJobTitles() {
            // alert('Fetching job titles...');
            const categoryId = jobCategory.value;
            const type = jobType.value;

            if (categoryId && type) {
                fetch(`/getJobTitles?job_category_id=${categoryId}&job_type=${type}`)
                    .then(response => response.json())
                    .then(data => {
                        jobTitle.innerHTML = '<option value="">Choose a Job Title</option>';
                        data.forEach(title => {
                            const option = document.createElement('option');
                            option.value = title.id;
                            option.textContent = title.name;
                            jobTitle.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching job titles:', error);
                        // alert('Failed to fetch job titles.');
                    });
            }
        }

        jobCategory.addEventListener('change', fetchJobTitles);
        jobType.addEventListener('change', fetchJobTitles);
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Handle form submission
        const form = document.getElementById('createRoleForm');
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
                    window.location.reload();
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.group-parent').forEach(parentCheckbox => {
            parentCheckbox.addEventListener('change', function () {
                const group = this.id.replace('group_', '');
                document.querySelectorAll(`.group-${group}`).forEach(child => {
                    child.checked = this.checked;
                });
            });
        });
    });
</script>

@endsection
@section('script-bottom')
@vite(['resources/js/components/form-fileupload.js'])
@endsection
@extends('layouts.vertical', ['title' => 'Edit Head Office', 'subTitle' => 'Head Offices'])

@section('css')
@vite(['node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-lg-12">
        <form id="editHeadOfficeForm" action="{{ route('head-offices.update') }}" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="office_id" value="{{ $office->id }}">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Head Office Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="office_name" class="form-label">Name</label>
                                <input type="text" id="office_name" class="form-control" @cannot('office-edit-name') readonly @endcannot name="office_name" value="{{ old('office_name', $office->office_name ) }}" placeholder="Full Name" required>
                                <div class="invalid-feedback">Please provide a name</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="office_type" class="form-label">Type</label>
                                <select class="form-select" id="office_type" name="office_type" required>
                                    <option value="">Choose Type</option>
                                    <option value="head_office" {{ old('office_type', $office->office_type == "head_office" ? 'selected':'') }}>Head Office</option>
                                    <option value="individual" {{ old('office_type', $office->office_type == "individual" ? 'selected':'') }}>Individual</option>
                                    <option value="independent" {{ old('office_type', $office->office_type == "independent" ? 'selected':'') }}>Independent</option>
                                </select>
                                <div class="invalid-feedback">Please select type</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="office_postcode" class="form-label">PostCode</label>
                                <input type="text" id="office_postcode" @cannot('office-edit-postcode') readonly @endcannot class="form-control" value="{{ old('office_postcode', $office->office_postcode ) }}" 
                                name="office_postcode" placeholder="Enter PostCode" required maxlength="8">
                                <div class="invalid-feedback">Please provide a postcode</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="office_website" class="form-label">Website</label>
                                <input type="url" id="office_website" class="form-control" name="office_website" 
                                value="{{ old('office_website', $office->office_website ) }}" placeholder="Enter URL">
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-3 border px-3 py-5 rounded" style="background-color: #f9f9f9;">
                                <label class="form-label">Contact Persons</label>
                                <div id="contactPersonsContainer">
                                    @forelse($contacts as $row)
                                    <div class="contact-person-form row g-3 mb-3">
                                        <div class="col-lg-3">
                                            <input type="text" class="form-control" name="contact_name[]" placeholder="Contact Name" required value="{{ $row->contact_name }}">
                                            <div class="invalid-feedback">Please provide a contact name</div>
                                        </div>
                                        <div class="col-lg-3">
                                            <input type="email" class="form-control" name="contact_email[]" placeholder="Contact Email" required value="{{ $row->contact_email }}">
                                            <div class="invalid-feedback">Please provide a valid email</div>
                                        </div>
                                        <div class="col-lg-3">
                                            <input type="text" class="form-control" name="contact_phone[]" placeholder="Contact Phone" maxlength="20" value="{{ $row->contact_phone }}">
                                            <div class="invalid-feedback">Please provide a phone number</div>
                                        </div>
                                        <div class="col-lg-3">
                                            <input type="text" class="form-control" name="contact_landline[]" placeholder="Contact Landline" maxlength="20" value="{{ $row->contact_landline }}">
                                            <div class="invalid-feedback">Please provide a landline number</div>
                                        </div>
                                        <div class="col-lg-12">
                                            <textarea class="form-control" name="contact_note[]" placeholder="Enter Contact Note">{{ $row->contact_note }}</textarea>
                                            <div class="invalid-feedback">Please provide a contact note</div>
                                        </div>
                                    </div>
                                    @empty
                                        <div class="contact-person-form row g-3 mb-3">
                                            <div class="col-lg-3">
                                                <input type="text" class="form-control" name="contact_name[]" placeholder="Contact Name" required>
                                                <div class="invalid-feedback">Please provide a contact name</div>
                                            </div>
                                            <div class="col-lg-3">
                                                <input type="email" class="form-control" name="contact_email[]" placeholder="Contact Email" required>
                                                <div class="invalid-feedback">Please provide a valid email</div>
                                            </div>
                                            <div class="col-lg-3">
                                                <input type="text" class="form-control" name="contact_phone[]" placeholder="Contact Phone" maxlength="20">
                                                <div class="invalid-feedback">Please provide a phone number</div>
                                            </div>
                                            <div class="col-lg-3">
                                                <input type="text" class="form-control" name="contact_landline[]" placeholder="Contact Landline" maxlength="20">
                                                <div class="invalid-feedback">Please provide a landline number</div>
                                            </div>
                                            <div class="col-lg-12">
                                            <textarea class="form-control" name="contact_note[]" placeholder="Enter Contact Note"></textarea>
                                            <div class="invalid-feedback">Please provide a contact note</div>
                                        </div>
                                        </div>
                                    @endforelse
                                </div>
                                @canany('office-add-more-contact-btn')
                                <button type="button" class="btn btn-secondary float-end" id="addContactPersonButton">Add More</button>
                                @endcanany
                            </div>
                        </div>

                        <script>
                            document.getElementById('addContactPersonButton').addEventListener('click', function () {
                                const container = document.getElementById('contactPersonsContainer');
                                const newForm = document.createElement('div');
                                newForm.classList.add('contact-person-form', 'row', 'g-3', 'mb-3');
                                newForm.innerHTML = `
                                    <div class="col-lg-3">
                                        <input type="text" class="form-control" name="contact_name[]" placeholder="Contact Name" required>
                                        <div class="invalid-feedback">Please provide a contact name</div>
                                    </div>
                                    <div class="col-lg-3">
                                        <input type="email" class="form-control" name="contact_email[]" placeholder="Contact Email" required>
                                        <div class="invalid-feedback">Please provide a valid email</div>
                                    </div>
                                    <div class="col-lg-3">
                                        <input type="text" class="form-control" name="contact_phone[]" placeholder="Contact Phone" required>
                                        <div class="invalid-feedback">Please provide a phone number</div>
                                    </div>
                                    <div class="col-lg-3">
                                        <input type="text" class="form-control" name="contact_landline[]" placeholder="Contact Landline">
                                        <div class="invalid-feedback">Please provide a landline number</div>
                                    </div>
                                    <div class="col-lg-11">
                                        <textarea class="form-control" name="contact_note[]" placeholder="Enter Contact Note"></textarea>
                                        <div class="invalid-feedback">Please provide a landline number</div>
                                    </div>
                                    <div class="col-lg-1 d-flex align-items-center">
                                        <button type="button" class="btn btn-transparent btn-sm removeContactPersonButton"> <iconify-icon icon="solar:trash-bin-minimalistic-bold" class="text-danger fs-24"></iconify-icon></button>
                                    </div>
                                `;
                                container.appendChild(newForm);
                            });

                            document.getElementById('contactPersonsContainer').addEventListener('click', function (e) {
                                const button = e.target.closest('.removeContactPersonButton');
                                if (button) {
                                    const forms = document.querySelectorAll('.contact-person-form');
                                    if (forms.length > 1) {
                                        button.closest('.contact-person-form').remove();
                                    }
                                }
                            });
                        </script>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label for="office_notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="office_notes" name="office_notes" rows="3" placeholder="Enter Notes" required>{{ old('office_notes') }}</textarea>
                                <div class="invalid-feedback">Please provide notes</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3 rounded">
                <div class="row justify-content-end g-2">
                    <div class="col-lg-2">
                        <a href="{{ route('head-offices.list') }}" class="btn btn-dark w-100">Cancel</a>
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
        const form = document.getElementById('editHeadOfficeForm');
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

</script>
@endsection
@section('script-bottom')
@vite(['resources/js/components/form-fileupload.js'])
@endsection
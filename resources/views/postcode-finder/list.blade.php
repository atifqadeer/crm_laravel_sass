@extends('layouts.vertical', ['title' => 'PostCode Finder', 'subTitle' => 'Home'])

@section('content')
    <style>
        .print_result {
            border: 1px solid #ddd;
            padding: 20px;
        }
        .print_result .card-body {
            padding: 0;
        }
        .print_result .card-body p {
            margin: 0;
        }
        .card {
            margin-bottom: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.345);
        }
        .card-container {
            max-height: 80vh;
            overflow-y: auto;
        }

    </style>
@php
    $jobCategories = \Horsefly\JobCategory::where('is_active', 1)->orderBy('name', 'asc')->get();
@endphp
<div class="row">
    <div class="col-xl-3 col-lg-3">
        <div class="card">
            <div class="card-header bg-light-subtle">
                <h4 class="card-title">Find PostCode</h4>
            </div>
            <div class="card-body">
                <form id="postcodeFinderForm" action="{{ route('getPostcodeResults') }}" class="needs-validation" novalidate>
                    @csrf()
                    <div class="mb-3">
                        <input type="text" id="postcode" name="postcode" class="form-control" placeholder="Enter PostCode" required>
                        <div class="invalid-feedback">Please enter a postcode</div>
                    </div>
                    <div class="mb-3">
                        <select class="form-select" id="radius" name="radius" required>
                            <option value="">Select Radius</option>
                            <option value="5" {{ old('radius') == 5 ? 'selected' : '' }}>5 KMs</option>
                            <option value="10" {{ old('radius') == 10 ? 'selected' : '' }}>10 KMs</option>
                            <option value="15" {{ old('radius') == 15 ? 'selected' : '' }}>15 KMs</option>
                            <option value="20" {{ old('radius') == 20 ? 'selected' : '' }}>20 KMs</option>
                            <option value="25" {{ old('radius') == 25 ? 'selected' : '' }}>25 KMs</option>
                            <option value="30" {{ old('radius') == 30 ? 'selected' : '' }}>30 KMs</option>
                            <option value="35" {{ old('radius') == 35 ? 'selected' : '' }}>35 KMs</option>
                            <option value="40" {{ old('radius') == 40 ? 'selected' : '' }}>40 KMs</option>
                            <option value="45" {{ old('radius') == 45 ? 'selected' : '' }}>45 KMs</option>
                            <option value="50" {{ old('radius') == 50 ? 'selected' : '' }}>50 KMs</option>
                            <option value="60" {{ old('radius') == 60 ? 'selected' : '' }}>60 KMs</option>
                            <option value="70" {{ old('radius') == 70 ? 'selected' : '' }}>70 KMs</option>
                            <option value="80" {{ old('radius') == 80 ? 'selected' : '' }}>80 KMs</option>
                            <option value="90" {{ old('radius') == 90 ? 'selected' : '' }}>90 KMs</option>
                            <option value="100" {{ old('radius') == 100 ? 'selected' : '' }}>100 KMs</option>
                        </select>
                        <div class="invalid-feedback">Please select a radius</div>
                    </div>
                    <div class="mb-3">
                        <select class="form-select" id="job_category" name="job_category_id" required>
                            <option value="">Select Job Category</option>
                            @foreach($jobCategories as $category)
                                <option value="{{ $category->id }}" {{ old('job_category_id') == $category->id ? 'selected':'' }}>{{ ucwords($category->name) }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please select a job category</div>
                    </div>
                    <div class="card-footer bg-light-subtle">
                        <button type="submit" class="btn btn-primary w-100">Find PostCode</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-9 col-lg-9 card-container">
        <div class="card print_result">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between my-3 gap-2">
                    <div>
                        <p>Please find out any data.</p>
                    </div>
                 
                </div>
               
            </div>

        </div>
    </div>
</div>
@section('script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
        <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('postcodeFinderForm');
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                // Remove previous validation feedback
                form.classList.remove('was-validated');

                // Simple client-side validation
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    return;
                }

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(async response => {
                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'An error occurred.');
                    }
                    return response.json();
                })
                .then(data => {
                    // Find the target card container
                    const cardContainer = document.querySelector('.card-container');

                    // Clear any existing cards (optional)
                    cardContainer.innerHTML = '';

                    // If the response contains coordinate results
                    if (data.data.cordinate_results && data.data.cordinate_results.length > 0) {
                        // Loop through each result and create a new card
                        data.data.cordinate_results.forEach(result => {
                            // Create a new card element
                            const card = document.createElement('div');
                            card.classList.add('card', 'print_result');
                            
                            // Create card body
                            const cardBody = document.createElement('div');
                            cardBody.classList.add('card-body');
                            const url = `/sales/fetch-applicants-by-radius/${result.id}/${data.radius}`;
                            // Build the HTML content for each card
                            const cardContent = `
                                <div class="row d-flex flex-wrap justify-content-between my-1 gap-2">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <div>
                                                <a href="#!" class="fs-18 text-dark fw-medium">
                                                    ${result.job_title.toUpperCase()} / ${result.job_category.toUpperCase()} / 
                                                    <span class="badge bg-primary text-white">${result.position_type.toUpperCase()}</span> / 
                                                    <span class="badge ${result.cv_limit_remains == 0 ? 'bg-danger' : 'bg-success'} text-white">
                                                        ${result.cv_limit_remains} ${result.cv_limit_remains == 0 ? 'Limit Reached' : 'Limit Remains'}</span>
                                                    </span>
                                                </a>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <span class="badge bg-info text-white">Distance: ${result.distance ? parseFloat(result.distance).toFixed(2) + ' KMs' : '-'}</span>
                                                <span class="badge bg-dark text-white">
                                                    ${result.created_at ? moment(result.created_at).format('D MMM YYYY') : '-'}
                                                </span>
                                            </div>
                                        </div>
                                        <p class="d-flex align-items-center gap-1 mt-1 mb-0">
                                            <iconify-icon icon="solar:map-point-wave-bold-duotone" class="fs-22 text-danger"></iconify-icon>
                                            <a href="${url}" style="color:blue">${result.sale_postcode.toUpperCase()}</a> - ${result.office_name}, ${result.unit_name}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <b>Benefits:</b> ${result.benefits}
                                </div>
                                <div class="mt-1">
                                    <p><b>Salary:</b> ${result.salary}</p>
                                    <p><b>Timing:</b> ${result.timing}</p>
                                    <p><b>Experience:</b> ${result.experience}</p>
                                    <p><b>Qualification:</b> ${result.qualification}</p>
                                </div>
                            `;

                            // Insert the content into the card body
                            cardBody.innerHTML = cardContent;

                            // Append the card body to the card
                            card.appendChild(cardBody);

                            // Append the card to the card container
                            cardContainer.appendChild(card);
                        });
                    } else {
                        // If no coordinate results are found, show a message
                        const card = document.createElement('div');
                        card.classList.add('card', 'print_result');
                        
                        const cardBody = document.createElement('div');
                        cardBody.classList.add('card-body');
                        cardBody.innerHTML = '<p>No job found.</p>';
                        
                        card.appendChild(cardBody);
                        cardContainer.appendChild(card);
                    }
                })
                .catch(error => {
                    toastr.error(error.message || 'An unexpected error occurred.');
                    console.error("Fetch error:", error);
                });

            });
        });

    </script>
@endsection
@endsection
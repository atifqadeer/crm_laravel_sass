@extends('layouts.vertical', ['title' => 'Job Details', 'subTitle' => 'Sales'])

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-highlight">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <ul class="list-unstyled mb-0">
                                <li><strong>Head Office Name:</strong> {{ $office->office_name ?? 'N/A' }}</li>
                                <li><strong>Unit Name:</strong> {{ $unit->unit_name ?? 'N/A' }}</li>
                                <li><strong>PostCode:</strong> {{ strtoupper($sale->sale_postcode) ?? 'N/A' }}</li>
                                <li><strong>Category:</strong> {{ $jobCategory ? ucwords($jobCategory->name) . $jobType : 'N/A' }}</li>
                                <li><strong>Title:</strong> {{ $jobTitle ? ucwords($jobTitle->name) : 'N/A' }}</li>
                                <li><strong>Qualification:</strong> {!! $sale->qualification !!}</li>
                                <li><strong>Benefits:</strong> {!! $sale->benefits !!}</li>
                            </ul>
                        </div>
                        <div class="col-md-6 mb-3">
                            <ul class="list-unstyled mb-0">
                                <input type="hidden" id="sale_id" value="">

                                <li><strong>Sale ID#:</strong> {{ $sale->id ?? 'N/A' }}</li>
                                <li><strong>Position Type:</strong> {!! $sale->position_type ? '<span class="badge bg-primary text-white fs-12">' . ucwords(str_replace('-', ' ', $sale->position_type)) . '</span>' : 'N/A' !!}</li>
                                <li><strong>Salary:</strong> {{ $sale->salary }}</li>
                                <li><strong>Timing:</strong> {{ $sale->timing }}</li>
                                <li><strong>Experience:</strong> {!! $sale->experience !!}</li>
                                <li><strong>Status:</strong>
                                    @php
                                        $status = $sale->status;
                                        if ($status == '1') {
                                            $statusClass = '<span class="badge bg-success">Active</span>';
                                        }elseif ($status == '2') {
                                            $statusClass = '<span class="badge bg-warning">Pending</span>';
                                        } else {
                                            $statusClass = '<span class="badge bg-danger">Inactive</span>';
                                        }
                                    @endphp
                                    {!! $statusClass !!}
                                </li>
                                <li>
                                    <button class="btn btn-warning btn-sm my-1" type="button" id="viewDocuments">
                                        <span class="nav-icon">
                                            <i class="ri-file-text-line fs-16"></i>
                                        </span>
                                        View Documents
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row justify-content-center">
    <div class="col-xl-12 col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Active Applicants within {{ $radius }}KMs / {{ $radiusInMiles }}Miles</h4>
                            <div>
                                 <!-- Button Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton4" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterStatus">All</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton4">
                                        <a class="dropdown-item status-filter" href="#">All</a>
                                        <a class="dropdown-item status-filter" href="#">Interested</a>
                                        <a class="dropdown-item status-filter" href="#">Not Interested</a>
                                        <a class="dropdown-item status-filter" href="#">No Job</a>
                                        <a class="dropdown-item status-filter" href="#">Blocked</a>
                                        <a class="dropdown-item status-filter" href="#">Have Nursing Home Experience</a>
                                    </div>
                                </div>
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i> Export
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                        <a class="dropdown-item" href="{{ route('applicantsExport', ['type' => 'withinRadius', 'radius' => $radius, 'model_type' => 'Horsefly\Sale', 'model_id' => $sale->id ]) }}">Export Data</a>
                                    </div>
                                </div>
                                <!-- Add Updated Sales Filter Button -->
                                <button class="btn btn-success my-1" title="Mark selected as having nursing home experience" type="button" id="markNursingHomeBtn">
                                    <span class="nav-icon">
                                        <i class="ri-check-line fs-16"></i>
                                    </span>
                                    Mark as Nursing Home Exp
                                </button>
                                <button class="btn btn-danger my-1" title="Mark selected as having no nursing home experience" type="button" id="markNoNursingHomeBtn">
                                     <span class="nav-icon">
                                        <i class="ri-close-line fs-16"></i>
                                    </span>
                                    Mark as No Nursing Home Exp
                                </button>
                            </div>
                            <!-- Button Dropdown -->
                        </div>
                        <div class="card">
                            <div class="card-body p-3">
                                <div class="table-responsive">
                                    <table id="applicants_table" class="table align-middle mb-3">
                                        <thead class="bg-light-subtle">
                                            <tr>
                                                <th><input type="checkbox" id="master-checkbox"></th>
                                                <th>Date</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Title</th>
                                                <th>Category</th>
                                                <th>PostCode</th>
                                                <th>Phone</th>
                                                <th>Landline</th>
                                                <th>Resume</th>
                                                <th>Experience</th>
                                                <th>Source</th>
                                                <th>Notes</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {{-- The data will be populated here by DataTables --}}
                                        </tbody>
                                    </table>
                                </div>
                                <!-- end table-responsive -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
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
        $(document).ready(function() {
            var currentFilter = '';

            // Create a loader row and append it to the table before initialization
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="100%" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td>`;

            // Append the loader row to the table's tbody
            $('#applicants_table tbody').append(loadingRow);

            // Initialize DataTable with server-side processing
            var table = $('#applicants_table').DataTable({
                processing: false,  // Disable default processing state
                serverSide: true,  // Enables server-side processing
                ajax: {
                    url: @json(route('getApplicantsBySaleRadius')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        d.sale_id = {{ $sale->id }};
                        d.radius = {{ $radius }};
                        d.status_filter = currentFilter;  // Send the current filter value as a parameter
                        // Clean up search parameter
                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }
                    }
                },
                columns: [
                    { data: 'checkbox', 'name': 'checkbox', orderable: false, searchable: false },
                    // { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at', name: 'applicants.created_at' },
                    { data: 'applicant_name', name: 'applicants.applicant_name' },
                    { data: 'applicant_email', name: 'applicants.applicant_email' },
                    { data: 'job_title', name: 'job_titles.name' },
                    { data: 'job_category', name: 'job_categories.name' },
                    { data: 'applicant_postcode', name: 'applicants.applicant_postcode' },
                    { data: 'applicant_phone', name: 'applicants.applicant_phone' },
                    { data: 'applicant_landline', name: 'applicants.applicant_landline' },
                    { data: 'resume', name:'applicants.resume', orderable: false, searchable: false },
                    { data: 'applicant_experience', name: 'applicants.applicant_experience' },
                    { data: 'job_source', name: 'job_sources.name' },
                    { data: 'applicant_notes', name: 'applicants.applicant_notes', orderable: false, searchable: false },
                    { data: 'paid_status', name: 'applicants.paid_status', searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                rowId: function(data) {
                    return 'row_' + data.id; // Assign a unique ID to each row using the 'id' field from the data
                },
                dom: 'flrtip',  // Change the order to 'filter' (f), 'length' (l), 'table' (r), 'pagination' (p), and 'information' (i)
                drawCallback: function (settings) {
                    const api = this.api();
                    const pagination = $(api.table().container()).find('.dataTables_paginate');
                    pagination.empty();

                    const pageInfo = api.page.info();
                    const currentPage = pageInfo.page + 1;
                    const totalPages = pageInfo.pages;

                    if (pageInfo.recordsTotal === 0) {
                        $('#applicants_table tbody').html('<tr><td colspan="100%" class="text-center">Data not found</td></tr>');
                        return;
                    }

                    let paginationHtml = `
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-rounded mb-0">
                                        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                                            <a class="page-link" href="javascript:void(0);" aria-label="Previous" onclick="movePage('previous')">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>`;

                        const visiblePages = 3;
                        const showDots = totalPages > visiblePages + 2;

                        // Always show page 1
                        paginationHtml += `<li class="page-item ${currentPage === 1 ? 'active' : ''}">
                            <a class="page-link" href="javascript:void(0);" onclick="movePage(1)">1</a>
                        </li>`;

                        let start = Math.max(2, currentPage - 1);
                        let end = Math.min(totalPages - 1, currentPage + 1);

                        if (start > 2) {
                            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                        }

                        for (let i = start; i <= end; i++) {
                            paginationHtml += `<li class="page-item ${currentPage === i ? 'active' : ''}">
                                <a class="page-link" href="javascript:void(0);" onclick="movePage(${i})">${i}</a>
                            </li>`;
                        }

                        if (end < totalPages - 1) {
                            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                        }

                        if (totalPages > 1) {
                            paginationHtml += `<li class="page-item ${currentPage === totalPages ? 'active' : ''}">
                                <a class="page-link" href="javascript:void(0);" onclick="movePage(${totalPages})">${totalPages}</a>
                            </li>`;
                        }

                        // Next button
                        paginationHtml += `
                            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                                <a class="page-link" href="javascript:void(0);" aria-label="Next" onclick="movePage('next')">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                        </nav>

                        <div class="d-flex align-items-center ms-3 text-primary">
                            <span class="me-2">Go to page:</span>
                            <input type="number" id="goToPageInput" min="1" max="${totalPages}" class="form-control form-control-sm" style="width: 80px;" 
                                onkeydown="if(event.key === 'Enter') goToPage(${totalPages})">
                        </div>
                        <small id="goToPageError" class="text-danger mt-1" style="font-size: 12px;"></small>
                        </div>`;

                    pagination.html(paginationHtml);
                },
            });

            // Status filter dropdown handler
            $('.status-filter').on('click', function () {
                currentFilter = $(this).text().toLowerCase();

                // Capitalize each word
                const formattedText = currentFilter
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterStatus').html(formattedText);
                table.ajax.reload(); // Reload with updated status filter
            });
        });
        function goToPage(totalPages) {
            const input = document.getElementById('goToPageInput');
            const errorMessage = document.getElementById('goToPageError');
            let page = parseInt(input.value);

            if (!isNaN(page) && page >= 1 && page <= totalPages) {
                $('#applicants_table').DataTable().page(page - 1).draw('page');
                input.classList.remove('is-invalid');
            } else {
                input.classList.add('is-invalid');
            }
        }

        // Function to move the page forward or backward
        function movePage(page) {
            var table = $('#applicants_table').DataTable();
            var currentPage = table.page.info().page + 1;
            var totalPages = table.page.info().pages;

            if (page === 'previous' && currentPage > 1) {
                table.page(currentPage - 2).draw('page');  // Move to the previous page
            } else if (page === 'next' && currentPage < totalPages) {
                table.page(currentPage).draw('page');  // Move to the next page
            } else if (typeof page === 'number' && page !== currentPage) {
                table.page(page - 1).draw('page');  // Move to the selected page
            }
        }
        // Function to mark as not interested modal
        function markNotInterestedModal(applicantID, saleID) {
            // Add the modal HTML to the page (only once, if not already present)
            if ($('#markApplicantNotInerestedModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="markApplicantNotInerestedModal" tabindex="-1" aria-labelledby="markApplicantNotInerestedModalLabel" aria-hidden="true">' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="markApplicantNotInerestedModalLabel">Mark As Not Interested On Sale</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<form id="markApplicantNotInterestedForm">' +
                                        '<div class="mb-3">' +
                                            '<label for="detailsTextareaApplicantNotInterested" class="form-label">Details</label>' +
                                            '<textarea class="form-control" id="detailsTextareaApplicantNotInterested" rows="4" required></textarea>' +
                                        '</div>' +
                                    '</form>' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>' +
                                    '<button type="button" class="btn btn-primary" id="saveApplicantNotInterestedButton">'+
                                        'Save</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Show the modal
            $('#markApplicantNotInerestedModal').modal('show');

            // Handle the save button click
            $('#saveApplicantNotInterestedButton').off('click').on('click', function() {
                const notes = $('#detailsTextareaApplicantNotInterested').val();

                if (!notes) {
                    if (!notes) {
                        $('#detailsTextareaApplicantNotInterested').addClass('is-invalid');
                        if ($('#detailsTextareaApplicantNotInterested').next('.invalid-feedback').length === 0) {
                            $('#detailsTextareaApplicantNotInterested').after('<div class="invalid-feedback">Please provide details.</div>');
                        }
                    }
                
                    // Add event listeners to remove validation errors dynamically
                    $('#detailsTextareaApplicantNotInterested').on('input', function() {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // Remove validation errors if inputs are valid
                $('#detailsTextareaApplicantNotInterested').removeClass('is-invalid').addClass('is-valid');
                $('#detailsTextareaApplicantNotInterested').next('.invalid-feedback').remove();

                // Send the data via AJAX
                $.ajax({
                    url: '{{ route("markApplicantNotInterestedOnSale") }}', // Replace with your endpoint
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}' // Directly include token in data
                    },
                    success: function(response) {
                        toastr.success('Mark as not interested successfully!');
                        $('#markApplicantNotInerestedModal').modal('hide'); // Close the modal
                        $('#markApplicantNotInterestedForm')[0].reset(); // Clear the form
                        $('#detailsTextareaApplicantNotInterested').removeClass('is-valid'); // Remove valid class
                        $('#detailsTextareaApplicantNotInterested').next('.invalid-feedback').remove(); // Remove error message
                        
                        $('#applicants_table').DataTable().ajax.reload(); // Reload the DataTable
                    },
                    error: function(xhr) {
                        toastr.error('An error occurred while saving notes.');
                    }
                });
            });
        }
        // Function to show the notes modal
        function addShortNotesModal(applicantID) {
            // Add the modal HTML to the page (only once, if not already present)
            if ($('#shortNotesModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="shortNotesModal" tabindex="-1" aria-labelledby="shortNotesModalLabel" aria-hidden="true">' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="shortNotesModalLabel">Add Notes</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<form id="shortNotesForm">' +
                                        '<div class="mb-3">' +
                                            '<label for="detailsTextarea" class="form-label">Details</label>' +
                                            '<textarea class="form-control" id="detailsTextarea" rows="4" required></textarea>' +
                                        '</div>' +
                                        '<div class="mb-3">' +
                                            '<label for="reasonDropdown" class="form-label">Reason</label>' +
                                            '<select class="form-select" id="reasonDropdown" required>' +
                                                '<option value="" disabled selected>Select Reason</option>' +
                                                '<option value="casual">Casual Notes</option>' +
                                                '<option value="blocked">Blocked Notes</option>' +
                                                '<option value="not_interested">Temp Not Interested Notes</option>' +
                                            '</select>' +
                                        '</div>' +
                                    '</form>' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>' +
                                    '<button type="button" class="btn btn-primary" id="saveShortNotesButton">'+
                                        'Save</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Show the modal
            $('#shortNotesModal').modal('show');

            // Handle the save button click
            $('#saveShortNotesButton').off('click').on('click', function() {
                const notes = $('#detailsTextarea').val();
                const reason = $('#reasonDropdown').val();

                if (!notes || !reason) {
                    if (!notes) {
                        $('#detailsTextarea').addClass('is-invalid');
                        if ($('#detailsTextarea').next('.invalid-feedback').length === 0) {
                            $('#detailsTextarea').after('<div class="invalid-feedback">Please provide details.</div>');
                        }
                    }
                
                    if (!reason) {
                        $('#reasonDropdown').addClass('is-invalid');
                        if ($('#reasonDropdown').next('.invalid-feedback').length === 0) {
                            $('#reasonDropdown').after('<div class="invalid-feedback">Please select a reason.</div>');
                        }
                    }
                
                    // Add event listeners to remove validation errors dynamically
                    $('#detailsTextarea').on('input', function() {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });
                
                    $('#reasonDropdown').on('change', function() {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });
                
                    return;
                }

                // Remove validation errors if inputs are valid
                $('#detailsTextarea').removeClass('is-invalid').addClass('is-valid');
                $('#detailsTextarea').next('.invalid-feedback').remove();
                $('#reasonDropdown').removeClass('is-invalid').addClass('is-valid');
                $('#reasonDropdown').next('.invalid-feedback').remove();

                // Send the data via AJAX
                $.ajax({
                    url: '{{ route("storeShortNotes") }}', // Replace with your endpoint
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        details: notes,
                        reason: reason,
                        _token: '{{ csrf_token() }}' // Directly include token in data
                    },
                    success: function(response) {
                        toastr.success('Notes saved successfully!');

                        $('#shortNotesModal').modal('hide'); // Close the modal
                        $('#shortNotesForm')[0].reset(); // Clear the form
                        $('#detailsTextarea').removeClass('is-valid'); // Remove valid class
                        $('#reasonDropdown').removeClass('is-valid'); // Remove valid class
                        $('#detailsTextarea').next('.invalid-feedback').remove(); // Remove error message
                        $('#reasonDropdown').next('.invalid-feedback').remove(); // Remove error message
                        
                        $('#applicants_table').DataTable().ajax.reload(); // Reload the DataTable
                    },
                    error: function(xhr) {
                        toastr.error('An error occurred while saving notes.');
                    }
                });
            });
        }
         // Function to show the notes modal
        function showNotesModal(notes, applicantName, applicantPostcode) {
            // Set the notes content in the modal with proper line breaks using HTML
            $('#showNotesModal .modal-body').html(
                'Applicant Name: <strong>' + applicantName + '</strong><br>' +
                'Postcode: <strong>' + applicantPostcode + '</strong><br>' +
                'Notes Detail: <p>' + notes + '</p>'
            );

            // Show the modal
            $('#showNotesModal').modal('show');

            // Add the modal HTML to the page (only once, if not already present)
            if ($('#showNotesModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="showNotesModal" tabindex="-1" aria-labelledby="showNotesModalLabel" aria-hidden="true">' +
                        '<div class="modal-dialog modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="showNotesModalLabel">Applicant Notes</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<!-- Notes content will be dynamically inserted here -->' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }
        }
        // Function to send cv modal
        function sendCVModal(applicantID, saleID) {
            const modalID = 'sendCVModal' + applicantID + '-' + saleID;
            const formID = 'sendCV_form' + applicantID + '-' + saleID;
            
            // Add modal if not exists
            if ($('#' + modalID).length === 0) {
                $('body').append(
                    '<div class="modal fade" id="' + modalID + '" tabindex="-1" aria-labelledby="sendCVModalLabel" aria-hidden="true">' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="sendCVModalLabel">Fill Out Form To Send CV</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<form class="form-horizontal" id="' + formID + '">' +
                                        '<input type="hidden" name="applicant_id" value="'+ applicantID +'">'+
                                        '<input type="hidden" name="sale_id" value="'+ saleID +'">'+
                                        '<input type="hidden" name="_token" value="{{ csrf_token() }}">'+

                                        '<div class="form-group row">' +
                                            '<label class="col-form-label col-sm-3"><strong style="font-size:18px">1.</strong> Current Employer Name</label>' +
                                            '<div class="col-sm-9">' +
                                                '<input type="text" name="current_employer_name" class="form-control" placeholder="Enter Employer Name">' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="form-group row">' +
                                            '<label class="col-form-label col-sm-3"><strong style="font-size:18px">2.</strong> PostCode</label>' +
                                            '<div class="col-sm-9">' +
                                                '<input type="text" name="postcode" class="form-control" placeholder="Enter PostCode">' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="form-group row">' +
                                            '<label class="col-form-label col-sm-3"><strong style="font-size:18px">3.</strong> Current/Expected Salary</label>' +
                                            '<div class="col-sm-9">' +
                                                '<input type="number" name="expected_salary" class="form-control" placeholder="Enter Salary">' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="form-group row">' +
                                            '<label class="col-form-label col-sm-3"><strong style="font-size:18px">4.</strong> Qualification</label>' +
                                            '<div class="col-sm-9">' +
                                                '<input type="text" name="qualification" class="form-control" placeholder="Enter Qualification">' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="form-group row">' +
                                            '<label class="col-form-label col-sm-3"><strong style="font-size:18px">5.</strong> Transport Type</label>' +
                                            '<div class="col-sm-9 d-flex align-items-center">' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input" type="checkbox" name="transport_type[]" id="by_walk" value="By Walk"><label class="form-check-label" for="by_walk">By Walk</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input" type="checkbox" name="transport_type[]" id="cycle" value="Cycle">' +
                                                    '<label class="form-check-label" for="cycle">Cycle</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input" type="checkbox" name="transport_type[]" id="car" value="Car">' +
                                                    '<label class="form-check-label" for="car">Car</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input" type="checkbox" name="transport_type[]" id="public_transport" value="Public Transport">' +
                                                    '<label class="form-check-label" for="public_transport">Public Transport</label>' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="form-group row">' +
                                            '<label class="col-form-label col-sm-3"><strong style="font-size:18px">6.</strong> Shift Pattern</label>' +
                                            '<div class="col-sm-9 d-flex align-items-center">' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input" type="checkbox" name="shift_pattern[]" id="day" value="Day">' +
                                                    '<label class="form-check-label" for="day">Day</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input" type="checkbox" name="shift_pattern[]" id="night" value="Night">' +
                                                    '<label class="form-check-label" for="night">Night</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input" type="checkbox" name="shift_pattern[]" id="full_time" value="Full Time">' +
                                                    '<label class="form-check-label" for="full_time">Full Time</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input" type="checkbox" name="shift_pattern[]" id="part_time" value="Part Time">' +
                                                    '<label class="form-check-label" for="part_time">Part Time</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input" type="checkbox" name="shift_pattern[]" id="twenty_four_hours" value="24 hours">' +
                                                    '<label class="form-check-label" for="twenty_four_hours">24 Hours</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input" type="checkbox" name="shift_pattern[]" id="day_night" value="Day/Night">' +
                                                    '<label class="form-check-label" for="day_night">Day/Night</label>' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="form-group row">'+
                                            '<label class="col-form-label col-sm-3"><strong style="font-size:18px">7.</strong> Visa Status</label>'+
                                            '<div class="col-sm-9 d-flex align-items-center">'+
                                                '<div class="d-flex">'+
                                                    '<div class="form-check form-check-inline">'+
                                                        '<input type="radio" name="visa_status" id="british" class="form-check-input" value="British">'+
                                                        '<label class="form-check-label" for="british">British</label>'+
                                                    '</div>'+
                                                    '<div class="form-check form-check-inline ml-3">'+
                                                        '<input type="radio" name="visa_status" id="required_sponsorship" class="form-check-input" value="Required Sponsorship">'+
                                                        '<label class="form-check-label" for="required_sponsorship">Required Sponsorship</label>'+
                                                    '</div>'+
                                                '</div>'+
                                            '</div>'+
                                        '</div>'+
                                        '<div class="form-group row">' +
                                            '<div class="col-6 my-2">'+
                                                '<div class="form-check form-switch">'+
                                                    '<input class="form-check-input" type="checkbox" role="switch" name="nursing_home" id="nursing_home_checkbox">'+
                                                    '<label class="form-check-label" for="nursing_home_checkbox">Nursing Home</label>'+
                                                '</div>'+
                                            '</div>'+
                                            '<div class="col-6 my-2">'+
                                                '<div class="form-check form-switch">'+
                                                    '<input class="form-check-input" type="checkbox" role="switch" name="alternate_weekend" id="alternate_weekend_checkbox">'+
                                                    '<label class="form-check-label" for="alternate_weekend_checkbox">Alternate Weekend</label>'+
                                                '</div>'+
                                            '</div>'+
                                            '<div class="col-6 my-2">'+
                                                '<div class="form-check form-switch">'+
                                                    '<input class="form-check-input" type="checkbox" role="switch" name="interview_availability" id="interview_availability_checkbox">'+
                                                    '<label class="form-check-label" for="interview_availability_checkbox">Interview Availability</label>'+
                                                '</div>'+
                                            '</div>'+
                                           '<div class="col-6 my-2">'+
                                                '<div class="form-check form-switch">'+
                                                    '<input class="form-check-input" type="checkbox" role="switch" name="no_job" id="no_job_checkbox" onclick="handleCheckboxClick(\'no_job_checkbox\', \'hangup_call_checkbox\')">'+
                                                    '<label class="form-check-label" for="no_job_checkbox">No Job</label>'+
                                                '</div>'+
                                            '</div>'+
                                            '<div class="col-6 my-2">'+
                                                '<div class="form-check form-switch">'+
                                                    '<input class="form-check-input" type="checkbox" name="hangup_call" role="switch" id="hangup_call_checkbox" onclick="handleCheckboxClick(\'hangup_call_checkbox\', \'no_job_checkbox\')">'+
                                                    '<label class="form-check-label" for="hangup_call_checkbox">Call Hung up/Not Interested</label>'+
                                                '</div>'+
                                            '</div>'+
                                        '</div>'+
                                        '<div class="form-group">'+
                                            '<label class="col-form-label col-sm-12" for="note_details">Other Details <span class="text-danger">*</span></label>'+
                                            '<div class="col-sm-12">'+
                                                '<textarea name="details" id="note_details" class="form-control" cols="30" rows="4" placeholder="Type here ..." required></textarea>'+
                                            '</div>'+
                                        '</div>'+
                                    '</form>'+
                                '</div>'+
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>' +
                                    '<button type="button" class="btn btn-primary" id="saveCVBtn_' + applicantID + '_' + saleID + '">Save</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            // Handle save button click - using event delegation
                $(document).off('click', '#saveCVBtn_' + applicantID + '_' + saleID).on('click', '#saveCVBtn_' + applicantID + '_' + saleID, function() {
                    const form = $('#' + formID);
                    const notes = form.find('[name="details"]').val();
                    let isValid = true;
                    
                    // Reset validation
                    form.find('.is-invalid').removeClass('is-invalid');
                    form.find('.invalid-feedback').remove();
                    
                    // Validate required fields
                    if (!notes) {
                        form.find('[name="details"]').addClass('is-invalid')
                            .after('<div class="invalid-feedback">Please enter note details.</div>');
                        isValid = false;
                    }
                    
                    // Validate at least one transport type is selected
                    if (form.find('[name="transport_type[]"]:checked').length === 0) {
                        form.find('[name="transport_type[]"]').first().closest('.form-group').find('.col-sm-9')
                            .append('<div class="invalid-feedback d-block">Please select at least one transport type.</div>');
                        isValid = false;
                    }
                    
                    // Validate visa status is selected
                    if (!form.find('[name="visa_status"]:checked').val()) {
                        form.find('[name="visa_status"]').first().closest('.form-group').find('.col-sm-9')
                            .append('<div class="invalid-feedback d-block">Please select visa status.</div>');
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        return false;
                    }
                    
                    // Show loading state
                    const btn = $(this);
                    const originalText = btn.html();
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...');
                    
                    // Submit form via AJAX
                    $.ajax({
                        url: '{{ route("sendCVtoQuality") }}',
                        type: 'POST',
                        data: form.serialize(),
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message || 'CV sent successfully!');
                                $('#' + modalID).modal('hide');
                                $('#applicants_table').DataTable().ajax.reload();
                            } else {
                                toastr.error(response.message || 'Failed to send CV');
                            }
                        },
                        error: function(xhr) {
                            let message = 'An error occurred';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            } else if (xhr.statusText) {
                                message = xhr.statusText;
                            }
                            toastr.error(message);
                        },
                        complete: function() {
                            btn.prop('disabled', false).html(originalText);
                        }
                    });
                });
            }
            
            // Reset form when modal shows
            $('#' + modalID).on('show.bs.modal', function() {
                $(this).find('form')[0].reset();
                $(this).find('.is-invalid').removeClass('is-invalid');
                $(this).find('.invalid-feedback').remove();
            });
            
            // Show modal
            $('#' + modalID).modal('show');
        }
        // Function to mark no nursing home modal
        function markNoNursingHomeModal(applicantID) {
            // Add the modal HTML to the page (only once, if not already present)
            if ($('#markNoNursingHomeModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="markNoNursingHomeModal" tabindex="-1" aria-labelledby="markNoNursingHomeModalLabel" aria-hidden="true">' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="markNoNursingHomeModalLabel">Mark As No Nursing Home</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<form id="markNoNursingHomeForm">' +
                                        '<div class="mb-3">' +
                                            '<label for="detailsTextareaMarkNoNursingHome" class="form-label">Details</label>' +
                                            '<textarea class="form-control" id="detailsTextareaMarkNoNursingHome" rows="4" required></textarea>' +
                                        '</div>' +
                                    '</form>' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>' +
                                    '<button type="button" class="btn btn-primary" id="saveMarkNoNursingHomeButton">'+
                                        'Save</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Show the modal
            $('#markNoNursingHomeModal').modal('show');

            // Handle the save button click
            $('#saveMarkNoNursingHomeButton').off('click').on('click', function() {
                const notes = $('#detailsTextareaMarkNoNursingHome').val();

                if (!notes) {
                    if (!notes) {
                        $('#detailsTextareaMarkNoNursingHome').addClass('is-invalid');
                        if ($('#detailsTextareaMarkNoNursingHome').next('.invalid-feedback').length === 0) {
                            $('#detailsTextareaMarkNoNursingHome').after('<div class="invalid-feedback">Please provide details.</div>');
                        }
                    }
                
                    // Add event listeners to remove validation errors dynamically
                    $('#detailsTextareaMarkNoNursingHome').on('input', function() {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // Remove validation errors if inputs are valid
                $('#detailsTextareaMarkNoNursingHome').removeClass('is-invalid').addClass('is-valid');
                $('#detailsTextareaMarkNoNursingHome').next('.invalid-feedback').remove();

                // Send the data via AJAX
                $.ajax({
                    url: '{{ route("markApplicantNoNursingHome") }}', // Replace with your endpoint
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        details: notes,
                        _token: '{{ csrf_token() }}' // Directly include token in data
                    },
                    success: function(response) {
                        toastr.success('Mark no nursing home saved successfully!');
                        $('#markNoNursingHomeModal').modal('hide'); // Close the modal
                        $('#markNoNursingHomeForm')[0].reset(); // Clear the form
                        $('#detailsTextareaMarkNoNursingHome').removeClass('is-valid'); // Remove valid class
                        $('#detailsTextareaMarkNoNursingHome').next('.invalid-feedback').remove(); // Remove error message
                        
                        $('#applicants_table').DataTable().ajax.reload(); // Reload the DataTable

                    },
                    error: function(xhr) {
                        toastr.error('An error occurred while saving notes.');
                    }
                });
            });
        }
        // Function to mark no nursing home modal
        function markApplicantCallbackModal(applicantID, saleID) {
            // Add the modal HTML to the page (only once, if not already present)
            if ($('#markApplicantCallbackModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="markApplicantCallbackModal" tabindex="-1" aria-labelledby="markApplicantCallbackModalLabel" aria-hidden="true">' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="markApplicantCallbackModalLabel">Mark As Callback</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<form id="markCallbackForm">' +
                                        '<div class="mb-3">' +
                                            '<label for="detailsTextareaMarkCallback" class="form-label">Details</label>' +
                                            '<textarea class="form-control" id="detailsTextareaMarkCallback" rows="4" required></textarea>' +
                                        '</div>' +
                                    '</form>' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>' +
                                    '<button type="button" class="btn btn-primary" id="saveMarkCallbackButton">'+
                                        'Save</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Show the modal
            $('#markApplicantCallbackModal').modal('show');

            // Handle the save button click
            $('#saveMarkCallbackButton').off('click').on('click', function() {
                const notes = $('#detailsTextareaMarkCallback').val();

                if (!notes) {
                    if (!notes) {
                        $('#detailsTextareaMarkCallback').addClass('is-invalid');
                        if ($('#detailsTextareaMarkCallback').next('.invalid-feedback').length === 0) {
                            $('#detailsTextareaMarkCallback').after('<div class="invalid-feedback">Please provide details.</div>');
                        }
                    }
                
                    // Add event listeners to remove validation errors dynamically
                    $('#detailsTextareaMarkCallback').on('input', function() {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // Remove validation errors if inputs are valid
                $('#detailsTextareaMarkCallback').removeClass('is-invalid').addClass('is-valid');
                $('#detailsTextareaMarkCallback').next('.invalid-feedback').remove();

                // Send the data via AJAX
                $.ajax({
                    url: '{{ route("markApplicantCallback") }}', // Replace with your endpoint
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}' // Directly include token in data
                    },
                    success: function(response) {
                        toastr.success('Mark callback saved successfully!');
                        $('#markApplicantCallbackModal').modal('hide'); // Close the modal
                        $('#markCallbackForm')[0].reset(); // Clear the form
                        $('#detailsTextareaMarkCallback').removeClass('is-valid'); // Remove valid class
                        $('#detailsTextareaMarkCallback').next('.invalid-feedback').remove(); // Remove error message
                        
                        $('#applicants_table').DataTable().ajax.reload(); // Reload the DataTable

                    },
                    error: function(xhr) {
                        toastr.error('An error occurred while saving notes.');
                    }
                });
            });
        }
          // Disable the Unblock button by default
        $('#markNursingHomeBtn').prop('disabled', true);
        $('#markNoNursingHomeBtn').prop('disabled', true);

        // Enable the Unblock button when any checkbox is checked, disable if none checked
        $(document).on('change', '.applicant_checkbox, #master-checkbox', function() {
            var anyChecked = $('.applicant_checkbox:checked').length > 0;
            $('#markNursingHomeBtn').prop('disabled', !anyChecked);
            $('#markNoNursingHomeBtn').prop('disabled', !anyChecked);
        });

         $('#master-checkbox').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('.applicant_checkbox').prop('checked', isChecked);

            // Manually toggle the DataTables selected class
            $('.applicant_checkbox').each(function() {
                var $row = $(this).closest('tr');
                if (isChecked) {
                    $row.addClass('selected');
                } else {
                    $row.removeClass('selected');
                }
            });
        });

        // Add a listener to individual checkboxes to update the master checkbox state
        $(document).on('change', '.applicant_checkbox', function() {
            var allCheckboxesChecked = $('.applicant_checkbox:checked').length === $('.applicant_checkbox').length;
            $('#master-checkbox').prop('checked', allCheckboxesChecked);

            // Manually toggle the DataTables selected class
            var $row = $(this).closest('tr');
            if ($(this).prop('checked')) {
                $row.addClass('selected');
            } else {
                $row.removeClass('selected');
            }
        });
        // Handle the button click to send an AJAX request
		$('#markNursingHomeBtn').on('click', function () {
			// Get all the selected checkboxes
			var selectedCheckboxes = [];
			$('.applicant_checkbox:checked').each(function () {
				selectedCheckboxes.push($(this).val()); // Push the value of the checked checkboxes to the array
			});

			if (selectedCheckboxes.length > 0) {
				// Send the selected values in an AJAX request
				$.ajax({
                    url: "{{ route('markAsNursingHomeExp') }}",
					method: 'POST',
					data: {
						selectedCheckboxes: selectedCheckboxes,
						_token: '{{ csrf_token() }}'
					},
					success: function (response) {
						 // Hide the rows that were successfully updated
						selectedCheckboxes.forEach(function(rowId) {
							$('#' + rowId).fadeOut(500); // 500ms = half-second fade

							// 2. Uncheck the checkbox based on matching value
							$('input.applicant_checkbox[value="' + rowId + '"]').prop('checked', false);
						});

						toastr.success('Marked nursing home experience successfully!');
						console.log(response); // You can log the server response for debugging
					},
					error: function (error) {
						// Handle the error response here
						toastr.error('Something went wrong. Please try again.');
						console.error(error);
					}
				});
			} else {
				toastr.error('Please select at least one checkbox.');
			}
		});

		// Handle the button click to send an AJAX request
		$('#markNoNursingHomeBtn').on('click', function () {
			// Get all the selected checkboxes
			var selectedCheckboxes = [];
			$('.applicant_checkbox:checked').each(function () {
				selectedCheckboxes.push($(this).val()); // Push the value of the checked checkboxes to the array
			});

			if (selectedCheckboxes.length > 0) {
				// Send the selected values in an AJAX request
				$.ajax({
                    url: "{{ route('markAsNoNursingHomeExp') }}",
					method: 'POST',
					data: {
						selectedCheckboxes: selectedCheckboxes,
						_token: '{{ csrf_token() }}'
					},
					success: function (response) {
						 // Hide the rows that were successfully updated
						selectedCheckboxes.forEach(function(rowId) {
							$('#' + rowId).addClass('marked-no-nursing-home'); 

							// 2. Uncheck the checkbox based on matching value
							$('input.applicant_checkbox[value="' + rowId + '"]').prop('checked', false);
						});

						toastr.success('Marked no nursing home experience successfully!');
						console.log(response); // You can log the server response for debugging
					},
					error: function (error) {
						// Handle the error response here
						toastr.error('Something went wrong. Please try again.');
						console.error(error);
					}
				});
			} else {
				toastr.error('Please select at least one checkbox.');
			}
		});

        // Function to show the notes modal
        $('#viewDocuments').on('click', function () {
            // Make an AJAX call to retrieve notes history data
            var id = $('#sale_id').val();

            $.ajax({
                url: '{{ route("getSaleDocuments") }}', // Your backend URL to fetch notes history, replace it with your actual URL
                type: 'GET',
                data: {
                    id: id
                }, // Pass the id to your server to fetch the corresponding applicant's notes
                success: function(response) {
                    console.log(response);
                    var notesHtml = '';  // This will hold the combined HTML for all notes

                    // Check if the response data is empty
                    if (response.data.length === 0) {
                        notesHtml = '<p>No record found.</p>';
                    } else {
                        // Loop through the response array (assuming it's an array of documents)
                        response.data.forEach(function(doc) {
                            var doc_name = doc.document_name;
                            var created = moment(doc.created_at).format('DD MMM YYYY, h:mmA');
                            var file_path = '/storage/' + doc.document_path;

                            // Append each document's details to the notesHtml string, with a button to open in new tab
                            notesHtml += 
                                '<div class="note-entry">' +
                                    '<p><strong>Dated:</strong> ' + created + '</p>' +
                                    '<p><strong>File:</strong> ' + doc_name + 
                                    '<br> <button class="btn btn-sm btn-primary" onclick="window.open(\'' + file_path + '\', \'_blank\')">Open</button></p>' +
                                '</div><hr>';  // Add a separator between notes
                        });
                    }

                    // Set the combined notes content in the modal
                    $('#viewSaleDocumentsModal .modal-body').html(notesHtml);

                    // Show the modal
                    $('#viewSaleDocumentsModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.log("Error fetching notes history: " + error);
                    // Optionally, you can display an error message in the modal
                    $('#viewSaleDocumentsModal .modal-body').html('<p>There was an error retrieving the notes. Please try again later.</p>');
                    $('#viewSaleDocumentsModal').modal('show');
                }
            });

            // Add the modal HTML to the page (only once, if not already present)
            if ($('#viewSaleDocumentsModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="viewSaleDocumentsModal" tabindex="-1" aria-labelledby="viewSaleDocumentsModalLabel" >' +
                        '<div class="modal-dialog modal-dialog-scrollable modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="viewSaleDocumentsModalLabel">Sale Documents</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<!-- Notes content will be dynamically inserted here -->' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }
        });
    </script>
@endsection
@endsection

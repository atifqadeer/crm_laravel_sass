@extends('layouts.vertical', ['title' => 'Category Wise Resources List', 'subTitle' => 'Resources'])
@section('style')
<style>
    .dropdown-toggle::after {
        display: none !important;
    }
    table.dataTable.no-footer {
        border-bottom: none !important;
    }

</style>
@php
$jobCategories = \Horsefly\JobCategory::where('is_active', 1)->orderBy('name','asc')->get();
$jobTitles = \Horsefly\JobTitle::where('is_active', 1)->orderBy('name','asc')->get();
@endphp

@endsection
@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-0">
                <div class="row justify-content-between">
                    <div class="col-lg-12">
                        <div class="text-md-end mt-3">
                            <!-- Button Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton4" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i> <span id="showFilterStatus">Interested</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton4">
                                    <a class="dropdown-item status-filter" href="#">Interested</a>
                                    <a class="dropdown-item status-filter" href="#">Not Interested</a>
                                    <a class="dropdown-item status-filter" href="#">Blocked</a>
                                    <a class="dropdown-item status-filter" href="#">Have Nursing Home Exp</a>
                                </div>
                            </div>

                             <!-- Date Range filter -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dateRangeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-calendar-line me-1"></i> <span id="showDateRange">Last 7 Days</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dateRangeDropdown">
                                    <a class="dropdown-item date-range-filter" href="#">Last 7 Days</a>
                                    <a class="dropdown-item date-range-filter" href="#">Last 21 Days</a>
                                    <a class="dropdown-item date-range-filter" href="#">Last 3 Months</a>
                                    <a class="dropdown-item date-range-filter" href="#">Last 6 Months</a>
                                    <a class="dropdown-item date-range-filter" href="#">Last 9 Months</a>
                                    <a class="dropdown-item date-range-filter" href="#">Other</a>
                                </div>
                            </div>
                            <!-- Category Filter Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i> <span id="showFilterCategory">All Category</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                    <a class="dropdown-item category-filter" href="#">All Category</a>
                                    @foreach($jobCategories as $category)
                                        <a class="dropdown-item category-filter" href="#" data-category-id="{{ $category->id }}">{{ $category->name }}</a>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Title Filter Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i> <span id="showFilterTitle">All Titles</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                    <a class="dropdown-item title-filter" href="#">All Titles</a>
                                    @foreach($jobTitles as $title)
                                        <a class="dropdown-item title-filter" href="#" data-title-id="{{ $title->id }}">{{ $title->name }}</a>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Type Filter Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton3" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i> <span id="showFilterType">All Types</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton3">
                                    <a class="dropdown-item type-filter" href="#">All Types</a>
                                    <a class="dropdown-item type-filter" href="#">Specialist</a>
                                    <a class="dropdown-item type-filter" href="#">Regular</a>
                                </div>
                            </div>
                             
                            <!-- Button Dropdown -->
                            {{-- <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-download-line me-1"></i> Export
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                    <a class="dropdown-item" href="{{ route('applicantsExport', ['type' => 'allBlocked']) }}">Export All Data</a>
                                </div>
                            </div> --}}
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
                    </div><!-- end col-->
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="applicants_table" class="table align-middle mb-3">
                        <thead class="bg-light-subtle">
                            <tr>
                                <th><input type="checkbox" id="master-checkbox"></th>
                                <th>Date</th>
                                <th>Sent By</th>
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
    <script>
        $(document).ready(function() {
            // Store the current filter in a variable
            var currentTypeFilter = '';
            var currentFilter = '';
            var currentCategoryFilter = '';
            var currentTitleFilter = '';
            var currentDateRangeFilter = '';

            // Create a loader row and append it to the table before initialization
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="16" class="text-center py-4">
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
                    url: @json(route('getResourcesCategoryWised')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        d.status_filter = currentFilter;  // Send the current filter value as a parameter
                        d.type_filter = currentTypeFilter;  // Send the current filter value as a parameter
                        d.category_filter = currentCategoryFilter;  // Send the current filter value as a parameter
                        d.title_filter = currentTitleFilter;  // Send the current filter value as a parameter
                        d.date_range_filter = currentDateRangeFilter;  // Send the current filter value as a parameter

                        // Clean up search parameter
                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }
                    }
                },
                columns: [
                    { data: "checkbox", orderable:false, searchable:false},
                    { data: 'updated_at', name: 'applicants.updated_at' },
                    { data: 'user_name', name: 'users.name', orderable:false, searchable:false },
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
                    { data: 'customStatus', name: 'customStatus', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                columnDefs: [
                    {
                        targets: 10,  // Column index for 'job_details'
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');  // Center the text in this column
                        }
                    },
                    {
                        targets: 13,  // Column index for 'job_details'
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');  // Center the text in this column
                        }
                    },
                    {
                        targets: 14,  // Column index for 'job_details'
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');  // Center the text in this column
                        }
                    },
                    {
                        targets: 15,  // Column index for 'job_details'
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');  // Center the text in this column
                        }
                    }
                ],
                rowId: function(data) {
                    return 'row_' + data.id; // Assign a unique ID to each row using the 'id' field from the data
                },
                dom: 'flrtip',  // Change the order to 'filter' (f), 'length' (l), 'table' (r), 'pagination' (p), and 'information' (i)
                drawCallback: function(settings) {
                    // Custom pagination HTML
                    var api = this.api();
                    var pagination = $(api.table().container()).find('.dataTables_paginate');
                    pagination.empty();  // Clear existing pagination

                    // Get the current page and total pages
                    var pageInfo = api.page.info();
                    var currentPage = pageInfo.page + 1;  // Page starts at 0, so add 1
                    var totalPages = pageInfo.pages;

                    // Check if there are no records
                    if (pageInfo.recordsTotal === 0) {
                        $('#applicants_table tbody').html('<tr><td colspan="16" class="text-center">Data not found</td></tr>');
                    } else {
                        // Build the custom pagination structure
                        var paginationHtml = `
                            <nav aria-label="Page navigation example">
                                <ul class="pagination pagination-rounded mb-0">
                                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                                        <a class="page-link" href="javascript:void(0);" aria-label="Previous" onclick="movePage('previous')">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>`;

                        for (var i = 1; i <= totalPages; i++) {
                            paginationHtml += `
                                <li class="page-item ${currentPage === i ? 'active' : ''}">
                                    <a class="page-link" href="javascript:void(0);" onclick="movePage(${i})">${i}</a>
                                </li>`;
                        }

                        paginationHtml += `
                                    <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                                        <a class="page-link" href="javascript:void(0);" aria-label="Next" onclick="movePage('next')">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>`;

                        pagination.html(paginationHtml); // Append custom pagination HTML
                    }
                }
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
            // Status filter dropdown handler
            $('.date-range-filter').on('click', function () {
                // Get the clicked text and convert to lowercase
                currentDateRangeFilter = $(this).text().toLowerCase().replace(/\s+/g, '-');

                // Format text for display: capitalize each word (using the original string)
                const formattedText = $(this).text()
                    .toLowerCase()
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                // Update the dropdown display label
                $('#showDateRange').html(formattedText);

                // Optionally, log or use currentDateRangeFilter with hyphens
                console.log('Selected filter:', currentDateRangeFilter);

                // Reload table (assuming it uses currentDateRangeFilter somehow)
                table.ajax.reload();
            });

            // Type filter dropdown handler
            $('.type-filter').on('click', function () {
                currentTypeFilter = $(this).text().toLowerCase();

                // Capitalize each word
                const formattedText = currentTypeFilter
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterType').html(formattedText);
                table.ajax.reload(); // Reload with updated type filter
            });
            
            // Status filter dropdown handler
            $('.category-filter').on('click', function () {
                const categoryName = $(this).text().trim();
                currentCategoryFilter = $(this).data('category-id') ?? ''; // nullish fallback for "All Category"

                const formattedText = categoryName
                    .toLowerCase()
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterCategory').html(formattedText); // Update displayed name
                table.ajax.reload();
            });

            $('.title-filter').on('click', function () {
                const titleName = $(this).text().trim();
                currentTitleFilter = $(this).data('title-id') ?? ''; // nullish fallback for "All Titles"

                const formattedText = titleName
                    .toLowerCase()
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterTitle').html(formattedText); // Update displayed name
                table.ajax.reload();
            });

        });

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
        
        // Function to show the notes modal
        function showNotesModal(applicantID, notes, applicantName, applicantPostcode) {
            const modalID = 'showNotesModal_' + applicantID;

            // If modal doesn't exist, append it to the body
            if ($('#' + modalID).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalID}" tabindex="-1" aria-labelledby="${modalID}Label" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalID}Label">Applicant Notes</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Notes content will be dynamically inserted here -->
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Insert the content dynamically
            $('#' + modalID + ' .modal-body').html(`
                Applicant Name: <strong>${applicantName}</strong><br>
                Postcode: <strong>${applicantPostcode}</strong><br>
                Notes Detail: <p style="line-height: 1.7;">${notes}</p>
            `);

            // Show the modal
            $('#' + modalID).modal('show');
        }


        // Function to show the notes modal
        function viewNotesHistory(id) {
            const modalID = 'viewNotesHistoryModal_' + id;

            // Add modal to the DOM if it doesn't already exist
            if ($('#' + modalID).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalID}" tabindex="-1" aria-labelledby="${modalID}Label" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalID}Label">Applicant Notes History</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body modal-body-text-left">
                                    <div class="text-center py-3">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Show the modal immediately with loader
            $('#' + modalID).modal('show');

            // Fetch notes via AJAX
            $.ajax({
                url: '{{ route("getModuleNotesHistory") }}',
                type: 'GET',
                data: {
                    id: id,
                    module: 'Horsefly\\Applicant'
                },
                success: function (response) {
                    let notesHtml = '';

                    if (response.data.length === 0) {
                        notesHtml = '<p class="text-center">No record found.</p>';
                    } else {
                        response.data.forEach(function (note) {
                            const notes = note.details;
                            const created = moment(note.created_at).format('DD MMM YYYY, h:mmA');
                            const status = note.status;
                            const statusClass = (status == 1) ? 'bg-success' : 'bg-dark';
                            const statusText = (status == 1) ? 'Active' : 'Inactive';

                            notesHtml += `
                                <div>
                                    <p><strong>Dated:</strong> ${created} &nbsp;&nbsp;
                                    <span class="badge ${statusClass}">${statusText}</span></p>
                                    <p><strong>Notes Detail:</strong></p>
                                    <p>${notes}</p>
                                </div><hr>
                            `;
                        });
                    }

                    $('#' + modalID + ' .modal-body').html(notesHtml);
                },
                error: function (xhr, status, error) {
                    console.log("Error fetching notes history: " + error);
                    $('#' + modalID + ' .modal-body').html('<p class="text-danger text-center">Error loading notes. Please try again later.</p>');
                }
            });
        }

        // Function to show the notes modal
        function addShortNotesModal(applicantID) {
            const modalID = 'shortNotesModal_' + applicantID;

            // If modal doesn't exist yet, add it to the page
            if ($('#' + modalID).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalID}" tabindex="-1" aria-labelledby="${modalID}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalID}Label">Add Notes</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="shortNotesForm_${applicantID}">
                                        <div class="mb-3">
                                            <label for="detailsTextarea_${applicantID}" class="form-label">Details</label>
                                            <textarea class="form-control" id="detailsTextarea_${applicantID}" rows="4" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="reasonDropdown_${applicantID}" class="form-label">Reason</label>
                                            <select class="form-select" id="reasonDropdown_${applicantID}" required>
                                                <option value="" disabled selected>Select Reason</option>
                                                <option value="casual">Casual Notes</option>
                                                <option value="blocked">Blocked Notes</option>
                                                <option value="not_interested">Temp Not Interested Notes</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary saveShortNotesButton" data-id="${applicantID}">
                                        Save
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Reset form and validation when modal opens
            $('#shortNotesForm_' + applicantID)[0].reset();
            $('#detailsTextarea_' + applicantID).removeClass('is-valid is-invalid').next('.invalid-feedback').remove();
            $('#reasonDropdown_' + applicantID).removeClass('is-valid is-invalid').next('.invalid-feedback').remove();

            // Show the modal
            $('#' + modalID).modal('show');

            // Attach save button click event
            $('#' + modalID + ' .saveShortNotesButton').off('click').on('click', function () {
                const notes = $('#detailsTextarea_' + applicantID).val();
                const reason = $('#reasonDropdown_' + applicantID).val();

                if (!notes || !reason) {
                    if (!notes) {
                        $('#detailsTextarea_' + applicantID).addClass('is-invalid')
                            .after('<div class="invalid-feedback">Please provide details.</div>');
                    }
                    if (!reason) {
                        $('#reasonDropdown_' + applicantID).addClass('is-invalid')
                            .after('<div class="invalid-feedback">Please select a reason.</div>');
                    }

                    // Remove validation dynamically
                    $('#detailsTextarea_' + applicantID).on('input', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid')
                                .next('.invalid-feedback').remove();
                        }
                    });

                    $('#reasonDropdown_' + applicantID).on('change', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid')
                                .next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // Clear validation messages
                $('#detailsTextarea_' + applicantID).removeClass('is-invalid is-valid');
                $('#reasonDropdown_' + applicantID).removeClass('is-invalid is-valid');

                // Send via AJAX
                $.ajax({
                    url: '{{ route("storeShortNotes") }}',
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        details: notes,
                        reason: reason,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        toastr.success('Notes saved successfully!');
                        $('#' + modalID).modal('hide');
                        $('#shortNotesForm_' + applicantID)[0].reset();
                        $('#applicants_table').DataTable().ajax.reload();
                    },
                    error: function () {
                        alert('An error occurred while saving notes.');
                    }
                });
            });
        }

        function handleCheckboxClick(currentCheckboxId, otherCheckboxId) {
            var currentCheckbox = document.getElementById(currentCheckboxId);
            var otherCheckbox = document.getElementById(otherCheckboxId);

            if (currentCheckbox.checked) {
                // If current checkbox is checked, uncheck and disable the other checkbox
                otherCheckbox.checked = false;
                otherCheckbox.disabled = true;
            } else {
                // If current checkbox is unchecked, enable the other checkbox
                otherCheckbox.disabled = false;
            }
        }

        function showDetailsModal(applicantId, name, email, secondaryEmail, postcode, landline, phone, jobTitle, jobCategory, jobSource, posted_date, status) {
            const modalID = 'showDetailsModal_' + applicantId;

            // Add the modal HTML to the page only once
            if ($('#' + modalID).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalID}" tabindex="-1" aria-labelledby="${modalID}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalID}Label">Applicant Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Content will be injected below -->
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Now inject content
            $('#' + modalID + ' .modal-body').html(
                '<table class="table table-bordered">' +
                    '<tr><th>Applicant ID</th><td>' + applicantId + '</td></tr>' +
                    '<tr><th>Created On</th><td>' + posted_date + '</td></tr>' +
                    '<tr><th>Name</th><td>' + name + '</td></tr>' +
                    '<tr><th>Phone</th><td>' + phone + '</td></tr>' +
                    '<tr><th>Landline</th><td>' + landline + '</td></tr>' +
                    '<tr><th>Postcode</th><td>' + postcode + '</td></tr>' +
                    '<tr><th>Email (Primary)</th><td>' + email + '</td></tr>' +
                    '<tr><th>Email (Secondary)</th><td>' + secondaryEmail + '</td></tr>' +
                    '<tr><th>Job Category</th><td>' + jobCategory + '</td></tr>' +
                    '<tr><th>Job Title</th><td>' + jobTitle + '</td></tr>' +
                    '<tr><th>Job Source</th><td>' + jobSource + '</td></tr>' +
                    '<tr><th>Status</th><td>' + status + '</td></tr>' +
                '</table>'
            );

            // Then show the modal
            $('#' + modalID).modal('show');
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

        // Function to change sale status modal
        function changeStatusModal(applicantID, status) {
            // Add the modal HTML to the page (only once, if not already present)
            if ($('#changeStatusModal').length === 0) {
                $('body').append(
                    `<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="changeStatusModalLabel">Change Applicant Status</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="changeStatusForm">
                                        <div class="mb-3">
                                            <label for="detailsTextarea" class="form-label">Details</label>
                                            <textarea class="form-control" id="detailsTextarea" rows="4" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="statusDropdown" class="form-label">Status</label>
                                            <select class="form-select" id="statusDropdown" required>
                                                <option value="" disabled selected>Select Status</option>
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="saveNotesButton">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>`
                );
            }

            // Show the modal
            $('#changeStatusModal').modal('show');

            $('#statusDropdown').val(status); // this should now work

            // Handle the save button click
            $('#saveNotesButton').off('click').on('click', function () {
                const notes = $('#detailsTextarea').val();
                const selectedStatus = $('#statusDropdown').val();

                let hasError = false;

                if (!notes) {
                    $('#detailsTextarea').addClass('is-invalid');
                    if ($('#detailsTextarea').next('.invalid-feedback').length === 0) {
                        $('#detailsTextarea').after('<div class="invalid-feedback">Please provide details.</div>');
                    }
                    hasError = true;
                }

                if (!selectedStatus) {
                    $('#statusDropdown').addClass('is-invalid');
                    if ($('#statusDropdown').next('.invalid-feedback').length === 0) {
                        $('#statusDropdown').after('<div class="invalid-feedback">Please select a status.</div>');
                    }
                    hasError = true;
                }

                if (hasError) {
                    $('#detailsTextarea').on('input', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    $('#statusDropdown').on('change', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // AJAX request
                $.ajax({
                    url: '{{ route("changeStatus") }}',
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        details: notes,
                        status: selectedStatus,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        toastr.success('Applicant status changed successfully!');
                        $('#changeStatusModal').modal('hide');
                        $('#changeStatusForm')[0].reset();
                        $('#detailsTextarea, #statusDropdown').removeClass('is-valid is-invalid');
                        $('#detailsTextarea, #statusDropdown').next('.invalid-feedback').remove();

                        $('#applicants_table').DataTable().ajax.reload();
                    },
                    error: function (xhr) {
                        toastr.error('An error occurred while saving applicant status changed.');
                    }
                });
            });
        }

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
        
        // Add a listener to the "Select All" button for additional actions
        $('#submitSelectedButton').on('click', function() {
            var selectedIds = [];

            // Get selected IDs
            $('.applicant_checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
             Swal.fire({
                title: 'Are you sure?',
                text: 'These applicants will be unblocked. Are you sure you want to continue?',
                icon: 'warning',
                showCancelButton: true,
                customClass: {
                    confirmButton: 'btn bg-danger text-white me-2 mt-2',
                    cancelButton: 'btn btn-dark mt-2'
                },
                confirmButtonText: 'Yes, Continue!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'revertBlockedApplicant', // Update the URL to match your route
                        type: 'post',
                        data: { 
                            ids: selectedIds,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {

                                // Display a success message
                                toastr.success(response.message);

                                // Reload the DataTable
                                $('#applicants_table').DataTable().ajax.reload();
                            } else {
                                // Display an error message
                                toastr.error(response.message);
                            }
                        },
                        error: function(error) {
                            // Handle other errors (e.g., network issues)
                            toastr.error('Error: ' + error.statusText);
                        }
                    });
                }
            });
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
                        $('#applicants_table').DataTable().ajax.reload(); // Reload the DataTable
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
                        $('#applicants_table').DataTable().ajax.reload(); // Reload the DataTable
						
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

        let applicantId = null; // Store applicant ID

        function triggerFileInput(id) {
            applicantId = id;
            document.getElementById('fileInput').click();
        }

        function uploadFile() {
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];

            if (!file) {
                toastr.error('No file selected.');
                return;
            }

            // Validate file type
            const allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (!allowedTypes.includes(file.type)) {
                toastr.error('Only PDF, DOC, or DOCX files are allowed.');
                return;
            }

            if (applicantId) {
                const formData = new FormData();
                formData.append('resume', file);
                formData.append('applicant_id', applicantId);
                formData.append('_token', '{{ csrf_token() }}');

                fetch('{{ route("applicants.uploadCv") }}', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success('File uploaded successfully');
                        $('#applicants_table').DataTable().ajax.reload();
                    } else {
                        toastr.error('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    toastr.error('Error uploading file: ' + error.message);
                });
            } else {
                toastr.error('Applicant ID is missing.');
            }
        }

    </script>
    
@endsection
@endsection                        
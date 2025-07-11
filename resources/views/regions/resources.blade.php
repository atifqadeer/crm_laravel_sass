@extends('layouts.vertical', ['title' => 'Region Wise Resources List', 'subTitle' => 'Regions'])
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
$regions = \Horsefly\Region::orderBy('name','asc')->get();
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
                            <!-- Regions Filter Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton10" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i> <span id="showFilterRegion">All Regions</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton10">
                                    <a class="dropdown-item region-filter" href="#">All Regions</a>
                                    @foreach($regions as $region)
                                        <a class="dropdown-item region-filter" href="#" data-region-id="{{ $region->id }}">{{ $region->name }}</a>
                                    @endforeach
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
                                    <a class="dropdown-item" href="{{ route('applicantsExport', ['type' => 'all']) }}">Export All Data</a>
                                    <a class="dropdown-item" href="{{ route('applicantsExport', ['type' => 'emails']) }}">Export Emails</a>
                                    <a class="dropdown-item" href="{{ route('applicantsExport', ['type' => 'noLatLong']) }}">Export no LAT & LONG</a>
                                </div>
                            </div> --}}
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
                                <th>#</th>
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

<!-- Modal -->
<div class="modal fade" id="viewApplicantModal" tabindex="-1" aria-labelledby="viewApplicantModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="viewApplicantModalLabel">Applicant Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <!-- Content will be dynamically loaded here -->
            <div id="applicantDetails"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            // Store the current filter in a variable
            var regionFilter = '';
            var currentTypeFilter = '';
            var currentCategoryFilter = '';
            var currentTitleFilter = '';

            // Create a loader row and append it to the table before initialization
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="14" class="text-center py-4">
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
                    url: @json(route('getApplicantsByRegions')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        // Add the current filter to the request parameters
                        d.region_filter = regionFilter;  // Send the current filter value as a parameter
                        d.type_filter = currentTypeFilter;  // Send the current filter value as a parameter
                        d.category_filter = currentCategoryFilter;  // Send the current filter value as a parameter
                        d.title_filter = currentTitleFilter;  // Send the current filter value as a parameter

                        // Clean up search parameter
                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'updated_at', name: 'cv_notes.updated_at' },
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
                        $('#applicants_table tbody').html('<tr><td colspan="14" class="text-center">Data not found</td></tr>');
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
            $('.region-filter').on('click', function () {
                const regionName = $(this).text().trim();
                regionFilter = $(this).data('region-id') ?? ''; // nullish fallback for "All Category"

                const formattedText = regionName
                    .toLowerCase()
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterRegion').html(formattedText); // Update displayed name
                table.ajax.reload();
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

        // Function to show the notes modal
        function viewNotesHistory(id) {
            // Make an AJAX call to retrieve notes history data
            $.ajax({
                url: '{{ route("getModuleNotesHistory") }}', // Your backend URL to fetch notes history, replace it with your actual URL
                type: 'GET',
                data: { 
                    id: id,
                    module: 'Horsefly\\Applicant'

                }, // Pass the id to your server to fetch the corresponding applicant's notes
                success: function(response) {
                    var notesHtml = '';  // This will hold the combined HTML for all notes

                    // Check if the response data is empty
                    if (response.data.length === 0) {
                        notesHtml = '<p>No record found.</p>';
                    } else {
                        // Loop through the response array (assuming it's an array of notes)
                        response.data.forEach(function(note) {
                            var notes = note.details;
                            var created = moment(note.created_at).format('DD MMM YYYY, h:mmA');
                            var status = note.status;

                            // Determine the badge class based on the status value
                            var statusClass = (status == 1) ? 'bg-success' : 'bg-dark'; // 'bg-success' for active, 'bg-dark' for inactive
                            var statusText = (status == 1) ? 'Active' : 'Inactive';

                            // Append each note's details to the notesHtml string
                            notesHtml += 
                                '<div class="note-entry">' +
                                    '<p><strong>Dated:</strong> ' + created + '&nbsp;&nbsp; <span class="badge ' + statusClass + '">' + statusText + '</span></p>' +
                                    '<p><strong>Notes Detail:</strong> <br>' + notes + '</p>' +
                                '</div><hr>';  // Add a separator between notes
                        });
                    }

                    // Set the combined notes content in the modal
                    $('#viewNotesHistoryModal .modal-body').html(notesHtml);

                    // Show the modal
                    $('#viewNotesHistoryModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.log("Error fetching notes history: " + error);
                    // Optionally, you can display an error message in the modal
                    $('#viewNotesHistoryModal .modal-body').html('<p>There was an error retrieving the notes. Please try again later.</p>');
                    $('#viewNotesHistoryModal').modal('show');
                }
            });

            // Add the modal HTML to the page (only once, if not already present)
            if ($('#viewNotesHistoryModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="viewNotesHistoryModal" tabindex="-1" aria-labelledby="viewNotesHistoryModalLabel" aria-hidden="true">' +
                        '<div class="modal-dialog modal-dialog-scrollable modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="viewNotesHistoryModalLabel">Applicant Notes History</h5>' +
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
                        alert('An error occurred while saving notes.');
                    }
                });
            });
        }

        // Function to show the notes modal
        function addNotesModal(applicantID) {
             // Open the modal and reset the form inside it
            $('#notesModal').on('show.bs.modal', function () {
                // Reset the form inside the modal
                $(this).find('form')[0].reset();
            });

            // Add the modal HTML to the page (only once, if not already present)
            if ($('#notesModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="notesModalLabel">Add Notes</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<form class="form-horizontal" id="note_form' + applicantID + '">' +
                                        '<input type="hidden" name="_token" value="">' +
                                        '<input type="hidden" name="request_from_applicants" value="1">'+
                                        '<input type="hidden" name="module" value="Applicant">' +
                                        '<input type="hidden" name="module_key" value="'+ applicantID +'">'+

                                        '<div id="note_alert'+ applicantID +'"></div>' +
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
                                                    '<input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="by_walk" value="By Walk"><label class="form-check-label" for="by_walk">By Walk</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="cycle" value="Cycle">' +
                                                    '<label class="form-check-label" for="cycle">Cycle</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="car" value="Car">' +
                                                    '<label class="form-check-label" for="car">Car</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="public_transport" value="Public Transport">' +
                                                    '<label class="form-check-label" for="public_transport">Public Transport</label>' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="form-group row">' +
                                            '<label class="col-form-label col-sm-3"><strong style="font-size:18px">6.</strong> Shift Pattern</label>' +
                                            '<div class="col-sm-9 d-flex align-items-center">' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="day" value="Day">' +
                                                    '<label class="form-check-label" for="day">Day</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="night" value="Night">' +
                                                    '<label class="form-check-label" for="night">Night</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="full_time" value="Full Time">' +
                                                    '<label class="form-check-label" for="full_time">Full Time</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="part_time" value="Part Time">' +
                                                    '<label class="form-check-label" for="part_time">Part Time</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="twenty_four_hours" value="24 hours">' +
                                                    '<label class="form-check-label" for="twenty_four_hours">24 Hours</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="day_night" value="Day/Night">' +
                                                    '<label class="form-check-label" for="day_night">Day/Night</label>' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="form-group row">'+
                                            '<label class="col-form-label col-sm-3"><strong style="font-size:18px">7.</strong> Visa Status</label>'+
                                            '<div class="col-sm-9 d-flex align-items-center">'+
                                                '<div class="d-flex">'+
                                                    '<div class="form-check form-check-inline">'+
                                                        '<input type="radio" name="visa_status" id="british" class="form-check-input mt-0" value="British">'+
                                                        '<label class="form-check-label" for="british">British</label>'+
                                                    '</div>'+
                                                    '<div class="form-check form-check-inline ml-3">'+
                                                        '<input type="radio" name="visa_status" id="required_sponsorship" class="form-check-input mt-0" value="Required Sponsorship">'+
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
                                    '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>' +
                                    '<button type="submit" data-note_key="214232" class="btn btn-primary" form="note_form' + applicantID + '">Save</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Show the modal
            $('#notesModal').modal('show');

            // Handle the save button click for form submission
             $('#note_form' + applicantID).off('submit').on('submit', function(event) {
                event.preventDefault(); // Prevent the default form submission

                const form = $(this);
                const formData = form.serialize(); // Serialize the form data

                // Add the CSRF token to the form data
                const token = '{{ csrf_token() }}';
                const dataWithToken = formData + '&_token=' + token;

                $.ajax({
                    url: '{{ route("moduleNotes.store") }}', // Replace with your endpoint
                    type: 'POST',
                    data: dataWithToken, // Send the serialized data with the CSRF token
                    success: function(response) {
                        toastr.success('Notes saved successfully!');
                        $('#notesModal').modal('hide'); // Close the modal
                        $('#applicants_table').DataTable().ajax.reload(); // Reload the DataTable
                    },
                    error: function(xhr) {
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

        function showDetailsModal(applicantId, name, email, secondaryEmail, postcode, landline, phone, jobTitle, jobCategory, jobSource, status) {
            // Set the notes content in the modal as a table
            $('#showDetailsModal .modal-body').html(
                '<table class="table table-bordered">' +
                    '<tr>' +
                        '<th>Applicant ID</th>' +
                        '<td>' + applicantId + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Name</th>' +
                        '<td>' + name + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Phone</th>' +
                        '<td>' + phone + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Landline</th>' +
                        '<td>' + landline + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Postcode</th>' +
                        '<td>' + postcode + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Email (Primary)</th>' +
                        '<td>' + email + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Email (Secondary)</th>' +
                        '<td>' + secondaryEmail + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Job Category</th>' +
                        '<td>' + jobCategory + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Job Title</th>' +
                        '<td>' + jobTitle + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Job Source</th>' +
                        '<td>' + jobSource + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Status</th>' +
                        '<td>' + status + '</td>' +
                    '</tr>' +
                '</table>'
            );

            // Show the modal
            $('#showDetailsModal').modal('show');

            // Add the modal HTML to the page (only once, if not already present)
            if ($('#showDetailsModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="showDetailsModal" tabindex="-1" aria-labelledby="showDetailsModalLabel" aria-hidden="true">' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="showDetailsModalLabel">Applicant Details</h5>' +
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

        let applicantId = null; // Store applicant ID

        function triggerFileInput(id) {
            // Store the applicant ID when the button is clicked
            applicantId = id;
            
            // Trigger the file input click event
            document.getElementById('fileInput').click();
        }

        function uploadFile() {
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0]; // Get the selected file

            if (file && applicantId) {
                // Create a FormData object to send the file along with the applicant ID
                const formData = new FormData();
                formData.append('resume', file);
                formData.append('applicant_id', applicantId); // Append applicant ID

                // Include CSRF token if you're using Laravel or any framework that requires CSRF protection
                formData.append('_token', '{{ csrf_token() }}');  // CSRF token

                // You can send the file to the server using an AJAX request or any method you prefer
                // Example using Fetch API
                fetch('{{ route("applicants.uploadCv") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                    // If needed, add other headers here (like Authorization headers if you're using token-based auth)
                    //'Authorization': 'Bearer ' + YOUR_TOKEN // Uncomment if needed
                    }
                })
                .then(response => response.json()) // Assuming the server returns JSON
                .then(data => {
                    if (data.success) {
                        toastr.success('File uploaded successfully');
                        $('#applicants_table').DataTable().ajax.reload(); // Reload the DataTable
                    } else {
                        toastr.error('Error:', data.message);
                    }
                })
                .catch(error => {
                    toastr.error('Error uploading file:', error);
                });
            } else {
                toastr.error('No file selected or applicant ID missing.');
            }
        }
    </script>
    
@endsection
@endsection                        
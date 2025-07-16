@extends('layouts.vertical', ['title' => 'Indirect Resources List', 'subTitle' => 'Resources'])
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
                             <!-- Date Range filter -->
                            <div class="d-inline">
                                <input type="text" id="dateRangePicker" class="form-control d-inline-block" style="width: 220px; display: inline-block;" placeholder="Select date range" readonly />
                                <button class="btn btn-outline-primary my-1" type="button" id="clearDateRange" title="Clear Date Range">
                                    <i class="ri-close-line"></i>
                                </button>
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
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton4" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i> <span id="showFilterStatus">All</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton4">
                                    <a class="dropdown-item status-filter" href="#">All</a>
                                    <a class="dropdown-item status-filter" href="#">Interested</a>
                                    <a class="dropdown-item status-filter" href="#">Not Interested</a>
                                </div>
                            </div>
                            <!-- Add Updated Sales Filter Button -->
                            <button class="btn btn-success my-1" type="button" id="addUpdatedSalesFilter">
                                Add/Remove Updated Sales Filter
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
   
    <!-- Add daterangepicker CSS/JS (place before your custom script section) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        let isButtonClicked = false;

        $(function () {
            // Get today's date in moment.js format
            const today = moment().startOf('day');

            // Initialize the date range picker with today's date as default
            $('#dateRangePicker').daterangepicker({
                startDate: today,
                endDate: today,
                autoUpdateInput: true,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                }
            });

            // Set default value in input and filter variable
            $('#dateRangePicker').val(today.format('YYYY-MM-DD') + ' to ' + today.format('YYYY-MM-DD'));
            window.currentDateRangeFilter = today.format('YYYY-MM-DD') + '|' + today.format('YYYY-MM-DD');
            $('#showDateRange').html($('#dateRangePicker').val());

            // On apply
            $('#dateRangePicker').on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
                window.currentDateRangeFilter = picker.startDate.format('YYYY-MM-DD') + '|' + picker.endDate.format('YYYY-MM-DD');
                $('#showDateRange').html($(this).val());
                $('#applicants_table').DataTable().ajax.reload();
            });

            // On cancel
            $('#dateRangePicker').on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
                window.currentDateRangeFilter = '';
                $('#showDateRange').html('All Data');
                $('#applicants_table').DataTable().ajax.reload();
            });

            // Clear button
            $('#clearDateRange').on('click', function () {
                $('#dateRangePicker').val('');
                window.currentDateRangeFilter = '';
                $('#showDateRange').html('All Data');
                $('#applicants_table').DataTable().ajax.reload();
            });

            // Toggle filter button
            $('#addUpdatedSalesFilter').on('click', function () {
                isButtonClicked = !isButtonClicked;
                $('#applicants_table').DataTable().ajax.reload();
            });
        });

        $(document).ready(function() {
            // Store the current filter in a variable
            var currentFilter = '';
            var currentTypeFilter = '';
            var currentCategoryFilter = '';
            var currentTitleFilter = '';

            // Create a loader row and append it to the table before initialization
            const button = document.getElementById("addUpdatedSalesFilter");
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
                    url: @json(route('getResourcesIndirectApplicants')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        // Add the current filter to the request parameters
                        d.type_filter = currentTypeFilter;  // Send the current filter value as a parameter
                        d.date_range_filter = window.currentDateRangeFilter;  // Send the current filter value as a parameter
                        d.category_filter = currentCategoryFilter;  // Send the current filter value as a parameter
                        d.title_filter = currentTitleFilter;  // Send the current filter value as a parameter
                        d.status_filter = currentFilter;

                        // Conditionally append the parameter if the button was clicked
                        if (isButtonClicked) {
                            // Change the button color when clicked
                            button.style.backgroundColor = '#358b57';  // You can customize this color
                            d.updated_sales_filter = true;  // Add the parameter when the button is clicked
                        } else {
                            // Reset the button color when unclicked
                            button.style.backgroundColor = '';  // Reset to the original color
                            delete d.updated_sales_filter;  // Remove the parameter when the button is unclicked
                        }

                        // Clean up search parameter
                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'updated_at', name: 'applicants.updated_at' },
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
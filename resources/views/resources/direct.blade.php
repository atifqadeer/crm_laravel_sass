@extends('layouts.vertical', ['title' => 'Direct Sales Resources List', 'subTitle' => 'Resources'])
@section('style')
<style>
    .dropdown-toggle::after {
        display: none !important;
    }
    table.dataTable.no-footer {
        border-bottom: none !important;
    }
</style>
@endsection
@section('content')
@php
$jobCategories = \Horsefly\JobCategory::where('is_active', 1)->orderBy('name','asc')->get();
$jobTitles = \Horsefly\JobTitle::where('is_active', 1)->orderBy('name','asc')->get();
$offices = \Horsefly\Office::where('status', 1)->orderBy('office_name','asc')->get();
$users = \Horsefly\User::where('is_active', 1)->orderBy('name','asc')->get();
@endphp
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
                            <!-- user Filter Dropdown -->
                             <!-- head office Filter Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton6" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i> <span id="showFilterOffice">All Head Office</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton6">
                                    <a class="dropdown-item office-filter" href="#">All Head Office</a>
                                    @foreach($offices as $office)
                                        <a class="dropdown-item office-filter" href="#" data-office-id="{{ $office->id }}">{{ ucwords($office->office_name) }}</a>
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
                                        <a class="dropdown-item category-filter" href="#" data-category-id="{{ $category->id }}">{{ ucwords($category->name) }}</a>
                                    @endforeach
                                </div>
                            </div>
                            <!-- Type Filter Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton4" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i> <span id="showFilterType">All Types</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton4">
                                    <a class="dropdown-item type-filter" href="#">All Types</a>
                                    <a class="dropdown-item type-filter" href="#">Specialist</a>
                                    <a class="dropdown-item type-filter" href="#">Regular</a>
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
                                        <a class="dropdown-item title-filter" href="#" data-title-id="{{ $title->id }}">{{ strtoupper($title->name) }}</a>
                                    @endforeach
                                </div>
                            </div>
                             <!-- cv limit Filter Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton7" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i> <span id="showFilterCvLimit">All Count</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton7">
                                    <a class="dropdown-item cv-limit-filter" href="#">All Count</a>
                                    <a class="dropdown-item cv-limit-filter" href="#">Zero</a>
                                    <a class="dropdown-item cv-limit-filter" href="#">Not Max</a>
                                    <a class="dropdown-item cv-limit-filter" href="#">Max</a>
                                </div>
                            </div>
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
                    <table id="sales_table" class="table align-middle mb-3">
                        <thead class="bg-light-subtle">
                            <tr>
                                <th>#</th>
                                <th>Updated Date</th>
                                <th>Head Office</th>
                                <th>Unit Name</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>PostCode</th>
                                <th>Experience</th>
                                <th>Qualification</th>
                                <th>Salary</th>
                                <th>CV Limit</th>
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
        $(function() {
            // Initialize the date range picker
            $('#dateRangePicker').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });

            // When a date range is selected
            $('#dateRangePicker').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
                // Set the filter variable and reload DataTable
                window.currentDateRangeFilter = picker.startDate.format('YYYY-MM-DD') + '|' + picker.endDate.format('YYYY-MM-DD');
                $('#showDateRange').html($(this).val());
                $('#sales_table').DataTable().ajax.reload();
            });

            // When the date range is cleared
            $('#dateRangePicker').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                window.currentDateRangeFilter = '';
                $('#showDateRange').html('All Data');
                $('#sales_table').DataTable().ajax.reload();
            });

            // Clear button
            $('#clearDateRange').on('click', function() {
                $('#dateRangePicker').val('');
                window.currentDateRangeFilter = '';
                $('#showDateRange').html('All Data');
                $('#sales_table').DataTable().ajax.reload();
            });
        });

        $(document).ready(function() {
            // Store the current filter in a variable
            var currentFilter = '';
            var currentTypeFilter = '';
            var currentCategoryFilter = '';
            var currentUserFilter = '';
            var currentTitleFilter = '';
            var currentOfficeFilter = '';
            var currentFilterCvLimit = '';

            // Create a loader row and append it to the table before initialization
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="14" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td>`;

            // Append the loader row to the table's tbody
            $('#sales_table tbody').append(loadingRow);

            // Initialize DataTable with server-side processing
            var table = $('#sales_table').DataTable({
                processing: false,  // Disable default processing state
                serverSide: true,  // Enables server-side processing
                ajax: {
                    url: @json(route('getResourcesDirectSales')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        // Add the current filter to the request parameters
                        d.type_filter = currentTypeFilter;  // Send the current filter value as a parameter
                        d.date_range_filter = window.currentDateRangeFilter;  // Send the current filter value as a parameter
                        d.category_filter = currentCategoryFilter;  // Send the current filter value as a parameter
                        d.title_filter = currentTitleFilter;  // Send the current filter value as a parameter
                        d.office_filter = currentOfficeFilter;  // Send the current filter value as a parameter
                        d.user_filter = currentUserFilter;  // Send the current filter value as a parameter
                        d.cv_limit_filter = currentFilterCvLimit;  // Send the current filter value as a parameter
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'updated_at', name: 'sales.updated_at' },
                    { data: 'office_name', name: 'offices.office_name'},
                    { data: 'unit_name', name: 'units.unit_name'  },
                    { data: 'job_title', name: 'job_titles.name' },
                    { data: 'job_category', name: 'job_categories.name' },
                    { data: 'sale_postcode', name: 'sales.sale_postcode' },
                    { data: 'experience', name: 'sales.experience' },
                    { data: 'qualification', name: 'sales.qualification' },
                    { data: 'salary', name: 'sales.salary' },
                    { data: 'cv_limit', name: 'sales.cv_limit' },
                    { data: 'action', name: 'action', orderable: false }
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
                        $('#sales_table tbody').html('<tr><td colspan="14" class="text-center">Data not found</td></tr>');
                    } else {
                        // Build the custom pagination structure
                        var paginationHtml = `
                            <nav aria-label="Page navigation example">
                                <ul class="pagination pagination-rounded mb-0">
                                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                                        <a class="page-link" href="javascript:void(0);" aria-label="Previous" onclick="movePage('previous')">
                                            <span >&laquo;</span>
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
                                            <span >&raquo;</span>
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
            // cv limit filter dropdown handler
            $('.cv-limit-filter').on('click', function () {
                currentFilterCvLimit = $(this).text().toLowerCase();

                // Capitalize each word
                const formattedText = currentFilterCvLimit
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterCvLimit').html(formattedText);
                table.ajax.reload(); // Reload with updated status filter
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
            // Status filter dropdown handler
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
            // Status filter dropdown handler
            $('.user-filter').on('click', function () {
                const userName = $(this).text().trim();
                currentUserFilter = $(this).data('user-id') ?? ''; // nullish fallback for "All Category"

                const formattedText = userName
                    .toLowerCase()
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterUser').html(formattedText); // Update displayed name
                table.ajax.reload();
            });
            // Status filter dropdown handler
            $('.office-filter').on('click', function () {
                const officeName = $(this).text().trim();
                currentOfficeFilter = $(this).data('office-id') ?? ''; // nullish fallback for "All Category"

                const formattedText = officeName
                    .toLowerCase()
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterOffice').html(formattedText); // Update displayed name
                table.ajax.reload();
            });
             // Handle the DataTable search
            $('#sales_table_filter input').on('keyup', function() {
                table.search(this.value).draw(); // Manually trigger search
            });
        });

        // Function to move the page forward or backward
        function movePage(page) {
            var table = $('#sales_table').DataTable();
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
        function showNotesModal(notes, unitName, unitPostcode) {
            // Set the notes content in the modal with proper line breaks using HTML
            $('#showNotesModal .modal-body').html(
                'Unit Name: <strong>' + unitName + '</strong><br>' +
                'Postcode: <strong>' + unitPostcode + '</strong><br>' +
                'Notes Detail: <p>' + notes + '</p>'
            );

            // Show the modal
            $('#showNotesModal').modal('show');

            // Add the modal HTML to the page (only once, if not already present)
            if ($('#showNotesModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="showNotesModal" tabindex="-1" aria-labelledby="showNotesModalLabel" >' +
                        '<div class="modal-dialog modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="showNotesModalLabel">Sale Notes</h5>' +
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
        function addNotesModal(saleID) {
            // Add the modal HTML to the page (only once, if not already present)
            if ($('#notesModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" >' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="notesModalLabel">Add Notes</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<form id="notesForm">' +
                                        '<div class="mb-3">' +
                                            '<label for="detailsTextarea" class="form-label">Details</label>' +
                                            '<textarea class="form-control" id="detailsTextarea" rows="4" required></textarea>' +
                                        '</div>' +
                                    '</form>' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>' +
                                    '<button type="button" class="btn btn-primary" id="saveNotesButton">'+
                                        'Save</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Show the modal
            $('#notesModal').modal('show');

            // Handle the save button click
            $('#saveNotesButton').off('click').on('click', function() {
                const notes = $('#detailsTextarea').val();

                if (!notes) {
                    if (!notes) {
                        $('#detailsTextarea').addClass('is-invalid');
                        if ($('#detailsTextarea').next('.invalid-feedback').length === 0) {
                            $('#detailsTextarea').after('<div class="invalid-feedback">Please provide details.</div>');
                        }
                    }
                
                    // Add event listeners to remove validation errors dynamically
                    $('#detailsTextarea').on('input', function() {
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

                // Send the data via AJAX
                $.ajax({
                    url: '{{ route("storeSaleNotes") }}', // Replace with your endpoint
                    type: 'POST',
                    data: {
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}' // Directly include token in data
                    },
                    success: function(response) {
                        toastr.success('Notes saved successfully!');

                        $('#notesModal').modal('hide'); // Close the modal
                        $('#notesForm')[0].reset(); // Clear the form
                        $('#detailsTextarea').removeClass('is-valid'); // Remove valid class
                        $('#detailsTextarea').next('.invalid-feedback').remove(); // Remove error message
                        
                        $('#sales_table').DataTable().ajax.reload(); // Reload the DataTable
                    },
                    error: function(xhr) {
                        toastr.error('An error occurred while saving notes.');
                    }
                });
            });
        }
        
        // Function to change sale status modal
        function changeSaleStatusModal(saleID, status) {
            // Add the modal HTML to the page (only once, if not already present)
            if ($('#changeSaleStatusModal').length === 0) {
                $('body').append(
                    `<div class="modal fade" id="changeSaleStatusModal" tabindex="-1" aria-labelledby="changeSaleStatusModalLabel" >
                        <div class="modal-dialog modal-lg modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="changeSaleStatusModalLabel">Change Sale Status</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="changeSaleStatusForm">
                                        <div class="mb-3">
                                            <label for="detailsTextarea" class="form-label">Details</label>
                                            <textarea class="form-control" id="detailsTextarea" rows="4" required></textarea>
                                        </div>
                                       <input type="hidden" id="statusDropdown" name="status" value="${status}">
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
            $('#changeSaleStatusModal').modal('show');
           
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

                if (hasError) {
                    $('#detailsTextarea').on('input', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });
                    return;
                }

                // AJAX request
                $.ajax({
                    url: '{{ route("changeSaleStatus") }}',
                    type: 'POST',
                    data: {
                        sale_id: saleID,
                        details: notes,
                        status: selectedStatus,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        toastr.success('Sale status changed successfully!');
                        $('#changeSaleStatusModal').modal('hide');
                        $('#changeSaleStatusForm')[0].reset();
                        $('#detailsTextarea, #statusDropdown').removeClass('is-valid is-invalid');
                        $('#detailsTextarea, #statusDropdown').next('.invalid-feedback').remove();

                        $('#sales_table').DataTable().ajax.reload();
                    },
                    error: function (xhr) {
                        toastr.error('An error occurred while saving sale status changed.');
                    }
                });
            });
        }
       
        // Function to change on hold status modal
        function changeSaleOnHoldStatusModal(saleID, status) {
            // Add the modal HTML to the page (only once, if not already present)
            if ($('#changeSaleOnHoldStatusModal').length === 0) {
                $('body').append(
                    `<div class="modal fade" id="changeSaleOnHoldStatusModal" tabindex="-1" aria-labelledby="changeSaleOnHoldStatusModalLabel" >
                        <div class="modal-dialog modal-lg modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="changeSaleOnHoldStatusModalLabel">Mark as On Hold Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="changeSaleOnHoldStatusForm">
                                        <div class="mb-3">
                                            <label for="detailsTextarea" class="form-label">Details</label>
                                            <textarea class="form-control" id="detailsTextarea" rows="4" required></textarea>
                                        </div>
                                        <input type="hidden" id="status" name="status" value="${status}">
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="saveOnHoldNotesButton">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>`
                );
            }

            // Show the modal
            $('#changeSaleOnHoldStatusModal').modal('show');

            // Handle the save button click
            $('#saveOnHoldNotesButton').off('click').on('click', function () {
                const notes = $('#detailsTextarea').val();
                const selectedStatus = $('#status').val();

                let hasError = false;

                if (!notes) {
                    $('#detailsTextarea').addClass('is-invalid');
                    if ($('#detailsTextarea').next('.invalid-feedback').length === 0) {
                        $('#detailsTextarea').after('<div class="invalid-feedback">Please provide details.</div>');
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

                    return;
                }

                // AJAX request
                $.ajax({
                    url: '{{ route("changeSaleHoldStatus") }}',
                    type: 'GET',
                    data: {
                        id: saleID,
                        details: notes,
                        status: selectedStatus,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        toastr.success('Sale status changed successfully!');
                        $('#changeSaleOnHoldStatusModal').modal('hide');
                        $('#changeSaleOnHoldStatusForm')[0].reset();
                        $('#detailsTextarea, #statusDropdown').removeClass('is-valid is-invalid');
                        $('#detailsTextarea, #statusDropdown').next('.invalid-feedback').remove();

                        $('#sales_table').DataTable().ajax.reload();
                    },
                    error: function (xhr) {
                        toastr.error('An error occurred while saving sale status changed.');
                    }
                });
            });
        }

        function showDetailsModal(saleId, officeName, name, postcode, 
            jobCategory, jobTitle, status, timing, experience, salary, 
            position, qualification, benefits
        ) 
        {
            // Set the notes content in the modal as a table
            $('#showDetailsModal .modal-body').html(
                '<table class="table table-bordered">' +
                    '<tr>' +
                        '<th>Sale ID</th>' +
                        '<td>' + saleId + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Head Office Name</th>' +
                        '<td>' + officeName + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Unit Name</th>' +
                        '<td>' + name + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Postcode</th>' +
                        '<td>' + postcode + '</td>' +
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
                        '<th>Status</th>' +
                        '<td>' + status + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Timing</th>' +
                        '<td>' + timing + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Qualification</th>' +
                        '<td>' + qualification + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Salary</th>' +
                        '<td>' + salary + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Position</th>' +
                        '<td>' + position + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Experience</th>' +
                        '<td>' + experience + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Benefits</th>' +
                        '<td>' + benefits + '</td>' +
                    '</tr>' +
                '</table>'
            );

            // Show the modal
            $('#showDetailsModal').modal('show');

            // Add the modal HTML to the page (only once, if not already present)
            if ($('#showDetailsModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="showDetailsModal" tabindex="-1" aria-labelledby="showDetailsModalLabel" >' +
                        '<div class="modal-dialog modal-lg modal-dialog-top modal-dialog-scrollable">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="showDetailsModalLabel">Sale Details</h5>' +
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
                    module: 'Horsefly\\Sale'

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
                                    '<p><strong>Dated:</strong> ' + created + '&nbsp;&nbsp;<span class="badge ' + statusClass + '">' + statusText + '</span></p>' +
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
                    '<div class="modal fade" id="viewNotesHistoryModal" tabindex="-1" aria-labelledby="viewNotesHistoryModalLabel" >' +
                        '<div class="modal-dialog modal-dialog-scrollable modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="viewNotesHistoryModalLabel">Sale Notes History</h5>' +
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
        function viewManagerDetails(id) {
            // Make an AJAX call to retrieve notes history data
            $.ajax({
                url: '{{ route("getModuleContacts") }}', // Your backend URL to fetch notes history, replace it with your actual URL
                type: 'GET',
                data: { 
                    id: id,
                    module: 'Horsefly\\Unit'

                }, // Pass the id to your server to fetch the corresponding applicant's notes
                success: function(response) {
                    var contactHtml = '';  // This will hold the combined HTML for all notes

                    // Check if the response data is empty
                    if (response.data.length === 0) {
                        contactHtml = '<p>No record found.</p>';
                    } else {
                        // Loop through the response array (assuming it's an array of notes)
                        response.data.forEach(function(contact) {
                            var name = contact.contact_name;
                            var email = contact.contact_email;
                            var phone = contact.contact_phone;
                            var landline = contact.contact_landline ? contact.contact_landline : '-';
                            var note = contact.contact_note ? contact.contact_note : 'N/A';

                            // Append each note's details to the notesHtml string
                            contactHtml += 
                                '<div class="note-entry">' +
                                    '<p><strong>Name:</strong> ' + name + '</p>' +
                                    '<p><strong>Email:</strong> ' + email + '</p>' +
                                    '<p><strong>Phone:</strong> ' + phone + '</p>' +
                                    '<p><strong>Landline:</strong> ' + landline + '</p>' +
                                    '<p><strong>Notes:</strong> ' + note + '</p>' +
                                '</div><hr>';  // Add a separator between notes
                        });
                    }

                    // Set the combined notes content in the modal
                    $('#viewManagerDetailsModal .modal-body').html(contactHtml);

                    // Show the modal
                    $('#viewManagerDetailsModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.log("Error fetching notes history: " + error);
                    // Optionally, you can display an error message in the modal
                    $('#viewManagerDetailsModal .modal-body').html('<p>There was an error retrieving the manager details. Please try again later.</p>');
                    $('#viewManagerDetailsModal').modal('show');
                }
            });

            // Add the modal HTML to the page (only once, if not already present)
            if ($('#viewManagerDetailsModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="viewManagerDetailsModal" tabindex="-1" aria-labelledby="viewManagerDetailsModalLabel" >' +
                        '<div class="modal-dialog modal-dialog-scrollable modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="viewManagerDetailsModalLabel">Manager Details</h5>' +
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
        
    </script>

@endsection
@endsection                        
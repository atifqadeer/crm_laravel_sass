@extends('layouts.vertical', ['title' => 'Sales List', 'subTitle' => 'Home'])
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
                             <!-- user Filter Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i> <span id="showFilterUser">All Users</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                    <a class="dropdown-item user-filter" href="#">All Users</a>
                                    @foreach($users as $user)
                                        <a class="dropdown-item user-filter" href="#" data-user-id="{{ $user->id }}">{{ ucwords($user->name) }}</a>
                                    @endforeach
                                </div>
                            </div>
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
                            <!-- Status Filter Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i> <span id="showFilterStatus">All</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                    <a class="dropdown-item status-filter" href="#">All</a>
                                    <a class="dropdown-item status-filter" href="#">Active</a>
                                    <a class="dropdown-item status-filter" href="#">Closed</a>
                                    <a class="dropdown-item status-filter" href="#">Pending</a>
                                    <a class="dropdown-item status-filter" href="#">Rejected</a>
                                    <a class="dropdown-item status-filter" href="#">On Hold</a>
                                </div>
                            </div>
                            <!-- Button Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton3" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-download-line me-1"></i> Export
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton3">
                                    <a class="dropdown-item" href="{{ route('salesExport', ['type' => 'all']) }}">Export All Data</a>
                                    <a class="dropdown-item" href="{{ route('salesExport', ['type' => 'emails']) }}">Export Emails</a>
                                    <a class="dropdown-item" href="{{ route('salesExport', ['type' => 'noLatLong']) }}">Export no LAT & LONG</a>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary me-1 my-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Import CSV">
                                <i class="ri-upload-line"></i>
                            </button>
                            <a href="{{ route('sales.create') }}"><button type="button" class="btn btn-success ml-1 my-1"><i class="ri-add-line"></i> Create Sale</button></a>
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
                                <th>Created Date</th>
                                <th>Updated Date</th>
                                <th>Open Date</th>
                                <th>Agent</th>
                                <th>Head Office</th>
                                <th>Unit Name</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>PostCode</th>
                                <th>Experience</th>
                                <th>Qualification</th>
                                <th>Salary</th>
                                <th>CV Limit</th>
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

<!-- Experience Modal -->
<div class="modal fade" id="experienceModal" tabindex="-1" aria-labelledby="experienceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="experienceModalLabel">Sale Experience</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="experienceModalBody">
        <!-- Experience will be injected here -->
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
            var currentFilter = '';
            var currentTypeFilter = '';
            var currentCategoryFilter = '';
            var currentUserFilter = '';
            var currentTitleFilter = '';
            var currentOfficeFilter = '';
            var currentCVLimitFilter = '';

            // Create a loader row and append it to the table before initialization
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="100%" class="text-center py-4">
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
                    url: @json(route('getSales')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        // Add the current filter to the request parameters
                        d.status_filter = currentFilter;  // Send the current filter value as a parameter
                        d.type_filter = currentTypeFilter;  // Send the current filter value as a parameter
                        d.category_filter = currentCategoryFilter;  // Send the current filter value as a parameter
                        d.title_filter = currentTitleFilter;  // Send the current filter value as a parameter
                        d.office_filter = currentOfficeFilter;  // Send the current filter value as a parameter
                        d.user_filter = currentUserFilter;  // Send the current filter value as a parameter
                        d.cv_limit_filter = currentCVLimitFilter;  // Send the current filter value as a parameter
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at', name: 'sales.created_at' },
                    { data: 'updated_at', name: 'sales.updated_at' },
                    { data: 'open_date', name: 'audits.created_at' },
                    { data: 'user_name', name: 'users.name'},
                    { data: 'office_name', name: 'offices.office_name'},
                    { data: 'unit_name', name: 'units.unit_name'  },
                    { data: 'job_title', name: 'job_titles.name' },
                    { data: 'job_category', name: 'job_categories.name' },
                    { data: 'sale_postcode', name: 'sales.sale_postcode' },
                    { data: 'experience', name: 'sales.experience' },
                    { data: 'qualification', name: 'sales.qualification' },
                    { data: 'salary', name: 'sales.salary' },
                    { data: 'cv_limit', name: 'sales.cv_limit' },
                    { data: 'sale_notes', name: 'sales.sale_notes', orderable: false },
                    { data: 'status', name: 'sales.status', orderable: false },
                    { data: 'action', name: 'action', orderable: false }
                ],
                columnDefs: [
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
                    },
                    {
                        targets: 16,  // Column index for 'job_details'
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');  // Center the text in this column
                        }
                    }
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
                        $('#sales_table tbody').html('<tr><td colspan="100%" class="text-center">Data not found</td></tr>');
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
            // cv limit filter dropdown handler
            $('.cv-limit-filter').on('click', function () {
                currentCVLimitFilter = $(this).text().toLowerCase();

                // Capitalize each word
                const formattedText = currentCVLimitFilter
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

        function goToPage(totalPages) {
            const input = document.getElementById('goToPageInput');
            const errorMessage = document.getElementById('goToPageError');
            let page = parseInt(input.value);

            if (!isNaN(page) && page >= 1 && page <= totalPages) {
                $('#sales_table').DataTable().page(page - 1).draw('page');
                input.classList.remove('is-invalid');
            } else {
                input.classList.add('is-invalid');
            }
        }
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
        function showNotesModal(saleId, notes, officeName, unitName, unitPostcode) {
            const modalId = `showNotesModal_${saleId}`;

            // Check and append modal only once
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalId}Label">Sale Notes</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <div class="spinner-border text-primary my-4" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            } else {
                // Reset body content with loader if it already exists
                $(`#${modalId} .modal-body`).html(`
                    <div class="spinner-border text-primary my-4" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                `);
            }

            // Show modal
            $(`#${modalId}`).modal('show');

            // Set content after short delay (simulate loading)
            setTimeout(() => {
                $(`#${modalId} .modal-body`).html(`
                    <div class="text-start">
                        <p class="mb-1"><strong>Head Office Name:</strong> ${officeName}</p>
                        <p class="mb-1"><strong>Unit Name:</strong> ${unitName}</p>
                        <p class="mb-1"><strong>Postcode:</strong> ${unitPostcode}</p>
                        <p><strong>Notes Detail:</strong><br>${notes.replace(/\n/g, '<br>')}</p>
                    </div>
                `);
            }, 300); // adjust delay if needed
        }

        // Function to show the notes modal
        function addNotesModal(saleID) {
            const modalId = `notesModal_${saleID}`;
            const formId = `notesForm_${saleID}`;
            const textareaId = `detailsTextarea_${saleID}`;
            const saveBtnId = `saveNotesButton_${saleID}`;

            // Append modal HTML only once
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalId}Label">Add Notes</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="${formId}">
                                        <div class="mb-3">
                                            <label for="${textareaId}" class="form-label">Details</label>
                                            <textarea class="form-control" id="${textareaId}" rows="4" required></textarea>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="${saveBtnId}">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Show modal
            $(`#${modalId}`).modal('show');

            // Reset form fields on open
            $(`#${modalId}`).on('shown.bs.modal', function () {
                $(`#${formId}`)[0].reset();
                $(`#${textareaId}`).removeClass('is-invalid is-valid');
                $(`#${textareaId}`).next('.invalid-feedback').remove();
            });

            // Save button logic
            $(`#${saveBtnId}`).off('click').on('click', function () {
                const notes = $(`#${textareaId}`).val();

                if (!notes) {
                    $(`#${textareaId}`).addClass('is-invalid');
                    if ($(`#${textareaId}`).next('.invalid-feedback').length === 0) {
                        $(`#${textareaId}`).after('<div class="invalid-feedback">Please provide details.</div>');
                    }

                    $(`#${textareaId}`).on('input', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                $(`#${textareaId}`).removeClass('is-invalid').addClass('is-valid');
                $(`#${textareaId}`).next('.invalid-feedback').remove();

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                $.ajax({
                    url: '{{ route("storeSaleNotes") }}',
                    type: 'POST',
                    data: {
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        toastr.success('Notes saved successfully!');

                        $(`#${modalId}`).modal('hide');
                        $(`#formId}`)[0].reset();
                        $(`#${textareaId}`).removeClass('is-valid');

                        $('#sales_table').DataTable().ajax.reload();
                    },
                    error: function (xhr) {
                        alert('An error occurred while saving notes.');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }
        
        // Function to change sale status modal
        function changeSaleStatusModal(saleID, currentStatus) {
            const modalId = `changeSaleStatusModal_${saleID}`;

            // Create modal if it doesn't exist
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalId}Label">Change Sale Status</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="changeSaleStatusForm_${saleID}">
                                        <div class="mb-3">
                                            <label for="detailsTextarea_${saleID}" class="form-label">Details</label>
                                            <textarea class="form-control" id="detailsTextarea_${saleID}" rows="4" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="statusDropdown_${saleID}" class="form-label">Status</label>
                                            <select class="form-select" id="statusDropdown_${saleID}" required>
                                                <option value="" disabled selected>Select Status</option>
                                                <option value="1">Active</option>
                                                <option value="0">Closed</option>
                                                <option value="2">Pending</option>
                                                <option value="3">Reject</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="saveNotesButton_${saleID}">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Show modal and reset form on load
            const modalSelector = `#${modalId}`;
            const formSelector = `#changeSaleStatusForm_${saleID}`;
            const textareaSelector = `#detailsTextarea_${saleID}`;
            const dropdownSelector = `#statusDropdown_${saleID}`;
            const saveButtonSelector = `#saveNotesButton_${saleID}`;

            $(modalSelector).on('shown.bs.modal', function () {
                $(formSelector)[0].reset();
                $(textareaSelector).removeClass('is-invalid is-valid');
                $(dropdownSelector).removeClass('is-invalid is-valid');
                $(textareaSelector).next('.invalid-feedback').remove();
                $(dropdownSelector).next('.invalid-feedback').remove();

                // Pre-select status if passed
                if (currentStatus !== undefined && currentStatus !== null) {
                    $(dropdownSelector).val(currentStatus);
                }
            });

            $(modalSelector).modal('show');

            // Save button handler
            $(saveButtonSelector).off('click').on('click', function () {
                const notes = $(textareaSelector).val();
                const selectedStatus = $(dropdownSelector).val();
                let hasError = false;

                // Notes validation
                if (!notes) {
                    $(textareaSelector).addClass('is-invalid');
                    if ($(textareaSelector).next('.invalid-feedback').length === 0) {
                        $(textareaSelector).after('<div class="invalid-feedback">Please provide details.</div>');
                    }
                    hasError = true;
                }

                // Status validation
                if (!selectedStatus) {
                    $(dropdownSelector).addClass('is-invalid');
                    if ($(dropdownSelector).next('.invalid-feedback').length === 0) {
                        $(dropdownSelector).after('<div class="invalid-feedback">Please select a status.</div>');
                    }
                    hasError = true;
                }

                if (hasError) {
                    $(textareaSelector).on('input', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });
                    $(dropdownSelector).on('change', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

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
                    success: function () {
                        toastr.success('Sale status changed successfully!');
                        $(modalSelector).modal('hide');
                        $('#sales_table').DataTable().ajax.reload();
                    },
                    error: function () {
                        toastr.error('An error occurred while updating the sale status.');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        // Function to change on hold status modal
        function changeSaleOnHoldStatusModal(saleID, status) {
            const modalId = `changeSaleOnHoldStatusModal_${saleID}`;
            const formId = `changeSaleOnHoldStatusForm_${saleID}`;
            const textareaId = `detailsTextarea_${saleID}`;
            const saveBtnId = `saveOnHoldNotesButton_${saleID}`;

            // Append modal HTML if it doesn't already exist
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalId}Label">Mark as On Hold Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="${formId}">
                                        <div class="mb-3">
                                            <label for="${textareaId}" class="form-label">Details</label>
                                            <textarea class="form-control" id="${textareaId}" rows="4" required></textarea>
                                        </div>
                                        <input type="hidden" id="status_${saleID}" name="status" value="${status}">
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="${saveBtnId}">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Reset form and show modal
            const modalSelector = `#${modalId}`;
            const textareaSelector = `#${textareaId}`;
            const formSelector = `#${formId}`;
            const saveBtnSelector = `#${saveBtnId}`;

            $(modalSelector).on('shown.bs.modal', function () {
                $(formSelector)[0].reset();
                $(textareaSelector).removeClass('is-invalid is-valid');
                $(textareaSelector).next('.invalid-feedback').remove();
            });

            $(modalSelector).modal('show');

            // Save button handler
            $(saveBtnSelector).off('click').on('click', function () {
                const notes = $(textareaSelector).val();
                const selectedStatus = $(`#status_${saleID}`).val();

                let hasError = false;

                if (!notes) {
                    $(textareaSelector).addClass('is-invalid');
                    if ($(textareaSelector).next('.invalid-feedback').length === 0) {
                        $(textareaSelector).after('<div class="invalid-feedback">Please provide details.</div>');
                    }
                    hasError = true;
                }

                if (hasError) {
                    $(textareaSelector).on('input', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // AJAX request (use POST instead of GET)
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
                        toastr.success('Sale marked as On Hold successfully!');
                        $(modalSelector).modal('hide');
                        $(formSelector)[0].reset();
                        $(textareaSelector).removeClass('is-valid is-invalid');
                        $(textareaSelector).next('.invalid-feedback').remove();

                        $('#sales_table').DataTable().ajax.reload();
                    },
                    error: function (xhr) {
                        toastr.error('An error occurred while updating the On Hold status.');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        function showDetailsModal(
            saleId, postedOn, officeName, name, postcode, 
            jobCategory, jobTitle, status, timing, 
            experience, salary, position, qualification, benefits
        ) {
            const modalId = `showDetailsModal_${saleId}`;

            // Create modal if it doesn't already exist
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-top modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalId}Label">Sale Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body modal-body-text-left">
                                    <div class="spinner-border text-primary my-4" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            } else {
                // Reset content with loader on each call
                $(`#${modalId} .modal-body`).html(`
                    <div class="spinner-border text-primary my-4" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                `);
            }

            // Show the modal
            $(`#${modalId}`).modal('show');

            // Simulate loading delay before filling in data
            setTimeout(() => {
                const tableHTML = `
                    <table class="table table-bordered mb-0">
                        <tr><th>Sale ID</th><td>${saleId}</td></tr>
                        <tr><th>Posted On</th><td>${postedOn}</td></tr>
                        <tr><th>Head Office Name</th><td>${officeName}</td></tr>
                        <tr><th>Unit Name</th><td>${name}</td></tr>
                        <tr><th>Postcode</th><td>${postcode}</td></tr>
                        <tr><th>Job Category</th><td>${jobCategory}</td></tr>
                        <tr><th>Job Title</th><td>${jobTitle}</td></tr>
                        <tr><th>Status</th><td>${status}</td></tr>
                        <tr><th>Timing</th><td>${timing}</td></tr>
                        <tr><th>Qualification</th><td>${qualification}</td></tr>
                        <tr><th>Salary</th><td>${salary}</td></tr>
                        <tr><th>Position</th><td>${position}</td></tr>
                        <tr><th>Experience</th><td>${experience}</td></tr>
                        <tr><th>Benefits</th><td>${benefits}</td></tr>
                    </table>
                `;

                $(`#${modalId} .modal-body`).html(tableHTML);
            }, 300); // Adjust delay to match actual data loading if needed
        }

        // Function to show the notes modal
        function viewSaleDocuments(saleId) {
            const modalId = `viewSaleDocumentsModal_${saleId}`;
            const modalLabelId = `viewSaleDocumentsModalLabel_${saleId}`;

            // Append modal HTML only if it doesn't already exist
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalLabelId}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalLabelId}">Sale Documents</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <div class="spinner-border text-primary my-4" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            } else {
                // Reset with loader if modal exists
                $(`#${modalId} .modal-body`).html(`
                    <div class="spinner-border text-primary my-4" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                `);
            }

            // Show modal
            $(`#${modalId}`).modal('show');

            // Make AJAX request
            $.ajax({
                url: '{{ route("getSaleDocuments") }}',
                type: 'GET',
                data: { id: saleId },
                success: function(response) {
                    let contentHtml = '';

                    if (!response.data || response.data.length === 0) {
                        contentHtml = '<p class="text-muted text-center">No record found.</p>';
                    } else {
                        response.data.forEach(doc => {
                            const created = moment(doc.created_at).format('DD MMM YYYY, h:mmA');
                            const filePath = '/storage/' + doc.document_path;
                            const docName = doc.document_name;

                            contentHtml += `
                                <div class="note-entry text-start">
                                    <p><strong>Dated:</strong> ${created}</p>
                                    <p><strong>File:</strong> ${docName}
                                        <br>
                                        <button class="btn btn-sm btn-primary mt-1" onclick="window.open('${filePath}', '_blank')">Open</button>
                                    </p>
                                </div>
                                <hr>
                            `;
                        });
                    }

                    $(`#${modalId} .modal-body`).html(contentHtml);
                },
                error: function(xhr) {
                    $(`#${modalId} .modal-body`).html('<p class="text-danger">There was an error retrieving the documents. Please try again later.</p>');
                }
            });
        }

        // Function to show the notes modal
        function viewNotesHistory(id) {
            const modalId = `viewNotesHistoryModal_${id}`;
            const modalLabelId = `viewNotesHistoryModalLabel_${id}`;

            // Create the modal if it doesn't already exist
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalLabelId}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalLabelId}">Sale Notes History</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <div class="spinner-border text-primary my-4" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            } else {
                // Reset modal content with loader if already exists
                $(`#${modalId} .modal-body`).html(`
                    <div class="spinner-border text-primary my-4" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                `);
            }

            // Show the modal
            $(`#${modalId}`).modal('show');

            // AJAX request to fetch notes
            $.ajax({
                url: '{{ route("getModuleNotesHistory") }}',
                type: 'GET',
                data: {
                    id: id,
                    module: 'Horsefly\\Sale'
                },
                success: function(response) {
                    let notesHtml = '';

                    if (!response.data || response.data.length === 0) {
                        notesHtml = '<p class="text-muted text-center">No record found.</p>';
                    } else {
                        response.data.forEach(note => {
                            const created = moment(note.created_at).format('DD MMM YYYY, h:mmA');
                            const status = note.status;
                            const statusClass = status == 1 ? 'bg-success' : 'bg-dark';
                            const statusText = status == 1 ? 'Active' : 'Inactive';
                            const notes = note.details.replace(/\n/g, '<br>');

                            notesHtml += `
                                <div class="note-entry text-start">
                                    <p><strong>Dated:</strong> ${created} &nbsp;
                                    <span class="badge ${statusClass}">${statusText}</span></p>
                                    <p><strong>Notes Detail:</strong><br>${notes}</p>
                                </div><hr>
                            `;
                        });
                    }

                    $(`#${modalId} .modal-body`).html(notesHtml);
                },
                error: function(xhr) {
                    $(`#${modalId} .modal-body`).html('<p class="text-danger">There was an error retrieving the notes. Please try again later.</p>');
                }
            });
        }
       
        // Function to show the manager details modal
        function viewManagerDetails(id) {
            const modalID = 'viewManagerDetailsModal-' + id;
            
            // Create modal if it doesn't exist
            if ($('#' + modalID).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalID}" tabindex="-1" aria-labelledby="viewManagerDetailsModalLabel-${id}">
                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="viewManagerDetailsModalLabel-${id}">Manager Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body modal-body-text-left">
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary" role="status">
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
            
            // Show modal immediately with loading state
            $('#' + modalID).modal('show');
            
            // Make AJAX call
            $.ajax({
                url: '{{ route("getModuleContacts") }}',
                type: 'GET',
                data: { 
                    id: id,
                    module: 'Horsefly\\Unit'
                },
                success: function(response) {
                    let contactHtml = '';
                    
                    if (response.data.length === 0) {
                        contactHtml = '<p>No record found.</p>';
                    } else {
                        response.data.forEach(function(contact) {
                            const name = contact.contact_name;
                            const email = contact.contact_email;
                            const phone = contact.contact_phone;
                            const landline = contact.contact_landline || '-';
                            const note = contact.contact_note || 'N/A';
                            
                            contactHtml += `
                                <div class="note-entry">
                                    <p><strong>Name:</strong> ${name}</p>
                                    <p><strong>Email:</strong> ${email}</p>
                                    <p><strong>Phone:</strong> ${phone}</p>
                                    <p><strong>Landline:</strong> ${landline}</p>
                                    <p><strong>Notes:</strong> ${note}</p>
                                </div><hr>`;
                        });
                    }
                    
                    $('#' + modalID + ' .modal-body').html(contactHtml);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching notes history:", error);
                    $('#' + modalID + ' .modal-body').html(
                        '<p class="text-danger">There was an error retrieving the manager details. Please try again later.</p>'
                    );
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.body.addEventListener('click', function (e) {
                if (e.target.classList.contains('view-experience')) {
                    e.preventDefault();
                    const experience = e.target.getAttribute('data-experience');
                    document.getElementById('experienceModalBody').innerHTML = experience;
                }
            });
        });
    </script>
    
@endsection
@endsection                        
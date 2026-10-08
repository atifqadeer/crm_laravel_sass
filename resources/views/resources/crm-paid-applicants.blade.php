@extends('layouts.vertical', ['title' => 'CRM Paid Resources List', 'subTitle' => 'Resources'])
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
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row justify-content-between">
                        <div class="col-lg-3">
                            <div class="text-md-start mt-3 pt-1">
                                <div class="input-group">
                                    <!-- Use padding-right to prevent text from overlapping the clear icon -->
                                    <input type="text" id="customSearchInput" class="form-control" placeholder="Search ..."
                                        style="padding-right: 30px;">
                                    <!-- Absolutely positioned over the input field -->
                                    <span class="position-absolute d-none" id="customClearBtn" title="Clear"
                                        style="right: 105px; top: 50%; transform: translateY(-50%); z-index: 10; cursor: pointer;">
                                        <i class="ri-close-line text-primary"
                                            style="font-size: 20px; font-weight: 900;"></i>
                                    </span>
                                    <button class="btn btn-primary z-3" id="customSearchBtn" type="button"><i
                                            class="ri-search-line"></i> Search</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-9">
                            <div class="text-md-end mt-3">
                                <!-- Category Filter Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterCategory">All
                                            Categories</span>
                                    </button>

                                    <div class="dropdown-menu filter-dropdowns" aria-labelledby="dropdownMenuButton1">
                                        <!-- Search input -->
                                        <input type="text" class="form-control mb-2" id="categorySearchInput"
                                            placeholder="Search category...">
                                        <div class="d-flex justify-content-end px-1 mb-1" id="categoryToggleContainer">
                                            <a href="#" id="categorySelectAll"
                                                class="text-primary small fw-semibold me-2">Select All</a>
                                            <a href="#" id="categoryDeselectAll" class="text-danger small fw-semibold"
                                                style="display:none">Deselect All</a>
                                        </div>

                                        <!-- Scrollable checkbox list -->
                                        <div id="categoryList">
                                            @foreach ($jobCategories as $category)
                                                <div class="form-check">
                                                    <input class="form-check-input category-filter" type="checkbox"
                                                        value="{{ $category->id }}" id="category_{{ $category->id }}"
                                                        data-category-id="{{ $category->id }}">
                                                    <label class="form-check-label"
                                                        for="category_{{ $category->id }}">{{ ucwords($category->name) }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <!-- Type Filter Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dropdownMenuButton4" data-bs-toggle="dropdown" aria-expanded="false">
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
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterTitle">All Titles</span>
                                    </button>

                                    <div class="dropdown-menu p-2 filter-dropdowns" aria-labelledby="dropdownMenuButton2"
                                        style="min-width: 250px;">
                                        <!-- Search input -->
                                        <input type="text" class="form-control mb-2" id="titleSearchInput"
                                            placeholder="Search titles...">
                                        <div class="d-flex justify-content-end px-1 mb-1" id="titleToggleContainer">
                                            <a href="#" id="titleSelectAll"
                                                class="text-primary small fw-semibold me-2">Select All</a>
                                            <a href="#" id="titleDeselectAll" class="text-danger small fw-semibold"
                                                style="display:none">Deselect All</a>
                                        </div>

                                        <!-- Scrollable checkbox list -->
                                        <div id="titleList">
                                            @foreach ($jobTitles as $title)
                                                <div class="form-check">
                                                    <input class="form-check-input title-filter" type="checkbox"
                                                        value="{{ $title->id }}" id="title_{{ $title->id }}"
                                                        data-title-id="{{ $title->id }}"
                                                        data-category-id="{{ $title->job_category_id }}">
                                                    <label class="form-check-label"
                                                        for="title_{{ $title->id }}">{{ ucwords($title->name) }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <!-- Sources Filter Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dropdownMenuButton10" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterSource">All Sources</span>
                                    </button>

                                    <div class="dropdown-menu filter-dropdowns" aria-labelledby="dropdownMenuButton10">
                                        <!-- Search input -->
                                        <input type="text" class="form-control mb-2" id="sourceSearchInput"
                                            placeholder="Search Source...">
                                        <!-- Select/Deselect All -->
                                        <div class="d-flex justify-content-end px-1 mb-1" id="sourceToggleContainer">
                                            <a href="#" id="sourceSelectAll"
                                                class="text-primary small fw-semibold me-2">Select All</a>
                                            <a href="#" id="sourceDeselectAll"
                                                class="text-danger small fw-semibold" style="display:none">Deselect
                                                All</a>
                                        </div>
                                        <!-- Scrollable checkbox list -->
                                        <div id="sourceList">

                                            @foreach ($jobSources as $source)
                                                <div class="form-check">
                                                    <input class="form-check-input source-filter" type="checkbox"
                                                        value="{{ $source->id }}" id="source_{{ $source->id }}"
                                                        data-source-id="{{ $source->id }}">
                                                    <label class="form-check-label"
                                                        for="source_{{ $source->id }}">{{ ucwords($source->name) }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <!-- Button Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i> Export
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                        <a class="dropdown-item"
                                            href="{{ route('applicantsExport', ['type' => 'allPaid']) }}">Export All
                                            Data</a>
                                        {{-- <a class="dropdown-item" href="{{ route('applicantsExport', ['type' => 'emailsRejected']) }}">Export Emails</a> --}}
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
                    <div class="d-flex justify-content-end mb-2">
                        <div class="dropdown" id="paidApplicantsColumnsToolbar">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button"
                                id="paidApplicantsColumnsButton" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                aria-expanded="false">
                                <i class="ri-layout-column-line me-1"></i> Columns
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="paidApplicantsColumnsButton"
                                style="min-width: 230px;">
                                <div class="d-flex justify-content-between align-items-center px-1 mb-2">
                                    <a href="#" id="paidApplicantsColumnsShowAll"
                                        class="text-primary small fw-semibold">Show All</a>
                                    <a href="#" id="paidApplicantsColumnsReset"
                                        class="text-secondary small fw-semibold">Reset Default</a>
                                </div>
                                <div id="paidApplicantsColumnsList" style="max-height: 280px; overflow-y: auto;"></div>
                            </div>
                        </div>
                    </div>
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
                                    <th width="15%">Phone / Landline</th>
                                    <th>Job Details</th>
                                    <th>Head Office</th>
                                    <th>Unit</th>
                                    <th>Postcode (Sale)</th>
                                    <th>Source (Sale)</th>
                                    <th>Notes</th>
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
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>

    <!-- DataTables CSS (for styling the table) -->
    <link rel="stylesheet" href="{{ asset('css/jquery.dataTables.min.css') }}">

    <!-- DataTables JS (for the table functionality) -->
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}">

    <!-- SweetAlert2 CDN -->
    <script src="{{ asset('js/sweetalert2@11.js') }}"></script>

    <!-- Toastr JS -->
    <script src="{{ asset('js/toastr.min.js') }}"></script>

    <!-- Moment JS -->
    <script src="{{ asset('js/moment.min.js') }}"></script>

    <!-- Summernote CSS -->
    <link rel="stylesheet" href="{{ asset('css/summernote-lite.min.css') }}">

    <!-- Summernote JS -->
    <script src="{{ asset('js/summernote-lite.min.js') }}"></script>

    <!-- Add daterangepicker -->
    <link rel="stylesheet" href="{{ asset('css/daterangepicker.css') }}">
    <script src="{{ asset('js/daterangepicker.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Store the current filter in a variable
            let currentTypeFilter = '';
            let currentCategoryFilters = [];
            let currentTitleFilters = [];
            let currentSourceFilters = [];

            // Create loader row
            const loadingRow = `<tr><td colspan="100%" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td></tr>`;

            // Function to show loader
            function showLoader() {
                $('#applicants_table tbody').empty().append(loadingRow);
            }

            // Initialize DataTable with server-side processing
            var table = $('#applicants_table').DataTable({
                processing: false, // Disable default processing state
                serverSide: true, // Enables server-side processing
                ajax: {
                    url: @json(route('getResourcesPaidApplicants')), // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        // Add the current filter to the request parameters
                        d.type_filter =
                            currentTypeFilter; // Send the current filter value as a parameter
                        d.category_filter =
                            currentCategoryFilters; // Send the current filter value as a parameter
                        d.title_filter =
                            currentTitleFilters; // Send the current filter value as a parameter

                        d.source_filter =
                            currentSourceFilters; // Send the current filter value as a parameter

                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }
                    },
                    beforeSend: function() {
                        showLoader(); // Show loader before AJAX request starts
                    },
                    error: function(xhr) {
                        console.error('DataTable AJAX error:', xhr.status, xhr.responseJSON);
                        $('#applicants_table tbody').empty().html(
                            '<tr><td colspan="100%" class="text-center">Failed to load data</td></tr>'
                        );
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'crm_notes_created_at',
                        name: 'crm_notes.created_at'
                    },
                    {
                        data: 'applicant_name',
                        name: 'applicants.applicant_name'
                    },
                    {
                        data: 'applicant_email',
                        name: 'applicants.applicant_email'
                    },
                    {
                        data: 'job_title',
                        name: 'job_titles.name'
                    },
                    {
                        data: 'job_category',
                        name: 'job_categories.name'
                    },
                    {
                        data: 'applicant_postcode',
                        name: 'applicants.applicant_postcode'
                    },
                    {
                        data: 'applicant_phone',
                        name: 'applicants.applicant_phone'
                    },
                    {
                        data: 'job_details',
                        name: 'job_details',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'sale_office',
                        name: 'offices.office_name'
                    },
                    {
                        data: 'sale_unit',
                        name: 'units.unit_name'
                    },
                    {
                        data: 'sale_postcode',
                        name: 'sales.sale_postcode'
                    },
                    {
                        data: 'sale_job_source',
                        name: 'sale_job_sources.name'
                    },
                    {
                        data: 'applicant_notes',
                        name: 'applicants.applicant_notes',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                columnDefs: [{
                    targets: [8, 11, 12, 13, 14],
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).css('text-align', 'center');
                    }
                }, ],
                rowId: function(data) {
                    // One row per applicant + sale (an applicant can be paid for more than one sale)
                    return 'row_' + data.id + '_' + data.sale_id;
                },
                dom: 'lrtip', // Change the order to 'filter' (f), 'length' (l), 'table' (r), 'pagination' (p), and 'information' (i)
                drawCallback: function(settings) {
                    const api = this.api();
                    const pagination = $(api.table().container()).find('.dataTables_paginate');
                    pagination.empty();

                    const pageInfo = api.page.info();
                    const currentPage = pageInfo.page + 1;
                    const totalPages = pageInfo.pages;

                    if (pageInfo.recordsTotal === 0) {
                        $('#applicants_table tbody').html(
                            '<tr><td colspan="100%" class="text-center">Data not found</td></tr>');
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
                        paginationHtml +=
                            `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }

                    for (let i = start; i <= end; i++) {
                        paginationHtml += `<li class="page-item ${currentPage === i ? 'active' : ''}">
                                <a class="page-link" href="javascript:void(0);" onclick="movePage(${i})">${i}</a>
                            </li>`;
                    }

                    if (end < totalPages - 1) {
                        paginationHtml +=
                            `<li class="page-item disabled"><span class="page-link">...</span></li>`;
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

            document.addEventListener('click', function(e) {
                const link = e.target.closest('.job-details');
                if (!link) return;

                e.preventDefault();

                let job;
                try {
                    job = JSON.parse(link.dataset.job);
                } catch (err) {
                    console.error('Invalid job data', err);
                    return;
                }

                showDetailsModal(job);
            });

            function showDetailsModal(job) {
                const modalId = `job-modal-${job.sale_id}`;
                document.getElementById(modalId)?.remove();

                document.body.insertAdjacentHTML('beforeend', `
                <div class="modal fade" id="${modalId}" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Job Details</h5>
                                <button class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-bordered">
                                    <tr><th>Sale ID</th><td>${job.sale_id}</td></tr>
                                    <tr><th>Posted Date</th><td>${job.posted_date}</td></tr>
                                    <tr><th>Head Office</th><td>${job.office_name}</td></tr>
                                    <tr><th>Unit Name</th><td>${job.unit_name}</td></tr>
                                    <tr><th>Postcode</th><td>${job.postcode}</td></tr>
                                    <tr><th>Job Category</th><td>${job.job_category}</td></tr>
                                    <tr><th>Job Title</th><td>${job.job_title}</td></tr>
                                    <tr><th>Job Source</th><td>${job.sale_source_name}</td></tr>
                                    <tr><th>Status</th><td>${job.status}</td></tr>
                                    <tr><th>Timing</th><td>${job.timing}</td></tr>
                                    <tr><th>Experience</th><td>${job.experience}</td></tr>
                                    <tr><th>Salary</th><td>${job.salary}</td></tr>
                                    <tr><th>Position</th><td>${job.position}</td></tr>
                                    <tr><th>Qualification</th><td>${job.qualification}</td></tr>
                                    <tr><th>Benefits</th><td>${job.benefits}</td></tr>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);

                new bootstrap.Modal(document.getElementById(modalId)).show();
            }

            let searchTimeout;

            // Search logic helper
            function handleCustomSearch() {
                clearTimeout(searchTimeout);
                let searchValue = $('#customSearchInput').val().trim();
                table.search(searchValue).draw();
            }

            // Custom Search Button Event
            $('#customSearchBtn').on('click', function() {
                handleCustomSearch();
            });

            // Custom Search Input Enter Key Event
            $('#customSearchInput').on('keypress', function(e) {
                if (e.which == 13) { // Enter key
                    e.preventDefault();
                    handleCustomSearch();
                }
            });
            // Keep results and the clear control in sync while typing.
            // Keep results and the clear control in sync while typing.
            $('#customSearchInput').on('input', function() {
                const searchValue = $(this).val().trim();
                if (searchValue !== '') {
                    $('#customClearBtn').removeClass('d-none');
                } else {
                    $('#customClearBtn').addClass('d-none');
                }

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    table.search(searchValue).draw();
                }, 300);
            });

            // Clear Button Event
            $('#customClearBtn').on('click', function() {
                clearTimeout(searchTimeout);
                $('#customSearchInput').val('');
                $(this).addClass('d-none');
                table.search('').draw();
            });

            const columnHeaders = table.columns().header().toArray();
            const columnDefaults = columnHeaders.map((header, index) => ({
                title: $(header).text().trim(),
                toggleable: index !== 0 && index !== columnHeaders.length - 1,
                visible: true
            }));
            const columnsStorageKey = 'crm_paid_applicants_column_visibility_v2';
            let storedColumnVisibility = {};
            try {
                storedColumnVisibility = JSON.parse(localStorage.getItem(columnsStorageKey)) || {};
            } catch (error) {
                storedColumnVisibility = {};
            }

            columnDefaults.forEach(function(column, index) {
                if (column.toggleable && Object.prototype.hasOwnProperty.call(storedColumnVisibility,
                        index)) {
                    column.visible = !!storedColumnVisibility[index];
                    table.column(index).visible(column.visible, false);
                }
            });

            function saveColumnVisibility() {
                const visibility = {};
                columnDefaults.forEach(function(column, index) {
                    if (column.toggleable) {
                        visibility[index] = column.visible;
                    }
                });
                localStorage.setItem(columnsStorageKey, JSON.stringify(visibility));
            }

            function renderColumnToggles() {
                const list = $('#paidApplicantsColumnsList').empty();
                columnDefaults.forEach(function(column, index) {
                    if (!column.toggleable) {
                        return;
                    }

                    const item = $('<div class="form-check"></div>');
                    const checkbox = $(
                            '<input class="form-check-input paid-applicant-column-toggle" type="checkbox">')
                        .attr('id', 'paid_applicant_column_' + index)
                        .attr('data-column-index', index)
                        .prop('checked', column.visible);
                    const label = $('<label class="form-check-label"></label>')
                        .attr('for', 'paid_applicant_column_' + index)
                        .text(column.title);
                    item.append(checkbox, label);
                    list.append(item);
                });
            }

            renderColumnToggles();
            table.columns.adjust();

            $('#paidApplicantsColumnsList').on('change', '.paid-applicant-column-toggle', function() {
                const index = Number(this.dataset.columnIndex);
                columnDefaults[index].visible = this.checked;
                table.column(index).visible(this.checked);
                saveColumnVisibility();
            });

            $('#paidApplicantsColumnsShowAll').on('click', function(event) {
                event.preventDefault();
                columnDefaults.forEach(function(column, index) {
                    if (column.toggleable) {
                        column.visible = true;
                        table.column(index).visible(true, false);
                    }
                });
                saveColumnVisibility();
                renderColumnToggles();
                table.columns.adjust().draw(false);
            });

            $('#paidApplicantsColumnsReset').on('click', function(event) {
                event.preventDefault();
                columnDefaults.forEach(function(column, index) {
                    if (column.toggleable) {
                        column.visible = true;
                        table.column(index).visible(true, false);
                    }
                });
                saveColumnVisibility();
                renderColumnToggles();
                table.columns.adjust().draw(false);
            });

            // Type filter dropdown handler
            $('.type-filter').on('click', function() {
                currentTypeFilter = $(this).text().toLowerCase();

                // Capitalize each word
                const formattedText = currentTypeFilter
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterType').html(formattedText);
                table.ajax.reload(); // Reload with updated type filter
            });

            function visibleFilterCheckboxes(selector) {
                return $(selector).filter(function() {
                    return this.closest('.form-check').style.display !== 'none';
                });
            }

            function updateSelectAllControls(selector, selectAllId, deselectAllId) {
                const checkboxes = visibleFilterCheckboxes(selector);
                const checkedCount = checkboxes.filter(':checked').length;
                $(selectAllId).toggle(checkedCount < checkboxes.length);
                $(deselectAllId).toggle(checkedCount > 0);
            }

            function updateCategoryFilterState(reloadTable) {
                currentCategoryFilters = $('.category-filter:checked').map(function() {
                    return String(this.value);
                }).get();

                $('#showFilterCategory').text(currentCategoryFilters.length ?
                    `Selected Categories (${currentCategoryFilters.length})` : 'All Categories');
                updateSelectAllControls('.category-filter', '#categorySelectAll', '#categoryDeselectAll');
                updateTitleOptions();
                if (reloadTable) {
                    table.ajax.reload();
                }
            }

            function updateTitleOptions() {
                const selectedCategories = currentCategoryFilters;
                const titleSearch = $('#titleSearchInput').val().trim().toLowerCase();
                $('.title-filter').each(function() {
                    const matchesCategory = !selectedCategories.length ||
                        selectedCategories.includes(String(this.dataset.categoryId));
                    const matchesSearch = $(this).next('label').text().toLowerCase().includes(titleSearch);
                    $(this).closest('.form-check').toggle(matchesCategory && matchesSearch);
                    if (!matchesCategory) {
                        this.checked = false;
                    }
                });

                currentTitleFilters = $('.title-filter:checked').map(function() {
                    return String(this.value);
                }).get();
                $('#showFilterTitle').text(currentTitleFilters.length ?
                    `Selected Titles (${currentTitleFilters.length})` : 'All Titles');
                updateSelectAllControls('.title-filter', '#titleSelectAll', '#titleDeselectAll');
            }

            function updateSourceFilterState(reloadTable) {
                currentSourceFilters = $('.source-filter:checked').map(function() {
                    return String(this.value);
                }).get();
                $('#showFilterSource').text(currentSourceFilters.length ?
                    `Selected Sources (${currentSourceFilters.length})` : 'All Sources');
                updateSelectAllControls('.source-filter', '#sourceSelectAll', '#sourceDeselectAll');
                if (reloadTable) {
                    table.ajax.reload();
                }
            }

            $('.category-filter').on('change', function() {
                updateCategoryFilterState(true);
            });

            $('.title-filter').on('change', function() {
                updateTitleOptions();
                table.ajax.reload();
            });

            $('.source-filter').on('change', function() {
                updateSourceFilterState(true);
            });

            $('#categorySelectAll').on('click', function(event) {
                event.preventDefault();
                visibleFilterCheckboxes('.category-filter').prop('checked', true);
                updateCategoryFilterState(true);
            });

            $('#categoryDeselectAll').on('click', function(event) {
                event.preventDefault();
                visibleFilterCheckboxes('.category-filter').prop('checked', false);
                updateCategoryFilterState(true);
            });

            $('#titleSelectAll').on('click', function(event) {
                event.preventDefault();
                visibleFilterCheckboxes('.title-filter').prop('checked', true);
                updateTitleOptions();
                table.ajax.reload();
            });

            $('#titleDeselectAll').on('click', function(event) {
                event.preventDefault();
                visibleFilterCheckboxes('.title-filter').prop('checked', false);
                updateTitleOptions();
                table.ajax.reload();
            });

            $('#sourceSelectAll').on('click', function(event) {
                event.preventDefault();
                visibleFilterCheckboxes('.source-filter').prop('checked', true);
                updateSourceFilterState(true);
            });

            $('#sourceDeselectAll').on('click', function(event) {
                event.preventDefault();
                visibleFilterCheckboxes('.source-filter').prop('checked', false);
                updateSourceFilterState(true);
            });

            updateCategoryFilterState(false);
            updateSourceFilterState(false);
        });

        function updateFilterDropdownControls(selector, selectAllId, deselectAllId) {
            const checkboxes = $(selector).filter(function() {
                return this.closest('.form-check').style.display !== 'none';
            });
            const checkedCount = checkboxes.filter(':checked').length;
            $(selectAllId).toggle(checkedCount < checkboxes.length);
            $(deselectAllId).toggle(checkedCount > 0);
        }

        document.getElementById('categorySearchInput').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const checkboxes = document.querySelectorAll('#categoryList .form-check');

            checkboxes.forEach(function(item) {
                const label = item.querySelector('label').innerText.toLowerCase();
                item.style.display = label.includes(searchValue) ? '' : 'none';
            });
            updateFilterDropdownControls('#categoryList .category-filter', '#categorySelectAll',
                '#categoryDeselectAll');
        });

        document.getElementById('titleSearchInput').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const checkboxes = document.querySelectorAll('#titleList .form-check');
            const selectedCategories = $('.category-filter:checked').map(function() {
                return String(this.value);
            }).get();

            checkboxes.forEach(function(item) {
                const categoryId = String(item.querySelector('.title-filter').dataset.categoryId);
                const label = item.querySelector('label').innerText.toLowerCase();
                const matchesCategory = !selectedCategories.length || selectedCategories.includes(
                    categoryId);
                item.style.display = matchesCategory && label.includes(searchValue) ? '' : 'none';
            });
            updateFilterDropdownControls('#titleList .title-filter', '#titleSelectAll', '#titleDeselectAll');
        });

        document.getElementById('sourceSearchInput').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            document.querySelectorAll('#sourceList .form-check').forEach(function(item) {
                const label = item.querySelector('label').innerText.toLowerCase();
                item.style.display = label.includes(searchValue) ? '' : 'none';
            });
            updateFilterDropdownControls('#sourceList .source-filter', '#sourceSelectAll', '#sourceDeselectAll');
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
                table.page(currentPage - 2).draw('page'); // Move to the previous page
            } else if (page === 'next' && currentPage < totalPages) {
                table.page(currentPage).draw('page'); // Move to the next page
            } else if (typeof page === 'number' && page !== currentPage) {
                table.page(page - 1).draw('page'); // Move to the selected page
            }
        }

        // Function to show the notes modal
        function showNotesModal(applicantID, notes, applicantName, applicantPostcode) {
            const modalId = `showNotesModal-${applicantID}`;
            const loaderId = `${modalId}-loader`;
            const contentId = `${modalId}-content`;

            // Remove any existing instance of this modal
            $(`#${modalId}`).remove();

            // Append new unique modal
            $('body').append(`
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}Label">Applicant Notes</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div id="${loaderId}" class="spinner-border text-primary my-3" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div id="${contentId}" class="d-none text-start"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();

            // Simulate loading delay
            setTimeout(() => {
                $(`#${loaderId}`).hide();
                $(`#${contentId}`).removeClass('d-none').html(`
                    <p><strong>Applicant Name:</strong> ${applicantName}</p>
                    <p><strong>Postcode:</strong> ${applicantPostcode}</p>
                    <p><strong>Notes Detail:</strong><br>${notes.replace(/\n/g, '<br>')}</p>
                `);
            }, 300);
        }

        // Function to show the notes modal
        function viewNotesHistory(id) {
            const modalId = `viewNotesHistoryModal-${id}`;
            const loaderId = `${modalId}-loader`;
            const contentId = `${modalId}-content`;

            // Remove any existing modal with same ID to keep it clean
            $(`#${modalId}`).remove();

            // Append the unique modal HTML
            $('body').append(`
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable modal-dialog-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}Label">Applicant Notes History</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div id="${loaderId}" class="spinner-border text-primary my-3" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div id="${contentId}" class="d-none text-start"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();

            // Make the AJAX request
            $.ajax({
                url: '{{ route('getModuleNotesHistory') }}',
                type: 'GET',
                data: {
                    id: id,
                    module: 'Applicant'
                },
                success: function(response) {
                    let notesHtml = '';

                    if (response.data.length === 0) {
                        notesHtml = '<p>No record found.</p>';
                    } else {
                        response.data.forEach(function(note) {
                            const created = moment(note.created_at).format('DD MMM YYYY, h:mmA');
                            const status = note.status;
                            const notes = note.details;
                            const statusClass = (status == 1) ? 'bg-success' : 'bg-dark';
                            const statusText = (status == 1) ? 'Active' : 'Inactive';

                            notesHtml += `
                                <div class="note-entry">
                                    <p><strong>Dated:</strong> ${created} &nbsp;&nbsp; 
                                    <span class="badge ${statusClass}">${statusText}</span></p>
                                    <p><strong>Notes Detail:</strong><br>${notes.replace(/\n/g, '<br>')}</p>
                                </div>
                                <hr>
                            `;
                        });
                    }

                    // Hide loader and show notes
                    $(`#${loaderId}`).hide();
                    $(`#${contentId}`).removeClass('d-none').html(notesHtml);
                },
                error: function() {
                    $(`#${loaderId}`).hide();
                    $(`#${contentId}`).removeClass('d-none').html(
                        '<p>There was an error retrieving the notes. Please try again later.</p>');
                }
            });
        }

        function showDetailsModal(applicantId, name, email, secondaryEmail, postcode, landline, phone, jobTitle,
            jobCategory, jobSource, createdAt, status) {
            const modalId = `showDetailsModal-${applicantId}`;
            const labelId = `${modalId}Label`;
            const contentId = `${modalId}-body`;

            // Remove existing instance of the same modal to avoid duplication
            $(`#${modalId}`).remove();

            // Append modal HTML with unique ID
            $('body').append(`
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${labelId}" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${labelId}">Applicant Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="${contentId}">
                                <!-- Details will be inserted dynamically -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            // Set the details table
            $(`#${contentId}`).html(`
                <table class="table table-bordered">
                    <tr><th>Applicant ID</th><td>${applicantId}</td></tr>
                    <tr><th>Created At</th><td>${createdAt}</td></tr>
                    <tr><th>Name</th><td>${name}</td></tr>
                    <tr><th>Phone</th><td>${phone}</td></tr>
                    <tr><th>Landline</th><td>${landline}</td></tr>
                    <tr><th>Postcode</th><td>${postcode}</td></tr>
                    <tr><th>Email (Primary)</th><td>${email}</td></tr>
                    <tr><th>Email (Secondary)</th><td>${secondaryEmail}</td></tr>
                    <tr><th>Job Category</th><td>${jobCategory}</td></tr>
                    <tr><th>Job Title</th><td>${jobTitle}</td></tr>
                    <tr><th>Job Source</th><td>${jobSource}</td></tr>
                    <tr><th>Status</th><td>${status}</td></tr>
                </table>
            `);

            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();
        }
    </script>
@endsection
@endsection

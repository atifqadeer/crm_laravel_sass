@extends('layouts.vertical', ['title' => 'Category Wise Resources List', 'subTitle' => 'Resources'])
{{-- The layout outputs @yield('css') in head-css; a 'style' section is never rendered --}}
@section('css')
    <style>
        .dropdown-toggle::after {
            display: none !important;
        }

        /* table.dataTable.no-footer {
                border-bottom: none !important;
            } */

        #applicants_table.table>tbody>tr:last-child>td {
            border-bottom: 0 !important;
        }

        /* Nursing home experience marker on the first (checkbox) cell */
        #applicants_table tbody td.triangle-green {
            border-left: 5px solid #5cc184 !important;
        }

        #applicants_table tbody td.triangle-red {
            border-left: 5px solid #e96767 !important;
        }
    </style>
@endsection
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row justify-content-end">
                        <div class="col-lg-12">
                            <div class="text-md-end mt-3">
                                <!-- Button Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="statusFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterStatus">Interested</span>
                                    </button>
                                    <div class="dropdown-menu p-2" aria-labelledby="statusFilterDropdown">
                                        <div class="form-check">
                                            <input class="form-check-input status-filter" type="checkbox" value="interested"
                                                id="status-interested" checked>
                                            <label class="form-check-label" for="status-interested">Interested</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input status-filter" type="checkbox"
                                                value="not interested" id="status-not-interested">
                                            <label class="form-check-label" for="status-not-interested">Not
                                                Interested</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input status-filter" type="checkbox" value="blocked"
                                                id="status-blocked">
                                            <label class="form-check-label" for="status-blocked">Blocked</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input status-filter" type="checkbox"
                                                value="have nursing home exp" id="status-nursing-home-exp">
                                            <label class="form-check-label" for="status-nursing-home-exp">Have Nursing Home
                                                Exp</label>
                                        </div>
                                    </div>
                                </div>
                                <!-- Date Range filter -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dateRangeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterCategory">All
                                            Category</span>
                                    </button>

                                    <div class="dropdown-menu filter-dropdowns" aria-labelledby="dropdownMenuButton1">
                                        <!-- Search input -->
                                        <input type="text" class="form-control mb-2" id="categorySearchInput"
                                            placeholder="Search category...">

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
                                        id="typeFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterType">All Types</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="typeFilterDropdown">
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

                                <!-- Button Dropdown -->
                                {{-- <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i> Export
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                        <a class="dropdown-item" href="{{ route('applicantsExport', ['type' => 'allBlocked']) }}">Export All Data</a>
                                    </div>
                                </div> --}}
                                <!-- Button Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-menu-line me-1"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                        <a class="dropdown-item" href="#" id="markNursingHomeBtn"><span
                                                class="nav-icon">
                                                <i class="ri-check-line fs-16"></i>
                                            </span> Mark as Nursing Home Exp</a>
                                        <a class="dropdown-item" href="#" id="markNoNursingHomeBtn"><span
                                                class="nav-icon">
                                                <i class="ri-close-line fs-16"></i>
                                            </span> Mark as No Nursing Home Exp</a>
                                    </div>
                                </div>
                                <!-- Add Updated Sales Filter Button -->
                                {{-- <button class="btn btn-success my-1" title="Mark selected as having nursing home experience" type="button">
                                    <span class="nav-icon">
                                        <i class="ri-check-line fs-16"></i>
                                    </span>
                                    Mark as Nursing Home Exp
                                </button>
                                <button class="btn btn-danger my-1" title="Mark selected as having no nursing home experience" type="button">
                                        <span class="nav-icon">
                                        <i class="ri-close-line fs-16"></i>
                                    </span>
                                    Mark as No Nursing Home Exp
                                </button> --}}
                            </div>
                        </div><!-- end col-->
                    </div>
                    <!-- Custom Search Bar -->
                    <div class="row justify-content-start">
                        <div class="col-lg-3">
                            <div class="text-md-start mt-3 pt-1">
                                <div class="input-group">
                                    <!-- Use padding-right to prevent text from overlapping the clear icon -->
                                    <input type="text" id="customSearchInput" class="form-control"
                                        placeholder="Search ..." style="padding-right: 30px;">
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body p-3">
                    <div id="columnsToolbar" class="dropdown d-inline">
                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button"
                            id="dropdownMenuApplicantColumns" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ri-layout-column-line me-1"></i> Columns
                        </button>
                        <div class="dropdown-menu filter-dropdowns p-2" aria-labelledby="dropdownMenuApplicantColumns"
                            style="min-width: 230px;">
                            <div class="d-flex justify-content-between align-items-center px-1 mb-2">
                                <a href="#" id="columnsSelectAll" class="text-primary small fw-semibold">Show
                                    All</a>
                                <a href="#" id="columnsResetDefault" class="text-secondary small fw-semibold">Reset
                                    Default</a>
                            </div>
                            <div id="columnsList" style="max-height: 280px; overflow-y: auto;"></div>
                        </div>
                    </div>
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
                                    <th width="15%">Phone / Landline</th>
                                    <th>Resume (Applicant)</th>
                                    <th>Resume (CRM)</th>
                                    <th>Experience</th>
                                    <th>Source</th>
                                    <th width="15%">Notes</th>
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
    <link rel="stylesheet" href="{{ asset('css/daterangepicker.css') }}" />
    <script src="{{ asset('js/daterangepicker.min.js') }}"></script>

    <script>
        $(document).ready(function() {

            // Store the current filter in a variable
            var currentTypeFilter = '';
            var currentStatusFilters = ['interested']; // default: Interested
            var currentCategoryFilters = [];
            var currentTitleFilters = [];
            var currentDateRangeFilter = '';

            function updateTitleVisibility() {
                const searchValue = $('#titleSearchInput').val().trim().toLowerCase();

                $('#titleList .form-check').each(function() {
                    const $row = $(this);
                    const $checkbox = $row.find('.title-filter');
                    const categoryId = String($checkbox.data('category-id'));
                    const matchesCategory = currentCategoryFilters.length === 0 ||
                        currentCategoryFilters.includes(categoryId);
                    const matchesSearch = $row.find('label').text().trim().toLowerCase().includes(
                        searchValue);

                    $row.toggle(matchesCategory && matchesSearch);
                });
            }

            function updateTitleFilterLabel() {
                $('#showFilterTitle').text(
                    currentTitleFilters.length ? 'Selected Titles (' + currentTitleFilters.length + ')' :
                    'All Titles'
                );
            }

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

            const columnConfig = [{
                    title: '#',
                    toggleable: false
                },
                {
                    title: 'Date',
                    default: true
                },
                {
                    title: 'Sent By',
                    default: true
                },
                {
                    title: 'Name',
                    default: true
                },
                {
                    title: 'Email',
                    default: true
                },
                {
                    title: 'Title',
                    default: true
                },
                {
                    title: 'Category',
                    default: true
                },
                {
                    title: 'PostCode',
                    default: true
                },
                {
                    title: 'Phone / Landline',
                    default: true
                },
                {
                    title: 'Resume (Applicant)',
                    default: true
                },
                {
                    title: 'Resume (CRM)',
                    default: true
                },
                {
                    title: 'Experience',
                    default: true
                },
                {
                    title: 'Source',
                    default: true
                },
                {
                    title: 'Notes',
                    default: true
                },
                {
                    title: 'Status',
                    default: true
                },
                {
                    title: 'Action',
                    toggleable: false
                }
            ];
            const COLUMN_VISIBILITY_STORAGE_KEY = 'resources_category_applicants_column_visibility_v1';

            function loadColumnVisibility() {
                let stored = {};
                try {
                    stored = JSON.parse(localStorage.getItem(COLUMN_VISIBILITY_STORAGE_KEY)) || {};
                } catch (error) {
                    stored = {};
                }

                return columnConfig.map(function(column, index) {
                    if (column.toggleable === false) {
                        return true;
                    }
                    return Object.prototype.hasOwnProperty.call(stored, index) ?
                        !!stored[index] :
                        !!column.default;
                });
            }

            function saveColumnVisibility() {
                const stored = {};
                columnConfig.forEach(function(column, index) {
                    if (column.toggleable !== false) {
                        stored[index] = !!columnVisibility[index];
                    }
                });
                localStorage.setItem(COLUMN_VISIBILITY_STORAGE_KEY, JSON.stringify(stored));
            }

            let columnVisibility = loadColumnVisibility();

            function renderColumnsDropdown() {
                const $list = $('#columnsList').empty();
                columnConfig.forEach(function(column, index) {
                    if (column.toggleable === false) {
                        return;
                    }

                    const checked = columnVisibility[index] ? 'checked' : '';
                    $list.append(`
                        <div class="form-check">
                            <input class="form-check-input column-toggle" type="checkbox"
                                id="applicant_column_${index}" data-column-index="${index}" ${checked}>
                            <label class="form-check-label" for="applicant_column_${index}">${column.title}</label>
                        </div>
                    `);
                });
            }

            function getHiddenColumnIndices() {
                return columnVisibility
                    .map(function(visible, index) {
                        return visible ? null : index;
                    })
                    .filter(function(index) {
                        return index !== null;
                    });
            }

            renderColumnsDropdown();

            // Initialize DataTable with server-side processing
            var table = $('#applicants_table').DataTable({
                processing: false, // Disable default processing state
                serverSide: true, // Enables server-side processing
                ajax: {
                    url: @json(route('getResourcesCategoryWised')), // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        d.status_filter = currentStatusFilters.length ? currentStatusFilters : ['all'];
                        d.type_filter =
                            currentTypeFilter; // Send the current filter value as a parameter
                        d.category_filter =
                            currentCategoryFilters; // Send the current filter value as a parameter
                        d.title_filter =
                            currentTitleFilters; // Send the current filter value as a parameter
                        d.date_range_filter =
                            currentDateRangeFilter; // Send the current filter value as a parameter
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
                        data: "checkbox",
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        name: 'latest_note_created'
                    },
                    {
                        data: 'user_name',
                        name: 'users.name'
                    },
                    {
                        data: 'applicant_name',
                        name: 'applicants.applicant_name'
                    },
                    {
                        data: 'applicantEmail',
                        name: 'applicantEmail'
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
                        data: 'applicantPhone',
                        name: 'applicantPhone'
                    },
                    {
                        data: 'applicant_resume',
                        name: 'applicants.applicant_cv',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'crm_resume',
                        name: 'applicants.updated_cv',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'applicant_experience',
                        name: 'applicants.applicant_experience'
                    },
                    {
                        data: 'job_source',
                        name: 'job_sources.name'
                    },
                    {
                        data: 'applicant_notes',
                        name: 'applicants.applicant_notes',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'customStatus',
                        name: 'customStatus',
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
                        targets: getHiddenColumnIndices(),
                        visible: false
                    },
                    {
                        targets: 9, // Column index for 'job_details'
                        createdCell: function(td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center'); // Center the text in this column
                        }
                    },
                    {
                        targets: 10, // Column index for 'job_details'
                        createdCell: function(td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center'); // Center the text in this column
                        }
                    },
                    {
                        targets: 14, // Column index for 'job_details'
                        createdCell: function(td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center'); // Center the text in this column
                        }
                    },
                    {
                        targets: 15, // Column index for 'job_details'
                        createdCell: function(td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center'); // Center the text in this column
                        }
                    }
                ],
                order: [
                    [1, 'desc']
                ], // Date (latest note), newest first
                rowId: function(data) {
                    return 'row_' + data
                        .id; // Assign a unique ID to each row using the 'id' field from the data
                },
                createdRow: function(row, data, dataIndex) {
                    const firstCell = $('td:eq(0)', row); // first column only

                    if (data.have_nursing_home_experience == 1) {
                        firstCell.addClass('triangle-green')
                            .attr('title', 'Has Nursing Home Experience');
                    } else if (data.have_nursing_home_experience == 0) {
                        firstCell.addClass('triangle-red')
                            .attr('title', 'No Nursing Home Experience');
                    }
                },
                dom: '<"d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2"l>rtip',
                initComplete: function() {
                    const api = this.api();
                    $(api.table().container())
                        .find('.dataTables_length')
                        .after($('#columnsToolbar'));
                },
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

            // Search logic helper
            function handleCustomSearch() {
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

            // Show/Hide Clear button
            $('#customSearchInput').on('keyup change', function() {
                if ($(this).val().trim() !== '') {
                    $('#customClearBtn').removeClass('d-none');
                } else {
                    $('#customClearBtn').addClass('d-none');
                }
            });

            // Clear Button Event
            $('#customClearBtn').on('click', function() {
                $('#customSearchInput').val('');
                $(this).addClass('d-none');
                table.search('').draw();
            });

            // Column visibility dropdown handlers
            $(document).on('change', '#columnsList .column-toggle', function() {
                const index = Number($(this).data('column-index'));
                columnVisibility[index] = this.checked;
                table.column(index).visible(this.checked);
                saveColumnVisibility();
            });

            $('#columnsSelectAll').on('click', function(event) {
                event.preventDefault();
                columnConfig.forEach(function(column, index) {
                    if (column.toggleable !== false) {
                        columnVisibility[index] = true;
                        table.column(index).visible(true, false);
                    }
                });
                table.columns.adjust().draw(false);
                saveColumnVisibility();
                renderColumnsDropdown();
            });

            $('#columnsResetDefault').on('click', function(event) {
                event.preventDefault();
                columnConfig.forEach(function(column, index) {
                    if (column.toggleable !== false) {
                        columnVisibility[index] = !!column.default;
                        table.column(index).visible(columnVisibility[index], false);
                    }
                });
                table.columns.adjust().draw(false);
                saveColumnVisibility();
                renderColumnsDropdown();
            });

            // Status filter dropdown handler
            $('.status-filter').on('change', function() {
                if (this.value === '') {
                    if (!this.checked) {
                        $(this).prop('checked', true);
                    }
                    $('.status-filter').not(this).prop('checked', false);
                    currentStatusFilters = [];
                } else {
                    currentStatusFilters = $('.status-filter:checked')
                        .not('#all-statuses')
                        .map(function() {
                            return this.value;
                        }).get();

                    if (currentStatusFilters.length === 0) {
                        $('#all-statuses').prop('checked', true);
                    } else {
                        $('#all-statuses').prop('checked', false);
                    }
                }

                const selectedLabels = $('.status-filter:checked')
                    .map(function() {
                        return $(this).next('label').text().trim();
                    }).get();

                $('#showFilterStatus').text(
                    currentStatusFilters.length === 0 ?
                    'All Statuses' :
                    selectedLabels.length === 1 ?
                    selectedLabels[0] :
                    'Selected Statuses (' + selectedLabels.length + ')'
                );
                table.ajax.reload();
            });
            // Status filter dropdown handler
            $('.date-range-filter').on('click', function() {
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
            /*** Category filter handler ***/
            $('.category-filter').on('change', function() {
                currentCategoryFilters = $('.category-filter:checked')
                    .map(function() {
                        return String(this.value);
                    }).get();

                // Update dropdown display text
                $('#showFilterCategory').text(currentCategoryFilters.length ?
                    'Selected Categories (' + currentCategoryFilters.length + ')' :
                    'All Category');

                // Keep title selections limited to titles belonging to the selected categories.
                $('.title-filter').each(function() {
                    const categoryId = String($(this).data('category-id'));
                    if (currentCategoryFilters.length && !currentCategoryFilters.includes(
                            categoryId)) {
                        $(this).prop('checked', false);
                    }
                });
                currentTitleFilters = $('.title-filter:checked').map(function() {
                    return String(this.value);
                }).get();
                updateTitleFilterLabel();
                updateTitleVisibility();

                // Trigger DataTable reload with the selected filters
                table.ajax.reload();
            });
            /*** Title Filter Handler ***/
            $('.title-filter').on('change', function() {
                currentTitleFilters = $('.title-filter:checked').map(function() {
                    return String(this.value);
                }).get();
                updateTitleFilterLabel();

                // Trigger DataTable reload with the selected filters
                table.ajax.reload();
            });

            $('#titleSearchInput').on('input', updateTitleVisibility);
            updateTitleVisibility();
        });

        document.getElementById('categorySearchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const checkboxes = document.querySelectorAll('#categoryList .form-check');

            checkboxes.forEach(function(item) {
                const label = item.querySelector('label').innerText.toLowerCase();
                item.style.display = label.includes(searchValue) ? '' : 'none';
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
                table.page(currentPage - 2).draw('page'); // Move to the previous page
            } else if (page === 'next' && currentPage < totalPages) {
                table.page(currentPage).draw('page'); // Move to the next page
            } else if (typeof page === 'number' && page !== currentPage) {
                table.page(page - 1).draw('page'); // Move to the selected page
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
                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-top modal-lg">
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
                url: '{{ route('getModuleNotesHistory') }}',
                type: 'GET',
                data: {
                    id: id,
                    module: 'Applicant'
                },
                success: function(response) {
                    let notesHtml = '';

                    if (response.data.length === 0) {
                        notesHtml = '<p class="text-center">No record found.</p>';
                    } else {
                        response.data.forEach(function(note) {
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
                error: function(xhr, status, error) {
                    console.log("Error fetching notes history: " + error);
                    $('#' + modalID + ' .modal-body').html(
                        '<p class="text-danger text-center">Error loading notes. Please try again later.</p>'
                    );
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
                                    <button type="button" class="btn btn-success saveShortNotesButton" data-id="${applicantID}">
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
            $('#' + modalID + ' .saveShortNotesButton').off('click').on('click', function() {
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
                    $('#detailsTextarea_' + applicantID).on('input', function() {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid')
                                .next('.invalid-feedback').remove();
                        }
                    });

                    $('#reasonDropdown_' + applicantID).on('change', function() {
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

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
                );

                // Send via AJAX
                $.ajax({
                    url: '{{ route('storeShortNotes') }}',
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        details: notes,
                        reason: reason,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        toastr.success('Notes saved successfully!');
                        $('#' + modalID).modal('hide');
                        $('#shortNotesForm_' + applicantID)[0].reset();
                        $('#applicants_table').DataTable().ajax.reload();
                    },
                    error: function() {
                        toastr.error('An error occurred while saving notes.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
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

        function showDetailsModal(applicantId, name, email, secondaryEmail, postcode, landline, phone, jobTitle,
            jobCategory, jobSource, posted_date, status) {
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
                                    <button type="button" class="btn btn-success" id="saveNotesButton">Save</button>
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
            $('#saveNotesButton').off('click').on('click', function() {
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
                    $('#detailsTextarea').on('input', function() {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    $('#statusDropdown').on('change', function() {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // AJAX request
                $.ajax({
                    url: '{{ route('changeStatus') }}',
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        details: notes,
                        status: selectedStatus,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        toastr.success('Applicant status changed successfully!');
                        $('#changeStatusModal').modal('hide');
                        $('#changeStatusForm')[0].reset();
                        $('#detailsTextarea, #statusDropdown').removeClass('is-valid is-invalid');
                        $('#detailsTextarea, #statusDropdown').next('.invalid-feedback').remove();

                        $('#applicants_table').DataTable().ajax.reload();
                    },
                    error: function(xhr) {
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
        $('#markNursingHomeBtn').on('click', function() {
            // Get all the selected checkboxes
            var selectedCheckboxes = [];
            $('.applicant_checkbox:checked').each(function() {
                selectedCheckboxes.push($(this)
                    .val()); // Push the value of the checked checkboxes to the array
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
                    success: function(response) {
                        // Hide the rows that were successfully updated
                        selectedCheckboxes.forEach(function(rowId) {
                            $('#' + rowId).fadeOut(500); // 500ms = half-second fade

                            // 2. Uncheck the checkbox based on matching value
                            $('input.applicant_checkbox[value="' + rowId + '"]').prop('checked',
                                false);
                        });

                        toastr.success('Marked nursing home experience successfully!');
                        $('#applicants_table').DataTable().ajax.reload(); // Reload the DataTable
                    },
                    error: function(error) {
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
        $('#markNoNursingHomeBtn').on('click', function() {
            // Get all the selected checkboxes
            var selectedCheckboxes = [];
            $('.applicant_checkbox:checked').each(function() {
                selectedCheckboxes.push($(this)
                    .val()); // Push the value of the checked checkboxes to the array
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
                    success: function(response) {
                        // Hide the rows that were successfully updated
                        selectedCheckboxes.forEach(function(rowId) {
                            $('#' + rowId).addClass('marked-no-nursing-home');

                            // 2. Uncheck the checkbox based on matching value
                            $('input.applicant_checkbox[value="' + rowId + '"]').prop('checked',
                                false);
                        });

                        toastr.success('Marked no nursing home experience successfully!');
                        $('#applicants_table').DataTable().ajax.reload(); // Reload the DataTable

                    },
                    error: function(error) {
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

                fetch('{{ route('applicants.uploadCv') }}', {
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

        function triggerCrmFileInput(id) {
            // Store the applicant ID when the button is clicked
            applicantId = id;

            // Trigger the file input click event
            document.getElementById('crmfileInput').click();
        }

        function crmuploadFile() {
            const fileInput = document.getElementById('crmfileInput');
            const file = fileInput.files[0]; // Get the selected file

            if (file && applicantId) {
                // Create a FormData object to send the file along with the applicant ID
                const formData = new FormData();
                formData.append('resume', file);
                formData.append('applicant_id', applicantId); // Append applicant ID

                // Include CSRF token if you're using Laravel or any framework that requires CSRF protection
                formData.append('_token', '{{ csrf_token() }}'); // CSRF token

                // You can send the file to the server using an AJAX request or any method you prefer
                // Example using Fetch API
                fetch('{{ route('applicants.crmuploadCv') }}', {
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

@extends('layouts.vertical', ['title' => 'Indirect Resources List', 'subTitle' => 'Resources'])
@section('style')
    <style>
        .dropdown-toggle::after {
            display: none !important;
        }

        table.dataTable.no-footer {
            border-bottom: none !important;
        }

        #addUpdatedSalesFilter {
            background-color: #6c757d;
            /* gray */
            border-color: #6c757d;
            /* gray */
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }
    </style>
@endsection
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row justify-content-between">
                        <div class="col-lg-12">
                            <div class="text-md-end mt-3">
                                <strong>Sale Count: </strong> <span class="badge bg-primary p-2" id="saleCount">0</span>
                                <!-- Date Range filter -->
                                <div class="d-inline">
                                    <input type="text" id="dateRangePicker" class="form-control d-inline-block"
                                        style="width: 220px; display: inline-block;" placeholder="Select date range"
                                        readonly />
                                    <button class="btn btn-outline-primary my-1" type="button" id="clearDateRange"
                                        title="Clear Date Range">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
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
                                        <!-- Select/Deselect All -->
                                        <div class="d-flex justify-content-end px-1 mb-1">
                                            <a href="#" class="filter-select-all text-primary small fw-semibold me-2"
                                                data-target=".category-filter">Select All</a>
                                            <a href="#" class="filter-deselect-all text-danger small fw-semibold"
                                                data-target=".category-filter" style="display:none">Deselect All</a>
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
                                        <!-- Select/Deselect All (applies to the titles currently listed) -->
                                        <div class="d-flex justify-content-end px-1 mb-1">
                                            <a href="#" class="filter-select-all text-primary small fw-semibold me-2"
                                                data-target=".title-filter">Select All</a>
                                            <a href="#" class="filter-deselect-all text-danger small fw-semibold"
                                                data-target=".title-filter" style="display:none">Deselect All</a>
                                        </div>

                                        <!-- Scrollable checkbox list: narrowed to the selected categories and type -->
                                        <div id="titleList">
                                            @foreach ($jobTitles as $title)
                                                <div class="form-check" data-title-category="{{ $title->job_category_id }}"
                                                    data-title-type="{{ strtolower((string) $title->type) }}">
                                                    <input class="form-check-input title-filter" type="checkbox"
                                                        value="{{ $title->id }}" id="title_{{ $title->id }}"
                                                        data-title-id="{{ $title->id }}">
                                                    <label class="form-check-label"
                                                        for="title_{{ $title->id }}">{{ ucwords($title->name) }}</label>
                                                </div>
                                            @endforeach
                                            <div class="text-muted small px-1 d-none" id="titleListEmpty">
                                                No titles for the selected category and type.
                                            </div>
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
                                                class="filter-select-all text-primary small fw-semibold me-2"
                                                data-target=".source-filter">Select All</a>
                                            <a href="#" id="sourceDeselectAll"
                                                class="filter-deselect-all text-danger small fw-semibold"
                                                data-target=".source-filter" style="display:none">Deselect All</a>
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
                                        id="dropdownMenuButton4" data-bs-toggle="dropdown" aria-expanded="false">
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
                    <!-- Columns Visibility Dropdown — moved via JS (initComplete) into the same
                                 flex row as DataTables' own "Show X entries" length control below
                                 (same as sales/list). -->
                    <div id="columnsToolbar" class="dropdown d-inline">
                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button"
                            id="dropdownMenuColumns" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            aria-expanded="false">
                            <i class="ri-layout-column-line me-1"></i> Columns
                        </button>
                        <div class="dropdown-menu filter-dropdowns p-2" aria-labelledby="dropdownMenuColumns"
                            style="min-width: 230px;">
                            <div class="d-flex justify-content-end align-items-center px-1 mb-2">
                                <a href="#" id="showAllColumns" class="text-primary small fw-semibold">Show All</a>
                            </div>
                            <div id="columnToggleList" style="max-height: 280px; overflow-y: auto;">
                                {{-- One checkbox per column is added by JS from the table's columns. --}}
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="applicants_table" class="table align-middle mb-3">
                            <thead class="bg-light-subtle">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Name (Applicant)</th>
                                    <th>Email</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>PostCode</th>
                                    <th width="15%">Phone / Landline</th>
                                    @canany(['applicant-download-resume'])
                                        <th>Resume (Applicant)</th>
                                        <th>Resume (CRM)</th>
                                    @endcanany
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
        let isButtonClicked = false;

        $(function() {
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
            $('#dateRangePicker').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format(
                    'YYYY-MM-DD'));
                window.currentDateRangeFilter = picker.startDate.format('YYYY-MM-DD') + '|' + picker.endDate
                    .format('YYYY-MM-DD');
                $('#showDateRange').html($(this).val());
                $('#applicants_table').DataTable().ajax.reload();
            });

            // On cancel
            $('#dateRangePicker').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                window.currentDateRangeFilter = '';
                $('#showDateRange').html('All Data');
                $('#applicants_table').DataTable().ajax.reload();
            });

            // Clear button
            $('#clearDateRange').on('click', function() {
                $('#dateRangePicker').val('');
                window.currentDateRangeFilter = '';
                $('#showDateRange').html('All Data');
                $('#applicants_table').DataTable().ajax.reload();
            });

            // Toggle filter button
            $('#addUpdatedSalesFilter').on('click', function() {
                isButtonClicked = !isButtonClicked;
                $('#applicants_table').DataTable().ajax.reload();
            });
        });

        $(document).ready(function() {
            // Store the current filter in a variable
            let currentFilter = '';
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

            // Centres a column's cells. Set on the column itself (resumes, status, action) rather
            // than by index, because the resume columns only exist for some users.
            function centerCell(td) {
                $(td).css('text-align', 'center');
            }

            // Initialize DataTable with server-side processing
            var table = $('#applicants_table').DataTable({
                processing: false, // Disable default processing state
                serverSide: true, // Enables server-side processing
                ajax: {
                    url: @json(route('getResourcesIndirectApplicants')), // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        // Add the current filter to the request parameters
                        d.type_filter =
                            currentTypeFilter; // Send the current filter value as a parameter
                        d.date_range_filter = window
                            .currentDateRangeFilter; // Send the current filter value as a parameter
                        d.category_filter =
                            currentCategoryFilters; // Send the current filter value as a parameter
                        d.title_filter =
                            currentTitleFilters; // Send the current filter value as a parameter
                        d.status_filter = currentFilter;
                        d.source_filter = currentSourceFilters;

                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }

                        var button = $('#addUpdatedSalesFilter');

                        if (isButtonClicked) {
                            button.css('background-color', '#358b57');
                            d.updated_sales_filter = true;
                        } else {
                            button.css('background-color', '#6c757d'); // explicitly set gray
                            delete d.updated_sales_filter;
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
                        data: 'updated_at',
                        name: 'applicants.updated_at'
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
                    // Must match the <thead>: the resume columns only exist for users who may download resumes.
                    @canany(['applicant-download-resume'])
                        {
                            data: 'applicant_resume',
                            name: 'applicants.applicant_cv',
                            orderable: false,
                            searchable: false,
                            createdCell: centerCell
                        }, {
                            data: 'crm_resume',
                            name: 'applicants.updated_cv',
                            orderable: false,
                            searchable: false,
                            createdCell: centerCell
                        },
                    @endcanany {
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
                        searchable: false
                    },
                    {
                        data: 'customStatus',
                        name: 'customStatus',
                        orderable: false,
                        searchable: false,
                        createdCell: centerCell
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        createdCell: centerCell
                    }
                ],
                rowId: function(data) {
                    return 'row_' + data
                        .id; // Assign a unique ID to each row using the 'id' field from the data
                },
                // 'l' (length control) wrapped in its own flex row so the "Columns" button
                // (moved here in initComplete below) lines up beside it, as on sales/list.
                dom: '<"d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2"l>rtip',
                initComplete: function() {
                    const api = this.api();
                    $(api.table().container())
                        .find('.dataTables_length')
                        .after($('#columnsToolbar'));
                },
                drawCallback: function(settings) {
                    const api = this.api();
                    const json = api.ajax.json(); // ✅ Access full JSON response from server

                    if (json && json.total_sale_count !== undefined) {
                        $('#saleCount').text(json.total_sale_count);
                    } else {
                        $('#saleCount').text(0);
                    }

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

            /*** Show / hide table columns (remembered in this browser) ***/
            const COLUMN_STORAGE_KEY = 'resources.indirect.hiddenColumns';
            const fixedColumns = ['DT_RowIndex', 'action']; // always shown

            function loadHiddenColumns() {
                try {
                    const saved = JSON.parse(localStorage.getItem(COLUMN_STORAGE_KEY));
                    return Array.isArray(saved) ? saved : [];
                } catch (e) {
                    return [];
                }
            }

            function saveHiddenColumns() {
                const hidden = [];
                table.columns().every(function() {
                    if (!this.visible()) {
                        hidden.push(this.dataSrc());
                    }
                });
                try {
                    localStorage.setItem(COLUMN_STORAGE_KEY, JSON.stringify(hidden));
                } catch (e) {
                    // Storage unavailable (private window, blocked): the choice just isn't remembered.
                }
            }

            function buildColumnToggleList() {
                const $list = $('#columnToggleList').empty();

                table.columns().every(function(index) {
                    if (fixedColumns.includes(this.dataSrc())) {
                        return;
                    }

                    const id = 'column_toggle_' + index;
                    $list.append(
                        $('<div class="form-check">').append(
                            $('<input type="checkbox" class="form-check-input column-toggle">')
                            .attr('id', id)
                            .attr('data-column', index)
                            .prop('checked', this.visible()),
                            $('<label class="form-check-label">')
                            .attr('for', id)
                            .text($(this.header()).text().trim() || this.dataSrc())
                        )
                    );
                });
            }

            // Re-apply the columns this user hid last time (stored by data key, not position,
            // because the resume columns only exist for users who may download resumes).
            const savedHiddenColumns = loadHiddenColumns();
            table.columns().every(function() {
                if (savedHiddenColumns.includes(this.dataSrc()) && !fixedColumns.includes(this.dataSrc())) {
                    this.visible(false, false);
                }
            });
            table.columns.adjust();
            buildColumnToggleList();

            $(document).on('change', '.column-toggle', function() {
                table.column($(this).data('column')).visible(this.checked);
                saveHiddenColumns();
            });

            $('#showAllColumns').on('click', function(e) {
                e.preventDefault();
                table.columns().visible(true);
                saveHiddenColumns();
                buildColumnToggleList();
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

            // Type filter dropdown handler
            $('.type-filter').on('click', function() {
                currentTypeFilter = $(this).text().toLowerCase();

                // Capitalize each word
                const formattedText = currentTypeFilter
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterType').html(formattedText);

                // Titles follow the selected type (and categories).
                refreshTitleOptions();

                table.ajax.reload(); // Reload with updated type filter
            });

            // Status filter dropdown handler
            $('.status-filter').on('click', function() {
                currentFilter = $(this).text().toLowerCase();

                // Capitalize each word
                const formattedText = currentFilter
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterStatus').html(formattedText);
                table.ajax.reload(); // Reload with updated status filter
            });

            /*** Category / Title / Source filters ***/
            // Ids of the checked boxes for a filter class, e.g. '.category-filter' + 'category-id'.
            function checkedIds(selector, dataKey) {
                return $(selector + ':checked').map(function() {
                    return $(this).data(dataKey);
                }).get();
            }

            function setFilterLabel(labelSelector, count, noun) {
                $(labelSelector).text(count ? 'Selected ' + noun + ' (' + count + ')' : 'All ' + noun);
            }

            // Show "Select All" while some listed box is unchecked, "Deselect All" while any is checked.
            function updateSelectToggles(target) {
                const $visible = $(target).filter(function() {
                    return $(this).closest('.form-check').css('display') !== 'none';
                });
                const $menu = $(target).first().closest('.dropdown-menu');
                $menu.find('.filter-select-all').toggle($visible.not(':checked').length > 0);
                $menu.find('.filter-deselect-all').toggle($(target + ':checked').length > 0);
            }

            function syncCategories() {
                currentCategoryFilters = checkedIds('.category-filter', 'category-id');
                setFilterLabel('#showFilterCategory', currentCategoryFilters.length, 'Categories');
                updateSelectToggles('.category-filter');
                refreshTitleOptions();
            }

            function syncTitles() {
                currentTitleFilters = checkedIds('.title-filter', 'title-id');
                setFilterLabel('#showFilterTitle', currentTitleFilters.length, 'Titles');
                updateSelectToggles('.title-filter');
            }

            function syncSources() {
                currentSourceFilters = checkedIds('.source-filter', 'source-id');
                setFilterLabel('#showFilterSource', currentSourceFilters.length, 'Sources');
                updateSelectToggles('.source-filter');
            }

            /**
             * List only the titles that belong to the selected categories (all, if none
             * selected) and the selected type (all, for "All Types"), also matching the
             * title search box. Titles that drop out of the list are unchecked.
             */
            window.refreshTitleOptions = function() {
                const categories = currentCategoryFilters.map(String);
                const type = (currentTypeFilter || '').toLowerCase();
                const search = ($('#titleSearchInput').val() || '').toLowerCase();
                let shown = 0;

                $('#titleList .form-check').each(function() {
                    const $item = $(this);
                    const matchesCategory = categories.length === 0 ||
                        categories.includes(String($item.data('title-category')));
                    const matchesType = type === '' || type === 'all types' ||
                        String($item.data('title-type')) === type;
                    const matchesSearch = $item.find('label').text().toLowerCase().includes(search);
                    const available = matchesCategory && matchesType;

                    if (!available) {
                        $item.find('.title-filter').prop('checked', false);
                    }

                    $item.toggle(available && matchesSearch);
                    if (available && matchesSearch) {
                        shown++;
                    }
                });

                $('#titleListEmpty').toggleClass('d-none', shown > 0);
                syncTitles();
            };

            $('.category-filter').on('change', function() {
                syncCategories();
                table.ajax.reload();
            });

            $('.title-filter').on('change', function() {
                syncTitles();
                table.ajax.reload();
            });

            $('.source-filter').on('change', function() {
                syncSources();
                table.ajax.reload();
            });

            // Select All / Deselect All: only the boxes currently listed (not hidden by search,
            // category or type), then one table reload.
            const filterSyncs = {
                '.category-filter': syncCategories,
                '.title-filter': syncTitles,
                '.source-filter': syncSources,
            };

            $(document).on('click', '.filter-select-all, .filter-deselect-all', function(e) {
                e.preventDefault();
                e.stopPropagation(); // keep the dropdown open

                const target = $(this).data('target');
                if (!filterSyncs[target]) {
                    return;
                }

                const selectAll = $(this).hasClass('filter-select-all');
                $(target).each(function() {
                    if ($(this).closest('.form-check').css('display') !== 'none') {
                        $(this).prop('checked', selectAll);
                    }
                });

                filterSyncs[target]();
                table.ajax.reload();
            });

            $('#titleSearchInput').on('keyup', refreshTitleOptions);

            $('#sourceSearchInput').on('keyup', function() {
                const searchValue = this.value.toLowerCase();
                $('#sourceList .form-check').each(function() {
                    $(this).toggle($(this).find('label').text().toLowerCase().includes(
                        searchValue));
                });
                updateSelectToggles('.source-filter');
            });
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
        function addShortNotesModal(applicantID) {
            const modalId = 'shortNotesModal-' + applicantID;

            // Remove any existing modal with the same ID
            $('#' + modalId).remove();

            // Modal HTML with unique ID
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}Label">Add Notes</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="shortNotesForm-${applicantID}">
                                    <div class="mb-3">
                                        <label for="detailsTextarea-${applicantID}" class="form-label">Details</label>
                                        <textarea class="form-control" id="detailsTextarea-${applicantID}" rows="4" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="reasonDropdown-${applicantID}" class="form-label">Reason</label>
                                        <select class="form-select" id="reasonDropdown-${applicantID}" required>
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
                                <button type="button" class="btn btn-success" id="saveShortNotesButton-${applicantID}">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Append the modal to body
            $('body').append(modalHtml);

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();

            // Reset the form fields each time it's opened
            $(`#shortNotesForm-${applicantID}`)[0].reset();

            // Remove validation classes and feedback
            $(`#detailsTextarea-${applicantID}`).removeClass('is-valid is-invalid').next('.invalid-feedback').remove();
            $(`#reasonDropdown-${applicantID}`).removeClass('is-valid is-invalid').next('.invalid-feedback').remove();

            // Handle Save button
            $(`#saveShortNotesButton-${applicantID}`).off('click').on('click', function() {
                const notes = $(`#detailsTextarea-${applicantID}`).val().trim();
                const reason = $(`#reasonDropdown-${applicantID}`).val();

                let valid = true;

                if (!notes) {
                    $(`#detailsTextarea-${applicantID}`).addClass('is-invalid');
                    if ($(`#detailsTextarea-${applicantID}`).next('.invalid-feedback').length === 0) {
                        $(`#detailsTextarea-${applicantID}`).after(
                            '<div class="invalid-feedback">Please provide details.</div>');
                    }
                    valid = false;
                }

                if (!reason) {
                    $(`#reasonDropdown-${applicantID}`).addClass('is-invalid');
                    if ($(`#reasonDropdown-${applicantID}`).next('.invalid-feedback').length === 0) {
                        $(`#reasonDropdown-${applicantID}`).after(
                            '<div class="invalid-feedback">Please select a reason.</div>');
                    }
                    valid = false;
                }

                // Remove validation on input/change
                $(`#detailsTextarea-${applicantID}`).on('input', function() {
                    if ($(this).val()) {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                        $(this).next('.invalid-feedback').remove();
                    }
                });

                $(`#reasonDropdown-${applicantID}`).on('change', function() {
                    if ($(this).val()) {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                        $(this).next('.invalid-feedback').remove();
                    }
                });

                if (!valid) return;

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
                );

                // Send data via AJAX
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
                        modal.hide();
                        $(`#shortNotesForm-${applicantID}`)[0].reset();
                        $('#applicants_table').DataTable().ajax.reload();
                    },
                    error: function(xhr) {
                        toastr.error('An error occurred while saving notes.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Optional cleanup when modal is hidden
            $(`#${modalId}`).on('hidden.bs.modal', function() {
                $(this).remove(); // removes the modal from DOM
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
                url: '{{ route('getModuleNotesHistory') }}', // Your backend URL to fetch notes history, replace it with your actual URL
                type: 'GET',
                data: {
                    id: id,
                    module: 'Applicant'

                }, // Pass the id to your server to fetch the corresponding applicant's notes
                success: function(response) {
                    var notesHtml = ''; // This will hold the combined HTML for all notes

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
                            var statusClass = (status == 1) ? 'bg-success' :
                                'bg-dark'; // 'bg-success' for active, 'bg-dark' for inactive
                            var statusText = (status == 1) ? 'Active' : 'Inactive';

                            // Append each note's details to the notesHtml string
                            notesHtml +=
                                '<div class="note-entry">' +
                                '<p><strong>Dated:</strong> ' + created +
                                '&nbsp;&nbsp; <span class="badge ' + statusClass + '">' + statusText +
                                '</span></p>' +
                                '<p><strong>Notes Detail:</strong> <br>' + notes + '</p>' +
                                '</div><hr>'; // Add a separator between notes
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
                    $('#viewNotesHistoryModal .modal-body').html(
                        '<p>There was an error retrieving the notes. Please try again later.</p>');
                    $('#viewNotesHistoryModal').modal('show');
                }
            });

            // Add the modal HTML to the page (only once, if not already present)
            if ($('#viewNotesHistoryModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="viewNotesHistoryModal" tabindex="-1" aria-labelledby="viewNotesHistoryModalLabel" aria-hidden="true">' +
                    '<div class="modal-dialog modal-dialog-scrollable modal-dialog-top modal-lg">' +
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

        function showDetailsModal(applicantId, name, email, secondaryEmail, postcode, landline, phone, jobTitle,
            jobCategory, jobSource, status) {
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

        function triggerFileInput(id) {
            // Store the applicant ID when the button is clicked
            applicantId = id;

            // Trigger the file input click event
            document.getElementById('fileInput').click();
        }

        function uploadFile() {
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];

            if (!file || !applicantId) {
                toastr.error('No file selected or applicant ID missing.');
                return;
            }

            const formData = new FormData();
            formData.append('resume', file);
            formData.append('applicant_id', applicantId);

            fetch('{{ route('applicants.uploadCv') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin' // ✅ important for session
                })
                .then(async response => {
                    // 🚨 Handle redirects / session issues
                    if (response.status === 302 || response.status === 401) {
                        throw new Error('Session expired. Please refresh the page.');
                    }

                    if (response.status === 419) {
                        throw new Error('CSRF token mismatch. Refresh the page.');
                    }

                    if (response.status === 422) {
                        const err = await response.json();
                        throw new Error(err.message || 'Validation failed');
                    }

                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message || 'File uploaded successfully');
                        $('#applicants_table').DataTable().ajax.reload(null, false);
                        fileInput.value = '';
                    } else {
                        toastr.error(data.message || 'Upload failed');
                    }
                })
                .catch(error => {
                    toastr.error(error.message || 'Error uploading file');
                    console.error(error);
                });
        }
    </script>
@endsection
@endsection

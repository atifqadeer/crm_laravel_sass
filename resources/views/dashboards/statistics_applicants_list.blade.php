@extends('layouts.vertical', [
    'title' => ucwords(str_replace('_', ' ', $status)) . ' Details List - ( ' . ucwords(str_replace('_', ' ', $categoryTitle)) . ($type == 'specialist' ? ' - ' . ucwords($type) : '') . ' )',
    'titleBadge' => $formatted_startDate . ($formatted_endDate ? ' to ' . $formatted_endDate : ''),
    'subTitle' => 'Dashboard',
])
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
                                <!-- Title Filter Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterTitle">All Titles</span>
                                    </button>

                                    <div class="dropdown-menu filter-dropdowns" aria-labelledby="dropdownMenuButton2">
                                        <!-- Search input -->
                                        <input type="text" class="form-control mb-2" id="titleSearchInput"
                                            placeholder="Search titles...">

                                        <!-- Select/Deselect All -->
                                        <div class="d-flex justify-content-end px-1 mb-1" id="titleToggleContainer">
                                            <a href="#" id="titleSelectAll"
                                                class="filter-select-all text-primary small fw-semibold me-2"
                                                data-target=".title-filter">Select
                                                All</a>
                                            <a href="#" id="titleDeselectAll"
                                                class="filter-deselect-all text-danger small fw-semibold"
                                                data-target=".title-filter" style="display:none">Deselect All</a>
                                        </div>

                                        <!-- Scrollable checkbox list -->
                                        <div id="titleList">
                                            @foreach ($jobTitles as $title)
                                                <div class="form-check">
                                                    <input class="form-check-input title-filter" type="checkbox"
                                                        value="{{ $title->id }}" id="title_{{ $title->id }}"
                                                        data-title-id="{{ $title->id }}">
                                                    <label class="form-check-label"
                                                        for="title_{{ $title->id }}">{{ ucwords($title->name) }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- Type Filter Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dropdownMenuButton3" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterType">All Types</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton3">
                                        <a class="dropdown-item type-filter" href="#">All Types</a>
                                        <a class="dropdown-item type-filter" href="#">Specialist</a>
                                        <a class="dropdown-item type-filter" href="#">Regular</a>
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
                                                data-target=".source-filter">Select
                                                All</a>
                                            <a href="#" id="sourceDeselectAll"
                                                class="filter-deselect-all text-danger small fw-semibold"
                                                data-target=".source-filter" style="display:none">Deselect All</a>
                                        </div>

                                        <!-- Scrollable checkbox list -->
                                        <div id="sourceList">
                                            @foreach ($jobSources ?? [] as $source)
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
                            </div>
                        </div>
                    </div><!-- end col-->
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
                            id="dropdownMenuColumns" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            aria-expanded="false">
                            <i class="ri-layout-column-line me-1"></i> Columns
                        </button>
                        <div class="dropdown-menu filter-dropdowns p-2" aria-labelledby="dropdownMenuColumns"
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
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Agent</th>
                                    <th id="schedule_date" style="display:none;">Schedule Date</th>
                                    <th>Name (Applicant)</th>
                                    <th>Email</th>
                                    <th width="15%">Phone / Landline</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>PostCode (Applicant)</th>
                                    @canany(['applicant-download-resume'])
                                        <th>Resume (Applicant)</th>
                                        <th>Resume (CRM)</th>
                                    @endcanany
                                    <th>Job</th>
                                    <th>Head Office</th>
                                    <th>Unit</th>
                                    <th>PostCode (Sale)</th>
                                    <th>Source (Sale)</th>
                                    <th width="20%">Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- The data will be populated here by DataTables --}}
                            </tbody>
                        </table>
                    </div>
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

    <script>
        const hasResumePermission = @json(auth()->user()->can('applicant-download-resume'));
        const hasViewNotePermission = @json(auth()->user()->can('applicant-view-note'));
        const hasAddNotePermission = @json(auth()->user()->can('applicant-add-note'));

        $(document).ready(function() {

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

            let titleFilters = [];
            let sourceFilters = [];

            let columns = [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'show_created_at',
                    name: 'show_created_at',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'user_name',
                    name: 'user_name',
                },
                {
                    data: 'schedule_date',
                    name: 'schedule_date',
                    visible: false,
                    orderable: false,
                    searchable: false,
                    defaultContent: '-',
                },
                {
                    data: 'applicant_name',
                    name: 'applicants.applicant_name'
                },
                {
                    data: 'applicantEmail',
                    name: 'applicantEmail',
                    orderable: false,
                    searchable: true
                },
                {
                    data: 'applicantPhone',
                    name: 'applicantPhone', // ← Use the same as data key
                    orderable: false,
                    searchable: true,
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

            ];

            if (hasResumePermission) {
                columns.push({
                    data: 'applicant_resume',
                    name: 'applicants.applicant_cv',
                    orderable: false,
                    searchable: false
                }, {
                    data: 'crm_resume',
                    name: 'applicants.updated_cv',
                    orderable: false,
                    searchable: false
                }, );
            }

            columns.push({
                data: 'job_details',
                name: 'job_details'
            }, {
                data: 'office_name',
                name: 'sale_offices.office_name'
            }, {
                data: 'unit_name',
                name: 'sale_units.unit_name'
            }, {
                data: 'sale_postcode',
                name: 'sales.sale_postcode'
            }, {
                data: 'sale_source_name',
                name: 'sale_job_sources.name'
            }, {
                data: 'notes_details',
                name: 'notes_details',
                orderable: false,
                searchable: false
            });
            columns.push({
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            });

            const columnHeaders = document.querySelectorAll('#applicants_table thead th');
            const columnConfig = columns.map((column, index) => ({
                title: columnHeaders[index].textContent.trim(),
                toggleable: index !== 0 && column.data !== 'action' && column.data !== 'schedule_date',
                default: column.visible !== false,
            }));
            const COLUMN_VISIBILITY_STORAGE_KEY = 'statistics_applicants_column_visibility_v1';

            function loadColumnVisibility() {
                let stored = {};
                try {
                    stored = JSON.parse(localStorage.getItem(COLUMN_VISIBILITY_STORAGE_KEY)) || {};
                } catch (error) {
                    stored = {};
                }

                return columnConfig.map((column, index) => {
                    if (!column.toggleable) {
                        return column.default;
                    }

                    return Object.prototype.hasOwnProperty.call(stored, index) ?
                        !!stored[index] :
                        column.default;
                });
            }

            function saveColumnVisibility() {
                const stored = {};
                columnConfig.forEach((column, index) => {
                    if (column.toggleable) {
                        stored[index] = columnVisibility[index];
                    }
                });
                localStorage.setItem(COLUMN_VISIBILITY_STORAGE_KEY, JSON.stringify(stored));
            }

            let columnVisibility = loadColumnVisibility();
            columns.forEach((column, index) => {
                column.visible = columnVisibility[index];
            });

            function renderColumnsDropdown() {
                const list = $('#columnsList').empty();
                columnConfig.forEach((column, index) => {
                    if (!column.toggleable) {
                        return;
                    }

                    const item = $('<div class="form-check"></div>');
                    const checkbox = $('<input class="form-check-input column-toggle" type="checkbox">')
                        .attr('id', 'column_' + index)
                        .attr('data-column-index', index)
                        .prop('checked', columnVisibility[index]);
                    const label = $('<label class="form-check-label"></label>')
                        .attr('for', 'column_' + index)
                        .text(column.title);
                    item.append(checkbox, label);
                    list.append(item);
                });
            }

            renderColumnsDropdown();

            let columnDefs = [];

            // Dynamically assign center alignment for columns starting from resume/applicant_experience
            const centerAlignedIndices = [];
            for (let i = 0; i < columns.length; i++) {
                const key = columns[i].data;
                if (['applicant_resume', 'crm_resume', 'sale_postcode', 'action'].includes(key)) {
                    centerAlignedIndices.push(i);
                }
            }

            centerAlignedIndices.forEach(idx => {
                columnDefs.push({
                    targets: idx,
                    createdCell: function(td) {
                        $(td).css('text-align', 'center');
                    }
                });
            });

            const table = $('#applicants_table').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: '{{ route('getStatisticsApplicants') }}',
                    type: 'GET', // <-- change GET → POST
                    data: function(d) {
                        d.status = '{{ $status }}';
                        d.range = '{{ $range }}';
                        d.type = '{{ $type }}';
                        d.category = '{{ $categoryTitle }}';
                        d.date_range = '{{ $date_range }}';
                        d.title_filter = titleFilters;
                        d.source_filter = sourceFilters;

                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }
                    },
                    beforeSend: function() {
                        showLoader(); // Show loader before AJAX request starts
                    },
                    error: function(xhr) {
                        console.error('DataTable AJAX error:', xhr.status, xhr.responseText);
                        $('#applicants_table tbody').html(
                            '<tr><td colspan="100%" class="text-center">Failed to load data</td></tr>'
                        );
                    }
                },
                columns: columns,
                columnDefs: columnDefs,
                order: [
                    [1, 'desc']
                ], // Date (CRM note created_at), newest first
                rowId: function(data) {
                    return 'row_' + data.id;
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

            function checkedIds(selector, key) {
                return $(selector + ':checked').map(function() {
                    return String($(this).data(key));
                }).get();
            }

            function updateFilterState() {
                titleFilters = checkedIds('.title-filter', 'title-id');
                sourceFilters = checkedIds('.source-filter', 'source-id');

                $('#showFilterTitle').text(titleFilters.length ?
                    `Selected Titles (${titleFilters.length})` :
                    'All Titles');
                $('#showFilterSource').text(sourceFilters.length ?
                    `Selected Sources (${sourceFilters.length})` :
                    'All Sources');

                [
                    ['#titleToggleContainer', '.title-filter', titleFilters.length],
                    ['#sourceToggleContainer', '.source-filter', sourceFilters.length],
                ].forEach(([containerSelector, filterSelector, selectedCount]) => {
                    const totalCount = $(filterSelector).length;
                    $(containerSelector).find('.filter-select-all').toggle(selectedCount < totalCount);
                    $(containerSelector).find('.filter-deselect-all').toggle(selectedCount > 0);
                });
            }

            function filterDropdownList(inputId, listId) {
                const searchTerm = $('#' + inputId).val().trim().toLowerCase();
                $('#' + listId + ' .form-check').each(function() {
                    const label = $(this).find('label').text().toLowerCase();
                    $(this).toggle(label.includes(searchTerm));
                });
            }

            $('.title-filter, .source-filter').on('change', function() {
                updateFilterState();
                table.ajax.reload();
            });

            $(document).on('click', '.filter-select-all', function(event) {
                event.preventDefault();
                event.stopPropagation();
                $($(this).data('target')).prop('checked', true);
                updateFilterState();
                table.ajax.reload();
            });

            $(document).on('click', '.filter-deselect-all', function(event) {
                event.preventDefault();
                event.stopPropagation();
                $($(this).data('target')).prop('checked', false);
                updateFilterState();
                table.ajax.reload();
            });

            $('#titleSearchInput').on('input', function() {
                filterDropdownList('titleSearchInput', 'titleList');
            });
            $('#sourceSearchInput').on('input', function() {
                filterDropdownList('sourceSearchInput', 'sourceList');
            });

            $(document).on('click', '.filter-dropdowns', function(event) {
                event.stopPropagation();
            });

            updateFilterState();

            $('#columnsList').on('change', '.column-toggle', function() {
                const index = Number(this.dataset.columnIndex);
                columnVisibility[index] = this.checked;
                table.column(index).visible(this.checked);
                saveColumnVisibility();
            });

            $('#columnsSelectAll').on('click', function(event) {
                event.preventDefault();
                columnConfig.forEach((column, index) => {
                    if (column.toggleable) {
                        columnVisibility[index] = true;
                        table.column(index).visible(true);
                    }
                });
                saveColumnVisibility();
                renderColumnsDropdown();
            });

            $('#columnsResetDefault').on('click', function(event) {
                event.preventDefault();
                columnConfig.forEach((column, index) => {
                    if (column.toggleable) {
                        columnVisibility[index] = column.default;
                        table.column(index).visible(column.default);
                    }
                });
                saveColumnVisibility();
                renderColumnsDropdown();
            });

            // Type filter handler
            $('.type-filter').on('click', function() {
                currentTypeFilter = $(this).text().toLowerCase();
                const formattedText = currentTypeFilter
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
                $('#showFilterType').html(formattedText);
                showLoader();
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
                table.page(currentPage - 2).draw('page'); // Move to the previous page
            } else if (page === 'next' && currentPage < totalPages) {
                table.page(currentPage).draw('page'); // Move to the next page
            } else if (typeof page === 'number' && page !== currentPage) {
                table.page(page - 1).draw('page'); // Move to the selected page
            }
        }

        $(document).on('click', '.job-details', function(event) {
            event.preventDefault();

            let job;
            try {
                job = JSON.parse(this.dataset.job);
            } catch (error) {
                console.error('Unable to read sale details:', error);
                return;
            }

            const modalId = 'jobDetailsModal-' + job.sale_id;
            $('#' + modalId).remove();

            const modal = $(`
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}-title" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}-title">Job Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-bordered mb-0"><tbody></tbody></table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            const details = [
                ['Sale ID', job.sale_id],
                ['Posted Date', job.posted_date],
                ['Head Office', job.office_name],
                ['Unit Name', job.unit_name],
                ['Postcode', job.postcode],
                ['Job Category', job.job_category],
                ['Job Title', job.job_title],
                ['Job Source', job.sale_source_name],
                ['Status', job.status],
                ['Timing', job.timing],
                ['Experience', job.experience],
                ['Salary', job.salary],
                ['Position', job.position],
                ['Qualification', job.qualification],
                ['Benefits', job.benefits],
            ];

            details.forEach(([label, value]) => {
                const textValue = value ?
                    String(value).replace(/<[^>]*>/g, '') :
                    '-';
                const $value = label === 'Status' ?
                    $('<span>').addClass('badge').addClass(
                        ['bg-success', 'bg-danger', 'bg-warning text-dark', 'bg-secondary'].includes(job
                            .status_class) ?
                        job.status_class :
                        'bg-secondary'
                    ).text(textValue) :
                    $('<span>').text(textValue);

                modal.find('tbody').append(
                    $('<tr>').append(
                        $('<th>').text(label),
                        $('<td>').append($value)
                    )
                );
            });

            $('body').append(modal);
            const modalInstance = new bootstrap.Modal(modal[0]);
            modal.on('hidden.bs.modal', function() {
                modal.remove();
            });
            modalInstance.show();
        });

        // Function to show the notes modal
        function showNotesModal(applicantId, notes, applicantName, applicantPostcode) {
            const modalId = 'showNotesModal-' + applicantId;

            // Remove existing modal with same ID if exists
            $('#' + modalId).remove();

            // Modal HTML with spinner loader and unique ID
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-top modal-md">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}Label">Applicant Notes</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div class="spinner-border text-primary mb-3" role="status" id="${modalId}-loader">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div id="${modalId}-content" class="note-content d-none text-start"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Append modal to body
            $('body').append(modalHtml);

            // Use Bootstrap's Modal API to show the modal
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();

            // Simulate content loading after a delay
            setTimeout(() => {
                $(`#${modalId}-loader`).hide(); // Hide loader
                $(`#${modalId}-content`).removeClass('d-none').html(`
                    <p><strong>Applicant Name:</strong> ${applicantName}</p>
                    <p><strong>Postcode:</strong> ${applicantPostcode}</p>
                    <p><strong>Notes Detail:</strong><br>${notes.replace(/\n/g, '<br>')}</p>
                `);
            }, 300); // Adjust delay if needed
        }

        // Function to show the notes modal
        function viewNotesHistory(id) {
            const modalId = 'viewNotesHistoryModal-' + id;

            // Remove existing modal with same ID to avoid duplicates
            $('#' + modalId).remove();

            // Create modal with loader
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable modal-dialog-top modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}Label">Applicant Notes History</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div class="spinner-border text-primary mb-3" role="status" id="${modalId}-loader">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div id="${modalId}-content" class="note-history-content d-none text-start"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Append modal to body
            $('body').append(modalHtml);

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();

            // AJAX call
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
                            const statusClass = note.status == 1 ? 'bg-success' : 'bg-dark';
                            const statusText = note.status == 1 ? 'Active' : 'Inactive';
                            const noteText = note.details.replace(/\n/g, '<br>');

                            notesHtml += `
                                <div class="note-entry">
                                    <p><strong>Dated:</strong> ${created} &nbsp; <span class="badge ${statusClass}">${statusText}</span></p>
                                    <p><strong>Notes Detail:</strong><br>${noteText}</p>
                                </div><hr>
                            `;
                        });
                    }

                    // Hide loader and show content
                    $(`#${modalId}-loader`).hide();
                    $(`#${modalId}-content`).removeClass('d-none').html(notesHtml);
                },
                error: function(xhr, status, error) {
                    $(`#${modalId}-loader`).hide();
                    $(`#${modalId}-content`).removeClass('d-none').html(
                        '<p class="text-danger">Error retrieving notes. Please try again later.</p>');
                    console.error("Error fetching notes history:", error);
                }
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
            jobCategory, jobSource, status) {
            const modalId = 'showDetailsModal-' + applicantId;

            // Remove existing modal with same ID (if any)
            $('#' + modalId).remove();

            // Modal HTML with loader and placeholder body
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}Label">Applicant Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div class="spinner-border text-primary my-3" role="status" id="${modalId}-loader">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div class="detail-content d-none text-start"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Append to body and show modal
            $('body').append(modalHtml);
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();

            // Simulate content load
            setTimeout(() => {
                $(`#${modalId}-loader`).hide();
                $(`#${modalId} .detail-content`).removeClass('d-none').html(`
                    <table class="table table-bordered mb-0">
                        <tr><th>Applicant ID</th><td>${applicantId}</td></tr>
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
            }, 300); // Adjust delay as needed

            // Remove modal from DOM on close
            $(`#${modalId}`).on('hidden.bs.modal', function() {
                $(this).remove();
            });
        }
    </script>
@endsection
@endsection

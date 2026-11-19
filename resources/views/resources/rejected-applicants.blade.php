@extends('layouts.vertical', ['title' => 'Rejected Resources List', 'subTitle' => 'Resources'])
{{-- The layout outputs @yield('css') in head-css; a 'style' section is never rendered --}}
@section('css')
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
                                <!-- Date Range filter -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dateRangeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-calendar-line me-1"></i> <span id="showDateRange">Last 3 Months</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dateRangeDropdown">
                                        <a class="dropdown-item date-filter" href="#">Last 3 Months</a>
                                        <a class="dropdown-item date-filter" href="#">Last 6 Months</a>
                                        <a class="dropdown-item date-filter" href="#">Last 9 Months</a>
                                        <a class="dropdown-item date-filter" href="#">Others</a>
                                    </div>
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
                                <!-- Sources Filter Dropdown (sale's job source) -->
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
                                        <div class="d-flex justify-content-end px-1 mb-1">
                                            <a href="#" class="filter-select-all text-primary small fw-semibold me-2"
                                                data-target=".source-filter">Select All</a>
                                            <a href="#" class="filter-deselect-all text-danger small fw-semibold"
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
                                        id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i> Export
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                        <a class="dropdown-item"
                                            href="{{ route('applicantsExport', ['type' => 'allRejected']) }}">Export All
                                            Data</a>
                                        {{-- <a class="dropdown-item" href="{{ route('applicantsExport', ['type' => 'emailsRejected']) }}">Export Emails</a> --}}
                                    </div>
                                </div>
                                @canany(['resource-rejected-revert'])
                                    <!-- Add Updated Sales Filter Button -->
                                    <button class="btn btn-primary my-1" type="button" id="submitSelectedButton">
                                        <i class="ri-arrow-go-forward-line me-1"></i> Revert
                                    </button>
                                @endcanany
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
                    <div id="columnsToolbar" class="dropdown d-inline">
                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button"
                            id="dropdownMenuApplicantColumns" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ri-layout-column-line me-1"></i> Columns
                        </button>
                        <div class="dropdown-menu filter-dropdowns p-2" aria-labelledby="dropdownMenuApplicantColumns"
                            style="min-width: 230px;">
                            <div class="d-flex justify-content-between align-items-center px-1 mb-2">
                                <a href="#" id="columnsSelectAll" class="text-primary small fw-semibold">Show All</a>
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
                                    @canany(['resource-rejected-revert'])
                                        <th><input type="checkbox" id="master-checkbox"></th>
                                    @endcanany
                                    <th>Date</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>PostCode</th>
                                    <th>Phone / Landline</th>
                                    <th>Job</th>
                                    <th>Head Office</th>
                                    <th>Unit</th>
                                    <th>PostCode (Sale)</th>
                                    <th>Source (Sale)</th>
                                    <th>Notes</th>
                                    <th>Rejection Type</th>
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
        // Disable the Unblock button by default
        $('#submitSelectedButton').prop('disabled', true);


        $(document).ready(function() {
            // ---------- State ----------
            let currentTypeFilter = '';
            let currentCategoryFilters = [];
            let currentTitleFilters = [];
            let currentSourceFilters = [];
            let currentDateFilter = '';

            const canSelect = @json(auth()->user()->can('resource-rejected-revert'));

            // ---------- Loader ----------
            const loadingRow = `<tr><td colspan="100%" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td></tr>`;

            function showLoader() {
                $('#applicants_table tbody').empty().append(loadingRow);
            }

            // ---------- Columns ----------
            const columns = [
                // Checkbox column only if the user has permission
                ...(canSelect ? [{
                    data: 'checkbox',
                    name: 'checkbox',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }] : []),
                {
                    data: 'crm_notes_created',
                    name: 'crm_notes.created_at'
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
                    name: 'applicantPhone',
                    orderable: false,
                    searchable: true
                },
                {
                    data: 'job_details',
                    name: 'job_details',
                    orderable: false, // computed column, not in DB
                    searchable: false, // computed column, not in DB
                    className: 'text-center'
                },
                {
                    data: 'office_name',
                    name: 'offices.office_name'
                },
                {
                    data: 'unit_name',
                    name: 'units.unit_name'
                },
                {
                    data: 'sale_postcode',
                    name: 'sales.sale_postcode',
                    className: 'text-center'
                },
                {
                    data: 'sale_source_name',
                    name: 'sale_job_sources.name',
                    className: 'text-center'
                },
                {
                    data: 'applicant_notes',
                    name: 'applicants.applicant_notes',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'sub_stage',
                    name: 'history.sub_stage'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ];

            // ---------- Column visibility ----------
            // Titles come from the table header so indexes always match `columns`
            // (the checkbox column only exists with the revert permission).
            const columnConfig = columns.map(function(column, index) {
                const title = $('#applicants_table thead th').eq(index).text().trim();
                return {
                    title: title,
                    toggleable: !['checkbox', 'action'].includes(column.data),
                    default: true
                };
            });
            const COLUMN_VISIBILITY_STORAGE_KEY = 'resources_rejected_applicants_column_visibility_v1';

            // Stored by column `data` key so it survives the checkbox column appearing/disappearing.
            function loadColumnVisibility() {
                let stored = {};
                try {
                    stored = JSON.parse(localStorage.getItem(COLUMN_VISIBILITY_STORAGE_KEY)) || {};
                } catch (error) {
                    stored = {};
                }

                return columnConfig.map(function(column, index) {
                    if (!column.toggleable) {
                        return true;
                    }
                    const key = columns[index].data;
                    return Object.prototype.hasOwnProperty.call(stored, key) ? !!stored[key] : column.default;
                });
            }

            function saveColumnVisibility() {
                const stored = {};
                columnConfig.forEach(function(column, index) {
                    if (column.toggleable) {
                        stored[columns[index].data] = !!columnVisibility[index];
                    }
                });
                try {
                    localStorage.setItem(COLUMN_VISIBILITY_STORAGE_KEY, JSON.stringify(stored));
                } catch (error) {
                    // storage unavailable: visibility just won't be remembered
                }
            }

            let columnVisibility = loadColumnVisibility();

            function renderColumnsDropdown() {
                const $list = $('#columnsList').empty();
                columnConfig.forEach(function(column, index) {
                    if (!column.toggleable) {
                        return;
                    }

                    const checked = columnVisibility[index] ? 'checked' : '';
                    $list.append(`
                        <div class="form-check">
                            <input class="form-check-input column-toggle" type="checkbox"
                                id="rejected_column_${index}" data-column-index="${index}" ${checked}>
                            <label class="form-check-label" for="rejected_column_${index}">${column.title}</label>
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

            // ---------- DataTable ----------
            const table = $('#applicants_table').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: @json(route('getResourcesRejectedApplicants')),
                    type: 'GET',
                    data: function(d) {
                        d.type_filter = currentTypeFilter;
                        d.category_filter = currentCategoryFilters;
                        d.title_filter = currentTitleFilters;
                        d.source_filter = currentSourceFilters;
                        d.date_filter = currentDateFilter;

                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }
                    },
                    beforeSend: function() {
                        showLoader();
                    },
                    error: function(xhr) {
                        console.error('DataTable AJAX error:', xhr.status, xhr.responseJSON);
                        $('#applicants_table tbody').empty().html(
                            '<tr><td colspan="100%" class="text-center">Failed to load data</td></tr>'
                        );
                    }
                },
                columns: columns,
                columnDefs: [{
                    targets: getHiddenColumnIndices(),
                    visible: false
                }],
                // Skip the checkbox column when it exists (it is not orderable)
                order: [
                    [canSelect ? 1 : 0, 'desc']
                ],
                rowId: function(data) {
                    return 'row_' + data.id;
                },
                dom: '<"d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2"l>rtip',
                initComplete: function() {
                    // Put the Columns button next to the "Show N entries" selector
                    $(this.api().table().container())
                        .find('.dataTables_length')
                        .after($('#columnsToolbar'));
                },
                drawCallback: function() {
                    const api = this.api();
                    const pagination = $(api.table().container()).find('.dataTables_paginate');
                    pagination.empty();

                    const pageInfo = api.page.info();
                    const currentPage = pageInfo.page + 1;
                    const totalPages = pageInfo.pages;

                    // Uncheck "select all" after every redraw
                    $('#select-all').prop('checked', false);

                    if (pageInfo.recordsTotal === 0) {
                        $('#applicants_table tbody').html(
                            '<tr><td colspan="100%" class="text-center">Data not found</td></tr>'
                        );
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

                    // Always show page 1
                    paginationHtml += `<li class="page-item ${currentPage === 1 ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0);" onclick="movePage(1)">1</a>
                </li>`;

                    const start = Math.max(2, currentPage - 1);
                    const end = Math.min(totalPages - 1, currentPage + 1);

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
                        <input type="number" id="goToPageInput" min="1" max="${totalPages}"
                            class="form-control form-control-sm" style="width: 80px;"
                            onkeydown="if(event.key === 'Enter') goToPage(${totalPages})">
                    </div>
                    <small id="goToPageError" class="text-danger mt-1" style="font-size: 12px;"></small>
                </div>`;

                    pagination.html(paginationHtml);
                }
            });

            // ---------- Select all (only if permitted) ----------
            if (canSelect) {
                $(document).on('change', '#select-all', function() {
                    $('.applicant_checkbox').prop('checked', this.checked);
                });

                $(document).on('change', '.applicant_checkbox', function() {
                    const total = $('.applicant_checkbox').length;
                    const checked = $('.applicant_checkbox:checked').length;
                    $('#select-all').prop('checked', total > 0 && total === checked);
                });
            }

            // ---------- Column visibility handlers ----------
            $(document).on('change', '#columnsList .column-toggle', function() {
                const index = Number($(this).data('column-index'));
                columnVisibility[index] = this.checked;
                table.column(index).visible(this.checked);
                saveColumnVisibility();
            });

            $('#columnsSelectAll').on('click', function(event) {
                event.preventDefault();
                columnConfig.forEach(function(column, index) {
                    if (column.toggleable) {
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
                    if (column.toggleable) {
                        columnVisibility[index] = !!column.default;
                        table.column(index).visible(columnVisibility[index], false);
                    }
                });
                table.columns.adjust().draw(false);
                saveColumnVisibility();
                renderColumnsDropdown();
            });

            // ---------- Search ----------
            function handleCustomSearch() {
                const searchValue = $('#customSearchInput').val().trim();
                table.search(searchValue).draw();
            }

            $('#customSearchBtn').on('click', handleCustomSearch);

            $('#customSearchInput').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    handleCustomSearch();
                }
            });

            $('#customSearchInput').on('keyup change', function() {
                $('#customClearBtn').toggleClass('d-none', $(this).val().trim() === '');
            });

            $('#customClearBtn').on('click', function() {
                $('#customSearchInput').val('');
                $(this).addClass('d-none');
                table.search('').draw();
            });

            // ---------- Type filter ----------
            $('.type-filter').on('click', function() {
                currentTypeFilter = $(this).text().trim().toLowerCase();

                const formattedText = currentTypeFilter
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterType').html(formattedText);

                // Titles follow the selected type (and categories).
                refreshTitleOptions();

                table.ajax.reload();
            });

            // ---------- Category / Title / Source filters ----------
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
            function refreshTitleOptions() {
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
            }

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
                const target = $(this).data('target');
                if (!filterSyncs[target]) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation(); // keep the dropdown open

                const selectAll = $(this).hasClass('filter-select-all');
                $(target).each(function() {
                    if ($(this).closest('.form-check').css('display') !== 'none') {
                        $(this).prop('checked', selectAll);
                    }
                });

                filterSyncs[target]();
                table.ajax.reload();
            });

            // Dropdown search boxes
            $('#titleSearchInput').on('input', refreshTitleOptions);

            $('#categorySearchInput').on('input', function() {
                const searchValue = this.value.toLowerCase();
                $('#categoryList .form-check').each(function() {
                    $(this).toggle($(this).find('label').text().toLowerCase().includes(searchValue));
                });
                updateSelectToggles('.category-filter');
            });

            $('#sourceSearchInput').on('input', function() {
                const searchValue = this.value.toLowerCase();
                $('#sourceList .form-check').each(function() {
                    $(this).toggle($(this).find('label').text().toLowerCase().includes(searchValue));
                });
                updateSelectToggles('.source-filter');
            });

            // ---------- Date filter ----------
            $('.date-filter').on('click', function() {
                const text = $(this).text().trim().toLowerCase();
                currentDateFilter = text.replace(/\s+/g, '-');

                const formattedText = text
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showDateRange').html(formattedText);
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
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
        function viewNotesHistory(applicantId) {
            const modalId = `viewNotesHistoryModal-${applicantId}`;
            const loaderId = `${modalId}-loader`;
            const contentId = `${modalId}-content`;

            // Remove existing modal with same ID if exists
            $(`#${modalId}`).remove();

            // Add the modal HTML to the page
            $('body').append(`
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable modal-dialog-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}Label">Applicant Notes History</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div class="spinner-border text-primary mb-3" role="status" id="${loaderId}">
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

            // AJAX call to retrieve notes
            $.ajax({
                url: '{{ route('getApplicantHistorybyStatus') }}',
                type: 'GET',
                data: {
                    id: applicantId,
                    status: 'rejected'
                },
                success: function(response) {
                    let notesHtml = '';

                    if (!response.data || response.data.length === 0) {
                        notesHtml = '<p>No record found.</p>';
                    } else {
                        response.data.forEach(function(note) {
                            const created = moment(note.created_at).format('DD MMM YYYY, h:mmA');
                            const status = note.status;
                            const badgeClass = status == 1 ? 'bg-success' : 'bg-dark';
                            const statusText = status == 1 ? 'Active' : 'Inactive';

                            notesHtml += `
                                <div class="note-entry">
                                    <p><strong>Dated:</strong> ${created}&nbsp;&nbsp;
                                        <span class="badge ${badgeClass}">${statusText}</span>
                                    </p>
                                    <p><strong>Notes Detail:</strong><br>${note.details}</p>
                                </div><hr>
                            `;
                        });
                    }

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

            showJobDetailsModal(job);
        });

        function showJobDetailsModal(job) {
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

        function showDetailsModal(applicantId, name, email, secondaryEmail, postcode, landline, phone, jobTitle,
            jobCategory, jobSource, createdAt, status) {
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
            }, 300); // Adjust delay as needed

            // Remove modal from DOM on close
            $(`#${modalId}`).on('hidden.bs.modal', function() {
                $(this).remove();
            });
        }


        // Enable the Unblock button when any checkbox is checked, disable if none checked
        $(document).on('change', '.applicant_checkbox, #master-checkbox', function() {
            var anyChecked = $('.applicant_checkbox:checked').length > 0;
            $('#submitSelectedButton').prop('disabled', !anyChecked);
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

        // Add a listener to the "Select All" button for additional actions
        $('#submitSelectedButton').on('click', function() {
            var selectedIds = [];

            // Get selected IDs
            $('.applicant_checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            Swal.fire({
                title: 'Are you sure?',
                text: 'These applicants will be revert from the rejected. Are you sure you want to continue?',
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
                        url: 'revertRejectedApplicant', // Update the URL to match your route
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
    </script>
@endsection
@endsection

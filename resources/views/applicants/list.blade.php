@extends('layouts.vertical', ['title' => 'Applicants List', 'subTitle' => 'Home'])
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
                        <div class="col-lg-12">
                            <div class="text-md-end mt-3">
                                @canany(['applicant-filters'])
                                    <!-- Category Filter Dropdown -->
                                    <div class="dropdown d-inline">
                                        <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-filter-line me-1"></i> <span id="showFilterCategory">All Category</span>
                                        </button>

                                        <div class="dropdown-menu filter-dropdowns" aria-labelledby="dropdownMenuButton1">
                                            <!-- Search input -->
                                            <input type="text" class="form-control mb-2" id="categorySearchInput"
                                                placeholder="Search category...">

                                            <!-- Scrollable checkbox list -->
                                            <div id="categoryList">
                                                <div class="form-check">
                                                    <input class="form-check-input category-filter" type="checkbox" value=""
                                                        id="all-categories" data-title-id="">
                                                    <label class="form-check-label" for="all-categories">All Category</label>
                                                </div>

                                                @foreach($jobCategories as $category)
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
                                            id="dropdownMenuButton3" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-filter-line me-1"></i> <span id="showFilterType">All Types</span>
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton3">
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
                                                <div class="form-check">
                                                    <input class="form-check-input title-filter" type="checkbox" value=""
                                                        id="all-titles" data-title-id="">
                                                    <label class="form-check-label" for="all-titles">All Titles</label>
                                                </div>
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

                                    <!-- Button Dropdown -->
                                    <div class="dropdown d-inline">
                                        <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                            id="dropdownMenuButton4" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-filter-line me-1"></i> <span id="showFilterStatus">All</span>
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton4">
                                            <a class="dropdown-item status-filter" href="#">All</a>
                                            <a class="dropdown-item status-filter" href="#">Active</a>
                                            <a class="dropdown-item status-filter" href="#">Inactive</a>
                                            <a class="dropdown-item status-filter" href="#">Blocked</a>
                                            <a class="dropdown-item status-filter" href="#">No Job</a>
                                            <a class="dropdown-item status-filter" href="#">Not Interested</a>
                                        </div>
                                    </div>
                                @endcanany
                                <!-- Button Dropdown -->
                                @canany(['applicant-export', 'applicant-export-all', 'applicant-export-emails'])
                                    <div class="dropdown d-inline">
                                        <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                            id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-download-line me-1"></i> Export
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                            @canany(['applicant-export-all'])
                                                <a class="dropdown-item"
                                                    href="{{ route('applicantsExport', ['type' => 'all']) }}">Export All Data</a>
                                            @endcanany
                                            @canany(['applicant-export-emails'])
                                                <a class="dropdown-item"
                                                    href="{{ route('applicantsExport', ['type' => 'emails']) }}">Export Emails</a>
                                            @endcanany
                                            <a class="dropdown-item"
                                                href="{{ route('applicantsExport', ['type' => 'noLatLong']) }}">Export no LAT &
                                                LONG</a>
                                        </div>
                                    </div>
                                @endcanany
                                @canany(['applicant-import'])
                                    <button type="button" class="btn btn-outline-primary me-1 my-1" data-bs-toggle="modal"
                                        data-bs-target="#csvImportModal" title="Import CSV">
                                        <i class="ri-upload-line"></i>
                                    </button>
                                    {{-- <button type="button" class="btn btn-outline-primary me-1 my-1" data-bs-toggle="modal"
                                        data-bs-target="#pdfImportModal" title="Import Doc">
                                        <i class="ri-file"></i>
                                    </button> --}}
                                @endcanany
                                @canany(['applicant-create'])
                                    <a href="{{ route('applicants.create') }}">
                                        <button type="button" class="btn btn-success ml-1 my-1"><i class="ri-add-line"></i>
                                            Create Applicant</button>
                                    </a>
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
                    <div class="table-responsive">
                        <table id="applicants_table" class="table align-middle mb-3">
                            <thead class="bg-light-subtle">
                                <tr>
                                    <th>#</th>
                                    <th>Created Date</th>
                                    {{-- <th>Updated Date</th> --}}
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>PostCode</th>
                                    <th width="15%">Phone</th>
                                    @canany(['applicant-download-resume'])
                                        <th>Applicant Resume</th>
                                        <th>CRM Resume</th>
                                    @endcanany
                                    <th>Experience</th>
                                    <th>Source</th>
                                    @canany(['applicant-view-note', 'applicant-add-note'])
                                        <th width="25%">Notes</th>
                                    @endcanany
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

    <!-- Import PDF Modal -->
    <div class="modal fade" id="pdfImportModal" tabindex="-1" aria-labelledby="pdfImportLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="pdfImportForm" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pdfImportLabel">Import CSV</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="process_file" class="form-label">Choose CSV File</label>
                            <input type="file" class="form-control" id="process_file" name="process_file" accept=".pdf,.doc,.docx"
                                required>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div id="uploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                role="progressbar" style="width: 0%">0%</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Import CSV Modal -->
    <div class="modal fade" id="csvImportModal" tabindex="-1" aria-labelledby="csvImportLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="csvImportForm" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="csvImportLabel">Import CSV</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="csvFile" class="form-label">Choose CSV File</label>
                            <input type="file" class="form-control" id="csvFile" name="csv_file" accept=".csv"
                                required>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div id="uploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                role="progressbar" style="width: 0%">0%</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@section('script')
    <!-- jQuery CDN (make sure this is loaded before DataTables) -->
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>

    <!-- DataTables CSS (for styling the table) -->
    <link rel="stylesheet" href="{{ asset('css/jquery.dataTables.min.css')}}">
 
    <!-- DataTables JS (for the table functionality) -->
    <script src="{{ asset('js/jquery.dataTables.min.js')}}"></script>

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}">

    <!-- SweetAlert2 CDN -->
    <script src="{{ asset('js/sweetalert2@11.js')}}"></script>

    <!-- Toastr JS -->
    <script src="{{ asset('js/toastr.min.js')}}"></script>

    <!-- Moment JS -->
    <script src="{{ asset('js/moment.min.js')}}"></script>

    <!-- Summernote CSS -->
    <link rel="stylesheet" href="{{ asset('css/summernote-lite.min.css')}}">

    <!-- Summernote JS -->
    <script src="{{ asset('js/summernote-lite.min.js')}}"></script>

    <script>
        const hasResumePermission = @json(auth()->user()->can('applicant-download-resume'));
        const hasViewNotePermission = @json(auth()->user()->can('applicant-view-note'));
        const hasAddNotePermission = @json(auth()->user()->can('applicant-add-note'));

        $(document).ready(function() {
            let currentFilter = '';
            let currentTypeFilter = '';
            let currentCategoryFilters = [];
            let currentTitleFilters = [];

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

            let columns = [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'created_at',
                    name: 'applicants.created_at'
                },
                // {
                //     data: 'updated_at',
                //     name: 'applicants.updated_at'
                // },
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
                data: 'applicant_experience',
                name: 'applicants.applicant_experience'
            }, {
                data: 'job_source',
                name: 'job_sources.name'
            }, );
            if (hasViewNotePermission || hasAddNotePermission) {
                columns.push({
                    data: 'applicant_notes',
                    name: 'applicants.applicant_notes',
                    orderable: false,
                    searchable: false
                });
            }
            columns.push({
                data: 'customStatus',
                name: 'customStatus',
                orderable: false,
                searchable: false
            }, {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            });

            let columnDefs = [];

            // Dynamically assign center alignment for columns starting from resume/applicant_experience
            const centerAlignedIndices = [];
            for (let i = 0; i < columns.length; i++) {
                const key = columns[i].data;
                if (['applicant_resume', 'crm_resume', 'customStatus', 'action'].includes(key)) {
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
                    url: @json(route('getApplicantsAjaxRequest')),
                    type: 'GET',
                    data: function(d) {
                        d.status_filter = currentFilter;
                        d.type_filter = currentTypeFilter;
                        d.category_filter = currentCategoryFilters;
                        d.title_filters = currentTitleFilters;
                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }
                    },
                    beforeSend: function() {
                        showLoader(); // Show loader before AJAX request starts
                    },
                    error: function(xhr) {
                        console.error('DataTable AJAX error:', xhr.status, xhr.responseJSON);
                        $('#applicants_table tbody').empty().html('<tr><td colspan="100%" class="text-center">Failed to load data</td></tr>');
                    }
                },
                columns: columns,
                columnDefs: columnDefs,
                rowId: function(data) {
                    return 'row_' + data.id;
                },
                dom: 'flrtip',
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

            /*** Type filter dropdown handler ***/
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

            /*** Status filter dropdown handler ***/
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

            /*** Category filter handler ***/
            $('.category-filter').on('click', function() {
                const id = $(this).data('category-id');
                // Handle "All Titles"
                if (id === '' || id === undefined) {
                    currentCategoryFilters = [];
                    $('.category-filter').not(this).prop('checked', false);
                } else {
                    // Remove or add to array
                    if (this.checked) {
                        currentCategoryFilters.push(id);
                        // Uncheck "All Titles"
                        $('.category-filter[data-category-id=""]').prop('checked', false);
                    } else {
                        currentCategoryFilters = currentCategoryFilters.filter(x => x !== id);
                    }
                }

                // Update dropdown display text
                const selectedLabels = $('.category-filter:checked')
                    .map(function() {
                        return $(this).next('label').text().trim();
                    }).get();

                $('#showFilterCategory').text(selectedLabels.length ? 'Selected Categories (' + selectedLabels.length +
                    ')' : 'All Categories');

                // Trigger DataTable reload with the selected filters
                table.ajax.reload();
            });

            /*** Title Filter Handler ***/
            $('.title-filter').on('change', function() {
                const id = $(this).data('title-id');

                // Handle "All Titles"
                if (id === '' || id === undefined) {
                    currentTitleFilters = [];
                    $('.title-filter').not(this).prop('checked', false);
                } else {
                    // Remove or add to array
                    if (this.checked) {
                        currentTitleFilters.push(id);
                        // Uncheck "All Titles"
                        $('.title-filter[data-title-id=""]').prop('checked', false);
                    } else {
                        currentTitleFilters = currentTitleFilters.filter(x => x !== id);
                    }
                }

                // Update dropdown display text
                const selectedLabels = $('.title-filter:checked')
                    .map(function() {
                        return $(this).next('label').text().trim();
                    }).get();

                $('#showFilterTitle').text(selectedLabels.length ? 'Selected Titles (' + selectedLabels.length +
                    ')' : 'All Titles');

                // Trigger DataTable reload with the selected filters
                table.ajax.reload();
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

        document.getElementById('titleSearchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const checkboxes = document.querySelectorAll('#titleList .form-check');

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
                    module: 'Horsefly\\Applicant'
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
        function addNotesModal(applicantID) {
            const modalId = `notesModal_${applicantID}`;
            const formId = `note_form_${applicantID}`;

            // If the modal does not exist yet, append it to the DOM
            if ($('#' + modalId).length === 0) {
                $('body').append(
                    '<div class="modal fade" id="' + modalId + '" tabindex="-1" aria-labelledby="' + modalId +
                    'Label" aria-hidden="true">' +
                    '<div class="modal-dialog modal-lg modal-dialog-top">' +
                    '<div class="modal-content">' +
                    '<div class="modal-header">' +
                    '<h5 class="modal-title" id="' + modalId + 'Label">Add Notes</h5>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                    '</div>' +
                    '<div class="modal-body">' +
                    '<form class="form-horizontal" id="' + formId + '">' +
                    '<input type="hidden" name="request_from_applicants" value="1">' +
                    '<input type="hidden" name="module" value="Applicant">' +
                    '<input type="hidden" name="module_key" value="' + applicantID + '">' +

                    '<div id="note_alert' + applicantID + '"></div>' +
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
                    '<div class="form-group row">' +
                    '<label class="col-form-label col-sm-3"><strong style="font-size:18px">7.</strong> Visa Status</label>' +
                    '<div class="col-sm-9 d-flex align-items-center">' +
                    '<div class="d-flex">' +
                    '<div class="form-check form-check-inline">' +
                    '<input type="radio" name="visa_status" id="british" class="form-check-input mt-0" value="British">' +
                    '<label class="form-check-label" for="british">British</label>' +
                    '</div>' +
                    '<div class="form-check form-check-inline ml-3">' +
                    '<input type="radio" name="visa_status" id="required_sponsorship" class="form-check-input mt-0" value="Required Sponsorship">' +
                    '<label class="form-check-label" for="required_sponsorship">Required Sponsorship</label>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="form-group row">' +
                    '<div class="col-6 my-2">' +
                    '<div class="form-check form-switch">' +
                    '<input class="form-check-input" type="checkbox" role="switch" name="nursing_home" id="nursing_home_checkbox">' +
                    '<label class="form-check-label" for="nursing_home_checkbox">Nursing Home</label>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-6 my-2">' +
                    '<div class="form-check form-switch">' +
                    '<input class="form-check-input" type="checkbox" role="switch" name="alternate_weekend" id="alternate_weekend_checkbox">' +
                    '<label class="form-check-label" for="alternate_weekend_checkbox">Alternate Weekend</label>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-6 my-2">' +
                    '<div class="form-check form-switch">' +
                    '<input class="form-check-input" type="checkbox" role="switch" name="interview_availability" id="interview_availability_checkbox">' +
                    '<label class="form-check-label" for="interview_availability_checkbox">Interview Availability</label>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-6 my-2">' +
                    '<div class="form-check form-switch">' +
                    '<input class="form-check-input" type="checkbox" role="switch" name="no_job" id="no_job_checkbox" onclick="handleCheckboxClick(\'no_job_checkbox\', \'hangup_call_checkbox\')">' +
                    '<label class="form-check-label" for="no_job_checkbox">No Job</label>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-6 my-2">' +
                    '<div class="form-check form-switch">' +
                    '<input class="form-check-input" type="checkbox" name="hangup_call" role="switch" id="hangup_call_checkbox" onclick="handleCheckboxClick(\'hangup_call_checkbox\', \'no_job_checkbox\')">' +
                    '<label class="form-check-label" for="hangup_call_checkbox">Call Hung up/Not Interested</label>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="form-group">' +
                    '<label class="col-form-label col-sm-12" for="note_details">Other Details <span class="text-danger">*</span></label>' +
                    '<div class="col-sm-12">' +
                    '<textarea name="details" id="note_details" class="form-control" cols="30" rows="4" placeholder="Type here ..." required></textarea>' +
                    '</div>' +
                    '</div>' +
                    '</form>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>' +
                    '<button type="submit" data-note_key="214232" class="btn btn-success" form="' + formId +
                    '">Save</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>'
                );
            }

            // Reset the form every time the modal is shown
            $('#' + modalId).on('shown.bs.modal', function() {
                $(this).find('form')[0].reset();
            });

            // Open the modal
            $('#' + modalId).modal('show');

            // Handle the form submission
            $('#' + formId).off('submit').on('submit', function(event) {
                event.preventDefault(); // Prevent the default form submission

                const form = $(this);
                const formData = form.serialize(); // Serialize the form data

                // Add the CSRF token to the form data
                const token = '{{ csrf_token() }}';
                const dataWithToken = formData + '&_token=' + token;

                $.ajax({
                    url: '{{ route('moduleNotes.store') }}', // Replace with your endpoint
                    type: 'POST',
                    data: dataWithToken, // Send the serialized data with the CSRF token
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message); // Show message from controller
                            $('#' + modalId).modal('hide');
                            $('#applicants_table').DataTable().ajax.reload();
                        } else {
                            toastr.error('Something went wrong.');
                        }
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

        // Function to change sale status modal
        function changeStatusModal(applicantID, currentStatus) {
            const modalId = `changeStatusModal-${applicantID}`;

            // Remove any existing modal with same ID
            $('#' + modalId).remove();

            // Append the modal HTML to the body
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}Label">Change Applicant Status</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="changeStatusForm-${applicantID}">
                                    <div class="mb-3">
                                        <label for="detailsTextarea-${applicantID}" class="form-label">Details</label>
                                        <textarea class="form-control" id="detailsTextarea-${applicantID}" rows="4" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="statusDropdown-${applicantID}" class="form-label">Status</label>
                                        <select class="form-select" id="statusDropdown-${applicantID}" required>
                                            <option value="" disabled>Select Status</option>
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-success" id="saveStatusButton-${applicantID}">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(modalHtml);

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();

            // Reset form inputs
            $(`#changeStatusForm-${applicantID}`)[0].reset();
            $(`#statusDropdown-${applicantID}`).val(currentStatus);
            $(`#detailsTextarea-${applicantID}, #statusDropdown-${applicantID}`).removeClass('is-valid is-invalid').next(
                '.invalid-feedback').remove();

            // Handle Save
            $(`#saveStatusButton-${applicantID}`).off('click').on('click', function() {
                const notes = $(`#detailsTextarea-${applicantID}`).val().trim();
                const selectedStatus = $(`#statusDropdown-${applicantID}`).val();
                let hasError = false;

                // Validate
                if (!notes) {
                    $(`#detailsTextarea-${applicantID}`).addClass('is-invalid');
                    if ($(`#detailsTextarea-${applicantID}`).next('.invalid-feedback').length === 0) {
                        $(`#detailsTextarea-${applicantID}`).after(
                            '<div class="invalid-feedback">Please provide details.</div>');
                    }
                    hasError = true;
                }

                if (!selectedStatus) {
                    $(`#statusDropdown-${applicantID}`).addClass('is-invalid');
                    if ($(`#statusDropdown-${applicantID}`).next('.invalid-feedback').length === 0) {
                        $(`#statusDropdown-${applicantID}`).after(
                            '<div class="invalid-feedback">Please select a status.</div>');
                    }
                    hasError = true;
                }

                // Clear errors on input
                $(`#detailsTextarea-${applicantID}`).on('input', function() {
                    if ($(this).val()) {
                        $(this).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback')
                            .remove();
                    }
                });
                $(`#statusDropdown-${applicantID}`).on('change', function() {
                    if ($(this).val()) {
                        $(this).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback')
                            .remove();
                    }
                });

                if (hasError) return;

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
                    );

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
                        modal.hide();
                        $('#applicants_table').DataTable().ajax.reload();
                    },
                    error: function() {
                        toastr.error('An error occurred while updating the status.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Cleanup modal after hide
            $(`#${modalId}`).on('hidden.bs.modal', function() {
                $(this).remove();
            });
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
                fetch('{{ route('applicants.uploadCv') }}', {
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

        $(document).ready(function() {
            $('#pdfImportForm').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                let submitBtn = form.find('button[type="submit"]');
                let formData = new FormData(this);
                let xhr = new XMLHttpRequest();

                // Disable button
                submitBtn.prop('disabled', true).text('Uploading...');

                xhr.open('POST', '{{ route('process.file') }}', true);
                xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

                xhr.upload.addEventListener("progress", function(event) {
                    if (event.lengthComputable) {
                        let percent = Math.round((event.loaded / event.total) * 100);
                        $('#uploadProgressBar').css('width', percent + '%').text(percent + '%');
                        console.log('Uploading: ' + percent + '%');
                    }
                });

                xhr.onload = function() {
                    console.log('Upload response:', xhr.status, xhr.responseText);

                    if (xhr.status === 200) {
                        $('#uploadProgressBar')
                            .removeClass('bg-danger')
                            .addClass('bg-success')
                            .text('Upload Complete');

                        form[0].reset();
                        // $('#applicants_table').DataTable().ajax.reload();

                        // ✅ Close modal after short delay
                        setTimeout(() => {
                            $('#csvImportModal').modal('hide');
                            $('#uploadProgressBar')
                                .css('width', '0%')
                                .removeClass('bg-success bg-danger')
                                .text('0%');
                        }, 800);
                    } else {
                        $('#uploadProgressBar')
                            .removeClass('bg-success')
                            .addClass('bg-danger')
                            .text('Upload Failed');
                        alert('Server Error: ' + xhr.responseText);
                    }

                    // Re-enable button
                    submitBtn.prop('disabled', false).text('Import CSV');
                };

                xhr.onerror = function() {
                    console.error('XHR error:', xhr.responseText);
                    $('#uploadProgressBar')
                        .removeClass('bg-success')
                        .addClass('bg-danger')
                        .text('Upload Error');
                    alert('XHR Error: ' + xhr.responseText);

                    // Re-enable button
                    submitBtn.prop('disabled', false).text('Import CSV');
                };

                xhr.send(formData);
            });
            
            $('#csvImportForm').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                let submitBtn = form.find('button[type="submit"]');
                let formData = new FormData(this);
                let xhr = new XMLHttpRequest();

                // Disable button
                submitBtn.prop('disabled', true).text('Uploading...');

                xhr.open('POST', '{{ route('applicants.import') }}', true);
                xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

                xhr.upload.addEventListener("progress", function(event) {
                    if (event.lengthComputable) {
                        let percent = Math.round((event.loaded / event.total) * 100);
                        $('#uploadProgressBar').css('width', percent + '%').text(percent + '%');
                        console.log('Uploading: ' + percent + '%');
                    }
                });

                xhr.onload = function() {
                    console.log('Upload response:', xhr.status, xhr.responseText);

                    if (xhr.status === 200) {
                        $('#uploadProgressBar')
                            .removeClass('bg-danger')
                            .addClass('bg-success')
                            .text('Upload Complete');

                        form[0].reset();
                        $('#applicants_table').DataTable().ajax.reload();

                        // ✅ Close modal after short delay
                        setTimeout(() => {
                            $('#csvImportModal').modal('hide');
                            $('#uploadProgressBar')
                                .css('width', '0%')
                                .removeClass('bg-success bg-danger')
                                .text('0%');
                        }, 800);
                    } else {
                        $('#uploadProgressBar')
                            .removeClass('bg-success')
                            .addClass('bg-danger')
                            .text('Upload Failed');
                        alert('Server Error: ' + xhr.responseText);
                    }

                    // Re-enable button
                    submitBtn.prop('disabled', false).text('Import CSV');
                };

                xhr.onerror = function() {
                    console.error('XHR error:', xhr.responseText);
                    $('#uploadProgressBar')
                        .removeClass('bg-success')
                        .addClass('bg-danger')
                        .text('Upload Error');
                    alert('XHR Error: ' + xhr.responseText);

                    // Re-enable button
                    submitBtn.prop('disabled', false).text('Import CSV');
                };

                xhr.send(formData);
            });
        });
    </script>
@endsection
@endsection

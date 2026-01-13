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
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle"
                                        type="button"
                                        id="dropdownMenuButton10"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">

                                        <i class="ri-filter-line me-1"></i>

                                        <span id="showFilterRegion">
                                            @if($regions->count())
                                                {{ $regions->first()->name }}
                                            @else
                                                No Regions Found
                                            @endif
                                        </span>
                                    </button>

                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton10">
                                        @forelse($regions as $region)
                                            <a
                                                class="dropdown-item region-filter"
                                                href="#"
                                                data-region-id="{{ $region->id }}">
                                                {{ $region->name }}
                                            </a>
                                        @empty
                                            <a class="dropdown-item disabled" href="#">
                                                No Regions Found
                                            </a>
                                        @endforelse
                                    </div>
                                </div>

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
                                    <th>Applicant Name</th>
                                    <th>Email</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>PostCode</th>
                                    <th width="10%">Phone</th>
                                    <th>Applicant Resume</th>
                                    <th>CRM Resume</th>
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

    <!-- Add daterangepicker -->
    <link rel="stylesheet" href="{{ asset('css/daterangepicker.css') }}" />
    <script src="{{ asset('js/daterangepicker.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Store the current filter in a variable
            var regionFilter = '';
            var currentTypeFilter = '';
            var currentCategoryFilters = [];
            var currentTitleFilters = [];

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
                processing: false,  // Disable default processing state
                serverSide: true,  // Enables server-side processing
                ajax: {
                    url: @json(route('getApplicantsByRegions')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        // Add the current filter to the request parameters
                        d.region_filter = regionFilter;  // Send the current filter value as a parameter
                        d.type_filter = currentTypeFilter;  // Send the current filter value as a parameter
                        d.category_filter = currentCategoryFilters;  // Send the current filter value as a parameter
                        d.title_filter = currentTitleFilters;  // Send the current filter value as a parameter

                        // Clean up search parameter
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
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at', name: 'cv_notes.created_at' },
                    { data: 'applicant_name', name: 'applicants.applicant_name' },
                    { data: 'applicant_email', name: 'applicants.applicant_email' },
                    { data: 'job_title', name: 'job_titles.name' },
                    { data: 'job_category', name: 'job_categories.name' },
                    { data: 'applicant_postcode', name: 'applicants.applicant_postcode' },
                    { data: 'applicant_phone', name: 'applicants.applicant_phone' },
                    { data: 'applicant_resume', name: 'applicants.applicant_cv', orderable: false, searchable: false },
                    { data: 'crm_resume', name: 'applicants.updated_cv', orderable: false, searchable: false },
                    { data: 'applicant_experience', name: 'applicants.applicant_experience' },
                    { data: 'job_source', name: 'job_sources.name' },
                    { data: 'applicant_notes', name: 'applicants.applicant_notes', orderable: false, searchable: false },
                    { data: 'customStatus', name: 'customStatus', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                columnDefs: [
                    {
                        targets: 8,  // Column index for 'job_details'
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');  // Center the text in this column
                        }
                    },
                    {
                        targets: 9,  // Column index for 'job_details'
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');  // Center the text in this column
                        }
                    },
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
                table.page(currentPage - 2).draw('page');  // Move to the previous page
            } else if (page === 'next' && currentPage < totalPages) {
                table.page(currentPage).draw('page');  // Move to the next page
            } else if (typeof page === 'number' && page !== currentPage) {
                table.page(page - 1).draw('page');  // Move to the selected page
            }
        }
        
        // Function to show the notes modal
        function showNotesModal(applicantID, notes, applicantName, applicantPostcode) {
            const modalId = `showNotesModal_${applicantID}`;

            // Check if modal already exists
            if ($(`#${modalId}`).length === 0) {
                // Create and append the modal
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalId}Label">Applicant Notes</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Notes content will be inserted below -->
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Set content with line breaks and data
            $(`#${modalId} .modal-body`).html(
                'Applicant Name: <strong>' + applicantName + '</strong><br>' +
                'Postcode: <strong>' + applicantPostcode + '</strong><br>' +
                'Notes Detail: <p>' + notes + '</p>'
            );

            // Show the modal
            $(`#${modalId}`).modal('show');
        }

        // Function to show the notes modal
        function viewNotesHistory(id) {
            const modalId = `viewNotesHistoryModal_${id}`;
            const modalLabelId = `${modalId}Label`;

            // Check if the modal already exists
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalLabelId}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalLabelId}">Applicant Notes History</h5>
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

            // Make an AJAX call to retrieve notes history data
            $.ajax({
                url: '{{ route("getModuleNotesHistory") }}',
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
                            const notes = note.details;
                            const created = moment(note.created_at).format('DD MMM YYYY, h:mmA');
                            const status = note.status;

                            const statusClass = status == 1 ? 'bg-success' : 'bg-dark';
                            const statusText = status == 1 ? 'Active' : 'Inactive';

                            notesHtml += `
                                <div class="note-entry">
                                    <p><strong>Dated:</strong> ${created} &nbsp;&nbsp; 
                                    <span class="badge ${statusClass}">${statusText}</span></p>
                                    <p><strong>Notes Detail:</strong><br>${notes}</p>
                                </div><hr>
                            `;
                        });
                    }

                    $(`#${modalId} .modal-body`).html(notesHtml);
                    $(`#${modalId}`).modal('show');
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching notes history: " + error);
                    $(`#${modalId} .modal-body`).html('<p>There was an error retrieving the notes. Please try again later.</p>');
                    $(`#${modalId}`).modal('show');
                }
            });
        }

        // Function to show the notes modal
        function addShortNotesModal(applicantID) {
            const modalId = `shortNotesModal_${applicantID}`;
            const formId = `shortNotesForm_${applicantID}`;
            const detailsId = `detailsTextarea_${applicantID}`;
            const reasonId = `reasonDropdown_${applicantID}`;
            const saveButtonId = `saveShortNotesButton_${applicantID}`;

            // Create modal if not exists
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
                                            <label for="${detailsId}" class="form-label">Details</label>
                                            <textarea class="form-control" id="${detailsId}" rows="4" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="${reasonId}" class="form-label">Reason</label>
                                            <select class="form-select" id="${reasonId}" required>
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
                                    <button type="button" class="btn btn-primary" id="${saveButtonId}">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Reset form fields every time modal is shown
            $(`#${modalId}`).on('shown.bs.modal', function () {
                const $details = $(`#${detailsId}`);
                const $reason = $(`#${reasonId}`);
                $details.val('').removeClass('is-valid is-invalid');
                $reason.val('').removeClass('is-valid is-invalid');
                $details.next('.invalid-feedback').remove();
                $reason.next('.invalid-feedback').remove();
            });

            // Show the modal
            $(`#${modalId}`).modal('show');

            // Attach Save button handler (off first to avoid duplicates)
            $(`#${saveButtonId}`).off('click').on('click', function () {
                const notes = $(`#${detailsId}`).val();
                const reason = $(`#${reasonId}`).val();

                // Validate
                let isValid = true;

                if (!notes) {
                    isValid = false;
                    $(`#${detailsId}`).addClass('is-invalid');
                    if ($(`#${detailsId}`).next('.invalid-feedback').length === 0) {
                        $(`#${detailsId}`).after('<div class="invalid-feedback">Please provide details.</div>');
                    }
                }

                if (!reason) {
                    isValid = false;
                    $(`#${reasonId}`).addClass('is-invalid');
                    if ($(`#${reasonId}`).next('.invalid-feedback').length === 0) {
                        $(`#${reasonId}`).after('<div class="invalid-feedback">Please select a reason.</div>');
                    }
                }

                // Live validation
                $(`#${detailsId}`).off('input').on('input', function () {
                    if ($(this).val()) {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                        $(this).next('.invalid-feedback').remove();
                    }
                });

                $(`#${reasonId}`).off('change').on('change', function () {
                    if ($(this).val()) {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                        $(this).next('.invalid-feedback').remove();
                    }
                });

                if (!isValid) return;

                // AJAX Submit
                $.ajax({
                    url: '{{ route("storeShortNotes") }}',
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        details: notes,
                        reason: reason,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        toastr.success('Notes saved successfully!');
                        $(`#${modalId}`).modal('hide');
                        $(`#${formId}`)[0].reset();
                        $(`#applicants_table`).DataTable().ajax.reload();
                    },
                    error: function () {
                        alert('An error occurred while saving notes.');
                    }
                });
            });
        }

        function showDetailsModal(applicantId, createdAt, name, email, secondaryEmail, postcode, landline, phone, jobTitle, jobCategory, jobSource, status) {
            const modalId = `showDetailsModal_${applicantId}`;
            const modalLabelId = `${modalId}Label`;
            const modalBodyId = `${modalId}_body`;

            // If modal doesn't exist, append it
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalLabelId}" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalLabelId}">Applicant Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body" id="${modalBodyId}">
                                    <div class="text-center my-4">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <p class="mt-2">Loading...</p>
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
                // Reset content with loader if modal exists
                $(`#${modalBodyId}`).html(`
                    <div class="text-center my-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Loading...</p>
                    </div>
                `);
            }

            // Show the modal
            $(`#${modalId}`).modal('show');

            // Populate modal after slight delay (simulate async if needed)
            setTimeout(() => {
                const tableHtml = `
                    <table class="table table-bordered">
                        <tr><th>Applicant ID</th><td>${applicantId}</td></tr>
                        <tr><th>Created On</th><td>${createdAt}</td></tr>
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
                `;
                $(`#${modalBodyId}`).html(tableHtml);
            }, 300); // Add delay only if you want to simulate loading
        }

    </script>
@endsection
@endsection
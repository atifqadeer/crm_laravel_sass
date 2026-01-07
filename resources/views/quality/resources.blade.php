@extends('layouts.vertical', ['title' => 'Quality Resources List', 'subTitle' => 'Quality'])
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
                                <!-- Button Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton4" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterStatus">Requested CVs</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton4">
                                        <a class="dropdown-item status-filter" href="#">Requested CVs</a>
                                        <a class="dropdown-item status-filter" href="#">Open CVs</a>
                                        <a class="dropdown-item status-filter" href="#">No Job CVs</a>
                                        <a class="dropdown-item status-filter" href="#">Rejected CVs</a>
                                        <a class="dropdown-item status-filter" href="#">Cleared CVs</a>
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
                                    <th>Sent By</th>
                                    <th>Applicant Name</th>
                                    {{-- <th>Email</th> --}}
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>PostCode</th>
                                    <th width="10%">Phone</th>
                                    <th>Applicant Resume</th>
                                    <th>CRM Resume</th>
                                    <th>Head Office</th>
                                    <th>Unit</th>
                                    <th>PostCode</th>
                                    <th width="10%">Notes</th>
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
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('copy-quality-resource-notes-btn')) {
                const targetSelector = e.target.getAttribute('data-copy-quality-resource-notes-target');
                const targetEl = document.querySelector(targetSelector);
                if (!targetEl) return;

                const temp = document.createElement('textarea');
                temp.value = targetEl.innerText;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);

                e.target.innerText = 'Copied!';
                e.target.classList.remove('btn-outline-secondary');
                e.target.classList.add('btn-success');

                setTimeout(() => {
                    e.target.innerText = 'Copy Notes';
                    e.target.classList.remove('btn-success');
                    e.target.classList.add('btn-outline-secondary');
                }, 1500);
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            // Store the current filter in a variable
            var currentTypeFilter = '';
            var currentFilter = '';
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
                    url: @json(route('getResourcesByTypeAjaxRequest')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        d.status_filter = currentFilter;  // Send the current filter value as a parameter
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
                    { data: 'notes_created_at', name: 'notes_created_at' },
                    { data: 'user_name', name: 'users.name'},
                    { data: 'applicant_name', name: 'applicants.applicant_name' },
                    // { data: 'applicant_email', name: 'applicants.applicant_email' },
                    { data: 'job_title', name: 'job_titles.name' },
                    { data: 'job_category', name: 'job_categories.name' },
                    { data: 'applicant_postcode', name: 'applicants.applicant_postcode' },
                    { data: 'applicant_phone', name: 'applicants.applicant_phone' },
                    { data: 'applicant_resume', name:'applicants.applicant_cv', orderable: false, searchable: false },
                    { data: 'crm_resume', name:'applicants.updated_cv', orderable: false, searchable: false },
                    { data: 'office_name', name: 'offices.office_name' },
                    { data: 'unit_name', name: 'units.unit_name' },
                    { data: 'sale_postcode', name: 'sales.sale_postcode' },
                    { data: 'notes_detail', name: 'notes_detail', orderable: false, searchable: false },
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

        // Basic HTML sanitizer function (you might want to use a library like DOMPurify in production)
        function sanitizeHtml(html, options) {
            if (!html) return '';
            
            // Create a temporary div
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            // Remove any unwanted tags
            const elements = temp.querySelectorAll('*');
            for (let el of elements) {
                // Remove if tag is not allowed
                if (!options.allowedTags.includes(el.tagName.toLowerCase())) {
                    el.parentNode.removeChild(el);
                }
                // Remove disallowed attributes
                else {
                    const attributes = el.attributes;
                    for (let i = attributes.length - 1; i >= 0; i--) {
                        const attrName = attributes[i].name.toLowerCase();
                        if (!options.allowedAttributes[el.tagName.toLowerCase()] || 
                            !options.allowedAttributes[el.tagName.toLowerCase()].includes(attrName)) {
                            el.removeAttribute(attrName);
                        }
                    }
                }
            }
            
            return temp.innerHTML;
        }

         // Function to show the notes modal
        function clearCVModal(applicantID, saleID, status, modalName) {
            const modalID = 'clearCVModal' + applicantID + '-' + saleID;
            const formID = 'clearCVForm' + applicantID + '-' + saleID;

            // Add the modal HTML to the page (only once, if not already present)
            if ($('#' + modalID).length === 0) {
                $('body').append(
                    '<div class="modal fade" id="'+ modalID +'" tabindex="-1" aria-labelledby="clearCVModalLabel" aria-hidden="true">' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="clearCVModalLabel'+ applicantID + '-' + saleID +'">'+ modalName +'</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<form id="' + formID + '">' +
                                        '<div class="mb-3">' +
                                            '<label for="detailsTextarea'+ applicantID + '-' + saleID +'" class="form-label">Details</label>' +
                                            '<textarea class="form-control" id="detailsTextarea'+ applicantID + '-' + saleID +'" rows="4" required></textarea>' +
                                        '</div>' +
                                    '</form>' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>' +
                                    '<button type="button" class="btn btn-success" id="saveClearCVButton'+ applicantID + '-' + saleID +'">'+
                                        'Save</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Reset the form whenever modal is opened
            function resetModal() {
                $('#' + formID)[0].reset();
                $('#detailsTextarea'+ applicantID + '-' + saleID).removeClass('is-invalid is-valid');
                $('#detailsTextarea'+ applicantID + '-' + saleID).next('.invalid-feedback').remove();
            }

            // Set up modal events
            $('#' + modalID)
                .off('show.bs.modal') // Remove previous handlers to avoid duplicates
                .on('show.bs.modal', function() {
                    resetModal();
                    $('#clearCVModalLabel'+ applicantID + '-' + saleID).text(modalName); // Update title in case it changed
                })
                .off('hidden.bs.modal') // Remove previous handlers
                .on('hidden.bs.modal', resetModal);

            // Show the modal
            $('#' + modalID).modal('show');

            // Handle the save button click
            $('#saveClearCVButton'+ applicantID + '-' + saleID).off('click').on('click', function() {
                const notes = $('#detailsTextarea'+ applicantID + '-' + saleID).val();

                if (!notes) {
                    if (!notes) {
                        $('#detailsTextarea'+ applicantID + '-' + saleID).addClass('is-invalid');
                        if ($('#detailsTextarea'+ applicantID + '-' + saleID).next('.invalid-feedback').length === 0) {
                            $('#detailsTextarea'+ applicantID + '-' + saleID).after('<div class="invalid-feedback">Please provide details.</div>');
                        }
                    }
                
                    // Add event listeners to remove validation errors dynamically
                    $('#detailsTextarea'+ applicantID + '-' + saleID).on('input', function() {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // Remove validation errors if inputs are valid
                $('#detailsTextarea'+ applicantID + '-' + saleID).removeClass('is-invalid').addClass('is-valid');
                $('#detailsTextarea'+ applicantID + '-' + saleID).next('.invalid-feedback').remove();

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Send the data via AJAX
                $.ajax({
                    url: '{{ route("updateApplicantStatusByQuality") }}', // Replace with your endpoint
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        status: status,
                        _token: '{{ csrf_token() }}' // Directly include token in data
                    },
                    success: function(response) {
                        toastr.success(response.message);
                        $('#' + modalID).modal('hide'); // Close the modal
                        $('#' + formID)[0].reset(); // Clear the form
                        $('#detailsTextarea'+ applicantID + '-' + saleID).removeClass('is-valid'); // Remove valid class
                        $('#detailsTextarea'+ applicantID + '-' + saleID).next('.invalid-feedback').remove(); // Remove error message
                        
                        $('#applicants_table').DataTable().ajax.reload(); // Reload the DataTable
                    },
                    error: function(xhr) {
                        toastr.error('An error occurred while saving notes.');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        // Function to show the notes modal
        function viewNotesHistory(applicantID, saleID) {
            const modalId = 'viewNotesHistoryModal-' + applicantID + '-' + saleID;

            // Add the modal only once
            if ($('#' + modalId).length === 0) {
                $('body').append(
                    '<div class="modal fade" id="' + modalId + '" tabindex="-1" aria-labelledby="' + modalId + 'Label" aria-hidden="true">' +
                        '<div class="modal-dialog modal-dialog-scrollable modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="' + modalId + 'Label">Quality Notes History</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body text-center">' +
                                    '<div class="spinner-border text-primary" role="status">' +
                                        '<span class="visually-hidden">Loading...</span>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Show the modal and display loader immediately
            $('#' + modalId).modal('show');
            $('#' + modalId + ' .modal-body').html(
                '<div class="text-center py-4">' +
                    '<div class="spinner-border text-dark" role="status">' +
                        '<span class="visually-hidden">Loading...</span>' +
                    '</div>' +
                '</div>'
            );

            // Make an AJAX call to retrieve notes history data
            $.ajax({
                url: '{{ route("getQualityNotesHistory") }}',
                type: 'GET',
                data: {
                    applicant_id: applicantID,
                    sale_id: saleID,
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
                            const movedTab = note.moved_tab_to
                                ? note.moved_tab_to.charAt(0).toUpperCase() + note.moved_tab_to.slice(1).toLowerCase()
                                : 'Unknown';

                            const statusClass = (status == 1) ? 'bg-success' : 'bg-dark';
                            const statusText = (status == 1) ? 'Active' : 'Inactive';

                            notesHtml +=
                                '<div class="note-entry text-start">' +
                                    '<p><strong>Dated:</strong> ' + created + '&nbsp;&nbsp;' +
                                    '<span class="badge ' + statusClass + '">' + statusText + '</span>&nbsp;&nbsp;' +
                                    '<span class="badge bg-primary">Moved To: ' + movedTab + '</span></p>' +
                                    '<p><strong>Notes Detail:</strong><br>' + notes + '</p>' +
                                '</div><hr>';
                        });
                    }

                    $('#' + modalId + ' .modal-body').html(notesHtml);
                },
                error: function(xhr, status, error) {
                    console.log("Error fetching notes history: " + error);
                    $('#' + modalId + ' .modal-body').html('<p>There was an error retrieving the notes. Please try again later.</p>');
                }
            });
        }

         // Function to show the notes modal
        function viewManagerDetails(id) {
            const modalId = 'viewManagerDetailsModal' + id;

            // Add modal only once
            if ($(`#${modalId}`).length === 0) {
                $('body').append(
                    '<div class="modal fade" id="' + modalId + '" tabindex="-1" aria-labelledby="' + modalId + 'Label">' +
                        '<div class="modal-dialog modal-dialog-scrollable modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="' + modalId + 'Label">Manager Details</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body text-center">' +
                                    '<!-- Loader shown by default -->' +
                                    '<div class="spinner-border text-primary" role="status">' +
                                        '<span class="visually-hidden">Loading...</span>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Show the modal and keep loader visible until data is loaded
            $('#' + modalId).modal('show');
            $('#' + modalId + ' .modal-body').html(
                '<div class="text-center py-4">' +
                    '<div class="spinner-border text-dark" role="status">' +
                        '<span class="visually-hidden">Loading...</span>' +
                    '</div>' +
                '</div>'
            );

            // AJAX request to fetch manager details
            $.ajax({
                url: '{{ route("getModuleContacts") }}',
                type: 'GET',
                data: {
                    id: id,
                    module: 'Unit'
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

                            contactHtml +=
                                '<div class="note-entry text-start">' +
                                    '<p><strong>Name:</strong> ' + name + '</p>' +
                                    '<p><strong>Email:</strong> ' + email + '</p>' +
                                    '<p><strong>Phone:</strong> ' + phone + '</p>' +
                                    '<p><strong>Landline:</strong> ' + landline + '</p>' +
                                    '<p><strong>Notes:</strong> ' + note + '</p>' +
                                '</div><hr>';
                        });
                    }

                    // Replace loader with content
                    $('#' + modalId + ' .modal-body').html(contactHtml);
                },
                error: function() {
                    $('#' + modalId + ' .modal-body').html(
                        '<p>There was an error retrieving the manager details. Please try again later.</p>'
                    );
                }
            });
        }

        document.addEventListener('click', function (e) {
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
                                    <tr><th>Head Office</th><td>${job.office_name}</td></tr>
                                    <tr><th>Unit Name</th><td>${job.unit_name}</td></tr>
                                    <tr><th>Postcode</th><td>${job.postcode}</td></tr>
                                    <tr><th>Job Category</th><td>${job.job_category}</td></tr>
                                    <tr><th>Job Title</th><td>${job.job_title}</td></tr>
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
                formData.append('_token', '{{ csrf_token() }}');  // CSRF token

                // You can send the file to the server using an AJAX request or any method you prefer
                // Example using Fetch API
                fetch('{{ route("applicants.crmuploadCv") }}', {
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
@extends('layouts.vertical', ['title' => 'CRM', 'subTitle' => 'Home'])

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
                                <button id="openToPaid" class="btn btn-success my-1" style="display: none;">
                                    Open To Applicants
                                </button>
                                <!-- Rejected Cv -->
                                <div class="dropdown d-inline d-none" id="rejected_cv_export_email">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="rejected_cv_btn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i>
                                        <span class="btn-text">Export</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="rejected_cv_btn">
                                        <a class="dropdown-item export-btn" href="{{ route('salesExport', ['type' => 'rejected_cv']) }}">Export Emails</a>
                                    </div>
                                </div>
                                <!-- Declined -->
                                <div class="dropdown d-inline d-none" id="declined_export_email">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="declined_btn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i>
                                        <span class="btn-text">Export</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="declined_btn">
                                        <a class="dropdown-item export-btn" href="{{ route('salesExport', ['type' => 'declined']) }}">Export Emails</a>
                                    </div>
                                </div>
                                <!-- NOT ATTENDED -->
                                <div class="dropdown d-inline d-none" id="not_attended_export_email">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="not_attended_btn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i> <span class="btn-text">Export</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="not_attended_btn">
                                        <a class="dropdown-item export-btn" href="{{ route('salesExport', ['type' => 'not_attended']) }}">Export Emails</a>
                                    </div>
                                </div>

                                <!-- START DATE HOLD -->
                                <div class="dropdown d-inline d-none" id="start_date_hold_export_email">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="start_date_hold_btn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i> <span class="btn-text">Export</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="start_date_hold_btn">
                                        <a class="dropdown-item export-btn" href="{{ route('salesExport', ['type' => 'start_date_hold']) }}">Export Emails</a>
                                    </div>
                                </div>

                                <!-- DISPUTE -->
                                <div class="dropdown d-inline d-none" id="dispute_export_email">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dispute_btn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i> <span class="btn-text">Export</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dispute_btn">
                                        <a class="dropdown-item export-btn" href="{{ route('salesExport', ['type' => 'dispute']) }}">Export Emails</a>
                                    </div>
                                </div>

                                <!-- PAID -->
                                <div class="dropdown d-inline d-none" id="paid_export_email">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="paid_btn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i> <span class="btn-text">Export</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="paid_btn">
                                        <a class="dropdown-item export-btn" href="{{ route('salesExport', ['type' => 'paid']) }}">Export Emails</a>
                                    </div>
                                </div>
                               
                                <!-- Button Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton4" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterTab">Sent CVs</span>
                                    </button>
                                    <div class="dropdown-menu filter-dropdowns" aria-labelledby="dropdownMenuButton4">
                                        @can('crm-sent-cv-list')<a class="dropdown-item tab-filter" href="#">Sent CVs</a>@endcan
                                        <a class="dropdown-item tab-filter" href="#">Open CVs</a>
                                        <a class="dropdown-item tab-filter" href="#">Sent CVs (No Job)</a>
                                        <a class="dropdown-item tab-filter" href="#">Rejected CVs</a>
                                        <a class="dropdown-item tab-filter" href="#">Request</a>
                                        <a class="dropdown-item tab-filter" href="#">Request (No Job)</a>
                                        <a class="dropdown-item tab-filter" href="#">Request (No Response)</a>
                                        <a class="dropdown-item tab-filter" href="#">Rejected By Request</a>
                                        <a class="dropdown-item tab-filter" href="#">Confirmation</a>
                                        <a class="dropdown-item tab-filter" href="#">Rebook</a>
                                        <a class="dropdown-item tab-filter" href="#">Attended to Pre-Start Date</a>
                                        <a class="dropdown-item tab-filter" href="#">Declined</a>
                                        <a class="dropdown-item tab-filter" href="#">Not Attended</a>
                                        <a class="dropdown-item tab-filter" href="#">Start Date</a>
                                        <a class="dropdown-item tab-filter" href="#">Start Date Hold</a>
                                        <a class="dropdown-item tab-filter" href="#">Invoice</a>
                                        <a class="dropdown-item tab-filter" href="#">Invoice Sent</a>
                                        <a class="dropdown-item tab-filter" href="#">Dispute</a>
                                        <a class="dropdown-item tab-filter" href="#">Paid</a>
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
                                
                                <!-- Type Filter Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton3" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterType">All Types</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton3">
                                        <a class="dropdown-item type-filter" href="#">All Types</a>
                                        <a class="dropdown-item type-filter" href="#">Specialist</a>
                                        <a class="dropdown-item type-filter" href="#">Regular</a>
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
                                    <th>Agent</th>
                                    <th id="schedule_date" style="display:none;">Schedule Date</th>
                                    <th>Applicant Name</th>
                                    {{-- <th>Email</th> --}}
                                    <th width="15%">Phone / Landline</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>PostCode</th>
                                    <th>Job</th>
                                    <th>Head Office</th>
                                    <th>Unit</th>
                                    <th>PostCode</th>
                                    <th width="20%">Notes</th>
                                    <th id="paid_status" style="display:none;">Paid Status</th>
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
    
    <div id="send_sms_to_requested_applicant" class="modal fade send_sms_to_requested_applicant_Modal" tabindex="-1" aria-labelledby="send_sms_to_requested_applicant_ModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-top">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Request SMS To <span id="smsName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="#" method="POST" id="send_non_nurse_sms" class="form-horizontal">
                <div class="modal-body">
                    <div id="sent_cv_alert_non_nurse"></div>
                    <div class="form-group row">
                        <label class="col-form-label col-sm-2">Message Text:</label>
                        <div class="col-sm-10">
                            <input type="hidden" name="applicant_id" id="applicant_id">
                            <input type="hidden" name="applicant_phone_number" id="applicant_phone_number">
                            <input type="hidden" name="non_nurse_modal_id" id="non_nurse_modal_id">
                            <textarea name="details" id="smsBodyDetails" class="form-control" cols="40" rows="8" placeholder="TYPE HERE.." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="sendSMSToRequestedApplicant" class="btn btn-success">Send SMS</button>
                </div>
            </form>
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">

    <!-- Summernote JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
    <script>
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('copy-btn')) {
                const targetSelector = e.target.getAttribute('data-copy-target');
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
            // Initialize Summernote and set content
            $('.summernote').summernote({
                height: 200,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['fontsize', ['fontsize']],
                    ['color', []],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', []],
                    ['view', []]
                ]
            });
        });

        $(document).ready(function() {
            // Store filter values
            var tabFilter = '';
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

            // Initialize DataTable
            var table = $('#applicants_table').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: '{{ route('getCrmApplicantsAjaxRequest') }}',
                    type: 'GET',
                    data: function(d) {
                        d.tab_filter = tabFilter;
                        d.type_filter = currentTypeFilter;
                        d.category_filter = currentCategoryFilters;
                        d.title_filter = currentTitleFilters;
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
                    { data: 'show_created_at', name: 'show_created_at' },
                    { data: 'user_name', name: 'users.name' },
                    { 
                        data: 'schedule_date', 
                        name: 'interviews.schedule_date', 
                        visible: tabFilter.toLowerCase() === 'confirmation',
                        createdCell: function(td, cellData, rowData, row, col) {
                            if (cellData) {
                                $(td).text(cellData); // Use moment.js if needed, e.g., moment(cellData).format('YYYY-MM-DD')
                            }
                        }
                    },
                    { data: 'applicant_name', name: 'applicants.applicant_name' },
                    // { data: 'applicant_email', name: 'applicants.applicant_email' },
                    {
                        data: 'applicantPhone',
                        name: 'applicantPhone',               // ← Use the same as data key
                        orderable: false,
                        searchable: true,
                    },
                    { data: 'job_title', name: 'job_titles.name' },
                    { data: 'job_category', name: 'job_categories.name' },
                    { data: 'applicant_postcode', name: 'applicants.applicant_postcode' },
                    { data: 'job_details', name: 'job_details' },
                    { data: 'office_name', name: 'offices.office_name' },
                    { data: 'unit_name', name: 'units.unit_name' },
                    { data: 'sale_postcode', name: 'sales.sale_postcode' },
                    { data: 'notes_detail', name: 'notes_detail', orderable: false, searchable: false },
                    { 
                        data: 'paid_status', 
                        name: 'applicants.paid_status', 
                        visible: tabFilter.toLowerCase() === 'paid',
                        createdCell: function(td, cellData, rowData, row, col) {
                            if (cellData) {
                                let badgeClass = 'bg-secondary';
                                let label = cellData;
                                if (cellData.toLowerCase() === 'open') {
                                    badgeClass = 'bg-success';
                                } else if (cellData.toLowerCase() === 'pending') {
                                    badgeClass = 'bg-warning text-dark';
                                } else if (cellData.toLowerCase() === 'close') {
                                    badgeClass = 'bg-dark';
                                }
                                label = label.charAt(0).toUpperCase() + label.slice(1).toLowerCase();
                                $(td).html(`<span class="badge ${badgeClass}">${label}</span>`);
                            } else {
                                $(td).html('');
                            }
                        }
                    },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                columnDefs: [
                    {
                        targets: [8, 9, 12, 15], // job_details, office_name, sale_postcode, notes_detail
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');
                        }
                    }
                ],
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
                        $('#applicants_table tbody').empty().html('<tr><td colspan="100%" class="text-center">Data not found</td></tr>');
                        return;
                    }

                    let paginationHtml = `
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-rounded mb-0">
                                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                                        <a class="page-link" href="javascript:void(0);" aria-label="Previous" onclick="movePage('previous')">
                                            <span aria-hidden="true">«</span>
                                        </a>
                                    </li>`;

                    const visiblePages = 3;
                    let start = Math.max(2, currentPage - 1);
                    let end = Math.min(totalPages - 1, currentPage + 1);

                    paginationHtml += `<li class="page-item ${currentPage === 1 ? 'active' : ''}">
                        <a class="page-link" href="javascript:void(0);" onclick="movePage(1)">1</a>
                    </li>`;

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

                    paginationHtml += `
                        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0);" aria-label="Next" onclick="movePage('next')">
                                <span aria-hidden="true">»</span>
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
                }
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

            // Status filter handler
            $('.tab-filter').on('click', function() {
                tabFilter = $(this).text().toLowerCase();
                const formattedText = tabFilter
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
                $('#showFilterTab').html(formattedText);
                showLoader();

                // Toggle column visibility
                table.column(3).visible(formattedText === 'Confirmation');
                table.column(14).visible(formattedText === 'Paid');

                // Toggle UI elements
                $('#openToPaid').toggle(formattedText === 'Paid');
                $('#schedule_date').toggle(formattedText === 'Confirmation');
                $('#rejected_cv_export_email').toggleClass('d-none', formattedText !== 'Rejected Cvs');
                $('#declined_export_email').toggleClass('d-none', formattedText !== 'Declined');
                $('#not_attended_export_email').toggleClass('d-none', formattedText !== 'Not Attended');
                $('#start_date_hold_export_email').toggleClass('d-none', formattedText !== 'Start Date Hold');
                $('#dispute_export_email').toggleClass('d-none', formattedText !== 'Dispute');
                $('#paid_export_email').toggleClass('d-none', formattedText !== 'Paid');
                $('#paid_status').toggle(formattedText === 'Paid');

                table.ajax.reload();
            });

            // Category filter handler
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

            // Title filter handler
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
    </script>

    <!--- other modal functions -->
    <script>
        // Function to show the notes modal
        function updateCrmNotesModal(applicantID, saleID, tab) {
            const formId = `#updateCrmNotesForm${applicantID}-${saleID}`;
            const modalId = `#updateCrmNotesModal${applicantID}-${saleID}`;
            const detailsId = `#details${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const reasonId = `#reasonDropdown${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveUpdateCrmNotesButton`);
            const rejectButton = $(`${formId} .crmSentCVRejectButton`);

            // Reset form when modal opens (clear fields, validation, and alerts)
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                $(formId)[0].reset();  // Resets all form fields to their initial state
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
                $(reasonId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
                $(notificationAlert).html('').hide();
                $(rejectButton).hide();
            });

            // Clear previous validation states (this can now be removed if redundant, but kept for safety)
            $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
            $(reasonId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
            $(notificationAlert).html('').hide();

            // Show/hide reject button based on reason selection
            $(reasonId).off('change.rejectButton').on('change.rejectButton', function () {
                // if ($(this).val() === 'position_filled') {
                    $(rejectButton).show();
                // } else {
                //     $(rejectButton).hide();
                // }
            });

            // Handle save button click
            rejectButton.off('click').on('click', function () {
                const notes = $(detailsId).val();
                const reason = $(reasonId).val();

                $(detailsId).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback').remove();
                $(reasonId).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback').remove();

                // Validate inputs
                if (!notes || !reason) {
                    if (!notes) {
                        $(detailsId).addClass('is-invalid');
                        if ($(detailsId).next('.invalid-feedback').length === 0) {
                            $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                        }
                    }
                    if (!reason) {
                        $(reasonId).addClass('is-invalid');
                        if ($(reasonId).next('.invalid-feedback').length === 0) {
                            $(reasonId).after('<div class="invalid-feedback">Please select a reason.</div>');
                        }
                    }

                    $(detailsId).off('input').on('input', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback').remove();
                        }
                    });
                    $(reasonId).off('change').on('change', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const form = $(formId)[0];

                $.ajax({
                    url: "{{ route('crmSendRejectedCv') }}",
                    method: 'POST',
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        reason: reason,
                        tab: tab,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        $(notificationAlert).html(`
                            <div class="notification-alert success">
                                ${response.message}
                            </div>
                        `).show();

                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function (xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                An error occurred while saving notes.
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Handle save button click
            saveButton.off('click').on('click', function () {
                const notes = $(detailsId).val();
                const reason = $(reasonId).val();

                $(detailsId).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback').remove();
                $(reasonId).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback').remove();

                // Validate inputs
                if (!notes || !reason) {
                    if (!notes) {
                        $(detailsId).addClass('is-invalid');
                        if ($(detailsId).next('.invalid-feedback').length === 0) {
                            $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                        }
                    }
                    if (!reason) {
                        $(reasonId).addClass('is-invalid');
                        if ($(reasonId).next('.invalid-feedback').length === 0) {
                            $(reasonId).after('<div class="invalid-feedback">Please select a reason.</div>');
                        }
                    }

                    $(detailsId).off('input').on('input', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback').remove();
                        }
                    });
                    $(reasonId).off('change').on('change', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const form = $(formId)[0];

                $.ajax({
                    url: "{{ route('updateCrmNotes') }}",
                    method: 'POST',
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        reason: reason,
                        tab: tab,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        $(notificationAlert).html(`
                            <div class="notification-alert success">
                                ${response.message}
                            </div>
                        `).show();

                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function (xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                An error occurred while saving notes.
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Sent CV To Request Modal */
        function crmSentCvToRequestModal(applicantID, saleID, tab, smsMessage) {
            const formId = `#crmSendRequestForm${applicantID}-${saleID}`;
            const modalId = `#crmSentCvToRequestModal${applicantID}-${saleID}`;
            const detailsId = `#sendRequestDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmSendRequestButton`);

            // Capture data from trigger <a> element
            if(smsMessage !== ''){
                const triggerEl = document.querySelector(`[data-applicant-id="${applicantID}"][data-sale-id="${saleID}"]`);
                const applicantName = triggerEl?.getAttribute('data-applicant-name') || '';
                const applicantPhone = triggerEl?.getAttribute('data-applicant-phone') || '';
                const applicantUnit = triggerEl?.getAttribute('data-applicant-unit') || '';
                const smsTriggerId = modalId; // for reference back

                // ✅ Show SMS Modal with pre-filled data
                $('#smsName').text(applicantName);
                $('#applicant_phone_number').val(applicantPhone);
                $('#applicant_id').val(applicantID);
                $('#smsBodyDetails').val(smsMessage);

                $('#send_sms_to_requested_applicant').modal('show');
            }

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                // Show loading state
                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        tab : tab,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $(notificationAlert).html(`
                            <div class="notification-alert success">
                                ${response.message}
                            </div>
                        `).show();

                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Sent CV to Revert in Quality */
        function crmRevertInQualityModal(applicantID, saleID, tab) {
            const formId = `#crmRevertInQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertInQualityModal${applicantID}-${saleID}`;
            const detailsId = `#revertInQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertInQualityButton`);

            // 🧼 Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        tab : tab,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        // Function to show the notes modal
        function updateCrmNoJobNotesModal(applicantID, saleID) {
            const formId = `#updateCrmNoJobNotesForm${applicantID}-${saleID}`;
            const modalId = `#updateCrmNoJobNotesModal${applicantID}-${saleID}`;
            const detailsId = `#noJobdetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const reasonId = `#reasonDropdownNoJob${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveupdateCrmNoJobNotesButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
                $(reasonId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Clear previous validation states
           
            $(notificationAlert).html('').hide(); // Clear previous alerts

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Remove validation errors if inputs are valid
                $(detailsId).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback').remove();
                $(reasonId).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback').remove();

                const notes = $(detailsId).val();
                const reason = $(reasonId).val();

                // Validate inputs
                if (!notes || !reason) {
                    if (!notes) {
                        $(detailsId).addClass('is-invalid');
                        if ($(detailsId).next('.invalid-feedback').length === 0) {
                            $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                        }
                    }
                    if (!reason) {
                        $(reasonId).addClass('is-invalid');
                        if ($(reasonId).next('.invalid-feedback').length === 0) {
                            $(reasonId).after('<div class="invalid-feedback">Please select a reason.</div>');
                        }
                    }

                    // Add event listeners to remove validation errors dynamically
                    $(detailsId).off('input').on('input', function() {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback').remove();
                        }
                    });
                    $(reasonId).off('change').on('change', function() {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid').next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // Show loading state
                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        reason: reason,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $(notificationAlert).html(`
                            <div class="notification-alert success">
                                ${response.message}
                            </div>
                        `).show();

                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                         $(notificationAlert).html(`
                                <div class="notification-alert error">
                                    An error occurred while saving notes.
                                </div>
                            `).show();
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Requested Cv to Sent CV */
        function crmRevertRequestedCvToSentCvModal(applicantID, saleID) {
            const formId = `#crmRevertRequestedCvToSentCvForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertRequestedCvToSentCvModal${applicantID}-${saleID}`;
            const detailsId = `#RevertRevertRequestedCvToSentCvDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertRequestedCvToSentCvButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // 💾 Save button handler
            saveButton.off('click').on('click', function () {
                // Clear previous validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();

                // Validate input
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const form = $(formId)[0];

                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Requested Cv to Quality */
        function crmRevertRequestedCvToQualityModal(applicantID, saleID) {
            const formId = `#crmRevertRequestedCvToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertRequestedCvToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#RevertRevertRequestedCvToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertRequestedCvToQualityButton`);

            // 🧼 Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // 💾 Save button logic
            saveButton.off('click').on('click', function () {
                // Clear previous validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();

                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const form = $(formId)[0];

                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function (xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Rejected Cv to Sent CV */
        function crmRevertRejectedCvToSentCvModal(applicantID, saleID) {
            const formId = `#crmRevertRejectedCvToSentCvForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertRejectedCvToSentCvModal${applicantID}-${saleID}`;
            const detailsId = `#RevertRevertRejectedCvToSentCvDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertRejectedCvToSentCvButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // 💾 Save button handler
            saveButton.off('click').on('click', function () {
                // Clear previous validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();

                // Validate input
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const form = $(formId)[0];

                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Rejected Cv to Quality */
        function crmRevertRejectedCvToQualityModal(applicantID, saleID) {
            const formId = `#crmRevertRejectedCvToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertRejectedCvToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#RevertRevertRejectedCvToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertRejectedCvToQualityButton`);

            // 🧼 Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // 💾 Save button logic
            saveButton.off('click').on('click', function () {
                // Clear previous validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();

                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const form = $(formId)[0];

                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function (xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Sent No Job to Request Modal */
        function crmSendNoJobRequestModal(applicantID, saleID) {
            const formId = `#crmSendNoJobRequestForm${applicantID}-${saleID}`;
            const modalId = `#crmSendNoJobRequestModal${applicantID}-${saleID}`;
            const detailsId = `#sendNoJobRequestDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmSendNoJobRequestButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                // Show loading state
                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $(notificationAlert).html(`
                            <div class="notification-alert success">
                                ${response.message}
                            </div>
                        `).show();

                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }
                
        /** Revert Sent Cv No Job to Quality Modal */
        function crmSentCvNoJobRevertInQualityModal(applicantID, saleID) {
            const formId = `#crmNoJobRevertInQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmSentCvNoJobRevertInQualityModal${applicantID}-${saleID}`;
            const detailsId = `#revertNoJobInQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmNoJobRevertInQualityButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }
        
        /** Revert Request Reject to Sent CV Modal */
        function crmRejectRequestRevertToSentCvModal(applicantID, saleID) {
            const formId = `#crmRevertToSentCVForm${applicantID}-${saleID}`;
            const modalId = `#crmRejectRequestRevertToSentCvModal${applicantID}-${saleID}`;
            const detailsId = `#crmRevertToSentCVDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertToSentCVButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                   
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Request Reject to Request Modal */
        function crmRejectRequestRevertToRequestModal(applicantID, saleID) {
            const formId = `#crmRevertToRequestForm${applicantID}-${saleID}`;
            const modalId = `#crmRejectRequestRevertToRequestModal${applicantID}-${saleID}`;
            const detailsId = `#crmRevertToRequestDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertToRequestButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Request Reject to Quality Modal */
        function crmRejectRequestRevertToQualityModal(applicantID, saleID) {
            const formId = `#crmRevertRequestRejectedToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmRejectRequestRevertToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#crmRevertRequestRejectedToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertRequestRejectedToQualityButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }
        
        /** Revert Confirmation to Quality Modal */
        function crmConfirmationRevertToQualityModal(applicantID, saleID) {
            const formId = `#crmConfirmationRevertToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmConfirmationRevertToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#crmConfirmationRevertToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertConfirmationToQualityButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }
        
        /** Revert Confirmation to Quality Modal */
        function crmRebookRevertToQualityModal(applicantID, saleID) {
            const formId = `#crmRebookRevertToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmRebookRevertToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#crmRebookRevertToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertRebookToQualityButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }
        
        /** Revert Attended to Quality Modal */
        function crmAttendedRevertToQualityModal(applicantID, saleID) {
            const formId = `#crmAttendedRevertToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmAttendedRevertToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#crmAttendedRevertToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertAttendedToQualityButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }
        
        /** Revert Declined to Quality Modal */
        function crmDeclinedRevertToQualityModal(applicantID, saleID) {
            const formId = `#crmDeclinedRevertToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmDeclinedRevertToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#crmDeclinedRevertToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertDeclinedToQualityButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Confirmation to Request Modal */
        function crmRevertConfirmationToRequestModal(applicantID, saleID) {
            const formId = `#crmRevertConfirmationToRequestForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertConfirmationToRequestModal${applicantID}-${saleID}`;
            const detailsId = `#crmRevertConfirmationToRequestDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertConfirmationToRequestButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Rebook to Confirmation */
        // function crmRevertRebookToConfirmationModal(applicantID, saleID) {
        //     const formId = `#crmRevertRebookToConfirmationForm${applicantID}-${saleID}`;
        //     const modalId = `#crmRevertRebookToConfirmationModal${applicantID}-${saleID}`;
        //     const detailsId = `#crmRevertRebookToConfirmationDetails${applicantID}-${saleID}`;
        //     const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
        //     const saveButton = $(`${formId} .saveCrmRevertRebookToConfirmationButton`);

        //     // Reset modal when it is about to be shown
        //     $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
        //         // Reset form fields
        //         $(formId)[0].reset();

        //         // Remove validation styles and messages
        //         $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

        //         // Hide any previous alerts
        //         $(notificationAlert).html('').hide();
        //     });
        //     // Handle save button click
        //     saveButton.off('click').on('click', function() {
        //         // Reset validation
        //         $(detailsId).removeClass('is-invalid is-valid')
        //                 .next('.invalid-feedback').remove();
                
        //         // Validate inputs
        //         const notes = $(detailsId).val();

        //         if (!notes) {
        //             $(detailsId).addClass('is-invalid');
        //             $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
        //             return;
        //         }

        //         const btn = $(this);
        //         const originalText = btn.html();
        //         btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

        //         // Get form properly
        //         const form = $(formId)[0];

        //         // Send data via AJAX
        //         $.ajax({
        //             url: form.action,
        //             method: form.method,
        //             data: {
        //                 applicant_id: applicantID,
        //                 sale_id: saleID,
        //                 details: notes,
        //                 _token: '{{ csrf_token() }}'
        //             },
        //             success: function(response) {
        //                 const alertClass = response.success ? 'success' : 'error';
        //                 $(notificationAlert).html(`
        //                     <div class="notification-alert ${alertClass}">
        //                         ${response.message}
        //                     </div>
        //                 `).show();

        //                 if (response.success) {
        //                     setTimeout(() => {
        //                         $(modalId).modal('hide');
        //                         $(formId)[0].reset();
        //                         $('#applicants_table').DataTable().ajax.reload();
        //                     }, 2000);
        //                 }
        //             },
        //             error: function(xhr) {
        //                 $(notificationAlert).html(`
        //                     <div class="notification-alert error">
        //                         ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
        //                     </div>
        //                 `).show();
        //             },
        //             complete: function () {
        //                 btn.prop('disabled', false).html(originalText);
        //             }
        //         });
        //     });
        // }

        // function crmReScheduleInterviewModal(applicantID, saleID) {
        //     const formId = `#crmReScheduleInterviewForm${applicantID}-${saleID}`;
        //     const modalId = `#crmReScheduleInterviewModal${applicantID}-${saleID}`;
        //     const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
        //     const schedule_date = `#reschedule_date${applicantID}-${saleID}`;
        //     const schedule_time = `#reschedule_time${applicantID}-${saleID}`;
        //     const saveButton = $(`${formId} .saveCrmReScheduleInterviewButton`);

        //     // Reset modal when it is about to be shown
        //     $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
        //         // Reset form fields
        //         $(formId)[0].reset();

        //         // Remove validation styles and messages
        //         $(schedule_date).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
        //         $(schedule_time).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

        //         // Hide any previous alerts
        //         $(notificationAlert).html('').hide();
        //     });

        //     // Handle save button click
        //     saveButton.off('click').on('click', function() {
        //         // Reset validation
        //         $(schedule_date).removeClass('is-invalid is-valid')
        //                 .next('.invalid-feedback').remove();
                        
        //         $(schedule_time).removeClass('is-invalid is-valid')
        //                 .next('.invalid-feedback').remove();
                
        //         // Validate inputs
        //         const sdate = $(schedule_date).val();
        //         const stime = $(schedule_time).val();

        //         // Add date validation
        //         if (sdate && new Date(sdate) < new Date()) {
        //             $(schedule_date).addClass('is-invalid');
        //             $(schedule_date).after('<div class="invalid-feedback">Date must be in the future.</div>');
        //             return;
        //         }
        //         if (!stime) {
        //             $(schedule_time).addClass('is-invalid');
        //             $(schedule_time).after('<div class="invalid-feedback">Please provide details.</div>');
        //             return;
        //         }

        //         // Show loading state
        //         const btn = $(this);
        //         const originalText = btn.html();
        //         btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

        //         // Get form properly
        //         const form = $(formId)[0];

        //         // Send data via AJAX
        //         $.ajax({
        //             url: form.action,
        //             method: form.method,
        //             data: {
        //                 applicant_id: applicantID,
        //                 sale_id: saleID,
        //                 schedule_date: sdate,
        //                 schedule_time: stime,
        //                 _token: '{{ csrf_token() }}'
        //             },
        //             success: function(response) {
        //                 $(notificationAlert).html(`
        //                     <div class="notification-alert success">
        //                         ${response.message}
        //                     </div>
        //                 `).show();

        //                 setTimeout(() => {
        //                     $(modalId).modal('hide');
        //                     $(formId)[0].reset();
        //                     // $('#applicants_table').DataTable().ajax.reload();
        //                 }, 2000);
        //             },
        //             error: function(xhr) {
        //                 let errorMessage = 'An error occurred while scheduling the interview.';
        //                 if (xhr.responseJSON) {
        //                     if (xhr.responseJSON.message) {
        //                         errorMessage = xhr.responseJSON.message;
        //                     } else if (xhr.responseJSON.errors) {
        //                         // Handle validation errors
        //                         errorMessage = Object.values(xhr.responseJSON.errors).join('<br>');
        //                     }
        //                 }
        //                 $(notificationAlert).html(`<div class="notification-alert error">${errorMessage}</div>`).show();
        //             },
        //             complete: function() {
        //                 btn.prop('disabled', false).html(originalText);
        //             }
        //         });
        //     });
        // }
      function crmRevertRebookToConfirmationModal(applicantID, saleID) {

    const formId = `#crmRevertRebookToConfirmationForm${applicantID}-${saleID}`;
    const modalId = `#crmRevertRebookToConfirmationModal${applicantID}-${saleID}`;
    const detailsId = `#crmRevertRebookToConfirmationDetails${applicantID}-${saleID}`;

    const saveButton = $(`${formId} .saveCrmRevertRebookToConfirmationButton`);

    saveButton.off('click').on('click', function () {

        $(detailsId).removeClass('is-invalid').next('.invalid-feedback').remove();

        if (!$(detailsId).val()) {
            $(detailsId).addClass('is-invalid')
                .after('<div class="invalid-feedback">Please provide details.</div>');
            return;
        }

        // Close first modal → open second
        $(modalId).modal('hide');

        setTimeout(() => {
            $(`#crmReScheduleInterviewModal${applicantID}-${saleID}`)
                .data('applicant-id', applicantID)
                .data('sale-id', saleID)
                .modal('show');
        }, 300);
    });
}
$(document).on('click', '.saveCrmReScheduleInterviewButton', function () {

    const btn = $(this);
    const applicantID = btn.data('applicant-id');
    const saleID = btn.data('sale-id');

    const revertForm = `#crmRevertRebookToConfirmationForm${applicantID}-${saleID}`;
    const scheduleForm = `#crmReScheduleInterviewForm${applicantID}-${saleID}`;
    const modalId = `#crmReScheduleInterviewModal${applicantID}-${saleID}`;
    const alertBox = `.notificationAlert${applicantID}-${saleID}`;

    const sdate = $(`#reschedule_date${applicantID}-${saleID}`).val();
    const stime = $(`#reschedule_time${applicantID}-${saleID}`).val();

    // Reset validation
    $('.invalid-feedback').remove();
    $('.is-invalid').removeClass('is-invalid');

    if (!sdate || !stime) {
        if (!sdate) $(`#reschedule_date${applicantID}-${saleID}`).addClass('is-invalid');
        if (!stime) $(`#reschedule_time${applicantID}-${saleID}`).addClass('is-invalid');
        return;
    }

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

    // =======================
    // AJAX #1 → REVERT STATUS
    // =======================
    $.ajax({
        url: $(revertForm).attr('action'),
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data: {
            applicant_id: $(revertForm + ' input[name="applicant_id"]').val(),
            sale_id: $(revertForm + ' input[name="sale_id"]').val(),
            details: $(revertForm + ' textarea[name="details"]').val()
        },
        success: function () {

            // =======================
            // AJAX #2 → RESCHEDULE
            // =======================
            $.ajax({
                url: $(scheduleForm).attr('action'),
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: {
                    applicant_id: applicantID,
                    sale_id: saleID,
                    schedule_date: sdate,
                    schedule_time: stime
                },
                success: function (response) {
                    $(alertBox).html(
                        `<div class="notification-alert success">${response.message}</div>`
                    ).show();

                    setTimeout(() => {
                        $(modalId).modal('hide');
                        $(revertForm)[0].reset();
                        $(scheduleForm)[0].reset();
                        $('#applicants_table').DataTable().ajax.reload();
                    }, 1500);
                },
                error: function (xhr) {
                    showError(xhr);
                }
            });
        },
        error: function (xhr) {
            showError(xhr);
        },
        complete: function () {
            btn.prop('disabled', false).html('Schedule');
        }
    });

    function showError(xhr) {
        $(alertBox).html(
            `<div class="notification-alert error">
                ${xhr.responseJSON?.message || 'Something went wrong'}
            </div>`
        ).show();
    }
});

        
        /** Schedule Interview Modal */
        function crmScheduleInterviewModal(applicantID, saleID) {
            const formId = `#crmScheduleInterviewForm${applicantID}-${saleID}`;
            const modalId = `#crmScheduleInterviewModal${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const schedule_date = `#schedule_date${applicantID}-${saleID}`;
            const schedule_time = `#schedule_time${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmScheduleInterviewButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(schedule_date).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
                $(schedule_time).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(schedule_date).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                        
                $(schedule_time).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const sdate = $(schedule_date).val();
                const stime = $(schedule_time).val();

                // Add date validation
                if (sdate && new Date(sdate) < new Date()) {
                    $(schedule_date).addClass('is-invalid');
                    $(schedule_date).after('<div class="invalid-feedback">Date must be in the future.</div>');
                    return;
                }
                if (!stime) {
                    $(schedule_time).addClass('is-invalid');
                    $(schedule_time).after('<div class="invalid-feedback">Please provide details.</div>');
                    return;
                }

                // Show loading state
                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        schedule_date: sdate,
                        schedule_time: stime,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $(notificationAlert).html(`
                            <div class="notification-alert success">
                                ${response.message}
                            </div>
                        `).show();

                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while scheduling the interview.';
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseJSON.errors) {
                                // Handle validation errors
                                errorMessage = Object.values(xhr.responseJSON.errors).join('<br>');
                            }
                        }
                        $(notificationAlert).html(`<div class="notification-alert error">${errorMessage}</div>`).show();
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }
        
        /** Send Email on Schedule Interview Request */
        function crmSendApplicantEmailRequestModal(applicantID, saleID) {
            const formId = `#crmSendApplicantEmailRequestForm${applicantID}-${saleID}`;
            const modalId = `#crmSendApplicantEmailRequestModal${applicantID}-${saleID}`;
            const emailAddressTo = `#email_to_requested_${applicantID}-${saleID}`;
            const emailAddressFrom = `#email_from_requested_${applicantID}-${saleID}`;
            const emailSubject = `#email_subject_requested_${applicantID}-${saleID}`;
            const emailBody = `#email_body_requested_${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmSendApplicantEmailRequestButton`);

            // Initialize Summernote and set content
            $(`${emailBody}`).summernote({
                height: 200,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['fontsize', ['fontsize']],
                    ['color', []],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                    ['view', []]
                ]
            });
            
            // Hide any previous alerts
            $(notificationAlert).empty().hide();

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(emailAddressTo).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
                $(emailSubject).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
                $(emailBody).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(emailAddressTo).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                $(emailSubject).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                $(emailBody).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const emailTo = $(emailAddressTo).val().trim();
                const emailFrom = $(emailAddressFrom).val().trim();
                const subjectTxt = $(emailSubject).val().trim();
                const bodyTxt = $(emailBody).val().trim();

                if (!bodyTxt) {
                    $(emailBody).addClass('is-invalid');
                    $(emailBody).after('<div class="invalid-feedback">Please provide email body.</div>');
                    return;
                }
                if (!subjectTxt) {
                    $(emailSubject).addClass('is-invalid');
                    $(emailSubject).after('<div class="invalid-feedback">Please provide email subject.</div>');
                    return;
                }
                if (!emailTo) {
                    $(emailTo).addClass('is-invalid');
                    $(emailTo).after('<div class="invalid-feedback">Please provide email address.</div>');
                    return;
                }

                // Show loading state
                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        email_from: emailFrom,
                        email_to: emailTo,
                        email_subject: subjectTxt,
                        email_body: bodyTxt,
                        email_title: subjectTxt, // Adjust as needed
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $(notificationAlert).html(`
                            <div class="notification-alert success">
                                ${response.message}
                            </div>
                        `).show();

                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Move to confirmation */
        function crmMoveToconfirmationModal(applicantID, saleID) {
            const formId = `#crmMoveToconfirmationForm${applicantID}-${saleID}`;
            const modalId = `#crmMoveToconfirmationModal${applicantID}-${saleID}`;
            const detailsId = `#crmMoveToconfirmationDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            
            console.log('Initializing crmMoveToconfirmationModal with applicantID:', applicantID, 'saleID:', saleID);
            
            const initModal = () => {
                resetValidation();
                attachEventHandlers();
            };

            const resetValidation = () => {
                $(detailsId).removeClass('is-invalid is-valid')
                    .next('.invalid-feedback').remove();
                $(notificationAlert).html('').hide();
            };

            const validateNotes = () => {
                const notes = $(detailsId).val().trim();
                console.log('Validating notes:', notes);
                if (!notes) {
                    $(detailsId).addClass('is-invalid')
                        .after('<div class="invalid-feedback">Please provide details.</div>');
                    return false;
                }
                return true;
            };

            const handleSubmit = (actionType) => {
                if (!validateNotes()) return false;

                const endpoints = {
                    confirm: "{{ route('crmRequestConfirm') }}",
                    save: "{{ route('crmRequestSave') }}",
                    reject: "{{ route('crmRequestReject') }}"
                };

                const btn = $(`${formId} .savecrmConfirmation${actionType === 'confirm' ? 'Button' : actionType === 'save' ? 'SaveButton' : 'RejectButton'}`);
                const originalText = btn.html();
                
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const formData = {
                    applicant_id: applicantID,
                    sale_id: saleID,
                    details: $(detailsId).val().trim(),
                    _token: '{{ csrf_token() }}'
                };

                console.log('Submitting form data for action:', actionType, formData);

                $.ajax({
                    url: endpoints[actionType],
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        showSuccess(response.message);
                        setTimeout(() => {
                            $(modalId).modal('hide');
                            if (actionType === 'reject') {
                                $(`#crmSendApplicantEmailOnRequestRejectModal${applicantID}-${saleID}`).modal('hide');
                            }
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        console.error('Form submission error:', xhr.status, xhr.responseJSON);
                        showError(xhr.responseJSON?.message || `Failed to process ${actionType} (Status: ${xhr.status})`);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            };

            const showSuccess = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-success alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            const showError = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-danger alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            const attachEventHandlers = () => {
                const rejectButtonSelector = `${formId} .savecrmMoveToconfirmationRejectButton`;
                const button = $(rejectButtonSelector);
                const reject_template = button.data('request-reject-template');
                console.log('Attaching event handler to reject button:', rejectButtonSelector);

                $(rejectButtonSelector).off('click').on('click', function () {
                    console.log('Reject button clicked');
                    resetValidation();
                    
                    if (validateNotes()) {
                        const notes = $(detailsId).val().trim();

                        // 🔥 Get values from the clicked button itself
                        const reject_template = $(this).data('request-reject-template');
                        const reject_subject = $(this).data('request-reject-subject');
                        const reject_slug = $(this).data('request-reject-slug');

                        crmSendApplicantEmailOnRequestRejectModal(applicantID, saleID, notes);

                        const emailModalId = `#crmSendApplicantEmailOnRequestRejectModal${applicantID}-${saleID}`;
                        const templateFieldId = `#request_reject_template${applicantID}-${saleID}`;
                        const subjectFieldId = `#request_reject_subject${applicantID}-${saleID}`;
                        const slugFieldId = `#request_reject_slug${applicantID}-${saleID}`;

                        console.log('Opening email modal:', emailModalId);
                        console.log('Template:', reject_template);

                        if ($(emailModalId).length) {
                            $(subjectFieldId).val(reject_subject);
                            $(slugFieldId).val(reject_slug);
                            // Initialize Summernote and set content
                            $(`${templateFieldId}`).summernote({
                                height: 200,
                                toolbar: [
                                    ['style', ['bold', 'italic', 'underline', 'clear']],
                                    ['font', ['strikethrough', 'superscript', 'subscript']],
                                    ['fontsize', ['fontsize']],
                                    ['color', []],
                                    ['para', ['ul', 'ol', 'paragraph']],
                                    ['insert', ['link']],
                                    ['view', []]
                                ]
                            });
                            $(`${templateFieldId}`).summernote('code', reject_template);
                            $(emailModalId).modal('show');
                        } else {
                            console.error('Email modal not found in DOM:', emailModalId);
                            showError('Email modal not found. Please contact support.');
                        }
                    }
                });

                $(`${formId} .savecrmMoveToconfirmationRequestButton`).off('click').on('click', () => handleSubmit('confirm'));
                $(`${formId} .savecrmConfirmationSaveButton`).off('click').on('click', () => handleSubmit('save'));

                $(modalId).off('hidden.bs.modal').on('hidden.bs.modal', () => {
                    $(formId)[0].reset();
                    resetValidation();
                });
            };

            initModal();
        }

        /** Move to confirmation */
        function crmMarkRequestConfirmOrRejectModal(applicantID, saleID) {

            const formId = `#crmMarkRequestConfirmOrRejectForm${applicantID}-${saleID}`;
            const modalId = `#crmMarkRequestConfirmOrRejectModal${applicantID}-${saleID}`;
            const detailsId = `#crmMarkRequestConfirmOrRejectDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;

            console.log(
                'Initializing crmMarkRequestConfirmOrRejectModal',
                { applicantID, saleID }
            );

            const resetValidation = () => {
                $(detailsId)
                    .removeClass('is-invalid is-valid')
                    .next('.invalid-feedback')
                    .remove();

                $(notificationAlert).html('').hide();
            };

            const validateNotes = () => {
                const notes = $(detailsId).val().trim();
                console.log('Validating notes:', notes);

                // remove old errors first
                $(detailsId).next('.invalid-feedback').remove();
                $(detailsId).removeClass('is-invalid is-valid');

                if (!notes) {
                    $(detailsId)
                        .addClass('is-invalid')
                        .after('<div class="invalid-feedback">Please provide details.</div>');
                    return false;
                }

                $(detailsId).addClass('is-valid');
                return true;
            };

            const showSuccess = (message) => {
                $(notificationAlert)
                    .html(`
                        <div class="alert alert-success alert-dismissible fade show">
                            ${message || 'Completed successfully.'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `)
                    .show();
            };

            const showError = (message) => {
                $(notificationAlert)
                    .html(`
                        <div class="alert alert-danger alert-dismissible fade show">
                            ${message || 'Something went wrong. Please try again.'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `)
                    .show();
            };

            const handleSubmit = (actionType) => {

                if (!validateNotes()) return;

                const endpoints = {
                    confirm: "{{ route('crmRequestNoResponseToConfirmedRequest') }}",
                    reject: "{{ route('crmRequestNoResponseToReject') }}"
                };

                if (!endpoints[actionType]) {
                    console.error('Unknown action type:', actionType);
                    showError('Invalid action.');
                    return;
                }

                const btnSelector =
                    actionType === 'confirm'
                        ? `${formId} .savecrmMarkRequestButtonConfirm`
                        : `${formId} .savecrmMarkRequestButtonReject`;

                const btn = $(btnSelector);
                const originalText = btn.html();

                btn.prop('disabled', true).html(`
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...
                `);

                const formData = {
                    applicant_id: applicantID,
                    sale_id: saleID,
                    details: $(detailsId).val().trim(),
                    _token: '{{ csrf_token() }}'
                };

                console.log('Submitting form data:', { actionType, formData });

                $.ajax({
                    url: endpoints[actionType],
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success(response) {
                        showSuccess(response?.message);
                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            resetValidation();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error(xhr) {
                        console.error('Form submission error:', xhr);
                        const message =
                            xhr?.responseJSON?.message ||
                            xhr?.responseJSON?.error ||
                            `Failed to process ${actionType} (Status: ${xhr.status})`;
                        showError(message);
                    },
                    complete() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            };

            const attachEventHandlers = () => {

                // confirm
                $(`${formId} .savecrmMarkRequestButtonConfirm`)
                    .off('click')
                    .on('click', () => handleSubmit('confirm'));

                // reject
                $(`${formId} .savecrmMarkRequestButtonReject`)
                    .off('click')
                    .on('click', () => handleSubmit('reject'));

                // reset on modal close
                $(modalId)
                    .off('hidden.bs.modal')
                    .on('hidden.bs.modal', () => {
                        $(formId)[0].reset();
                        resetValidation();
                    });
            };

            const initModal = () => {
                resetValidation();
                attachEventHandlers();
            };

            initModal();
        }

        /** Move to confirmation */
        function crmMoveRequestToNoResponseModal(applicantID, saleID) {
            const formId = `#crmMoveRequestToNoResponseForm${applicantID}-${saleID}`;
            const modalId = `#crmMoveRequestToNoResponseModal${applicantID}-${saleID}`;
            const detailsId = `#crmMoveRequestToNoResponseDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;

            console.log('Initializing crmMoveRequestToNoResponseModal with applicantID:', applicantID, 'saleID:', saleID);

            const initModal = () => {
                resetValidation();
                attachEventHandlers();
            };

            const resetValidation = () => {
                $(detailsId).removeClass('is-invalid is-valid')
                    .next('.invalid-feedback').remove();
                $(notificationAlert).html('').hide();
            };

            const validateNotes = () => {
                const notes = $(detailsId).val().trim();
                console.log('Validating notes:', notes);
                if (!notes) {
                    $(detailsId).addClass('is-invalid')
                        .after('<div class="invalid-feedback">Please provide details.</div>');
                    return false;
                }
                return true;
            };

            const handleSubmit = () => {
                if (!validateNotes()) return false;

                const btn = $(`${formId} .savecrmRequestToNoResponseSaveButton`);
                const originalText = btn.html();

                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const formData = {
                    applicant_id: applicantID,
                    sale_id: saleID,
                    details: $(detailsId).val().trim(),
                    _token: '{{ csrf_token() }}' // Ensure this token is correctly set in your template engine
                };

                console.log('Submitting form data:', formData);

                $.ajax({
                    url: "{{ route('crmRequestNoResponse') }}", // Ensure this route is properly set in your routing
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' // CSRF protection
                    },
                    success: function(response) {
                        showSuccess(response.message);
                        setTimeout(() => {
                            $(modalId).modal('hide'); // Hide the modal after successful submission
                            $(formId)[0].reset(); // Reset form fields
                            $('#applicants_table').DataTable().ajax.reload(); // Reload the table with updated data
                        }, 2000);
                    },
                    error: function(xhr) {
                        console.error('Form submission error:', xhr.status, xhr.responseJSON);
                        showError(xhr.responseJSON?.message || `Failed to process request (Status: ${xhr.status})`);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText); // Restore button state
                    }
                });
            };

            const showSuccess = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-success alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            const showError = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-danger alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            const attachEventHandlers = () => {
                // Attach the event handler for the single save button
                const saveButtonSelector = `${formId} .savecrmRequestToNoResponseSaveButton`;
                console.log('Attaching event handler to save button:', saveButtonSelector);

                // Reset previously bound click event handler to avoid duplicates
                $(saveButtonSelector).off('click').on('click', handleSubmit);

                // Reset modal and form when it is closed
                $(modalId).off('hidden.bs.modal').on('hidden.bs.modal', () => {
                    $(formId)[0].reset(); // Reset form fields
                    resetValidation(); // Clear validation feedback
                });
            };

            initModal();
        }

        /** Send Email to applicant on request reject */
        function crmSendApplicantEmailOnRequestRejectModal(applicantID, saleID, notes) {
            const formId = `#rejectEmailForm${applicantID}-${saleID}`;
            const modalId = `#crmSendApplicantEmailOnRequestRejectModal${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlertReject${applicantID}-${saleID}`;

            console.log('Initializing crmSendApplicantEmailOnRequestRejectModal:', modalId, 'with notes:', notes);
            console.log('Route for crmRequestReject:', '{{ route('crmRequestReject') }}');

            const resetValidation = () => {
                $(`${formId} [required]`).removeClass('is-invalid')
                    .next('.invalid-feedback').remove();
                $(notificationAlert).empty().hide();
            };

            const validateForm = () => {
                let valid = true;
                $(`${formId} [required]`).each(function () {
                    if (!$(this).val().trim()) {
                        $(this).addClass('is-invalid')
                            .after('<div class="invalid-feedback">This field is required.</div>');
                        valid = false;
                    }
                });
                return valid;
            };

            const showAlert = (type, message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-${type} alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `).show();
            };

            const handleSubmit = function (e) {
                e.preventDefault();

                if (!validateForm()) return;

                const btn = $(`${formId} .saveCrmSendApplicantEmailRequestRejectButton`);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

                const formData = new FormData($(formId)[0]);
                formData.append('_token', '{{ csrf_token() }}');
                formData.set('details', notes || '');
                formData.set('applicant_id', applicantID);
                formData.set('sale_id', saleID);

                console.log('Sending combined data:', Object.fromEntries(formData));

                $.ajax({
                    url: "{{ route('crmRequestReject') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: (response) => {
                        showAlert('success', response.message);
                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(`#crmMoveToconfirmationModal${applicantID}-${saleID}`).modal('hide');
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 1500);
                    },
                    error: (xhr) => {
                        console.error('AJAX error:', xhr.status, xhr.responseJSON);
                        let errorMessage = xhr.responseJSON?.message || `Failed to process rejection and email (Status: ${xhr.status})`;
                        if (xhr.status === 405) {
                            errorMessage = 'POST method not supported. Check route configuration for /crm/request-reject.';
                        }
                        showAlert('danger', errorMessage);
                        btn.prop('disabled', false).html(originalText);
                    },
                    complete: () => {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            };

            $(document).off('submit', formId).on('submit', formId, handleSubmit);
        }

        /** Confirmation Accept Modal */
        function crmConfirmationAcceptCVModal(applicantID, saleID) {
            const formId = `#crmConfirmationAcceptCVForm${applicantID}-${saleID}`;
            const modalId = `#crmConfirmationAcceptCVModal${applicantID}-${saleID}`;
            const detailsId = `#crmConfirmationAcceptCVDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            
            // Initialize modal
            const initModal = () => {
                resetValidation();
                attachEventHandlers();
            };

            // Reset validation states
            const resetValidation = () => {
                $(detailsId).removeClass('is-invalid is-valid')
                    .next('.invalid-feedback').remove();
                $(notificationAlert).html('').hide();
            };

            // Validate notes field
            const validateNotes = () => {
                const notesEl = $(detailsId);
                const notes = notesEl.val().trim();
                notesEl.next('.invalid-feedback').remove(); // always remove old feedback

                if (!notes) {
                    notesEl.addClass('is-invalid');
                    notesEl.after('<div class="invalid-feedback">Please provide details.</div>');
                    return false;
                }

                notesEl.removeClass('is-invalid').addClass('is-valid');
                return true;
            };

            // Handle form submission
            const handleSubmit = (actionType, btn) => {
                if (!validateNotes()) return false;

                const endpoints = {
                    not_attend: "{{ route('crmConfirmInterviewToNotAttend') }}",
                    attend: "{{ route('crmConfirmInterviewToAttend') }}",
                    rebook: "{{ route('crmConfirmInterviewToRebook') }}",
                    save: "{{ route('crmConfirmSave') }}"
                };

                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const formData = {
                    applicant_id: applicantID,
                    sale_id: saleID,
                    details: $(detailsId).val().trim(),
                    _token: '{{ csrf_token() }}'
                };

                if (actionType === 'reject') {
                    formData.rejection_data = sessionStorage.getItem(`rejectNotes_${applicantID}_${saleID}`);
                }

                $.ajax({
                    url: endpoints[actionType],
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        showSuccess(response.message);
                        setTimeout(() => {
                            $(modalId).modal('hide');
                            if (actionType === 'reject') {
                                $(`#crmSendApplicantEmailOnRequestRejectModal${applicantID}-${saleID}`).modal('hide');
                            }
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        showError(xhr.responseJSON?.message || 'An error occurred while processing your request.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            };

            // Show success message
            const showSuccess = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-success alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Show error message
            const showError = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-danger alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Attach event handlers
            const attachEventHandlers = () => {
                $(`${formId} .crmConfirmationNotAttendButton`).off('click').on('click', function () {
                    handleSubmit('not_attend', $(this));
                });

                $(`${formId} .crmConfirmationAttendButton`).off('click').on('click', function () {
                    handleSubmit('attend', $(this));
                });

                $(`${formId} .crmConfirmationRebookButton`).off('click').on('click', function () {
                    handleSubmit('rebook', $(this));
                });

                $(`${formId} .crmConfirmationSaveButton`).off('click').on('click', function () {
                    handleSubmit('save', $(this));
                });

                // Reset on modal hide
                $(modalId).off('hidden.bs.modal').on('hidden.bs.modal', () => {
                    $(formId)[0].reset();
                    resetValidation();
                    sessionStorage.removeItem(`rejectNotes_${applicantID}_${saleID}`);
                });
            };

            // Initialize the modal
            initModal();
        }

        /** Rebook Accept Modal */
        function crmRebookAcceptCVModal(applicantID, saleID) {
            const formId = `#crmRebookAcceptCVForm${applicantID}-${saleID}`;
            const modalId = `#crmRebookAcceptCVModal${applicantID}-${saleID}`;
            const detailsId = `#crmRebookAcceptCVDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            
            // Initialize modal
            const initModal = () => {
                resetValidation();
                attachEventHandlers();
            };

            // Reset validation states
            const resetValidation = () => {
                $(detailsId).removeClass('is-invalid is-valid')
                    .next('.invalid-feedback').remove();
                $(notificationAlert).html('').hide();
            };

            // Validate notes field
            const validateNotes = () => {
                const notes = $(detailsId).val().trim();
                if (!notes) {
                    $(detailsId).addClass('is-invalid')
                        .after('<div class="invalid-feedback">Please provide details.</div>');
                    return false;
                }
                return true;
            };

            // Handle form submission
            const handleSubmit = (actionType, btn) => {
                if (!validateNotes()) return false;

                const endpoints = {
                    not_attend: "{{ route('crmRebookToNotAttended') }}",
                    attend: "{{ route('crmRebookToAttended') }}",
                    save: "{{ route('crmRebookSave') }}"
                };

                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const formData = {
                    applicant_id: applicantID,
                    sale_id: saleID,
                    details: $(detailsId).val().trim(),
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: endpoints[actionType],
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        showSuccess(response.message);
                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        showError(xhr.responseJSON?.message || 'An error occurred while processing your request.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            };

            // Show success message
            const showSuccess = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-success alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Show error message
            const showError = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-danger alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Attach event handlers
            const attachEventHandlers = () => {          
                $(`${formId} .crmRebookToNotAttendButton`).off('click').on('click', function () {
                    handleSubmit('not_attend', $(this));
                });

                $(`${formId} .crmRebookToAttendButton`).off('click').on('click', function () {
                    handleSubmit('attend', $(this));
                });

                $(`${formId} .crmRebookSaveButton`).off('click').on('click', function () {
                    handleSubmit('save', $(this));
                });

                // Reset on modal hide
                $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                    $(formId)[0].reset();
                    resetValidation();
                });
            };

            // Initialize the modal
            initModal();
        }

        /** Attended to Pre-start Date Notes */
        function crmAttendedPreStartDateAcceptCVModal(applicantID, saleID) {
            const formId = `#crmAttendedPreStartDateAcceptCVForm${applicantID}-${saleID}`;
            const modalId = `#crmAttendedPreStartDateAcceptCVModal${applicantID}-${saleID}`;
            const detailsId = `#crmAttendedPreStartDateAcceptCVDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            
            // Initialize modal
            const initModal = () => {
                resetValidation();
                attachEventHandlers();
            };

            // Reset validation states
            const resetValidation = () => {
                $(detailsId).removeClass('is-invalid is-valid')
                    .next('.invalid-feedback').remove();
                $(notificationAlert).html('').hide();
            };

            // Validate notes field
            const validateNotes = () => {
                const notes = $(detailsId).val().trim();
                if (!notes) {
                    $(detailsId).addClass('is-invalid')
                        .after('<div class="invalid-feedback">Please provide details.</div>');
                    return false;
                }
                return true;
            };

            // Handle form submission
            const handleSubmit = (actionType, btn) => {
                if (!validateNotes()) return false;

                const endpoints = {
                    decline: "{{ route('crmAttendedToDecline') }}",
                    start_date: "{{ route('crmAttendedToStartDate') }}",
                    save: "{{ route('crmAttendedSave') }}"
                };

                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const formData = {
                    applicant_id: applicantID,
                    sale_id: saleID,
                    details: $(detailsId).val().trim(),
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: endpoints[actionType],
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        showSuccess(response.message);
                        setTimeout(() => {
                            $(modalId).modal('hide');
                            if (actionType === 'reject') {
                                $(`#crmSendApplicantEmailOnRequestRejectModal${applicantID}-${saleID}`).modal('hide');
                            }
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        showError(xhr.responseJSON?.message || 'An error occurred while processing your request.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            };

            // Show success message
            const showSuccess = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-success alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Show error message
            const showError = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-danger alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Attach event handlers
            const attachEventHandlers = () => {  
                $(`${formId} .crmAttendedToDeclineButton`).off('click').on('click', function () {
                    handleSubmit('decline', $(this));
                });
                
                $(`${formId} .crmAttendedToStartDateButton`).off('click').on('click', function () {
                    handleSubmit('start_date', $(this));
                });
                
                $(`${formId} .crmAttendedSaveButton`).off('click').on('click', function () {
                    handleSubmit('save', $(this));
                });

                // Reset on modal hide
                $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                    $(formId)[0].reset();
                    resetValidation();
                });
            };

            // Initialize the modal
            initModal();
        }

        /** Revert Attended to Rebook */
        function crmRevertAttendToRebookModal(applicantID, saleID) {
            const formId = `#crmRevertAttendToRebookForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertAttendToRebookModal${applicantID}-${saleID}`;
            const detailsId = `#crmRevertAttendToRebookDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertAttendToRebookButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Not Attended to Quality */
        function crmNotAttendedToQualityModal(applicantID, saleID) {
            const formId = `#crmNotAttendedToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmNotAttendedToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#crmNotAttendedToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmNotAttendedToQualityButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Not Attended to Attended */
        function crmRevertNotAttendedToAttendedModal(applicantID, saleID) {
            const formId = `#crmNotAttendedToAttendedForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertNotAttendedToAttendedModal${applicantID}-${saleID}`;
            const detailsId = `#crmNotAttendedToAttendedDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmNotAttendedToAttendedButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Start Date to Quality */
        function crmStartDateToQualityModal(applicantID, saleID) {
            const formId = `#crmStartDateToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmStartDateToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#crmStartDateToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmStartDateToQualityButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Start Date to Quality */
        function crmStartDateHoldToQualityModal(applicantID, saleID) {
            const formId = `#crmStartDateHoldToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmStartDateHoldToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#crmStartDateHoldToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmStartDateHoldToQualityButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }
        
        /** Revert Invoice to Quality */
        function crmInvoiceToQualityModal(applicantID, saleID) {
            const formId = `#crmInvoiceToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmInvoiceToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#crmInvoiceToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmInvoiceToQualityButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Invoice Sent to Quality */
        function crmInvoiceSentToQualityModal(applicantID, saleID) {
            const formId = `#crmInvoiceSentToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmInvoiceSentToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#crmInvoiceSentToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmInvoiceSentToQualityButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Revert Not Attended to Attended */
        function crmRevertDeclinedToAttendedModal(applicantID, saleID) {
            const formId = `#crmRevertDeclinedToAttendedForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertDeclinedToAttendedModal${applicantID}-${saleID}`;
            const detailsId = `#crmRevertDeclinedToAttendedDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertDeclinedToAttendedButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Start Date Accept Modal */
        function crmStartDateAcceptCVModal(applicantID, saleID) {
            const formId = `#crmStartDateAcceptCVForm${applicantID}-${saleID}`;
            const modalId = `#crmStartDateAcceptCVModal${applicantID}-${saleID}`;
            const detailsId = `#crmStartDateAcceptCVDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            
            // Initialize modal
            const initModal = () => {
                resetValidation();
                attachEventHandlers();
            };

            // Reset validation states
            const resetValidation = () => {
                $(detailsId).removeClass('is-invalid is-valid')
                    .next('.invalid-feedback').remove();
                $(notificationAlert).html('').hide();
            };

            // Validate notes field
            const validateNotes = () => {
                const notes = $(detailsId).val().trim();
                if (!notes) {
                    $(detailsId).addClass('is-invalid')
                        .after('<div class="invalid-feedback">Please provide details.</div>');
                    return false;
                }
                return true;
            };

            // Handle form submission
            const handleSubmit = (actionType, btn) => {
                if (!validateNotes()) return false;

                const endpoints = {
                    invoice: "{{ route('crmStartDateToInvoice') }}",
                    startDate_hold: "{{ route('crmStartDateToHold') }}",
                    save: "{{ route('crmStartDateSave') }}"
                };

                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const formData = {
                    applicant_id: applicantID,
                    sale_id: saleID,
                    details: $(detailsId).val().trim(),
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: endpoints[actionType],
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        showSuccess(response.message);
                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        showError(xhr.responseJSON?.message || 'An error occurred while processing your request.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            };

            // Show success message
            const showSuccess = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-success alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Show error message
            const showError = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-danger alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Attach event handlers
            const attachEventHandlers = () => {
                $(`${formId} .crmStartDateToInvoiceButton`).off('click').on('click', function () {
                    handleSubmit('invoice', $(this));
                });
                
                $(`${formId} .crmStartDateToHoldButton`).off('click').on('click', function () {
                    handleSubmit('startDate_hold', $(this));
                });
                
                $(`${formId} .crmStartDateSaveButton`).off('click').on('click', function () {
                    handleSubmit('save', $(this));
                });

                // Reset on modal hide
                $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                    $(formId)[0].reset();
                    resetValidation();
                });
            };

            // Initialize the modal
            initModal();
        }

        /** Revert Start Date to Attended */
        function crmRevertStartDateToAttendedModal(applicantID, saleID) {
            const formId = `#crmRevertStartDateToAttendedForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertStartDateToAttendedModal${applicantID}-${saleID}`;
            const detailsId = `#crmRevertStartDateToAttendedDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertStartDateToAttendedButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Start Date Hold Accept Modal */
        function crmStartDateHoldAcceptCVModal(applicantID, saleID) {
            const formId = `#crmStartDateHoldAcceptCVForm${applicantID}-${saleID}`;
            const modalId = `#crmStartDateHoldAcceptCVModal${applicantID}-${saleID}`;
            const detailsId = `#crmStartDateHoldAcceptCVDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            
            // Initialize modal
            const initModal = () => {
                resetValidation();
                attachEventHandlers();
            };

            // Reset validation states
            const resetValidation = () => {
                $(detailsId).removeClass('is-invalid is-valid')
                    .next('.invalid-feedback').remove();
                $(notificationAlert).html('').hide();
            };

            // Validate notes field
            const validateNotes = () => {
                const notes = $(detailsId).val().trim();
                if (!notes) {
                    $(detailsId).addClass('is-invalid')
                        .after('<div class="invalid-feedback">Please provide details.</div>');
                    return false;
                }
                return true;
            };

            // Handle form submission
            const handleSubmit = (actionType, btn) => {
                if (!validateNotes()) return false;

                const endpoints = {
                    save: "{{ route('crmStartDateHoldSave') }}"
                };

                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const formData = {
                    applicant_id: applicantID,
                    sale_id: saleID,
                    details: $(detailsId).val().trim(),
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: endpoints[actionType],
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        showSuccess(response.message);
                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        showError(xhr.responseJSON?.message || 'An error occurred while processing your request.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            };

            // Show success message
            const showSuccess = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-success alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Show error message
            const showError = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-danger alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Attach event handlers
            const attachEventHandlers = () => {
                $(`${formId} .crmStartDateHoldSaveButton`).off('click').on('click', function () {
                    handleSubmit('save', $(this));
                });

                // Reset on modal hide
                $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                    $(formId)[0].reset();
                    resetValidation();
                });
            };

            // Initialize the modal
            initModal();
        }

        /** Revert Start Date Hold to Start Date */
        function crmRevertStartDateHoldToStartDateModal(applicantID, saleID) {
            const formId = `#crmRevertStartDateHoldToStartDateForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertStartDateHoldToStartDateModal${applicantID}-${saleID}`;
            const detailsId = `#crmRevertStartDateHoldToStartDateDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertStartDateHoldToStartDateButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Invoice Accept Modal */
        function crmInvoiceAcceptCVModal(applicantID, saleID) {
            const formId = `#crmInvoiceAcceptCVForm${applicantID}-${saleID}`;
            const modalId = `#crmInvoiceAcceptCVModal${applicantID}-${saleID}`;
            const detailsId = `#crmInvoiceAcceptCVDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            
            // Initialize modal
            const initModal = () => {
                resetValidation();
                attachEventHandlers();
            };

            // Reset validation states
            const resetValidation = () => {
                $(detailsId).removeClass('is-invalid is-valid')
                    .next('.invalid-feedback').remove();
                $(notificationAlert).html('').hide();
            };

            // Validate notes field
            const validateNotes = () => {
                const notes = $(detailsId).val().trim();
                if (!notes) {
                    $(detailsId).addClass('is-invalid')
                        .after('<div class="invalid-feedback">Please provide details.</div>');
                    return false;
                }
                return true;
            };

            // Handle form submission
            const handleSubmit = (actionType, btn) => {
                if (!validateNotes()) return false;

                const endpoints = {
                    sendInvoice: "{{ route('crmSendInvoiceToInvoiceSent') }}",
                    dispute: "{{ route('crmInvoiceToDispute') }}",
                    save: "{{ route('crmInvoiceFinalSave') }}",
                };

                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const formData = {
                    applicant_id: applicantID,
                    sale_id: saleID,
                    details: $(detailsId).val().trim(),
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: endpoints[actionType],
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        showSuccess(response.message);
                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        showError(xhr.responseJSON?.message || 'An error occurred while processing your request.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            };

            // Show success message
            const showSuccess = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-success alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Show error message
            const showError = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-danger alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Attach event handlers
            const attachEventHandlers = () => {
                $(`${formId} .crmInvoiceSendInvoiceButton`).off('click').on('click', function () {
                    handleSubmit('sendInvoice', $(this));
                });

                $(`${formId} .crmInvoiceDisputeButton`).off('click').on('click', function () {
                    handleSubmit('dispute', $(this));
                });

                $(`${formId} .crmInvoiceSaveButton`).off('click').on('click', function () {
                    handleSubmit('save', $(this));
                });

                // Reset on modal hide
                $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                    $(formId)[0].reset();
                    resetValidation();
                });
            };

            // Initialize the modal
            initModal();
        }

        /** Revert Invoice to Start Date */
        function crmRevertInvoiceToStartDateModal(applicantID, saleID) {
            const formId = `#crmRevertInvoiceToStartDateForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertInvoiceToStartDateModal${applicantID}-${saleID}`;
            const detailsId = `#crmRevertInvoiceToStartDateDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertInvoiceToStartDateButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                   
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Invoice Accept Modal */
        function crmInvoiceSentAcceptCVModal(applicantID, saleID) {
            const formId = `#crmInvoiceSentAcceptCVForm${applicantID}-${saleID}`;
            const modalId = `#crmInvoiceSentAcceptCVModal${applicantID}-${saleID}`;
            const detailsId = `#crmInvoiceSentAcceptCVDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            
            // Initialize modal
            const initModal = () => {
                resetValidation();
                attachEventHandlers();
            };

            // Reset validation states
            const resetValidation = () => {
                $(detailsId).removeClass('is-invalid is-valid')
                    .next('.invalid-feedback').remove();
                $(notificationAlert).html('').hide();
            };

            // Validate notes field
            const validateNotes = () => {
                const notes = $(detailsId).val().trim();
                if (!notes) {
                    $(detailsId).addClass('is-invalid')
                        .after('<div class="invalid-feedback">Please provide details.</div>');
                    return false;
                }
                return true;
            };

            // Handle form submission
            const handleSubmit = (actionType, btn) => {
                if (!validateNotes()) return false;

                const endpoints = {
                    paid: "{{ route('crmInvoiceSentToPaid') }}",
                    dispute: "{{ route('crmInvoiceSentToDispute') }}"
                };

                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                const formData = {
                    applicant_id: applicantID,
                    sale_id: saleID,
                    details: $(detailsId).val().trim(),
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: endpoints[actionType],
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        showSuccess(response.message);
                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(formId)[0].reset();
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        showError(xhr.responseJSON?.message || 'An error occurred while processing your request.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            };

            // Show success message
            const showSuccess = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-success alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Show error message
            const showError = (message) => {
                $(notificationAlert).html(`
                    <div class="alert alert-danger alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `).show();
            };

            // Attach event handlers
            const attachEventHandlers = () => {
                $(`${formId} .crmInvoiceSentPaidButton`).off('click').on('click', function () {
                    handleSubmit('paid', $(this));
                });
               
                $(`${formId} .crmInvoiceSentDisputeButton`).off('click').on('click', function () {
                    handleSubmit('dispute', $(this));
                });

                // Reset on modal hide
                $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                    $(formId)[0].reset();
                    resetValidation();
                });
            };

            // Initialize the modal
            initModal();
        }

        /** Revert Dispute To Invoice */
        function crmDisputeToQualityModal(applicantID, saleID) {
            const formId = `#crmDisputeToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmDisputeToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#crmDisputeToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmDisputeToQualityButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                   
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }
        
        /** Revert Dispute To Invoice */
        function crmRevertDisputeToInvoiceModal(applicantID, saleID) {
            const formId = `#crmRevertDisputeToInvoiceForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertDisputeToInvoiceModal${applicantID}-${saleID}`;
            const detailsId = `#crmRevertDisputeToInvoiceDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertDisputeToInvoiceButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(detailsId).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const notes = $(detailsId).val();

                if (!notes) {
                    $(detailsId).addClass('is-invalid');
                    $(detailsId).after('<div class="invalid-feedback">Please provide details.</div>');
                   
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        /** Change Paid CV Status */
        function crmChangePaidStatusModal(applicantID, saleID) {
            const formId = `#crmChangePaidStatusForm${applicantID}-${saleID}`;
            const modalId = `#crmChangePaidStatusModal${applicantID}-${saleID}`;
            const paid_status = `#paid_status-${applicantID}-${saleID}`;
            // const detailsId = `#crmChangePaidStatusDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmChangePaidStatusButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();
                
                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                // Get form properly
                const form = $(formId)[0];

                // Send data via AJAX
                $.ajax({
                    url: form.action,
                    method: form.method,
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        paid_status: $(paid_status).val(),
                        // details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'error';
                        $(notificationAlert).html(`
                            <div class="notification-alert ${alertClass}">
                                ${response.message}
                            </div>
                        `).show();

                        if (response.success) {
                            setTimeout(() => {
                                $(modalId).modal('hide');
                                $(formId)[0].reset();
                                $('#applicants_table').DataTable().ajax.reload();
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        $(notificationAlert).html(`
                            <div class="notification-alert error">
                                ${xhr.responseJSON?.message || 'An error occurred while saving notes.'}
                            </div>
                        `).show();
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        // /** Function to show the job details modal */
        // function showDetailsModal(saleId, sale_posted_date, officeName, name, postcode, 
        //     jobCategory, jobTitle, status, timing, experience, salary, 
        //     position, qualification, benefits) 
        // {
        //     // Find the modal for this particular saleId
        //     var modalId = 'jobDetailsModal_' + saleId;

        //     // Populate the modal body dynamically with job details
        //     $('#' + modalId + ' .modal-body').html(
        //         '<table class="table table-bordered">' +
        //             '<tr>' +
        //                 '<th>Sale ID</th>' +
        //                 '<td>' + saleId + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Posted Date</th>' +
        //                 '<td>' + sale_posted_date + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Head Office Name</th>' +
        //                 '<td>' + officeName + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Unit Name</th>' +
        //                 '<td>' + name + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Postcode</th>' +
        //                 '<td>' + postcode + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Job Category</th>' +
        //                 '<td>' + jobCategory + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Job Title</th>' +
        //                 '<td>' + jobTitle + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Status</th>' +
        //                 '<td>' + status + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Timing</th>' +
        //                 '<td>' + timing + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Qualification</th>' +
        //                 '<td>' + qualification + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Salary</th>' +
        //                 '<td>' + salary + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Position</th>' +
        //                 '<td>' + position + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Experience</th>' +
        //                 '<td>' + experience + '</td>' +
        //             '</tr>' +
        //             '<tr>' +
        //                 '<th>Benefits</th>' +
        //                 '<td>' + benefits + '</td>' +
        //             '</tr>' +
        //         '</table>'
        //     );

        //     // Show the modal
        //     $('#' + modalId).modal('show');
        // }

        /** Function to show the manager details modal */
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

        /** Function for make open to all applicants */
        $(document).on("click", "#openToPaid", function (event) {
            event.preventDefault();

            Swal.fire({
                title: "Are you sure?",
                text: "This action will reopen applications that have been closed for 5 months.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, proceed!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('openToPaidApplicants') }}",
                        method: "GET",
                        dataType: "json",
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // Optional: reload table or update UI
                                // $('#applicants_table').DataTable().ajax.reload();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function (xhr) {
                            const message = xhr.responseJSON?.message || "An error occurred while processing your request.";
                            toastr.error(message);
                        }
                    });
                }
            });
        });

        $(document).on("click", "#sendSMSToRequestedApplicant", function (event) {
            event.preventDefault();

            const applicantMessage = $.trim($('#smsBodyDetails').val());
            const applicantNumber = $('#applicant_phone_number').val();
            const applicantID = $('#applicant_id').val();
            const btn = $(this);

            if (!applicantMessage) {
                toastr.error('Please enter message...');
                return; // prevent AJAX call if message is empty
            }

            btn.prop("disabled", true);

            $.ajax({
                url: "{{ route('sendMessageToApplicant') }}",
                type: "POST",
                
                dataType: "json",
                data: { 
                    phone_number: applicantNumber, 
                    applicant_id: applicantID, 
                    message: applicantMessage,
                    _token: '{{ csrf_token() }}' 
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#send_sms_to_requested_applicant').modal('hide');
                    } else {
                        toastr.error(response.error || "Failed to send SMS.");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let message = 'Something went wrong, please try again...';

                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        message = jqXHR.responseJSON.message;
                    } else if (jqXHR.responseText) {
                        try {
                            const response = JSON.parse(jqXHR.responseText);
                            if (response.message) {
                                message = response.message;
                            }
                        } catch (e) {
                            // if not JSON, fallback to default message
                            message = jqXHR.responseText || message;
                        }
                    }

                    toastr.error(message);
                },
                complete: function () {
                    btn.prop("disabled", false); // Re-enable button after request
                }
            });
        });

        function crmRevertPaidApp(applicant_id, sale_id) {
            Swal.fire({
                title: "Are you sure?",
                text: "This action will move the applicant back to the Paid stage in Invoice Sent.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, proceed!",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('crmPaidRevertToInvoiceSent') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            applicant_id: applicant_id,
                            sale_id: sale_id
                        },
                        success: function (response) {
                            toastr.success("Applicant reverted successfully!");
                            // Reload table
                            $('#applicants_table').DataTable().ajax.reload();
                        },
                        error: function (xhr) {
                            toastr.error("Error: " + xhr.responseJSON?.message);
                        }
                    });
                }
            });
        }
        
    </script>

    <script>
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
                                    <tr><th>Posted Date</th><td>${job.posted_date}</td></tr>
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
        $(document).on('click', '.export-btn', function (e) {
            e.preventDefault();

            const $link = $(this);
            const url = $link.attr('href');
            const $dropdown = $link.closest('.dropdown');
            const $btn = $dropdown.find('button');
            const $icon = $btn.find('i');
            const $text = $btn.find('.btn-text');

            // Disable button + show loader
            $btn.prop('disabled', true);
            $icon.removeClass().addClass('spinner-border spinner-border-sm me-1');
            $text.text('Exporting...');

            $.ajax({
                url: url,
                type: 'GET',
                xhrFields: { responseType: 'blob' }, // for binary file
                success: function (data, status, xhr) {
                    const blob = new Blob([data]);
                    const link = document.createElement('a');
                    const fileName = xhr.getResponseHeader('Content-Disposition')
                        ?.split('filename=')[1]?.replace(/['"]/g, '') || 'export.xlsx';
                    link.href = window.URL.createObjectURL(blob);
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                },
                error: function () {
                    alert('Export failed. Please try again.');
                },
                complete: function () {
                    // Re-enable button + reset text
                    $btn.prop('disabled', false);
                    $icon.removeClass().addClass('ri-download-line me-1');
                    $text.text('Export');
                }
            });
        });
    </script>
@endsection
@endsection                  
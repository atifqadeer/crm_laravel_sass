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
@php
$jobCategories = \Horsefly\JobCategory::where('is_active', 1)->orderBy('name','asc')->get();
$jobTitles = \Horsefly\JobTitle::where('is_active', 1)->orderBy('name','asc')->get();
@endphp

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
                            <div class="dropdown d-inline d-none" id="declined_export_email">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-download-line me-1"></i> Export
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                    <a class="dropdown-item" href="{{ route('salesExport', ['type' => 'declined']) }}">Export Emails</a>
                                </div>
                            </div>
                            <div class="dropdown d-inline d-none" id="not_attended_export_email">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-download-line me-1"></i> Export
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                    <a class="dropdown-item" href="{{ route('salesExport', ['type' => 'not_attended']) }}">Export Emails</a>
                                </div>
                            </div>
                            <div class="dropdown d-inline d-none" id="start_date_hold_export_email">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-download-line me-1"></i> Export
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                    <a class="dropdown-item" href="{{ route('salesExport', ['type' => 'start_date_hold']) }}">Export Emails</a>
                                </div>
                            </div>
                            <div class="dropdown d-inline d-none" id="dispute_export_email">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-download-line me-1"></i> Export
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                    <a class="dropdown-item" href="{{ route('salesExport', ['type' => 'dispute']) }}">Export Emails</a>
                                </div>
                            </div>
                            <div class="dropdown d-inline d-none" id="paid_export_email">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-download-line me-1"></i> Export
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                    <a class="dropdown-item" href="{{ route('salesExport', ['type' => 'paid']) }}">Export Emails</a>
                                </div>
                            </div>

                            <!-- Button Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton4" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i> <span id="showFilterTab">Sent CVs</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton4">
                                    <a class="dropdown-item tab-filter" href="#">Sent CVs</a>
                                    <a class="dropdown-item tab-filter" href="#">Open CVs</a>
                                    <a class="dropdown-item tab-filter" href="#">Sent CVs (No Job)</a>
                                    <a class="dropdown-item tab-filter" href="#">Rejected CVs</a>
                                    <a class="dropdown-item tab-filter" href="#">Request</a>
                                    <a class="dropdown-item tab-filter" href="#">Request (No Job)</a>
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
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                    <a class="dropdown-item category-filter" href="#">All Category</a>
                                    @foreach($jobCategories as $category)
                                        <a class="dropdown-item category-filter" href="#" data-category-id="{{ $category->id }}">{{ $category->name }}</a>
                                    @endforeach
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
                                        <a class="dropdown-item title-filter" href="#" data-title-id="{{ $title->id }}">{{ $title->name }}</a>
                                    @endforeach
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
                                <th>Name</th>
                                <th>Email</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>PostCode</th>
                                <th>Job Details</th>
                                <th>Head Office</th>
                                <th>Unit</th>
                                <th>Job PostCode</th>
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
  
<div id="send_sms_to_requested_applicant" class="modal fade send_sms_to_requested_applicant_Modal" tabindex="-1" aria-labelledby="send_sms_to_requested_applicant_ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-top">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Send Request Sms To <span id="smsName"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="#" method="POST" id="send_non_nurse_sms" class="form-horizontal">
            <div class="modal-body">
                <div id="sent_cv_alert_non_nurse"></div>
                <div class="form-group row">
                    <label class="col-form-label col-sm-2">Message Text:</label>
                    <div class="col-sm-10">
                        <input type="hidden" name="applicant_phone_number" id="applicant_phone_number">
                        <input type="hidden" name="non_nurse_modal_id" id="non_nurse_modal_id">
                        <textarea name="details" id="smsBodyDetails" class="form-control" cols="40" rows="8" placeholder="TYPE HERE.." required></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="sendSMSToRequestedApplicant" class="btn btn-primary">Send SMS</button>
            </div>
        </form>
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
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    <script>
        $(document).ready(function() {
            // Store the current filter in a variable
            var tabFilter = '';
            var currentTypeFilter = '';
            var currentCategoryFilter = '';
            var currentTitleFilter = '';

            // Create a loader row and append it to the table before initialization
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="100%" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td>`;

            // Append the loader row to the table's tbody
            $('#applicants_table tbody').append(loadingRow);

            // Initialize DataTable with server-side processing
            var table = $('#applicants_table').DataTable({
                processing: false,  // Disable default processing state
                serverSide: true,  // Enables server-side processing
                ajax: {
                    url: @json(route('getCrmApplicantsAjaxRequest')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        // Add the current filter to the request parameters
                        d.tab_filter = tabFilter;  // Send the current filter value as a parameter
                        d.type_filter = currentTypeFilter;  // Send the current filter value as a parameter
                        d.category_filter = currentCategoryFilter;  // Send the current filter value as a parameter
                        d.title_filter = currentTitleFilter;  // Send the current filter value as a parameter

                        // Clean up search parameter
                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'updated_at', name: 'applicants.updated_at' },
                    { data: 'user_name', name: 'users.name' },
                    { data: 'applicant_name', name: 'applicants.applicant_name' },
                    { data: 'applicant_email', name: 'applicants.applicant_email' },
                    { data: 'job_title', name: 'job_titles.name' },
                    { data: 'job_category', name: 'job_categories.name' },
                    { data: 'applicant_postcode', name: 'applicants.applicant_postcode' },
                    { data: 'job_details', name: 'job_details' },
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
                        targets: 12,  // Column index for 'job_details'
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');  // Center the text in this column
                        }
                    },
                    {
                        targets: 13,  // Column index for 'job_details'
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
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-rounded mb-0">
                                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous" onclick="movePage('previous')">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>`;

                    // Generate page range
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

                    // Always show last page if it's not already shown
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
                    </nav>`;

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
            $('.tab-filter').on('click', function () {
                tabFilter = $(this).text().toLowerCase();

                // Capitalize each word
                const formattedText = tabFilter
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterTab').html(formattedText);

                // Show button only if "Paid" is selected
                if (formattedText === 'Paid') {
                    $('#openToPaid').show();
                } else {
                    $('#openToPaid').hide();
                }
                
                // Show button only if "Paid" is selected
                if (formattedText === 'Confirmation') {
                    $('#schedule_date').show();
                } else {
                    $('#schedule_date').hide();
                }
                
                if (formattedText === 'Declined') {
                    $('#declined_export_email').removeClass('d-none');
                } else {
                    $('#declined_export_email').addClass('d-none');
                }
                
                if (formattedText === 'Not Attended') {
                    $('#not_attended_export_email').removeClass('d-none');
                } else {
                    $('#not_attended_export_email').addClass('d-none');
                }
                
                if (formattedText === 'Start Date Hold') {
                    $('#start_date_hold_export_email').removeClass('d-none');
                } else {
                    $('#start_date_hold_export_email').addClass('d-none');
                }

                if (formattedText === 'Dispute') {
                    $('#dispute_export_email').removeClass('d-none');
                } else {
                    $('#dispute_export_email').addClass('d-none');
                }
                
                if (formattedText === 'Paid') {
                    $('#paid_export_email').removeClass('d-none');
                } else {
                    $('#paid_export_email').addClass('d-none');
                }

                
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
        });

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
        function updateCrmNotesModal(applicantID, saleID, tab) {
            const formId = `#updateCrmNotesForm${applicantID}-${saleID}`;
            const modalId = `#updateCrmNotesModal${applicantID}-${saleID}`;
            const detailsId = `#details${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const reasonId = `#reasonDropdown${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveUpdateCrmNotesButton`);
            const rejectButton = $(`${formId} .crmSentCVRejectButton`);

            // Capture data from trigger <a> element
            const triggerEl = document.querySelector(`[data-applicant-id="${applicantID}"][data-sale-id="${saleID}"]`);
            const applicantName = triggerEl?.getAttribute('data-applicant-name') || '';
            const applicantPhone = triggerEl?.getAttribute('data-applicant-phone') || '';
            const applicantUnit = triggerEl?.getAttribute('data-applicant-unit') || '';
            const smsTriggerId = modalId; // for reference back

            // ✅ Show SMS Modal with pre-filled data
            $('#smsName').text(applicantName);
            $('#applicant_phone_number').val(applicantPhone);
            // $('#non_nurse_modal_id').val(smsTriggerId);

            const smsMessage = `Hi ${applicantName} Congratulations! ${applicantUnit} would like to invite you to their office for an in-person interview. Are you available next Tues 1-3pm or Fri 10am-12pm? Please do advise a suitable time. You can either reply to this message or contact us on the information given below Thank you for choosing Kingbury to represent you. Best regards, CRM TEAM T: 01494211220 E: crm@kingsburypersonnel.com`;
            $('#smsBodyDetails').val(smsMessage);

            $('#send_sms_to_requested_applicant').modal('show');

            // Clear previous validation states
            $(detailsId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
            $(reasonId).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
            $(notificationAlert).html('').hide();

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
        function crmSentCvToRequestModal(applicantID, saleID, tab) {
            const formId = `#crmSendRequestForm${applicantID}-${saleID}`;
            const modalId = `#crmSentCvToRequestModal${applicantID}-${saleID}`;
            const detailsId = `#sendRequestDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmSendRequestButton`);

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
            const formId = `#crmRevertToQualityForm${applicantID}-${saleID}`;
            const modalId = `#crmRejectRequestRevertToQualityModal${applicantID}-${saleID}`;
            const detailsId = `#crmRevertToQualityDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertToQualityButton`);

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
        function crmRevertRebookToConfirmationModal(applicantID, saleID) {
            const formId = `#crmRevertRebookToConfirmationForm${applicantID}-${saleID}`;
            const modalId = `#crmRevertRebookToConfirmationModal${applicantID}-${saleID}`;
            const detailsId = `#crmRevertRebookToConfirmationDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmRevertRebookToConfirmationButton`);

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
            const emailAddress = `#email_address_requested_${applicantID}-${saleID}`;
            const emailSubject = `#email_subject_requested_${applicantID}-${saleID}`;
            const emailBody = `#email_body_requested_${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmSendApplicantEmailRequestButton`);

            // Reset modal when it is about to be shown
            $(modalId).off('show.bs.modal').on('show.bs.modal', function () {
                // Reset form fields
                $(formId)[0].reset();

                // Remove validation styles and messages
                $(emailAddress).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
                $(emailSubject).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();
                $(emailBody).removeClass('is-invalid is-valid').next('.invalid-feedback').remove();

                // Hide any previous alerts
                $(notificationAlert).html('').hide();
            });

            // Handle save button click
            saveButton.off('click').on('click', function() {
                // Reset validation
                $(emailAddress).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                $(emailSubject).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                $(emailBody).removeClass('is-invalid is-valid')
                        .next('.invalid-feedback').remove();
                
                // Validate inputs
                const addressTxt = $(emailAddress).val().trim();
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
                if (!addressTxt) {
                    $(emailAddress).addClass('is-invalid');
                    $(emailAddress).after('<div class="invalid-feedback">Please provide email address.</div>');
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
                        email_address: addressTxt,
                        email_subject: subjectTxt,
                        email_body: bodyTxt,
                        email_title: "Request Configuration Email", // Adjust as needed
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
            const handleSubmit = (actionType) => {
                if (!validateNotes()) return false;

                const endpoints = {
                    confirm: "{{ route('crmRequestConfirm') }}",
                    save: "{{ route('crmRequestSave') }}",
                    reject: "{{ route('crmRequestReject') }}"
                };

                const btn = $(`${formId} .savecrmConfirmation${actionType === 'confirm' ? 'Button' : actionType === 'save' ? 'SaveButton' : 'RejectButton'}`);
                const originalText = btn.html();
                
                // Show loading state
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
                // Reject button handler
                $(`${formId} .savecrmMoveToconfirmationRejectButton`).off('click').on('click', () => {
                    resetValidation();
                    if (validateNotes()) {
                        sessionStorage.setItem(`rejectNotes_${applicantID}_${saleID}`, $(detailsId).val().trim());
                        $(`#crmSendApplicantEmailOnRequestRejectModal${applicantID}-${saleID}`).modal('show');
                    }
                });

                // Confirm button handler
                $(`${formId} .savecrmMoveToconfirmationRejectButton`).off('click').on('click', () => handleSubmit('reject'));
                $(`${formId} .savecrmConfirmationButton`).off('click').on('click', () => handleSubmit('confirm'));
                
                // Save button handler
                $(`${formId} .savecrmConfirmationSaveButton`).off('click').on('click', () => handleSubmit('save'));

                // SMS button handler (if needed)
                $(`${formId} .savecrmConfirmationSendSMSButton`).off('click').on('click', () => {
                    // Add SMS functionality here
                });

                // Cleanup on modal close
                $(modalId).off('hidden.bs.modal').on('hidden.bs.modal', () => {
                    $(formId)[0].reset();
                    resetValidation();
                    sessionStorage.removeItem(`rejectNotes_${applicantID}_${saleID}`);
                });
            };

            // Initialize the modal
            initModal();
        }
        
        /** Send Email to applicant on request reject */
        function crmSendApplicantEmailOnRequestRejectModal(applicantID, saleID) {
            const formId = `#rejectEmailForm${applicantID}-${saleID}`;
            const modalId = `#crmSendApplicantEmailOnRequestRejectModal${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlertReject${applicantID}-${saleID}`;

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
                            .after(`<div class="invalid-feedback">This field is required.</div>`);
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
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Sending...');

                const formData = new FormData($(formId)[0]);
                formData.append('_token', '{{ csrf_token() }}');
                formData.set('notes', sessionStorage.getItem(`rejectNotes_${applicantID}_${saleID}`)); // Add notes

                $.ajax({
                    url: $(formId).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: (response) => {
                        showAlert('success', response.message);
                        setTimeout(() => {
                            $(modalId).modal('hide');
                            $(`#crmMoveToconfirmationModal${applicantID}-${saleID}`).modal('hide');
                            $('#applicants_table').DataTable().ajax.reload();
                        }, 1500);
                    },
                    error: (xhr) => {
                        showAlert('danger', xhr.responseJSON?.message || 'Failed to send email');
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
            const detailsId = `#crmChangePaidStatusDetails${applicantID}-${saleID}`;
            const notificationAlert = `.notificationAlert${applicantID}-${saleID}`;
            const saveButton = $(`${formId} .saveCrmChangePaidStatusButton`);

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

        /** Function to show the job details modal */
        function showDetailsModal(saleId, sale_posted_date, officeName, name, postcode, 
            jobCategory, jobTitle, status, timing, experience, salary, 
            position, qualification, benefits) 
        {
            // Find the modal for this particular saleId
            var modalId = 'jobDetailsModal_' + saleId;

            // Populate the modal body dynamically with job details
            $('#' + modalId + ' .modal-body').html(
                '<table class="table table-bordered">' +
                    '<tr>' +
                        '<th>Sale ID</th>' +
                        '<td>' + saleId + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Posted Date</th>' +
                        '<td>' + sale_posted_date + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Head Office Name</th>' +
                        '<td>' + officeName + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Unit Name</th>' +
                        '<td>' + name + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Postcode</th>' +
                        '<td>' + postcode + '</td>' +
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
                        '<th>Status</th>' +
                        '<td>' + status + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Timing</th>' +
                        '<td>' + timing + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Qualification</th>' +
                        '<td>' + qualification + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Salary</th>' +
                        '<td>' + salary + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Position</th>' +
                        '<td>' + position + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Experience</th>' +
                        '<td>' + experience + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Benefits</th>' +
                        '<td>' + benefits + '</td>' +
                    '</tr>' +
                '</table>'
            );

            // Show the modal
            $('#' + modalId).modal('show');
        }

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
                    message: applicantMessage,
                    _token: '{{ csrf_token() }}' 
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.success);
                        $('#send_sms_to_requested_applicant').modal('hide');
                    } else {
                        toastr.error(response.error || "Failed to send SMS.");
                    }
                },
                error: function () {
                    toastr.error('Something went wrong, please try again...');
                },
                complete: function () {
                    btn.prop("disabled", false); // Re-enable button after request
                }
            });
        });

        $(document).on("change", ".crm_select_reason", function () {
            $(".crmSentCVRejectButton").css("display", "block");
        });

    </script>
    
@endsection
@endsection                  
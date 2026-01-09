@extends('layouts.vertical', ['title' => 'Notifications List', 'subTitle' => 'Home'])
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
    {{-- <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row justify-content-between">
                        <div class="col-lg-12"> 
                        </div><!-- end col-->
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

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
                                    <th>Notify By</th>
                                    <th>Applicant Name</th>
                                    <th>Email</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>PostCode</th>
                                    <th>Job</th>
                                    <th>Head Office</th>
                                    <th>Unit</th>
                                    <th>PostCode</th>
                                    <th width="20%">Notes</th>
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

    <script>
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

            // Initialize DataTable
            var table = $('#applicants_table').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: "{{ route('getUserNotifications') }}",
                    type: 'GET',
                    data: function(d) {
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
                    { data: 'created_at', name: 'created_at' },
                    { data: 'notify_by_name', name: 'users.name' },
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
                        targets: [8, 9, 12, 13], // Adjusted target columns for styling (job_details, office_name, sale_postcode, notes_detail)
                        createdCell: function(td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');
                        }
                    }
                ],
                rowId: function(data) {
                    return 'row_' + data.id;
                },
                dom: 'flrtip', // Default dom for DataTables
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

            // Helper function to navigate pages (if required)
            function movePage(page) {
                const table = $('#applicants_table').DataTable();
                const pageInfo = table.page.info();
                if (page === 'previous') {
                    if (pageInfo.page > 0) {
                        table.page('previous').draw('page');
                    }
                } else if (page === 'next') {
                    if (pageInfo.page < pageInfo.pages - 1) {
                        table.page('next').draw('page');
                    }
                } else {
                    table.page(page - 1).draw('page');
                }
            }

            // Function to navigate to specific page
            function goToPage(totalPages) {
                const page = parseInt($('#goToPageInput').val());
                if (page >= 1 && page <= totalPages) {
                    $('#applicants_table').DataTable().page(page - 1).draw('page');
                    $('#goToPageError').text('');
                } else {
                    $('#goToPageError').text('Page number is out of range');
                }
            }
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

                // Style buttons
                $(`${formId} .savecrmMarkRequestButtonConfirm`)
                    .removeClass('btn-secondary btn-danger')
                    .addClass('btn btn-success');

                $(`${formId} .savecrmMarkRequestButtonReject`)
                    .removeClass('btn-secondary btn-success')
                    .addClass('btn btn-danger');

                attachEventHandlers();
            };

            initModal();
        }

    </script>
@endsection
@endsection

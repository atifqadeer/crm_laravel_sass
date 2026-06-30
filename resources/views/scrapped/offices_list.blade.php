@extends('layouts.vertical', ['title' => 'Scrapped Offices List', 'subTitle' => 'Scrap'])
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
                                    <div class="position-relative flex-grow-1" style="display: flex;">
                                        <input type="text" id="customSearchInput" class="form-control w-100"
                                            placeholder="Search ...">
                                        <button class="d-none" id="customClearBtn" type="button" title="Clear"><i
                                                class="ri-close-line"></i></button>
                                    </div>
                                    <button class="btn btn-primary" id="customSearchBtn" type="button"><i
                                            class="ri-search-line"></i> Search</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-9">
                            <div class="text-md-end mt-3">
                                <!-- buttons Dropdown -->
                                <div class="dropdown d-inline" id="bulkActionButtons">
                                    <button class="btn btn-success me-1 my-1" id="bulk-approve-btn" disabled>
                                        <i class="ri-check-line me-1"></i> Bulk Approve
                                    </button>

                                    <button class="btn btn-danger me-1 my-1" id="bulk-delete-btn" disabled>
                                        <i class="ri-delete-bin-line me-1"></i> Bulk Delete
                                    </button>

                                    <button class="btn btn-info me-1 my-1" id="bulk-email-btn" disabled>
                                        <i class="ri-mail-line me-1"></i> Bulk Email
                                    </button>
                                </div>
                                <div class="dropdown d-inline d-none" id="bulkRestoreActionButtons">
                                    <button class="btn btn-info me-1 my-1" id="bulk-restore-btn" disabled>
                                        <i class="ri-refresh-line me-1"></i> Bulk Restore
                                    </button>
                                </div>
                                <!-- Button Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                        id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterStatus">Scraped</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <a class="dropdown-item status-filter" href="#">Scraped</a>
                                        <a class="dropdown-item status-filter" href="#">Deleted</a>
                                    </div>
                                </div>
                                <!-- Button Dropdown -->
                                @canany(['office-export'])
                                    <div class="dropdown d-inline">
                                        <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button"
                                            id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-download-line me-1"></i> <span class="btn-text">Export</span>
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                            @canany(['office-export-all'])
                                                <a class="dropdown-item export-btn"
                                                    href="{{ route('officesExport', ['type' => 'scrapped-all']) }}">Export All
                                                    Data</a>
                                            @endcanany
                                            @canany(['office-export-emails'])
                                                <a class="dropdown-item export-btn"
                                                    href="{{ route('officesExport', ['type' => 'scrapped-emails']) }}">Export
                                                    Emails</a>
                                            @endcanany
                                        </div>
                                    </div>
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
                        <table id="headOffice_table" class="table align-middle mb-3">
                            <thead class="bg-light-subtle">
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>#</th>
                                    <th>Created Date</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th width="8%">PostCode</th>
                                    <th>Contact Email</th>
                                    <th>Contact Phone</th>
                                    <th>Contact Landline</th>
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
    <div class="modal fade" id="bulkEmailModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Send Bulk Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="bulk-sales-ids">
                    <input type="hidden" name="bulk_from_email" id="bulk_from_email">
                    {{--
                    <div class="mb-3">
                        <label>To</label>
                        <input type="text" id="bulk-email-to" class="form-control">
                    </div> --}}

                    <div class="mb-3">
                        <label>Subject</label>
                        <input type="text" id="bulk-email-subject" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Message</label>
                        <textarea id="bulk-email-body" class="form-control" rows="6"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" id="submit-bulk-email-btn" onclick="sendBulkEmail()">
                        Send Email
                    </button>
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">

    <!-- Summernote JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#bulk-email-body').summernote({
                height: 500,
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
            const hasViewNotePermission = @json(auth()->user()->can('office-view-note'));
            const hasAddNotePermission = @json(auth()->user()->can('office-add-note'));

            // Store the current filter in a variable
            var currentFilter = '';

            // Create loader row
            const loadingRow = `<tr><td colspan="100%" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td></tr>`;

            // Function to show loader
            function showLoader() {
                $('#headOffice_table tbody').empty().append(loadingRow);
            }

            let columns = [{
                    data: 'checkbox',
                    name: 'checkbox',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'created_at',
                    name: 'offices.created_at'
                },
                {
                    data: 'office_name',
                    name: 'offices.office_name'
                },
                {
                    data: 'office_type',
                    name: 'offices.office_type'
                },
                {
                    data: 'office_postcode',
                    name: 'offices.office_postcode'
                },
                {
                    data: 'contact_email',
                    name: 'office_contacts.office_emails'
                },
                {
                    data: 'contact_phone',
                    name: 'office_contacts.office_phones'
                },
                {
                    data: 'contact_landline',
                    name: 'office_contacts.office_landlines'
                },
                {
                    data: 'office_notes',
                    name: 'offices.office_notes',
                    orderable: false
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ];

            let columnDefs = [];

            // Dynamically assign center alignment for columns starting from resume/applicant_experience
            const centerAlignedIndices = [];
            for (let i = 0; i < columns.length; i++) {
                const key = columns[i].data;
                if (['action'].includes(key)) {
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

            // Initialize DataTable with server-side processing
            var table = $('#headOffice_table').DataTable({
                processing: false, // Disable default processing state
                serverSide: true, // Enables server-side processing
                ajax: {
                    url: @json(route('getScrappedOffices')), // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        // Add the current filter to the request parameters
                        d.status_filter = currentFilter; // Send the current filter value as a parameter
                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
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
                columns: columns,
                columnDefs: columnDefs,
                rowId: function(data) {
                    return 'row_' + data
                        .id; // Assign a unique ID to each row using the 'id' field from the data
                },
                dom: 'lrtip', // Change the order to 'filter' (f), 'length' (l), 'table' (r), 'pagination' (p), and 'information' (i)

                drawCallback: function(settings) {
                    const api = this.api();
                    const pagination = $(api.table().container()).find('.dataTables_paginate');
                    pagination.empty();

                    const pageInfo = api.page.info();
                    const currentPage = pageInfo.page + 1;
                    const totalPages = pageInfo.pages;

                    if (pageInfo.recordsTotal === 0) {
                        $('#headOffice_table tbody').html(
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

            /*** Status filter dropdown handler ***/
            $('.status-filter').on('click', function() {
                currentFilter = $(this).text().toLowerCase();

                // Capitalize each word
                const formattedText = currentFilter
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterStatus').html(formattedText);

                if (currentFilter === 'deleted') {
                    $('#bulkRestoreActionButtons').removeClass('d-none');
                    $('#bulkActionButtons').addClass('d-none');
                } else {
                    $('#bulkRestoreActionButtons').addClass('d-none');
                    $('#bulkActionButtons').removeClass('d-none');
                }

                // ✅ Uncheck all checkboxes
                $('.office-checkbox').prop('checked', false);
                $('#select-all').prop('checked', false);

                // Optional: reset indeterminate state (if you used it)
                $('#select-all').prop('indeterminate', false);

                // Trigger change if you rely on it
                $('.office-checkbox').trigger('change');

                table.ajax.reload(); // Reload with updated status filter
            });
        });

        function goToPage(totalPages) {
            const input = document.getElementById('goToPageInput');
            const errorMessage = document.getElementById('goToPageError');
            let page = parseInt(input.value);

            if (!isNaN(page) && page >= 1 && page <= totalPages) {
                $('#headOffice_table').DataTable().page(page - 1).draw('page');
                input.classList.remove('is-invalid');
            } else {
                input.classList.add('is-invalid');
            }
        }

        // Function to move the page forward or backward
        function movePage(page) {
            var table = $('#headOffice_table').DataTable();
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
        function showNotesModal(officeId, notes, officeName, officePostcode) {
            const modalId = `showNotesModal_${officeId}`;
            const modalLabelId = `${modalId}Label`;

            // Add modal HTML only once
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                                                                                            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalLabelId}" aria-hidden="true">
                                                                                                <div class="modal-dialog modal-dialog-top">
                                                                                                    <div class="modal-content">
                                                                                                        <div class="modal-header">
                                                                                                            <h5 class="modal-title" id="${modalLabelId}">Head Office Notes</h5>
                                                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                        </div>
                                                                                                        <div class="modal-body text-center">
                                                                                                            <div class="spinner-border text-primary my-4" role="status">
                                                                                                                <span class="visually-hidden">Loading...</span>
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
                // Reset modal body to loader
                $(`#${modalId} .modal-body`).html(`
                                                                                            <div class="spinner-border text-primary my-4" role="status">
                                                                                                <span class="visually-hidden">Loading...</span>
                                                                                            </div>
                                                                                        `);
            }

            // Show the modal
            $(`#${modalId}`).modal('show');

            // Set content after short delay to simulate loading
            setTimeout(() => {
                const formattedNotes = notes.replace(/\n/g, '<br>');
                $(`#${modalId} .modal-body`).html(`
                                                                                            <div class="text-start">
                                                                                                <p class="mb-1"><strong>Head Office Name:</strong> ${officeName}</p>
                                                                                                <p class="mb-1"><strong>Postcode:</strong> ${officePostcode}</p>
                                                                                                <p><strong>Notes Detail:</strong><br>${formattedNotes}</p>
                                                                                            </div>
                                                                                        `);
            }, 300); // Delay in ms
        }

        // Function to show the notes modal
        function addShortNotesModal(officeID) {
            const modalId = `shortNotesModal_${officeID}`;
            const formId = `shortNotesForm_${officeID}`;
            const textareaId = `detailsTextarea_${officeID}`;
            const saveBtnId = `saveShortNotesButton_${officeID}`;

            // Add the modal HTML to the page (only once, if not already present)
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
                                                                                                                    <label for="${textareaId}" class="form-label">Details</label>
                                                                                                                    <textarea class="form-control" id="${textareaId}" rows="4" required></textarea>
                                                                                                                </div>
                                                                                                            </form>
                                                                                                        </div>
                                                                                                        <div class="modal-footer">
                                                                                                            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                                                                            <button type="button" class="btn btn-primary" id="${saveBtnId}">Save</button>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        `);
            }

            // Reset the form when showing
            $(`#${formId}`)[0]?.reset(); // Reset if form exists
            $(`#${textareaId}`).removeClass('is-valid is-invalid').next('.invalid-feedback').remove();

            // Show the modal
            $(`#${modalId}`).modal('show');

            // Handle Save button click
            $(`#${saveBtnId}`).off('click').on('click', function() {
                const notes = $(`#${textareaId}`).val();

                if (!notes) {
                    $(`#${textareaId}`).addClass('is-invalid');
                    if ($(`#${textareaId}`).next('.invalid-feedback').length === 0) {
                        $(`#${textareaId}`).after('<div class="invalid-feedback">Please provide details.</div>');
                    }

                    $(`#${textareaId}`).on('input', function() {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // Remove validation states
                $(`#${textareaId}`).removeClass('is-invalid').addClass('is-valid');
                $(`#${textareaId}`).next('.invalid-feedback').remove();

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
                );

                // AJAX request
                $.ajax({
                    url: '{{ route('storeHeadOfficeShortNotes') }}',
                    type: 'POST',
                    data: {
                        office_id: officeID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        toastr.success('Notes saved successfully!');
                        $(`#${modalId}`).modal('hide');
                        $(`#${formId}`)[0].reset();
                        $(`#${textareaId}`).removeClass('is-valid');
                        $(`#${textareaId}`).next('.invalid-feedback').remove();
                        $('#headOffice_table').DataTable().ajax.reload();
                    },
                    error: function() {
                        alert('An error occurred while saving notes.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        function showDetailsModal(officeId, name, postcode, status) {
            const modalId = `showDetailsModal_${officeId}`;
            const labelId = `${modalId}Label`;

            // Add modal HTML only once
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${labelId}" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="${labelId}">Head Office Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="text-center py-3">
                                                <div class="spinner-border text-primary my-4" role="status">
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
            } else {
                // Reset modal content with loader if already exists
                $(`#${modalId} .modal-body`).html(`
                                        <div class="spinner-border text-primary my-4" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    `);
            }

            // Show the modal
            $(`#${modalId}`).modal('show');

            // Simulate loading delay before showing content (optional)
            setTimeout(() => {
                $(`#${modalId} .modal-body`).html(`
                                                                                            <table class="table table-bordered">
                                                                                                <tr>
                                                                                                    <th>Head Office ID</th>
                                                                                                    <td>${officeId}</td>
                                                                                                </tr>
                                                                                                <tr>
                                                                                                    <th>Name</th>
                                                                                                    <td>${name}</td>
                                                                                                </tr>
                                                                                                <tr>
                                                                                                    <th>Postcode</th>
                                                                                                    <td>${postcode}</td>
                                                                                                </tr>
                                                                                                <tr>
                                                                                                    <th>Status</th>
                                                                                                    <td>${status}</td>
                                                                                                </tr>
                                                                                            </table>
                                                                                        `);
            }, 300); // Adjust delay if needed
        }

        // Function to show the notes modal
        function viewNotesHistory(officeId) {
            const modalId = `viewNotesHistoryModal_${officeId}`;
            const labelId = `${modalId}Label`;

            // Add the modal HTML to the page (only once)
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                                                                                            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${labelId}" aria-hidden="true">
                                                                                                <div class="modal-dialog modal-dialog-scrollable modal-dialog-top">
                                                                                                    <div class="modal-content">
                                                                                                        <div class="modal-header">
                                                                                                            <h5 class="modal-title" id="${labelId}">Head Office Notes History</h5>
                                                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                        </div>
                                                                                                        <div class="modal-body">
                                                                                                            <div class="text-center py-3">
                                                                                                                <div class="spinner-border text-primary my-4" role="status">
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
            } else {
                // Reset content to loader if modal already exists
                $(`#${modalId} .modal-body`).html(`
                                                                                            <div class="spinner-border text-primary my-4" role="status">
                                                                                                <span class="visually-hidden">Loading...</span>
                                                                                            </div>
                                                                                        `);
            }

            // Show modal immediately with loader
            $(`#${modalId}`).modal('show');

            // AJAX request to fetch notes history
            $.ajax({
                url: '{{ route('getModuleNotesHistory') }}',
                type: 'GET',
                data: {
                    id: officeId,
                    module: 'Office'
                },
                success: function(response) {
                    let notesHtml = '';

                    if (response.data.length === 0) {
                        notesHtml = '<p>No record found.</p>';
                    } else {
                        response.data.forEach(function(note) {
                            const created = moment(note.created_at).format('DD MMM YYYY, h:mmA');
                            const status = note.status == 1 ? 'Active' : 'Inactive';
                            const badgeClass = note.status == 1 ? 'bg-success' : 'bg-dark';
                            notesHtml += `
                                                                                                        <div class="note-entry mb-3">
                                                                                                            <p><strong>Dated:</strong> ${created} &nbsp;
                                                                                                                <span class="badge ${badgeClass}">${status}</span>
                                                                                                            </p>
                                                                                                            <p><strong>Notes Detail:</strong><br>${note.details}</p>
                                                                                                        </div><hr>`;
                        });
                    }

                    $(`#${modalId} .modal-body`).html(notesHtml);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching notes history:", error);
                    $(`#${modalId} .modal-body`).html(
                        '<p class="text-danger">There was an error retrieving the notes. Please try again later.</p>'
                    );
                }
            });
        }

        // Function to show the notes modal
        function viewManagerDetails(officeId) {
            const modalId = `viewManagerDetailsModal_${officeId}`;
            const labelId = `${modalId}Label`;

            // Add modal HTML if not already present
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                                                                                            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${labelId}" aria-hidden="true">
                                                                                                <div class="modal-dialog modal-dialog-scrollable modal-dialog-top">
                                                                                                    <div class="modal-content">
                                                                                                        <div class="modal-header">
                                                                                                            <h5 class="modal-title" id="${labelId}">Manager Details</h5>
                                                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                        </div>
                                                                                                        <div class="modal-body modal-body-text-left">
                                                                                                            <div class="text-center py-3">
                                                                                                                <div class="spinner-border text-primary my-4 text-center" role="status">
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
            } else {
                // Reset modal body to loader if already exists
                $(`#${modalId} .modal-body`).html(`
                                                                                            <div class="spinner-border text-primary my-4" role="status">
                                                                                                <span class="visually-hidden">Loading...</span>
                                                                                            </div>
                                                                                        `);
            }

            // Show the modal immediately with loader
            $(`#${modalId}`).modal('show');

            // AJAX request to fetch contact data
            $.ajax({
                url: '{{ route('getModuleContacts') }}',
                type: 'GET',
                data: {
                    id: officeId,
                    module: 'Office'
                },
                success: function(response) {
                    let contactHtml = '';

                    if (!response.data || response.data.length === 0) {
                        contactHtml = '<p>No record found.</p>';
                    } else {
                        response.data.forEach(function(contact) {
                            const name = contact.contact_name || 'N/A';
                            const email = contact.contact_email || 'N/A';
                            const phone = contact.contact_phone || 'N/A';
                            const landline = contact.contact_landline || 'N/A';
                            const note = contact.contact_note || 'N/A';

                            contactHtml += `
                                                                                                        <div class="note-entry mb-3">
                                                                                                            <p><strong>Name:</strong> ${name}</p>
                                                                                                            <p><strong>Email:</strong> ${email}</p>
                                                                                                            <p><strong>Phone:</strong> ${phone}</p>
                                                                                                            <p><strong>Landline:</strong> ${landline}</p>
                                                                                                            <p><strong>Note:</strong><br>${note}</p>
                                                                                                        </div>
                                                                                                        <hr>`;
                        });
                    }

                    $(`#${modalId} .modal-body`).html(contactHtml);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching manager details:", error);
                    $(`#${modalId} .modal-body`).html(
                        '<p class="text-danger">There was an error retrieving the manager details. Please try again later.</p>'
                    );
                }
            });
        }

        $(document).ready(function() {
            $('#csvImportForm').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                let submitBtn = form.find('button[type="submit"]');
                let formData = new FormData(this);
                let xhr = new XMLHttpRequest();

                // Disable button
                submitBtn.prop('disabled', true).text('Uploading...');

                xhr.open('POST', '{{ route('offices.import') }}', true);
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
                        $('#headOffice_table').DataTable().ajax.reload();

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

        $(document).on('click', '.export-btn', function(e) {
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
                xhrFields: {
                    responseType: 'blob'
                }, // for binary file
                success: function(data, status, xhr) {
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
                error: function() {
                    alert('Export failed. Please try again.');
                },
                complete: function() {
                    // Re-enable button + reset text
                    $btn.prop('disabled', false);
                    $icon.removeClass().addClass('ri-download-line me-1');
                    $text.text('Export');
                }
            });
        });

        function restoreOffice(id) {

            Swal.fire({
                title: 'Are you sure?',
                text: "This office will be restored! If you restore this office then it will restore its units, contacts and sales.",
                icon: 'warning',

                input: 'textarea', // 👈 reason input
                inputLabel: 'Reason for restore',
                inputPlaceholder: 'Enter reason...',
                inputAttributes: {
                    'aria-label': 'Type your reason here'
                },

                showCancelButton: true,
                confirmButtonColor: '#45c5cd',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, restore it!',
                cancelButtonText: 'Cancel',

                // ✅ validation
                preConfirm: (reason) => {
                    if (!reason || reason.trim() === '') {
                        Swal.showValidationMessage('Reason is required!');
                        return false;
                    }
                    return reason;
                }

            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: "{{ route('scrapped.office.restore') }}",
                        type: 'PUT',
                        data: {
                            id: id,
                            reason: result.value, // 👈 send reason
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },

                        success: function(response) {

                            Swal.fire(
                                'Restored!',
                                response.message || 'Office(s) has been restored.',
                                'success'
                            );

                            $('#headOffice_table').DataTable().ajax.reload(null, false);
                        },

                        error: function(xhr) {

                            Swal.fire(
                                'Error!',
                                xhr.responseJSON?.message || 'Something went wrong.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

        function deleteOffice(id) {

            Swal.fire({
                title: 'Are you sure?',
                text: "This office will be permanently deleted! If you delete this office then it will delete its units, contacts and sales.",
                icon: 'warning',
                input: 'textarea', // ✅ textarea added
                inputLabel: 'Reason for deletion',
                inputPlaceholder: 'Enter reason here...',
                inputAttributes: {
                    'aria-label': 'Type your reason here'
                },
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',

                // ✅ validation (optional but recommended)
                inputValidator: (value) => {
                    if (!value) {
                        return 'Please enter a reason!';
                    }
                }

            }).then((result) => {

                if (result.isConfirmed) {

                    let reason = result.value; // ✅ get textarea value

                    $.ajax({
                        url: "{{ route('scrapped.office.destroy') }}",
                        type: 'DELETE',
                        data: {
                            id: id,
                            reason: reason, // ✅ send reason
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },

                        success: function(response) {

                            Swal.fire(
                                'Deleted!',
                                response.message || 'Office has been deleted.',
                                'success'
                            );

                            $('#headOffice_table').DataTable().ajax.reload(null, false);
                        },

                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                xhr.responseJSON?.message || 'Something went wrong.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

        let selectedIds = [];

        // Individual checkbox
        $(document).on('change', '.office-checkbox', function() {

            let total = $('.office-checkbox').length;
            let checked = $('.office-checkbox:checked').length;

            // If any checkbox is unchecked → uncheck select-all
            $('#select-all').prop('checked', total === checked);

            toggleBulkButtons();
        });

        // Select all checkbox
        $(document).on('change', '#select-all', function() {
            $('.office-checkbox')
                .prop('checked', this.checked)
                .trigger('change'); // keep your existing logic
        });

        const bulkButtons = $('#bulk-approve-btn, #bulk-delete-btn, #bulk-email-btn, #bulk-restore-btn');

        function toggleBulkButtons() {
            bulkButtons.prop('disabled', $('.office-checkbox:checked').length === 0);
        }

        function getSelectedOffices() {
            let ids = [];

            $('.office-checkbox:checked').each(function() {
                ids.push($(this).val());
            });

            return ids;
        }

        $('#bulk-email-btn').on('click', function() {
            let ids = getSelectedOffices();

            if (ids.length === 0) {
                alert('Select at least one record');
                return;
            }

            $.ajax({
                url: "{{ route('scrap.bulk.offices.email.template') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: ids
                },
                success: function(res) {
                    if (res.sale_ids.length > 0) {
                        // Set modal fields
                        $('#bulk-email-subject').val(res.subject);
                        $('#bulk-email-body').summernote('code', res.email_template);
                        $('#bulk-sales-ids').val(res.sale_ids); // store full map
                        $('#bulk_from_email').val(res.from_email);

                        // Show modal
                        $('#bulkEmailModal').modal('show');
                    } else {
                        toastr.error('No emails found');
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('Something went wrong while fetching email template');
                    console.error(error);
                }
            });
        });

        function sendBulkEmail() {
            var message = $('#bulk-email-body').summernote('code');
            let rawIds = $('#bulk-sales-ids').val();

            let saleIds = [];
            try {
                let parsed = JSON.parse(rawIds);
                saleIds = Array.isArray(parsed) ? parsed : [parsed]; // ← wrap single value in array
            } catch (e) {
                // fallback: treat as comma-separated string
                saleIds = rawIds.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id));
            }

            if (!message || message === '<p><br></p>') {
                toastr.error('Message body cannot be empty.');
                return;
            }

            if (saleIds.length === 0) {
                toastr.error('No office IDs found.');
                return;
            }

            var $btn = $('#submit-bulk-email-btn');
            $btn.prop('disabled', true).text('Sending...');

            $.ajax({
                url: '/send-bulk-emails-to-offices',
                type: 'POST',
                data: {
                    sale_ids: saleIds,
                    from_email: $('#bulk_from_email').val(),
                    subject: $('#bulk-email-subject').val(),
                    email_title: 'Scrap Bulk Emails',
                    message: message,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    $('#bulkEmailModal').modal('hide');

                    // ✅ Uncheck all checkboxes
                    $('.office-checkbox').prop('checked', false);
                    $('#select-all').prop('checked', false);

                    // Optional: reset indeterminate state (if you used it)
                    $('#select-all').prop('indeterminate', false);

                    // Trigger change if you rely on it
                    $('.office-checkbox').trigger('change');

                    toastr.success(res.message || 'Email sent successfully!');
                },
                error: function(xhr) {
                    var err = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to send email.';
                    toastr.error(err);
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Send');
                }
            });
        }

        $('#bulk-delete-btn').on('click', function() {
            let ids = getSelectedOffices();

            if (ids.length === 0) {
                alert('Select at least one record');
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: "This office will be permanently deleted! If you delete this office then it will delete its units, sales and contacts.",
                icon: 'warning',

                // ✅ Add textarea
                input: 'textarea',
                inputLabel: 'Reason for deletion',
                inputPlaceholder: 'Enter reason here...',
                inputAttributes: {
                    'aria-label': 'Type your reason here'
                },

                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',

                // ✅ validation
                inputValidator: (value) => {
                    if (!value) {
                        return 'Please enter a reason!';
                    }
                }

            }).then((result) => {

                if (result.isConfirmed) {

                    let reason = result.value; // ✅ get reason

                    $.ajax({
                        url: "{{ route('scrapped.office.destroy') }}",
                        type: 'DELETE',
                        data: {
                            id: ids,
                            reason: reason, // ✅ send reason
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },

                        success: function(response) {

                            Swal.fire(
                                'Deleted!',
                                response.message || 'Office(s) has been deleted.',
                                'success'
                            );

                            // ✅ Uncheck all checkboxes
                            $('.office-checkbox').prop('checked', false);
                            $('#select-all').prop('checked', false).prop('indeterminate',
                            false);

                            $('.office-checkbox').trigger('change');

                            $('#headOffice_table').DataTable().ajax.reload(null, false);
                        },

                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                xhr.responseJSON?.message || 'Something went wrong.',
                                'error'
                            );
                        }
                    });
                }
            });
        });

        $('#bulk-approve-btn').on('click', function() {
            let ids = getSelectedOffices();

            if (ids.length === 0) {
                alert('Select at least one record');
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: "This will approve to the office.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'rgba(34, 190, 13, 1)',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, approve it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: "{{ route('scrapped.office.approve') }}",
                        type: 'POST',
                        data: {
                            id: ids,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {

                            Swal.fire(
                                'Approved!',
                                response.message || 'Office(s) has been approved.',
                                'success'
                            );

                            // ✅ Uncheck all checkboxes
                            $('.office-checkbox').prop('checked', false);
                            $('#select-all').prop('checked', false);

                            // Optional: reset indeterminate state (if you used it)
                            $('#select-all').prop('indeterminate', false);

                            // Trigger change if you rely on it
                            $('.office-checkbox').trigger('change');

                            // ✅ Reload DataTable WITHOUT refreshing page
                            $('#headOffice_table').DataTable().ajax.reload(null, false);
                        },

                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                xhr.responseJSON?.message || 'Something went wrong.',
                                'error'
                            );
                        }
                    });
                }
            });
        });

        $('#bulk-restore-btn').on('click', function() {
            let ids = getSelectedOffices();

            if (ids.length === 0) {
                alert('Select at least one record');
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: "This office will be restored! If you restore this office then it will restore its units, sales and contacts.",
                icon: 'warning',

                input: 'textarea', // 👈 add this
                inputLabel: 'Reason for restore',
                inputPlaceholder: 'Enter reason...',
                inputAttributes: {
                    'aria-label': 'Type your reason here'
                },

                showCancelButton: true,
                confirmButtonColor: '#45c5cd',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, restore it!',
                cancelButtonText: 'Cancel',

                // ✅ validation
                preConfirm: (reason) => {
                    if (!reason || reason.trim() === '') {
                        Swal.showValidationMessage('Reason is required!');
                        return false;
                    }
                    return reason;
                }

            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: "{{ route('scrapped.office.restore') }}",
                        type: 'PUT',
                        data: {
                            id: ids,                 // 👈 multiple IDs
                            reason: result.value,    // 👈 send reason
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },

                        success: function(response) {

                            Swal.fire(
                                'Restored!',
                                response.message || 'Office(s) has been restored.',
                                'success'
                            );

                            // ✅ Uncheck all checkboxes
                            $('.office-checkbox').prop('checked', false);
                            $('#select-all').prop('checked', false);
                            $('#select-all').prop('indeterminate', false);
                            $('.office-checkbox').trigger('change');

                            // ✅ Reload DataTable
                            $('#headOffice_table').DataTable().ajax.reload(null, false);
                        },

                        error: function(xhr) {

                            Swal.fire(
                                'Error!',
                                xhr.responseJSON?.message || 'Something went wrong.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
    </script>
@endsection
@endsection

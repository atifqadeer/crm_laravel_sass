@extends('layouts.vertical', ['title' => ucwords(str_replace('_', ' ', $status)).' Details List - ( ' . ucwords(str_replace('_',' ',$category)) . ' )  -> '. \Carbon\Carbon::parse($date_range)->format('d M Y'),  'subTitle' => 'Dashboard'])
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
                                    <th>Date</th>
                                    <th>Sent By</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>PostCode</th>
                                    <th width="15%">Phone / Landline</th>
                                    @canany(['applicant-download-resume'])
                                        <th>Applicant Resume</th>
                                        <th>CRM Resume</th>
                                    @endcanany
                                    <th>Experience</th>
                                    <th>Source</th>
                                    @canany(['applicant-view-note', 'applicant-add-note'])
                                        <th width="25%">Notes</th>
                                    @endcanany
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
        const hasResumePermission = @json(auth()->user()->can('applicant-download-resume'));
        const hasViewNotePermission = @json(auth()->user()->can('applicant-view-note'));
        const hasAddNotePermission = @json(auth()->user()->can('applicant-add-note'));

        $(document).ready(function() {
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
                    data: 'history_created_at',
                    name: 'history_created_at'
                },
                {
                    data: 'user_name',
                    name: 'users.name'
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
                    name: 'applicantPhone',               // ← Use the same as data key
                    orderable: false,
                    searchable: true,
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
                }, 
                {
                    data: 'job_source',
                    name: 'job_sources.name'
                },
                {
                    data: 'notes_details',
                    name: 'notes_details',
                    orderable: false,
                    searchable: false
                }
            );
            columns.push({
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
                if (['applicant_resume', 'crm_resume', 'action'].includes(key)) {
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
                    url: '{{ route("getStatisticsApplicants") }}',
                    type: 'GET', // <-- change GET → POST
                    data: function (d) {
                        d.status = '{{ $status }}';
                        d.range = '{{ $range }}';
                        d.type = '{{ $type }}';
                        d.category = '{{ $category }}';
                        d.date_range = '{{ $date_range }}';
                        d.title_filters = currentTitleFilters || [];
                    },
                    beforeSend: function() {
                        showLoader(); // Show loader before AJAX request starts
                    },
                    error: function(xhr) {
                        console.error('DataTable AJAX error:', xhr.status, xhr.responseText);
                        $('#applicants_table tbody').html('<tr><td colspan="100%" class="text-center">Failed to load data</td></tr>');
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

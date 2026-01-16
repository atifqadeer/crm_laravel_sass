@section('content')
@extends('layouts.vertical', ['title' => ucwords($applicant->applicant_name). '`s Job History', 'subTitle' => 'Applicants'])

<div class="row">
    <div class="col-lg-12">
        <div class="card card-highlight">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <ul class="list-unstyled mb-0">
                                <li><strong>PostCode:</strong><span  style="font-size:18px;"> {{ strtoupper($postcode ?? 'N/A') }}</span></li>
                                <li><strong>Name:</strong> {{ ucwords($applicant->applicant_name) ?? 'N/A' }}</li>
                                <li><strong>Email <small>(Primary)</small>:</strong> {{ strtolower($applicant->applicant_email) ?? 'N/A' }}</li>
                                <li><strong>Email <small>(Secondary)</small>:</strong> {{ strtolower($applicant->applicant_email_secondary) ?? 'N/A' }}</li>
                                <li><strong>Phone <small>(Primary)</small>:</strong> {{ $applicant->applicant_phone ?? 'N/A' }}</li>
                                <li><strong>Phone <small>(Secondary)</small>:</strong> {{ $applicant->applicant_phone_secondary ?? 'N/A' }}</li>
                                <li><strong>Landline:</strong> {{ $applicant->applicant_landline ?? 'N/A' }}</li>
                            </ul>
                        </div>
                        <div class="col-md-6 mb-3">
                            <ul class="list-unstyled mb-0">
                                <li><strong>Applicant ID#:</strong> {{ $applicant->id ?? 'N/A' }}</li>
                                <li><strong>Gender:</strong>
                                    @if($applicant->gender == 'm')
                                        Male
                                    @elseif($applicant->gender == 'f')
                                        Female
                                    @else
                                        Unknown
                                    @endif
                                </li>
                                <li><strong>Source:</strong> {{ $jobSource ? ucwords($jobSource->name) : 'N/A' }}</li>
                                <li><strong>Category:</strong> {{ $jobCategory ? ucwords($jobCategory->name) . $jobType : 'N/A' }}</li>
                                <li><strong>Title:</strong> {{ $jobTitle ? strtoupper($jobTitle->name) : 'N/A' }}</li>
                                <li><strong>Status:</strong>
                                    @php
                                        $status = $applicant->status;
                                        if ($status == '1') {
                                            $statusClass = '<span class="badge bg-success">Active</span>';
                                        } else {
                                            $statusClass = '<span class="badge bg-danger">Inactive</span>';
                                        }
                                    @endphp
                                    {!! $statusClass !!}
                                </li>
                            </ul>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="row d-flex justify-content-start">
                    <div class="col-lg-12">
                        <button class="btn btn-md btn-dark" onclick="showNoNursingHomeNotes('{{ $applicant->id }}')">No Nursing Home Notes</button>
                        <button class="btn btn-md btn-secondary" onclick="showCallbackNotes('{{ $applicant->id }}')">Callback Notes</button>
                        <button class="btn btn-md btn-primary" onclick="showUpdateHistory('{{ $applicant->id }}')">Updated History</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row justify-content-center">
    <div class="col-xl-12 col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body p-3">
                                <div class="table-responsive">
                                    <table id="history_table" class="table align-middle mb-3">
                                        <thead class="bg-light-subtle">
                                            <tr>
                                                <th>#</th>
                                                <th>Date</th>
                                                <th>PostCode</th>
                                                <th>Job Details</th>
                                                <th>Office</th>
                                                <th>Unit</th>
                                                <th>Title</th>
                                                <th>Category</th>
                                                <th>Stage</th>
                                                <th>Notes</th>
                                                <th>Notes History</th>
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
                $('#history_table tbody').empty().append(loadingRow);
            }

            // Initialize DataTable with server-side processing
            var table = $('#history_table').DataTable({
                processing: false,  // Disable default processing state
                serverSide: true,  // Enables server-side processing
                ajax: {
                    url: "{{ route('getApplicantHistoryAjaxRequest')}}",
                    type: 'GET',
                    data: function(d) {
                        d.applicant_id = {{ $applicant->id }};
                        // Clean up search parameter
                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }
                    },
                    beforeSend: function() {
                        showLoader(); // Show loader before AJAX request starts
                    },
                    error: function(xhr) {
                        console.error('DataTable AJAX error:', xhr.status, xhr.responseText);
                        $('#history_table tbody').html('<tr><td colspan="100%" class="text-center">Failed to load data</td></tr>');
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'history_created_at', name: 'history.created_at' },
                    { data: 'sale_postcode', name: 'sales.sale_postcode' },
                    { data: 'job_details', name: 'job_details', orderable: false, searchable: false },
                    { data: 'office_name', name: 'offices.office_name' },
                    { data: 'unit_name', name: 'units.unit_name' },
                    { data: 'job_title', name: 'job_titles.name' },
                    { data: 'job_category', name: 'job_categories.name' },
                    { data: 'sub_stage', name: 'history.sub_stage' },
                    { data: 'details', name: 'crm_notes.details', orderable: false},
                    { data: 'action', name: 'action', orderable: false},
                ],
                columnDefs: [
                    {
                        targets: 3,  // Column index for 'job_details'
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');  // Center the text in this column
                        }
                    },
                    {
                        targets: 8,  // Column index for 'job_details'
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');  // Center the text in this column
                        }
                    },
                    {
                        targets: 10,  // Column index for 'job_details'
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
                        $('#history_table tbody').html('<tr><td colspan="100%" class="text-center">Data not found</td></tr>');
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
        });

        function goToPage(totalPages) {
            const input = document.getElementById('goToPageInput');
            const errorMessage = document.getElementById('goToPageError');
            let page = parseInt(input.value);

            if (!isNaN(page) && page >= 1 && page <= totalPages) {
                $('#history_table').DataTable().page(page - 1).draw('page');
                input.classList.remove('is-invalid');
            } else {
                input.classList.add('is-invalid');
            }
        }

                // Function to move the page forward or backward
        function movePage(page) {
            var table = $('#history_table').DataTable();
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

        // Function to show the notes modal
        function showNotesModal(saleID, notes, officeName, unitName, salePostcode, createdAt) {
            const modalID = `showNotesModal${saleID}`;
            const created = moment(createdAt).format('DD MMM YYYY, h:mm A');
            // Add the modal HTML to the page only once
            if ($('#' + modalID).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalID}" tabindex="-1" aria-labelledby="${modalID}Label" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalID}Label">CRM Notes</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center py-3">
                                        <div class="spinner-border" role="status">
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

            // Set the notes content in the modal with proper line breaks using HTML
            $('#' + modalID + ' .modal-body').html(`
                Office Name: <strong>${officeName}</strong><br>
                Unit Name: <strong>${unitName}</strong><br>
                Postcode: <strong>${salePostcode}</strong><br>
                Dated: <strong>${created}</strong><br>
                Notes Detail: <p>${notes}</p>
            `);

            // Show the modal
            $('#' + modalID).modal('show');
        }

        // Function to show the notes modal
        function showUpdateHistory(applicant_id) {
            const modalID = 'showUpdateHistoryModal' + applicant_id;

            // Add the modal HTML to the page only once
            if ($('#' + modalID).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalID}" tabindex="-1" aria-labelledby="${modalID}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalID}Label">Applicant Update History</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center py-3 loader">
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
            } else {
                // If modal already exists, reset body to loader in case of repeated calls
                $('#' + modalID + ' .modal-body').html(`
                    <div class="text-center py-3 loader">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);
            }

            // ✅ Show the modal *immediately*, so loader is visible while data loads
            $('#' + modalID).modal('show');

            // Make AJAX call to load content
            $.ajax({
                url: '{{ route("getModuleUpdateHistory") }}',
                type: 'GET',
                data: {
                    module_key: applicant_id,
                    module: 'Applicant'
                },
                success: function(response) {
                    let notesHtml = '';

                    if (!response.updated_by_audits || response.updated_by_audits.length === 0) {
                        notesHtml = '<p>No record found.</p>';
                    } else {
                        response.updated_by_audits.forEach(function(entry) {
                            const user = entry.changes_made_by;
                            const changes = entry.changes_made;

                            notesHtml += `
                                <div class="note-entry mb-3">
                                    <p><strong>Updated By:</strong> ${user}</p>
                                    <ul class="mb-2">`;

                            for (const field in changes) {
                                const value = changes[field];
                                notesHtml += `<li><strong>${field.replace(/_/g, ' ')}:</strong> ${value}</li>`;
                            }
                            notesHtml += `</ul><hr></div>`;
                        });
                    }

                    $('#' + modalID + ' .modal-body').html(notesHtml);
                },
                error: function(xhr, status, error) {
                    console.log("Error fetching notes history: " + error);
                    $('#' + modalID + ' .modal-body').html('<p>There was an error retrieving the notes. Please try again later.</p>');
                }
            });

        }

        function showCallbackNotes(applicant_id) {
            const modalID = 'showCallbackNotesModal' + applicant_id;

            // Add the modal HTML to the page only once
            if ($('#' + modalID).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalID}" tabindex="-1" aria-labelledby="${modalID}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalID}Label">Callback Notes History</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center py-3 loader">
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
            } else {
                // If modal already exists, reset body to loader in case of repeated calls
                $('#' + modalID + ' .modal-body').html(`
                    <div class="text-center py-3 loader">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);
            }

            // ✅ Show the modal *immediately*, so loader is visible while data loads
            $('#' + modalID).modal('show');

            // Make AJAX call to load content
            $.ajax({
                url: '{{ route("getApplicanCallbackNotes") }}',
                type: 'GET',
                data: {
                    id: applicant_id,
                },
                success: function(response) {
                    let notesHtml = '';

                    // Check if the response data is empty
                    if (response.data.length === 0) {
                        notesHtml = '<p>No record found.</p>';
                    } else {
                        // Loop through the response array (assuming it's an array of notes)
                        response.data.forEach(function(note) {
                            var notes = note.details;
                            var created = moment(note.created_at).format('DD MMM YYYY, h:mmA');
                            var status = note.status;

                            // Determine the badge class based on the status value
                            var statusClass = (status == 1) ? 'bg-success' : 'bg-dark'; // 'bg-success' for active, 'bg-dark' for inactive
                            var statusText = (status == 1) ? 'Active' : 'Inactive';

                            // Append each note's details to the notesHtml string
                            notesHtml += 
                                '<div class="note-entry">' +
                                    '<p><strong>Dated:</strong> ' + created + '&nbsp;&nbsp; <span class="badge ' + statusClass + '">' + statusText + '</span></p>' +
                                    '<p><strong>Notes Detail:</strong> <br>' + notes + '</p>' +
                                '</div><hr>';  // Add a separator between notes
                        });
                    }

                    $('#' + modalID + ' .modal-body').html(notesHtml);
                },
                error: function(xhr, status, error) {
                    console.log("Error fetching notes history: " + error);
                    $('#' + modalID + ' .modal-body').html('<p>There was an error retrieving the notes. Please try again later.</p>');
                }
            });
        }
        
        function showNoNursingHomeNotes(applicant_id) {
            const modalID = 'showNoNursingHomeNotesModal' + applicant_id;

            // Add the modal HTML to the page only once
            if ($('#' + modalID).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalID}" tabindex="-1" aria-labelledby="${modalID}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalID}Label">No Nursing Home Notes History</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center py-3 loader">
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
            } else {
                // If modal already exists, reset body to loader in case of repeated calls
                $('#' + modalID + ' .modal-body').html(`
                    <div class="text-center py-3 loader">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);
            }

            // ✅ Show the modal *immediately*, so loader is visible while data loads
            $('#' + modalID).modal('show');

            // Make AJAX call to load content
            $.ajax({
                url: '{{ route("getApplicantNoNursingHomeNotes") }}',
                type: 'GET',
                data: {
                    id: applicant_id,
                },
                success: function(response) {
                    let notesHtml = '';

                    // Check if the response data is empty
                    if (response.data.length === 0) {
                        notesHtml = '<p>No record found.</p>';
                    } else {
                        // Loop through the response array (assuming it's an array of notes)
                        response.data.forEach(function(note) {
                            var notes = note.details;
                            var created = moment(note.created_at).format('DD MMM YYYY, h:mmA');
                            var status = note.status;

                            // Determine the badge class based on the status value
                            var statusClass = (status == 1) ? 'bg-success' : 'bg-dark'; // 'bg-success' for active, 'bg-dark' for inactive
                            var statusText = (status == 1) ? 'Active' : 'Inactive';

                            // Append each note's details to the notesHtml string
                            notesHtml += 
                                '<div class="note-entry">' +
                                    '<p><strong>Dated:</strong> ' + created + '&nbsp;&nbsp; <span class="badge ' + statusClass + '">' + statusText + '</span></p>' +
                                    '<p><strong>Notes Detail:</strong> <br>' + notes + '</p>' +
                                '</div><hr>';  // Add a separator between notes
                        });
                    }
                    $('#' + modalID + ' .modal-body').html(notesHtml);
                },
                error: function(xhr, status, error) {
                    console.log("Error fetching notes history: " + error);
                    $('#' + modalID + ' .modal-body').html('<p>There was an error retrieving the notes. Please try again later.</p>');
                }
            });
        }

        function viewNotesHistory(applicant_id, sale_id) {
            const modalID = 'viewNotesHistoryModal' + applicant_id + '-' + sale_id;

            // Add the modal HTML to the page only once
            if ($('#' + modalID).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalID}" tabindex="-1" aria-labelledby="${modalID}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalID}Label">CRM Notes History</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center py-3 loader">
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
            } else {
                // If modal already exists, reset body to loader in case of repeated calls
                $('#' + modalID + ' .modal-body').html(`
                    <div class="text-center py-3 loader">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);
            }

            // ✅ Show the modal *immediately*, so loader is visible while data loads
            $('#' + modalID).modal('show');

            // Make AJAX call to load content
            $.ajax({
                url: '{{ route("getApplicantCrmNotes") }}',
                type: 'GET',
                data: {
                    applicant_id: applicant_id,
                    sale_id: sale_id,
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
                            const moved_tab_to = note.moved_tab_to;
                            const formattedTab = moved_tab_to.replace(/_/g, ' ')
                               .replace(/\b\w/g, char => char.toUpperCase());

                            const statusClass = (status == 1) ? 'bg-success' : 'bg-dark';
                            const statusText = (status == 1) ? 'Active' : 'Inactive';

                            notesHtml += `
                                <div class="note-entry mb-3">
                                    <div class="row justify-content-between align-items-center mb-2">
                                        <div class="col-auto">
                                            <p class="mb-0"><strong>Dated:</strong> ${created} &nbsp;&nbsp; <span class="badge ${statusClass}">${statusText}</span></p>
                                        </div>
                                        <div class="col-auto">
                                           <p class="mb-0"><strong>Stage:</strong> 
                                            <span class="badge bg-primary">${formattedTab}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <p><strong>Notes Detail:</strong><br>${notes}</p>
                                </div>
                                <hr>
                            `;

                });
            }

            $('#' + modalID + ' .modal-body').html(notesHtml);
                },
                error: function(xhr, status, error) {
                    console.log("Error fetching notes history: " + error);
                    $('#' + modalID + ' .modal-body').html('<p>There was an error retrieving the notes. Please try again later.</p>');
                }
            });
        }
    </script>
@endsection
@endsection

@section('script-bottom')
@vite(['resources/js/pages/agent-detail.js'])
@endsection

@section('content')
@php
$applicant_id = request()->query('id');
$applicant = \Horsefly\Applicant::find($applicant_id);   
$jobCategory = \Horsefly\JobCategory::where('id', $applicant->job_category_id)->select('name')->first();
$jobTitle = \Horsefly\JobCategory::where('id', $applicant->job_title_id)->select('name')->first();
$jobSource = \Horsefly\JobSource::where('id', $applicant->job_source_id)->select('name')->first();
$jobType = ucwords(str_replace('-', ' ', $applicant->job_type));
$jobType = $jobType == 'Specialist' ? ' (' . $jobType . ')' : '';
$postcode = ucwords($applicant->applicant_postcode);

@endphp
@extends('layouts.vertical', ['title' => $applicant->applicant_name. '`s Job History', 'subTitle' => 'Applicants'])

<div class="row">
    <div class="col-lg-12">
        <div class="card card-highlight">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <ul class="list-unstyled mb-0">
                                <li><strong>Name:</strong> {{ $applicant->applicant_name ?? 'N/A' }}</li>
                                <li><strong>Email <small>(Primary)</small>:</strong> {{ $applicant->applicant_email ?? 'N/A' }}</li>
                                <li><strong>Email <small>(Secondary)</small>:</strong> {{ $applicant->applicant_email_secondary ?? 'N/A' }}</li>
                                <li><strong>Phone:</strong> {{ $applicant->applicant_phone ?? 'N/A' }}</li>
                                <li><strong>Landline:</strong> {{ $applicant->applicant_landline ?? 'N/A' }}</li>
                                <li><strong>Gender:</strong>
                                    @if($applicant->gender == 'm')
                                        Male
                                    @elseif($applicant->gender == 'f')
                                        Female
                                    @else
                                        N/A
                                    @endif
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6 mb-3">
                            <ul class="list-unstyled mb-0">
                                <li><strong>Applicant ID#:</strong> {{ $applicant->id ?? 'N/A' }}</li>
                                <li><strong>PostCode:</strong> {{ $postcode ?? 'N/A' }}</li>
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
                        <button class="btn btn-md btn-dark" onclick="showNoNursingHomeNotes('{{ $applicant_id }}')">No Nursing Home Notes</button>
                        <button class="btn btn-md btn-secondary" onclick="showCallbackNotes('{{ $applicant_id }}')">Callback Notes</button>
                        <button class="btn btn-md btn-primary" onclick="showUpdateHistory('{{ $applicant_id }}')">Update History</button>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables CSS (for styling the table) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

    <!-- DataTables JS (for the table functionality) -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    <script>
        $(document).ready(function() {
            // Create a loader row and append it to the table before initialization
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="14" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td>`;

            // Append the loader row to the table's tbody
            $('#history_table tbody').append(loadingRow);

            // Initialize DataTable with server-side processing
            var table = $('#history_table').DataTable({
                processing: false,  // Disable default processing state
                serverSide: true,  // Enables server-side processing
                ajax: {
                    url: "{{ route('getApplicantHistoryAjaxRequest')}}",
                    type: 'GET',
                    data: function(d) {
                        d.applicant_id = {{ $applicant_id }};
                        // Clean up search parameter
                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at', name: 'history.created_at' },
                    { data: 'sale_postcode', name: 'sales.sale_postcode' },
                    { data: 'job_details', name: 'job_details', orderable: false, searchable: false },
                    { data: 'office_name', name: 'offices.office_name' },
                    { data: 'unit_name', name: 'units.unit_name' },
                    { data: 'job_title', name: 'job_titles.name' },
                    { data: 'job_category', name: 'job_categories.name' },
                    { data: 'sub_stage', name: 'history.sub_stage' },
                    { data: 'details', name: 'crm_notes.details', orderable: false},
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
                    }
                ],
                rowId: function(data) {
                    return 'row_' + data.id; // Assign a unique ID to each row using the 'id' field from the data
                },
                dom: 'flrtip',  // Change the order to 'filter' (f), 'length' (l), 'table' (r), 'pagination' (p), and 'information' (i)
                drawCallback: function(settings) {
                    // Custom pagination HTML
                    var api = this.api();
                    var pagination = $(api.table().container()).find('.dataTables_paginate');
                    pagination.empty();  // Clear existing pagination

                    // Get the current page and total pages
                    var pageInfo = api.page.info();
                    var currentPage = pageInfo.page + 1;  // Page starts at 0, so add 1
                    var totalPages = pageInfo.pages;

                    // Check if there are no records
                    if (pageInfo.recordsTotal === 0) {
                        $('#history_table tbody').html('<tr><td colspan="14" class="text-center">Data not found</td></tr>');
                    } else {
                        // Build the custom pagination structure
                        var paginationHtml = `
                            <nav aria-label="Page navigation example">
                                <ul class="pagination pagination-rounded mb-0">
                                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                                        <a class="page-link" href="javascript:void(0);" aria-label="Previous" onclick="movePage('previous')">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>`;

                        for (var i = 1; i <= totalPages; i++) {
                            paginationHtml += `
                                <li class="page-item ${currentPage === i ? 'active' : ''}">
                                    <a class="page-link" href="javascript:void(0);" onclick="movePage(${i})">${i}</a>
                                </li>`;
                        }

                        paginationHtml += `
                                    <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                                        <a class="page-link" href="javascript:void(0);" aria-label="Next" onclick="movePage('next')">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>`;

                        pagination.html(paginationHtml); // Append custom pagination HTML
                    }
                }
            });
        });

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

                    if (!response.audit_history || response.audit_history.length === 0) {
                        notesHtml = '<p>No record found.</p>';
                    } else {
                        response.audit_history.forEach(function(entry) {
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

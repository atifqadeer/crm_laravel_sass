@extends('layouts.vertical', ['title' => 'Job Details', 'subTitle' => 'Sales'])

@section('content')
<style>
    .triangle-green {
        position: relative;
    }

    .triangle-green::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 5px; /* thickness of the bar */
        height: 100%; /* full row height */
        background-color: #5cc184; /* main green bar */
        z-index: 2;
    }
    .triangle-red {
        position: relative;
    }

    .triangle-red::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 5px;
        height: 100%;
        background-color: #e96767; /* main red bar */
        z-index: 2;
    }
</style>
<div class="row">
    <div class="col-lg-12">
        <div class="card card-highlight">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <ul class="list-unstyled mb-0">
                                <li><strong>Head Office Name:</strong> {{ $office->office_name ?? 'N/A' }}</li>
                                <li><strong>Unit Name:</strong> {{ $unit->unit_name ?? 'N/A' }}</li>
                                <li><strong>PostCode:</strong> {{ strtoupper($sale->sale_postcode) ?? 'N/A' }}</li>
                                <li><strong>Category:</strong> {{ $jobCategory ? ucwords($jobCategory->name) . $jobType : 'N/A' }}</li>
                                <li><strong>Title:</strong> {{ $jobTitle ? ucwords($jobTitle->name) : 'N/A' }}</li>
                                @php
                                    $fullHtml = $sale->qualification; // HTML from Summernote
                                    $id = 'qualification-' . $sale->id;

                                    // 0. Remove inline styles and <span> tags
                                    $cleanedHtml = preg_replace('/<(span|[^>]+) style="[^"]*"[^>]*>/i', '<$1>', $fullHtml);
                                    $cleanedHtml = preg_replace('/<\/?span[^>]*>/i', '', $cleanedHtml);

                                    // 1. Convert block-level and <br> tags into \n
                                    $withBreaks = preg_replace(
                                        '/<(\/?(p|div|li|br|ul|ol|tr|td|table|h[1-6]))[^>]*>/i',
                                        "\n",
                                        $cleanedHtml
                                    );

                                    // 2. Remove all other HTML tags except basic formatting tags
                                    $plainText = strip_tags($withBreaks, '<b><strong><i><em><u>');

                                    // 3. Decode HTML entities
                                    $decodedText = html_entity_decode($plainText);

                                    // 4. Normalize multiple newlines
                                    $normalizedText = preg_replace("/[\r\n]+/", "\n", $decodedText);

                                    // 5. Limit preview characters
                                    $preview = Str::limit(trim($normalizedText), 300, '...');

                                    // 6. Convert newlines to <br>
                                    $shortText = nl2br($preview);

                                    $qualification = '
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#' . $id . '">' . $shortText . '</a>

                                        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="' . $id . '-label">Sale Qualification</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        ' . $fullHtml . '
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>';
                                @endphp

                                <li><strong>Qualification:</strong> {!! $qualification !!}</li>

                                @php
                                    $fullHtml = $sale->benefits; // HTML from Summernote
                                    $id = 'benefits-' . $sale->id;

                                    // 0. Remove inline styles and <span> tags
                                    $cleanedHtml = preg_replace('/<(span|[^>]+) style="[^"]*"[^>]*>/i', '<$1>', $fullHtml);
                                    $cleanedHtml = preg_replace('/<\/?span[^>]*>/i', '', $cleanedHtml);

                                    // 1. Convert block-level and <br> tags into \n
                                    $withBreaks = preg_replace(
                                        '/<(\/?(p|div|li|br|ul|ol|tr|td|table|h[1-6]))[^>]*>/i',
                                        "\n",
                                        $cleanedHtml
                                    );

                                    // 2. Remove all other HTML tags except basic formatting tags
                                    $plainText = strip_tags($withBreaks, '<b><strong><i><em><u>');

                                    // 3. Decode HTML entities
                                    $decodedText = html_entity_decode($plainText);

                                    // 4. Normalize multiple newlines
                                    $normalizedText = preg_replace("/[\r\n]+/", "\n", $decodedText);

                                    // 5. Limit preview characters
                                    $preview = Str::limit(trim($normalizedText), 300, '...');

                                    // 6. Convert newlines to <br>
                                    $shortText = nl2br($preview);

                                    $benefits = '
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#' . $id . '">' . $shortText . '</a>

                                        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="' . $id . '-label">Sale Benefits</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        ' . $fullHtml . '
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>';
                                @endphp

                                <li><strong>Benefits:</strong> {!! $benefits !!}</li>
                            </ul>
                        </div>
                        <div class="col-md-6 mb-3">
                            <ul class="list-unstyled mb-0">
                                <input type="hidden" id="sale_id" value="{{ $sale->id }}">

                                <li><strong>Sale ID#:</strong> {{ $sale->id ?? 'N/A' }}</li>
                                <li><strong>Posted On:</strong> {{ \Carbon\Carbon::parse($sale->created_at)->format('d M Y, h:i A') }}</li>
                                <li><strong>Position Type:</strong> {!! $sale->position_type ? '<span class="badge bg-primary text-white fs-12">' . ucwords(str_replace('-', ' ', $sale->position_type)) . '</span>' : 'N/A' !!}</li>
                                <li><strong>Salary:</strong> {{ $sale->salary }}</li>
                                <li><strong>Timing:</strong> {{ $sale->timing }}</li>
                                 @php
                                    $fullHtml = $sale->experience; // HTML from Summernote
                                    $id = 'experience-' . $sale->id;

                                    // 0. Remove inline styles and <span> tags
                                    $cleanedHtml = preg_replace('/<(span|[^>]+) style="[^"]*"[^>]*>/i', '<$1>', $fullHtml);
                                    $cleanedHtml = preg_replace('/<\/?span[^>]*>/i', '', $cleanedHtml);

                                    // 1. Convert block-level and <br> tags into \n
                                    $withBreaks = preg_replace(
                                        '/<(\/?(p|div|li|br|ul|ol|tr|td|table|h[1-6]))[^>]*>/i',
                                        "\n",
                                        $cleanedHtml
                                    );

                                    // 2. Remove all other HTML tags except basic formatting tags
                                    $plainText = strip_tags($withBreaks, '<b><strong><i><em><u>');

                                    // 3. Decode HTML entities
                                    $decodedText = html_entity_decode($plainText);

                                    // 4. Normalize multiple newlines
                                    $normalizedText = preg_replace("/[\r\n]+/", "\n", $decodedText);

                                    // 5. Limit preview characters
                                    $preview = Str::limit(trim($normalizedText), 300, '...');

                                    // 6. Convert newlines to <br>
                                    $shortText = nl2br($preview);

                                    $experience = '
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#' . $id . '">' . $shortText . '</a>

                                        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="' . $id . '-label">Sale Experience</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        ' . $fullHtml . '
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>';
                                @endphp
                                <li><strong>Experience:</strong> {!! $experience !!}</li>
                                <li><strong>Status:</strong>
                                    @php
                                        $status = $sale->status;
                                        if ($status == '1') {
                                            $statusClass = '<span class="badge bg-success">Active</span>';
                                        }elseif ($status == '2') {
                                            $statusClass = '<span class="badge bg-warning">Pending</span>';
                                        } else {
                                            $statusClass = '<span class="badge bg-danger">Inactive</span>';
                                        }
                                    @endphp
                                    {!! $statusClass !!}
                                </li>
                                <li>
                                    <!-- Button (Blade) -->
                                    <button class="btn btn-info btn-sm my-1" type="button" id="viewJobDescription" data-description="{{ $sale->job_description }}">
                                        <span class="nav-icon">
                                            <i class="ri-file-text-line fs-16"></i>
                                        </span>
                                        Job Description
                                    </button>

                                    <button class="btn btn-warning btn-sm my-1" type="button" id="viewDocuments">
                                        <span class="nav-icon">
                                            <i class="ri-file-text-line fs-16"></i>
                                        </span>
                                        View Documents
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
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
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Active Applicants within {{ $radius }}KMs / {{ $radiusInMiles }}Miles</h4>
                            <div>
                                 <!-- Button Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton4" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-filter-line me-1"></i> <span id="showFilterStatus">All</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton4">
                                        <a class="dropdown-item status-filter" href="#">All</a>
                                        <a class="dropdown-item status-filter" href="#">Interested</a>
                                        <a class="dropdown-item status-filter" href="#">Not Interested</a>
                                        <a class="dropdown-item status-filter" href="#">No Job</a>
                                        <a class="dropdown-item status-filter" href="#">Blocked</a>
                                        <a class="dropdown-item status-filter" href="#">Callback</a>
                                        <a class="dropdown-item status-filter" href="#">Have Nursing Home Experience</a>
                                    </div>
                                </div>
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i> <span class="btn-text">Export</span>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                                        <a class="dropdown-item export-btn" href="{{ route('applicantsExport', ['type' => 'withinRadius', 'radius' => $radius, 'model_type' => 'Horsefly\\Sale', 'model_id' => $sale->id ]) }}">Export Data</a>
                                    </div>
                                </div>
                                <!-- Add Updated Sales Filter Button -->
                                <button class="btn btn-success my-1" title="Mark selected as having nursing home experience" type="button" id="markNursingHomeBtn">
                                    <span class="nav-icon">
                                        <i class="ri-check-line fs-16"></i>
                                    </span>
                                    Mark as Nursing Home Exp
                                </button>
                                <button class="btn btn-danger my-1" title="Mark selected as having no nursing home experience" type="button" id="markNoNursingHomeBtn">
                                     <span class="nav-icon">
                                        <i class="ri-close-line fs-16"></i>
                                    </span>
                                    Mark as No Nursing Home Exp
                                </button>
                            </div>
                            <!-- Button Dropdown -->
                        </div>
                        <div class="card">
                            <div class="card-body p-3">
                                <div class="table-responsive">
                                    <table id="applicants_table" class="table align-middle mb-3">
                                        <thead class="bg-light-subtle">
                                            <tr>
                                                <th><input type="checkbox" id="master-checkbox"></th>
                                                <th>Date</th>
                                                <th>Applicant Name</th>
                                                <th>Email</th>
                                                <th>Title</th>
                                                <th>Category</th>
                                                <th>PostCode</th>
                                                <th width="10%">Phone / Landline</th>
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
            var currentFilter = '';

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
                    url: @json(route('getApplicantsBySaleRadius')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        d.sale_id = {{ $sale->id }};
                        d.radius = {{ $radius }};
                        d.status_filter = currentFilter;  // Send the current filter value as a parameter
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
                    { data: 'checkbox', 'name': 'checkbox', orderable: false, searchable: false },
                    // { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at', name: 'applicants.created_at' },
                    { data: 'applicant_name', name: 'applicants.applicant_name' },
                    { data: 'applicant_email', name: 'applicants.applicant_email' },
                    { data: 'job_title', name: 'job_titles.name' },
                    { data: 'job_category', name: 'job_categories.name' },
                    { data: 'applicant_postcode', name: 'applicants.applicant_postcode' },
                    { data: 'applicantPhone', name: 'applicantPhone' },
                    { data: 'applicant_resume', name:'applicants.applicant_cv', orderable: false, searchable: false },
                    { data: 'crm_resume', name:'applicants.updated_cv', orderable: false, searchable: false },
                    { data: 'applicant_experience', name: 'applicants.applicant_experience' },
                    { data: 'job_source', name: 'job_sources.name' },
                    { data: 'applicant_notes', name: 'applicants.applicant_notes', orderable: false, searchable: false },
                    { data: 'paid_status', name: 'applicants.paid_status', searchable: false },
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
                createdRow: function(row, data, dataIndex) {
                    const firstCell = $('td:eq(0)', row); // first column only

                    if (data.have_nursing_home_experience == 1) {
                        firstCell.addClass('triangle-green')
                                .attr('title', 'Has Nursing Home Experience');
                    } else if (data.have_nursing_home_experience == 0) {
                        firstCell.addClass('triangle-red')
                                .attr('title', 'No Nursing Home Experience');
                    }
                },
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
        // Function to mark as not interested modal
        function markNotInterestedModal(applicantID, saleID) {
            const modalId = `markApplicantNotInterestedModal_${applicantID}`;
            const formId = `markApplicantNotInterestedForm_${applicantID}`;
            const textareaId = `detailsTextareaApplicantNotInterested_${applicantID}`;
            const saveBtnId = `saveApplicantNotInterestedButton_${applicantID}`;

            // Check and append modal if not already present
            if ($(`#${modalId}`).length === 0) {
                $('body').append(
                    `<div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalId}Label">Mark As Not Interested On Sale</h5>
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
                                    <button type="button" class="btn btn-success" id="${saveBtnId}">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>`
                );
            }

            // Show modal
            $(`#${modalId}`).modal('show');

            // Handle save
            $(`#${saveBtnId}`).off('click').on('click', function () {
                const notes = $(`#${textareaId}`).val();

                if (!notes) {
                    $(`#${textareaId}`).addClass('is-invalid');
                    if ($(`#${textareaId}`).next('.invalid-feedback').length === 0) {
                        $(`#${textareaId}`).after('<div class="invalid-feedback">Please provide details.</div>');
                    }

                    $(`#${textareaId}`).on('input', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // Remove error states
                $(`#${textareaId}`).removeClass('is-invalid').addClass('is-valid');
                $(`#${textareaId}`).next('.invalid-feedback').remove();

                // Submit via AJAX
                $.ajax({
                    url: '{{ route("markApplicantNotInterestedOnSale") }}',
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        toastr.success('Marked as not interested successfully!');
                        $(`#${modalId}`).modal('hide');
                        $(`#${formId}`)[0].reset();
                        $(`#${textareaId}`).removeClass('is-valid');
                        $(`#${textareaId}`).next('.invalid-feedback').remove();
                        $('#applicants_table').DataTable().ajax.reload();
                    },
                    error: function () {
                        toastr.error('An error occurred while saving notes.');
                    }
                });
            });
        }
        // Function to show the notes modal
        function addShortNotesModal(applicantID) {
            const modalId = 'shortNotesModal-' + applicantID;

            // If modal doesn't exist yet, append it
            if ($('#' + modalId).length === 0) {
                $('body').append(
                    '<div class="modal fade" id="' + modalId + '" tabindex="-1" aria-labelledby="' + modalId + 'Label" aria-hidden="true">' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="' + modalId + 'Label">Add Notes</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<form id="shortNotesForm-' + applicantID + '">' +
                                        '<div class="mb-3">' +
                                            '<label for="detailsTextarea-' + applicantID + '" class="form-label">Details</label>' +
                                            '<textarea class="form-control" id="detailsTextarea-' + applicantID + '" rows="4" required></textarea>' +
                                        '</div>' +
                                        '<div class="mb-3">' +
                                            '<label for="reasonDropdown-' + applicantID + '" class="form-label">Reason</label>' +
                                            '<select class="form-select" id="reasonDropdown-' + applicantID + '" required>' +
                                                '<option value="" disabled selected>Select Reason</option>' +
                                                '<option value="casual">Casual Notes</option>' +
                                                '<option value="blocked">Blocked Notes</option>' +
                                                '<option value="not_interested">Temp Not Interested Notes</option>' +
                                            '</select>' +
                                        '</div>' +
                                    '</form>' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>' +
                                    '<button type="button" class="btn btn-success saveShortNotesButton" data-applicant-id="' + applicantID + '">Save</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Reset form fields on open
            $('#shortNotesForm-' + applicantID)[0].reset();
            $('#detailsTextarea-' + applicantID).removeClass('is-valid is-invalid');
            $('#reasonDropdown-' + applicantID).removeClass('is-valid is-invalid');
            $('#detailsTextarea-' + applicantID).next('.invalid-feedback').remove();
            $('#reasonDropdown-' + applicantID).next('.invalid-feedback').remove();

            // Show modal
            $('#' + modalId).modal('show');

            // Handle save button click (unbind previous first)
            $('.saveShortNotesButton').off('click').on('click', function () {
                const id = $(this).data('applicant-id');
                const notes = $('#detailsTextarea-' + id).val();
                const reason = $('#reasonDropdown-' + id).val();

                // Validation
                let isValid = true;

                if (!notes) {
                    $('#detailsTextarea-' + id).addClass('is-invalid');
                    if ($('#detailsTextarea-' + id).next('.invalid-feedback').length === 0) {
                        $('#detailsTextarea-' + id).after('<div class="invalid-feedback">Please provide details.</div>');
                    }
                    isValid = false;
                }

                if (!reason) {
                    $('#reasonDropdown-' + id).addClass('is-invalid');
                    if ($('#reasonDropdown-' + id).next('.invalid-feedback').length === 0) {
                        $('#reasonDropdown-' + id).after('<div class="invalid-feedback">Please select a reason.</div>');
                    }
                    isValid = false;
                }

                if (!isValid) {
                    // Attach dynamic error removal
                    $('#detailsTextarea-' + id).on('input', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    $('#reasonDropdown-' + id).on('change', function () {
                        if ($(this).val()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // Submit via AJAX
                $.ajax({
                    url: '{{ route("storeShortNotes") }}',
                    type: 'POST',
                    data: {
                        applicant_id: id,
                        details: notes,
                        reason: reason,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        toastr.success('Notes saved successfully!');
                        $('#' + modalId).modal('hide');
                        $('#shortNotesForm-' + id)[0].reset();
                        $('#applicants_table').DataTable().ajax.reload();
                    },
                    error: function () {
                        toastr.error('An error occurred while saving notes.');
                    }
                });
            });
        }
         // Function to show the notes modal
        function showNotesModal(applicantID, notes, applicantName, applicantPostcode) {
            let modalId = 'showNotesModal-' + applicantID;

            // If the modal does not exist yet, create and append it
            if ($('#' + modalId).length === 0) {
                $('body').append(
                    '<div class="modal fade" id="' + modalId + '" tabindex="-1" aria-labelledby="' + modalId + 'Label" aria-hidden="true">' +
                        '<div class="modal-dialog modal-md modal-dialog-scrollable modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="' + modalId + 'Label">Applicant Notes</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<!-- Content will be inserted dynamically -->' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Populate modal content
            $('#' + modalId + ' .modal-body').html(
                'Applicant Name: <strong>' + applicantName + '</strong><br>' +
                'Postcode: <strong>' + applicantPostcode + '</strong><br>' +
                'Notes Detail:<p>' + notes + '</p>'
            );

            // Show the modal
            $('#' + modalId).modal('show');
        }
        // Function to send cv modal
        function sendCVModal(applicantID, saleID) {
            const modalID = 'sendCVModal' + applicantID + '-' + saleID;
            const formID = 'sendCV_form' + applicantID + '-' + saleID;
            
            // Add modal if not exists
            if ($('#' + modalID).length === 0) {
                $('body').append(
                    '<div class="modal fade" id="' + modalID + '" tabindex="-1" aria-labelledby="sendCVModalLabel" aria-hidden="true">' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="sendCVModalLabel">Fill Out Form To Send CV</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<form class="form-horizontal" id="' + formID + '">' +
                                        '<input type="hidden" name="applicant_id" value="'+ applicantID +'">'+
                                        '<input type="hidden" name="sale_id" value="'+ saleID +'">'+
                                        '<input type="hidden" name="_token" value="{{ csrf_token() }}">'+

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
                                                    '<input class="form-check-input" type="checkbox" name="transport_type[]" id="by_walk" value="By Walk"><label class="form-check-label" for="by_walk">By Walk</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input" type="checkbox" name="transport_type[]" id="cycle" value="Cycle">' +
                                                    '<label class="form-check-label" for="cycle">Cycle</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input" type="checkbox" name="transport_type[]" id="car" value="Car">' +
                                                    '<label class="form-check-label" for="car">Car</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input" type="checkbox" name="transport_type[]" id="public_transport" value="Public Transport">' +
                                                    '<label class="form-check-label" for="public_transport">Public Transport</label>' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="form-group row">' +
                                            '<label class="col-form-label col-sm-3"><strong style="font-size:18px">6.</strong> Shift Pattern</label>' +
                                            '<div class="col-sm-9 d-flex align-items-center">' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input" type="checkbox" name="shift_pattern[]" id="day" value="Day">' +
                                                    '<label class="form-check-label" for="day">Day</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input" type="checkbox" name="shift_pattern[]" id="night" value="Night">' +
                                                    '<label class="form-check-label" for="night">Night</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input" type="checkbox" name="shift_pattern[]" id="full_time" value="Full Time">' +
                                                    '<label class="form-check-label" for="full_time">Full Time</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input" type="checkbox" name="shift_pattern[]" id="part_time" value="Part Time">' +
                                                    '<label class="form-check-label" for="part_time">Part Time</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline ml-3">' +
                                                    '<input class="form-check-input" type="checkbox" name="shift_pattern[]" id="twenty_four_hours" value="24 hours">' +
                                                    '<label class="form-check-label" for="twenty_four_hours">24 Hours</label>' +
                                                '</div>' +
                                                '<div class="form-check form-check-inline">' +
                                                    '<input class="form-check-input" type="checkbox" name="shift_pattern[]" id="day_night" value="Day/Night">' +
                                                    '<label class="form-check-label" for="day_night">Day/Night</label>' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="form-group row">'+
                                            '<label class="col-form-label col-sm-3"><strong style="font-size:18px">7.</strong> Visa Status</label>'+
                                            '<div class="col-sm-9 d-flex align-items-center">'+
                                                '<div class="d-flex">'+
                                                    '<div class="form-check form-check-inline">'+
                                                        '<input type="radio" name="visa_status" id="british" class="form-check-input" value="British">'+
                                                        '<label class="form-check-label" for="british">British</label>'+
                                                    '</div>'+
                                                    '<div class="form-check form-check-inline ml-3">'+
                                                        '<input type="radio" name="visa_status" id="required_sponsorship" class="form-check-input" value="Required Sponsorship">'+
                                                        '<label class="form-check-label" for="required_sponsorship">Required Sponsorship</label>'+
                                                    '</div>'+
                                                '</div>'+
                                            '</div>'+
                                        '</div>'+
                                        '<div class="form-group row">' +
                                            '<div class="col-6 my-2">'+
                                                '<div class="form-check form-switch">'+
                                                    '<input class="form-check-input" type="checkbox" role="switch" name="nursing_home" id="nursing_home_checkbox">'+
                                                    '<label class="form-check-label" for="nursing_home_checkbox">Nursing Home</label>'+
                                                '</div>'+
                                            '</div>'+
                                            '<div class="col-6 my-2">'+
                                                '<div class="form-check form-switch">'+
                                                    '<input class="form-check-input" type="checkbox" role="switch" name="alternate_weekend" id="alternate_weekend_checkbox">'+
                                                    '<label class="form-check-label" for="alternate_weekend_checkbox">Alternate Weekend</label>'+
                                                '</div>'+
                                            '</div>'+
                                            '<div class="col-6 my-2">'+
                                                '<div class="form-check form-switch">'+
                                                    '<input class="form-check-input" type="checkbox" role="switch" name="interview_availability" id="interview_availability_checkbox">'+
                                                    '<label class="form-check-label" for="interview_availability_checkbox">Interview Availability</label>'+
                                                '</div>'+
                                            '</div>'+
                                           '<div class="col-6 my-2">'+
                                                '<div class="form-check form-switch">'+
                                                    '<input class="form-check-input" type="checkbox" role="switch" name="no_job" id="no_job_checkbox" onclick="handleCheckboxClick(\'no_job_checkbox\', \'hangup_call_checkbox\')">'+
                                                    '<label class="form-check-label" for="no_job_checkbox">No Job</label>'+
                                                '</div>'+
                                            '</div>'+
                                            '<div class="col-6 my-2">'+
                                                '<div class="form-check form-switch">'+
                                                    '<input class="form-check-input" type="checkbox" name="hangup_call" role="switch" id="hangup_call_checkbox" onclick="handleCheckboxClick(\'hangup_call_checkbox\', \'no_job_checkbox\')">'+
                                                    '<label class="form-check-label" for="hangup_call_checkbox">Call Hung up/Not Interested</label>'+
                                                '</div>'+
                                            '</div>'+
                                        '</div>'+
                                        '<div class="form-group">'+
                                            '<label class="col-form-label col-sm-12" for="note_details">Other Details <span class="text-danger">*</span></label>'+
                                            '<div class="col-sm-12">'+
                                                '<textarea name="details" id="note_details" class="form-control" cols="30" rows="4" placeholder="Type here ..." required></textarea>'+
                                            '</div>'+
                                        '</div>'+
                                    '</form>'+
                                '</div>'+
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>' +
                                    '<button type="button" class="btn btn-success" id="saveCVBtn_' + applicantID + '_' + saleID + '">Save</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            // Handle save button click - using event delegation
                $(document).off('click', '#saveCVBtn_' + applicantID + '_' + saleID).on('click', '#saveCVBtn_' + applicantID + '_' + saleID, function() {
                    const form = $('#' + formID);
                    const notes = form.find('[name="details"]').val();
                    let isValid = true;
                    
                    // Reset validation
                    form.find('.is-invalid').removeClass('is-invalid');
                    form.find('.invalid-feedback').remove();
                    
                    // Validate required fields
                    if (!notes) {
                        form.find('[name="details"]').addClass('is-invalid')
                            .after('<div class="invalid-feedback">Please enter note details.</div>');
                        isValid = false;
                    }
                    
                    // Validate at least one transport type is selected
                    if (form.find('[name="transport_type[]"]:checked').length === 0) {
                        form.find('[name="transport_type[]"]').first().closest('.form-group').find('.col-sm-9')
                            .append('<div class="invalid-feedback d-block">Please select at least one transport type.</div>');
                        isValid = false;
                    }
                    
                    // Validate visa status is selected
                    if (!form.find('[name="visa_status"]:checked').val()) {
                        form.find('[name="visa_status"]').first().closest('.form-group').find('.col-sm-9')
                            .append('<div class="invalid-feedback d-block">Please select visa status.</div>');
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        return false;
                    }
                    
                    // Show loading state
                    const btn = $(this);
                    const originalText = btn.html();
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...');
                    
                    // Submit form via AJAX
                    $.ajax({
                        url: '{{ route("sendCVtoQuality") }}',
                        type: 'POST',
                        data: form.serialize(),
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message || 'CV sent successfully!');
                                $('#' + modalID).modal('hide');
                                $('#applicants_table').DataTable().ajax.reload();
                            } else {
                                toastr.error(response.message || 'Failed to send CV');
                            }
                        },
                        error: function(xhr) {
                            let message = 'An error occurred';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            } else if (xhr.statusText) {
                                message = xhr.statusText;
                            }
                            toastr.error(message);
                        },
                        complete: function() {
                            btn.prop('disabled', false).html(originalText);
                        }
                    });
                });
            }
            
            // Reset form when modal shows
            $('#' + modalID).on('show.bs.modal', function() {
                $(this).find('form')[0].reset();
                $(this).find('.is-invalid').removeClass('is-invalid');
                $(this).find('.invalid-feedback').remove();
            });
            
            // Show modal
            $('#' + modalID).modal('show');
        }
        // Function to mark no nursing home modal
        function markNoNursingHomeModal(applicantID) {
            const modalID = 'markNoNursingHomeModal_' + applicantID;
            const textareaID = 'detailsTextareaMarkNoNursingHome_' + applicantID;
            const formID = 'markNoNursingHomeForm_' + applicantID;
            const saveButtonID = 'saveMarkNoNursingHomeButton_' + applicantID;

            // Add the modal HTML to the page if not already present
            if ($('#' + modalID).length === 0) {
                $('body').append(
                    '<div class="modal fade" id="' + modalID + '" tabindex="-1" aria-labelledby="' + modalID + 'Label" aria-hidden="true">' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="' + modalID + 'Label">Mark As No Nursing Home</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<form id="' + formID + '">' +
                                        '<div class="mb-3">' +
                                            '<label for="' + textareaID + '" class="form-label">Details</label>' +
                                            '<textarea class="form-control" id="' + textareaID + '" rows="4" required></textarea>' +
                                        '</div>' +
                                    '</form>' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>' +
                                    '<button type="button" class="btn btn-success" id="' + saveButtonID + '">Save</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Reset the form and validation before showing
            $('#' + formID)[0].reset();
            $('#' + textareaID).removeClass('is-valid is-invalid');
            $('#' + textareaID).next('.invalid-feedback').remove();

            // Show the modal
            $('#' + modalID).modal('show');

            // Handle save button click (unbind previous first)
            $('#' + saveButtonID).off('click').on('click', function () {
                const notes = $('#' + textareaID).val().trim();

                if (!notes) {
                    $('#' + textareaID).addClass('is-invalid');
                    if ($('#' + textareaID).next('.invalid-feedback').length === 0) {
                        $('#' + textareaID).after('<div class="invalid-feedback">Please provide details.</div>');
                    }

                    // Handle live validation
                    $('#' + textareaID).on('input', function () {
                        if ($(this).val().trim()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // Clear validation states
                $('#' + textareaID).removeClass('is-invalid').addClass('is-valid');
                $('#' + textareaID).next('.invalid-feedback').remove();

                // Send AJAX request
                $.ajax({
                    url: '{{ route("markApplicantNoNursingHome") }}',
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function () {
                        toastr.success('Marked successfully!');
                        $('#' + modalID).modal('hide');
                        $('#' + formID)[0].reset();
                        $('#' + textareaID).removeClass('is-valid');
                        $('#' + textareaID).next('.invalid-feedback').remove();
                        $('#applicants_table').DataTable().ajax.reload();
                    },
                    error: function () {
                        toastr.error('An error occurred while saving notes.');
                    }
                });
            });
        }
        // Function to mark no nursing home modal
        function markApplicantCallbackModal(applicantID, saleID) {
            const modalID = 'markApplicantCallbackModal_' + applicantID;
            const textareaID = 'detailsTextareaMarkCallback_' + applicantID;
            const formID = 'markCallbackForm_' + applicantID;
            const saveButtonID = 'saveMarkCallbackButton_' + applicantID;

            // Add the modal HTML only once for this applicant
            if ($('#' + modalID).length === 0) {
                $('body').append(
                    '<div class="modal fade" id="' + modalID + '" tabindex="-1" aria-labelledby="' + modalID + 'Label" aria-hidden="true">' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="' + modalID + 'Label">Mark As Callback</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<form id="' + formID + '">' +
                                        '<div class="mb-3">' +
                                            '<label for="' + textareaID + '" class="form-label">Details</label>' +
                                            '<textarea class="form-control" id="' + textareaID + '" rows="4" required></textarea>' +
                                        '</div>' +
                                    '</form>' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>' +
                                    '<button type="button" class="btn btn-success" id="' + saveButtonID + '">Save</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }

            // Reset and show the modal
            $('#' + formID)[0].reset();
            $('#' + textareaID).removeClass('is-valid is-invalid');
            $('#' + textareaID).next('.invalid-feedback').remove();
            $('#' + modalID).modal('show');

            // Handle Save button click
            $('#' + saveButtonID).off('click').on('click', function () {
                const notes = $('#' + textareaID).val().trim();

                if (!notes) {
                    $('#' + textareaID).addClass('is-invalid');
                    if ($('#' + textareaID).next('.invalid-feedback').length === 0) {
                        $('#' + textareaID).after('<div class="invalid-feedback">Please provide details.</div>');
                    }

                    // Live validation
                    $('#' + textareaID).on('input', function () {
                        if ($(this).val().trim()) {
                            $(this).removeClass('is-invalid').addClass('is-valid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    return;
                }

                // Clear validation
                $('#' + textareaID).removeClass('is-invalid').addClass('is-valid');
                $('#' + textareaID).next('.invalid-feedback').remove();

                // AJAX request
                $.ajax({
                    url: '{{ route("markApplicantCallback") }}',
                    type: 'POST',
                    data: {
                        applicant_id: applicantID,
                        sale_id: saleID,
                        details: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function () {
                        toastr.success('Mark callback saved successfully!');
                        $('#' + modalID).modal('hide');
                        $('#' + formID)[0].reset();
                        $('#' + textareaID).removeClass('is-valid');
                        $('#' + textareaID).next('.invalid-feedback').remove();
                        $('#applicants_table').DataTable().ajax.reload();
                    },
                    error: function () {
                        toastr.error('An error occurred while saving notes.');
                    }
                });
            });
        }
        // Disable the Unblock button by default
        $('#markNursingHomeBtn').prop('disabled', true);
        $('#markNoNursingHomeBtn').prop('disabled', true);

        // Enable the Unblock button when any checkbox is checked, disable if none checked
        $(document).on('change', '.applicant_checkbox, #master-checkbox', function() {
            var anyChecked = $('.applicant_checkbox:checked').length > 0;
            $('#markNursingHomeBtn').prop('disabled', !anyChecked);
            $('#markNoNursingHomeBtn').prop('disabled', !anyChecked);
        });

         $('#master-checkbox').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('.applicant_checkbox').prop('checked', isChecked);

            // Manually toggle the DataTables selected class
            $('.applicant_checkbox').each(function() {
                var $row = $(this).closest('tr');
                if (isChecked) {
                    $row.addClass('selected');
                } else {
                    $row.removeClass('selected');
                }
            });
        });

        // Add a listener to individual checkboxes to update the master checkbox state
        $(document).on('change', '.applicant_checkbox', function() {
            var allCheckboxesChecked = $('.applicant_checkbox:checked').length === $('.applicant_checkbox').length;
            $('#master-checkbox').prop('checked', allCheckboxesChecked);

            // Manually toggle the DataTables selected class
            var $row = $(this).closest('tr');
            if ($(this).prop('checked')) {
                $row.addClass('selected');
            } else {
                $row.removeClass('selected');
            }
        });

        // Handle the button click to send an AJAX request
		$('#markNursingHomeBtn').on('click', function () {
			// Get all the selected checkboxes
			var selectedCheckboxes = [];
			$('.applicant_checkbox:checked').each(function () {
				selectedCheckboxes.push($(this).val()); // Push the value of the checked checkboxes to the array
			});

            const btn = $(this);
            const originalText = btn.html();
            btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
                );

			if (selectedCheckboxes.length > 0) {
				// Send the selected values in an AJAX request
				$.ajax({
                    url: "{{ route('markAsNursingHomeExp') }}",
					method: 'POST',
					data: {
						selectedCheckboxes: selectedCheckboxes,
						_token: '{{ csrf_token() }}'
					},
					success: function (response) {
						 // Hide the rows that were successfully updated
						selectedCheckboxes.forEach(function(rowId) {
							$('#' + rowId).fadeOut(500); // 500ms = half-second fade

							// 2. Uncheck the checkbox based on matching value
							$('input.applicant_checkbox[value="' + rowId + '"]').prop('checked', false);
						});

						toastr.success('Marked nursing home experience successfully!');

                        $('#applicants_table').DataTable().ajax.reload();

						console.log(response); // You can log the server response for debugging
					},
					error: function (error) {
						// Handle the error response here
						toastr.error('Something went wrong. Please try again.');
						console.error(error);
					},
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                        $('#markNoNursingHomeBtn').prop('disabled', true);
                    }
				});
			} else {
				toastr.error('Please select at least one checkbox.');
			}
		});

		// Handle the button click to send an AJAX request
		$('#markNoNursingHomeBtn').on('click', function () {
			// Get all the selected checkboxes
			var selectedCheckboxes = [];
			$('.applicant_checkbox:checked').each(function () {
				selectedCheckboxes.push($(this).val()); // Push the value of the checked checkboxes to the array
			});

            const btn = $(this);
            const originalText = btn.html();
            btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
                );

			if (selectedCheckboxes.length > 0) {
				// Send the selected values in an AJAX request
				$.ajax({
                    url: "{{ route('markAsNoNursingHomeExp') }}",
					method: 'POST',
					data: {
						selectedCheckboxes: selectedCheckboxes,
						_token: '{{ csrf_token() }}'
					},
					success: function (response) {
						 // Hide the rows that were successfully updated
						selectedCheckboxes.forEach(function(rowId) {
							$('#' + rowId).addClass('marked-no-nursing-home'); 

							// 2. Uncheck the checkbox based on matching value
							$('input.applicant_checkbox[value="' + rowId + '"]').prop('checked', false);
						});

						toastr.success('Marked no nursing home experience successfully!');

                        $('#applicants_table').DataTable().ajax.reload();
						console.log(response); // You can log the server response for debugging
					},
					error: function (error) {
						// Handle the error response here
						toastr.error('Something went wrong. Please try again.');
						console.error(error);
					},
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                        $('#markNursingHomeBtn').prop('disabled', true);
                    }
				});
			} else {
				toastr.error('Please select at least one checkbox.');
			}
		});

        // Function to show the job description modal
        $('#viewJobDescription').on('click', function () {
            var description = $(this).data('description');

            // Append modal HTML only once
            if ($('#viewSaleDescriptionModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="viewSaleDescriptionModal" tabindex="-1" aria-labelledby="viewSaleDescriptionModalLabel">' +
                        '<div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="viewSaleDescriptionModalLabel">Sale Description</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body" style="white-space: pre-wrap; word-wrap: break-word;">' +
                                    description +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            } else {
                // Update modal content if already exists
                $('#viewSaleDescriptionModal .modal-body').html(description);
            }

            // Show the modal
            $('#viewSaleDescriptionModal').modal('show');
        });

        $('#viewDocuments').on('click', function () {
            // Make an AJAX call to retrieve notes history data
            var id = $('#sale_id').val();

            $.ajax({
                url: '{{ route("getSaleDocuments") }}', // Your backend URL to fetch notes history, replace it with your actual URL
                type: 'GET',
                data: {
                    id: id
                }, // Pass the id to your server to fetch the corresponding applicant's notes
                success: function(response) {
                    console.log(response);
                    var notesHtml = '';  // This will hold the combined HTML for all notes

                    // Check if the response data is empty
                    if (response.data.length === 0) {
                        notesHtml = '<p>No record found.</p>';
                    } else {
                        // Loop through the response array (assuming it's an array of documents)
                        response.data.forEach(function(doc) {
                            var doc_name = doc.document_name;
                            var created = moment(doc.created_at).format('DD MMM YYYY, h:mmA');
                            var file_path = '/storage/uploads/' + doc.document_path;

                            // Append each document's details to the notesHtml string, with a button to open in new tab
                            notesHtml += 
                                '<div class="note-entry">' +
                                    '<p><strong>Dated:</strong> ' + created + '</p>' +
                                    '<p><strong>File:</strong> ' + doc_name + 
                                    '<br> <button class="btn btn-sm btn-primary" onclick="window.open(\'' + file_path + '\', \'_blank\')">Open</button></p>' +
                                '</div><hr>';  // Add a separator between notes
                        });
                    }

                    // Set the combined notes content in the modal
                    $('#viewSaleDocumentsModal .modal-body').html(notesHtml);

                    // Show the modal
                    $('#viewSaleDocumentsModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.log("Error fetching notes history: " + error);
                    // Optionally, you can display an error message in the modal
                    $('#viewSaleDocumentsModal .modal-body').html('<p>There was an error retrieving the notes. Please try again later.</p>');
                    $('#viewSaleDocumentsModal').modal('show');
                }
            });

            // Add the modal HTML to the page (only once, if not already present)
            if ($('#viewSaleDocumentsModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="viewSaleDocumentsModal" tabindex="-1" aria-labelledby="viewSaleDocumentsModalLabel" >' +
                        '<div class="modal-dialog modal-dialog-scrollable modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="viewSaleDocumentsModalLabel">Sale Documents</h5>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body">' +
                                    '<!-- Notes content will be dynamically inserted here -->' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }
        });

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

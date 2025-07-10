@section('content')
@php
$applicant_id = request()->query('applicant_id');
$sale_id = request()->query('sale_id');
$applicant = \Horsefly\Applicant::find($applicant_id);  
// Get the latest CV Note and Quality Note
$cv_notes = \Horsefly\CvNote::where('applicant_id', $applicant_id)
    ->where('sale_id', $sale_id)
    ->latest()
    ->first(); // Only one, so use first() instead of get()

$quality_notes = \Horsefly\QualityNotes::where('applicant_id', $applicant_id)
    ->where('sale_id', $sale_id)
    ->latest()
    ->first();

@endphp
@extends('layouts.vertical', ['title' => $applicant->applicant_name. '`s Notes History', 'subTitle' => 'CRM'])

<div class="row">
    <div class="col-lg-6">
        <div class="card card-highlight">
            <div class="card-body">
                <div class="card-title">CV Notes</div>
                <ul class="list-unstyled mb-0">
                    <li>
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>Date:</strong> {{ date('d M Y, h:iA',strtotime($cv_notes->created_at)) ?? 'N/A' }}
                            </div>
                            <div>
                                @php
                                    if($cv_notes->status == 1){
                                        $status = 'Active';
                                        $badgeColor = 'bg-success';
                                    }else{
                                        $status = 'Inactive';
                                        $badgeColor = 'bg-danger';
                                    }

                                @endphp
                                Status: <span class="badge {{ $badgeColor }}">{{ $status }}</span>
                            </div>
                        </div>
                    </li>
                    <li><strong>Notes:</strong> {!! $cv_notes->details ?? 'N/A' !!}</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
       <div class="card card-highlight">
            <div class="card-body">
                <div class="card-title">Quality Notes</div>
                <ul class="list-unstyled mb-0">
                    <li>
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>Date:</strong> {{ date('d M Y, h:iA',strtotime($quality_notes->created_at)) ?? 'N/A' }}
                            </div>
                            <div>
                                @php
                                    if($quality_notes->status == 1){
                                        $status = 'Active';
                                        $badgeColor = 'bg-success';
                                    }else{
                                        $status = 'Inactive';
                                        $badgeColor = 'bg-danger';
                                    }

                                    $movedTabTo = str_replace('_', ' ', $quality_notes->moved_tab_to);

                                @endphp
                                Status: <span class="badge {{ $badgeColor }}">{{ $status }}</span>
                                <span class="badge bg-primary">{{ ucwords($movedTabTo) }}</span>
                            </div>
                        </div>
                    </li>
                    <li><strong>Notes:</strong> {!! $quality_notes->details ?? 'N/A' !!}</li>
                </ul>
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
                            <div class="card-title">CRM Notes History</div>
                            <div class="card-body p-3">
                                <div class="table-responsive">
                                    <table id="history_table" class="table align-middle mb-3">
                                        <thead class="bg-light-subtle">
                                            <tr>
                                                <th>#</th>
                                                <th>Date</th>
                                                <th>Active IN</th>
                                                <th>Notes</th>
                                                <th>Status</th>
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
    <script>
        $(document).ready(function() {
            // Create a loader row and append it to the table before initialization
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="5" class="text-center py-4">
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
                    url: "{{ route('getApplicantCrmNotesHistoryAjaxRequest')}}",
                    type: 'GET',
                    data: function(d) {
                         d.applicant_id = {{ $applicant_id }};
                         d.sale_id = {{ $sale_id }};
                        // Clean up search parameter
                        if (d.search && d.search.value) {
                            d.search.value = d.search.value.toString().trim();
                        }
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'moved_tab_to', name: 'moved_tab_to' },
                    { data: 'details', name: 'details' },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                ],
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
                        $('#history_table tbody').html('<tr><td colspan="5" class="text-center">Data not found</td></tr>');
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
    </script>
@endsection
@endsection

@section('script-bottom')
@vite(['resources/js/pages/agent-detail.js'])
@endsection

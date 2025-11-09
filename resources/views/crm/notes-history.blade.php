@section('content')

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
                                                <th width="15%">Date</th>
                                                <th width="10%">Active IN</th>
                                                <th>Notes</th>
                                                <th width="10%">Status</th>
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
            // Create a loader row and append it to the table before initialization
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="100%" class="text-center py-4">
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
    </script>
@endsection
@endsection

@section('script-bottom')
@vite(['resources/js/pages/agent-detail.js'])
@endsection

@extends('layouts.vertical', ['title' => 'Dashboard', 'subTitle' => 'Dashboard'])

@section('content')
    <!-- start page title -->
    <style>
        .card {
            margin-bottom: 1.5625rem;
            box-shadow: 0 0.5rem 3.25rem rgba(0, 0, 0, 0.05);
        }
        .collapse {
    visibility: visible;
}
    </style>
    @canany(['dashboard-top-stats'])
        <div class="row">
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-2 fs-15 fw-medium">Total Applicants</p>
                                <h3 class="text-dark fw-bold d-flex align-items-center gap-2 mb-0 fs-22" id="applicantsCount">
                                    <span class="skeleton-loader w-16 h-8 rounded animate-pulse bg-gray-200"></span>
                                </h3>
                            </div>
                            <div>
                                <div class="avatar-md bg-primary bg-opacity-10 rounded">
                                    <iconify-icon icon="solar:user-plus-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-2 fs-15 fw-medium">Total Head Offices</p>
                                <h3 class="text-dark fw-bold d-flex align-items-center gap-2 mb-0 fs-22" id="officesCount">
                                    <span class="skeleton-loader w-16 h-8 rounded animate-pulse bg-gray-200"></span>
                                </h3>
                            </div>
                            <div>
                                <div class="avatar-md bg-warning bg-opacity-10 rounded">
                                    <iconify-icon icon="solar:buildings-2-bold-duotone" class="fs-32 text-warning avatar-title"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-2 fs-15 fw-medium d-flex align-items-center gap-2">Total Units</p>
                                <h3 class="text-dark fw-bold d-flex align-items-center gap-2 mb-0 fs-22" id="unitsCount">
                                    <span class="skeleton-loader w-16 h-8 rounded animate-pulse bg-gray-200"></span>
                                </h3>
                            </div>
                            <div>
                                <div class="avatar-md bg-success bg-opacity-10 rounded">
                                    <iconify-icon icon="solar:graph-new-broken" class="fs-32 text-success avatar-title"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-2 fs-15 fw-medium">Total Sales</p>
                                <h3 class="text-dark fw-bold d-flex align-items-center gap-2 mb-0 fs-22" id="salesCount">
                                    <span class="skeleton-loader w-16 h-8 rounded animate-pulse bg-gray-200"></span>
                                </h3>
                            </div>
                            <div>
                                <div class="avatar-md bg-info bg-opacity-10 rounded">
                                    <iconify-icon icon="solar:chart-2-broken" class="fs-32 text-info avatar-title"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-2 fs-15 fw-medium">Last 7 Days Applicants</p>
                                <h3 class="text-dark fw-bold d-flex align-items-center gap-2 mb-0 fs-22" id="last7DaysCount">
                                    <span class="skeleton-loader w-16 h-8 rounded animate-pulse bg-gray-200"></span>
                                </h3>
                            </div>
                            <div>
                                <div class="avatar-md bg-info bg-opacity-10 rounded">
                                    <iconify-icon icon="solar:calendar-date-broken" class="fs-32 text-info avatar-title"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-2 fs-15 fw-medium">Last 21 Days Applicants</p>
                                <h3 class="text-dark fw-bold d-flex align-items-center gap-2 mb-0 fs-22" id="last21DaysCount">
                                    <span class="skeleton-loader w-16 h-8 rounded animate-pulse bg-gray-200"></span>
                                </h3>
                            </div>
                            <div>
                                <div class="avatar-md bg-primary bg-opacity-10 rounded">
                                    <iconify-icon icon="solar:calendar-date-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-2 fs-15 fw-medium d-flex align-items-center gap-2">Last 3 Months Applicants</p>
                                <h3 class="text-dark fw-bold d-flex align-items-center gap-2 mb-0 fs-22" id="last3MonthsCount">
                                    <span class="skeleton-loader w-16 h-8 rounded animate-pulse bg-gray-200"></span>
                                </h3>
                            </div>
                            <div>
                                <div class="avatar-md bg-success bg-opacity-10 rounded">
                                    <iconify-icon icon="solar:calendar-date-broken" class="fs-32 text-success avatar-title"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcanany
    @canany(['dashboard-sales-analytics-chart','dashboard-sales-weekly-analytics'])
        <div class="row">
            @canany(['dashboard-sales-analytics-chart'])
                <div class="col-xl-9 col-lg-9">
                    <div class="card overflow-hidden">
                        <div class="card-header d-flex justify-content-between align-items-center pb-1">
                            <div>
                                <h4 class="card-title mb-0">Sales Analytic</h4>
                            </div>
                            <div class="dropdown">
                                <a href="#" class="dropdown-toggle btn btn-sm btn-outline-light rounded"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                    This Year
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="#!" class="dropdown-item chart-filter" data-range="month">Month</a>
                                    <a href="#!" class="dropdown-item chart-filter" data-range="year">Year</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-2" style="height: 420px;">
                            <div id="sales_analytic" class="w-100 h-100"></div>
                        </div>
                    </div>
                </div>
            @endcanany
            @canany(['dashboard-sales-weekly-analytics'])
                <div class="col-xl-3 col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Weekly New Sales</h4>
                        </div>
                        <div class="card-body">
                            <div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                        <img src="{{ asset('images/dashboard/kingsburyPersonnel_1.jpg') }}" class="d-block w-100 rounded img-fluid" style="height:170px" alt="crm">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="{{ asset('images/dashboard/kingsburyPersonnel_2.jpg') }}" class="d-block w-100 rounded img-fluid" style="height:170px" alt="crm">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="{{ asset('images/dashboard/kingsburyPersonnel_3.png') }}" class="d-block w-100 rounded img-fluid" style="height:170px" alt="crm">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="{{ asset('images/dashboard/kingsburyPersonnel_4.jpg') }}" class="d-block w-100 rounded img-fluid" style="height:170px" alt="crm">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="{{ asset('images/dashboard/kingsburyPersonnel_5.jpg') }}" class="d-block w-100 rounded img-fluid" style="height:170px" alt="crm">
                                    </div>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions"
                                        data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions"
                                        data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            </div>

                            <div id="sales_funnel" class="apex-charts mt-4"></div>
                        </div>
                        <div class="card-footer border-top d-flex align-items-center justify-content-between">
                            <p id="weeklySalesText" class="text-muted fw-medium fs-15 mb-0">
                                <span class="text-dark me-1">Total Sales :</span> <span id="weeklySalesCount">0</span>
                            </p>
                            <div>
                                <a href="#!" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#weeklySalesModal">View More</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endcanany
        </div>

        <!-- Modal -->
        <div class="modal fade" id="weeklySalesModal" tabindex="-1" aria-labelledby="weeklySalesModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Weekly Sales Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="weeklySalesDetails">
                        <p>Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    @endcanany
    @canany(['dashboard-users'])
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title">All Users</h4>
                        </div>
                        <!-- Date Range filter -->
                        <div class="d-inline">
                            <input type="text" id="dateRangePicker" class="form-control d-inline-block" style="width: 220px; display: inline-block;" placeholder="Select date range" readonly />
                            <button class="btn btn-outline-primary my-1" type="button" id="clearDateRange" title="Clear Date Range">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body px-3">
                        <div class="table-responsive">
                            <table id="users_table" class="table align-middle text-nowrap table-hover table-centered mb-3">
                                <thead class="bg-light-subtle">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created At</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="100%" class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcanany
@endsection

@section('script')
    <!-- jQuery CDN -->
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="{{ asset('css/jquery.dataTables.min.css')}}">

    <!-- DataTables JS -->
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

    <!-- Daterangepicker CSS/JS -->
    <link rel="stylesheet" href="{{ asset('css/daterangepicker.css') }}" />
    <script src="{{ asset('js/daterangepicker.min.js')}}"></script>

    <!-- Tailwind CSS CDN (for skeleton loader styling) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        $(document).ready(function () {
            // Deferred AJAX for counts
            const fetchCounts = $.ajax({
                url: "{{ route('dashboard.counts') }}",
                method: "GET",
                dataType: "json" // Explicitly expect JSON response
            });

            // Use $.when to handle deferred AJAX requests
            $.when(fetchCounts).done(function(countsResponse) {
                // Log the response for debugging
                console.log('Counts Response:', countsResponse);

                // Handle both array and object responses
                const data = Array.isArray(countsResponse) && countsResponse.length > 0 ? countsResponse[0] : countsResponse;

                // Check if the expected properties exist, default to 0 if undefined
                $('#applicantsCount').html(data.applicantsCount !== undefined ? data.applicantsCount : 0);
                $('#officesCount').html(data.officesCount !== undefined ? data.officesCount : 0);
                $('#unitsCount').html(data.unitsCount !== undefined ? data.unitsCount : 0);
                $('#salesCount').html(data.salesCount !== undefined ? data.salesCount : 0);
                $('#last7DaysCount').html(data.last7DaysCount !== undefined ? data.last7DaysCount : 0);
                $('#last21DaysCount').html(data.last21DaysCount !== undefined ? data.last21DaysCount : 0);
                $('#last3MonthsCount').html(data.last3MonthsCount !== undefined ? data.last3MonthsCount : 0);
            }).fail(function(xhr, status, error) {
                console.error('Error fetching counts:', xhr.responseText, status, error);
                $('#applicantsCount, #officesCount, #unitsCount, #salesCount, #last7DaysCount, #last21DaysCount, #last3MonthsCount')
                    .html('<span class="text-danger">Error</span>');
            });
        });

        $(document).ready(function () {
            // Initialize DataTable with server-side processing
            const table = $('#users_table').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: @json(route('getUsersForDashboard')),
                    type: 'GET',
                    data: function(d) {
                        d.status_filter = window.currentFilter || '';
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'users.name' },
                    { data: 'email', name: 'users.email' },
                    { data: 'role_name', name: 'roles.name' },
                    { data: 'created_at', name: 'users.created_at' },
                    { data: 'is_active', name: 'users.is_active', orderable: false },
                    { data: 'action', name: 'action', orderable: false }
                ],
                rowId: function(data) {
                    return 'row_' + data.id;
                },
                dom: 'flrtip',
                drawCallback: function (settings) {
                    const api = this.api();
                    const pagination = $(api.table().container()).find('.dataTables_paginate');
                    pagination.empty();

                    const pageInfo = api.page.info();
                    const currentPage = pageInfo.page + 1;
                    const totalPages = pageInfo.pages;

                    if (pageInfo.recordsTotal === 0) {
                        $('#users_table tbody').html('<tr><td colspan="100%" class="text-center">Data not found</td></tr>');
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

                    const visiblePages = 3;
                    const showDots = totalPages > visiblePages + 2;

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

                    paginationHtml += `
                        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0);" aria-label="Next" onclick="movePage('next')">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        </ul>
                    </nav>`;

                    pagination.html(paginationHtml);
                }
            });

            // Handle DataTable search
            $('#users_table_filter input').on('keyup', function() {
                table.search(this.value).draw();
            });
        });

        function showDetailsModal(id, name, email, role, status) {
            const modalId = `showDetailsModal-${id}`;
            const modalSelector = `#${modalId}`;

            if ($(modalSelector).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label">
                        <div class="modal-dialog modal-lg modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalId}Label">User Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body modal-body-text-left">
                                    <div class="text-center my-3">
                                        <div class="spinner-border text-primary my-3" role="status">
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

            $(modalSelector).modal('show');

            setTimeout(() => {
                $(modalSelector + ' .modal-body').html(`
                    <table class="table table-bordered mb-0">
                        <tr><th>User ID</th><td>${id}</td></tr>
                        <tr><th>User Name</th><td>${name}</td></tr>
                        <tr><th>Email</th><td>${email}</td></tr>
                        <tr><th>Role</th><td>${role}</td></tr>
                        <tr><th>Status</th><td>${status}</td></tr>
                    </table>
                `);
            }, 500);
        }

        $(function () {
            const today = moment().format('YYYY-MM-DD');
            $('#dateRangePicker').val(today + ' to ' + today);
            window.userStatisticsDateRange = today + '|' + today;

            $('#dateRangePicker').daterangepicker({
                startDate: moment(),
                endDate: moment(),
                autoUpdateInput: true,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                }
            });

            $('#showDateRange').html(today + ' to ' + today);

            $('#dateRangePicker').on('apply.daterangepicker', function (ev, picker) {
                const formatted = picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD');
                $(this).val(formatted);
                window.userStatisticsDateRange = picker.startDate.format('YYYY-MM-DD') + '|' + picker.endDate.format('YYYY-MM-DD');
                $('#showDateRange').html(formatted);
                $('#sales_table').DataTable().ajax.reload();
            });

            $('#dateRangePicker').on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
                window.userStatisticsDateRange = '';
                $('#showDateRange').html('All Data');
                $('#sales_table').DataTable().ajax.reload();
            });

            $('#clearDateRange').on('click', function () {
                $('#dateRangePicker').val('');
                window.userStatisticsDateRange = '';
                $('#showDateRange').html('All Data');
                $('#sales_table').DataTable().ajax.reload();
            });
        });

        function showStatisticsModal(id) {
            const modalId = 'showStatisticsModal-' + id;
            $('#' + modalId).remove();

            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable modal-dialog-top modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}Label">User Statistics</h5>
                                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
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

            $('body').append(modalHtml);
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();

            $.ajax({
                url: '{{ route("getUserStatistics") }}',
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    user_key: id,
                    date_range_filter: window.userStatisticsDateRange
                },
                success: function (response) {
                    let notesHtml = `
                        <div class="row bg-primary text-white rounded px-3 py-2 mb-3">
                            <div class="col-md-4">
                                ${response.user_name ? `<p class="mb-0"><strong>User:</strong> ${response.user_name}</p>` : ''}
                            </div>
                            <div class="col-md-4">
                                ${window.userStatisticsDateRange ? `<p class="mb-0"><strong>Date Range:</strong> ${window.userStatisticsDateRange}</p>` : ''}
                            </div>
                            <div class="col-md-4 text-md-end">
                                ${response.user_role ? `<p class="mb-0"><strong>Role:</strong> ${response.user_role}</p>` : ''}
                            </div>
                        </div>
                    `;

                    const currentIcons = {
                        cvs_sent: 'file-send-broken',
                        close_sales: 'bag-check-line-duotone',
                        open_sales: 'bag-cross-line-duotone',
                        psl_offices: 'office-line-duotone',
                        non_psl_offices: 'home-line-duotone',
                        cvs_cleared: 'shield-check-line-duotone',
                        cvs_rejected: 'shield-cross-line-duotone',
                        CRM_sent_cvs: 'plain-line-duotone',
                        CRM_rejected_cv: 'adhesive-plaster-line-duotone',
                        CRM_request: 'question-circle-line-duotone',
                        CRM_rejected_by_request: 'user-cross-line-duotone',
                        CRM_confirmation: 'check-circle-line-duotone',
                        CRM_rebook: 'refresh-line-duotone',
                        CRM_attended: 'calendar-add-line-duotone',
                        CRM_not_attended: 'calendar-minimalistic-line-duotone',
                        CRM_start_date: 'calendar-line-duotone',
                        CRM_start_date_hold: 'calendar-search-line-duotone',
                        CRM_declined: 'quit-full-screen-line-duotone',
                        CRM_invoice: 'file-text-line-duotone',
                        CRM_dispute: 'shield-warning-line-duotone',
                        CRM_paid: 'wallet-line-duotone'
                    };

                    const prevIcons = {
                        CRM_start_date: 'calendar-line-duotone',
                        CRM_invoice: 'file-text-line-duotone',
                        CRM_paid: 'wallet-line-duotone'
                    };

                    function renderQualityStatBlock(data, icons, badgeClass) {
                        let html = `<div class="row">`;
                        Object.entries(data).forEach(([key, value]) => {
                            const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            const icon = icons[key] || 'dot-line-duotone';
                            html += `
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex align-items-center border rounded p-3 h-100">
                                        <iconify-icon icon="solar:${icon}" class="fs-1 text-${badgeClass} me-3"></iconify-icon>
                                        <div class="d-flex flex-column justify-content-center">
                                            <span class="fs-4 fw-bold text-${badgeClass}">${value}</span>
                                            <small class="text-muted">${label}</small>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        html += `</div>`;
                        return html;
                    }

                    function renderStatBlock(data, icons, badgeClass) {
                        let html = `<div class="row">`;
                        Object.entries(data).forEach(([key, value]) => {
                            const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            const icon = icons[key] || 'dot-line-duotone';
                            html += `
                                <div class="col-md-3 mb-3">
                                    <div class="d-flex align-items-center border rounded p-3 h-100">
                                        <iconify-icon icon="solar:${icon}" class="fs-1 text-${badgeClass} me-3"></iconify-icon>
                                        <div class="d-flex flex-column justify-content-center">
                                            <span class="fs-4 fw-bold text-${badgeClass}">${value}</span>
                                            <small class="text-muted">${label}</small>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        html += `</div>`;
                        return html;
                    }

                    if (response.quality_stats && Object.keys(response.quality_stats).length > 0) {
                        notesHtml += '<h6 class="mt-3">Quality Statistics</h6>';
                        notesHtml += renderQualityStatBlock(response.quality_stats, currentIcons, 'primary');
                    }

                    if (response.user_stats && Object.keys(response.user_stats).length > 0) {
                        notesHtml += '<h6 class="mt-3">CRM Statistics</h6>';
                        notesHtml += renderStatBlock(response.user_stats, currentIcons, 'primary');
                    }

                    if (response.prev_user_stats && Object.keys(response.prev_user_stats).length > 0) {
                        notesHtml += '<h6 class="mt-4">Previous Month Stats</h6>';
                        notesHtml += renderQualityStatBlock(response.prev_user_stats, prevIcons, 'secondary');
                    }

                    $(`#${modalId}-loader`).hide();
                    $(`#${modalId}-content`).removeClass('d-none').html(notesHtml);
                },
                error: function (xhr) {
                    $(`#${modalId}-loader`).hide();
                    $(`#${modalId}-content`).removeClass('d-none').html('<p class="text-danger">Error retrieving statistics. Please try again.</p>');
                    console.error(xhr.responseText);
                }
            });
        }

        function fetchWeeklySales() {
            return new Promise((resolve, reject) => {
                fetch('/get-weekly-sales')
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('weeklySalesCount').textContent = data.total;
                        updateSalesChart(data.chartData);
                        let html = `<table class="table table-bordered">
                                        <thead>
                                            <tr><th>ID</th><th>Office Name</th><th>Unit Name</th><th>PostCode</th><th>Date</th></tr>
                                        </thead>
                                        <tbody>`;
                        data.details.forEach(sale => {
                            html += `<tr>
                                <td>${sale.id}</td>
                                <td>${sale.office?.office_name ?? ''}</td>
                                <td>${sale.unit?.unit_name ?? ''}</td>
                                <td>${sale.sale_postcode ?? ''}</td>
                                <td>${moment(sale.created_at).format('DD-MM-YYYY hh:mm A')}</td>
                            </tr>`;
                        });
                        html += '</tbody></table>';
                        document.getElementById('weeklySalesDetails').innerHTML = html;
                        resolve(data);
                    })
                    .catch(error => {
                        console.error('Error fetching weekly sales:', error);
                        document.getElementById('weeklySalesDetails').innerHTML = '<p class="text-danger">Failed to load data.</p>';
                        reject(error);
                    });
            });
        }

        // Initial call for weekly sales
        $.when(fetchWeeklySales()).done(function() {
            console.log('Weekly sales fetched successfully');
        }).fail(function(error) {
            console.error('Failed to fetch weekly sales:', error);
        });

        setInterval(function() {
            $.when(fetchWeeklySales()).done(function() {
                console.log('Weekly sales updated');
            });
        }, 60000);
    </script>

    @vite(['resources/js/pages/dashboard-analytics.js'])
@endsection
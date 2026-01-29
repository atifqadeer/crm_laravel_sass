@extends('layouts.vertical', ['title' => 'Dashboard', 'subTitle' => 'Dashboard'])

@section('content')
    <!-- start page title -->
    <style>
        .main-nav {
            margin-top: 15px;
        }
        .card {
            margin-bottom: 1.5625rem;
            box-shadow: 0 0.5rem 3.25rem rgba(0, 0, 0, 0.05);
        }
        .collapse {
            visibility: visible;
        }
        /* ApexCharts legend in 4 columns */
        .apexcharts-legend {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px 12px;
            justify-items: start;
        }

        /* Each legend item */
        .apexcharts-legend-series {
            margin: 0 !important;
            padding: 2px 0;
            white-space: nowrap;
        }
        @media (max-width: 768px) {
            .apexcharts-legend {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .apexcharts-legend {
                grid-template-columns: 1fr;
            }
        }

    </style>
    @canany(['dashboard-top-stats'])
        <div class="row">
            <div class="col-md-3 col-xl-3">
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
            <div class="col-md-3 col-xl-3">
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
            <div class="col-md-3 col-xl-3">
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
            <div class="col-md-3 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-2 fs-15 fw-medium">Total Open Sales</p>
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
            <div class="col-md-4 col-xl-4">
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
            <div class="col-md-4 col-xl-4">
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
            <div class="col-md-4 col-xl-4">
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
    @canany(['dashboard-top-stats'])
        <div class="row">
            <div class="col-xl-7 col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center pb-1">
                        <div>
                            <h4 class="card-title mb-0">Statistics</h4>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <!-- Stats Range Filter -->
                            <div class="dropdown">
                                <a href="#" id="statsRangeBtn" 
                                class="dropdown-toggle btn btn-sm btn-outline-light rounded"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                    Daily
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="#!" class="dropdown-item stats-filter active" data-range="daily">Daily</a>
                                    <a href="#!" class="dropdown-item stats-filter" data-range="weekly">Weekly</a>
                                    <a href="#!" class="dropdown-item stats-filter" data-range="monthly">Monthly</a>
                                    <a href="#!" class="dropdown-item stats-filter" data-range="yearly">Yearly</a>
                                    <a href="#!" class="dropdown-item stats-filter" data-range="aggregate">Aggregate</a>
                                </div>
                            </div>

                            <!-- Adaptive Calendar -->
                            <input type="text" id="statsDateRange" class="form-control form-control-sm" 
                                placeholder="Select date" style="width: 220px;" />
                        </div>
                    </div>
                    <div class="card-body p-2 mx-3">
                        <!-- Applicants Statistics Created -->
                        <div class="row">
                            <h6 class="mt-3 mb-1">Applicants Statistics (Created)</h6><hr>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:stethoscope-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="nurses">
                                        <span class="fs-4 fw-bold text-primary stats-nurses-created"></span>
                                        <small class="text-muted">Nurses</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:user-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="non_nurses">
                                        <span class="fs-4 fw-bold text-primary stats-non-nurses-created"></span>
                                        <small class="text-muted">Non Nurses</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:history-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="callbacks">
                                        <span class="fs-4 fw-bold text-primary stats-callbacks-created"></span>
                                        <small class="text-muted">Callbacks</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:forbidden-circle-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="not_interested">
                                        <span class="fs-4 fw-bold text-primary stats-not-interested-created"></span>
                                        <small class="text-muted">Not Interested</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Applicants Statistics Updated -->
                        <div class="row">
                            <h6 class="mt-3 mb-1">Applicants Statistics (Updated)</h6><hr>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:stethoscope-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="nurses">
                                        <span class="fs-4 fw-bold text-primary stats-nurses-updated"></span>
                                        <small class="text-muted">Nurses</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:user-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="non_nurses">
                                        <span class="fs-4 fw-bold text-primary stats-non-nurses-updated"></span>
                                        <small class="text-muted">Non Nurses</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:history-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="callbacks">
                                        <span class="fs-4 fw-bold text-primary stats-callbacks-updated"></span>
                                        <small class="text-muted">Callbacks</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:forbidden-circle-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="not_interested">
                                        <span class="fs-4 fw-bold text-primary stats-not-interested-updated"></span>
                                        <small class="text-muted">Not Interested</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Sales Statistics Created -->
                        <div class="row">
                            <h6 class="mt-2 mb-1">Sales Statistics (Created)</h6><hr>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:bag-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="open_sale">
                                        <span class="fs-4 fw-bold text-primary stats-open-created"></span>
                                        <small class="text-muted">Open</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:bag-cross-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="close_sale">
                                        <span class="fs-4 fw-bold text-primary stats-close-created"></span>
                                        <small class="text-muted">Close</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:hourglass-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="pending_sale">
                                        <span class="fs-4 fw-bold text-primary stats-pending-created"></span>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:shield-cross-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="rejected_sale">
                                        <span class="fs-4 fw-bold text-primary stats-rejected-created"></span>
                                        <small class="text-muted">Rejected</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Sales Statistics Updated -->
                        <div class="row">
                            <h6 class="mt-2 mb-1">Sales Statistics (Updated)</h6><hr>
                           <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center rounded p-3 h-100 bg-light-primary border-0">
                                    <iconify-icon icon="solar:bag-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    
                                    <div class="d-flex w-100">
                                        <div class="d-flex flex-column justify-content-center stat-box border-end pe-3" data-type="open_sale_updated" style="flex: 1;">
                                            <span class="fs-4 fw-bold text-primary stats-open-updated">0</span>
                                            <small class="text-muted fw-semibold">Open</small>
                                        </div>

                                        <div class="d-flex flex-column justify-content-center stat-box ps-3" data-type="reopen_sale_updated" style="flex: 1;">
                                            <span class="fs-4 fw-bold text-primary stats-reopen-updated">0</span>
                                            <small class="text-muted fw-semibold">ReOpen</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:bag-cross-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="close_sale">
                                        <span class="fs-4 fw-bold text-primary stats-close-updated"></span>
                                        <small class="text-muted">Close</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:hourglass-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="pending_sale">
                                        <span class="fs-4 fw-bold text-primary stats-pending-updated"></span>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:shield-cross-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="rejected_sale">
                                        <span class="fs-4 fw-bold text-primary stats-rejected-updated"></span>
                                        <small class="text-muted">Rejected</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Quality Statistics -->
                        <div class="row">
                            <h6 class="mt-2 mb-1">Quality Statistics</h6><hr>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:clipboard-check-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="requested_cvs">
                                        <span class="fs-4 fw-bold text-primary stats-requested-cvs"></span>
                                        <small class="text-muted">Requested CVs</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:folder-open-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="open_cvs">
                                        <span class="fs-4 fw-bold text-primary stats-open-cvs"></span>
                                        <small class="text-muted">Open CVs</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:shield-cross-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="rejected_cvs">
                                        <span class="fs-4 fw-bold text-primary stats-rejected-cvs"></span>
                                        <small class="text-muted">Rejected CVs</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center  rounded p-3 h-100">
                                    <iconify-icon icon="solar:shield-check-line-duotone" class="fs-1 text-primary me-3"></iconify-icon>
                                    <div class="d-flex flex-column justify-content-center stat-box" data-type="cleared_sale">
                                        <span class="fs-4 fw-bold text-primary stats-cleared-cvs"></span>
                                        <small class="text-muted">Cleared CVs</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            <div class="col-xl-5 col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center pb-1">
                        <div>
                            <h4 class="card-title mb-0">CRM Statistics Chart</h4>
                        </div>
                    </div>
                    <div class="card-body d-flex py-3"> <!-- fixed min-height -->
                        <div id="statisticsChart" style="flex: 1; min-width: 600px;"></div>
                    </div>
                </div>
            </div>
        </div>
    @endcanany
    @canany(['dashboard-sales-analytics-chart','dashboard-sales-weekly-analytics'])
        <div class="row">
            @canany(['dashboard-sales-analytics-chart'])
                <div class="col-xl-9 col-lg-8">
                    <div class="card overflow-hidden">
                        <div class="card-header d-flex justify-content-between align-items-center pb-1">
                            <div>
                                <h4 class="card-title mb-0">Sales Analytics</h4>
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
                <div class="col-xl-3 col-lg-4">
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

    <!-- Modal -->
    <div class="modal fade" id="applicantDetailsModal" tabindex="-1" aria-labelledby="applicantDetailsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-top">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="applicantDetailsLabel">Applicants Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Job Type Summary -->
                <div class="row mb-4 text-center">
                <div class="col-md-6">
                    <h6>Regular</h6>
                    <h3 id="regularCount">0</h3>
                </div>
                <div class="col-md-6">
                    <h6>Specialist</h6>
                    <h3 id="specialistCount">0</h3>
                </div>
                </div>

                <!-- Job Source Breakdown -->
                <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                    <th>Job Source</th>
                    <th>Count</th>
                    </tr>
                </thead>
                <tbody id="jobSourceBreakdown">
                    <tr><td colspan="2" class="text-center">No data available</td></tr>
                </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>
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

    <!-- Tailwind CSS CDN (for skeleton loader styling) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Daterangepicker CSS/JS -->
    <link rel="stylesheet" href="{{ asset('css/daterangepicker.css') }}" />
    <script src="{{ asset('js/daterangepicker.min.js')}}"></script>

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- Month Select -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    
    <!-- Include ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

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

        let currentRange = 'daily';
        let currentDateRange = null;
        let fp = null;

        const dateInput = document.getElementById("statsDateRange");
        const rangeBtn = document.getElementById("statsRangeBtn");

        // Correct syntax for calling:
        loadStatsBoxes('daily');
        loadChartData('daily');
        initFlatpickr("daily");

        /*** statistics data **/
        function loadStatsBoxes(range, dateRange = null) {
            $.get('/dashboard/statistics-data', {
                range: range,
                date_range: dateRange
            }, function (resp) {

                $('.stats-nurses-created').text(resp.applicants?.nurses.created ?? 0);
                $('.stats-non-nurses-created').text(resp.applicants?.non_nurses.created ?? 0);
                $('.stats-callbacks-created').text(resp.applicants?.callbacks.created ?? 0);
                $('.stats-not-interested-created').text(resp.applicants?.not_interested.created ?? 0);

                $('.stats-nurses-updated').text(resp.applicants?.nurses.updated ?? 0);
                $('.stats-non-nurses-updated').text(resp.applicants?.non_nurses.updated ?? 0);
                $('.stats-callbacks-updated').text(resp.applicants?.callbacks.updated ?? 0);
                $('.stats-not-interested-updated').text(resp.applicants?.not_interested.updated ?? 0);

                $('.stats-open-created').text(resp.sales?.open.created ?? 0);
                $('.stats-close-created').text(resp.sales?.close.created ?? 0);
                $('.stats-pending-created').text(resp.sales?.pending.created ?? 0);
                $('.stats-rejected-created').text(resp.sales?.rejected.created ?? 0);

                $('.stats-open-updated').text(resp.sales?.open.updated ?? 0);
                $('.stats-reopen-updated').text(resp.sales?.reopen ?? 0);
                $('.stats-close-updated').text(resp.sales?.close.updated ?? 0);
                $('.stats-pending-updated').text(resp.sales?.pending.updated ?? 0);
                $('.stats-rejected-updated').text(resp.sales?.rejected.updated ?? 0);

                $('.stats-requested-cvs').text(resp.quality?.requested_cvs ?? 0);
                $('.stats-open-cvs').text(resp.quality?.open_cvs ?? 0);
                $('.stats-rejected-cvs').text(resp.quality?.rejected_cvs ?? 0);
                $('.stats-cleared-cvs').text(resp.quality?.cleared_cvs ?? 0);
            });
        }

        // When any stats box is clicked
        $(document).on('click', '.stat-box', function () {
            const type = $(this).data('type');
            const selectedDate = $('#selectedDate').val() || null; // Assuming your page has a date picker or variable
            if (!type) {
                console.warn('⚠️ Missing data-type on this .stat-box element.');
                return;
            }

            $.ajax({
                url: '/dashboard/statistics-details',
                method: 'GET',
                data: { type, date: selectedDate },
                success: function(resp) {
                    // Set modal title
                    $('#applicantDetailsLabel').text(resp.title);

                    // Set counts
                    $('#regularCount').text(resp.job_types.regular ?? 0);
                    $('#specialistCount').text(resp.job_types.specialist ?? 0);

                    // Fill Job Source Breakdown
                    const tbody = $('#jobSourceBreakdown');
                    tbody.empty();

                    if (resp.sources && Object.keys(resp.sources).length > 0) {
                        $.each(resp.sources, function (name, count) {
                            tbody.append(`<tr><td>${name}</td><td>${count}</td></tr>`);
                        });
                    } else {
                        tbody.html('<tr><td colspan="2" class="text-center">No data available</td></tr>');
                    }

                    // Show modal
                    $('#applicantDetailsModal').modal('show');
                },
                error: function(xhr) {
                    console.error('Error:', xhr.responseText);
                }
            });
        });

        let chart;

        function loadChartData(range = 'daily', dateRange = null) {
            // ✅ If no date selected, use today (d-m-Y)
            if (!dateRange) {
                const today = new Date();
                const day   = String(today.getDate()).padStart(2, '0');
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const year  = today.getFullYear();

                dateRange = `${day}-${month}-${year}`;
            }
            
            $.get('/statistics/chart-data', {
                range: range,
                date_range: dateRange
            }, function (response) {
                if (response.series?.length) {
                    chart.updateOptions({ labels: response.labels });
                    chart.updateSeries(response.series);
                } else {
                    chart.updateSeries([]);
                }
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
            // Default UI
            currentRange = 'daily';
            rangeBtn.innerText = 'Daily';

            document.querySelectorAll('.stats-filter').forEach(el => el.classList.remove('active'));
            document.querySelector('[data-range="daily"]').classList.add('active');

            // Init calendar
            initFlatpickr('daily');

            // Init chart
            chart = new ApexCharts(
                document.querySelector("#statisticsChart"),
                {
                    chart: { type: 'donut', height: 720 },

                    series: [],
                    labels: [],

                    // 🎨 Your custom colors
                    colors: [
                        "#4B70E2",
                        "#E57373",
                        "#81C784",
                        "#FFD54F",
                        "#BA68C8",
                        "#4DD0E1",
                        "#F06292",
                        "#9575CD",
                        "#4DB6AC",
                        "#7986CB",
                        "#A1887F",
                        "#64B5F6",
                        "#E0A96D",
                        "#90A4AE",
                        "#FFB74D",
                        "#AED581",
                        "#FBC02D"
                    ],

                    noData: { text: 'Loading chart data...' },
                    dataLabels: { enabled: false },

                    legend: {
                        position: 'bottom',
                        fontSize: '13px',
                        markers: {
                            width: 12,
                            height: 12,
                            radius: 12
                        },
                        formatter: function (val, opts) {
                            const value = opts.w.globals.series[opts.seriesIndex] || 0;
                            return `${val} - ${value}`;
                        }
                    }
                }
            );


            chart.render();

            // Load data with TODAY
            loadStatsBoxes(currentRange, currentDateRange);
            loadChartData(currentRange, currentDateRange);
        });

        function initFlatpickr(mode) {
            if (fp) fp.destroy();

            const today = new Date();

            let options = {
                allowInput: false,
                defaultDate: today,
                onChange: function (selectedDates, dateStr) {
                    if (!dateStr) return;

                    currentDateRange = dateStr;
                    loadStatsBoxes(currentRange, currentDateRange);
                    loadChartData(currentRange, currentDateRange);
                }
            };

            if (mode === "daily") {
                options.dateFormat = "Y-m-d";
            } 
            else if (mode === "weekly") {
                options.mode = "range";
                options.dateFormat = "Y-m-d";
            } 
            else if (mode === "monthly") {
                options.plugins = [new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: "Y-m",
                    altFormat: "F Y"
                })];
            } 
            else if (mode === "yearly") {
                options.plugins = [new flatpickr.plugins.yearSelect({
                    shorthand: true,
                    dateFormat: "Y"
                })];
            } 
            else if (mode === "aggregate") {
                options.mode = "range";
                options.dateFormat = "Y-m-d";
            }

            fp = flatpickr(dateInput, options);
            currentDateRange = fp.input.value; // today
        }

        // Dropdown filter click
        document.querySelectorAll(".stats-filter").forEach(item => {
            item.addEventListener("click", function (e) {
                e.preventDefault();

                document.querySelectorAll('.stats-filter').forEach(el => el.classList.remove('active'));
                this.classList.add('active');

                currentRange = this.dataset.range;
                rangeBtn.innerText = this.innerText;

                initFlatpickr(currentRange);

                loadStatsBoxes(currentRange, currentDateRange);
                loadChartData(currentRange, currentDateRange);
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

        $(document).ready(function () {
             // Create loader row
            const loadingRow = `<tr><td colspan="100%" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td></tr>`;

            // Function to show loader
            function showLoader() {
                $('#users_table tbody').empty().append(loadingRow);
            }

            // Initialize DataTable with server-side processing
            const table = $('#users_table').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: @json(route('getUsersForDashboard')),
                    type: 'GET',
                    data: function(d) {
                        d.status_filter = window.currentFilter || '';
                    },
                    beforeSend: function() {
                        showLoader(); // Show loader before AJAX request starts
                    },
                    error: function(xhr) {
                        console.error('DataTable AJAX error:', xhr.status, xhr.responseJSON);
                        $('#users_table tbody').empty().html('<tr><td colspan="100%" class="text-center">Failed to load data</td></tr>');
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
                drawCallback: function(settings) {
                    const api = this.api();
                    const pagination = $(api.table().container()).find('.dataTables_paginate');
                    pagination.empty();

                    const pageInfo = api.page.info();
                    const currentPage = pageInfo.page + 1;
                    const totalPages = pageInfo.pages;

                    if (pageInfo.recordsTotal === 0) {
                        $('#users_table tbody').html(
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

            // Handle DataTable search
            // $('#users_table_filter input').on('keyup', function() {
            //     table.search(this.value).draw();
            // });
        });

        function goToPage(totalPages) {
            const input = document.getElementById('goToPageInput');
            const errorMessage = document.getElementById('goToPageError');
            let page = parseInt(input.value);

            if (!isNaN(page) && page >= 1 && page <= totalPages) {
                $('#users_table').DataTable().page(page - 1).draw('page');
                input.classList.remove('is-invalid');
            } else {
                input.classList.add('is-invalid');
            }
        }

        // Function to move the page forward or backward
        function movePage(page) {
            var table = $('#users_table').DataTable();
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

    </script>
    @vite(['resources/js/pages/dashboard-analytics.js'])
@endsection
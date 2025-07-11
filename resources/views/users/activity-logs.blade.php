@extends('layouts.vertical', ['title' => 'Activity Logs', 'subTitle' => 'Users'])
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
@php $user_id = request()->query('id'); @endphp
<div class="row">
    <div class="col-lg-12">
       
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="users_table" class="table align-middle mb-3">
                        <thead class="bg-light-subtle">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Message</th>
                                <th>Module</th>
                                <th>Details</th>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables CSS (for styling the table) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

    <!-- DataTables JS (for the table functionality) -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
   
    <!-- Toastr css -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        $(document).ready(function() {
            // Store the current filter in a variable
            var user_id = @json($user_id);

            // Create a loader row and append it to the table before initialization
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="100%" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td>`;

            // Append the loader row to the table's tbody
            $('#users_table tbody').append(loadingRow);

            // Initialize DataTable with server-side processing
            var table = $('#users_table').DataTable({
                processing: false,  // Disable default processing state
                serverSide: true,  // Enables server-side processing
                ajax: {
                    url: @json(route('getUserActivityLogs')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        // Add the current filter to the request parameters
                        d.id = user_id;  // Send the current filter value as a parameter
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at', name: 'audits.created_at' },
                    { data: 'message', name: 'audits.message'  },
                    { data: 'auditable_type', name: 'audits.auditable_type' },
                    { data: 'details', name: 'audits.data' }
                ],
                columnDefs: [
                    {
                        targets: 4,  // Column index for 'job_details'
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
                        $('#users_table tbody').html('<tr><td colspan="100%" class="text-center">Data not found</td></tr>');
                    } else {
                        // Build the custom pagination structure
                        var paginationHtml = `
                            <nav aria-label="Page navigation example">
                                <ul class="pagination pagination-rounded mb-0">
                                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                                        <a class="page-link" href="javascript:void(0);" aria-label="Previous" onclick="movePage('previous')">
                                            <span >&laquo;</span>
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
                                            <span >&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>`;

                        pagination.html(paginationHtml); // Append custom pagination HTML
                    }
                }
            });

            // Handle filter button clicks and send filter parameters to the DataTable
            $('.dropdown-item').on('click', function() {
                  currentFilter = $(this).text().toLowerCase();

                // Capitalize each word
                const formattedText = currentFilter
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');

                $('#showFilterStatus').html(formattedText);
                table.ajax.reload(); // Reload with updated status filter
            });

             // Handle the DataTable search
            $('#users_table_filter input').on('keyup', function() {
                table.search(this.value).draw(); // Manually trigger search
            });
        });

        // Function to move the page forward or backward
        function movePage(page) {
            var table = $('#users_table').DataTable();
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
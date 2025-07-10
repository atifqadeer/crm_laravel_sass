@extends('layouts.vertical', ['title' => 'IP Address List', 'subTitle' => 'Home'])
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
@php
    $users = \DB::table('users')->where('is_active', true)->orderBy('name', 'asc')->get();
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-0">
                <div class="row justify-content-between">
                    <div class="col-lg-12">
                        <div class="text-md-end mt-3">
                            <!-- Button Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-filter-line me-1"></i>  <span id="showFilterStatus">All</span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                    <a class="dropdown-item" href="#">All</a>
                                    <a class="dropdown-item" href="#">Active</a>
                                    <a class="dropdown-item" href="#">Inactive</a>
                                </div>
                            </div>
                            <!-- Button Dropdown -->
                            <div class="dropdown d-inline">
                                <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-download-line me-1"></i> Export
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                    <a class="dropdown-item" href="{{ route('ipaddressExport', ['type' => 'all']) }}">Export All Data</a>
                                </div>
                            </div>
                            <!-- Create User Button triggers modal -->
                            <button type="button" class="btn btn-success ml-1 my-1" onclick="createIP()">
                                <i class="ri-add-line"></i> Create IP Address
                            </button>
                        </div>
                    </div>
                    <!-- end col-->
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
                    <table id="ip_table" class="table align-middle mb-3">
                        <thead class="bg-light-subtle">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>User Name</th>
                                <th>IP Address</th>
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

<!-- Create ip address Modal -->
<div class="modal fade" id="createIPModal" tabindex="-1" aria-labelledby="createIPModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-top">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add IP Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createIPForm">
                    @csrf
                    <div class="mb-3">
                        <label for="userName" class="form-label">User Name</label>
                        <select class="form-select" id="userName" name="user_id" required>
                            <option value="">Select User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ ucwords($user->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="ip_address" name="ip_address" placeholder="Enter Valid IP Address" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="savecreateIPButton">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- edit ip address Modal -->
<div class="modal fade" id="editIPModal" tabindex="-1" aria-labelledby="editIPModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-top">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit IP Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editIPForm">
                    @csrf
                    <input type="hidden" id="ip_id" name="id">
                    <div class="mb-3">
                        <label for="editUserName" class="form-label">User Name</label>
                        <select class="form-select" id="editUserName" name="user_id" required>
                            <option value="">Select User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ ucwords($user->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="edit_ip_address" name="ip_address" placeholder="Enter Valid IP Address" required>
                    </div>
                    <div class="mb-3">
                        <label for="editIPStatus" class="form-label">Status</label>
                        <select class="form-select" id="editIPStatus" name="status" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="saveEditIPButton">Save</button>
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
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Store the current filter in a variable
            var currentFilter = '';

            // Create a loader row and append it to the table before initialization
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="14" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td>`;

            // Append the loader row to the table's tbody
            $('#ip_table tbody').append(loadingRow);

            // Initialize DataTable with server-side processing
            var table = $('#ip_table').DataTable({
                processing: false,  // Disable default processing state
                serverSide: true,  // Enables server-side processing
                ajax: {
                    url: @json(route('getIPs')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        // Add the current filter to the request parameters
                        d.status_filter = currentFilter;  // Send the current filter value as a parameter
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at', name: 'ip_addresses.created_at' },
                    { data: 'user_name', name: 'users.name'  },
                    { data: 'ip_address', name: 'ip_addresses.ip_address' },
                    { data: 'status', name: 'ip_addresses.status', orderable: false },
                    { data: 'action', name: 'action', orderable: false }
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
                        $('#users_table tbody').html('<tr><td colspan="14" class="text-center">Data not found</td></tr>');
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
            $('#ip_table_filter input').on('keyup', function() {
                table.search(this.value).draw(); // Manually trigger search
            });
        });

        // Function to move the page forward or backward
        function movePage(page) {
            var table = $('#ip_table').DataTable();
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

        document.addEventListener('DOMContentLoaded', function () {
            const editModal = $('#editIPModal');
            const editForm = $('#editIPForm');
            const saveEditBtn = $('#saveEditIPButton');

            // Show modal and populate fields
            window.showEditModal = function (id, ip_address, user_id, status) {
                $('#ip_id').val(id);
                $('#edit_ip_address').val(ip_address);
                if (typeof user_id !== 'undefined') {
                    $('#editUserName').val(user_id);
                }
               
                if (typeof status !== 'undefined') {
                    $('#editIPStatus').val(status);
                }

                editModal.modal('show');
            };

            // Save user changes
            saveEditBtn.on('click', function () {
                const userId = $('#ip_id').val();
                const url = '{{ route("ip-address.update") }}';
                const method = 'PUT';
                const form = $('#editIPForm');
                const formData = form.serialize();

                // Clear previous errors
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').remove();

                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    success: function (response) {
                        toastr.success(response.message);
                        editModal.modal('hide');
                        editForm[0].reset();
                        $('#ip_table').DataTable().ajax.reload();
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;

                            for (let field in errors) {
                                let input = form.find(`[name="${field}"]`);
                                input.addClass('is-invalid');

                                if (input.next('.invalid-feedback').length === 0) {
                                    input.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
                                }
                            }
                        } else {
                            toastr.error('An error occurred while updating the ip.');
                        }
                    }
                });
            });
        });

        function showDetailsModal(unitId, officeName, name, postcode, status) {
            // Set the notes content in the modal as a table
            $('#showDetailsModal .modal-body').html(
                '<table class="table table-bordered">' +
                    '<tr>' +
                        '<th>Unit ID</th>' +
                        '<td>' + unitId + '</td>' +
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
                        '<th>Status</th>' +
                        '<td>' + status + '</td>' +
                    '</tr>' +
                '</table>'
            );

            // Show the modal
            $('#showDetailsModal').modal('show');

            // Add the modal HTML to the page (only once, if not already present)
            if ($('#showDetailsModal').length === 0) {
                $('body').append(
                    '<div class="modal fade" id="showDetailsModal" tabindex="-1" aria-labelledby="showDetailsModalLabel" >' +
                        '<div class="modal-dialog modal-lg modal-dialog-top">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<h5 class="modal-title" id="showDetailsModalLabel">Unit Details</h5>' +
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
        }

        $(document).on('click', '.toggle-password', function() {
            var input = $($(this).data('target'));
            var icon = $(this).find('i');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('ri-eye-off-line').addClass('ri-eye-line');
            } else {
                input.attr('type', 'password');
                icon.removeClass('ri-eye-line').addClass('ri-eye-off-line');
            }
        });

        function createIP() {
            $('#createIPModal').modal('show');

            $('#savecreateIPButton').off('click').on('click', function () {
                let form = $('#createIPForm');
                let formData = form.serialize();

                // Clear previous errors
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').remove();

                $.ajax({
                    url: '{{ route("ip-address.store") }}',
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        toastr.success('IP Address created successfully!');
                        $('#createIPModal').modal('hide');
                        form[0].reset();

                        $('#ip_table').DataTable().ajax.reload(); // Reload the DataTable
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            for (let key in errors) {
                                let input = form.find('[name="' + key + '"]');
                                input.addClass('is-invalid');
                                input.after('<div class="invalid-feedback">' + errors[key][0] + '</div>');
                            }
                        } else {
                            alert('An error occurred.');
                        }
                    }
                });
            });
        }

        function deleteIpAddress(id) {
            Swal.fire({
            title: 'Are you sure?',
            text: 'This IP address will be permanently deleted. Are you sure you want to continue?',
            icon: 'warning',
            showCancelButton: true,
            customClass: {
                confirmButton: 'btn bg-danger text-white me-2 mt-2',
                cancelButton: 'btn btn-secondary mt-2'
            },
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                url: '{{ route("ip-address.destroy") }}',
                type: 'post',
                data: {
                    id: id,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success(response.message || 'IP Address deleted successfully!');
                    $('#ip_table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    toastr.error('An error occurred while deleting the IP address.');
                }
                });
            }
            });
        }
    </script>
    
@endsection
@endsection                        
@extends('layouts.vertical', ['title' => 'User Login Report', 'subTitle' => 'Home'])
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
                                <!-- Date Range Picker -->
                                <div class="d-inline">
                                    <input type="text" id="singleDatePicker" class="form-control d-inline-block" style="width: 160px; display: inline-block;" placeholder="Select date" readonly />
                                    <button class="btn btn-outline-primary my-1" type="button" id="clearSingleDate" title="Clear Date">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                                <!-- user Filter Dropdown -->
                                <!-- Button Dropdown -->
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-primary me-1 my-1 dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-download-line me-1"></i> Export
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <a class="dropdown-item" href="#">Export All Data</a>
                                        <a class="dropdown-item" href="#">Export Emails</a>
                                    </div>
                                </div>
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
                        <table id="users_table" class="table align-middle mb-3">
                            <thead class="bg-light-subtle">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Credit Hours</th>
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

    <script>
        $(document).ready(function () {
            let selectedDate = ''; // Selected date to be sent in AJAX call

            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="100%" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td>`;
            $('#users_table tbody').append(loadingRow);

            var table = $('#users_table').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: @json(route('getUsersLoginReport')),
                    type: 'GET',
                    data: function (d) {
                        d.date_filter = selectedDate; // ✅ Send selected date
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'user_name', name: 'users.name' },
                    { data: 'created_at', name: 'login_details.created_at' },
                    { data: 'login_at', name: 'login_details.login_at' },
                    { data: 'logout_at', name: 'login_details.logout_at' },
                    { data: 'credit_hours', name: 'credit_hours' },
                    { data: 'action', name: 'action', orderable: false }
                ],
                rowId: function (data) {
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

            // Date picker
            $('#singleDatePicker').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: false,
                locale: {
                    format: 'YYYY-MM-DD',
                    cancelLabel: 'Clear'
                }
            });

            $('#singleDatePicker').on('apply.daterangepicker', function (ev, picker) {
                selectedDate = picker.startDate.format('YYYY-MM-DD');
                $(this).val(selectedDate);
                table.ajax.reload(); // Reload with new date
            });

            $('#singleDatePicker').on('cancel.daterangepicker', function (ev, picker) {
                selectedDate = '';
                $(this).val('');
                table.ajax.reload();
            });

            $('#clearSingleDate').on('click', function () {
                selectedDate = '';
                $('#singleDatePicker').val('');
                table.ajax.reload();
            });

            $('#users_table_filter input').on('keyup', function () {
                table.search(this.value).draw();
            });
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
                table.page(currentPage - 2).draw('page');  // Move to the previous page
            } else if (page === 'next' && currentPage < totalPages) {
                table.page(currentPage).draw('page');  // Move to the next page
            } else if (typeof page === 'number' && page !== currentPage) {
                table.page(page - 1).draw('page');  // Move to the selected page
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const editModal = $('#editUserModal');
            const editForm = $('#editUserForm');
            const saveEditBtn = $('#saveEditUserButton');

            // Show modal and populate fields
            window.showEditModal = function (id, name, email, status, roleId) {
                $('#userId').val(id);
                $('#editUserName').val(name);
                $('#editUserEmail').val(email);
                $('#editUserRole').val(roleId);
                if (typeof status !== 'undefined') {
                    $('#editUserStatus').val(status);
                }
                $('#editUserPassword').val('');
                $('#editUserPasswordConfirmation').val('');

                editModal.modal('show');
            };

            // Save user changes
            saveEditBtn.on('click', function () {
                const userId = $('#userId').val();
                const url = '{{ route("users.update") }}';
                const method = 'PUT';
                const form = $('#editUserForm');
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
                        $('#users_table').DataTable().ajax.reload();
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
                            toastr.error('An error occurred while updating the user.');
                        }
                    }
                });
            });
        });

        function showDetailsModal(id, name, email, role, status) {
            // Set the notes content in the modal as a table
            $('#showDetailsModal .modal-body').html(
                '<table class="table table-bordered">' +
                    '<tr>' +
                        '<th>Unit ID</th>' +
                        '<td>' + id + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>User Name</th>' +
                        '<td>' + name + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Email</th>' +
                        '<td>' + email + '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th>Role</th>' +
                        '<td>' + role + '</td>' +
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

        function createUser() {
            $('#createUserModal').modal('show');

            $('#savecreateUserButton').off('click').on('click', function () {
                let form = $('#createUserForm');
                let formData = form.serialize();

                // Clear previous errors
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').remove();

                $.ajax({
                    url: '{{ route("users.store") }}',
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        toastr.success('User created successfully!');
                        $('#createUserModal').modal('hide');
                        form[0].reset();

                        $('#users_table').DataTable().ajax.reload(); // Reload the DataTable
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

    </script>
    
@endsection
@endsection                        
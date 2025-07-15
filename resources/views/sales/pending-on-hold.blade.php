@extends('layouts.vertical', ['title' => 'Pending On Hold Sales List', 'subTitle' => 'Sales'])
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
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="sales_table" class="table align-middle mb-3">
                        <thead class="bg-light-subtle">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Open Date</th>
                                <th>Head Office</th>
                                <th>Unit Name</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>PostCode</th>
                                <th>Experience</th>
                                <th>Qualification</th>
                                <th>Salary</th>
                                <th>CV Limit</th>
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

<!-- Experience Modal -->
<div class="modal fade" id="experienceModal" tabindex="-1" aria-labelledby="experienceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="experienceModalLabel">Sale Experience</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="experienceModalBody">
        <!-- Experience will be injected here -->
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
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

         <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            // Store the current filter in a variable
            var currentFilter = '';

            // Create a loader row and append it to the table before initialization
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = `<td colspan="100%" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td>`;

            // Append the loader row to the table's tbody
            $('#sales_table tbody').append(loadingRow);

            // Initialize DataTable with server-side processing
            var table = $('#sales_table').DataTable({
                processing: false,  // Disable default processing state
                serverSide: true,  // Enables server-side processing
                ajax: {
                    url: @json(route('pendingOnHoldSales')),  // Fetch data from the backend
                    type: 'GET',
                    data: function(d) {
                        // Add the current filter to the request parameters
                        d.status_filter = currentFilter;  // Send the current filter value as a parameter
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at', name: 'sales.created_at' },
                    { data: 'updated_at', name: 'sales.updated_at' },
                    { data: 'office_name', name: 'office_name', searchable: false, orderable: false  },
                    { data: 'unit_name', name: 'unit_name', searchable: true  },
                    { data: 'job_title', name: 'sales.job_title_id'  },
                    { data: 'job_category', name: 'sales.job_category_id'  },
                    { data: 'sale_postcode', name: 'sales.sale_postcode' },
                    { data: 'experience', name: 'sales.experience' },
                    { data: 'qualification', name: 'sales.qualification' },
                    { data: 'salary', name: 'sales.salary' },
                    { data: 'cv_limit', name: 'sales.cv_limit' },
                    { data: 'sale_notes', name: 'sales.sale_notes', orderable: false },
                    { data: 'status', name: 'sales.status', orderable: false },
                    { data: 'action', name: 'action', orderable: false }
                ],
                columnDefs: [
                    {
                        targets: 11,  // Column index for 'job_details'
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).css('text-align', 'center');  // Center the text in this column
                        }
                    },
                    {
                        targets: 12,  // Column index for 'job_details'
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
                        $('#sales_table tbody').html('<tr><td colspan="100%" class="text-center">Data not found</td></tr>');
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

                    // Generate page range
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

                    // Always show last page if it's not already shown
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
                    </nav>`;

                    pagination.html(paginationHtml);
                },
            });

            // Handle filter button clicks and send filter parameters to the DataTable
            $('.dropdown-item').on('click', function() {
                // Get the selected filter value
                currentFilter = $(this).text().toLowerCase();

                // Update the DataTable request with the selected filter
                table.ajax.reload();  // Reload the table with the new filter
            });

             // Handle the DataTable search
            $('#sales_table_filter input').on('keyup', function() {
                table.search(this.value).draw(); // Manually trigger search
            });
        });

        // Function to move the page forward or backward
        function movePage(page) {
            var table = $('#sales_table').DataTable();
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
        
        // Function to show the notes modal
        function showNotesModal(saleId, notes, officeName, unitName, unitPostcode) {
            const modalId = `showNotesModal_${saleId}`;

            // Check and append modal only once
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalId}Label">Sale Notes</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <div class="spinner-border text-primary my-4" role="status">
                                        <span class="visually-hidden">Loading...</span>
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
                // Reset body content with loader if it already exists
                $(`#${modalId} .modal-body`).html(`
                    <div class="spinner-border text-primary my-4" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                `);
            }

            // Show modal
            $(`#${modalId}`).modal('show');

            // Set content after short delay (simulate loading)
            setTimeout(() => {
                $(`#${modalId} .modal-body`).html(`
                    <div class="text-start">
                        <p class="mb-1"><strong>Head Office Name:</strong> ${officeName}</p>
                        <p class="mb-1"><strong>Unit Name:</strong> ${unitName}</p>
                        <p class="mb-1"><strong>Postcode:</strong> ${unitPostcode}</p>
                        <p><strong>Notes Detail:</strong><br>${notes.replace(/\n/g, '<br>')}</p>
                    </div>
                `);
            }, 300); // adjust delay if needed
        }

        function showDetailsModal(
            saleId, postedOn, officeName, name, postcode, 
            jobCategory, jobTitle, status, timing, 
            experience, salary, position, qualification, benefits
        ) {
            const modalId = `showDetailsModal_${saleId}`;

            // Create modal if it doesn't already exist
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-top modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalId}Label">Sale Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body modal-body-text-left">
                                    <div class="spinner-border text-primary my-4" role="status">
                                        <span class="visually-hidden">Loading...</span>
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
                // Reset content with loader on each call
                $(`#${modalId} .modal-body`).html(`
                    <div class="spinner-border text-primary my-4" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                `);
            }

            // Show the modal
            $(`#${modalId}`).modal('show');

            // Simulate loading delay before filling in data
            setTimeout(() => {
                const tableHTML = `
                    <table class="table table-bordered mb-0">
                        <tr><th>Sale ID</th><td>${saleId}</td></tr>
                        <tr><th>Posted On</th><td>${postedOn}</td></tr>
                        <tr><th>Head Office Name</th><td>${officeName}</td></tr>
                        <tr><th>Unit Name</th><td>${name}</td></tr>
                        <tr><th>Postcode</th><td>${postcode}</td></tr>
                        <tr><th>Job Category</th><td>${jobCategory}</td></tr>
                        <tr><th>Job Title</th><td>${jobTitle}</td></tr>
                        <tr><th>Status</th><td>${status}</td></tr>
                        <tr><th>Timing</th><td>${timing}</td></tr>
                        <tr><th>Qualification</th><td>${qualification}</td></tr>
                        <tr><th>Salary</th><td>${salary}</td></tr>
                        <tr><th>Position</th><td>${position}</td></tr>
                        <tr><th>Experience</th><td>${experience}</td></tr>
                        <tr><th>Benefits</th><td>${benefits}</td></tr>
                    </table>
                `;

                $(`#${modalId} .modal-body`).html(tableHTML);
            }, 300); // Adjust delay to match actual data loading if needed
        }

        // Function to show the notes modal
        function viewNotesHistory(id) {
            const modalId = `viewNotesHistoryModal_${id}`;
            const modalLabelId = `viewNotesHistoryModalLabel_${id}`;

            // Create the modal if it doesn't already exist
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalLabelId}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalLabelId}">Sale Notes History</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <div class="spinner-border text-primary my-4" role="status">
                                        <span class="visually-hidden">Loading...</span>
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
                // Reset modal content with loader if already exists
                $(`#${modalId} .modal-body`).html(`
                    <div class="spinner-border text-primary my-4" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                `);
            }

            // Show the modal
            $(`#${modalId}`).modal('show');

            // AJAX request to fetch notes
            $.ajax({
                url: '{{ route("getModuleNotesHistory") }}',
                type: 'GET',
                data: {
                    id: id,
                    module: 'Horsefly\\Sale'
                },
                success: function(response) {
                    let notesHtml = '';

                    if (!response.data || response.data.length === 0) {
                        notesHtml = '<p class="text-muted text-center">No record found.</p>';
                    } else {
                        response.data.forEach(note => {
                            const created = moment(note.created_at).format('DD MMM YYYY, h:mmA');
                            const status = note.status;
                            const statusClass = status == 1 ? 'bg-success' : 'bg-dark';
                            const statusText = status == 1 ? 'Active' : 'Inactive';
                            const notes = note.details.replace(/\n/g, '<br>');

                            notesHtml += `
                                <div class="note-entry text-start">
                                    <p><strong>Dated:</strong> ${created} &nbsp;
                                    <span class="badge ${statusClass}">${statusText}</span></p>
                                    <p><strong>Notes Detail:</strong><br>${notes}</p>
                                </div><hr>
                            `;
                        });
                    }

                    $(`#${modalId} .modal-body`).html(notesHtml);
                },
                error: function(xhr) {
                    $(`#${modalId} .modal-body`).html('<p class="text-danger">There was an error retrieving the notes. Please try again later.</p>');
                }
            });
        }
       
        // Function to show the notes modal
        function viewManagerDetails(id) {
            const modalID = 'viewManagerDetailsModal-' + id;
            
            // Create modal if it doesn't exist
            if ($('#' + modalID).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalID}" tabindex="-1" aria-labelledby="viewManagerDetailsModalLabel-${id}">
                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="viewManagerDetailsModalLabel-${id}">Manager Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body modal-body-text-left">
                                    <div class="text-center py-3">
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
            }
            
            // Show modal immediately with loading state
            $('#' + modalID).modal('show');
            
            // Make AJAX call
            $.ajax({
                url: '{{ route("getModuleContacts") }}',
                type: 'GET',
                data: { 
                    id: id,
                    module: 'Horsefly\\Unit'
                },
                success: function(response) {
                    let contactHtml = '';
                    
                    if (response.data.length === 0) {
                        contactHtml = '<p>No record found.</p>';
                    } else {
                        response.data.forEach(function(contact) {
                            const name = contact.contact_name;
                            const email = contact.contact_email;
                            const phone = contact.contact_phone;
                            const landline = contact.contact_landline || '-';
                            const note = contact.contact_note || 'N/A';
                            
                            contactHtml += `
                                <div class="note-entry">
                                    <p><strong>Name:</strong> ${name}</p>
                                    <p><strong>Email:</strong> ${email}</p>
                                    <p><strong>Phone:</strong> ${phone}</p>
                                    <p><strong>Landline:</strong> ${landline}</p>
                                    <p><strong>Notes:</strong> ${note}</p>
                                </div><hr>`;
                        });
                    }
                    
                    $('#' + modalID + ' .modal-body').html(contactHtml);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching notes history:", error);
                    $('#' + modalID + ' .modal-body').html(
                        '<p class="text-danger">There was an error retrieving the manager details. Please try again later.</p>'
                    );
                }
            });
        }

        function changeSaleOnHoldStatusModal(saleId, status) {
            const statusLabel = status === 1 ? 'approved' : 'dis-approved';

            Swal.fire({
                title: 'Are you sure?',
                text: `This sale will be marked as ${statusLabel}. Are you sure you want to continue?`,
                icon: 'warning',
                showCancelButton: true,
                customClass: {
                    confirmButton: 'btn bg-danger text-white me-2 mt-2',
                    cancelButton: 'btn btn-dark mt-2'
                },
                confirmButtonText: 'Yes, Do it!',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    // Optional: add loading state after confirm click
                    const confirmBtn = Swal.getConfirmButton();
                    confirmBtn.addEventListener('click', () => {
                        confirmBtn.disabled = true;
                        confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Processing...`;
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('changeSaleHoldStatus') }}",
                        type: 'GET',
                        data: {
                            id: saleId,
                            status: status,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.close();
                            toastr.success(response.message || `Sale marked as ${statusLabel} successfully!`);
                            $('#sales_table').DataTable().ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.close();
                            toastr.error('An error occurred while updating the sale status.');
                        }
                    });
                }
            });
        }

        // Function to show the notes modal
        function viewSaleDocuments(saleId) {
            const modalId = `viewSaleDocumentsModal_${saleId}`;
            const modalLabelId = `viewSaleDocumentsModalLabel_${saleId}`;

            // Append modal HTML only if it doesn't already exist
            if ($(`#${modalId}`).length === 0) {
                $('body').append(`
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalLabelId}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-top">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="${modalLabelId}">Sale Documents</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <div class="spinner-border text-primary my-4" role="status">
                                        <span class="visually-hidden">Loading...</span>
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
                // Reset with loader if modal exists
                $(`#${modalId} .modal-body`).html(`
                    <div class="spinner-border text-primary my-4" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                `);
            }

            // Show modal
            $(`#${modalId}`).modal('show');

            // Make AJAX request
            $.ajax({
                url: '{{ route("getSaleDocuments") }}',
                type: 'GET',
                data: { id: saleId },
                success: function(response) {
                    let contentHtml = '';

                    if (!response.data || response.data.length === 0) {
                        contentHtml = '<p class="text-muted text-center">No record found.</p>';
                    } else {
                        response.data.forEach(doc => {
                            const created = moment(doc.created_at).format('DD MMM YYYY, h:mmA');
                            const filePath = '/storage/' + doc.document_path;
                            const docName = doc.document_name;

                            contentHtml += `
                                <div class="note-entry text-start">
                                    <p><strong>Dated:</strong> ${created}</p>
                                    <p><strong>File:</strong> ${docName}
                                        <br>
                                        <button class="btn btn-sm btn-primary mt-1" onclick="window.open('${filePath}', '_blank')">Open</button>
                                    </p>
                                </div>
                                <hr>
                            `;
                        });
                    }

                    $(`#${modalId} .modal-body`).html(contentHtml);
                },
                error: function(xhr) {
                    $(`#${modalId} .modal-body`).html('<p class="text-danger">There was an error retrieving the documents. Please try again later.</p>');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.body.addEventListener('click', function (e) {
                if (e.target.classList.contains('view-experience')) {
                    e.preventDefault();
                    const experience = e.target.getAttribute('data-experience');
                    document.getElementById('experienceModalBody').innerHTML = experience;
                }
            });
        });
    </script>
    
@endsection
@endsection                        
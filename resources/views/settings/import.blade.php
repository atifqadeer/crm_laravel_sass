@extends('layouts.vertical', ['title' => 'Import Data', 'subTitle' => 'Administrator'])

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mt-4">
        @foreach ([
            ['number' => 1, 'type' => 'Users', 'label' => 'Users'],
            ['number' => 2, 'type' => 'Offices', 'label' => 'Offices'],
            ['number' => 3, 'type' => 'Units', 'label' => 'Units'],
            ['number' => 4, 'type' => 'Applicants', 'label' => 'Applicants'],
            ['number' => 5, 'type' => 'Sales', 'label' => 'Sales'],
            ['number' => 6, 'type' => 'Messages', 'label' => 'Messages'],
            ['number' => 7, 'type' => 'Applicant-Notes', 'label' => 'Applicant Notes'],
            ['number' => 8, 'type' => 'Applicant-Pivot-Sales', 'label' => 'Applicant Pivot Sales'],
            ['number' => 9, 'type' => 'Note-Range-Pivot-Sales', 'label' => 'Pivot Notes Sales'],
            ['number' => 10, 'type' => 'Audits', 'label' => 'Audits'],
            ['number' => 11, 'type' => 'CRM-Notes', 'label' => 'CRM Notes'],
            ['number' => 12, 'type' => 'CRM-Rejected-Cv', 'label' => 'CRM Rejected Cv'],
            ['number' => 13, 'type' => 'Cv-Notes', 'label' => 'CV Notes'],
            ['number' => 14, 'type' => 'History', 'label' => 'History'],
            ['number' => 15, 'type' => 'Interview', 'label' => 'Interview'],
            ['number' => 16, 'type' => 'IP-Address', 'label' => 'IP Address'],
            ['number' => 17, 'type' => 'Module-Notes', 'label' => 'Module Notes'],
            ['number' => 18, 'type' => 'Quality-Notes', 'label' => 'Quality Notes'],
            ['number' => 19, 'type' => 'Regions-Notes', 'label' => 'Regions Notes'],
            ['number' => 20, 'type' => 'Revert-Stages', 'label' => 'Revert Stages'],
            ['number' => 21, 'type' => 'Sale-Documents', 'label' => 'Sale Documents'],
            ['number' => 22, 'type' => 'Sale-Notes', 'label' => 'Sale Notes'],
            ['number' => 23, 'type' => 'Sent-Emails', 'label' => 'Sent Emails'],
        ] as $item)
        <div class="col-md-3 col-lg-3 col-sm-12">
            <div class="card bg-light">
                <div class="card-header">
                    <h4 class="card-title">{{ $item['number'] }} - Import {{ $item['label'] }}</h4>
                    <small>You should have a CSV file.</small>
                </div>
                <div class="card-body text-center">
                    <button type="button" class="btn btn-outline-primary btn-lg me-1 my-1 w-50" data-bs-toggle="modal" data-bs-target="#csv{{ $item['type'] }}ImportModal" title="Import CSV">
                        <i class="ri-upload-line"></i> Attach File
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

@foreach ([
        'Users', 'Offices', 'Units', 'CRM-Rejected-Cv', 'IP-Address', 'Regions-Notes',
        'Applicants', 'Sales', 'Messages', 'Cv-Notes', 'Interview', 'Module-Notes', 'Sent-Emails',
        'Applicant-Notes', 'Applicant-Pivot-Sales', 'History', 'Quality-Notes', 'Sale-Notes',
        'Note-Range-Pivot-Sales', 'Audits', 'CRM-Notes', 'Revert-Stages', 'Sale-Documents',
    ] as $type)
<div class="modal fade" id="csv{{ $type }}ImportModal" tabindex="-1" aria-labelledby="csv{{ $type }}ImportLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="csv{{ $type }}ImportForm" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="csv{{ $type }}ImportLabel">Import {{ $type }} CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="csvFile{{ $type }}" class="form-label">Choose CSV File</label>
                        <input type="file" class="form-control" id="csvFile{{ $type }}" name="csv_file" accept=".csv" required>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div id="upload{{ $type }}ProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <div id="processing{{ $type }}Status" class="mt-2 text-muted d-none">Processing CSV...</div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection

@section('script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
@vite(['resources/js/pages/settings.js'])
<script>
    $(document).ready(function () {
        @foreach ([
            'Users' => 'users',
            'Offices' => 'offices',
            'Units' => 'units',
            'Applicants' => 'applicants',
            'Sales' => 'sales',
            'Messages' => 'messages',
            'Applicant-Notes' => 'applicantNotes',
            'Applicant-Pivot-Sales' => 'applicantPivotSale',
            'Note-Range-Pivot-Sales' => 'notesRangeForPivotSale',
            'Audits' => 'audits',
            'CRM-Notes' => 'crmNotes',
            'CRM-Rejected-Cv' => 'crmRejectedCv',
            'Cv-Notes' => 'cvNotes',
            'History' => 'history',
            'Interview' => 'interview',
            'IP-Address' => 'ipAddress',
            'Module-Notes' => 'moduleNotes',
            'Quality-Notes' => 'qualityNotes',
            'Regions-Notes' => 'regions',
            'Revert-Stages' => 'revertStage',
            'Sale-Documents' => 'saleDocuments',
            'Sale-Notes' => 'saleNotes',
            'Sent-Emails' => 'sentEmailData',
        ] as $type => $route)
        $('#csv{{ $type }}ImportForm').on('submit', function (e) {
            e.preventDefault();

            let form = $(this);
            let submitBtn = form.find('button[type="submit"]');
            let progressBar = $('#upload{{ $type }}ProgressBar');
            let processingStatus = $('#processing{{ $type }}Status');
            let formData = new FormData(this);
            let xhr = new XMLHttpRequest();

            submitBtn.prop('disabled', true).text('Uploading...');
            progressBar.removeClass('bg-success bg-danger').addClass('progress-bar-animated');
            processingStatus.addClass('d-none');

            xhr.open('POST', '{{ route($route . ".import") }}', true);
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

            xhr.upload.addEventListener('progress', function (event) {
                if (event.lengthComputable) {
                    let percent = Math.round((event.loaded / event.total) * 100);
                    progressBar.css('width', percent + '%').text(percent + '%');
                    console.log('Uploading {{ $type }}: ' + percent + '%');
                    if (percent === 100) {
                        processingStatus.removeClass('d-none');
                    }
                }
            });

            xhr.onload = function () {
                console.log('Upload response for {{ $type }}:', xhr.status, xhr.responseText);
                submitBtn.prop('disabled', false).text('Upload');

                try {
                    let response = JSON.parse(xhr.responseText);
                    if (xhr.status === 200 || xhr.status === 201) {
                        progressBar.removeClass('progress-bar-animated').addClass('bg-success').text('Upload Complete');
                        toastr.success(response.message || 'CSV import completed successfully.', '{{ $type }} Import');
                        toastr.info(`Successful rows: ${response.successful_rows || 0}, Failed rows: ${response.failed_rows || 0}`);
                        if (response.failed_rows > 0) {
                            toastr.warning('Some rows failed to import. Check logs for details.');
                        }

                        form[0].reset();
                        setTimeout(() => {
                            $('#csv{{ $type }}ImportModal').modal('hide');
                            progressBar.css('width', '0%').removeClass('bg-success bg-danger').text('0%');
                            processingStatus.addClass('d-none');
                        }, 1000);
                    } else {
                        progressBar.removeClass('progress-bar-animated').addClass('bg-danger').text('Upload Failed');
                        toastr.error(response.error || response.message || 'Failed to import CSV.', '{{ $type }} Import');
                    }
                } catch (e) {
                    progressBar.removeClass('progress-bar-animated').addClass('bg-danger').text('Upload Failed');
                    toastr.error('Invalid server response: ' + xhr.responseText, '{{ $type }} Import');
                }
            };

            xhr.onerror = function () {
                console.error('XHR error for {{ $type }}:', xhr.responseText);
                submitBtn.prop('disabled', false).text('Upload');
                progressBar.removeClass('progress-bar-animated').addClass('bg-danger').text('Upload Error');
                processingStatus.addClass('d-none');
                toastr.error('Network error occurred during upload.', '{{ $type }} Import');
            };

            xhr.send(formData);
        });
        @endforeach
    });
</script>
@endsection
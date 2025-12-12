@extends('layouts.vertical', ['title' => 'Compose Email', 'subTitle' => 'Emails'])

{{-- @section('css')
    @vite(['node_modules/quill/dist/quill.snow.css'])
@endsection --}}

@section('content')

    <div class="card">
        <div class="row g-0">
            <div class="shadow-sm rounded-4">
                <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">New Email</h5>
                    {{-- <div>
                        <a href="#" id="export_email" class="btn bg-primary text-white">
                            <i class="icon-cloud-upload"></i> Export Emails
                        </a>
                    </div> --}}
                </div>

                <form id="composeEmailForm">
                    @csrf

                    <div class="card-body" id="composeCard">
                        
                        {{-- To Email --}}
                        <div class="mb-3">
                            <input type="text" class="form-control @error('to_email') is-invalid @enderror" id="toEmail" name="to_email[]" placeholder="To: ">
                            <div class="invalid-feedback">Please provide at least one valid email address.</div>
                        </div>

                        {{-- Subject --}}
                        <div class="mb-3">
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" placeholder="Subject">
                            <div class="invalid-feedback">Please provide a subject.</div>
                        </div>

                        {{-- Body --}}
                        <div class="mb-3">
                            <textarea name="body" id="emailBody" class="summernote form-control @error('body') is-invalid @enderror"></textarea>
                            <div class="invalid-feedback">Please provide the email body.</div>
                        </div>

                        {{-- Buttons --}}
                        <div class="d-flex justify-content-end align-items-center gap-1">
                            {{-- Optional cancel button --}}
                            {{-- <a href="{{ route('emails.inbox') }}" class="btn bg-dark text-white">Cancel</a> --}}
                            <button type="submit" class="btn btn-primary" id="sendEmailBtn">Send</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
@section('script')
    <!-- jQuery CDN (make sure this is loaded before DataTables) -->
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
    
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}">

    <!-- SweetAlert2 CDN -->
    <script src="{{ asset('js/sweetalert2@11.js')}}"></script>

    <!-- Toastr JS -->
    <script src="{{ asset('js/toastr.min.js')}}"></script>

        <!-- Summernote CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">

    <!-- Summernote JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Summernote and set content
            $('.summernote').summernote({
                height: 500,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['fontsize', ['fontsize']],
                    ['color', []],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', []],
                    ['view', []]
                ]
            });
        });
        $("#sendEmailBtn").on("click", function (e) {
            e.preventDefault();

            const email_body = $('#emailBody').val(); // Put into hidden input
            const app_email = $("#toEmail").val().trim();
            const subject = $("#subject").val().trim();

            let isValid = true;

            // Reset validation
            $("#toEmail, #subject, #emailBody").removeClass('is-invalid');

            if (!app_email) {
                $("#toEmail").addClass('is-invalid');
                isValid = false;
            }

            if (!subject) {
                $("#subject").addClass('is-invalid');
                isValid = false;
            }

            if (!email_body || email_body === '<p><br></p>') {
                $("#snow-editor").addClass('is-invalid');
                isValid = false;
            } else {
                $("#snow-editor").removeClass('is-invalid');
            }

            if (!isValid) return;

            const btn = $(this);
            const originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...');

            $.ajax({
                url: "{{ route('emails.saveComposedEmail') }}",
                type: "POST",
                dataType: "json",
                data: {
                    email_body: email_body,
                    app_email: app_email,
                    email_subject: subject,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    $("#toEmail").val('');
                    toastr.success(response.message);

                    window.location.reload();
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON.message || 'Email failed to send.');
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

    </script>
    @endsection

    @section('script-bottom')
        @vite(['resources/js/components/form-quilljs.js'])
    @endsection

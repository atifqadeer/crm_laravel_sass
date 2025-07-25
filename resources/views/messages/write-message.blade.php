@extends('layouts.vertical', ['title' => 'Send Message', 'subTitle' => 'Communication'])

@section('content')
    <div class="card">
        <div class="row g-0">
            <div class="shadow-sm rounded-4">
                <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">New Message</h5>
                </div>

                <form id="composeEmailForm" method="POST" action="">
                    @csrf
                    <div class="card-body" id="composeCard">
                        <div class="mb-3">
                            <label for="">Phone Number</label>
                            <textarea class="form-control" rows="3" id="phone" name="phone[]" placeholder="Please enter comma seperated numbers like 07500000000,07500000000"></textarea>
                            <div class="invalid-feedback">Please provide phone number</div>
                        </div>

                        <div class="mb-3">
                            <label for="">Message</label>
                            <textarea class="form-control" rows="15" name="message" id="message" placeholder="Enter text here..."></textarea>
                            <div class="invalid-feedback">Please type message</div>
                        </div>

                        <div class="d-flex justify-content-end align-items-center gap-1">
                            <a href="{{ route('resources.directIndex') }}" class="btn bg-dark text-white">
                                 Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="sendSMSBtn">Send</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $("#sendSMSBtn").on("click", function (e) {
            e.preventDefault();
            const message = $('#message').val(); // Put into hidden input
            const phone = $("#phone").val().trim();

            let isValid = true;

            // Reset validation
            $("#phone, #message").removeClass('is-invalid');

            if (!phone) {
                $("#phone").addClass('is-invalid');
                isValid = false;
            }

            if (!message) {
                $("#message").addClass('is-invalid');
                isValid = false;
            }

            if (!isValid) return;

            const btn = $(this);
            const originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...');

            $.ajax({
                url: "{{ route('sendMessageToApplicant') }}",
                type: "POST",
                dataType: "json",
                data: {
                    message: message,
                    phone_number: phone,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    $("#phone").val('');
                    $("#message").val('');
                    toastr.success(response.message);
                    // window.location.reload();
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON.message || 'SMS failed to send.');
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

    </script>
    @endsection

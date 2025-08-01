@extends('layouts.vertical', ['title' => 'Settings', 'subTitle' => 'Administrator'])

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .settings-menu .list-group-item {
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .settings-menu .list-group-item.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .settings-form-section {
            display: none !important;
        }
        .settings-form-section.active {
            display: block !important;
        }
        .card-body {
            min-height: 80vh;
        }
        .smtp-entry {
            position: relative;
        }
        .remove-smtp-btn {
            display: none;
        }
        .smtp-entry:not(:first-child) .remove-smtp-btn {
            display: block;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Left Menu Column -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Settings Menu</h5>
                </div>
                <div class="list-group list-group-flush settings-menu" id="settings-menu">
                    <button class="list-group-item list-group-item-action active" data-target="#form-general" type="button" id="menu-general" aria-controls="form-general">General Settings</button>
                    <button class="list-group-item list-group-item-action" data-target="#form-profile" type="button" id="menu-profile" aria-controls="form-profile">Profile Settings</button>
                    <button class="list-group-item list-group-item-action" data-target="#form-notifications" type="button" id="menu-notifications" aria-controls="form-notifications">Notification Settings</button>
                    <button class="list-group-item list-group-item-action" data-target="#form-sms" type="button" id="menu-sms" aria-controls="form-sms">SMS Settings</button>
                    <button class="list-group-item list-group-item-action" data-target="#form-smtp" type="button" id="menu-smtp" aria-controls="form-smtp">SMTP Settings</button>
                </div>
            </div>
        </div>
        <!-- Right Forms Column -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title" id="form-title">General Settings</h4>
                </div>
                <div class="card-body">
                    <!-- General Settings Form -->
                    <section id="form-general" class="settings-form-section active">
                        <form id="generalSettingsForm" data-type="general">
                            @csrf
                            <div class="mb-3">
                                <label for="site_name" class="form-label">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="{{ old('site_name') }}">
                            </div>
                            <button type="submit" class="btn btn-primary">Save General</button>
                        </form>
                    </section>
                    <!-- Profile Settings Form -->
                    <section id="form-profile" class="settings-form-section">
                        <form id="profileSettingsForm" data-type="profile">
                            @csrf
                            <div class="mb-3">
                                <label for="user_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="user_email" name="user_email" value="{{ old('user_email') }}">
                            </div>
                            <div class="mb-3">
                                <label for="user_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="user_name" name="user_name" value="{{ old('user_name') }}">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Profile</button>
                        </form>
                    </section>
                    <!-- Notification Settings Form -->
                    <section id="form-notifications" class="settings-form-section">
                        <form id="notificationSettingsForm" data-type="notification">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Enable Email Notifications</label>
                                <select class="form-select" name="email_notifications" id="email_notifications">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Enable SMS Notifications</label>
                                <select class="form-select" name="sms_notifications" id="sms_notifications">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Notifications</button>
                        </form>
                    </section>
                    <!-- SMS Settings Form -->
                    <section id="form-sms" class="settings-form-section">
                        <form id="smsSettingsForm" data-type="sms">
                            @csrf
                            <div class="mb-3">
                                <label for="sms_provider" class="form-label">SMS Provider</label>
                                <input type="text" class="form-control" id="sms_provider" name="sms_provider" value="{{ old('sms_provider') }}">
                            </div>
                            <div class="mb-3">
                                <label for="sms_api_key" class="form-label">SMS API Key</label>
                                <input type="text" class="form-control" id="sms_api_key" name="sms_api_key" value="{{ old('sms_api_key') }}">
                            </div>
                            <button type="submit" class="btn btn-primary">Save SMS Settings</button>
                        </form>
                    </section>
                    <!-- SMTP Settings Form -->
                    <section id="form-smtp" class="settings-form-section">
                        <form id="smtpSettingsForm" data-type="smtp">
                            @csrf
                            <div id="smtp-entries">
                                <!-- Initial SMTP Entry Group -->
                                <div class="smtp-entry border rounded p-3 mb-3 position-relative">
                                    <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2 remove-smtp-btn" aria-label="Remove SMTP Entry"></button>
                                    <input type="hidden" name="smtp[0][id]" class="smtp-id">
                                    <div class="mb-3">
                                        <label class="form-label">Mailer</label>
                                        <input type="text" class="form-control" name="smtp[0][mailer]" placeholder="e.g., smtp">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" name="smtp[0][host]" placeholder="e.g., smtp.mailtrap.io">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" name="smtp[0][port]" placeholder="e.g., 587">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" name="smtp[0][username]">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" name="smtp[0][password]">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Encryption</label>
                                        <select class="form-select" name="smtp[0][encryption]">
                                            <option value="">Select Encryption</option>
                                            <option value="tls">TLS</option>
                                            <option value="ssl">SSL</option>
                                            <option value="null">None</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">From Email</label>
                                        <input type="email" class="form-control" name="smtp[0][from_address]">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">From Name</label>
                                        <input type="text" class="form-control" name="smtp[0][from_name]">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <button type="button" class="btn btn-secondary" id="addSmtpBtn">+ Add More SMTP</button>
                                    <button type="button" class="btn btn-danger d-none" id="removeSmtpBtn">− Remove Last SMTP</button>
                                </div>
                                <button type="submit" class="btn btn-primary">Save SMTP Settings</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
@vite(['resources/js/pages/settings.js'])
<script>
$(document).ready(function() {
    // Ensure jQuery is loaded
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded.');
        return;
    }

    const $menuButtons = $('#settings-menu button');
    const $formSections = $('.settings-form-section');
    const $formTitle = $('#form-title');
    let smtpIndex = 1;

    // Store the initial SMTP entry template
    const $smtpTemplate = $('.smtp-entry').first().clone();

    // Debugging: Log available sections and initial state
    console.log('Available form sections:', $formSections.length, $formSections);
    console.log('Initial active section:', $('.settings-form-section.active').attr('id'));

    // Ensure only General Settings is visible on page load
    $formSections.removeClass('active').css('display', 'none');
    $('#form-general').addClass('active').css('display', 'block');
    $formTitle.text('General Settings');

    // Load existing settings
    $.ajax({
        url: '{{ route("settings.get") }}',
        method: 'GET',
        success: function(data) {
            console.log('Settings data:', data); // Debug: Log full response
            // General Settings
            $('#site_name').val(data['general.site_name'] || '');
            
            // Profile Settings
            $('#user_email').val(data['profile.user_email'] || '');
            $('#user_name').val(data['profile.user_name'] || '');
            
            // Notification Settings
            $('#email_notifications').val(data['notification.email_notifications'] || '0');
            $('#sms_notifications').val(data['notification.sms_notifications'] || '0');
            
            // SMS Settings
            $('#sms_provider').val(data['sms.sms_provider'] || '');
            $('#sms_api_key').val(data['sms.sms_api_key'] || '');
            
            // SMTP Settings
            if (data.smtp && Array.isArray(data.smtp) && data.smtp.length > 0) {
                console.log('Populating SMTP settings:', data.smtp); // Debug: Log SMTP data
                $('#smtp-entries').empty(); // Clear existing entries
                data.smtp.forEach((setting, index) => {
                    console.log('Creating entry for index:', index, setting); // Debug: Log each entry
                    const $entry = $smtpTemplate.clone();
                    $entry.find('input[name="smtp[0][id]"]').val(setting.id || '').attr('name', `smtp[${index}][id]`);
                    $entry.find('input[name="smtp[0][mailer]"]').val(setting.mailer || '').attr('name', `smtp[${index}][mailer]`);
                    $entry.find('input[name="smtp[0][host]"]').val(setting.host || '').attr('name', `smtp[${index}][host]`);
                    $entry.find('input[name="smtp[0][port]"]').val(setting.port || '').attr('name', `smtp[${index}][port]`);
                    $entry.find('input[name="smtp[0][username]"]').val(setting.username || '').attr('name', `smtp[${index}][username]`);
                    $entry.find('input[name="smtp[0][password]"]').val(setting.password || '').attr('name', `smtp[${index}][password]`);
                    $entry.find('select[name="smtp[0][encryption]"]').val(setting.encryption || '').attr('name', `smtp[${index}][encryption]`);
                    $entry.find('input[name="smtp[0][from_address]"]').val(setting.from_address || '').attr('name', `smtp[${index}][from_address]`);
                    $entry.find('input[name="smtp[0][from_name]"]').val(setting.from_name || '').attr('name', `smtp[${index}][from_name]`);
                    $('#smtp-entries').append($entry);
                });
                smtpIndex = data.smtp.length;
            } else {
                console.warn('No SMTP settings found or invalid format:', data.smtp);
                $('#smtp-entries').empty().append($smtpTemplate.clone());
            }
            toggleRemoveButton();
        },
        error: function(xhr) {
            console.error('Error loading settings:', xhr.responseText);
            toastr.error('Failed to load settings.');
        }
    });

    // Handle menu button clicks
    $menuButtons.on('click', function(e) {
        e.preventDefault();
        const $this = $(this);
        const target = $this.data('target');

        console.log('Button clicked:', $this.text(), 'Target:', target);

        $menuButtons.removeClass('active');
        $this.addClass('active');

        $formSections.removeClass('active').css('display', 'none');
        const $targetSection = $(target);
        if ($targetSection.length) {
            $targetSection.addClass('active').css('display', 'block');
            console.log('Target section activated:', target);
        } else {
            console.error('Target section not found:', target);
        }

        $formTitle.text($this.text());
    });

    // Add new SMTP entry
    $('#addSmtpBtn').on('click', function() {
        const $newEntry = $smtpTemplate.clone();
        $newEntry.find('input, select').each(function() {
            const name = $(this).attr('name').replace('[0]', `[${smtpIndex}]`);
            $(this).attr('name', name).val('');
        });
        $('#smtp-entries').append($newEntry);
        smtpIndex++;
        toggleRemoveButton();
    });

    // Remove SMTP entry
    $(document).on('click', '.remove-smtp-btn', function() {
        const $entry = $(this).closest('.smtp-entry');
        const id = $entry.find('input[name$="[id]"]').val();

        if (id && id !== '') {
            $.ajax({
                url: '{{ route("settings.smtp.delete") }}',
                method: 'POST',
                data: {
                    id: id,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    toastr.success(response.message);
                    $entry.remove();
                    smtpIndex--;
                    toggleRemoveButton();
                },
                error: function(xhr) {
                    console.error('Error deleting SMTP setting:', xhr.responseText);
                    toastr.error('Failed to delete SMTP setting.');
                }
            });
        } else {
            $entry.remove();
            smtpIndex--;
            toggleRemoveButton();
        }
    });

    // Remove last SMTP entry
    $('#removeSmtpBtn').on('click', function() {
        if ($('.smtp-entry').length > 1) {
            const $lastEntry = $('.smtp-entry').last();
            const id = $lastEntry.find('input[name$="[id]"]').val();
            if (id && id !== '') {
                $.ajax({
                    url: '{{ route("settings.smtp.delete") }}',
                    method: 'POST',
                    data: {
                        id: id,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        toastr.success(response.message);
                        $lastEntry.remove();
                        smtpIndex--;
                        toggleRemoveButton();
                    },
                    error: function(xhr) {
                        console.error('Error deleting SMTP setting:', xhr.responseText);
                        toastr.error('Failed to delete SMTP setting.');
                    }
                });
            } else {
                $lastEntry.remove();
                smtpIndex--;
                toggleRemoveButton();
            }
        }
    });

    // Toggle remove button visibility
    function toggleRemoveButton() {
        $('.remove-smtp-btn').toggleClass('d-none', $('.smtp-entry').length <= 1);
        $('#removeSmtpBtn').toggleClass('d-none', $('.smtp-entry').length <= 1);
    }

    // Handle form submissions
    $formSections.find('form').submit(function(e) {
        e.preventDefault();
        const $form = $(this);
        const formType = $form.data('type');

        if (formType === 'smtp') {
            const formData = new FormData(this);
            // Debug: Log form data
            const formDataEntries = {};
            for (let [key, value] of formData.entries()) {
                formDataEntries[key] = value;
            }
            console.log('Submitting SMTP form data:', formDataEntries);

            $.ajax({
                url: '{{ route("settings.smtp.save") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        // Reload SMTP entries to update IDs
                        $.ajax({
                            url: '{{ route("settings.get") }}',
                            method: 'GET',
                            success: function(data) {
                                console.log('Reloaded settings:', data);
                                if (data.smtp && Array.isArray(data.smtp)) {
                                    $('#smtp-entries').empty();
                                    data.smtp.forEach((setting, index) => {
                                        const $entry = $smtpTemplate.clone();
                                        $entry.find('input[name="smtp[0][id]"]').val(setting.id || '').attr('name', `smtp[${index}][id]`);
                                        $entry.find('input[name="smtp[0][mailer]"]').val(setting.mailer || '').attr('name', `smtp[${index}][mailer]`);
                                        $entry.find('input[name="smtp[0][host]"]').val(setting.host || '').attr('name', `smtp[${index}][host]`);
                                        $entry.find('input[name="smtp[0][port]"]').val(setting.port || '').attr('name', `smtp[${index}][port]`);
                                        $entry.find('input[name="smtp[0][username]"]').val(setting.username || '').attr('name', `smtp[${index}][username]`);
                                        $entry.find('input[name="smtp[0][password]"]').val(setting.password || '').attr('name', `smtp[${index}][password]`);
                                        $entry.find('select[name="smtp[0][encryption]"]').val(setting.encryption || '').attr('name', `smtp[${index}][encryption]`);
                                        $entry.find('input[name="smtp[0][from_address]"]').val(setting.from_address || '').attr('name', `smtp[${index}][from_address]`);
                                        $entry.find('input[name="smtp[0][from_name]"]').val(setting.from_name || '').attr('name', `smtp[${index}][from_name]`);
                                        $('#smtp-entries').append($entry);
                                    });
                                    smtpIndex = data.smtp.length;
                                    toggleRemoveButton();
                                }
                            },
                            error: function(xhr) {
                                console.error('Error reloading settings:', xhr.responseText);
                            }
                        });
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Error saving SMTP settings:', xhr.responseText);
                    toastr.error('Failed to save SMTP settings: ' + (xhr.responseJSON?.error || 'Unknown error'));
                }
            });
        } else {
            $.ajax({
                url: '{{ route("settings.save") }}',
                method: 'POST',
                data: $form.serialize() + '&form_type=' + formType,
                success: function(response) {
                    toastr.success(response.message);
                },
                error: function(xhr) {
                    console.error('Error saving settings:', xhr.responseText);
                    toastr.error('Failed to save settings.');
                }
            });
        }
    });
});
</script>
@endsection
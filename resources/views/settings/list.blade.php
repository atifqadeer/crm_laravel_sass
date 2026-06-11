@extends('layouts.vertical', ['title' => 'Settings', 'subTitle' => 'Administrator'])

@section('style')
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
                        <button class="list-group-item list-group-item-action active" data-target="#form-general"
                            type="button" id="menu-general" aria-controls="form-general">General Settings</button>
                        <button class="list-group-item list-group-item-action" data-target="#form-profile" type="button"
                            id="menu-profile" aria-controls="form-profile">Profile Settings</button>
                        <button class="list-group-item list-group-item-action" data-target="#form-google-maps"
                            type="button" id="menu-profile" aria-controls="form-google-maps">Google Maps Settings</button>
                        <button class="list-group-item list-group-item-action" data-target="#form-notifications"
                            type="button" id="menu-notifications" aria-controls="form-notifications">Notification
                            Settings</button>
                        <button class="list-group-item list-group-item-action" data-target="#form-sms" type="button"
                            id="menu-sms" aria-controls="form-sms">SMS Settings</button>
                        <button class="list-group-item list-group-item-action" data-target="#form-smtp" type="button"
                            id="menu-smtp" aria-controls="form-smtp">SMTP Settings</button>
                        <button class="list-group-item list-group-item-action" data-target="#form-dialing" type="button"
                            id="menu-dialing" aria-controls="form-dialing">Dial Lock Settings</button>

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
                                    <input type="text" class="form-control" id="site_name" name="site_name"
                                        value="{{ old('site_name') }}">
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success">Save General</button>
                                </div>
                            </form>
                        </section>
                        <!-- Profile Settings Form -->
                        <section id="form-profile" class="settings-form-section">
                            <form id="profileSettingsForm" data-type="profile">
                                @csrf
                                <div class="mb-3">
                                    <label for="user_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="user_email" name="user_email"
                                        value="{{ old('user_email') }}">
                                </div>
                                <div class="mb-3">
                                    <label for="user_name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="user_name" name="user_name"
                                        value="{{ old('user_name') }}">
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success">Save Profile</button>
                                </div>
                            </form>
                        </section>
                        <!-- Google Maps Settings Form -->
                        <section id="form-google-maps" class="settings-form-section">
                            <form id="googleMapsSettingsForm" data-type="google_maps">
                                @csrf
                                <div class="mb-3">
                                    <label for="google_api_url" class="form-label">API URL</label>
                                    <input type="text" class="form-control" id="google_api_url" name="google_api_url"
                                        value="{{ old('google_api_url') }}">
                                </div>
                                <div class="mb-3">
                                    <label for="google_api_key" class="form-label">API Key</label>
                                    <input type="text" class="form-control" id="google_api_key" name="google_api_key"
                                        value="{{ old('google_api_key') }}">
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success">Save</button>
                                </div>
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
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success">Save Notifications</button>
                                </div>
                            </form>
                        </section>
                        <!-- SMS Settings Form -->
                        <section id="form-sms" class="settings-form-section">
                            <form id="smsSettingsForm" data-type="sms">
                                @csrf
                                <div class="mb-3">
                                    <label for="sms_api_url" class="form-label">SMS API URL</label>
                                    <input type="text" class="form-control" id="sms_api_url" name="sms_api_url"
                                        value="{{ old('sms_api_url') }}">
                                </div>
                                <div class="mb-3">
                                    <label for="sms_port" class="form-label">SMS Port</label>
                                    <input type="text" class="form-control" id="sms_port" name="sms_port"
                                        value="{{ old('sms_port') }}">
                                </div>
                                <div class="mb-3">
                                    <label for="sms_username" class="form-label">SMS Username</label>
                                    <input type="text" class="form-control" id="sms_username" name="sms_username"
                                        value="{{ old('sms_username') }}">
                                </div>
                                <div class="mb-3">
                                    <label for="sms_password" class="form-label">SMS Password</label>
                                    <input type="text" class="form-control" id="sms_password" name="sms_password"
                                        value="{{ old('sms_password') }}">
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success">Save SMS Settings</button>
                                </div>
                            </form>
                        </section>
                        <!-- SMTP Settings Form -->
                        <section id="form-smtp" class="settings-form-section">
                            <form id="smtpSettingsForm" data-type="smtp">
                                @csrf
                                <div id="smtp-entries">
                                    <!-- Initial SMTP Entry Group -->
                                    <div class="smtp-entry border rounded p-3 mb-3 position-relative">
                                        <button type="button"
                                            class="btn-close position-absolute top-0 end-0 mt-2 me-2 remove-smtp-btn"
                                            aria-label="Remove SMTP Entry"></button>
                                        <input type="hidden" name="smtp[0][id]" class="smtp-id">
                                        <div class="mb-3">
                                            <label class="form-label">Mailer</label>
                                            <input type="text" class="form-control" name="smtp[0][mailer]"
                                                placeholder="e.g., smtp">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Host</label>
                                            <input type="text" class="form-control" name="smtp[0][host]"
                                                placeholder="e.g., smtp.mailtrap.io">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Port</label>
                                            <input type="number" class="form-control" name="smtp[0][port]"
                                                placeholder="e.g., 587">
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
                                        <button type="button" class="btn btn-secondary" id="addSmtpBtn">+ Add More
                                            SMTP</button>
                                        <button type="button" class="btn btn-danger d-none" id="removeSmtpBtn">− Remove
                                            Last SMTP</button>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-success">Save SMTP Settings</button>
                                    </div>
                                </div>
                            </form>
                        </section>
                        <!-- Dial Lock Settings -->
                        <section id="form-dialing" class="settings-form-section">

                            {{-- Stats row --}}
                            <div class="d-flex gap-3 flex-wrap mb-4" id="dial-stats-row">
                                <div class="stat-pill bg-primary">
                                    <div class="fs-4 fw-bold" id="stat-active-locks">–</div>
                                    <small>Active Locks</small>
                                </div>
                                <div class="stat-pill bg-success">
                                    <div class="fs-4 fw-bold" id="stat-calls-today">–</div>
                                    <small>Calls Today</small>
                                </div>
                            </div>

                            <form id="dialingSettingsForm" data-type="dialing">
                                @csrf

                                {{-- Master toggle --}}
                                <div class="d-flex align-items-center gap-3 mb-4 p-3 border rounded bg-light">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            id="dialing_lock_enabled" name="dialing_lock_enabled"
                                            style="width:3em;height:1.5em" checked>
                                        <label class="form-check-label fw-bold fs-6 ms-2" for="dialing_lock_enabled">
                                            Dial Lock System
                                        </label>
                                    </div>
                                    <span id="dial-lock-status-badge"
                                        class="badge bg-success px-3 py-2 fs-6">Enabled</span>
                                    <small class="text-muted ms-auto">When disabled, any agent can dial any number at any
                                        time.</small>
                                </div>

                                {{-- Timer controls --}}
                                <div id="dial-lock-controls" class="row g-4 mb-4">

                                    {{-- Same agent --}}
                                    <div class="col-md-6">
                                        <div class="card dial-timer-card border-warning h-100">
                                            <div
                                                class="card-header bg-warning bg-opacity-10 d-flex align-items-center gap-2">
                                                <span style="font-size:1.2rem">🔒</span>
                                                <div>
                                                    <div class="fw-bold">Same Agent Re-dial Lock</div>
                                                    <small class="text-muted">How long the dialling agent is blocked from
                                                        re-calling the same number</small>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex align-items-center gap-3 mb-3">
                                                    <input type="range" class="form-range flex-grow-1"
                                                        id="same_user_slider" min="0" max="60"
                                                        step="1" value="0">
                                                    <div class="input-group" style="width:110px">
                                                        <input type="number" class="form-control text-center fw-bold"
                                                            id="dialing_lock_same_user_minutes"
                                                            name="dialing_lock_same_user_minutes" min="0"
                                                            max="60" value="0">
                                                        <span class="input-group-text">min</span>
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <span id="same-user-preview"
                                                        class="badge dial-preview-badge bg-secondary px-3 py-2">
                                                        Same agent: can re-dial immediately
                                                    </span>
                                                </div>
                                                <div class="mt-2 text-center">
                                                    <small class="text-muted">Set to <strong>0</strong> to let the same
                                                        agent re-dial without any wait.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Other agents --}}
                                    <div class="col-md-6">
                                        <div class="card dial-timer-card border-danger h-100">
                                            <div
                                                class="card-header bg-danger bg-opacity-10 d-flex align-items-center gap-2">
                                                <span style="font-size:1.2rem">🚫</span>
                                                <div>
                                                    <div class="fw-bold">Other Agents Lock</div>
                                                    <small class="text-muted">How long all other agents are blocked from
                                                        calling a number in use</small>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex align-items-center gap-3 mb-3">
                                                    <input type="range" class="form-range flex-grow-1"
                                                        id="other_user_slider" min="1" max="60"
                                                        step="1" value="5">
                                                    <div class="input-group" style="width:110px">
                                                        <input type="number" class="form-control text-center fw-bold"
                                                            id="dialing_lock_other_user_minutes"
                                                            name="dialing_lock_other_user_minutes" min="1"
                                                            max="60" value="5">
                                                        <span class="input-group-text">min</span>
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <span id="other-user-preview"
                                                        class="badge dial-preview-badge bg-danger px-3 py-2">
                                                        Other agents: locked for 5 min
                                                    </span>
                                                </div>
                                                <div class="mt-2 text-center">
                                                    <small class="text-muted">Minimum <strong>1 min</strong>. Recommended:
                                                        3–10 min.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <button type="button" class="btn btn-outline-danger" id="clearAllLocksBtn">
                                        Clear All Active Locks
                                    </button>
                                    <button type="submit" class="btn btn-success px-4">Save Dial Lock Settings</button>
                                </div>
                            </form>

                            {{-- Active Locks Live Table --}}
                            <div class="border rounded p-3 bg-light">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0 fw-bold">
                                        Active Locks
                                        <span class="badge bg-danger ms-1" id="locks-count-badge">0</span>
                                    </h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted" style="font-size:.8rem" id="locks-last-refresh"></span>
                                        <span class="badge bg-secondary" style="font-size:.75rem">Live · refreshes every
                                            5s</span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" id="active-locks-table">
                                        <thead class="table-grey">
                                            <tr>
                                                <th>Number</th>
                                                <th>Agent</th>
                                                <th>Locked At</th>
                                                <th>Expires In</th>
                                                <th>Total Calls</th>
                                                <th class="text-end">Release</th>
                                            </tr>
                                        </thead>
                                        <tbody id="active-locks-body">
                                            <tr id="no-locks-row">
                                                <td colspan="6" class="text-center text-muted py-3">No active locks
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <!-- jQuery CDN (make sure this is loaded before DataTables) -->
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>

    <!-- DataTables CSS (for styling the table) -->
    <link rel="stylesheet" href="{{ asset('css/jquery.dataTables.min.css') }}">

    <!-- DataTables JS (for the table functionality) -->
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}">

    <!-- SweetAlert2 CDN -->
    <script src="{{ asset('js/sweetalert2@11.js') }}"></script>

    <!-- Toastr JS -->
    <script src="{{ asset('js/toastr.min.js') }}"></script>

    <!-- Moment JS -->
    <script src="{{ asset('js/moment.min.js') }}"></script>

    {{-- @vite(['resources/js/pages/settings.js']) --}}

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
                url: '{{ route('settings.get') }}',
                method: 'GET',
                success: function(data) {
                    console.log('Settings data:', data); // Debug: Log full response

                    // General Settings
                    if (data.general) {
                        $('#site_name').val(data.general.site_name || '');
                    }

                    // Profile Settings
                    if (data.profile) {
                        $('#user_email').val(data.profile.user_email || '');
                        $('#user_name').val(data.profile.user_name || '');
                    }

                    // Google Settings
                    if (data.google_maps) {
                        $('#google_api_url').val(data.google_maps.google_map_api_url || '');
                        $('#google_api_key').val(data.google_maps.google_map_api_key || '');
                    }

                    // Notification Settings
                    if (data.notifications) {
                        $('#email_notifications').val(data.notifications.email_notifications ? '1' :
                            '0');
                        $('#sms_notifications').val(data.notifications.sms_notifications ? '1' : '0');
                    }

                    // SMS Settings
                    if (data.sms) {
                        $('#sms_api_url').val(data.sms.sms_api_url || '');
                        $('#sms_port').val(data.sms.sms_port || '');
                        $('#sms_username').val(data.sms.sms_username || '');
                        $('#sms_password').val(data.sms.sms_password || '');
                    }

                    // SMTP Settings
                    if (data.smtp && Array.isArray(data.smtp) && data.smtp.length > 0) {
                        console.log('Populating SMTP settings:', data.smtp);
                        $('#smtp-entries').empty(); // Clear existing entries
                        data.smtp.forEach((setting, index) => {
                            const $entry = $smtpTemplate.clone();
                            $entry.find('input[name="smtp[0][id]"]').val(setting.id || '').attr(
                                'name', `smtp[${index}][id]`);
                            $entry.find('input[name="smtp[0][mailer]"]').val(setting.mailer ||
                                '').attr('name', `smtp[${index}][mailer]`);
                            $entry.find('input[name="smtp[0][host]"]').val(setting.host || '')
                                .attr('name', `smtp[${index}][host]`);
                            $entry.find('input[name="smtp[0][port]"]').val(setting.port || '')
                                .attr('name', `smtp[${index}][port]`);
                            $entry.find('input[name="smtp[0][username]"]').val(setting
                                .username || '').attr('name', `smtp[${index}][username]`);
                            $entry.find('input[name="smtp[0][password]"]').val(setting
                                .password || '').attr('name', `smtp[${index}][password]`);
                            $entry.find('select[name="smtp[0][encryption]"]').val(setting
                                .encryption || '').attr('name',
                                `smtp[${index}][encryption]`);
                            $entry.find('input[name="smtp[0][from_address]"]').val(setting
                                .from_address || '').attr('name',
                                `smtp[${index}][from_address]`);
                            $entry.find('input[name="smtp[0][from_name]"]').val(setting
                                .from_name || '').attr('name', `smtp[${index}][from_name]`);
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

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This SMTP setting will be deleted permanently.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (id && id !== '') {
                            $.ajax({
                                url: '{{ route('settings.smtp.delete') }}',
                                method: 'POST',
                                data: {
                                    id: id,
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(response) {
                                    $entry.remove();
                                    smtpIndex--;
                                    toggleRemoveButton();

                                    Swal.fire(
                                        'Deleted!',
                                        'SMTP setting has been deleted.',
                                        'success'
                                    );
                                },
                                error: function(xhr) {
                                    console.error('Error deleting SMTP setting:', xhr
                                        .responseText);
                                    toastr.error('Failed to delete SMTP setting.');
                                }
                            });
                        } else {
                            $entry.remove();
                            smtpIndex--;
                            toggleRemoveButton();

                            Swal.fire(
                                'Deleted!',
                                'SMTP setting has been deleted.',
                                'success'
                            );
                        }
                    }
                });
            });

            // Remove last SMTP entry
            $('#removeSmtpBtn').on('click', function() {
                if ($('.smtp-entry').length > 1) {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This SMTP setting will be deleted permanently.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const $lastEntry = $('.smtp-entry').last();
                            const id = $lastEntry.find('input[name$="[id]"]').val();

                            if (id && id !== '') {
                                $.ajax({
                                    url: '{{ route('settings.smtp.delete') }}',
                                    method: 'POST',
                                    data: {
                                        id: id,
                                        _token: $('meta[name="csrf-token"]').attr('content')
                                    },
                                    success: function(response) {
                                        $lastEntry.remove();
                                        smtpIndex--;
                                        toggleRemoveButton();

                                        Swal.fire(
                                            'Deleted!',
                                            'SMTP setting has been deleted.',
                                            'success'
                                        );
                                    },
                                    error: function(xhr) {
                                        console.error('Error deleting SMTP setting:',
                                            xhr.responseText);
                                        toastr.error('Failed to delete SMTP setting.');
                                    }
                                });
                            } else {
                                $lastEntry.remove();
                                smtpIndex--;
                                toggleRemoveButton();

                                Swal.fire(
                                    'Deleted!',
                                    'SMTP setting has been deleted.',
                                    'success'
                                );
                            }
                        }
                    });
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

                // get the actual submit button inside the form
                const $btn = $form.find('[type="submit"]');
                const originalText = $btn.html();

                if (formType === 'smtp') {
                    const formData = new FormData(this);

                    // disable + show loader
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...'
                    );

                    $.ajax({
                        url: '{{ route('settings.smtp.save') }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // ... (reload SMTP entries code remains same)
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error saving SMTP settings:', xhr.responseText);
                            toastr.error('Failed to save SMTP settings: ' + (xhr.responseJSON
                                ?.error || 'Unknown error'));
                        },
                        complete: function() {
                            // restore button
                            $btn.prop('disabled', false).html(originalText);
                        }
                    });
                } else if (formType === 'general') {
                    const formData = new FormData(this);

                    // disable + show loader
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...'
                    );

                    $.ajax({
                        url: '{{ route('settings.general.save') }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // ... (reload SMTP entries code remains same)
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error saving general settings:', xhr.responseText);
                            toastr.error('Failed to save general settings: ' + (xhr.responseJSON
                                ?.error || 'Unknown error'));
                        },
                        complete: function() {
                            // restore button
                            $btn.prop('disabled', false).html(originalText);
                        }
                    });
                } else if (formType === 'profile') {
                    const formData = new FormData(this);

                    // disable + show loader
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...'
                    );

                    $.ajax({
                        url: '{{ route('settings.profile.save') }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // ... (reload SMTP entries code remains same)
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error saving profile settings:', xhr.responseText);
                            toastr.error('Failed to save profile settings: ' + (xhr.responseJSON
                                ?.error || 'Unknown error'));
                        },
                        complete: function() {
                            // restore button
                            $btn.prop('disabled', false).html(originalText);
                        }
                    });
                } else if (formType === 'google_maps') {
                    const formData = new FormData(this);

                    // disable + show loader
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...'
                    );

                    $.ajax({
                        url: '{{ route('settings.google.save') }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // ... (reload SMTP entries code remains same)
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error saving Google Map settings:', xhr
                                .responseText);
                            toastr.error('Failed to save Google Map settings: ' + (xhr
                                .responseJSON?.error || 'Unknown error'));
                        },
                        complete: function() {
                            // restore button
                            $btn.prop('disabled', false).html(originalText);
                        }
                    });
                } else if (formType === 'notification') {
                    const formData = new FormData(this);

                    // disable + show loader
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...'
                    );

                    $.ajax({
                        url: '{{ route('settings.notification.save') }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // ... (reload SMTP entries code remains same)
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error saving notifications settings:', xhr
                                .responseText);
                            toastr.error('Failed to save notifications settings: ' + (xhr
                                .responseJSON?.error || 'Unknown error'));
                        },
                        complete: function() {
                            // restore button
                            $btn.prop('disabled', false).html(originalText);
                        }
                    });
                } else if (formType === 'sms') {
                    const formData = new FormData(this);

                    // disable + show loader
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...'
                    );

                    $.ajax({
                        url: '{{ route('settings.sms.save') }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // ... (reload SMTP entries code remains same)
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error saving sms settings:', xhr.responseText);
                            toastr.error('Failed to save sms settings: ' + (xhr.responseJSON
                                ?.error || 'Unknown error'));
                        },
                        complete: function() {
                            // restore button
                            $btn.prop('disabled', false).html(originalText);
                        }
                    });
                    // } else {
                    //     // disable + show loader
                    //     $btn.prop('disabled', true).html(
                    //         '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...'
                    //     );

                    //     $.ajax({
                    //         url: '{{ route('settings.save') }}',
                    //         method: 'POST',
                    //         data: $form.serialize() + '&form_type=' + formType,
                    //         success: function(response) {
                    //             toastr.success(response.message);
                    //         },
                    //         error: function(xhr) {
                    //             console.error('Error saving settings:', xhr.responseText);
                    //             toastr.error('Failed to save settings.');
                    //         },
                    //         complete: function() {
                    //             // restore button
                    //             $btn.prop('disabled', false).html(originalText);
                    //         }
                    //     });
                }
            });
            // ── Dial Lock Settings ──────────────────────────────────────────────────

            var dialLocksRefreshTimer = null;
            var dialCountdownTimer = null;

            // Sync slider ↔ number input and update preview badge
            function bindDialSlider(sliderId, inputId, previewId, isSameUser) {
                var $slider = $('#' + sliderId);
                var $input = $('#' + inputId);
                var $preview = $('#' + previewId);

                function updatePreview(val) {
                    val = parseInt(val, 10);
                    if (isSameUser) {
                        if (val === 0) {
                            $preview.removeClass('bg-warning bg-danger').addClass('bg-secondary')
                                .text('Same agent: can re-dial immediately');
                        } else {
                            $preview.removeClass('bg-secondary bg-danger').addClass('bg-warning text-dark')
                                .text('Same agent: locked for ' + val + ' min' + (val === 1 ? '' : 's'));
                        }
                    } else {
                        $preview.text('Other agents: locked for ' + val + ' min' + (val === 1 ? '' : 's'));
                    }
                }

                $slider.on('input', function() {
                    var v = $(this).val();
                    $input.val(v);
                    updatePreview(v);
                });

                $input.on('input change', function() {
                    var min = parseInt($(this).attr('min'), 10);
                    var max = parseInt($(this).attr('max'), 10);
                    var v = Math.min(max, Math.max(min, parseInt($(this).val(), 10) || min));
                    $(this).val(v);
                    $slider.val(v);
                    updatePreview(v);
                });
            }

            bindDialSlider('same_user_slider', 'dialing_lock_same_user_minutes', 'same-user-preview', true);
            bindDialSlider('other_user_slider', 'dialing_lock_other_user_minutes', 'other-user-preview', false);

            // Master toggle badge
            $('#dialing_lock_enabled').on('change', function() {
                var on = $(this).is(':checked');
                $('#dial-lock-status-badge')
                    .removeClass('bg-success bg-secondary')
                    .addClass(on ? 'bg-success' : 'bg-secondary')
                    .text(on ? 'Enabled' : 'Disabled');
                $('#dial-lock-controls').toggleClass('opacity-50 pe-none', !on);
            });

            // Load dialing values from the getSettings response
            // (called after the main AJAX succeeds — we hook in via a custom event)
            $(document).on('dialingSettingsLoaded', function(e, data) {
                if (!data) return;
                var enabled = data.dialing_lock_enabled !== false && data.dialing_lock_enabled !==
                    'false' && data.dialing_lock_enabled !== 0;
                var sameMin = parseInt(data.dialing_lock_same_user_minutes, 10) || 0;
                var otherMin = parseInt(data.dialing_lock_other_user_minutes, 10) || 5;

                $('#dialing_lock_enabled').prop('checked', enabled).trigger('change');
                $('#dialing_lock_same_user_minutes').val(sameMin).trigger('change');
                $('#same_user_slider').val(sameMin);
                $('#dialing_lock_other_user_minutes').val(otherMin).trigger('change');
                $('#other_user_slider').val(otherMin);
                $('#same-user-preview').trigger('updatePreview');
            });

            // ── Active locks table ──────────────────────────────────────────────

            function formatCountdown(seconds) {
                if (seconds <= 0) return '<span class="text-muted">Expiring…</span>';
                var m = Math.floor(seconds / 60);
                var s = seconds % 60;
                var color = seconds <= 30 ? 'text-danger' : seconds <= 60 ? 'text-warning' : 'text-success';
                return '<span class="countdown-cell ' + color + '">' +
                    (m > 0 ? m + 'm ' : '') + String(s).padStart(2, '0') + 's' +
                    '</span>';
            }

            function tickCountdowns() {
                $('#active-locks-body tr[data-expires]').each(function() {
                    var expiry = new Date($(this).data('expires'));
                    var remaining = Math.ceil((expiry - Date.now()) / 1000);
                    if (remaining <= 0) {
                        $(this).fadeOut(400, function() {
                            $(this).remove();
                            refreshLockCount();
                        });
                    } else {
                        $(this).find('.countdown-col').html(formatCountdown(remaining));
                        if (remaining <= 10) $(this).addClass('lock-row-expiring');
                    }
                });
            }

            function refreshLockCount() {
                var count = $('#active-locks-body tr[data-expires]').length;
                $('#locks-count-badge').text(count);
                $('#stat-active-locks').text(count);
                if (count === 0) {
                    if ($('#no-locks-row').length === 0) {
                        $('#active-locks-body').html(
                            '<tr id="no-locks-row"><td colspan="6" class="text-center text-muted py-3">No active locks</td></tr>'
                            );
                    }
                } else {
                    $('#no-locks-row').remove();
                }
            }

            function loadActiveLocks() {
                $.ajax({
                    url: '{{ route('dialing.active-locks') }}',
                    method: 'GET',
                    success: function(res) {
                        $('#locks-last-refresh').text('Updated ' + new Date().toLocaleTimeString());
                        $('#stat-active-locks').text(res.stats.active_count);
                        $('#stat-calls-today').text(res.stats.calls_today);
                        $('#locks-count-badge').text(res.stats.active_count);

                        var $body = $('#active-locks-body');
                        if (!res.locks || res.locks.length === 0) {
                            $body.html(
                                '<tr id="no-locks-row"><td colspan="6" class="text-center text-muted py-3">No active locks</td></tr>'
                                );
                            return;
                        }

                        // Keep existing rows (update countdown in-place) or rebuild
                        var existingIds = {};
                        $body.find('tr[data-id]').each(function() {
                            existingIds[$(this).data('id')] = $(this);
                        });

                        var seenIds = {};
                        $.each(res.locks, function(i, lock) {
                            seenIds[lock.id] = true;
                            var countdown = formatCountdown(lock.remaining_seconds);
                            if (existingIds[lock.id]) {
                                existingIds[lock.id].find('.countdown-col').html(countdown);
                                existingIds[lock.id].attr('data-expires', lock.expires_at_iso);
                            } else {
                                var row = '<tr data-id="' + lock.id + '" data-expires="' + lock
                                    .expires_at_iso + '">' +
                                    '<td><strong>' + $('<div>').text(lock.full_number).html() +
                                    '</strong></td>' +
                                    '<td>' + $('<div>').text(lock.user_name).html() + '</td>' +
                                    '<td><code>' + $('<div>').text(lock.locked_at).html() +
                                    '</code></td>' +
                                    '<td class="countdown-col">' + countdown + '</td>' +
                                    '<td><span class="badge bg-info">' + lock.call_count +
                                    '</span></td>' +
                                    '<td class="text-end">' +
                                    '<button type="button" class="btn btn-sm btn-outline-danger release-lock-btn" data-lock-id="' +
                                    lock.id + '" data-number="' + $('<div>').text(lock
                                        .full_number).html() + '">Release</button>' +
                                    '</td></tr>';
                                $body.append(row);
                            }
                        });

                        // Remove rows for expired/released locks
                        $body.find('tr[data-id]').each(function() {
                            if (!seenIds[$(this).data('id')]) $(this).remove();
                        });

                        $('#no-locks-row').remove();
                    }
                });
            }

            // Release a single lock
            $(document).on('click', '.release-lock-btn', function() {
                var lockId = $(this).data('lock-id');
                var num = $(this).data('number');
                var $btn = $(this);
                $btn.prop('disabled', true).text('Releasing…');
                $.ajax({
                    url: '{{ route('dialing.clear-lock') }}',
                    method: 'POST',
                    data: {
                        id: lockId,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        $btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            refreshLockCount();
                        });
                        toastr.success('Lock released for ' + num);
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('Release');
                        toastr.error('Failed to release lock.');
                    }
                });
            });

            // Clear all locks
            $('#clearAllLocksBtn').on('click', function() {
                var count = parseInt($('#locks-count-badge').text(), 10) || 0;
                if (count === 0) {
                    toastr.info('No active locks to clear.');
                    return;
                }
                Swal.fire({
                    title: 'Clear all active locks?',
                    text: count + ' lock' + (count === 1 ? '' : 's') +
                        ' will be released immediately.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Yes, clear all'
                }).then(function(r) {
                    if (!r.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('dialing.clear-all-locks') }}',
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {
                            toastr.success('Cleared ' + res.cleared + ' lock' + (res
                                .cleared === 1 ? '' : 's') + '.');
                            loadActiveLocks();
                        },
                        error: function() {
                            toastr.error('Failed to clear locks.');
                        }
                    });
                });
            });

            // Start the live countdown ticker
            dialCountdownTimer = setInterval(tickCountdowns, 1000);

            // Auto-refresh the table every 5 s when the dialing section is visible
            function startLocksRefresh() {
                loadActiveLocks();
                dialLocksRefreshTimer = setInterval(loadActiveLocks, 5000);
            }

            function stopLocksRefresh() {
                clearInterval(dialLocksRefreshTimer);
                dialLocksRefreshTimer = null;
            }

            // Only poll when the Dial Lock section is active
            $menuButtons.on('click', function() {
                if ($(this).data('target') === '#form-dialing') {
                    startLocksRefresh();
                } else {
                    stopLocksRefresh();
                }
            });

            // ── Save dialing settings form ──────────────────────────────────────
            $('#dialingSettingsForm').on('submit', function(e) {
                e.preventDefault();
                var $btn = $(this).find('[type="submit"]');
                var orig = $btn.html();
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Saving…');

                var enabled = $('#dialing_lock_enabled').is(':checked') ? 1 : 0;
                $.ajax({
                    url: '{{ route('settings.dialing.save') }}',
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        dialing_lock_enabled: enabled,
                        dialing_lock_same_user_minutes: $('#dialing_lock_same_user_minutes').val(),
                        dialing_lock_other_user_minutes: $('#dialing_lock_other_user_minutes')
                        .val(),
                    },
                    success: function(res) {
                        if (res.success) toastr.success(res.message);
                        else toastr.error(res.message);
                    },
                    error: function(xhr) {
                        toastr.error('Failed to save: ' + (xhr.responseJSON?.error ||
                            'Unknown error'));
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(orig);
                    }
                });
            });

        });
    </script>
@endsection

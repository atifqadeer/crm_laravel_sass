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
                        <button class="list-group-item list-group-item-action" data-target="#form-google-maps" type="button"
                            id="menu-profile" aria-controls="form-google-maps">Google Maps Settings</button>
                        <button class="list-group-item list-group-item-action" data-target="#form-notifications"
                            type="button" id="menu-notifications" aria-controls="form-notifications">Notification
                            Settings</button>
                        <button class="list-group-item list-group-item-action" data-target="#form-sms" type="button"
                            id="menu-sms" aria-controls="form-sms">SMS Settings</button>
                        <button class="list-group-item list-group-item-action" data-target="#form-smtp" type="button"
                            id="menu-smtp" aria-controls="form-smtp">SMTP Settings</button>
                        <button class="list-group-item list-group-item-action" data-target="#form-scraper" type="button"
                            id="menu-scraper" aria-controls="form-scraper">Scraper Settings</button>
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
                                        <button type="button" class="btn btn-danger d-none" id="removeSmtpBtn">− Remove Last
                                            SMTP</button>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-success">Save SMTP Settings</button>
                                    </div>
                                </div>
                            </form>
                        </section>
                        <!-- Scraper Settings Form -->
                        <section id="form-scraper" class="settings-form-section">
                            <form id="scraperSettingsForm" data-type="scraper">
                                @csrf
                                <div class="mb-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0">Scraper Actors</h5>
                                        <small class="text-muted">Add one or more actor configurations for Scrap or other
                                            providers.</small>
                                    </div>
                                </div>
                                <div id="scraperCards"></div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <!-- <div class="text-muted small"></div> -->
                                    <button type="button" class="btn btn-outline-primary left" id="addScraperCardBtn">
                                        <i class="ri-add-line"></i> Add Actor Card
                                    </button>
                                    <button type="submit" class="btn btn-success">Save Scraper Settings</button>
                                </div>
                            </form>
                        </section>
                        <template id="scraperCardTemplate">
                            <div class="card mb-3 scraper-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="card-title mb-0">Actor Configuration</h6>
                                        <div class="d-flex align-items-center scraper-actions">
                                            <span class="card-scraper-status small me-2"></span>
                                            <button type="button" class="btn btn-success btn-sm run-scraper-actor me-2">
                                                <i class="ri-play-line"></i> Run
                                            </button>
                                            <button type="button"
                                                class="btn btn-outline-danger btn-sm remove-scraper-card me-3">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Provider</label>
                                            <select class="form-select scraper-provider" name="actors[__INDEX__][provider]">
                                                <option value="apify">Apify</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Scrap Source</label>
                                            <select class="form-select scraper-source" name="actors[__INDEX__][source]">
                                                <option value="indeed">Indeed</option>
                                                <option value="totaljob">TotalJob</option>
                                                <option value="reed">Reed</option>
                                                <option value="monster">Monster</option>
                                                <option value="cvlibrary">CV Library</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Actor ID</label>
                                            <input type="text" class="form-control scraper-actor-id"
                                                name="actors[__INDEX__][actor_id]" value="">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Token</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control scraper-token"
                                                    name="actors[__INDEX__][token]" value="">
                                                <button class="btn btn-outline-secondary toggle-token" type="button">
                                                    <i class="ri-eye-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Base URL</label>
                                            <input type="text" class="form-control scraper-base-url"
                                                name="actors[__INDEX__][base_url]" value="https://api.apify.com/v2/datasets">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
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
    <link rel="stylesheet" href="{{ asset('css/jquery.dataTables.min.css')}}">

    <!-- DataTables JS (for the table functionality) -->
    <script src="{{ asset('js/jquery.dataTables.min.js')}}"></script>

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}">

    <!-- SweetAlert2 CDN -->
    <script src="{{ asset('js/sweetalert2@11.js')}}"></script>

    <!-- Toastr JS -->
    <script src="{{ asset('js/toastr.min.js')}}"></script>

    <!-- Moment JS -->
    <script src="{{ asset('js/moment.min.js')}}"></script>

    {{-- @vite(['resources/js/pages/settings.js']) --}}

    <script>
        $(document).ready(function () {
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
            const scraperCardTemplateHtml = $('#scraperCardTemplate').html();
            const $scraperCards = $('#scraperCards');

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
                success: function (data) {
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

                    // Scraper Settings
                    let scraperActors = [];

                    if (data.scraper && Array.isArray(data.scraper.actors) && data.scraper.actors.length > 0) {
                        scraperActors = data.scraper.actors;
                    } else {
                        // Default row if nothing saved yet
                        scraperActors = [{
                            provider: 'apify',
                            source: 'indeed',
                            actor_id: '',
                            token: '',
                            base_url: 'https://api.apify.com/v2/datasets'
                        }];
                    }

                    renderScraperCards(scraperActors);

                    // Notification Settings
                    if (data.notifications) {
                        $('#email_notifications').val(data.notifications.email_notifications ? '1' : '0');
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
                            $entry.find('input[name="smtp[0][id]"]').val(setting.id || '').attr('name', `smtp[${index}][id]`);
                            $entry.find('input[name="smtp[0][mailer]"]').val(setting.mailer || '').attr('name', `smtp[${index}][mailer]`);
                            $entry.find('input[name="smtp[0][host]"]').val(setting.host || '').attr('name', `smtp[${index}][host]`);
                            $entry.find('input[name="smtp[0][port]"]').val(setting.port || '').attr('name', `smtp[${index}][port]`);
                            $entry.find('input[name="smtp[0][username]"]').val(setting.username || '').attr('name', `smtp[${index}][username]`);
                            $entry.find('input[name="smtp[0][password]"]').val(setting.password || '').attr('name', `smtp[${index}][password]`);
                            $entry.find('select[name="smtp[0][encryption]"]').val(setting.encryption || '').attr('name', `smtp[${index}][encryption]`);
                            // ✅ FROM EMAIL (disable if exists)
                            const $fromEmail = $entry.find('input[name="smtp[0][from_address]"]')
                                .val(setting.from_address || '')
                                .attr('name', `smtp[${index}][from_address]`);

                            if (setting.from_address) {
                                $fromEmail.prop('readonly', true);
                            }
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
                error: function (xhr) {
                    console.error('Error loading settings:', xhr.responseText);
                    toastr.error('Failed to load settings.');
                }
            });

            // Handle menu button clicks
            $menuButtons.on('click', function (e) {
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
            $('#addSmtpBtn').on('click', function () {
                const $newEntry = $smtpTemplate.clone();
                $newEntry.find('input, select').each(function () {
                    const name = $(this).attr('name').replace('[0]', `[${smtpIndex}]`);
                    $(this).attr('name', name).val('');
                });
                $('#smtp-entries').append($newEntry);
                smtpIndex++;
                toggleRemoveButton();
            });

            // Remove SMTP entry
            $(document).on('click', '.remove-smtp-btn', function () {
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
                                url: '{{ route("settings.smtp.delete") }}',
                                method: 'POST',
                                data: {
                                    id: id,
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function (response) {
                                    $entry.remove();
                                    smtpIndex--;
                                    toggleRemoveButton();

                                    Swal.fire(
                                        'Deleted!',
                                        'SMTP setting has been deleted.',
                                        'success'
                                    );
                                },
                                error: function (xhr) {
                                    console.error('Error deleting SMTP setting:', xhr.responseText);
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
            $('#removeSmtpBtn').on('click', function () {
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
                                    url: '{{ route("settings.smtp.delete") }}',
                                    method: 'POST',
                                    data: {
                                        id: id,
                                        _token: $('meta[name="csrf-token"]').attr('content')
                                    },
                                    success: function (response) {
                                        $lastEntry.remove();
                                        smtpIndex--;
                                        toggleRemoveButton();

                                        Swal.fire(
                                            'Deleted!',
                                            'SMTP setting has been deleted.',
                                            'success'
                                        );
                                    },
                                    error: function (xhr) {
                                        console.error('Error deleting SMTP setting:', xhr.responseText);
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

            let scraperCardCounter = 0;

            function createScraperCard(actor = {}, index = 0) {
                const providerValue = actor.provider || 'apify';
                const sourceValue = actor.source || 'indeed';

                const $card = $(scraperCardTemplateHtml);
                const uniqueId = 'scraper-card-' + (++scraperCardCounter);

                $card.attr('id', uniqueId);
                $card.attr('data-key', actor.key || '');

                // Set values
                $card.find('.scraper-provider').val(providerValue);
                $card.find('.scraper-source').val(sourceValue);
                $card.find('.scraper-actor-id').val(actor.actor_id || '');
                $card.find('.scraper-token').val(actor.token || '');
                $card.find('.scraper-base-url').val(actor.base_url || 'https://api.apify.com/v2/datasets');

                // ✅ Check if existing record
                const isExisting = !!actor.key;

                if (isExisting) {
                    // Disable fields
                    $card.find('.scraper-provider').prop('disabled', true);
                    $card.find('.scraper-source').prop('disabled', true);

                    // Add hidden inputs so values still submit
                    $card.append(`<input type="hidden" name="actors[${index}][provider]" value="${providerValue}">`);
                    $card.append(`<input type="hidden" name="actors[${index}][source]" value="${sourceValue}">`);
                } else {
                    // New card → keep editable + proper name attributes
                    $card.find('.scraper-provider').attr('name', `actors[${index}][provider]`);
                    $card.find('.scraper-source').attr('name', `actors[${index}][source]`);
                }

                // Always set names for other fields
                $card.find('.scraper-actor-id').attr('name', `actors[${index}][actor_id]`);
                $card.find('.scraper-token').attr('name', `actors[${index}][token]`);
                $card.find('.scraper-base-url').attr('name', `actors[${index}][base_url]`);

                return $card;
            }

            function updateScraperRemoveButtons() {
                const count = $scraperCards.find('.scraper-card').length;
                $scraperCards.find('.remove-scraper-card').toggleClass('d-none', count <= 1);
            }

            function renderScraperCards(actors) {
                $scraperCards.empty();
                if (!Array.isArray(actors) || actors.length === 0) {
                    actors = [{}];
                }
                actors.forEach(function (actor) {
                    $scraperCards.append(createScraperCard(actor));
                });
                updateScraperRemoveButtons();
            }

            $('#addScraperCardBtn').on('click', function () {
                $scraperCards.append(createScraperCard({}));
                updateScraperRemoveButtons();
            });

            $scraperCards.on('change', '.scraper-provider', function () {
                toggleCardFields($(this).closest('.scraper-card'));
            });

            // Toggle show/hide token
            $scraperCards.on('click', '.toggle-token', function () {
                const $btn = $(this);
                const $input = $btn.closest('.input-group').find('.scraper-token');
                const $icon = $btn.find('i');

                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $icon.removeClass('ri-eye-line').addClass('ri-eye-off-line');
                } else {
                    $input.attr('type', 'password');
                    $icon.removeClass('ri-eye-off-line').addClass('ri-eye-line');
                }
            });

            $scraperCards.on('click', '.run-scraper-actor', function () {
                const $btn = $(this);
                const $card = $btn.closest('.scraper-card');
                const $cardStatus = $card.find('.card-scraper-status');

                // Get key from attribute for reliability
                const key = $card.attr('data-key');

                if (!key || key === '') {
                    $cardStatus.html('<span class="text-warning">Please save settings first</span>');
                    $('#scraperStatus').html('<span class="text-warning">Actor not saved yet. Please save settings first.</span>');
                    return;
                }

                // Highlight only this card
                $card.addClass('border-primary shadow-sm');
                $cardStatus.html('<span class="spinner-border spinner-border-sm text-info"></span> <span class="ms-1">Running...</span>');
                $('#scraperStatus').html('<span class="text-info">Running specific scraper...</span>');

                const originalBtnHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: '{{ route("settings.scraper.run", ":key") }}'.replace(':key', key),
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            const fetched = response.fetched || 0;
                            const imported = response.imported || 0;
                            const skipped = response.skipped || 0;
                            const resText = `Total:${fetched} Imported:${imported} Skipped:${skipped}`;

                            $cardStatus.html(`<span class="text-success fw-bold ms-1" title="Fetched: ${fetched}, Imported: ${imported}, Skipped: ${skipped}">${resText}</span>`);
                            $('#scraperStatus').html(`<span class="text-success">Import success: ${resText}</span>`);
                            toastr.success(`Run completed: ${resText}`);
                        } else {
                            $cardStatus.html('<span class="text-danger ms-1">Failed</span>');
                            $('#scraperStatus').html('<span class="text-danger">Error: ' + response.message + '</span>');
                        }
                    },
                    error: function (xhr) {
                        $cardStatus.html('<span class="text-danger ms-1">Error</span>');
                        $('#scraperStatus').html('<span class="text-danger">Error running scraper.</span>');
                        toastr.error('Connection error running scraper.');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html(originalBtnHtml);
                        setTimeout(() => {
                            $card.removeClass('border-primary shadow-sm');
                        }, 2000);
                    }
                });
            });

            $scraperCards.on('click', '.remove-scraper-card', function () {
                const $card = $(this).closest('.scraper-card');
                const key = $card.data('key');

                if (key) {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'This will permanently delete this scraper actor from the database.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '{{ route("settings.scraper.delete", ":key") }}'.replace(':key', key),
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function (response) {
                                    if (response.success) {
                                        $card.remove();
                                        updateScraperRemoveButtons();
                                        toastr.success(response.message);
                                    } else {
                                        toastr.error(response.message);
                                    }
                                },
                                error: function (xhr) {
                                    toastr.error('Error deleting scraper actor.');
                                }
                            });
                        }
                    });
                } else {
                    $card.remove();
                    updateScraperRemoveButtons();
                }
            });

            // Handle form submissions
            $formSections.find('form').submit(function (e) {
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
                        url: '{{ route("settings.smtp.save") }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // ... (reload SMTP entries code remains same)
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function (xhr) {
                            console.error('Error saving SMTP settings:', xhr.responseText);
                            toastr.error('Failed to save SMTP settings: ' + (xhr.responseJSON?.error || 'Unknown error'));
                        },
                        complete: function () {
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
                        url: '{{ route("settings.general.save") }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // ... (reload SMTP entries code remains same)
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function (xhr) {
                            console.error('Error saving general settings:', xhr.responseText);
                            toastr.error('Failed to save general settings: ' + (xhr.responseJSON?.error || 'Unknown error'));
                        },
                        complete: function () {
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
                        url: '{{ route("settings.profile.save") }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // ... (reload SMTP entries code remains same)
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function (xhr) {
                            console.error('Error saving profile settings:', xhr.responseText);
                            toastr.error('Failed to save profile settings: ' + (xhr.responseJSON?.error || 'Unknown error'));
                        },
                        complete: function () {
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
                        url: '{{ route("settings.google.save") }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // ... (reload SMTP entries code remains same)
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function (xhr) {
                            console.error('Error saving Google Map settings:', xhr.responseText);
                            toastr.error('Failed to save Google Map settings: ' + (xhr.responseJSON?.error || 'Unknown error'));
                        },
                        complete: function () {
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
                        url: '{{ route("settings.notification.save") }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // ... (reload SMTP entries code remains same)
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function (xhr) {
                            console.error('Error saving notifications settings:', xhr.responseText);
                            toastr.error('Failed to save notifications settings: ' + (xhr.responseJSON?.error || 'Unknown error'));
                        },
                        complete: function () {
                            // restore button
                            $btn.prop('disabled', false).html(originalText);
                        }
                    });
                } else if (formType === 'scraper') {
                    // ── Re-index cards before serializing ─────────────────────────────
                    $('#scraperCards .scraper-card').each(function (index) {
                        $(this).find('[name*="actors["]').each(function () {
                            const newName = $(this).attr('name').replace(/actors\[\d+\]/, `actors[${index}]`);
                            $(this).attr('name', newName);
                        });
                    });

                    const formData = new FormData(this);

                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...'
                    );

                    $.ajax({
                        url: '{{ route("settings.scraper.save") }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.message);
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function (xhr) {
                            console.error('Error saving scraper settings:', xhr.responseText);
                            toastr.error('Failed to save scraper settings: ' + (xhr.responseJSON?.error || 'Unknown error'));
                        },
                        complete: function () {
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
                        url: '{{ route("settings.sms.save") }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // ... (reload SMTP entries code remains same)
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function (xhr) {
                            console.error('Error saving sms settings:', xhr.responseText);
                            toastr.error('Failed to save sms settings: ' + (xhr.responseJSON?.error || 'Unknown error'));
                        },
                        complete: function () {
                            // restore button
                            $btn.prop('disabled', false).html(originalText);
                        }
                    });
                } else {
                    // disable + show loader
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...'
                    );

                    $.ajax({
                        url: '{{ route("settings.save") }}',
                        method: 'POST',
                        data: $form.serialize() + '&form_type=' + formType,
                        success: function (response) {
                            toastr.success(response.message);
                        },
                        error: function (xhr) {
                            console.error('Error saving settings:', xhr.responseText);
                            toastr.error('Failed to save settings.');
                        },
                        complete: function () {
                            // restore button
                            $btn.prop('disabled', false).html(originalText);
                        }
                    });
                }
            });

        });
    </script>
@endsection
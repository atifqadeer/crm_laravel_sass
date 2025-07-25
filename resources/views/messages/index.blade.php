@extends('layouts.vertical', ['title' => 'Messages', 'subTitle' => 'Real Estate'])

@section('css')
@vite(['node_modules/swiper/swiper-bundle.min.css'])
<style>
    .chat-conversation-list { max-height: 500px; overflow-y: auto; }
</style>
@endsection

@section('content')
<div class="row g-1">
    <div class="col-xxl-3">
        <div class="offcanvas-xxl offcanvas-start h-100" tabindex="-1" id="Contactoffcanvas" aria-labelledby="ContactoffcanvasLabel">
            <div class="card position-relative overflow-hidden">
                <div class="card-header border-0 d-flex justify-content-between align-items-center gap-3">
                    <form class="chat-search pb-0">
                        <div class="chat-search-box">
                            <input class="form-control" type="text" name="search" placeholder="Search ..." id="searchApplicants">
                            <button type="submit" class="btn btn-sm btn-link search-icon p-0 fs-15"><i class="ri-search-eye-line"></i></button>
                        </div>
                    </form>
                </div>
                <h4 class="card-title mb-3 mx-3">Active Applicants</h4>
                <div class="swiper mySwiper mx-3">
                    <div class="swiper-wrapper" id="activeApplicants">
                        <!-- Applicants will be loaded here via AJAX -->
                    </div>
                </div>
                <h4 class="card-title m-3">Messages <span class="badge bg-danger badge-pill" id="unreadCount">0</span></h4>
                <ul class="nav nav-pills chat-tab-pills nav-justified p-1 rounded mx-1">
                    <li class="nav-item">
                        <a href="#chat-list" data-bs-toggle="tab" aria-expanded="false" class="nav-link active">Chat</a>
                    </li>
                    <li class="nav-item">
                        <a href="#group-list" data-bs-toggle="tab" aria-expanded="true" class="nav-link">Group</a>
                    </li>
                    <li class="nav-item">
                        <a href="#contact-list" data-bs-toggle="tab" aria-expanded="true" class="nav-link">Contact</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane show active" id="chat-list">
                        <div class="px-2 mb-3 chat-setting-height" data-simplebar id="chatList">
                            <!-- Chat list will be loaded here via AJAX -->
                        </div>
                    </div>
                    <div class="tab-pane" id="group-list">
                        <div class="px-2 mb-3 chat-setting-height" data-simplebar>
                            <!-- Group list remains static -->
                        </div>
                    </div>
                    <div class="tab-pane" id="contact-list">
                        <div class="px-2 mb-3 chat-setting-height" data-simplebar>
                            <!-- Contact list remains static -->
                        </div>
                    </div>
                </div>
                <!-- User Settings Offcanvas remains unchanged -->
            </div>
        </div>
    </div>
    <div class="col-xxl-9">
        <div class="card position-relative overflow-hidden">
            <div class="card-header d-flex align-items-center mh-100 bg-light-subtle">
                <button class="btn btn-light d-xxl-none d-flex align-items-center px-2 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#Contactoffcanvas" aria-controls="Contactoffcanvas">
                    <i class="ri-menu-line fs-18"></i>
                </button>
                <div class="d-flex align-items-center" id="chatHeader">
                    <!-- Chat header will be updated via AJAX -->
                </div>
                {{-- <div class="flex-grow-1">
                    <ul class="list-inline float-end d-flex gap-1 mb-0 align-items-center">
                        <li class="list-inline-item fs-20 dropdown">
                            <a href="javascript: void(0);" class="btn btn-light avatar-sm d-flex align-items-center justify-content-center text-dark fs-20" data-bs-toggle="modal" data-bs-target="#videocall">
                                <iconify-icon icon="solar:videocamera-record-bold-duotone"></iconify-icon>
                            </a>
                        </li>
                        <li class="list-inline-item fs-20 dropdown">
                            <a href="javascript: void(0);" class="btn btn-light avatar-sm d-flex align-items-center justify-content-center text-dark fs-20" data-bs-toggle="modal" data-bs-target="#voicecall">
                                <iconify-icon icon="solar:outgoing-call-rounded-bold-duotone"></iconify-icon>
                            </a>
                        </li>
                        <li class="list-inline-item fs-20 dropdown">
                            <a data-bs-toggle="offcanvas" href="#user-profile" class="btn btn-light avatar-sm d-flex align-items-center justify-content-center text-dark fs-20">
                                <iconify-icon icon="solar:user-bold-duotone"></iconify-icon>
                            </a>
                        </li>
                        <li class="list-inline-item fs-20 dropdown d-none d-md-flex">
                            <a href="javascript: void(0);" class="dropdown-toggle arrow-none text-dark" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-2-fill"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="javascript: void(0);"><i class="ri-user-6-line me-2"></i>View Profile</a>
                                <a class="dropdown-item" href="javascript: void(0);"><i class="ri-music-2-line me-2"></i>Media, Links and Docs</a>
                                <a class="dropdown-item" href="javascript: void(0);"><i class="ri-search-2-line me-2"></i>Search</a>
                                <a class="dropdown-item" href="javascript: void(0);"><i class="ri-image-line me-2"></i>Wallpaper</a>
                                <a class="dropdown-item" href="javascript: void(0);"><i class="ri-arrow-right-circle-line me-2"></i>More</a>
                            </div>
                        </li>
                    </ul>
                </div> --}}
            </div>
            <div class="chat-box">
                <ul class="chat-conversation-list p-3 chatbox-height" id="chatConversation">
                    <!-- Messages will be loaded here via AJAX -->
                </ul>
                <div class="bg-light bg-opacity-50 p-2">
                    <form class="needs-validation" name="chat-form" id="chat-form">
                        <input type="hidden" id="applicantId" name="applicant_id">
                        <input type="hidden" id="phoneNumber" name="phone_number">
                        <div class="row align-items-center">
                            <div class="col mb-2 mb-sm-0 d-flex">
                                <div class="input-group">
                                    <a href="javascript: void(0);" class="btn btn-sm btn-primary rounded-start d-flex align-items-center input-group-text"><i class="ri-emotion-line fs-18"></i></a>
                                    <input type="text" class="form-control border-0" placeholder="Enter your message" name="message" id="messageInput" maxlength="255">
                                </div>
                            </div>
                            <div class="col-sm-auto">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm chat-send"><i class="ri-send-plane-2-line fs-18"></i></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Video and Voice call modals, Profile offcanvas remain unchanged -->
        </div>
    </div>
</div>
@endsection

@section('script-bottom')
@vite(['resources/js/pages/app-chat.js'])
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script>
$(document).ready(function() {
    let currentApplicantId = null;
    let currentPhoneNumber = null;

    // Load applicants
    function loadApplicants() {
        $.ajax({
            url: '{{ route("getApplicantsForMessage") }}',
            method: 'GET',
            success: function(response) {
                let applicantsHtml = '';
                let chatListHtml = '';
                let unreadCount = 0;

                response.forEach(applicant => {
                    applicantsHtml += `
                        <div class="swiper-slide avatar">
                            <a href="#!" class="rounded-circle applicant-chat" data-applicant-id="${applicant.id}" data-phone-number="${applicant.last_message ? applicant.last_message.phone_number : ''}">
                                <div class="position-relative">
                                    <img src="/images/users/avatar-${applicant.id % 10 || 1}.jpg" alt="" class="avatar rounded-circle flex-shrink-0">
                                    ${applicant.last_message ? '<span class="position-absolute bottom-0 end-0 p-1 bg-success border border-light border-2 rounded-circle"><span class="visually-hidden">New alerts</span></span>' : ''}
                                </div>
                            </a>
                        </div>
                    `;

                    if (applicant.last_message) {
                        const lastMessage = applicant.last_message.message;
                        const time = applicant.last_message.time;
                        const unread = applicant.last_message.unread_count || 0;
                        unreadCount += unread;
                        chatListHtml += `
                            <div class="d-flex flex-column h-100 border-bottom">
                                <a href="#!" class="d-block applicant-chat" data-applicant-id="${applicant.id}" data-phone-number="${applicant.last_message.phone_number}">
                                    <div class="d-flex align-items-center p-2 mb-1 rounded">
                                        <div class="position-relative">
                                            <img src="/images/users/avatar-${applicant.id % 10 || 1}.jpg" alt="" class="avatar rounded-circle flex-shrink-0">
                                            <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-light border-2 rounded-circle">
                                                <span class="visually-hidden">New alerts</span>
                                            </span>
                                        </div>
                                        <div class="d-block ms-3 flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h5 class="mb-0">${applicant.name}</h5>
                                                <div>
                                                    <p class="text-muted fs-13 mb-0">${time}</p>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <p class="mb-0 text-muted d-flex align-items-center gap-1">${lastMessage}</p>
                                                ${unread > 0 ? `<span class="badge bg-danger badge-pill">${unread}</span>` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        `;
                    }
                });

                $('#activeApplicants').html(applicantsHtml);
                $('#chatList').html(chatListHtml);
                $('#unreadCount').text(unreadCount);

                // Initialize Swiper
                new Swiper('.mySwiper', {
                    slidesPerView: 'auto',
                    spaceBetween: 10,
                });

                // Click handler for applicant selection
                $('.applicant-chat').click(function(e) {
                    e.preventDefault();
                    currentApplicantId = $(this).data('applicant-id');
                    currentPhoneNumber = $(this).data('phone-number');
                    $('#applicantId').val(currentApplicantId);
                    $('#phoneNumber').val(currentPhoneNumber);
                    loadMessages(currentApplicantId);
                });
            },
            error: function(xhr) {
                console.error('Error loading applicants:', xhr);
            }
        });
    }

    // Load messages for a specific applicant
    function loadMessages(applicantId) {
        $.ajax({
            url: '{{ route("messages.get", ":applicantId") }}'.replace(':applicantId', applicantId),
            method: 'post',
            success: function(response) {
                const messages = response.messages;
                const applicant = response.applicant;
                let messagesHtml = '';

                messages.forEach(message => {
                    const isSender = message.is_sender;
                    const className = isSender ? 'justify-content-end odd' : '';
                    const avatar = isSender ? '/images/users/avatar-1.jpg' : `/images/users/avatar-${applicant.id % 10 || 1}.jpg`;
                    const statusIcon = message.is_read ? '<i class="ri-check-double-line fs-18 text-primary"></i>' : '<i class="ri-check-line fs-18 text-muted"></i>';

                    messagesHtml += `
                        <li class="d-flex gap-2 clearfix ${className}">
                            <div class="chat-avatar text-center">
                                <img src="${avatar}" alt="" class="avatar rounded-circle">
                            </div>
                            <div class="chat-conversation-text ${isSender ? 'ms-0' : ''}">
                                <div>
                                    <p class="mb-2"><span class="text-dark fw-medium me-1">${isSender ? 'You' : message.user_name}</span> ${message.created_at}</p>
                                </div>
                                <div class="d-flex ${isSender ? 'justify-content-end' : 'align-items-start'}">
                                    <div class="chat-ctext-wrap">
                                        <p>${message.message}</p>
                                        <small class="text-muted">${message.phone_number} | ${message.status}</small>
                                    </div>
                                    <div class="chat-conversation-actions dropdown ${isSender ? 'dropstart' : 'dropend'}">
                                        <a href="javascript: void(0);" class="${isSender ? 'pe-1' : 'ps-1'}" data-bs-toggle="dropdown" aria-expanded="false"><i class='ri-more-2-fill fs-18'></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);"><i class="ri-share-forward-line me-2"></i>Reply</a>
                                            <a class="dropdown-item" href="javascript: void(0);"><i class="ri-share-line me-2"></i>Forward</a>
                                            <a class="dropdown-item" href="javascript: void(0);"><i class="ri-file-copy-line me-2"></i>Copy</a>
                                            <a class="dropdown-item" href="javascript: void(0);"><i class="ri-bookmark-line me-2"></i>Bookmark</a>
                                            <a class="dropdown-item" href="javascript: void(0);"><i class="ri-star-line me-2"></i>Starred</a>
                                            <a class="dropdown-item" href="javascript: void(0);"><i class="ri-information-2-line me-2"></i>Mark as Unread</a>
                                            <a class="dropdown-item" href="javascript: void(0);"><i class="ri-delete-bin-line me-2"></i>Delete</a>
                                        </div>
                                    </div>
                                </div>
                                ${isSender ? `<div class="d-flex justify-content-end">${statusIcon}</div>` : ''}
                            </div>
                        </li>
                    `;
                });

                $('#chatConversation').html(messagesHtml);
                $('#chatHeader').html(`
                    <img src="/images/users/avatar-${applicant.id % 10 || 1}.jpg" class="me-2 rounded" height="36" alt="avatar-${applicant.id % 10 || 1}" />
                    <div class="d-none d-md-flex flex-column">
                        <h5 class="my-0 fs-16 fw-semibold">
                            <a data-bs-toggle="offcanvas" href="#user-profile" class="text-dark">${applicant.name}</a>
                        </h5>
                        <p class="mb-0 text-success fw-medium">Active <i class="ri-circle-fill fs-10"></i></p>
                    </div>
                `);
                $('#chatConversation').scrollTop($('#chatConversation')[0].scrollHeight);
            },
            error: function(xhr) {
                console.error('Error loading messages:', xhr);
            }
        });
    }

    // Send message
    $('#chat-form').submit(function(e) {
        e.preventDefault();
        if (!currentApplicantId || !currentPhoneNumber) {
            alert('Please select an applicant to send a message.');
            return;
        }

        $.ajax({
            url: '{{ route("messages.send") }}',
            method: 'POST',
            data: {
                applicant_id: currentApplicantId,
                phone_number: currentPhoneNumber,
                message: $('#messageInput').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(message) {
                const messageHtml = `
                    <li class="d-flex gap-2 clearfix justify-content-end odd">
                        <div class="chat-conversation-text ms-0">
                            <div>
                                <p class="mb-2">${message.created_at} <span class="text-dark fw-medium ms-1">You</span></p>
                            </div>
                            <div class="d-flex justify-content-end">
                                <div class="chat-ctext-wrap">
                                    <p>${message.message}</p>
                                    <small class="text-muted">${message.phone_number} | ${message.status}</small>
                                </div>
                                <div class="chat-conversation-actions dropdown dropstart">
                                    <a href="javascript: void(0);" class="pe-1" data-bs-toggle="dropdown" aria-expanded="false"><i class='ri-more-2-fill fs-18'></i></a>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript: void(0);"><i class="ri-share-forward-line me-2"></i>Reply</a>
                                        <a class="dropdown-item" href="javascript: void(0);"><i class="ri-share-line me-2"></i>Forward</a>
                                        <a class="dropdown-item" href="javascript: void(0);"><i class="ri-file-copy-line me-2"></i>Copy</a>
                                        <a class="dropdown-item" href="javascript: void(0);"><i class="ri-bookmark-line me-2"></i>Bookmark</a>
                                        <a class="dropdown-item" href="javascript: void(0);"><i class="ri-star-line me-2"></i>Starred</a>
                                        <a class="dropdown-item" href="javascript: void(0);"><i class="ri-information-2-line me-2"></i>Mark as Unread</a>
                                        <a class="dropdown-item" href="javascript: void(0);"><i class="ri-delete-bin-line me-2"></i>Delete</a>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <i class="ri-check-line fs-18 text-muted"></i>
                            </div>
                        </div>
                        <div class="chat-avatar text-center">
                            <img src="/images/users/avatar-1.jpg" alt="" class="avatar rounded-circle">
                        </div>
                    </li>
                `;
                $('#chatConversation').append(messageHtml);
                $('#chatConversation').scrollTop($('#chatConversation')[0].scrollHeight);
                $('#messageInput').val('');
                loadApplicants(); // Refresh applicant list to update last message
            },
            error: function(xhr) {
                console.error('Error sending message:', xhr);
                alert('Failed to send message. Please try again.');
            }
        });
    });

    // Initial load
    loadApplicants();
});

</script>
@endsection
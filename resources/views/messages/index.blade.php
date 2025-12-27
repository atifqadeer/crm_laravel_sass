@extends('layouts.vertical', ['title' => 'Messages', 'subTitle' => 'Communication'])

@section('css')
@vite(['node_modules/swiper/swiper-bundle.min.css'])
<style>
    .chat-conversation-list { max-height: 700px; overflow-y: auto; }
    .chat-setting-height { max-height: 400px; overflow-y: auto; }
    .chat-box { display: flex; flex-direction: column; height: 71vh; }
    .chat-conversation-list { flex-grow: 1; }
    .nav-link { cursor: pointer; }
    .applicant-chat:hover, .user-chat:hover { background-color: #f8f9fa; }
    .chatbox-height { max-height: calc(100vh - 255px) !important; }
    #chatList, #userList { display: block; min-height: 52.4vh; } /* Ensure visibility and minimum height */
    .loader { text-align: center; padding: 10px; display: none; }
    .loader i { font-size: 20px; color: #007bff; }
    .simplebar-mask,
    .simplebar-offset {
        pointer-events: none !important;
    }
    .simplebar-content-wrapper,
    .simplebar-content {
        pointer-events: auto !important;
    }
    .user_name{
        font-size: 17px;
        font-weight: 700;
    }
    .active-chat {
        background: #eef3ff;
        border-radius: 0 6px 6px 0;
        position: relative;
    }

    .active-chat::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #4f6ef7;
        border-radius: 6px 0 0 6px;
    }

    #chatConversationLoader {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10;
    }
    #noChatMessage {
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        text-align: center;
        color: #6c757d;
        font-size: 14px;
        font-weight: 500;
        z-index: 10;
        display: none;
    }
    #scrollBottomBtn {
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        border-radius: 25px;
        padding: 6px 12px;
        font-size: 14px;
    }

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
                            <span class="btn btn-sm btn-link search-icon p-0 fs-15"><i class="ri-search-eye-line"></i></span>
                        </div>
                    </form>
                </div>
                <h4 class="card-title m-3">Messages <span class="badge bg-danger badge-pill" id="unreadCount">0</span></h4>
                <ul class="nav nav-pills chat-tab-pills nav-justified p-1 rounded mx-1">
                    <li class="nav-item">
                        <a href="#chat-list" data-bs-toggle="tab" aria-expanded="false" class="nav-link active">All Chats</a>
                    </li>
                    <li class="nav-item">
                        <a href="#contact-list" data-bs-toggle="tab" aria-expanded="true" class="nav-link">My Chats</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane show active" id="chat-list">
                        <div class="px-2 mb-3 chat-setting-height" data-simplebar id="chatList">
                            <!-- Chat list will be loaded here via AJAX -->
                            <div class="loader" id="chatListLoader">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Scroll to Bottom Button -->
                        <button id="scrollBottomBtn" class="btn btn-primary btn-sm" 
                            style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: none; z-index: 999;">
                            ↓ Scroll to Bottom
                        </button>
                    </div>

                    <div class="tab-pane" id="contact-list">
                        <div class="px-2 mb-3 chat-setting-height" data-simplebar id="userList">
                            <!-- User list will be loaded here via AJAX -->
                            <div class="loader" id="userListLoader">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
            </div>
            <div class="chat-box">
                <ul class="chat-conversation-list p-3 chatbox-height" id="chatConversation">
                    <div class="loader" id="chatConversationLoader" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="noChatMessage">
                        No Chat Available
                    </div>
                </ul>
                 
                <div class="bg-light bg-opacity-50 p-2">
                    <form class="needs-validation" name="chat-form" id="chat-form">
                        <input type="hidden" id="recipientId" name="recipient_id">
                        <input type="hidden" id="recipientType" name="recipient_type">
                        <input type="hidden" id="recipientPhone" name="recipient_phone">
                        <div class="row align-items-center">
                            <div class="col mb-2 mb-sm-0 d-flex">
                                <div class="input-group">
                                    <a href="javascript: void(0);" class="btn btn-sm btn-primary rounded-start d-flex align-items-center input-group-text"><i class="ri-text fs-24"></i></a>
                                    <input type="text" class="form-control border-0" placeholder="Enter your message" name="message" id="messageInput" required>
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
        </div>
    </div>
</div>
@endsection
@section('script-bottom')
@vite(['resources/js/pages/app-chat.js'])
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/simplebar@latest/dist/simplebar.min.js"></script>
<script>
    // Custom debounce function
    function debounce(func, wait) {
        let timeout;
        return function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, arguments), wait);
        };
    }

    let currentRecipientId = null;
    let currentRecipientType = null; // applicant | user
    let currentListRef = null;       // all-chat | user-chat
    
    let applicantLimit = 10;
    let applicantStart = 0;
    let isLoadingApplicants = false;
    let applicantAction = 'inactive';

    let activeTab = 'all-chat'; // default
    let currentSearchKeyword = '';

    function loadApplicants(search = '') {
        if (isLoadingApplicants) return;

        isLoadingApplicants = true;
        applicantAction = 'active';

        // Append a loader at the bottom of the chat list
        const loaderId = 'chatListScrollLoader';
        if ($('#' + loaderId).length === 0) {
            $('#chatList').append(`
                <div class="text-center py-2" id="${loaderId}">
                    <div class="spinner-border text-primary spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
        }

        $.ajax({
            url: '{{ route("getApplicantsForMessage") }}',
            method: 'GET',
            data: {
                limit: applicantLimit,
                start: applicantStart,
                search: search
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                let chatListHtml = '';
                let unreadCount = parseInt($('#unreadCount').text()) || 0;

                response.data.forEach(applicant => {
                    const lastMessage = applicant.last_message?.message ?? 'No messages';
                    const time = applicant.last_message?.time ?? '';
                    const unread = applicant.last_message?.unread_count ?? 0;
                    const hasMessage = applicant.last_message !== null;

                    unreadCount += unread;

                    chatListHtml += `
                        <div class="d-flex flex-column h-100 border-bottom">
                            <a href="#!" class="d-block applicant-chat" data-ref-name="all-chat"
                            data-recipient-id="${applicant.id}"
                            data-recipient-type="applicant">
                                <div class="d-flex align-items-center p-2 mb-1 rounded">
                                    <div class="position-relative">
                                        <img src="/images/users/avatar-${applicant.id % 10 || 1}.jpg"
                                            class="avatar rounded-circle">

                                        ${hasMessage ? `
                                            <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-light border-2 rounded-circle">
                                                <span class="visually-hidden">Active</span>
                                            </span>
                                        ` : `<span class="position-absolute bottom-0 end-0 p-1 bg-danger border border-light border-2 rounded-circle">
                                                <span class="visually-hidden">Inactive</span>
                                            </span>`}
                                    </div>

                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h5 class="mb-0 user_name">${applicant.name}</h5>
                                            <small class="text-muted">${time}</small>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p class="mb-0 text-muted">${lastMessage}</p>
                                            ${unread > 0 ? `<span class="badge bg-danger" style="height: fit-content;">${unread}</span>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    `;
                });

                // Append new applicants **above the loader**
                $('#' + loaderId).before(chatListHtml);
                $('#unreadCount').text(unreadCount);

                if (!response.data || response.data.length == 0) {
                    $('#' + loaderId).html('<p class="text-center fw-bold">End</p>');
                    applicantAction = 'active';
                    return;
                }

                applicantStart += applicantLimit; // 🔥 Increment start
                applicantAction = 'inactive';
            },
            error: function(xhr) {
                console.error(xhr);
            },
            complete: function() {
                isLoadingApplicants = false;

                // Remove loader if there are more records
                if ($('#chatListScrollLoader').length && applicantAction === 'inactive') {
                    $('#chatListScrollLoader').remove();
                }

                // Recalculate SimpleBar
                SimpleBar.instances.get(document.getElementById('chatList'))?.recalculate();
            }
        });
    }

    let loading = false;
    let oldestMessageId = null;
    let activeRecipient = null;
    
    let userLimit = 10;
    let userStart = 0;
    let isLoadingUsers = false;
    let hasMoreUsers = true;

    function loadUsers(search = '') {
        if (isLoadingUsers || !hasMoreUsers) return;

        isLoadingUsers = true;

        const loaderId = 'userListScrollLoader';
        // Append loader if it doesn't exist
        if ($('#' + loaderId).length === 0) {
            $('#userList').append(`
                <div class="text-center py-2" id="${loaderId}">
                    <div class="spinner-border text-primary spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
        }

        $.ajax({
            url: "{{ route('getUserChats') }}",
            method: 'GET',
            data: {
                limit: userLimit,
                start: userStart,
                search: search
            },
            success: function(response) {
                // Remove loader first
                $('#' + loaderId).remove();

                if (!response.data || !Array.isArray(response.data) || response.data.length === 0) {
                    hasMoreUsers = false;
                    // Optional: show "End of List"
                    $('#userList').append('<p class="text-center fw-bold">End of users</p>');
                    return;
                }

                let userListHtml = '';

                response.data.forEach(user => {
                    userListHtml += `
                        <div class="border-bottom">
                            <a href="#!"
                            class="d-block user-chat"
                            data-ref-name="user-chat"
                            data-recipient-id="${user.id}"
                            data-recipient-type="applicant">
                                <div class="d-flex align-items-center p-2">
                                    <img src="/images/users/avatar-${user.id % 10 || 1}.jpg"
                                        class="avatar rounded-circle">
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-0 user_name">${user.name}</h6>
                                            <small class="text-muted">
                                                ${user.last_message?.time ?? ''}
                                            </small>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">
                                                ${user.last_message?.message ?? ''}
                                            </span>
                                            ${
                                                user.last_message?.unread_count > 0
                                                    ? `<span class="badge bg-danger">${user.last_message.unread_count}</span>`
                                                    : ''
                                            }
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    `;
                });

                $('#userList').append(userListHtml);

                // ✅ Update pagination
                userStart += userLimit;
                hasMoreUsers = response.has_more === true; // must come from backend
            },
            error: function(xhr) {
                console.error('Error loading users:', xhr);
                // Remove loader on error
                $('#' + loaderId).remove();
            },
            complete: function() {
                isLoadingUsers = false;
                $('#userListLoader').hide();
            }
        });
    }

    $(document).ready(function() {
        // Initialize SimpleBar
        const chatList = $('#chatList');
        const userList = $('#userList');
        const scrollBtn = $('#scrollBottomBtn');

        // Load initial lists
        loadApplicants();
        loadUsers();

        // Infinite scroll for applicants
        chatList.on('scroll', function () {
            const scrollTop = $(this).scrollTop();
            const scrollHeight = this.scrollHeight;
            const height = $(this).height();
            const list_ref = $(this).data('ref-name');

            if (scrollTop + height >= scrollHeight - 10 && applicantAction === 'inactive') {
                loadApplicants(currentSearchKeyword);
            }
        });

        // Scroll to bottom when button is clicked
        scrollBtn.on('click', function() {
            chatList.animate({ scrollTop: chatList.prop('scrollHeight') }, 500);
            scrollBtn.hide();
        });

        userList.on('scroll', function () {
            const el = this;
            const list_ref = $(this).data('ref-name');

            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 20) {
                loadUsers(currentSearchKeyword);
            }
        });

        // Click handler for recipient selection
        $(document).on('click', '.applicant-chat, .user-chat', function (e) {
            e.preventDefault();
            $('.applicant-chat, .user-chat').removeClass('active-chat');
            $(this).addClass('active-chat');

            currentRecipientId   = $(this).data('recipient-id');
            currentRecipientType = $(this).data('recipient-type');
            currentListRef       = $(this).data('ref-name');

            $('#recipientId').val(currentRecipientId);
            $('#recipientType').val(currentRecipientType);

            loadMessages(currentRecipientId, currentRecipientType, currentListRef);
        });

        // Send message
        $('#chat-form').submit(function(e) {
            e.preventDefault();
            if (!currentRecipientId || !currentRecipientType) {
                alert('Please select a recipient to send a message.');
                return;
            }

            $.ajax({
                url: "{{ route('sendChatBoxMsg') }}",
                method: 'POST',
                data: {
                    recipient_id: currentRecipientId,
                    recipient_type: currentRecipientType,
                    recipient_phone: $('#recipientPhone').val(),
                    message: $('#messageInput').val(),
                    _token: '{{ csrf_token() }}'
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
                                        <small class="text-muted">${message.phone_number || ''} | ${message.status}</small>
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
                    // Refresh relevant list based on recipient type
                    if (currentRecipientType === 'applicant') {
                        loadApplicants(1); // Reload page 1 to update last_message
                    } else if (currentRecipientType === 'user') {
                        loadUsers(1); // Reload page 1 to update last_message
                    }
                },
                error: function(xhr) {
                    console.error('Error sending message:', xhr);
                    alert('Failed to send message. Please try again.');
                }
            });
        });

        // Tab switch handler
        $('.nav-link').on('click', function() {
            currentRecipientType = $(this).attr('href').substring(1).split('-')[0]; // Extract 'chat' or 'contact'
            currentRecipientId = null;
            $('#recipientId').val('');
            $('#recipientType').val('');
            $('#chatConversation').html('');
            $('#chatHeader').html('');

            currentSearchKeyword = ''; // reset search
            $('#searchApplicants').val('');

            if (currentRecipientType === 'chat') {
                activeTab = 'all-chat';
                applicantStart = 0;       // reset pagination
                $('#chatList').html('');  // clear old list
                hasMoreApplicants = true; // reset flag
                loadApplicants('');        // ✅ pass empty string
            } else if (currentRecipientType === 'contact') {
                activeTab = 'user-chat';
                userStart = 0;             // reset pagination
                $('#userList').html('');   // clear old list
                hasMoreUsers = true;       // reset flag
                loadUsers('');             // ✅ pass empty string
            }
        });

        // Search functionality
        let searchTimeout;

        $('#searchApplicants').on('keyup', function () {
            clearTimeout(searchTimeout);

            currentSearchKeyword = $(this).val().trim(); // ✅ store globally

            searchTimeout = setTimeout(() => {

                if (activeTab === 'user-chat') {
                    userStart = 0;
                    isLoadingUsers = false;
                    hasMoreUsers = true;
                    $('#userList').html('');
                    loadUsers(currentSearchKeyword);
                }

                if (activeTab === 'all-chat') {
                    applicantStart = 0;
                    isLoadingApplicants = false;
                    applicantAction = 'inactive';
                    $('#chatList').html('');
                    loadApplicants(currentSearchKeyword);
                }

            }, 300);
        });


    });

    function loadMessages(recipientId, recipientType, list_ref) {
        loading = true;
        hasMore = true;
        oldestMessageId = null;

        // Clear chat and show main loader
        $('#chatConversation').html('');
        $('#chatConversationLoader').show();

        // Append temporary loader so it renders before AJAX
        $('#chatConversation').prepend(`
            <div id="tempMessageLoader" class="text-center py-2">
                <div class="spinner-border text-primary spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);

        // Use setTimeout to let the browser render the loader
        setTimeout(() => {
            $.post("{{ route('getChatBoxMessages') }}", {
                recipient_id: recipientId,
                recipient_type: recipientType,
                list_ref: list_ref,
                _token: '{{ csrf_token() }}'
            }, function(response) {
                const messages = response.messages || [];
                const recipient = response.recipient;

                // ✅ Always render header
                $('#recipientPhone').val(recipient.phone);
                $('#chatHeader').html(`
                    <img src="/images/users/avatar-${recipientId % 10 || 1}.jpg"
                        class="me-2 rounded"
                        height="36" />
                    <div class="d-none d-md-flex flex-column">
                        <h5 class="my-0 fs-16 fw-semibold">
                            <a data-bs-toggle="offcanvas"
                            href="#user-profile"
                            class="text-dark">${recipient.name}</a>
                        </h5>
                        <p class="mb-0 text-success fw-medium">
                            ${recipient.phone}
                        </p>
                    </div>
                `);

                // ✅ If no messages → show empty state
                if (!messages.length) {
                    $('#chatConversation').html(`
                        <li class="text-center text-muted py-4">
                            No messages yet. Start the conversation 👋
                        </li>
                    `);
                    return;
                }


                oldestMessageId = messages[0].id;

                let html = '';
                messages.forEach(msg => {
                    html += renderMessage(msg, recipient);
                });

                $('#chatConversation').html(html); // Keep existing layout
                // ✅ HEADER (UNCHANGED)
                    $('#recipientPhone').val(recipient.phone);
                    $('#chatHeader').html(`
                        <img src="/images/users/avatar-${recipientId % 10 || 1}.jpg"
                            class="me-2 rounded"
                            height="36" />
                        <div class="d-none d-md-flex flex-column">
                            <h5 class="my-0 fs-16 fw-semibold">
                                <a data-bs-toggle="offcanvas"
                                href="#user-profile"
                                class="text-dark">${recipient.name}</a>
                            </h5>
                            <p class="mb-0 text-success fw-medium">
                                ${recipient.phone}
                            </p>
                        </div>
                    `);

                const el = $('#chatConversation')[0];
                el.scrollTop = el.scrollHeight;

            }).always(() => {
                loading = false;
                $('#chatConversationLoader').hide();
                $('#tempMessageLoader').remove(); // remove temporary loader
            });
        }, 0);
    }

    $('#chatConversation').on('scroll', function () {
        if ($(this).scrollTop() !== 0 || loading || !hasMore || !oldestMessageId) return;

        loading = true;

        const container = $('#chatConversation');

        // Prepend a loader at the top
        if ($('#scrollLoader').length === 0) {
            container.prepend(`
                <div id="scrollLoader" class="text-center py-2">
                    <div class="spinner-border text-primary spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
        }

        // Use setTimeout to let the loader render before AJAX
        setTimeout(() => {
            $.post("{{ route('getChatBoxMessages') }}", {
                recipient_id: currentRecipientId,
                recipient_type: currentRecipientType,
                list_ref: currentListRef,
                before_id: oldestMessageId,
                _token: '{{ csrf_token() }}'
            }, function (response) {
                const messages = response.messages || [];
                if (!messages.length) {
                    hasMore = false;
                    return;
                }

                oldestMessageId = messages[0].id;

                let html = '';
                messages.forEach(msg => {
                    html += renderMessage(msg, activeRecipient);
                });

                const prevHeight = container[0].scrollHeight;
                container.prepend(html);
                container[0].scrollTop = container[0].scrollHeight - prevHeight;

            }).always(() => {
                loading = false;
                $('#scrollLoader').remove(); // remove loader after data is loaded
            });
        }, 0);
    });

    function renderMessage(message, recipient) {
        const isSender = message.is_sender;

        // Use recipient fallback if available
        const recipientId = recipient?.id || 1;

        // Avatar
        const avatarIndex = (message.user_id || message.id || 1) % 10 || 1;
        const avatar = isSender
            ? '/images/users/avatar-1.jpg'
            : `/images/users/avatar-${avatarIndex}.jpg`;

        let sendStatusIcon = '';
        if (message.is_sent === 1) {
            sendStatusIcon = 'Sent <i class="ri-check-double-line fs-18 text-info"></i>'; // Sent
        } else if (message.is_sent === 2) {
            sendStatusIcon = 'Failed <i class="ri-close-circle-line fs-18 text-danger" title="Failed"></i>'; // Failed
        } else {
            sendStatusIcon = '<i class="ri-check-line fs-18 text-muted"></i>'; // Pending / Not sent
        }


        const receiveStatusIcon = message.is_read
            ? '<i class="ri-check-double-line fs-18 text-info"></i>'
            : '<i class="ri-check-line fs-18 text-muted"></i>';

        if (message.status === 'Sent') {
            return `
                <li class="d-flex gap-2 clearfix justify-content-end odd">
                    <div class="chat-conversation-text ms-0">
                            <div>
                                <p class="mb-2"><span class="text-dark fw-medium me-1">${isSender ? 'You' : message.user_name}</span> ${message.created_at}</p>
                            </div>
                        <div class="chat-ctext-wrap">
                            <p>${message.message}</p>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">
                                ${message.phone_number || ''} | 
                            </small>
                            ${sendStatusIcon}
                        </div>
                    </div>
                    <img src="${avatar}" class="avatar rounded-circle">
                </li>
            `;
        }

        return `
            <li class="d-flex gap-2 clearfix justify-content-start even">
                <div class="chat-avatar text-center">
                    <img src="/images/users/avatar-${recipientId % 10}.jpg" alt="avatar-${recipientId % 10}" class="avatar rounded-circle">
                </div>
                <div class="chat-conversation-text ms-0">
                    <div>
                        <p class="mb-2"><span class="text-dark fw-medium me-1"><em class="text-muted">Replied</em> </span> ${message.created_at}</p>
                    </div>
                    <div class="chat-ctext-wrap">
                        <p>${message.message}</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">
                            ${message.phone_number || ''} | Seen
                        </small>
                        ${receiveStatusIcon}
                    </div>
                </div>
            </li>
        `;
    }

</script>

@endsection
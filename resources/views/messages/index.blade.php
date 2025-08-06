@extends('layouts.vertical', ['title' => 'Messages', 'subTitle' => 'Communication'])

@section('css')
@vite(['node_modules/swiper/swiper-bundle.min.css'])
<style>
    .chat-conversation-list { max-height: 500px; overflow-y: auto; }
    .chat-setting-height { max-height: 400px; overflow-y: auto; }
    .chat-box { display: flex; flex-direction: column; height: 75vh; }
    .chat-conversation-list { flex-grow: 1; }
    .nav-link { cursor: pointer; }
    .applicant-chat:hover, .user-chat:hover { background-color: #f8f9fa; }
    .chatbox-height { max-height: calc(100vh - 255px) !important; }
    #chatList, #userList { display: block; min-height: 55.6vh; } /* Ensure visibility and minimum height */
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

    let isLoadingApplicants = false;
    let isLoadingUsers = false;
    let currentRecipientId = null;
    let currentRecipientType = null; // 'applicant' or 'user'

    function loadApplicants(page = 1) {
        if (isLoadingApplicants) return; // Prevent multiple simultaneous requests
        isLoadingApplicants = true;
        $('#chatListLoader').show(); // Show loader

        $.ajax({
            url: '{{ route("getApplicantsForMessage") }}',
            method: 'GET',
            data: { page: page, per_page: 20},
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                console.log('Applicants Response (Page ' + page + '):', response); // Debug: Log response
                let chatListHtml = '';
                let unreadCount = parseInt($('#unreadCount').text()) || 0;

                if (!response.data || !Array.isArray(response.data)) {
                    console.error('Invalid response data:', response);
                    isLoadingApplicants = false;
                    $('#chatListLoader').hide();
                    return;
                }

                response.data.forEach(applicant => {
                    // Render all applicants, even without messages
                    const lastMessage = applicant.last_message ? applicant.last_message.message : 'No messages';
                    const time = applicant.last_message ? applicant.last_message.time : '';
                    const unread = applicant.last_message ? applicant.last_message.unread_count || 0 : 0;
                    unreadCount += unread;
                    chatListHtml += `
                        <div class="d-flex flex-column h-100 border-bottom">
                            <a href="#!" class="d-block applicant-chat" data-ref-name="applicant-chat" data-recipient-id="${applicant.id}" data-recipient-type="applicant">
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
                });

                console.log('Generated HTML (Page ' + page + '):', chatListHtml); // Debug: Log generated HTML

                // Use DocumentFragment for efficient DOM updates
                const fragment = document.createDocumentFragment();
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = chatListHtml;
                while (tempDiv.firstChild) {
                    fragment.appendChild(tempDiv.firstChild);
                }

                // Append records (never clear)
                $('#chatList').append(fragment);
                $('#unreadCount').text(unreadCount);

                // Store next page if more data exists
                $('#chatList').data('next-page', response.has_more ? response.next_page : null);
                console.log('Stored next-page for applicants:', $('#chatList').data('next-page')); // Debug: Log next-page

                // Debug: Verify DOM update
                console.log('chatList children:', $('#chatList').children().length);
            },
            error: function(xhr) {
                console.error('Error loading applicants (Page ' + page + '):', xhr);
            },
            complete: function() {
                isLoadingApplicants = false;
                $('#chatListLoader').hide(); // Hide loader
                // Reinitialize SimpleBar
                const chatListSimpleBar = new SimpleBar(document.getElementById('chatList'));
                chatListSimpleBar.recalculate();
            }
        });
    }

    function loadUsers(page = 1) {
        if (isLoadingUsers) return;
        isLoadingUsers = true;
        $('#userListLoader').show(); // Show loader

        $.ajax({
            url: "{{ route('getUserChats') }}",
            method: 'GET',
            data: { page: page, per_page: 20 },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                console.log('Users Response (Page ' + page + '):', response); // Debug: Log response
                let userListHtml = '';

                if (!response.data || !Array.isArray(response.data)) {
                    console.error('Invalid response data:', response);
                    isLoadingUsers = false;
                    $('#userListLoader').hide();
                    return;
                }

                response.data.forEach(user => {
                    userListHtml += `
                        <div class="d-flex flex-column h-100 border-bottom">
                            <a href="#!" class="d-block user-chat" data-ref-name="user-chat" data-recipient-id="${user.id}" data-recipient-type="applicant">
                                <div class="d-flex align-items-center p-2 mb-1 rounded">
                                    <div class="position-relative">
                                        <img src="/images/users/avatar-${user.id % 10 || 1}.jpg" alt="" class="avatar rounded-circle flex-shrink-0">
                                        <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-light border-2 rounded-circle">
                                            <span class="visually-hidden">New alerts</span>
                                        </span>
                                    </div>
                                    <div class="d-block ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h5 class="mb-0">${user.name}</h5>
                                            <div>
                                                <p class="text-muted fs-13 mb-0">${user.last_message ? user.last_message.time : ''}</p>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="mb-0 text-muted d-flex align-items-center gap-1">${user.last_message.message}</p>
                                            ${user.last_message && user.last_message.unread_count > 0 ? `<span class="badge bg-danger badge-pill">${user.last_message.unread_count}</span>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        `;
                });

                console.log('Generated User HTML (Page ' + page + '):', userListHtml); // Debug: Log generated HTML

                // Use DocumentFragment for efficient DOM updates
                const fragment = document.createDocumentFragment();
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = userListHtml;
                while (tempDiv.firstChild) {
                    fragment.appendChild(tempDiv.firstChild);
                }

                // Append records (never clear)
                $('#userList').append(fragment);

                // Store next page if more data exists
                $('#userList').data('next-page', response.has_more ? response.next_page : null);
                console.log('Stored next-page for users:', $('#userList').data('next-page')); // Debug: Log next-page

                // Debug: Verify DOM update
                console.log('userList children:', $('#userList').children().length);
            },
            error: function(xhr) {
                console.error('Error loading users (Page ' + page + '):', xhr);
            },
            complete: function() {
                isLoadingUsers = false;
                $('#userListLoader').hide(); // Hide loader
                // Reinitialize SimpleBar
                const userListSimpleBar = new SimpleBar(document.getElementById('userList'));
                userListSimpleBar.recalculate();
            }
        });
    }

    function loadMessages(recipientId, recipientType, list_ref) {
        $('#chatConversationLoader').show(); // Show loader before AJAX
        $('#noChatMessage').hide(); // Hide no chat message
        
        $.ajax({
            url: "{{ route('getChatBoxMessages') }}",
            method: 'POST',
            data: {
                recipient_id: recipientId,
                recipient_type: recipientType,
                list_ref: list_ref,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                const messages = response.messages;
                const recipient = response.recipient;
                let messagesHtml = '';
                let recipientPhone = recipient.phone ?? '';

                if (!messages || messages.length === 0) {
                    // Show "No Chat Available" message if no messages
                    $('#noChatMessage').show();
                } else {
                    messages.forEach(message => {
                        const isSender = message.is_sender;
                        const avatar = isSender ? '/images/users/avatar-1.jpg' : `/images/users/avatar-${recipient.id % 10 || 1}.jpg`;
                        const sendStatusIcon = message.is_sent ? '<i class="ri-check-double-line fs-18 text-info"></i>' : '<i class="ri-check-line fs-18 text-muted"></i>';
                        const receiveStatusIcon = message.is_read ? '<i class="ri-check-double-line fs-18 text-info"></i>' : '<i class="ri-check-line fs-18 text-muted"></i>';
                    
                        if (message.status == 'Sent') {
                            messagesHtml += `
                                <li class="d-flex gap-2 clearfix justify-content-end odd">
                                    <div class="chat-conversation-text ms-0">
                                        <div>
                                            <p class="mb-2"><span class="text-dark fw-medium me-1">${isSender ? 'You' : message.user_name}</span> ${message.created_at}</p>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <div class="chat-ctext-wrap">
                                                <p>${message.message}</p>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end align-items-center"><small class="text-muted">${message.phone_number || ''} | ${message.status}</small>&nbsp;&nbsp;<span>${sendStatusIcon}</span></div>
                                    </div>
                                    <div class="chat-avatar text-center">
                                        <img src="${avatar}" alt="" class="avatar rounded-circle">
                                    </div>
                                </li>
                            `;
                        } else {
                            messagesHtml += `
                                <li class="d-flex gap-2 clearfix justify-content-start even">
                                    <div class="chat-avatar text-center">
                                        <img src="/images/users/avatar-${recipient.id % 10 || 1}.jpg" alt="avatar-${recipient.id % 10 || 1}" class="avatar rounded-circle">
                                    </div>
                                    <div class="chat-conversation-text ms-0">
                                        <div>
                                            <p class="mb-2"><span class="text-dark fw-medium me-1"><em class="text-muted">Replied</em> </span> ${message.created_at}</p>
                                        </div>
                                        <div class="d-flex align-items-start">
                                            <div class="chat-ctext-wrap">
                                                <p>${message.message}</p>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end align-items-center"><small class="text-muted">${message.phone_number || ''} | Seen </small>&nbsp;&nbsp;<span>${receiveStatusIcon}</span></div>
                                    </div>
                                </li>
                            `;
                        }
                    });
                }

                $('#chatConversation').html(messagesHtml);
                $('#recipientPhone').val(recipientPhone);
                $('#chatHeader').html(`
                    <img src="/images/users/avatar-${recipient.id % 10 || 1}.jpg" class="me-2 rounded" height="36" alt="avatar-${recipient.id % 10 || 1}" />
                    <div class="d-none d-md-flex flex-column">
                        <h5 class="my-0 fs-16 fw-semibold">
                            <a data-bs-toggle="offcanvas" href="#user-profile" class="text-dark">${recipient.name}</a>
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

    $(document).ready(function() {
        // Initialize SimpleBar
        const chatListSimpleBar = new SimpleBar(document.getElementById('chatList'));
        const userListSimpleBar = new SimpleBar(document.getElementById('userList'));

        // Load initial lists
        loadApplicants(1);
        loadUsers(1);

        // Infinite scroll for applicants
        $(chatListSimpleBar.getScrollElement()).on('scroll', debounce(function() {
            alert("this");
            if (isLoadingApplicants) return;
            const scrollElement = chatListSimpleBar.getScrollElement();
            const scrollPosition = scrollElement.scrollTop + scrollElement.clientHeight;
            const scrollThreshold = scrollElement.scrollHeight - 100; // Increased threshold for reliability
            console.log('Scroll Position:', scrollPosition, 'Scroll Threshold:', scrollThreshold, 'Scroll Height:', scrollElement.scrollHeight); // Debug: Log scroll metrics
            if (scrollPosition >= scrollThreshold) {
                let nextPage = $('#chatList').data('next-page');
                console.log('Triggering loadApplicants for page:', nextPage); // Debug: Log page trigger
                if (nextPage) {
                    loadApplicants(nextPage);
                }
            }
        }, 100)); // Reduced debounce to 100ms for faster response

        // Infinite scroll for users
        $(userListSimpleBar.getScrollElement()).on('scroll', debounce(function() {
            if (isLoadingUsers) return;
            const scrollElement = userListSimpleBar.getScrollElement();
            const scrollPosition = scrollElement.scrollTop + scrollElement.clientHeight;
            const scrollThreshold = scrollElement.scrollHeight - 100; // Increased threshold
            console.log('Scroll Position (Users):', scrollPosition, 'Scroll Threshold (Users):', scrollThreshold, 'Scroll Height (Users):', scrollElement.scrollHeight); // Debug: Log scroll metrics
            if (scrollPosition >= scrollThreshold) {
                let nextPage = $('#userList').data('next-page');
                console.log('Triggering loadUsers for page:', nextPage); // Debug: Log page trigger
                if (nextPage) {
                    loadUsers(nextPage);
                }
            }
        }, 100)); // Reduced debounce to 100ms

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

        // Click handler for recipient selection
        $(document).on('click', '.applicant-chat, .user-chat', function(e) {
            e.preventDefault();

            // Determine clicked class
            const list_ref = $(this).data('ref-name');
            currentRecipientId = $(this).data('recipient-id');
            currentRecipientType = $(this).data('recipient-type');

            $('#recipientId').val(currentRecipientId);
            $('#recipientType').val(currentRecipientType);

            loadMessages(currentRecipientId, currentRecipientType, list_ref);
        });

        // Search functionality
        $('#searchApplicants').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            if (currentRecipientType === 'applicant' || !currentRecipientType) {
                $('#chatList .applicant-chat').each(function() {
                    const name = $(this).find('h5').text().toLowerCase();
                    $(this).parent().toggle(name.includes(searchTerm));
                });
            } else if (currentRecipientType === 'user') {
                $('#userList .user-chat').each(function() {
                    const name = $(this).find('h5').text().toLowerCase();
                    $(this).parent().toggle(name.includes(searchTerm));
                });
            }
        });

        // Tab switch handler
        $('.nav-link').on('click', function() {
            currentRecipientType = $(this).attr('href').substring(1).split('-')[0]; // Extract 'chat' or 'contact'
            currentRecipientId = null;
            $('#recipientId').val('');
            $('#recipientType').val('');
            $('#chatConversation').html('');
            $('#chatHeader').html('');
            if (currentRecipientType === 'chat') {
                loadApplicants(1);
            } else if (currentRecipientType === 'contact') {
                loadUsers(1);
            }
        });
    });
</script>
@endsection
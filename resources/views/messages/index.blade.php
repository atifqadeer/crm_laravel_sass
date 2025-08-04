@extends('layouts.vertical', ['title' => 'Messages', 'subTitle' => 'Real Estate'])

@section('css')
@vite(['node_modules/swiper/swiper-bundle.min.css'])
<style>
    .chat-conversation-list { max-height: 500px; overflow-y: auto; }
    .chat-setting-height { max-height: 400px; overflow-y: auto; }
    .chat-box { display: flex; flex-direction: column; height: 600px; }
    .chat-conversation-list { flex-grow: 1; }
    .nav-link { cursor: pointer; }
    .applicant-chat:hover, .user-chat:hover { background-color: #f8f9fa; }
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
                        <a href="#chat-list" data-bs-toggle="tab" aria-expanded="false" class="nav-link active">Chats</a>
                    </li>
                    <li class="nav-item">
                        <a href="#contact-list" data-bs-toggle="tab" aria-expanded="true" class="nav-link">Users</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane show active" id="chat-list">
                        <div class="px-2 mb-3 chat-setting-height" data-simplebar id="chatList">
                            <!-- Chat list will be loaded here via AJAX -->
                        </div>
                    </div>
                    <div class="tab-pane" id="contact-list">
                        <div class="px-2 mb-3 chat-setting-height" data-simplebar id="userList">
                            <!-- User list will be loaded here via AJAX -->
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
                    <!-- Messages will be loaded here via AJAX -->
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

<script>
    $(document).ready(function() {
        let currentRecipientId = null;
        let currentRecipientType = null; // 'applicant' or 'user'

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
                        if (applicant.last_message) {
                            const lastMessage = applicant.last_message.message;
                            const time = applicant.last_message.time;
                            const unread = applicant.last_message.unread_count || 0;
                            unreadCount += unread;
                            chatListHtml += `
                                <div class="d-flex flex-column h-100 border-bottom">
                                    <a href="#!" class="d-block applicant-chat" data-recipient-id="${applicant.id}" data-recipient-type="applicant">
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
                    $('#chatList').html(chatListHtml);
                    $('#unreadCount').text(unreadCount);

                    // Initialize Swiper
                    new Swiper('.mySwiper', {
                        slidesPerView: 'auto',
                        spaceBetween: 10,
                    });
                },
                error: function(xhr) {
                    console.error('Error loading applicants:', xhr);
                }
            });
        }

        // Load users
        function loadUsers() {
            $.ajax({
                url: "{{ route('getUserChats') }}",
                method: 'GET',
                success: function(response) {
                    let userListHtml = '';
                    response.forEach(user => {
                        userListHtml += `
                            <div class="d-flex flex-column h-100 border-bottom">
                                <a href="#!" class="d-block user-chat" data-recipient-id="${user.id}" data-recipient-type="user">
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
                                                <p class="mb-0 text-muted d-flex align-items-center gap-1">${user.last_message ? user.last_message.message : 'No messages'}</p>
                                                ${user.last_message && user.last_message.unread_count > 0 ? `<span class="badge bg-danger badge-pill">${user.last_message.unread_count}</span>` : ''}
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            `;
                        });

                        $('#userList').html(userListHtml);
                    },
                    error: function(xhr) {
                        console.error('Error loading users:', xhr);
                    }
            });
        }

        // Load messages for a specific recipient
        function loadMessages(recipientId, recipientType) {
            $.ajax({
                url: "{{ route('getChatBoxMessages') }}",
                method: 'POST',
                data: {
                    recipient_id: recipientId,
                    recipient_type: recipientType,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    const messages = response.messages;
                    const recipient = response.recipient;
                    let messagesHtml = '';
                    let recipientPhone = '';

                    messages.forEach(message => {
                        const isSender = message.is_sender;
                        const avatar = isSender ? '/images/users/avatar-1.jpg' : `/images/users/avatar-${recipient.id % 10 || 1}.jpg`;
                        const sendStatusIcon = message.is_sent ? '<i class="ri-check-double-line fs-18 text-info"></i>' : '<i class="ri-check-line fs-18 text-muted"></i>';
                        const receiveStatusIcon = message.is_read ? '<i class="ri-check-double-line fs-18 text-info"></i>' : '<i class="ri-check-line fs-18 text-muted"></i>';
                        recipientPhone = message.phone_number;
                        if(message.status == 'Sent'){
                            messagesHtml += `
                                <li class="d-flex gap-2 clearfix justify-content-end odd">
                                    <div class="chat-avatar text-center">
                                        <img src="${avatar}" alt="" class="avatar rounded-circle">
                                    </div>
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
                                </li>
                            `;
                        }else{
                            messagesHtml += `
                                <li class="d-flex gap-2 clearfix justify-content-start even">
                                    <div class="chat-avatar text-center">
                                        <img src="/images/users/avatar-${recipient.id % 10 || 1}.jpg" alt="avatar-${recipient.id % 10 || 1}" class="avatar rounded-circle">
                                    </div>
                                    <div class="chat-conversation-text ms-0">
                                        <div>
                                            <p class="mb-2"><span class="text-dark fw-medium me-1">Dated: </span> ${message.created_at}</p>
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
                    // Refresh relevant list based on recipient type
                    if (currentRecipientType === 'applicant') {
                        loadApplicants();
                    } else if (currentRecipientType === 'user') {
                        loadUsers();
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
            currentRecipientId = $(this).data('recipient-id');
            currentRecipientType = $(this).data('recipient-type');
            $('#recipientId').val(currentRecipientId);
            $('#recipientType').val(currentRecipientType);
            loadMessages(currentRecipientId, currentRecipientType);
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
                loadApplicants();
            } else if (currentRecipientType === 'contact') {
                loadUsers();
            }
        });

        // Initial load
        loadApplicants();
        loadUsers();
    });
</script>
@endsection
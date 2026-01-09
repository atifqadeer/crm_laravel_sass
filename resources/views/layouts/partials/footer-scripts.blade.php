@yield('script')
@vite(['resources/js/app.js','resources/js/layout.js'])
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Only run if route is available
            if (window.laravelRoutes && window.laravelRoutes.unreadMessages) {
                fetchUnreadMessages();
                setInterval(fetchUnreadMessages, 20000);  // Every 20 seconds
            }
            if (window.laravelRoutes && window.laravelRoutes.unreadNotifications) {
                fetchUnreadNotifications();
                setInterval(fetchUnreadNotifications, 20000);  // Every 20 seconds
            }
        });
    // Function to fetch unread messages
    function fetchUnreadMessages() {
        $.ajax({
            url: window.laravelRoutes.unreadMessages,
            method: 'GET',
            success: function (response) {
                console.log(response);  // Log the full response to verify the structure
                if (response.success) {
                    // Update unread count for messages
                    $('#unread-message-count').text(response.unread_count || 0);

                    // Clear the message list before populating new messages
                    $('#message-items').empty();

                    if (response.messages.length === 0) {
                        $('#message-items').append('<div class="text-center py-3 text-muted">No new messages</div>');
                    } else {
                        response.messages.forEach(function (message) {
                            const messagesIndexUrl = "/messages";
                            const html = `
                                <a href="${messagesIndexUrl}" class="dropdown-item py-3 border-bottom text-wrap">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <img src="${message.avatar}" class="img-fluid me-2 avatar-sm rounded-circle" alt="user-avatar" />
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-0">
                                                <span class="fw-medium">${message.user_name}</span><br>
                                                <span>${message.message}</span>
                                            </p>
                                            <small class="text-muted">${message.created_at}</small>
                                        </div>
                                    </div>
                                </a>`;
                            $('#message-items').append(html);
                        });
                    }
                } else {
                    console.log('Error fetching messages:', response.error);
                }
            },
            error: function (xhr) {
                console.log('AJAX error:', xhr.responseText);
            }
        });
    }

   // Global variable to track if the first notification has been checked
let firstNotificationChecked = false;
let unreadCheckInterval = null;

function fetchUnreadNotifications() {
    $.ajax({
        url: window.laravelRoutes.unreadNotifications,
        method: 'GET',
        success: function (response) {
            console.log(response);

            if (!response.success) {
                console.log('Error fetching notifications:', response.error);
                return;
            }

            // Update unread count UI
            $('#unread-notification-count').text(response.notifications.length || 0);
            $('#unread-notification-items').empty();

            if (response.notifications.length === 0) {
                $('#unread-notification-items')
                    .append('<div class="text-center py-3 text-muted">No new notifications</div>');

                $('#page-header-notifications-dropdown i')
                    .removeClass('unread-notifications-alert');
            } else {
                showNotificationBanner();

                // Loop through notifications and display them
                response.notifications.forEach(notification => {
                    const html = `
                        <a href="/notifications" class="dropdown-item py-3 border-bottom text-wrap"
                           data-notification-id="${notification.id}">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <iconify-icon icon="ic:round-notifications" class="fs-24 text-primary"></iconify-icon>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0">
                                        <span class="fw-medium">${notification.user_name}</span><br>
                                        <span>${notification.message}</span>
                                    </p>
                                    <small class="text-muted">${notification.created_at}</small>
                                </div>
                            </div>
                        </a>`;
                    $('#unread-notification-items').append(html);
                });

                $('#page-header-notifications-dropdown i')
                    .addClass('unread-notifications-alert');
            }

            // ----------------------------
            // 🔔 SWEETALERT LOGIC
            // ----------------------------
            if (response.unread_count > 0) {
                // Show alert with delay only the first time
                if (!firstNotificationChecked) {
                    firstNotificationChecked = true;
                    setTimeout(() => {
                        showSwalAlert(response.notifications[0]);
                    }, 2000); // Delay for 2 seconds (adjust as needed)
                } else {
                    showSwalAlert(response.notifications[0]);
                }

                // Start interval ONLY ONCE for fetching unread notifications
                if (!unreadCheckInterval) {
                    unreadCheckInterval = setInterval(() => {
                        fetchUnreadNotifications();
                    }, 2 * 60 * 1000); // Check every 2 minutes
                }
            }
        },
        error: function (xhr) {
            console.log('AJAX error:', xhr.responseText);
        }
    });
}

function showNotificationBanner() {
    // Show the notification banner
    $('#notification-banner').fadeIn();

    // Hide the banner after 5 seconds
    setTimeout(function () {
        $('#notification-banner').fadeOut();
    }, 5000);
}

function showSwalAlert(notification) {
    Swal.fire({
        title: 'New Notification!',
        text: notification.message,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Read Notifications',
        cancelButtonText: 'Reject',
        confirmButtonColor: '#28a745', // Green
        cancelButtonColor: '#dc3545',  // Red
        customClass: {
            confirmButton: 'btn text-white mt-2',
            cancelButton: 'btn text-white mt-2'
        },
        buttonsStyling: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/notifications/mark-as-read',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        window.location.href = '/notifications';
                    }
                }
            });
        }
    });
}


</script>
@yield('script-bottom')

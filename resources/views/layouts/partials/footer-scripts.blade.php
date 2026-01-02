@yield('script')
@vite(['resources/js/app.js','resources/js/layout.js'])
<script>
    console.log('Setting up periodic fetch for unread messages and notifications');
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

    // Function to fetch unread notifications
    function fetchUnreadNotifications() {
        $.ajax({
            url: window.laravelRoutes.unreadNotifications,
            method: 'GET',
            success: function (response) {
                console.log(response);  // Log the full response to verify the structure
                
                if (response.success) {
                    // Update unread count for notifications
                    $('#notification-count').text(response.unread_count || 0);

                    // Clear the notification list before populating new notifications
                    $('#notification-items').empty();

                    // Check if there are unread notifications
                    if (response.notifications.length === 0) {
                        $('#notification-items').append('<div class="text-center py-3 text-muted">No new notifications</div>');
                        // Remove the pulse animation if no unread notifications
                        // $('#page-header-notifications-dropdown').removeClass('unread-notifications');
                    } else {
                        response.notifications.forEach(function (notification) {
                            const html = `
                                <a href="/notifications" class="dropdown-item py-3 border-bottom text-wrap" data-bs-toggle="modal" data-bs-target="#notificationModal" data-notification-id="${notification.id}">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <!-- Use an Icon instead of an Image -->
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
                            $('#notification-items').append(html);
                        });

                        // Add the class to trigger animation (pulse)
                        // $('#page-header-notifications-dropdown').addClass('unread-notifications');
                    }
                } else {
                    console.log('Error fetching notifications:', response.error);
                }
            },
            error: function (xhr) {
                console.log('AJAX error:', xhr.responseText);
            }
        });
    }

</script>
@yield('script-bottom')

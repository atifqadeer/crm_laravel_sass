@yield('script')
 <script>
$(document).ready(function () {
    function fetchNotifications() {
        $.ajax({
            url: "{{ route('unread-messages') }}",
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    // Update badge count
                    $('#unread-count').text(response.unread_count || 0);

                    // Clear existing notifications
                    $('#notification-items').empty();

                    // Populate notifications
                    if (response.messages.length === 0) {
                        $('#notification-items').append(
                            '<div class="text-center py-3 text-muted">No new notifications</div>'
                        );
                    } else {
                        response.messages.forEach(function (message) {
                            const notificationHtml = `
                                <a href="javascript:void(0);" class="dropdown-item py-3 border-bottom text-wrap">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <img src="${message.avatar}" class="img-fluid me-2 avatar-sm rounded-circle" alt="user-avatar" />
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-0">
                                                <span class="fw-medium">${message.user_name}</span>
                                                <span>${message.message}</span>
                                            </p>
                                            <small class="text-muted">${message.created_at}</small>
                                        </div>
                                    </div>
                                </a>`;
                            $('#notification-items').append(notificationHtml);
                        });
                    }
                } else {
                    console.error('Error fetching notifications:', response.error);
                }
            },
            error: function (xhr) {
                console.error('AJAX error:', xhr.responseText);
            }
        });
    }

    // Fetch notifications on page load
    fetchNotifications();

    // Optional: Poll for new notifications every 30 seconds
    setInterval(fetchNotifications, 30000);

   
});
</script>
@vite(['resources/js/app.js','resources/js/layout.js'])
@yield('script-bottom')

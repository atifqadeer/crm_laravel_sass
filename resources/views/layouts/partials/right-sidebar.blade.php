<div class="offcanvas offcanvas-end border-0" tabindex="-1" id="chatOffcanvas" style="width: 20%; height: 700px;">
    <div class="offcanvas-header bg-primary text-white">
        <div class="d-flex align-items-center">
            <img src="{{ asset('images/users/boy.png') ?? asset('images/users/default.jpg') }}"
                class="rounded-circle me-2" width="40" height="40" id="chatUserAvatar">
            <div>
                <h5 class="mb-0" id="chatUserName"></h5>
                <small id="chatUserPhone"></small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
            aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0 d-flex flex-column">
        <!-- Messages Container -->
        <div class="flex-grow-1 p-3 overflow-auto" id="messagesContainer" style="background-color: #f8f9fa;">
            <!-- Messages will be loaded here -->
            <div class="text-center py-5" id="noMessages">
                <i class="ri-chat-3-line fs-1 text-muted"></i>
                <p class="text-muted">No messages yet</p>
            </div>
        </div>

        <!-- Message Input -->
        <div class="border-top p-3 bg-white">
            <form id="messageForm">
                <input type="hidden" id="applicantId">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Type your message..." id="messageInput"
                        required>
                    <button class="btn btn-primary" type="submit">
                        <i class="ri-send-plane-2-fill"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="offcanvas offcanvas-end border-0" tabindex="-1" id="emailOffcanvas" style="width: 40%; height: 700px;">
    <div class="offcanvas-header bg-primary text-white">
        <div class="d-flex align-items-center">
            <img src="{{ asset('images/users/boy.png') ?? asset('images/users/default.jpg') }}"
                class="rounded-circle me-2" width="40" height="40" id="chatUserAvatar">
            <div>
                <h5 class="mb-0" id="chatUserName"></h5>
                <small id="chatUserPhone"></small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
            aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0 d-flex flex-column">
        <!-- Messages Container -->
        <div class="flex-grow-1 p-3 overflow-auto" id="messagesContainer" style="background-color: #f8f9fa;">
            <!-- Messages will be loaded here -->
            <div class="text-center py-5" id="noMessages">
                <i class="ri-chat-3-line fs-1 text-muted"></i>
                <p class="text-muted">No messages yet</p>
            </div>
        </div>

        <!-- Message Input -->
        <div class="border-top p-3 bg-white">
            <form id="messageForm">
                <input type="hidden" id="applicantId">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Type your message..." id="messageInput"
                        required>
                    <button class="btn btn-primary" type="submit">
                        <i class="ri-send-plane-2-fill"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Custom styling for right offcanvas */
    #chatOffcanvas.offcanvas-end {
        position: fixed;
        bottom: 0;
        top: auto;
        right: 0;
        width: 20%;
        max-width: 20%;
        transform: translateX(100%);
        border-left: 1px solid rgba(0, 0, 0, .1);
        box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1), 0 -5px 15px rgba(0, 0, 0, 0.1);
        border-radius: 10px 0 0 0;
    }

    #chatOffcanvas.offcanvas-end.show {
        transform: translateX(0);
    }

    /* Message styling */
    .message {
        max-width: 80%;
        margin-bottom: 15px;
        padding: 10px 15px;
        border-radius: 18px;
    }

    .incoming {
        background-color: var(--bs-primary);
        color: #ffffff;
        border-radius: 10px 0 10px 10px;
        padding: 7px 15px;
    }

    .outgoing {
        background-color: var(--bs-secondary);
        color: #ffffff;
        border-radius: 0 10px 10px 10px;
        padding: 7px 15px;
    }

    .message-time {
        font-size: 0.75rem;
        margin-top: 5px;
        display: block;
        text-align: right;
        opacity: 0.7;
    }

    /* Scrollbar styling */
    #messagesContainer::-webkit-scrollbar {
        width: 6px;
    }

    #messagesContainer::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    #messagesContainer::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        #chatOffcanvas.offcanvas-end {
            width: 30%;
            max-width: 30%;
        }
    }

    @media (max-width: 768px) {
        #chatOffcanvas.offcanvas-end {
            width: 50%;
            max-width: 50%;
        }
    }

    @media (max-width: 576px) {
        #chatOffcanvas.offcanvas-end {
            width: 80%;
            max-width: 80%;
        }
    }
</style>

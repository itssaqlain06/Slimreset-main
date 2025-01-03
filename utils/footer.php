<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 footer-copyright d-flex flex-wrap align-items-center justify-content-between">
                <p class="mb-0 f-w-600">Copyright 2024 © Slim Reset. </p>
            </div>
        </div>
    </div>
</footer>


<script>
    let ws = new WebSocket('ws://localhost:8080?from_user_id=' + <?php echo $user_one_id; ?> + '&to_user_id=' + <?php echo $user_two_id; ?>);

    let userOneId = <?php echo $user_one_id; ?>;
    let userTwoId = <?php echo $user_two_id; ?>;
    let loggedInUserRole = "<?php echo $login_user_role ?>";
    let isInMessageTab = false;
    const currentUrl = window.location.pathname;

    ws.onopen = function() {
        console.log('WebSocket connection opened.');
    };

    document.addEventListener("DOMContentLoaded", function() {
        var tab = document.getElementById("successful-wizard-tab");
        if (tab) {
            tab.addEventListener("click", function() {
                let chatBox = document.getElementById('chat-box');
                chatBox.scrollTop = chatBox.scrollHeight;

                $.ajax({
                    type: "GET",
                    url: "utils/chatAllowed.php?id=<?php echo $user_two_id ?>",
                    dataType: "json",
                    success: function(response) {
                        if (response.status === 'success') {
                            let chatBox = document.getElementById('chat-box');
                            chatBox.scrollTop = chatBox.scrollHeight;
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'Ok'
                            }).then(() => {
                                var wizardInfoTab = document.getElementById("wizard-info-tab");
                                if (wizardInfoTab) {
                                    wizardInfoTab.click();
                                }
                            });
                        }
                    }
                });
                resetNotifications();
                isInMessageTab = true;
            });

            tab.addEventListener("click", function() {
                let from_user_id = <?php echo $user_one_id; ?>;
                let to_user_id = <?php echo $user_two_id; ?>;

                markMessagesAsRead(from_user_id, to_user_id);
            });
        }

        let activeTab = null;

        window.onfocus = function() {
            if (document.getElementById("successful-wizard-tab").classList.contains("active")) {
                isInMessageTab = true;
                markMessagesAsRead(userOneId, userTwoId);
                resetNotifications();
            }
        };

        window.onblur = function() {
            isInMessageTab = false;
        };

    });

    // Displays the conversation messages
    function renderMessage(data, isOwnMessage) {
        let chatBox = document.getElementById('chat-box');

        // Create a new message div
        let newMessage = document.createElement('div');
        newMessage.className = isOwnMessage ? 'message user1' : 'message user2';

        // Message content
        let messageText = data.message || "No message content";

        // Format timestamp
        let timestamp = new Date(data.sent_at).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        }) || "Unknown time";

        // Render the message HTML
        newMessage.innerHTML =
            `<div class="message-content">
            <p>${messageText}</p>
            <div class="timestamp">${timestamp}</div>
        </div>`;

        // Append the new message to the chat box
        chatBox.appendChild(newMessage);

        // Scroll to the bottom of the chat box
        chatBox.scrollTop = chatBox.scrollHeight;
    }


    function resetNotifications() {
        notificationCount = 0;
        updateNotificationCounter();

        const notificationList = document.getElementById('notification-list');
        notificationList.innerHTML = '';

        checkNotifications();
    }

    let notificationCount = 0;
    const MAX_NOTIFICATIONS_DISPLAYED = 99;

    function addNotificationToPanel(notification) {
        checkNotifications();

        notificationCount++;
        updateNotificationCounter();

        const notificationList = document.getElementById('notification-list');

        const notificationItem = document.createElement('li');
        notificationItem.classList.add('custom-notification-item');
        notificationItem.id = 'notification-' + notification.message_id;
        notificationItem.innerHTML =
            `<div class="d-flex justify-content-between align-items-center">
            <div class="custom-notification-content">
                <div class="d-flex align-items-center gap-2">
                    <img src="${notification.sender_profile_image}" class="custom-avatar" alt="Avatar">
                    <span class="sender-name">${notification.sender_name}</span>
                </div>
                <div class="custom-message mt-2">
                    <span>${notification.message}</span>
                </div>
            </div>
            <i class="fa fa-times" aria-hidden="true" style="cursor: pointer;" onclick="dismissMessage(${notification.message_id})"></i>
        </div>`;

        // Prepend the notification (add it to the top)
        notificationList.insertBefore(notificationItem, notificationList.firstChild);
    }

    function checkNotifications() {
        const notificationList = document.getElementById('notification-list');
        const noUnreadMsg = document.querySelector('.no-unread-msg');

        if (notificationList.childElementCount === 0) {
            if (!noUnreadMsg) {
                const messageItem = document.createElement('li');
                messageItem.classList.add('no-unread-msg');
                messageItem.innerHTML =
                    `<div class="d-flex justify-content-center">
                    <span>No unread messages</span>
                </div>`;
                notificationList.appendChild(messageItem);
            }
        } else if (noUnreadMsg) {
            noUnreadMsg.remove();
        }
    }

    function updateNotificationCounter() {
        const counterElement = document.getElementById('notification-counter');

        if (notificationCount > 0) {
            counterElement.style.display = 'inline';
            counterElement.textContent = notificationCount > MAX_NOTIFICATIONS_DISPLAYED ? '99+' : notificationCount;
        } else {
            counterElement.style.display = 'none';
        }
    }

    // Reset counter and remove all notifications when marked as read
    function resetNotificationCounter() {
        notificationCount = 0;
        updateNotificationCounter();
    }


    function removeFirstDots(path) {
        if (path.startsWith('../')) {
            return path.substring(3);
        }
        return path;
    }

    const DEFAULT_IMAGE_URL = 'https://avatar.iran.liara.run/public/33';

    let notificationsArray = [];
    ws.onmessage = function(event) {
        let data = JSON.parse(event.data);
        if (Array.isArray(data)) {
            data.forEach(msg => {
                if (data.is_read === 0 && data.receiver_id === userOneId) {
                    notificationsArray.push(data)
                    processNotifications(data);
                }
                processMessage(msg);
            });
        } else {
            if (data.is_read === 0 && data.receiver_id === userOneId) {
                notificationsArray.push(data)
                processNotifications(data);
            }
            processMessage(data);
        }
    };

    function processMessage(data) {
        if ((data.sender_id === userOneId && data.receiver_id === userTwoId) ||
            (data.sender_id === userTwoId && data.receiver_id === userOneId)) {
            window.location.pathname.includes("summary.php") && renderMessage(data, data.sender_id === userOneId);
        }
    }

    function processNotifications(data) {
        if (loggedInUserRole === "coach") {
            if (!isInMessageTab) {
                addNotificationToPanel({
                    is_read: data.is_read,
                    sender_profile_image: removeFirstDots(data.sender_profile_image) || DEFAULT_IMAGE_URL,
                    sender_name: data.sender_name,
                    message: data.message,
                    sent_at: data.sent_at,
                    message_id: data.message_id
                });
            }
        } else {
            if (data.receiver_id === userOneId && !isInMessageTab) {
                addNotificationToPanel({
                    is_read: data.is_read,
                    sender_profile_image: removeFirstDots(data.sender_profile_image) || DEFAULT_IMAGE_URL,
                    sender_name: data.sender_name,
                    message: data.message,
                    sent_at: data.sent_at,
                    message_id: data.message_id
                });
            }
        }
    }

    function sendMessage() {
        let messageInput = document.getElementById('message-input').value;

        // const uploadedFiles = pond.getFiles();
        // console.log("Uploaded files:", uploadedFiles)

        // const imagePaths = uploadedFiles.map(file => {
        //     let filename = file.filename;
        //     let extension = filename.split('.').pop();
        //     let sanitizedFilename = filename.replace(/\s+/g, '-');
        //     let uniqueName = sanitizedFilename + Date.now() + '.' + extension;

        //     return uniqueName;
        // });


        // if (messageInput.trim() !== '' || imagePaths.length > 0) {
        //     let messageData = {
        //         from_user_id: userOneId,
        //         to_user_id: userTwoId,
        //         message: messageInput,
        //         image: imagePaths,
        //         sent_at: new Date().toISOString()
        //     };
        //     ws.send(JSON.stringify(messageData));

        //     let chatBox = document.getElementById('chat-box');
        //     chatBox.scrollTop = chatBox.scrollHeight;

        //     document.getElementById('message-input').value = '';
        // }

        if (messageInput.trim() !== '') {
            let messageData = {
                from_user_id: userOneId,
                to_user_id: userTwoId,
                message: messageInput,
                sent_at: new Date().toISOString()
            };
            ws.send(JSON.stringify(messageData));

            let chatBox = document.getElementById('chat-box');
            chatBox.scrollTop = chatBox.scrollHeight;

            document.getElementById('message-input').value = '';
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        checkNotifications();
    });

    if (currentUrl.includes("summary.php")) {
        document.getElementById('send-button').onclick = function() {
            sendMessage();
        };
        document.getElementById('upload-button').onclick = function() {
            sendMessage();
        };
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            sendMessage();
        }
    })

    function markMessagesAsRead(fromUserId, toUserId) {
        $.ajax({
            type: "POST",
            url: "utils/markAsRead.php",
            data: {
                from_user_id: fromUserId,
                to_user_id: toUserId
            },
            success: function(response) {
                // console.log("Messages marked as read: ", response.message);
            },
            error: function(err) {
                // console.error("Error marking messages as read:", err);
            }
        });
    }

    function dismissMessage(id) {
        $.ajax({
            type: "POST",
            url: "../functions/coach/fetch_notifications.php",
            data: {
                id: id
            },
            success: function(response) {
                let jsonResponse;
                try {
                    jsonResponse = typeof response === "string" ? JSON.parse(response) : response;

                    if (jsonResponse.success) {
                        const notificationItem = document.getElementById('notification-' + id);
                        if (notificationItem) {
                            notificationItem.remove();
                            notificationCount--;
                            updateNotificationCounter();
                        }
                        checkNotifications();
                    } else {
                        console.error("Failed to dismiss the message:", jsonResponse.error);
                    }
                } catch (e) {
                    console.error("Error parsing JSON:", e, response);
                }
            },
            error: function(xhr, status, error) {
                console.error("Failed to dismiss the message:", error);
            }
        });
    }

    if (!currentUrl.includes('summary.php')) {
        localStorage.removeItem('activeTab');
    }
</script>

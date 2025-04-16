<?php
require_once 'functions.php';
requireLogin();

$user = getUserById($conn, $_SESSION['user_id']);
$connectionRequests = getConnectionRequests($conn, $_SESSION['user_id']);
$unreadMessages = getUnreadMessageCount($conn, $_SESSION['user_id']);

// Get user's chats
$chats = getUserChats($conn, $_SESSION['user_id']);

// Check if specific chat is selected
$selectedUserId = isset($_GET['user']) ? (int)$_GET['user'] : null;
$selectedUser = null;
$messages = [];

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $receiverId = (int)$_POST['receiver_id'];
    $messageContent = sanitizeInput($_POST['message']);
    
    if (!empty($messageContent)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $_SESSION['user_id'], $receiverId, $messageContent);
        
        if ($stmt->execute()) {
            // Refresh messages
            $messages = getMessages($conn, $_SESSION['user_id'], $receiverId);
            // Redirect to prevent form resubmission
            header("Location: messages.php?user=$receiverId");
            exit();
        }
    }
}

// If a user is selected, get their info and messages
if ($selectedUserId) {
    $selectedUser = getUserById($conn, $selectedUserId);
    
    if ($selectedUser) {
        // Check if they are connected
        $connected = areConnected($conn, $_SESSION['user_id'], $selectedUserId);
        
        if ($connected) {
            // Get messages between the two users
            $messages = getMessages($conn, $_SESSION['user_id'], $selectedUserId);
            
            // Mark messages from selected user as read
            markMessagesAsRead($conn, $selectedUserId, $_SESSION['user_id']);
            
            // Refresh unread count
            $unreadMessages = getUnreadMessageCount($conn, $_SESSION['user_id']);
        } else {
            // Not connected, can't message
            $errorMessage = "You need to connect with this user before messaging them.";
            $selectedUser = null;
        }
    } else {
        // User not found
        $errorMessage = "User not found.";
        $selectedUserId = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | LinkedIn Clone</title>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f3f2ef; color: #000000;">
    <!-- Header/Navigation -->
    <header style="background-color: #fff; padding: 0; box-shadow: 0 0 5px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100;">
        <div style="max-width: 1128px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 12px 20px;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <a href="home.php" style="text-decoration: none;">
                    <h1 style="color: #0a66c2; margin: 0; font-size: 28px; font-weight: bold;">in</h1>
                </a>
                <div style="position: relative;">
                    <input type="text" placeholder="Search" style="padding: 8px 36px 8px 16px; border-radius: 4px; border: 1px solid #ddd; background-color: #eef3f8; width: 280px;" onkeypress="searchOnEnter(event)">
                    <i class="fas fa-search" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #666; cursor: pointer;" onclick="search()"></i>
                </div>
            </div>
            <nav style="display: flex; align-items: center; gap: 24px;">
                <a href="home.php" style="text-decoration: none; color: #666; display: flex; flex-direction: column; align-items: center; padding: 0 8px;">
                    <i class="fas fa-home" style="font-size: 20px; margin-bottom: 4px;"></i>
                    <span style="font-size: 12px;">Home</span>
                </a>
                <a href="connections.php" style="text-decoration: none; color: #666; display: flex; flex-direction: column; align-items: center; padding: 0 8px; position: relative;">
                    <i class="fas fa-user-friends" style="font-size: 20px; margin-bottom: 4px;"></i>
                    <span style="font-size: 12px;">My Network</span>
                    <?php if (count($connectionRequests) > 0): ?>
                    <span style="position: absolute; top: -4px; right: -4px; background-color: #e0245e; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; justify-content: center; align-items: center; font-size: 12px;"><?php echo count($connectionRequests); ?></span>
                    <?php endif; ?>
                </a>
                <a href="messages.php" style="text-decoration: none; color: #0a66c2; display: flex; flex-direction: column; align-items: center; padding: 0 8px; position: relative;">
                    <i class="fas fa-comment-dots" style="font-size: 20px; margin-bottom: 4px;"></i>
                    <span style="font-size: 12px;">Messaging</span>
                    <?php if ($unreadMessages > 0): ?>
                    <span style="position: absolute; top: -4px; right: -4px; background-color: #e0245e; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; justify-content: center; align-items: center; font-size: 12px;"><?php echo $unreadMessages; ?></span>
                    <?php endif; ?>
                </a>
                <a href="profile.php" style="text-decoration: none; color: #666; display: flex; flex-direction: column; align-items: center; padding: 0 8px;">
                    <div style="width: 24px; height: 24px; border-radius: 50%; overflow: hidden; margin-bottom: 4px;">
                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <span style="font-size: 12px;">Me</span>
                </a>
                <a href="logout.php" style="text-decoration: none; color: #666; display: flex; flex-direction: column; align-items: center; padding: 0 8px;">
                    <i class="fas fa-sign-out-alt" style="font-size: 20px; margin-bottom: 4px;"></i>
                    <span style="font-size: 12px;">Logout</span>
                </a>
            </nav>
        </div>
    </header>

    <main style="max-width: 1128px; margin: 24px auto; padding: 0 20px; display: grid; grid-template-columns: 350px 1fr; gap: 24px; height: calc(100vh - 172px); max-height: 700px;">
        <!-- Chats List -->
        <aside style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); display: flex; flex-direction: column;">
            <div style="padding: 16px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 20px; margin: 0;">Messaging</h2>
                <?php if ($unreadMessages > 0): ?>
                <span style="background-color: #e0245e; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; justify-content: center; align-items: center; font-size: 14px;"><?php echo $unreadMessages; ?></span>
                <?php endif; ?>
            </div>
            
            <div style="flex: 1; overflow-y: auto; padding: 8px 0;">
                <?php if (empty($chats)): ?>
                <div style="padding: 16px; text-align: center; color: #666;">
                    <p>No conversations yet</p>
                    <p style="font-size: 14px; margin-top: 8px;">Connect with people to start messaging</p>
                </div>
                <?php else: ?>
                    <?php foreach ($chats as $chat): ?>
                    <a href="messages.php?user=<?php echo $chat['user_id']; ?>" style="display: flex; align-items: center; padding: 12px 16px; text-decoration: none; color: inherit; border-bottom: 1px solid #f3f2ef; <?php echo $selectedUserId == $chat['user_id'] ? 'background-color: #f3f2ef;' : ''; ?>">
                        <div style="position: relative;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; margin-right: 12px;">
                                <img src="<?php echo htmlspecialchars($chat['profile_pic']); ?>" alt="<?php echo htmlspecialchars($chat['first_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <?php if ($chat['unread_count'] > 0): ?>
                            <span style="position: absolute; bottom: 0; right: 8px; background-color: #e0245e; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; justify-content: center; align-items: center; font-size: 12px; font-weight: bold;"><?php echo $chat['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <h3 style="font-size: 16px; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($chat['first_name'] . ' ' . $chat['last_name']); ?></h3>
                                <span style="font-size: 12px; color: #666;"><?php echo formatMessageTime($chat['last_message_time']); ?></span>
                            </div>
                            <p style="margin: 4px 0 0; font-size: 14px; color: <?php echo $chat['unread_count'] > 0 ? '#000' : '#666'; ?>; font-weight: <?php echo $chat['unread_count'] > 0 ? 'bold' : 'normal'; ?>; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars($chat['last_message']); ?>
                            </p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Message Content -->
        <section style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); display: flex; flex-direction: column;">
            <?php if ($selectedUser): ?>
            <!-- Selected chat header -->
            <div style="padding: 16px; border-bottom: 1px solid #eee; display: flex; align-items: center;">
                <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; margin-right: 12px;">
                    <img src="<?php echo htmlspecialchars($selectedUser['profile_pic']); ?>" alt="<?php echo htmlspecialchars($selectedUser['first_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div>
                    <h3 style="font-size: 16px; margin: 0;">
                        <a href="profile.php?id=<?php echo $selectedUser['user_id']; ?>" style="color: inherit; text-decoration: none;">
                            <?php echo htmlspecialchars($selectedUser['first_name'] . ' ' . $selectedUser['last_name']); ?>
                        </a>
                    </h3>
                    <?php if (!empty($selectedUser['headline'])): ?>
                    <p style="margin: 4px 0 0; font-size: 14px; color: #666;"><?php echo htmlspecialchars($selectedUser['headline']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Messages container -->
            <div id="messages-container" style="flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 16px;">
                <?php if (empty($messages)): ?>
                <div style="text-align: center; padding: 40px 0; color: #666;">
                    <p>No messages yet</p>
                    <p style="font-size: 14px; margin-top: 8px;">Send a message to start the conversation</p>
                </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php $isOwn = $msg['sender_id'] == $_SESSION['user_id']; ?>
                        <div style="display: flex; flex-direction: <?php echo $isOwn ? 'row-reverse' : 'row'; ?>; align-items: flex-start; gap: 12px; max-width: 80%;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
                                <img src="<?php echo htmlspecialchars($msg['sender_profile_pic']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div style="background-color: <?php echo $isOwn ? '#0a66c2' : '#f2f2f2'; ?>; color: <?php echo $isOwn ? 'white' : 'black'; ?>; padding: 10px 16px; border-radius: 18px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                <p style="margin: 0; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                <span style="display: block; font-size: 12px; margin-top: 4px; text-align: right; opacity: 0.8;"><?php echo formatMessageTime($msg['created_at']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Message input form -->
            <form method="POST" action="messages.php?user=<?php echo $selectedUserId; ?>" style="padding: 16px; border-top: 1px solid #eee; display: flex; gap: 12px;">
                <input type="hidden" name="action" value="send_message">
                <input type="hidden" name="receiver_id" value="<?php echo $selectedUserId; ?>">
                <textarea name="message" placeholder="Write a message..." required style="flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 24px; resize: none; min-height: 40px; max-height: 120px;"></textarea>
                <button type="submit" style="background-color: #0a66c2; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
            
            <?php elseif (isset($errorMessage)): ?>
            <!-- Error message -->
            <div style="padding: 40px; text-align: center; color: #c62828;">
                <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 16px;"></i>
                <p style="margin: 0;"><?php echo $errorMessage; ?></p>
            </div>
            
            <?php else: ?>
            <!-- No chat selected -->
            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100%; padding: 40px; text-align: center; color: #666;">
                <i class="fas fa-comment-dots" style="font-size: 64px; color: #0a66c2; margin-bottom: 24px;"></i>
                <h2 style="font-size: 24px; margin: 0 0 16px;">Your Messages</h2>
                <p style="margin: 0 0 24px;">Select a conversation from the list or start a new one from your connections</p>
                <a href="connections.php" style="background-color: #0a66c2; color: white; padding: 8px 16px; border-radius: 24px; text-decoration: none; font-weight: bold;">View Connections</a>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
    // JavaScript for page redirection
    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') && !this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                let href = this.getAttribute('href');
                window.location.href = href;
            }
        });
    });
    
    // Search functionality
    function search() {
        const searchInput = document.querySelector('input[placeholder="Search"]');
        if (searchInput.value.trim().length > 0) {
            window.location.href = 'search.php?q=' + encodeURIComponent(searchInput.value.trim());
        }
    }
    
    function searchOnEnter(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            search();
        }
    }
    
    // Scroll to bottom of messages container
    document.addEventListener('DOMContentLoaded', function() {
        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Adjust textarea height dynamically
        const textarea = document.querySelector('textarea[name="message"]');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
    });
    </script>
</body>
</html>

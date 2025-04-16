<?php
require_once 'functions.php';
requireLogin();

$user = getUserById($conn, $_SESSION['user_id']);
$connectionRequests = getConnectionRequests($conn, $_SESSION['user_id']);
$connections = getUserConnections($conn, $_SESSION['user_id']);
$unreadMessages = getUnreadMessageCount($conn, $_SESSION['user_id']);

// Handle connection action
if (isset($_GET['action']) && $_GET['action'] === 'connect' && isset($_GET['id'])) {
    $targetUserId = (int)$_GET['id'];
    $targetUser = getUserById($conn, $targetUserId);
    
    if ($targetUser && $targetUserId !== $_SESSION['user_id']) {
        $connectionStatus = getConnectionStatus($conn, $_SESSION['user_id'], $targetUserId);
        
        if ($connectionStatus === 'not_connected') {
            // Send connection request
            $stmt = $conn->prepare("INSERT INTO connections (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("ii", $_SESSION['user_id'], $targetUserId);
            
            if ($stmt->execute()) {
                $successMessage = "Connection request sent to " . $targetUser['first_name'] . ' ' . $targetUser['last_name'];
            } else {
                $errorMessage = "Failed to send connection request. Please try again.";
            }
        } else {
            $errorMessage = "You already have a connection or pending request with this user.";
        }
    } else {
        $errorMessage = "Invalid user.";
    }
}

// Handle accept connection request
if (isset($_GET['action']) && $_GET['action'] === 'accept' && isset($_GET['id'])) {
    $connectionId = (int)$_GET['id'];
    
    // Check if request exists and belongs to current user
    $stmt = $conn->prepare("SELECT * FROM connections WHERE connection_id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $connectionId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Accept connection request
        $stmt = $conn->prepare("UPDATE connections SET status = 'accepted' WHERE connection_id = ?");
        $stmt->bind_param("i", $connectionId);
        
        if ($stmt->execute()) {
            $successMessage = "Connection request accepted!";
            // Refresh connection requests
            $connectionRequests = getConnectionRequests($conn, $_SESSION['user_id']);
            $connections = getUserConnections($conn, $_SESSION['user_id']);
        } else {
            $errorMessage = "Failed to accept connection request. Please try again.";
        }
    } else {
        $errorMessage = "Invalid connection request.";
    }
}

// Handle reject connection request
if (isset($_GET['action']) && $_GET['action'] === 'reject' && isset($_GET['id'])) {
    $connectionId = (int)$_GET['id'];
    
    // Check if request exists and belongs to current user
    $stmt = $conn->prepare("SELECT * FROM connections WHERE connection_id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $connectionId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Reject connection request
        $stmt = $conn->prepare("DELETE FROM connections WHERE connection_id = ?");
        $stmt->bind_param("i", $connectionId);
        
        if ($stmt->execute()) {
            $successMessage = "Connection request rejected.";
            // Refresh connection requests
            $connectionRequests = getConnectionRequests($conn, $_SESSION['user_id']);
        } else {
            $errorMessage = "Failed to reject connection request. Please try again.";
        }
    } else {
        $errorMessage = "Invalid connection request.";
    }
}

// Get connection suggestions
$stmt = $conn->prepare("
    SELECT u.* FROM users u
    WHERE u.user_id != ?
    AND u.user_id NOT IN (
        SELECT IF(c.sender_id = ?, c.receiver_id, c.sender_id)
        FROM connections c
        WHERE (c.sender_id = ? OR c.receiver_id = ?)
    )
    ORDER BY RAND()
    LIMIT 10
");
$stmt->bind_param("iiii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Network | LinkedIn Clone</title>
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
                <a href="connections.php" style="text-decoration: none; color: #0a66c2; display: flex; flex-direction: column; align-items: center; padding: 0 8px; position: relative;">
                    <i class="fas fa-user-friends" style="font-size: 20px; margin-bottom: 4px;"></i>
                    <span style="font-size: 12px;">My Network</span>
                    <?php if (count($connectionRequests) > 0): ?>
                    <span style="position: absolute; top: -4px; right: -4px; background-color: #e0245e; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; justify-content: center; align-items: center; font-size: 12px;"><?php echo count($connectionRequests); ?></span>
                    <?php endif; ?>
                </a>
                <a href="messages.php" style="text-decoration: none; color: #666; display: flex; flex-direction: column; align-items: center; padding: 0 8px; position: relative;">
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

    <main style="max-width: 1128px; margin: 24px auto; padding: 0 20px; display: grid; grid-template-columns: 300px 1fr; gap: 24px;">
        <!-- Left Sidebar -->
        <aside style="display: flex; flex-direction: column; gap: 16px;">
            <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
                <div style="padding: 16px; border-bottom: 1px solid #eee;">
                    <h2 style="font-size: 18px; margin: 0;">Manage my network</h2>
                </div>
                <nav style="display: flex; flex-direction: column;">
                    <a href="#connections-section" style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; transition: background-color 0.2s; color: #0a66c2;">
                        <span>Connections</span>
                        <span><?php echo count($connections); ?></span>
                    </a>
                    <a href="#requests-section" style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; transition: background-color 0.2s; position: relative;">
                        <span>Invitations</span>
                        <?php if (count($connectionRequests) > 0): ?>
                        <span style="color: #0a66c2; font-weight: bold;"><?php echo count($connectionRequests); ?></span>
                        <?php else: ?>
                        <span>0</span>
                        <?php endif; ?>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <?php if (isset($successMessage)): ?>
            <div style="background-color: #e8f5e9; color: #2e7d32; padding: 16px; border-radius: 4px; margin-bottom: 0;">
                <?php echo $successMessage; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage)): ?>
            <div style="background-color: #ffebee; color: #c62828; padding: 16px; border-radius: 4px; margin-bottom: 0;">
                <?php echo $errorMessage; ?>
            </div>
            <?php endif; ?>
            
            <!-- Pending Invitations Section -->
            <section id="requests-section" style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
                <div style="padding: 16px 24px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="font-size: 20px; margin: 0;">Invitations</h2>
                    <?php if (count($connectionRequests) > 0): ?>
                    <span style="color: #0a66c2; font-weight: bold;"><?php echo count($connectionRequests); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($connectionRequests)): ?>
                <div style="padding: 40px 24px; text-align: center;">
                    <p style="color: #666; margin: 0;">No pending invitations</p>
                </div>
                <?php else: ?>
                <div style="padding: 0 24px;">
                    <?php foreach ($connectionRequests as $request): ?>
                    <div style="display: flex; align-items: center; padding: 16px 0; border-bottom: 1px solid #eee;">
                        <div style="width: 56px; height: 56px; border-radius: 50%; overflow: hidden; margin-right: 16px;">
                            <img src="<?php echo htmlspecialchars($request['profile_pic']); ?>" alt="<?php echo htmlspecialchars($request['first_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="flex: 1;">
                            <h3 style="font-size: 16px; margin: 0 0 4px;">
                                <a href="profile.php?id=<?php echo $request['user_id']; ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                </a>
                            </h3>
                            <?php if (!empty($request['headline'])): ?>
                            <p style="margin: 0 0 4px; color: #666; font-size: 14px;"><?php echo htmlspecialchars($request['headline']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <a href="connections.php?action=reject&id=<?php echo $request['connection_id']; ?>" style="background-color: white; color: #666; border: 1px solid #666; padding: 8px 12px; border-radius: 24px; text-decoration: none; font-weight: bold; font-size: 14px;">Ignore</a>
                            <a href="connections.php?action=accept&id=<?php echo $request['connection_id']; ?>" style="background-color: #0a66c2; color: white; padding: 8px 12px; border-radius: 24px; text-decoration: none; font-weight: bold; font-size: 14px;">Accept</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
            
            <!-- Connections Section -->
            <section id="connections-section" style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
                <div style="padding: 16px 24px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="font-size: 20px; margin: 0;">Your connections</h2>
                    <span><?php echo count($connections); ?></span>
                </div>
                
                <?php if (empty($connections)): ?>
                <div style="padding: 40px 24px; text-align: center;">
                    <p style="color: #666; margin: 0 0 16px;">You don't have any connections yet</p>
                    <p style="margin: 0;">Connections help you discover new opportunities and stay updated with your professional network.</p>
                </div>
                <?php else: ?>
                <div style="padding: 0 24px;">
                    <?php foreach ($connections as $connection): ?>
                    <div style="display: flex; align-items: center; padding: 16px 0; border-bottom: 1px solid #eee;">
                        <div style="width: 56px; height: 56px; border-radius: 50%; overflow: hidden; margin-right: 16px;">
                            <img src="<?php echo htmlspecialchars($connection['profile_pic']); ?>" alt="<?php echo htmlspecialchars($connection['first_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="flex: 1;">
                            <h3 style="font-size: 16px; margin: 0 0 4px;">
                                <a href="profile.php?id=<?php echo $connection['user_id']; ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo htmlspecialchars($connection['first_name'] . ' ' . $connection['last_name']); ?>
                                </a>
                            </h3>
                            <?php if (!empty($connection['headline'])): ?>
                            <p style="margin: 0 0 4px; color: #666; font-size: 14px;"><?php echo htmlspecialchars($connection['headline']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="messages.php?user=<?php echo $connection['user_id']; ?>" style="background-color: white; color: #0a66c2; border: 1px solid #0a66c2; padding: 8px 12px; border-radius: 24px; text-decoration: none; font-weight: bold; font-size: 14px;">Message</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
            
            <!-- People You May Know Section -->
            <section style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
                <div style="padding: 16px 24px; border-bottom: 1px solid #eee;">
                    <h2 style="font-size: 20px; margin: 0;">People you may know</h2>
                </div>
                
                <?php if (empty($suggestions)): ?>
                <div style="padding: 40px 24px; text-align: center;">
                    <p style="color: #666; margin: 0;">No suggestions available at the moment</p>
                </div>
                <?php else: ?>
                <div style="padding: 0 24px;">
                    <?php foreach ($suggestions as $suggestion): ?>
                    <div style="display: flex; align-items: center; padding: 16px 0; border-bottom: 1px solid #eee;">
                        <div style="width: 56px; height: 56px; border-radius: 50%; overflow: hidden; margin-right: 16px;">
                            <img src="<?php echo htmlspecialchars($suggestion['profile_pic']); ?>" alt="<?php echo htmlspecialchars($suggestion['first_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="flex: 1;">
                            <h3 style="font-size: 16px; margin: 0 0 4px;">
                                <a href="profile.php?id=<?php echo $suggestion['user_id']; ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo htmlspecialchars($suggestion['first_name'] . ' ' . $suggestion['last_name']); ?>
                                </a>
                            </h3>
                            <?php if (!empty($suggestion['headline'])): ?>
                            <p style="margin: 0 0 4px; color: #666; font-size: 14px;"><?php echo htmlspecialchars($suggestion['headline']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="connections.php?action=connect&id=<?php echo $suggestion['user_id']; ?>" style="background-color: white; color: #0a66c2; border: 1px solid #0a66c2; padding: 8px 12px; border-radius: 24px; text-decoration: none; font-weight: bold; font-size: 14px;">Connect</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
        </div>
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
    </script>
</body>
</html>

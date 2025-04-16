<?php
require_once 'functions.php';
requireLogin();

$user = getUserById($conn, $_SESSION['user_id']);
$searchQuery = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$searchResults = [];

// Perform search if query is provided
if (!empty($searchQuery)) {
    $searchResults = searchUsers($conn, $searchQuery, $_SESSION['user_id']);
}

$connectionRequests = getConnectionRequests($conn, $_SESSION['user_id']);
$unreadMessages = getUnreadMessageCount($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search | LinkedIn Clone</title>
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
                <form action="search.php" method="GET" style="position: relative; flex-grow: 1; max-width: 400px;">
                    <input type="text" name="q" placeholder="Search" value="<?php echo htmlspecialchars($searchQuery); ?>" style="padding: 8px 36px 8px 16px; border-radius: 4px; border: 1px solid #ddd; background-color: #eef3f8; width: 100%; box-sizing: border-box;">
                    <button type="submit" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #666; cursor: pointer;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
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

    <main style="max-width: 1128px; margin: 24px auto; padding: 0 20px;">
        <div style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
            <h1 style="font-size: 20px; margin: 0 0 24px;">
                <?php if (empty($searchQuery)): ?>
                    Search for people
                <?php else: ?>
                    Search results for "<?php echo htmlspecialchars($searchQuery); ?>"
                <?php endif; ?>
            </h1>
            
            <?php if (empty($searchQuery)): ?>
                <div style="text-align: center; padding: 40px 0;">
                    <i class="fas fa-search" style="font-size: 48px; color: #0a66c2; margin-bottom: 16px;"></i>
                    <p style="font-size: 16px; color: #666;">Enter a name or keyword in the search box above to find people</p>
                </div>
            <?php elseif (empty($searchResults)): ?>
                <div style="text-align: center; padding: 40px 0;">
                    <i class="fas fa-user-slash" style="font-size: 48px; color: #666; margin-bottom: 16px;"></i>
                    <p style="font-size: 16px; color: #666;">No results found for "<?php echo htmlspecialchars($searchQuery); ?>"</p>
                    <p style="font-size: 14px; color: #666; margin-top: 8px;">Try different keywords or check your spelling</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach ($searchResults as $result): ?>
                        <?php $connectionStatus = getConnectionStatus($conn, $_SESSION['user_id'], $result['user_id']); ?>
                        <div style="display: flex; align-items: center; padding: 16px; border-radius: 8px; border: 1px solid #ddd;">
                            <div style="width: 72px; height: 72px; border-radius: 50%; overflow: hidden; margin-right: 16px; flex-shrink: 0;">
                                <img src="<?php echo htmlspecialchars($result['profile_pic']); ?>" alt="<?php echo htmlspecialchars($result['first_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div style="flex: 1;">
                                <a href="profile.php?id=<?php echo $result['user_id']; ?>" style="text-decoration: none; color: inherit;">
                                    <h2 style="font-size: 18px; margin: 0 0 4px; color: #000;"><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></h2>
                                </a>
                                <?php if (!empty($result['headline'])): ?>
                                <p style="margin: 0 0 8px; color: #666;"><?php echo htmlspecialchars($result['headline']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($result['location'])): ?>
                                <p style="margin: 0; font-size: 14px; color: #666;"><?php echo htmlspecialchars($result['location']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($connectionStatus === 'connected'): ?>
                                    <a href="messages.php?user=<?php echo $result['user_id']; ?>" style="display: inline-block; background-color: #fff; color: #0a66c2; border: 1px solid #0a66c2; padding: 8px 16px; border-radius: 24px; text-decoration: none; font-weight: bold; font-size: 14px;">Message</a>
                                <?php elseif ($connectionStatus === 'request_sent'): ?>
                                    <button style="background-color: #fff; color: #666; border: 1px solid #666; padding: 8px 16px; border-radius: 24px; cursor: default; font-weight: bold; font-size: 14px;">Request Sent</button>
                                <?php elseif ($connectionStatus === 'request_received'): ?>
                                    <a href="connections.php" style="display: inline-block; background-color: #0a66c2; color: white; padding: 8px 16px; border-radius: 24px; text-decoration: none; font-weight: bold; font-size: 14px;">Respond</a>
                                <?php else: ?>
                                    <a href="connections.php?action=connect&id=<?php echo $result['user_id']; ?>" style="display: inline-block; background-color: #fff; color: #0a66c2; border: 1px solid #0a66c2; padding: 8px 16px; border-radius: 24px; text-decoration: none; font-weight: bold; font-size: 14px;">Connect</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
    </script>
</body>
</html>

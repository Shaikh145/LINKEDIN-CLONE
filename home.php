<?php
require_once 'functions.php';
requireLogin();

$user = getUserById($conn, $_SESSION['user_id']);
$connections = getUserConnections($conn, $_SESSION['user_id']);
$connectionRequests = getConnectionRequests($conn, $_SESSION['user_id']);
$unreadMessages = getUnreadMessageCount($conn, $_SESSION['user_id']);
$feedPosts = getFeedPosts($conn, $_SESSION['user_id']);

// Post a new status
$postError = '';
$postSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post') {
    $content = sanitizeInput($_POST['content']);
    
    if (empty($content)) {
        $postError = "Post content cannot be empty.";
    } else {
        $mediaType = 'none';
        $mediaUrl = null;
        
        // Handle file upload
        if (isset($_FILES['media']) && $_FILES['media']['error'] === 0) {
            $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $allowedVideoTypes = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv'];
            
            $fileType = $_FILES['media']['type'];
            $fileSize = $_FILES['media']['size'];
            $fileTmpName = $_FILES['media']['tmp_name'];
            
            // Check file type
            if (in_array($fileType, $allowedImageTypes)) {
                $mediaType = 'image';
            } elseif (in_array($fileType, $allowedVideoTypes)) {
                $mediaType = 'video';
                // Check if video size is under 100MB
                if ($fileSize > 100 * 1024 * 1024) {
                    $postError = "Video files must be under 100MB.";
                    $mediaType = 'none';
                }
            } else {
                $postError = "Unsupported file type. Please upload an image or video.";
                $mediaType = 'none';
            }
            
            // If valid file, proceed with upload
            if ($mediaType !== 'none' && empty($postError)) {
                $uploadDir = 'uploads/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['media']['name']);
                $targetFilePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                    $mediaUrl = $targetFilePath;
                } else {
                    $postError = "Failed to upload file. Please try again.";
                    $mediaType = 'none';
                }
            }
        }
        
        if (empty($postError)) {
            // Insert post into database
            $stmt = $conn->prepare("INSERT INTO posts (user_id, content, media_type, media_url) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $_SESSION['user_id'], $content, $mediaType, $mediaUrl);
            
            if ($stmt->execute()) {
                $postSuccess = "Post published successfully!";
                // Refresh feed posts
                $feedPosts = getFeedPosts($conn, $_SESSION['user_id']);
            } else {
                $postError = "Failed to publish post. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkedIn Clone - Home</title>
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
                    <input type="text" placeholder="Search" style="padding: 8px 36px 8px 16px; border-radius: 4px; border: 1px solid #ddd; background-color: #eef3f8; width: 280px;">
                    <i class="fas fa-search" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #666;"></i>
                </div>
            </div>
            <nav style="display: flex; align-items: center; gap: 24px;">
                <a href="home.php" style="text-decoration: none; color: #666; display: flex; flex-direction: column; align-items: center; padding: 0 8px;">
                    <i class="fas fa-home" style="font-size: 20px; margin-bottom: 4px; color: #0a66c2;"></i>
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

    <main style="max-width: 1128px; margin: 24px auto; padding: 0 20px; display: grid; grid-template-columns: 225px 1fr 300px; gap: 24px;">
        <!-- Left Sidebar -->
        <aside style="background-color: white; border-radius: 10px; overflow: hidden; height: fit-content; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
            <div style="text-align: center; position: relative; padding-bottom: 24px;">
                <div style="height: 60px; background-color: #0a66c2;"></div>
                <div style="width: 72px; height: 72px; border-radius: 50%; overflow: hidden; margin: -36px auto 0; border: 4px solid white;">
                    <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <h3 style="margin: 12px 0 0; font-size: 16px;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <p style="margin: 4px 0 16px; color: #666; font-size: 14px; padding: 0 16px;"><?php echo !empty($user['headline']) ? htmlspecialchars($user['headline']) : 'Add a headline'; ?></p>
                <a href="profile.php" style="color: #0a66c2; text-decoration: none; font-weight: bold; font-size: 14px;">View Profile</a>
            </div>
            
            <div style="border-top: 1px solid #eee; padding: 16px;">
                <div style="margin-bottom: 16px;">
                    <p style="display: flex; justify-content: space-between; color: #666; margin: 0 0 4px; font-size: 14px;">
                        <span>Connections</span>
                        <span style="font-weight: bold; color: #0a66c2;"><?php echo count($connections); ?></span>
                    </p>
                    <p style="font-size: 14px; margin: 0;"><a href="connections.php" style="color: #000; text-decoration: none; font-weight: bold;">Grow your network</a></p>
                </div>
                
                <?php if (count($connectionRequests) > 0): ?>
                <div style="margin-top: 16px;">
                    <a href="connections.php" style="text-decoration: none; color: #666; font-size: 14px; display: flex; justify-content: space-between; align-items: center;">
                        <span>Invitations</span>
                        <span style="color: #e0245e; font-weight: bold;"><?php echo count($connectionRequests); ?></span>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Content -->
        <section style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Post Creation -->
            <div style="background-color: white; border-radius: 10px; padding: 16px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
                <?php if (!empty($postError)): ?>
                <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 16px;">
                    <?php echo $postError; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($postSuccess)): ?>
                <div style="background-color: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 4px; margin-bottom: 16px;">
                    <?php echo $postSuccess; ?>
                </div>
                <?php endif; ?>
                <form method="POST" action="home.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="post">
                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
                            <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <textarea name="content" placeholder="What's on your mind?" style="flex: 1; border: none; resize: none; padding: 12px; border-radius: 24px; background-color: #f3f2ef; min-height: 80px;"></textarea>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <label for="media-upload" style="cursor: pointer; display: flex; align-items: center; gap: 8px; color: #666;">
                                <i class="fas fa-image" style="font-size: 20px; color: #70b5f9;"></i>
                                <span>Photo/Video</span>
                            </label>
                            <input id="media-upload" type="file" name="media" accept="image/*,video/*" style="display: none;">
                        </div>
                        <button type="submit" style="background-color: #0a66c2; color: white; border: none; border-radius: 24px; padding: 8px 16px; font-weight: bold; cursor: pointer;">Post</button>
                    </div>
                </form>
            </div>

            <!-- Feed Posts -->
            <?php if (empty($feedPosts)): ?>
            <div style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); text-align: center;">
                <p style="color: #666; margin-bottom: 16px;">No posts in your feed yet.</p>
                <p>Connect with more people to see their updates, or start posting yourself!</p>
            </div>
            <?php else: ?>
                <?php foreach ($feedPosts as $post): ?>
                <article style="background-color: white; border-radius: 10px; padding: 16px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden;">
                            <img src="<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div>
                            <h4 style="margin: 0; font-size: 16px;">
                                <a href="profile.php?id=<?php echo $post['user_id']; ?>" style="color: #000; text-decoration: none;"><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></a>
                            </h4>
                            <p style="margin: 4px 0 0; color: #666; font-size: 14px;"><?php echo formatPostTime($post['created_at']); ?></p>
                        </div>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <p style="margin: 0 0 12px; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        <?php if ($post['media_type'] === 'image'): ?>
                        <div style="border-radius: 8px; overflow: hidden; margin-top: 12px;">
                            <img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post image" style="max-width: 100%; display: block;">
                        </div>
                        <?php elseif ($post['media_type'] === 'video'): ?>
                        <div style="border-radius: 8px; overflow: hidden; margin-top: 12px;">
                            <video controls style="max-width: 100%; display: block;">
                                <source src="<?php echo htmlspecialchars($post['media_url']); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-top: 12px; border-top: 1px solid #eee;">
                        <button style="background: none; border: none; color: #666; display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 16px; border-radius: 4px; transition: background-color 0.2s;">
                            <i class="far fa-thumbs-up"></i>
                            <span>Like</span>
                        </button>
                        <button style="background: none; border: none; color: #666; display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 16px; border-radius: 4px; transition: background-color 0.2s;">
                            <i class="far fa-comment"></i>
                            <span>Comment</span>
                        </button>
                        <button style="background: none; border: none; color: #666; display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 16px; border-radius: 4px; transition: background-color 0.2s;">
                            <i class="fas fa-share"></i>
                            <span>Share</span>
                        </button>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- Right Sidebar -->
        <aside>
            <!-- Connection Suggestions -->
            <div style="background-color: white; border-radius: 10px; padding: 16px; margin-bottom: 16px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
                <h3 style="margin: 0 0 16px; font-size: 16px;">People you may know</h3>
                <?php
                // Get connection suggestions (simple implementation - just fetch some users that are not already connected)
                $stmt = $conn->prepare("
                    SELECT u.* FROM users u
                    WHERE u.user_id != ?
                    AND u.user_id NOT IN (
                        SELECT IF(c.sender_id = ?, c.receiver_id, c.sender_id)
                        FROM connections c
                        WHERE (c.sender_id = ? OR c.receiver_id = ?)
                    )
                    LIMIT 3
                ");
                $stmt->bind_param("iiii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while ($suggestion = $result->fetch_assoc()) {
                ?>
                <div style="display: flex; margin-bottom: 16px; align-items: center;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; margin-right: 12px;">
                        <img src="<?php echo htmlspecialchars($suggestion['profile_pic']); ?>" alt="<?php echo htmlspecialchars($suggestion['first_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="flex: 1;">
                        <a href="profile.php?id=<?php echo $suggestion['user_id']; ?>" style="color: #000; text-decoration: none; font-weight: bold; font-size: 14px;">
                            <?php echo htmlspecialchars($suggestion['first_name'] . ' ' . $suggestion['last_name']); ?>
                        </a>
                        <p style="margin: 4px 0 0; color: #666; font-size: 14px;">
                            <?php echo !empty($suggestion['headline']) ? htmlspecialchars($suggestion['headline']) : '&nbsp;'; ?>
                        </p>
                    </div>
                    <a href="connections.php?action=connect&id=<?php echo $suggestion['user_id']; ?>" style="border: 1px solid #0a66c2; color: #0a66c2; background-color: white; border-radius: 24px; padding: 4px 12px; text-decoration: none; font-weight: bold; font-size: 14px; white-space: nowrap;">Connect</a>
                </div>
                <?php
                    }
                } else {
                    echo '<p style="color: #666; text-align: center;">No connection suggestions at the moment.</p>';
                }
                ?>
                <a href="connections.php" style="color: #666; display: block; text-align: center; text-decoration: none; font-size: 14px; padding-top: 8px;">See all recommendations</a>
            </div>
            
            <!-- Footer Links -->
            <div style="padding: 16px;">
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px;">
                    <a href="#" style="color: #666; text-decoration: none; font-size: 12px;">About</a>
                    <a href="#" style="color: #666; text-decoration: none; font-size: 12px;">Help Center</a>
                    <a href="#" style="color: #666; text-decoration: none; font-size: 12px;">Privacy & Terms</a>
                    <a href="#" style="color: #666; text-decoration: none; font-size: 12px;">Accessibility</a>
                </div>
                <p style="color: #666; font-size: 12px; margin: 0;">LinkedIn Clone &copy; 2023</p>
            </div>
        </aside>
    </main>

    <script>
    // JavaScript for page redirection and interactions
    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') && !this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                let href = this.getAttribute('href');
                window.location.href = href;
            }
        });
    });
    
    // Display selected media filename
    const mediaUpload = document.getElementById('media-upload');
    mediaUpload.addEventListener('change', function() {
        if (this.files.length > 0) {
            const fileLabel = this.previousElementSibling;
            fileLabel.textContent = 'File selected: ' + this.files[0].name;
        }
    });
    </script>
</body>
</html>

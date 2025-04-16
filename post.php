<?php
require_once 'functions.php';
requireLogin();

$user = getUserById($conn, $_SESSION['user_id']);
$connectionRequests = getConnectionRequests($conn, $_SESSION['user_id']);
$unreadMessages = getUnreadMessageCount($conn, $_SESSION['user_id']);

// Get post ID from URL
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch post data
$stmt = $conn->prepare("SELECT p.*, u.first_name, u.last_name, u.profile_pic, u.headline 
                       FROM posts p 
                       JOIN users u ON p.user_id = u.user_id 
                       WHERE p.post_id = ?");
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Post not found, redirect to home
    header("Location: home.php");
    exit();
}

$post = $result->fetch_assoc();

// Check if user is connected to the post author or if it's their own post
$isOwn = ($post['user_id'] == $_SESSION['user_id']);
$isConnected = areConnected($conn, $_SESSION['user_id'], $post['user_id']);

// Fetch comments for the post
$stmt = $conn->prepare("SELECT c.*, u.first_name, u.last_name, u.profile_pic 
                       FROM comments c 
                       JOIN users u ON c.user_id = u.user_id 
                       WHERE c.post_id = ? 
                       ORDER BY c.created_at ASC");
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();
$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

// Handle new comment submission
$commentError = '';
$commentSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $commentContent = sanitizeInput($_POST['comment']);
    
    if (empty($commentContent)) {
        $commentError = "Comment cannot be empty.";
    } else {
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $postId, $_SESSION['user_id'], $commentContent);
        
        if ($stmt->execute()) {
            $commentSuccess = "Comment added successfully!";
            // Refresh comments
            $stmt = $conn->prepare("SELECT c.*, u.first_name, u.last_name, u.profile_pic 
                                   FROM comments c 
                                   JOIN users u ON c.user_id = u.user_id 
                                   WHERE c.post_id = ? 
                                   ORDER BY c.created_at ASC");
            $stmt->bind_param("i", $postId);
            $stmt->execute();
            $result = $stmt->get_result();
            $comments = [];
            while ($row = $result->fetch_assoc()) {
                $comments[] = $row;
            }
        } else {
            $commentError = "Failed to add comment. Please try again.";
        }
    }
}

// Handle like/unlike action
if (isset($_GET['action']) && ($_GET['action'] === 'like' || $_GET['action'] === 'unlike')) {
    // Check if user already liked the post
    $stmt = $conn->prepare("SELECT like_id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $postId, $_SESSION['user_id']);
    $stmt->execute();
    $likeResult = $stmt->get_result();
    $alreadyLiked = ($likeResult->num_rows > 0);
    
    if ($_GET['action'] === 'like' && !$alreadyLiked) {
        // Add like
        $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $postId, $_SESSION['user_id']);
        $stmt->execute();
    } elseif ($_GET['action'] === 'unlike' && $alreadyLiked) {
        // Remove like
        $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $postId, $_SESSION['user_id']);
        $stmt->execute();
    }
    
    // Redirect to remove the action from URL
    header("Location: post.php?id=$postId");
    exit();
}

// Check if user liked the post
$stmt = $conn->prepare("SELECT COUNT(*) as liked FROM likes WHERE post_id = ? AND user_id = ?");
$stmt->bind_param("ii", $postId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$userLiked = ($row['liked'] > 0);

// Get like count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$likeCount = $row['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post | LinkedIn Clone</title>
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
        <!-- Back to Home Link -->
        <div style="margin-bottom: 16px;">
            <a href="home.php" style="text-decoration: none; color: #666; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Home</span>
            </a>
        </div>
        
        <!-- Post Content -->
        <article style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div style="width: 56px; height: 56px; border-radius: 50%; overflow: hidden;">
                    <img src="<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 18px;">
                        <a href="profile.php?id=<?php echo $post['user_id']; ?>" style="color: #000; text-decoration: none;"><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></a>
                    </h2>
                    <?php if (!empty($post['headline'])): ?>
                    <p style="margin: 4px 0 0; color: #666; font-size: 14px;"><?php echo htmlspecialchars($post['headline']); ?></p>
                    <?php endif; ?>
                    <p style="margin: 4px 0 0; color: #666; font-size: 14px;"><?php echo formatPostTime($post['created_at']); ?></p>
                </div>
            </div>
            
            <div style="margin-bottom: 24px;">
                <p style="font-size: 16px; line-height: 1.5; white-space: pre-wrap; margin: 0 0 16px;"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                
                <?php if ($post['media_type'] === 'image'): ?>
                <div style="border-radius: 8px; overflow: hidden; margin-top: 16px;">
                    <img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post image" style="max-width: 100%; display: block;">
                </div>
                <?php elseif ($post['media_type'] === 'video'): ?>
                <div style="border-radius: 8px; overflow: hidden; margin-top: 16px;">
                    <video controls style="max-width: 100%; display: block;">
                        <source src="<?php echo htmlspecialchars($post['media_url']); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Like/Comment Stats -->
            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; margin-bottom: 16px;">
                <div>
                    <?php if ($likeCount > 0): ?>
                    <span>
                        <i class="fas fa-thumbs-up" style="color: #0a66c2;"></i>
                        <?php echo $likeCount; ?> <?php echo $likeCount === 1 ? 'like' : 'likes'; ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (count($comments) > 0): ?>
                    <span><?php echo count($comments); ?> <?php echo count($comments) === 1 ? 'comment' : 'comments'; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; justify-content: space-between; margin-bottom: 24px;">
                <?php if ($userLiked): ?>
                <a href="post.php?id=<?php echo $postId; ?>&action=unlike" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 8px; border-radius: 4px; background-color: #f2f2f2; text-decoration: none; color: #0a66c2; font-weight: bold;">
                    <i class="fas fa-thumbs-up"></i>
                    <span>Liked</span>
                </a>
                <?php else: ?>
                <a href="post.php?id=<?php echo $postId; ?>&action=like" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 8px; border-radius: 4px; text-decoration: none; color: #666; transition: background-color 0.2s;">
                    <i class="far fa-thumbs-up"></i>
                    <span>Like</span>
                </a>
                <?php endif; ?>
                
                <a href="#comments-section" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 8px; border-radius: 4px; text-decoration: none; color: #666; transition: background-color 0.2s;">
                    <i class="far fa-comment"></i>
                    <span>Comment</span>
                </a>
                
                <button style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 8px; border-radius: 4px; background: none; border: none; color: #666; cursor: pointer; transition: background-color 0.2s;">
                    <i class="fas fa-share"></i>
                    <span>Share</span>
                </button>
            </div>
            
            <!-- Comments Section -->
            <section id="comments-section">
                <h3 style="font-size: 18px; margin: 0 0 16px;">Comments</h3>
                
                <?php if (!empty($commentError)): ?>
                <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 16px;">
                    <?php echo $commentError; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($commentSuccess)): ?>
                <div style="background-color: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 4px; margin-bottom: 16px;">
                    <?php echo $commentSuccess; ?>
                </div>
                <?php endif; ?>
                
                <!-- Add Comment Form -->
                <form method="POST" action="post.php?id=<?php echo $postId; ?>" style="margin-bottom: 24px; display: flex; gap: 12px; align-items: flex-start;">
                    <input type="hidden" name="action" value="add_comment">
                    <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="flex: 1; background-color: #f2f2f2; border-radius: 24px; padding: 4px;">
                        <textarea name="comment" placeholder="Add a comment..." required style="width: 100%; min-height: 36px; padding: 8px 16px; border: none; background: transparent; resize: none; outline: none;"></textarea>
                        <div style="display: flex; justify-content: flex-end; padding: 0 8px 8px 0;">
                            <button type="submit" style="background-color: #0a66c2; color: white; border: none; border-radius: 16px; padding: 6px 16px; font-weight: bold; cursor: pointer;">Post</button>
                        </div>
                    </div>
                </form>
                
                <!-- Comments List -->
                <?php if (empty($comments)): ?>
                <p style="text-align: center; color: #666; padding: 20px 0;">No comments yet. Be the first to comment!</p>
                <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach ($comments as $comment): ?>
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
                            <img src="<?php echo htmlspecialchars($comment['profile_pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="flex: 1;">
                            <div style="background-color: #f2f2f2; border-radius: 8px; padding: 12px; position: relative;">
                                <h4 style="margin: 0 0 4px; font-size: 16px;">
                                    <a href="profile.php?id=<?php echo $comment['user_id']; ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?>
                                    </a>
                                </h4>
                                <p style="margin: 0; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            </div>
                            <div style="display: flex; gap: 16px; margin-top: 4px; padding-left: 8px;">
                                <span style="color: #666; font-size: 12px;"><?php echo formatPostTime($comment['created_at']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
        </article>
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
    
    // Auto-resize textarea
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.querySelector('textarea[name="comment"]');
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

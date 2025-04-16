<?php
require_once 'functions.php';
requireLogin();

// Check if viewing own profile or someone else's
$profileId = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$isOwnProfile = ($profileId === $_SESSION['user_id']);

// Get user data
$user = getUserById($conn, $profileId);
if (!$user) {
    // User not found, redirect to home
    header("Location: home.php");
    exit();
}

// Get user experiences, education, skills
$experiences = getUserExperience($conn, $profileId);
$education = getUserEducation($conn, $profileId);
$skills = getUserSkills($conn, $profileId);

// Check connection status if not own profile
$connectionStatus = '';
if (!$isOwnProfile) {
    $connectionStatus = getConnectionStatus($conn, $_SESSION['user_id'], $profileId);
}

// Handle connection request
if (isset($_GET['action']) && $_GET['action'] === 'connect' && !$isOwnProfile) {
    // Check if not already connected or pending
    if ($connectionStatus === 'not_connected') {
        // Send connection request
        $stmt = $conn->prepare("INSERT INTO connections (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("ii", $_SESSION['user_id'], $profileId);
        $stmt->execute();
        
        // Update connection status
        $connectionStatus = 'request_sent';
    }
}

// Handle message redirect
if (isset($_GET['action']) && $_GET['action'] === 'message' && !$isOwnProfile) {
    header("Location: messages.php?user=" . $profileId);
    exit();
}

// Get user posts
$stmt = $conn->prepare("SELECT p.*, u.first_name, u.last_name, u.profile_pic 
                      FROM posts p
                      JOIN users u ON p.user_id = u.user_id
                      WHERE p.user_id = ?
                      ORDER BY p.created_at DESC");
$stmt->bind_param("i", $profileId);
$stmt->execute();
$result = $stmt->get_result();
$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> | LinkedIn Clone</title>
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
                    <i class="fas fa-home" style="font-size: 20px; margin-bottom: 4px;"></i>
                    <span style="font-size: 12px;">Home</span>
                </a>
                <a href="connections.php" style="text-decoration: none; color: #666; display: flex; flex-direction: column; align-items: center; padding: 0 8px;">
                    <i class="fas fa-user-friends" style="font-size: 20px; margin-bottom: 4px;"></i>
                    <span style="font-size: 12px;">My Network</span>
                </a>
                <a href="messages.php" style="text-decoration: none; color: #666; display: flex; flex-direction: column; align-items: center; padding: 0 8px;">
                    <i class="fas fa-comment-dots" style="font-size: 20px; margin-bottom: 4px;"></i>
                    <span style="font-size: 12px;">Messaging</span>
                </a>
                <a href="profile.php" style="text-decoration: none; color: #666; display: flex; flex-direction: column; align-items: center; padding: 0 8px; <?php echo $isOwnProfile ? 'color: #0a66c2;' : ''; ?>">
                    <div style="width: 24px; height: 24px; border-radius: 50%; overflow: hidden; margin-bottom: 4px;">
                        <img src="<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'default.jpg'); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
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

    <main style="max-width: 1128px; margin: 24px auto; padding: 0 20px; display: grid; grid-template-columns: 3fr 1fr; gap: 24px;">
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Profile Card -->
            <section style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
                <!-- Cover Photo -->
                <div style="height: 200px; background-color: #0a66c2; position: relative;">
                    <?php if ($isOwnProfile): ?>
                    <a href="edit_profile.php" style="position: absolute; top: 16px; right: 16px; background-color: white; color: #666; padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                        <i class="fas fa-pencil-alt"></i>
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Profile Info -->
                <div style="padding: 0 24px 24px; position: relative;">
                    <div style="width: 152px; height: 152px; border-radius: 50%; overflow: hidden; border: 4px solid white; position: absolute; top: -80px; left: 24px;">
                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="<?php echo htmlspecialchars($user['first_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    
                    <div style="margin-top: 80px; display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <h1 style="margin: 0; font-size: 24px;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                            <?php if (!empty($user['headline'])): ?>
                            <p style="margin: 8px 0 16px; color: #666; font-size: 16px;"><?php echo htmlspecialchars($user['headline']); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($user['location'])): ?>
                            <p style="margin: 8px 0; color: #666; font-size: 14px;"><?php echo htmlspecialchars($user['location']); ?></p>
                            <?php endif; ?>
                            
                            <?php
                            // Count connections
                            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM connections WHERE (sender_id = ? OR receiver_id = ?) AND status = 'accepted'");
                            $stmt->bind_param("ii", $profileId, $profileId);
                            $stmt->execute();
                            $connectionCount = $stmt->get_result()->fetch_assoc()['count'];
                            ?>
                            <p style="margin: 8px 0; font-size: 14px;">
                                <a href="#" style="color: #0a66c2; text-decoration: none;"><?php echo $connectionCount; ?> connections</a>
                            </p>
                        </div>
                        
                        <?php if (!$isOwnProfile): ?>
                            <div style="display: flex; gap: 12px;">
                                <?php if ($connectionStatus === 'connected'): ?>
                                    <a href="messages.php?user=<?php echo $profileId; ?>" style="background-color: #0a66c2; color: white; padding: 8px 16px; border-radius: 24px; text-decoration: none; font-weight: bold;">Message</a>
                                <?php elseif ($connectionStatus === 'request_sent'): ?>
                                    <button style="background-color: #fff; color: #666; border: 1px solid #666; padding: 8px 16px; border-radius: 24px; cursor: default; font-weight: bold;">Request Sent</button>
                                <?php elseif ($connectionStatus === 'request_received'): ?>
                                    <a href="connections.php" style="background-color: #0a66c2; color: white; padding: 8px 16px; border-radius: 24px; text-decoration: none; font-weight: bold;">Respond to Request</a>
                                <?php else: ?>
                                    <a href="profile.php?id=<?php echo $profileId; ?>&action=connect" style="background-color: #0a66c2; color: white; padding: 8px 16px; border-radius: 24px; text-decoration: none; font-weight: bold;">Connect</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <a href="edit_profile.php" style="background-color: white; color: #0a66c2; border: 1px solid #0a66c2; padding: 8px 16px; border-radius: 24px; text-decoration: none; font-weight: bold;">Edit Profile</a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($user['about'])): ?>
                    <div style="margin-top: 24px;">
                        <h2 style="font-size: 20px; margin-bottom: 16px;">About</h2>
                        <p style="white-space: pre-wrap; margin: 0;"><?php echo nl2br(htmlspecialchars($user['about'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Experience Section -->
            <section style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="font-size: 20px; margin: 0;">Experience</h2>
                    <?php if ($isOwnProfile): ?>
                    <a href="edit_profile.php#experience" style="color: #666; text-decoration: none;">
                        <i class="fas fa-plus"></i>
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($experiences)): ?>
                <p style="color: #666;">No experience listed yet.</p>
                <?php else: ?>
                    <?php foreach ($experiences as $exp): ?>
                    <div style="display: flex; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #eee;">
                        <div style="width: 48px; height: 48px; background-color: #f3f2ef; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                            <i class="fas fa-building" style="color: #666;"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 16px; margin: 0 0 4px;"><?php echo htmlspecialchars($exp['title']); ?></h3>
                            <p style="margin: 0 0 4px;"><?php echo htmlspecialchars($exp['company']); ?></p>
                            <p style="margin: 0 0 8px; color: #666; font-size: 14px;">
                                <?php echo formatDate($exp['start_date']); ?> - <?php echo $exp['current'] ? 'Present' : formatDate($exp['end_date']); ?>
                                <?php if (!empty($exp['location'])): ?>
                                · <?php echo htmlspecialchars($exp['location']); ?>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($exp['description'])): ?>
                            <p style="margin: 0; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($exp['description'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Education Section -->
            <section style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="font-size: 20px; margin: 0;">Education</h2>
                    <?php if ($isOwnProfile): ?>
                    <a href="edit_profile.php#education" style="color: #666; text-decoration: none;">
                        <i class="fas fa-plus"></i>
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($education)): ?>
                <p style="color: #666;">No education listed yet.</p>
                <?php else: ?>
                    <?php foreach ($education as $edu): ?>
                    <div style="display: flex; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #eee;">
                        <div style="width: 48px; height: 48px; background-color: #f3f2ef; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                            <i class="fas fa-university" style="color: #666;"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 16px; margin: 0 0 4px;"><?php echo htmlspecialchars($edu['school']); ?></h3>
                            <?php if (!empty($edu['degree']) && !empty($edu['field_of_study'])): ?>
                            <p style="margin: 0 0 4px;"><?php echo htmlspecialchars($edu['degree'] . ', ' . $edu['field_of_study']); ?></p>
                            <?php elseif (!empty($edu['degree'])): ?>
                            <p style="margin: 0 0 4px;"><?php echo htmlspecialchars($edu['degree']); ?></p>
                            <?php elseif (!empty($edu['field_of_study'])): ?>
                            <p style="margin: 0 0 4px;"><?php echo htmlspecialchars($edu['field_of_study']); ?></p>
                            <?php endif; ?>
                            <p style="margin: 0 0 8px; color: #666; font-size: 14px;">
                                <?php echo formatDate($edu['start_date']); ?> - <?php echo formatDate($edu['end_date']); ?>
                            </p>
                            <?php if (!empty($edu['description'])): ?>
                            <p style="margin: 0; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($edu['description'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Skills Section -->
            <section style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="font-size: 20px; margin: 0;">Skills</h2>
                    <?php if ($isOwnProfile): ?>
                    <a href="edit_profile.php#skills" style="color: #666; text-decoration: none;">
                        <i class="fas fa-plus"></i>
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($skills)): ?>
                <p style="color: #666;">No skills listed yet.</p>
                <?php else: ?>
                <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                    <?php foreach ($skills as $skill): ?>
                    <span style="background-color: #f3f2ef; padding: 6px 12px; border-radius: 16px; font-size: 14px;"><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <!-- Posts Section -->
            <?php if (!empty($posts)): ?>
            <section style="margin-top: 24px;">
                <h2 style="font-size: 20px; margin-bottom: 16px;">Posts</h2>
                <?php foreach ($posts as $post): ?>
                <article style="background-color: white; border-radius: 10px; padding: 16px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); margin-bottom: 16px;">
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
                </article>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>
        </div>

        <!-- Right Sidebar -->
        <div>
            <?php if (!$isOwnProfile && $connectionStatus === 'connected'): ?>
            <!-- Message Button for Mobile -->
            <div style="background-color: white; border-radius: 10px; padding: 16px; margin-bottom: 16px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); display: none;">
                <a href="messages.php?user=<?php echo $profileId; ?>" style="background-color: #0a66c2; color: white; padding: 8px 16px; border-radius: 24px; text-decoration: none; font-weight: bold; display: block; text-align: center;">Message</a>
            </div>
            <?php endif; ?>
            
            <!-- People Also Viewed -->
            <div style="background-color: white; border-radius: 10px; padding: 16px; margin-bottom: 16px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08);">
                <h3 style="margin: 0 0 16px; font-size: 16px;">People also viewed</h3>
                <?php
                // Get some random users as suggestions
                $stmt = $conn->prepare("
                    SELECT u.* FROM users u
                    WHERE u.user_id != ? AND u.user_id != ?
                    ORDER BY RAND()
                    LIMIT 3
                ");
                $stmt->bind_param("ii", $_SESSION['user_id'], $profileId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while ($suggestion = $result->fetch_assoc()) {
                ?>
                <div style="display: flex; margin-bottom: 16px; align-items: center;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; margin-right: 12px;">
                        <img src="<?php echo htmlspecialchars($suggestion['profile_pic']); ?>" alt="<?php echo htmlspecialchars($suggestion['first_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="flex: 1;">
                        <a href="profile.php?id=<?php echo $suggestion['user_id']; ?>" style="color: #000; text-decoration: none; font-weight: bold; font-size: 14px;">
                            <?php echo htmlspecialchars($suggestion['first_name'] . ' ' . $suggestion['last_name']); ?>
                        </a>
                        <p style="margin: 4px 0 0; color: #666; font-size: 14px; line-height: 1.4;">
                            <?php echo !empty($suggestion['headline']) ? htmlspecialchars($suggestion['headline']) : '&nbsp;'; ?>
                        </p>
                    </div>
                </div>
                <?php
                    }
                } else {
                    echo '<p style="color: #666; text-align: center;">No suggestions available.</p>';
                }
                ?>
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
        </div>
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
    </script>
</body>
</html>

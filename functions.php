<?php
session_start();

// Include database connection
require_once 'db.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get user information by ID
function getUserById($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Get user experience
function getUserExperience($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM experience WHERE user_id = ? ORDER BY current DESC, end_date DESC, start_date DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $experiences = [];
    while ($row = $result->fetch_assoc()) {
        $experiences[] = $row;
    }
    return $experiences;
}

// Get user education
function getUserEducation($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM education WHERE user_id = ? ORDER BY end_date DESC, start_date DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $education = [];
    while ($row = $result->fetch_assoc()) {
        $education[] = $row;
    }
    return $education;
}

// Get user skills
function getUserSkills($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM skills WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $skills = [];
    while ($row = $result->fetch_assoc()) {
        $skills[] = $row;
    }
    return $skills;
}

// Check if users are connected
function areConnected($conn, $user1Id, $user2Id) {
    $stmt = $conn->prepare("SELECT * FROM connections WHERE 
        ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) 
        AND status = 'accepted'");
    $stmt->bind_param("iiii", $user1Id, $user2Id, $user2Id, $user1Id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Get connection status between users
function getConnectionStatus($conn, $user1Id, $user2Id) {
    $stmt = $conn->prepare("SELECT * FROM connections WHERE 
        ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))");
    $stmt->bind_param("iiii", $user1Id, $user2Id, $user2Id, $user1Id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $connection = $result->fetch_assoc();
        if ($connection['status'] === 'accepted') {
            return "connected";
        } elseif ($connection['status'] === 'pending') {
            if ($connection['sender_id'] == $user1Id) {
                return "request_sent";
            } else {
                return "request_received";
            }
        } else {
            return "not_connected";
        }
    }
    
    return "not_connected";
}

// Get user connections (accepted)
function getUserConnections($conn, $userId) {
    $stmt = $conn->prepare("SELECT u.* FROM users u
        JOIN connections c ON (u.user_id = c.receiver_id OR u.user_id = c.sender_id)
        WHERE (c.sender_id = ? OR c.receiver_id = ?) 
        AND c.status = 'accepted'
        AND u.user_id != ?");
    $stmt->bind_param("iii", $userId, $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $connections = [];
    while ($row = $result->fetch_assoc()) {
        $connections[] = $row;
    }
    return $connections;
}

// Get connection requests (pending)
function getConnectionRequests($conn, $userId) {
    $stmt = $conn->prepare("SELECT u.*, c.connection_id FROM users u
        JOIN connections c ON u.user_id = c.sender_id
        WHERE c.receiver_id = ? AND c.status = 'pending'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    return $requests;
}

// Get posts for feed (from user and connections)
function getFeedPosts($conn, $userId, $limit = 10, $offset = 0) {
    $query = "SELECT p.*, u.first_name, u.last_name, u.profile_pic 
              FROM posts p
              JOIN users u ON p.user_id = u.user_id
              WHERE p.user_id = ? 
              OR p.user_id IN (
                  SELECT IF(c.sender_id = ?, c.receiver_id, c.sender_id) 
                  FROM connections c 
                  WHERE (c.sender_id = ? OR c.receiver_id = ?) 
                  AND c.status = 'accepted'
              )
              ORDER BY p.created_at DESC
              LIMIT ? OFFSET ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiiii", $userId, $userId, $userId, $userId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    return $posts;
}

// Get all messages between two users
function getMessages($conn, $user1Id, $user2Id) {
    $stmt = $conn->prepare("SELECT m.*, 
                           sender.first_name as sender_first_name, 
                           sender.last_name as sender_last_name,
                           sender.profile_pic as sender_profile_pic
                           FROM messages m
                           JOIN users sender ON m.sender_id = sender.user_id
                           WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                           OR (m.sender_id = ? AND m.receiver_id = ?)
                           ORDER BY m.created_at ASC");
    $stmt->bind_param("iiii", $user1Id, $user2Id, $user2Id, $user1Id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    return $messages;
}

// Get count of unread messages
function getUnreadMessageCount($conn, $userId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Mark messages as read
function markMessagesAsRead($conn, $senderId, $receiverId) {
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmt->bind_param("ii", $senderId, $receiverId);
    $stmt->execute();
}

// Get chats list for a user
function getUserChats($conn, $userId) {
    $query = "SELECT DISTINCT 
              u.user_id, u.first_name, u.last_name, u.profile_pic,
              (SELECT message FROM messages 
               WHERE (sender_id = u.user_id AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = u.user_id) 
               ORDER BY created_at DESC LIMIT 1) as last_message,
              (SELECT created_at FROM messages 
               WHERE (sender_id = u.user_id AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = u.user_id) 
               ORDER BY created_at DESC LIMIT 1) as last_message_time,
              (SELECT COUNT(*) FROM messages 
               WHERE sender_id = u.user_id AND receiver_id = ? AND is_read = 0) as unread_count
              FROM users u
              JOIN messages m ON (m.sender_id = u.user_id AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = u.user_id)
              WHERE u.user_id != ?
              GROUP BY u.user_id
              ORDER BY last_message_time DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiiiiii", $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $chats = [];
    while ($row = $result->fetch_assoc()) {
        $chats[] = $row;
    }
    return $chats;
}

// Format date for display
function formatDate($date) {
    if (empty($date) || $date == '0000-00-00') return 'Present';
    return date('M Y', strtotime($date));
}

// Function to format message time
function formatMessageTime($time) {
    $now = new DateTime();
    $messageTime = new DateTime($time);
    $diff = $now->diff($messageTime);
    
    if ($diff->y > 0) {
        return $messageTime->format('M j, Y');
    } elseif ($diff->d > 0) {
        if ($diff->d == 1) {
            return 'Yesterday';
        } elseif ($diff->d < 7) {
            return $messageTime->format('l'); // Day name
        } else {
            return $messageTime->format('M j');
        }
    } else {
        return $messageTime->format('g:i A'); // Today, show time
    }
}

// Format post time for display
function formatPostTime($timestamp) {
    $now = time();
    $postTime = strtotime($timestamp);
    $diff = $now - $postTime;
    
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . " week" . ($weeks > 1 ? "s" : "") . " ago";
    } else {
        return date("M j, Y", $postTime);
    }
}

// Search users by name or headline
function searchUsers($conn, $query, $currentUserId) {
    $searchQuery = "%$query%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE 
                           (first_name LIKE ? OR last_name LIKE ? OR 
                           CONCAT(first_name, ' ', last_name) LIKE ? OR
                           headline LIKE ?) AND 
                           user_id != ? 
                           LIMIT 20");
    $stmt->bind_param("ssssi", $searchQuery, $searchQuery, $searchQuery, $searchQuery, $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

// Get notification count (connection requests)
function getNotificationCount($conn, $userId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM connections WHERE receiver_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}
?>

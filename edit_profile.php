<?php
require_once 'functions.php';
requireLogin();

$user = getUserById($conn, $_SESSION['user_id']);
$experiences = getUserExperience($conn, $_SESSION['user_id']);
$education = getUserEducation($conn, $_SESSION['user_id']);
$skills = getUserSkills($conn, $_SESSION['user_id']);

$success = '';
$error = '';

// Handle basic info update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'basic_info') {
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $headline = sanitizeInput($_POST['headline']);
    $location = sanitizeInput($_POST['location']);
    $about = sanitizeInput($_POST['about']);
    $website = sanitizeInput($_POST['website']);
    
    if (empty($firstName) || empty($lastName)) {
        $error = "First name and last name are required.";
    } else {
        // Handle profile picture upload
        $profilePic = $user['profile_pic'];
        
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['profile_pic']['type'], $allowedTypes)) {
                $error = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
            } elseif ($_FILES['profile_pic']['size'] > $maxSize) {
                $error = "File is too large. Maximum size is 5MB.";
            } else {
                $uploadDir = 'uploads/profile/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['profile_pic']['name']);
                $targetFilePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFilePath)) {
                    $profilePic = $targetFilePath;
                } else {
                    $error = "Failed to upload profile picture. Please try again.";
                }
            }
        }
        
        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, headline = ?, location = ?, about = ?, website = ?, profile_pic = ? WHERE user_id = ?");
            $stmt->bind_param("sssssssi", $firstName, $lastName, $headline, $location, $about, $website, $profilePic, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = "Profile updated successfully!";
                // Refresh user data
                $user = getUserById($conn, $_SESSION['user_id']);
            } else {
                $error = "Failed to update profile. Please try again.";
            }
        }
    }
}

// Handle experience addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_experience') {
    $title = sanitizeInput($_POST['title']);
    $company = sanitizeInput($_POST['company']);
    $location = sanitizeInput($_POST['location']);
    $startDate = sanitizeInput($_POST['start_date']);
    $endDate = isset($_POST['current']) ? NULL : sanitizeInput($_POST['end_date']);
    $current = isset($_POST['current']) ? 1 : 0;
    $description = sanitizeInput($_POST['description']);
    
    if (empty($title) || empty($company) || empty($startDate)) {
        $error = "Title, company, and start date are required for experience.";
    } else {
        $stmt = $conn->prepare("INSERT INTO experience (user_id, title, company, location, start_date, end_date, current, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $_SESSION['user_id'], $title, $company, $location, $startDate, $endDate, $current, $description);
        
        if ($stmt->execute()) {
            $success = "Experience added successfully!";
            // Refresh experiences
            $experiences = getUserExperience($conn, $_SESSION['user_id']);
        } else {
            $error = "Failed to add experience. Please try again.";
        }
    }
}

// Handle education addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_education') {
    $school = sanitizeInput($_POST['school']);
    $degree = sanitizeInput($_POST['degree']);
    $fieldOfStudy = sanitizeInput($_POST['field_of_study']);
    $startDate = sanitizeInput($_POST['edu_start_date']);
    $endDate = sanitizeInput($_POST['edu_end_date']);
    $description = sanitizeInput($_POST['edu_description']);
    
    if (empty($school) || empty($startDate)) {
        $error = "School and start date are required for education.";
    } else {
        $stmt = $conn->prepare("INSERT INTO education (user_id, school, degree, field_of_study, start_date, end_date, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $_SESSION['user_id'], $school, $degree, $fieldOfStudy, $startDate, $endDate, $description);
        
        if ($stmt->execute()) {
            $success = "Education added successfully!";
            // Refresh education
            $education = getUserEducation($conn, $_SESSION['user_id']);
        } else {
            $error = "Failed to add education. Please try again.";
        }
    }
}

// Handle skill addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_skill') {
    $skillName = sanitizeInput($_POST['skill_name']);
    
    if (empty($skillName)) {
        $error = "Skill name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO skills (user_id, skill_name) VALUES (?, ?)");
        $stmt->bind_param("is", $_SESSION['user_id'], $skillName);
        
        if ($stmt->execute()) {
            $success = "Skill added successfully!";
            // Refresh skills
            $skills = getUserSkills($conn, $_SESSION['user_id']);
        } else {
            $error = "Failed to add skill. Please try again.";
        }
    }
}

// Handle experience deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_experience' && isset($_GET['id'])) {
    $experienceId = (int)$_GET['id'];
    
    // Check if experience belongs to the user
    $stmt = $conn->prepare("SELECT user_id FROM experience WHERE experience_id = ?");
    $stmt->bind_param("i", $experienceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0 && $result->fetch_assoc()['user_id'] === $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM experience WHERE experience_id = ?");
        $stmt->bind_param("i", $experienceId);
        
        if ($stmt->execute()) {
            $success = "Experience deleted successfully!";
            // Refresh experiences
            $experiences = getUserExperience($conn, $_SESSION['user_id']);
        } else {
            $error = "Failed to delete experience. Please try again.";
        }
    } else {
        $error = "Invalid request.";
    }
}

// Handle education deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_education' && isset($_GET['id'])) {
    $educationId = (int)$_GET['id'];
    
    // Check if education belongs to the user
    $stmt = $conn->prepare("SELECT user_id FROM education WHERE education_id = ?");
    $stmt->bind_param("i", $educationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0 && $result->fetch_assoc()['user_id'] === $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM education WHERE education_id = ?");
        $stmt->bind_param("i", $educationId);
        
        if ($stmt->execute()) {
            $success = "Education deleted successfully!";
            // Refresh education
            $education = getUserEducation($conn, $_SESSION['user_id']);
        } else {
            $error = "Failed to delete education. Please try again.";
        }
    } else {
        $error = "Invalid request.";
    }
}

// Handle skill deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_skill' && isset($_GET['id'])) {
    $skillId = (int)$_GET['id'];
    
    // Check if skill belongs to the user
    $stmt = $conn->prepare("SELECT user_id FROM skills WHERE skill_id = ?");
    $stmt->bind_param("i", $skillId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0 && $result->fetch_assoc()['user_id'] === $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM skills WHERE skill_id = ?");
        $stmt->bind_param("i", $skillId);
        
        if ($stmt->execute()) {
            $success = "Skill deleted successfully!";
            // Refresh skills
            $skills = getUserSkills($conn, $_SESSION['user_id']);
        } else {
            $error = "Failed to delete skill. Please try again.";
        }
    } else {
        $error = "Invalid request.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | LinkedIn Clone</title>
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
                <a href="profile.php" style="text-decoration: none; color: #0a66c2; display: flex; flex-direction: column; align-items: center; padding: 0 8px;">
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

    <main style="max-width: 800px; margin: 24px auto; padding: 0 20px;">
        <h1 style="margin-bottom: 24px;">Edit Profile</h1>
        
        <?php if (!empty($error)): ?>
        <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 16px;">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div style="background-color: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 4px; margin-bottom: 16px;">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>

        <!-- Basic Information Form -->
        <section style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); margin-bottom: 24px;">
            <h2 style="margin-top: 0; margin-bottom: 24px; font-size: 20px;">Basic Information</h2>
            <form method="POST" action="edit_profile.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="basic_info">
                
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; align-items: center; gap: 24px; margin-bottom: 16px;">
                        <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; position: relative;">
                            <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover;" id="profile-preview">
                            <label for="profile_pic" style="position: absolute; bottom: 0; left: 0; right: 0; background-color: rgba(0,0,0,0.6); color: white; text-align: center; padding: 4px 0; font-size: 12px; cursor: pointer;">Change</label>
                            <input type="file" id="profile_pic" name="profile_pic" accept="image/*" style="display: none;">
                        </div>
                        <div style="flex: 1;">
                            <p style="margin: 0 0 8px; font-weight: bold;">Profile Photo</p>
                            <p style="margin: 0; color: #666; font-size: 14px;">Upload a new photo (5MB maximum)</p>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 16px;">
                        <div style="flex: 1;">
                            <label for="first_name" style="display: block; margin-bottom: 8px; font-weight: bold;">First Name*</label>
                            <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($user['first_name']); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div style="flex: 1;">
                            <label for="last_name" style="display: block; margin-bottom: 8px; font-weight: bold;">Last Name*</label>
                            <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($user['last_name']); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </div>
                    
                    <div>
                        <label for="headline" style="display: block; margin-bottom: 8px; font-weight: bold;">Headline</label>
                        <input type="text" id="headline" name="headline" value="<?php echo htmlspecialchars($user['headline'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <div>
                        <label for="location" style="display: block; margin-bottom: 8px; font-weight: bold;">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <div>
                        <label for="website" style="display: block; margin-bottom: 8px; font-weight: bold;">Website</label>
                        <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <div>
                        <label for="about" style="display: block; margin-bottom: 8px; font-weight: bold;">About</label>
                        <textarea id="about" name="about" rows="6" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"><?php echo htmlspecialchars($user['about'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="margin-top: 16px;">
                        <button type="submit" style="background-color: #0a66c2; color: white; border: none; border-radius: 24px; padding: 10px 20px; font-weight: bold; cursor: pointer;">Save Changes</button>
                    </div>
                </div>
            </form>
        </section>

        <!-- Experience Section -->
        <section id="experience" style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); margin-bottom: 24px;">
            <h2 style="margin-top: 0; margin-bottom: 24px; font-size: 20px;">Experience</h2>
            
            <?php if (!empty($experiences)): ?>
            <div style="margin-bottom: 24px;">
                <?php foreach ($experiences as $exp): ?>
                <div style="display: flex; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid #eee;">
                    <div>
                        <h3 style="margin: 0 0 8px; font-size: 16px;"><?php echo htmlspecialchars($exp['title']); ?> at <?php echo htmlspecialchars($exp['company']); ?></h3>
                        <p style="margin: 0 0 8px; color: #666; font-size: 14px;">
                            <?php echo formatDate($exp['start_date']); ?> - <?php echo $exp['current'] ? 'Present' : formatDate($exp['end_date']); ?>
                            <?php if (!empty($exp['location'])): ?>
                            · <?php echo htmlspecialchars($exp['location']); ?>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($exp['description'])): ?>
                        <p style="margin: 0; color: #666; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 400px;"><?php echo htmlspecialchars($exp['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="edit_profile.php?action=delete_experience&id=<?php echo $exp['experience_id']; ?>" style="color: #666; text-decoration: none;" onclick="return confirm('Are you sure you want to delete this experience?');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="edit_profile.php#experience">
                <input type="hidden" name="action" value="add_experience">
                
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label for="title" style="display: block; margin-bottom: 8px; font-weight: bold;">Title*</label>
                        <input type="text" id="title" name="title" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <div style="display: flex; gap: 16px;">
                        <div style="flex: 1;">
                            <label for="company" style="display: block; margin-bottom: 8px; font-weight: bold;">Company*</label>
                            <input type="text" id="company" name="company" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div style="flex: 1;">
                            <label for="location" style="display: block; margin-bottom: 8px; font-weight: bold;">Location</label>
                            <input type="text" id="location" name="location" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 16px; align-items: flex-start;">
                        <div style="flex: 1;">
                            <label for="start_date" style="display: block; margin-bottom: 8px; font-weight: bold;">Start Date*</label>
                            <input type="date" id="start_date" name="start_date" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div style="flex: 1;">
                            <label for="end_date" style="display: block; margin-bottom: 8px; font-weight: bold;">End Date</label>
                            <input type="date" id="end_date" name="end_date" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="current" name="current" style="margin: 0;">
                        <label for="current">I am currently working in this role</label>
                    </div>
                    
                    <div>
                        <label for="description" style="display: block; margin-bottom: 8px; font-weight: bold;">Description</label>
                        <textarea id="description" name="description" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
                    </div>
                    
                    <div style="margin-top: 16px;">
                        <button type="submit" style="background-color: #0a66c2; color: white; border: none; border-radius: 24px; padding: 10px 20px; font-weight: bold; cursor: pointer;">Add Experience</button>
                    </div>
                </div>
            </form>
        </section>

        <!-- Education Section -->
        <section id="education" style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); margin-bottom: 24px;">
            <h2 style="margin-top: 0; margin-bottom: 24px; font-size: 20px;">Education</h2>
            
            <?php if (!empty($education)): ?>
            <div style="margin-bottom: 24px;">
                <?php foreach ($education as $edu): ?>
                <div style="display: flex; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid #eee;">
                    <div>
                        <h3 style="margin: 0 0 8px; font-size: 16px;"><?php echo htmlspecialchars($edu['school']); ?></h3>
                        <?php if (!empty($edu['degree']) && !empty($edu['field_of_study'])): ?>
                        <p style="margin: 0 0 8px;"><?php echo htmlspecialchars($edu['degree'] . ', ' . $edu['field_of_study']); ?></p>
                        <?php elseif (!empty($edu['degree'])): ?>
                        <p style="margin: 0 0 8px;"><?php echo htmlspecialchars($edu['degree']); ?></p>
                        <?php elseif (!empty($edu['field_of_study'])): ?>
                        <p style="margin: 0 0 8px;"><?php echo htmlspecialchars($edu['field_of_study']); ?></p>
                        <?php endif; ?>
                        <p style="margin: 0 0 8px; color: #666; font-size: 14px;">
                            <?php echo formatDate($edu['start_date']); ?> - <?php echo formatDate($edu['end_date']); ?>
                        </p>
                    </div>
                    <div>
                        <a href="edit_profile.php?action=delete_education&id=<?php echo $edu['education_id']; ?>" style="color: #666; text-decoration: none;" onclick="return confirm('Are you sure you want to delete this education?');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif;<?php
require_once 'functions.php';
requireLogin();

$user = getUserById($conn, $_SESSION['user_id']);
$experiences = getUserExperience($conn, $_SESSION['user_id']);
$education = getUserEducation($conn, $_SESSION['user_id']);
$skills = getUserSkills($conn, $_SESSION['user_id']);

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic info update
    if (isset($_POST['action']) && $_POST['action'] === 'update_info') {
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $headline = sanitizeInput($_POST['headline']);
        $about = sanitizeInput($_POST['about']);
        $location = sanitizeInput($_POST['location']);
        $website = sanitizeInput($_POST['website']);
        
        if (empty($firstName) || empty($lastName)) {
            $error = "First name and last name are required.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, headline = ?, about = ?, location = ?, website = ? WHERE user_id = ?");
            $stmt->bind_param("ssssssi", $firstName, $lastName, $headline, $about, $location, $website, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = "Profile information updated successfully!";
                // Refresh user data
                $user = getUserById($conn, $_SESSION['user_id']);
            } else {
                $error = "Failed to update profile information.";
            }
        }
    }
    
    // Profile picture update
    if (isset($_POST['action']) && $_POST['action'] === 'update_picture') {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = $_FILES['profile_pic']['type'];
            $fileSize = $_FILES['profile_pic']['size'];
            $fileTmpName = $_FILES['profile_pic']['tmp_name'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $error = "Only JPG, PNG, GIF, and WEBP images are allowed.";
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $error = "File size must be less than 5MB.";
            } else {
                $uploadDir = 'uploads/profiles/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . $_SESSION['user_id'] . '_' . basename($_FILES['profile_pic']['name']);
                $targetFilePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                    // Update profile picture in database
                    $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $targetFilePath, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $success = "Profile picture updated successfully!";
                        // Refresh user data
                        $user = getUserById($conn, $_SESSION['user_id']);
                    } else {
                        $error = "Failed to update profile picture in database.";
                    }
                } else {
                    $error = "Failed to upload profile picture.";
                }
            }
        } else {
            $error = "Please select a valid image file.";
        }
    }
    
    // Add new experience
    if (isset($_POST['action']) && $_POST['action'] === 'add_experience') {
        $title = sanitizeInput($_POST['title']);
        $company = sanitizeInput($_POST['company']);
        $location = sanitizeInput($_POST['location']);
        $startDate = sanitizeInput($_POST['start_date']);
        $endDate = isset($_POST['current']) ? null : sanitizeInput($_POST['end_date']);
        $current = isset($_POST['current']) ? 1 : 0;
        $description = sanitizeInput($_POST['description']);
        
        if (empty($title) || empty($company) || empty($startDate)) {
            $error = "Title, company, and start date are required for experience.";
        } else {
            $stmt = $conn->prepare("INSERT INTO experience (user_id, title, company, location, start_date, end_date, current, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $_SESSION['user_id'], $title, $company, $location, $startDate, $endDate, $current, $description);
            
            if ($stmt->execute()) {
                $success = "Experience added successfully!";
                // Refresh experiences
                $experiences = getUserExperience($conn, $_SESSION['user_id']);
                
                // Redirect to anchor
                header("Location: edit_profile.php#experience_section");
                exit();
            } else {
                $error = "Failed to add experience.";
            }
        }
    }
    
    // Delete experience
    if (isset($_POST['action']) && $_POST['action'] === 'delete_experience') {
        $experienceId = (int)$_POST['experience_id'];
        
        $stmt = $conn->prepare("DELETE FROM experience WHERE experience_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $experienceId, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "Experience deleted successfully!";
            // Refresh experiences
            $experiences = getUserExperience($conn, $_SESSION['user_id']);
            
            // Redirect to anchor
            header("Location: edit_profile.php#experience_section");
            exit();
        } else {
            $error = "Failed to delete experience.";
        }
    }
    
    // Add new education
    if (isset($_POST['action']) && $_POST['action'] === 'add_education') {
        $school = sanitizeInput($_POST['school']);
        $degree = sanitizeInput($_POST['degree']);
        $fieldOfStudy = sanitizeInput($_POST['field_of_study']);
        $startDate = sanitizeInput($_POST['start_date']);
        $endDate = sanitizeInput($_POST['end_date']);
        $description = sanitizeInput($_POST['description']);
        
        if (empty($school) || empty($startDate)) {
            $error = "School and start date are required for education.";
        } else {
            $stmt = $conn->prepare("INSERT INTO education (user_id, school, degree, field_of_study, start_date, end_date, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $_SESSION['user_id'], $school, $degree, $fieldOfStudy, $startDate, $endDate, $description);
            
            if ($stmt->execute()) {
                $success = "Education added successfully!";
                // Refresh education
                $education = getUserEducation($conn, $_SESSION['user_id']);
                
                // Redirect to anchor
                header("Location: edit_profile.php#education_section");
                exit();
            } else {
                $error = "Failed to add education.";
            }
        }
    }
    
    // Delete education
    if (isset($_POST['action']) && $_POST['action'] === 'delete_education') {
        $educationId = (int)$_POST['education_id'];
        
        $stmt = $conn->prepare("DELETE FROM education WHERE education_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $educationId, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "Education deleted successfully!";
            // Refresh education
            $education = getUserEducation($conn, $_SESSION['user_id']);
            
            // Redirect to anchor
            header("Location: edit_profile.php#education_section");
            exit();
        } else {
            $error = "Failed to delete education.";
        }
    }
    
    // Add new skill
    if (isset($_POST['action']) && $_POST['action'] === 'add_skill') {
        $skillName = sanitizeInput($_POST['skill_name']);
        
        if (empty($skillName)) {
            $error = "Skill name is required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO skills (user_id, skill_name) VALUES (?, ?)");
            $stmt->bind_param("is", $_SESSION['user_id'], $skillName);
            
            if ($stmt->execute()) {
                $success = "Skill added successfully!";
                // Refresh skills
                $skills = getUserSkills($conn, $_SESSION['user_id']);
                
                // Redirect to anchor
                header("Location: edit_profile.php#skills_section");
                exit();
            } else {
                $error = "Failed to add skill.";
            }
        }
    }
    
    // Delete skill
    if (isset($_POST['action']) && $_POST['action'] === 'delete_skill') {
        $skillId = (int)$_POST['skill_id'];
        
        $stmt = $conn->prepare("DELETE FROM skills WHERE skill_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $skillId, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "Skill deleted successfully!";
            // Refresh skills
            $skills = getUserSkills($conn, $_SESSION['user_id']);
            
            // Redirect to anchor
            header("Location: edit_profile.php#skills_section");
            exit();
        } else {
            $error = "Failed to delete skill.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | LinkedIn Clone</title>
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
                <a href="profile.php" style="text-decoration: none; color: #0a66c2; display: flex; flex-direction: column; align-items: center; padding: 0 8px;">
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h1 style="font-size: 24px; margin: 0;">Edit Profile</h1>
            <a href="profile.php" style="background-color: #fff; color: #0a66c2; border: 1px solid #0a66c2; padding: 8px 16px; border-radius: 24px; text-decoration: none; font-weight: bold;">View Profile</a>
        </div>
        
        <?php if (!empty($error)): ?>
        <div style="background-color: #ffebee; color: #c62828; padding: 16px; border-radius: 4px; margin-bottom: 24px;">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div style="background-color: #e8f5e9; color: #2e7d32; padding: 16px; border-radius: 4px; margin-bottom: 24px;">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <!-- Basic Info Section -->
        <section style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); margin-bottom: 24px;">
            <h2 style="font-size: 20px; margin: 0 0 24px;">Profile Information</h2>
            
            <form method="POST" action="edit_profile.php" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="hidden" name="action" value="update_info">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="first_name" style="font-weight: bold;">First Name*</label>
                        <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($user['first_name']); ?>" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="last_name" style="font-weight: bold;">Last Name*</label>
                        <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($user['last_name']); ?>" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label for="headline" style="font-weight: bold;">Headline</label>
                    <input type="text" id="headline" name="headline" value="<?php echo htmlspecialchars($user['headline'] ?? ''); ?>" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    <span style="font-size: 14px; color: #666;">E.g., Web Developer, Project Manager, Student at XYZ University</span>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label for="about" style="font-weight: bold;">About</label>
                    <textarea id="about" name="about" rows="5" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; resize: vertical;"><?php echo htmlspecialchars($user['about'] ?? ''); ?></textarea>
                    <span style="font-size: 14px; color: #666;">Tell us about yourself, your experience, and your goals.</span>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label for="location" style="font-weight: bold;">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    <span style="font-size: 14px; color: #666;">E.g., New York, NY</span>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label for="website" style="font-weight: bold;">Website</label>
                    <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    <span style="font-size: 14px; color: #666;">Your personal website or portfolio</span>
                </div>
                
                <div style="margin-top: 8px;">
                    <button type="submit" style="background-color: #0a66c2; color: white; padding: 10px 20px; border: none; border-radius: 24px; font-weight: bold; font-size: 16px; cursor: pointer;">Save Changes</button>
                </div>
            </form>
        </section>
        
        <!-- Profile Picture Section -->
        <section style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); margin-bottom: 24px;">
            <h2 style="font-size: 20px; margin: 0 0 24px;">Profile Picture</h2>
            
            <div style="display: flex; gap: 24px; align-items: center;">
                <div style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; box-shadow: 0 0 0 1px rgba(0,0,0,0.15);">
                    <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                
                <form method="POST" action="edit_profile.php" enctype="multipart/form-data" style="flex: 1;">
                    <input type="hidden" name="action" value="update_picture">
                    
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label for="profile_pic" style="font-weight: bold;">Upload New Profile Picture</label>
                            <input type="file" id="profile_pic" name="profile_pic" accept="image/*" required style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <span style="font-size: 14px; color: #666;">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF, WEBP.</span>
                        </div>
                        
                        <div>
                            <button type="submit" style="background-color: #0a66c2; color: white; padding: 10px 20px; border: none; border-radius: 24px; font-weight: bold; cursor: pointer;">Upload Photo</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- Experience Section -->
        <section id="experience_section" style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); margin-bottom: 24px;">
            <h2 style="font-size: 20px; margin: 0 0 24px;">Experience</h2>
            
            <!-- Existing Experiences -->
            <?php if (!empty($experiences)): ?>
            <div style="margin-bottom: 32px;">
                <h3 style="font-size: 18px; margin: 0 0 16px;">Current Experience</h3>
                
                <?php foreach ($experiences as $exp): ?>
                <div style="display: flex; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #eee;">
                    <div style="width: 48px; height: 48px; background-color: #f3f2ef; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                        <i class="fas fa-building" style="color: #666;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 16px; margin: 0 0 4px;"><?php echo htmlspecialchars($exp['title']); ?></h4>
                        <p style="margin: 0 0 4px;"><?php echo htmlspecialchars($exp['company']); ?></p>
                        <p style="margin: 0 0 8px; color: #666; font-size: 14px;">
                            <?php echo formatDate($exp['start_date']); ?> - <?php echo $exp['current'] ? 'Present' : formatDate($exp['end_date']); ?>
                            <?php if (!empty($exp['location'])): ?>
                            · <?php echo htmlspecialchars($exp['location']); ?>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($exp['description'])): ?>
                        <p style="margin: 0 0 12px; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($exp['description'])); ?></p>
                        <?php endif; ?>
                        
                        <form method="POST" action="edit_profile.php" style="display: inline;">
                            <input type="hidden" name="action" value="delete_experience">
                            <input type="hidden" name="experience_id" value="<?php echo $exp['experience_id']; ?>">
                            <button type="submit" style="background-color: transparent; color: #666; border: none; cursor: pointer; padding: 4px 8px; font-size: 14px;">Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Add New Experience Form -->
            <div>
                <h3 style="font-size: 18px; margin: 0 0 16px;">Add New Experience</h3>
                <form method="POST" action="edit_profile.php" style="display: flex; flex-direction: column; gap: 16px;">
                    <input type="hidden" name="action" value="add_experience">
                    
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="title" style="font-weight: bold;">Title*</label>
                        <input type="text" id="title" name="title" required style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="company" style="font-weight: bold;">Company*</label>
                        <input type="text" id="company" name="company" required style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="location" style="font-weight: bold;">Location</label>
                        <input type="text" id="location" name="location" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    
                    <div style="display: flex; gap: 16px;">
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                            <label for="start_date" style="font-weight: bold;">Start Date*</label>
                            <input type="date" id="start_date" name="start_date" required style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                        </div>
                        
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                            <label for="end_date" style="font-weight: bold;">End Date</label>
                            <input type="date" id="end_date" name="end_date" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                        </div>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="current" name="current" value="1" style="width: 18px; height: 18px;" onchange="document.getElementById('end_date').disabled = this.checked;">
                        <label for="current">I am currently working in this role</label>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="description" style="font-weight: bold;">Description</label>
                        <textarea id="description" name="description" rows="4" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; resize: vertical;"></textarea>
                    </div>
                    
                    <div>
                        <button type="submit" style="background-color: #0a66c2; color: white; padding: 10px 20px; border: none; border-radius: 24px; font-weight: bold; cursor: pointer;">Add Experience</button>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- Education Section -->
        <section id="education_section" style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); margin-bottom: 24px;">
            <h2 style="font-size: 20px; margin: 0 0 24px;">Education</h2>
            
            <!-- Existing Education -->
            <?php if (!empty($education)): ?>
            <div style="margin-bottom: 32px;">
                <h3 style="font-size: 18px; margin: 0 0 16px;">Current Education</h3>
                
                <?php foreach ($education as $edu): ?>
                <div style="display: flex; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #eee;">
                    <div style="width: 48px; height: 48px; background-color: #f3f2ef; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                        <i class="fas fa-university" style="color: #666;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 16px; margin: 0 0 4px;"><?php echo htmlspecialchars($edu['school']); ?></h4>
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
                        <p style="margin: 0 0 12px; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($edu['description'])); ?></p>
                        <?php endif; ?>
                        
                        <form method="POST" action="edit_profile.php" style="display: inline;">
                            <input type="hidden" name="action" value="delete_education">
                            <input type="hidden" name="education_id" value="<?php echo $edu['education_id']; ?>">
                            <button type="submit" style="background-color: transparent; color: #666; border: none; cursor: pointer; padding: 4px 8px; font-size: 14px;">Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Add New Education Form -->
            <div>
                <h3 style="font-size: 18px; margin: 0 0 16px;">Add New Education</h3>
                <form method="POST" action="edit_profile.php" style="display: flex; flex-direction: column; gap: 16px;">
                    <input type="hidden" name="action" value="add_education">
                    
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="school" style="font-weight: bold;">School*</label>
                        <input type="text" id="school" name="school" required style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="degree" style="font-weight: bold;">Degree</label>
                        <input type="text" id="degree" name="degree" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="field_of_study" style="font-weight: bold;">Field of Study</label>
                        <input type="text" id="field_of_study" name="field_of_study" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    
                    <div style="display: flex; gap: 16px;">
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                            <label for="start_date" style="font-weight: bold;">Start Date*</label>
                            <input type="date" id="start_date" name="start_date" required style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                        </div>
                        
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                            <label for="end_date" style="font-weight: bold;">End Date</label>
                            <input type="date" id="end_date" name="end_date" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                        </div>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="description" style="font-weight: bold;">Description</label>
                        <textarea id="description" name="description" rows="4" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; resize: vertical;"></textarea>
                    </div>
                    
                    <div>
                        <button type="submit" style="background-color: #0a66c2; color: white; padding: 10px 20px; border: none; border-radius: 24px; font-weight: bold; cursor: pointer;">Add Education</button>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- Skills Section -->
        <section id="skills_section" style="background-color: white; border-radius: 10px; padding: 24px; box-shadow: 0 0 0 1px rgba(0,0,0,0.08); margin-bottom: 24px;">
            <h2 style="font-size: 20px; margin: 0 0 24px;">Skills</h2>
            
            <!-- Existing Skills -->
            <?php if (!empty($skills)): ?>
            <div style="margin-bottom: 32px;">
                <h3 style="font-size: 18px; margin: 0 0 16px;">Current Skills</h3>
                
                <div style="display: flex; flex-wrap: wrap; gap: 16px;">
                    <?php foreach ($skills as $skill): ?>
                    <div style="display: flex; align-items: center; background-color: #f3f2ef; padding: 8px 16px; border-radius: 20px;">
                        <span style="margin-right: 12px;"><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                        <form method="POST" action="edit_profile.php" style="display: inline;">
                            <input type="hidden" name="action" value="delete_skill">
                            <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                            <button type="submit" style="background-color: transparent; color: #666; border: none; cursor: pointer; padding: 0; font-size: 14px; display: flex; align-items: center; justify-content: center; width: 20px; height: 20px;">×</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Add New Skill Form -->
            <div>
                <h3 style="font-size: 18px; margin: 0 0 16px;">Add New Skill</h3>
                <form method="POST" action="edit_profile.php" style="display: flex; gap: 16px; align-items: flex-end;">
                    <input type="hidden" name="action" value="add_skill">
                    
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                        <label for="skill_name" style="font-weight: bold;">Skill Name*</label>
                        <input type="text" id="skill_name" name="skill_name" required style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    
                    <div>
                        <button type="submit" style="background-color: #0a66c2; color: white; padding: 12px 20px; border: none; border-radius: 24px; font-weight: bold; cursor: pointer;">Add Skill</button>
                    </div>
                </form>
            </div>
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
    
    // Toggle end date field based on current checkbox
    document.addEventListener('DOMContentLoaded', function() {
        const currentCheckbox = document.querySelector('input[name="current"]');
        const endDateField = document.getElementById('end_date');
        
        if (currentCheckbox && endDateField) {
            currentCheckbox.addEventListener('change', function() {
                endDateField.disabled = this.checked;
                if (this.checked) {
                    endDateField.value = '';
                }
            });
        }
    });
    </script>
</body>
</html>

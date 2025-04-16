<?php
require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: home.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already in use. Please choose another one.";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $firstName, $lastName, $email, $hashedPassword);
            
            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again later.";
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
    <title>LinkedIn Clone - Register</title>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f3f2ef; color: #000000;">
    <header style="background-color: #0a66c2; padding: 12px 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <div style="max-width: 1128px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
            <div style="display: flex; align-items: center;">
                <a href="index.php" style="text-decoration: none;">
                    <h1 style="color: white; margin: 0; font-size: 28px; font-weight: bold;">LinkedIn</h1>
                </a>
            </div>
            <div style="display: flex; gap: 16px;">
                <a href="login.php" style="background-color: transparent; color: white; border: 1px solid white; padding: 8px 16px; text-decoration: none; border-radius: 24px; font-weight: bold;">Sign In</a>
            </div>
        </div>
    </header>

    <main style="max-width: 1128px; margin: 0 auto; padding: 40px 20px;">
        <div style="max-width: 500px; margin: 0 auto; background-color: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 style="text-align: center; margin-bottom: 24px; color: #0a66c2;">Join LinkedIn Clone</h2>
            <?php if (!empty($error)): ?>
                <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 16px; text-align: center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div style="background-color: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 4px; margin-bottom: 16px; text-align: center;">
                    <?php echo $success; ?>
                    <p style="margin-top: 10px;">
                        <a href="login.php" style="color: #0a66c2; text-decoration: none; font-weight: bold;">Sign in now</a>
                    </p>
                </div>
            <?php else: ?>
                <form method="POST" action="register.php" style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; gap: 16px;">
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                            <label for="first_name" style="font-weight: bold;">First Name</label>
                            <input type="text" id="first_name" name="first_name" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                        </div>
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                            <label for="last_name" style="font-weight: bold;">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label for="email" style="font-weight: bold;">Email</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label for="password" style="font-weight: bold;">Password (6+ characters)</label>
                        <input type="password" id="password" name="password" minlength="6" required style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label for="confirm_password" style="font-weight: bold;">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6" required style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    <p style="font-size: 12px; color: #666; margin-top: 0;">By clicking Join Now, you agree to the LinkedIn Clone User Agreement, Privacy Policy, and Cookie Policy.</p>
                    <button type="submit" style="background-color: #0a66c2; color: white; padding: 12px; border: none; border-radius: 24px; font-weight: bold; font-size: 16px; cursor: pointer; margin-top: 8px;">Join Now</button>
                </form>
                <div style="text-align: center; margin-top: 24px;">
                    <p>Already on LinkedIn Clone? <a href="login.php" style="color: #0a66c2; text-decoration: none; font-weight: bold;">Sign in</a></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer style="background-color: #f3f2ef; padding: 40px 0; margin-top: 60px; border-top: 1px solid #ddd;">
        <div style="max-width: 1128px; margin: 0 auto; padding: 0 20px; text-align: center;">
            <p style="margin-bottom: 16px;">LinkedIn Clone &copy; 2023</p>
            <div style="display: flex; justify-content: center; gap: 24px;">
                <a href="#" style="color: #666; text-decoration: none;">About</a>
                <a href="#" style="color: #666; text-decoration: none;">Privacy Policy</a>
                <a href="#" style="color: #666; text-decoration: none;">Terms</a>
            </div>
        </div>
    </footer>

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

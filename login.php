<?php
require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: home.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check credentials
        $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['user_id'];
                
                // Redirect to home page
                header("Location: home.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkedIn Clone - Login</title>
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
                <a href="register.php" style="background-color: white; color: #0a66c2; padding: 8px 16px; text-decoration: none; border-radius: 24px; font-weight: bold;">Join Now</a>
            </div>
        </div>
    </header>

    <main style="max-width: 1128px; margin: 0 auto; padding: 40px 20px;">
        <div style="max-width: 400px; margin: 0 auto; background-color: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 style="text-align: center; margin-bottom: 24px; color: #0a66c2;">Sign In</h2>
            <?php if (!empty($error)): ?>
                <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 16px; text-align: center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="login.php" style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="email" style="font-weight: bold;">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="password" style="font-weight: bold;">Password</label>
                    <input type="password" id="password" name="password" required style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                </div>
                <button type="submit" style="background-color: #0a66c2; color: white; padding: 12px; border: none; border-radius: 24px; font-weight: bold; font-size: 16px; cursor: pointer; margin-top: 8px;">Sign In</button>
            </form>
            <div style="text-align: center; margin-top: 24px;">
                <p>New to LinkedIn? <a href="register.php" style="color: #0a66c2; text-decoration: none; font-weight: bold;">Join now</a></p>
            </div>
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

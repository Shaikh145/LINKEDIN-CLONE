<?php
require_once 'functions.php';

// Redirect to home if already logged in
if (isLoggedIn()) {
    header("Location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkedIn Clone - Welcome</title>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f3f2ef; color: #000000;">
    <header style="background-color: #0a66c2; padding: 12px 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <div style="max-width: 1128px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
            <div style="display: flex; align-items: center;">
                <h1 style="color: white; margin: 0; font-size: 28px; font-weight: bold;">LinkedIn</h1>
            </div>
            <div style="display: flex; gap: 16px;">
                <a href="login.php" style="background-color: transparent; color: white; border: 1px solid white; padding: 8px 16px; text-decoration: none; border-radius: 24px; font-weight: bold;">Sign In</a>
                <a href="register.php" style="background-color: white; color: #0a66c2; padding: 8px 16px; text-decoration: none; border-radius: 24px; font-weight: bold;">Join Now</a>
            </div>
        </div>
    </header>

    <main style="max-width: 1128px; margin: 0 auto; padding: 40px 20px;">
        <div style="display: flex; flex-direction: column; gap: 20px; padding: 0 20px;">
            <section style="display: flex; flex-wrap: wrap; gap: 40px; align-items: center;">
                <div style="flex: 1; min-width: 300px;">
                    <h2 style="font-size: 48px; color: #0a66c2; margin-bottom: 24px;">Welcome to your professional community</h2>
                    <p style="font-size: 18px; margin-bottom: 32px; line-height: 1.5;">Connect with professionals, stay informed with latest industry trends, and build your career with LinkedIn Clone.</p>
                    <div style="display: flex; flex-direction: column; gap: 16px; max-width: 360px;">
                        <a href="register.php" style="display: block; background-color: #0a66c2; color: white; text-align: center; padding: 12px 24px; text-decoration: none; border-radius: 28px; font-weight: bold; font-size: 16px;">Join LinkedIn Clone</a>
                        <a href="login.php" style="display: block; background-color: transparent; color: #0a66c2; border: 1px solid #0a66c2; text-align: center; padding: 12px 24px; text-decoration: none; border-radius: 28px; font-weight: bold; font-size: 16px;">Sign In</a>
                    </div>
                </div>
                <div style="flex: 1; min-width: 300px;">
                    <div style="background-color: #f5f5f5; border-radius: 8px; padding: 20px; text-align: center;">
                        <i class="fas fa-network-wired" style="font-size: 64px; color: #0a66c2; margin-bottom: 20px;"></i>
                        <h3 style="margin-bottom: 16px;">Build your network</h3>
                        <p>Connect with professionals in your industry</p>
                    </div>
                </div>
            </section>

            <section style="margin-top: 60px;">
                <h2 style="text-align: center; font-size: 32px; margin-bottom: 40px;">Features of LinkedIn Clone</h2>
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 24px;">
                    <div style="flex: 1; min-width: 250px; background-color: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <i class="fas fa-user-circle" style="font-size: 36px; color: #0a66c2; margin-bottom: 16px;"></i>
                        <h3 style="margin-bottom: 12px;">Professional Profile</h3>
                        <p>Create a comprehensive profile showcasing your skills, experience, and education.</p>
                    </div>
                    <div style="flex: 1; min-width: 250px; background-color: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <i class="fas fa-users" style="font-size: 36px; color: #0a66c2; margin-bottom: 16px;"></i>
                        <h3 style="margin-bottom: 12px;">Network Building</h3>
                        <p>Connect with colleagues, classmates, and industry professionals to expand your network.</p>
                    </div>
                    <div style="flex: 1; min-width: 250px; background-color: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <i class="fas fa-comment-dots" style="font-size: 36px; color: #0a66c2; margin-bottom: 16px;"></i>
                        <h3 style="margin-bottom: 12px;">Messaging</h3>
                        <p>Communicate directly with your connections through our messaging system.</p>
                    </div>
                </div>
            </section>
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
    // JavaScript for page redirection (as requested)
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

<?php
session_start();
include '../db/db_connect.php';

// Initialize variables
$errorMsg = '';
$successMsg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';

    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $errorMsg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $errorMsg = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirmPassword) {
        $errorMsg = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT userID FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errorMsg = "Email address already registered.";
        } else {
            // Insert new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'customer'; // Default role for new users

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                $successMsg = "Account created successfully! Please login.";
                // Redirect to login page after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $errorMsg = "Error creating account: " . $conn->error;
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickShop - Sign Up</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/signup.css">
</head>
<body>
<br>
<header class="header">
    <div class="logo">QuickShop</div>
</header>
<br>
<div class="signup-container">
    <div class="signup-card">
        <div class="shape-decorator shape-1"></div>
        <div class="shape-decorator shape-2"></div>
        <h2 class="form-title">Create Account</h2>

        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="input-group">
                <input type="text" id="name" name="name" required
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                <label for="name">Full Name</label>
            </div>

            <div class="input-group">
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                <label for="email">Email Address</label>
            </div>

            <div class="input-group">
                <input type="password" id="password" name="password" required>
                <label for="password">Password</label>
            </div>

            <div class="input-group">
                <input type="password" id="confirm-password" name="confirm-password" required>
                <label for="confirm-password">Confirm Password</label>
            </div>

            <button type="submit" class="auth-button">Create Account</button>

            <div class="terms">
                By creating an account, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
            </div>

            <div class="social-signup">
                <p>Or sign up with</p>
                <br>
                <div class="social-buttons">
                    <a href="#" class="social-button google">
                        <i class="fab fa-google"></i>
                    </a>
                    <a href="#" class="social-button facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-button apple">
                        <i class="fab fa-apple"></i>
                    </a>
                </div>
            </div>

            <div class="login-link">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-hide alerts after 3 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.display = 'none';
            }, 3000);
        });
    });
</script>
</body>
</html>
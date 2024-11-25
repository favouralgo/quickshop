<?php
session_start();
include '../db/db_connect.php';

// Initialize variables
$errorMsg = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email) || empty($password)) {
        $errorMsg = "Please enter both email and password.";
    } else {
        // Prepare SQL to prevent injection
        $stmt = $conn->prepare("SELECT userID, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Verify password
            if (password_verify($password, $row['password'])) {
                // Clear any existing session data
                session_unset();
                session_destroy();
                session_start();

                // Set new session variables
                $_SESSION['user_id'] = $row['userID'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];

                // Set last login time
                $_SESSION['last_login'] = time();

                // Redirect based on role
                switch ($row['role']) {
                    case 'administrator':
                        header('Location: admin/admindashboard.php');
                        exit();
                    case 'customer':
                        header('Location: admin/customerdashboard.php');
                        exit();
                    case 'sales':
                        header('Location: admin/salesdashboard.php');
                        exit();
                    default:
                        $errorMsg = "Invalid user role.";
                }
            } else {
                $errorMsg = "Invalid email or password.";
            }
        } else {
            $errorMsg = "Invalid email or password.";
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
    <title>QuickShop - Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
<header class="header">
    <div class="logo">QuickShop</div>
</header>

<div class="login-container">
    <div class="login-card">
        <h2 class="form-title">Welcome Back</h2>

        <?php if ($errorMsg): ?>
            <div class="alert alert-error" id="errorAlert">
                <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="input-group">
                <input type="email" id="login-email" name="email" required
                       value="<?php echo htmlspecialchars($email); ?>">
                <label for="login-email">Email Address</label>
            </div>
            <div class="input-group">
                <input type="password" id="login-password" name="password" required>
                <label for="login-password">Password</label>
            </div>
            <button type="submit" class="auth-button">Login</button>
            <div class="auth-links">
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
            <div class="social-login">
                <p>Or continue with</p>
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
            <div class="signup-link">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-hide error messages after 3 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) {
            setTimeout(() => {
                errorAlert.style.display = 'none';
            }, 3000);
        }
    });
</script>
</body>
</html>
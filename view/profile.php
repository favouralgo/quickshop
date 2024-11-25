<?php
session_start();
include '../db/db_connect.php';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Get user ID from session
$userID = $_SESSION['user_id'] ?? null;

if (!$userID) {
    header('Location: login.php');
    exit();
}

// Initialize message variables
$successMsg = '';
$errorMsg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Start building the update query
    $updateFields = [];
    $params = [];
    $types = '';

    // Add basic fields to update
    if ($name) {
        $updateFields[] = "name = ?";
        $params[] = $name;
        $types .= 's';
    }
    if ($email) {
        $updateFields[] = "email = ?";
        $params[] = $email;
        $types .= 's';
    }

    // Handle password update if provided
    if ($password || $confirmPassword) {
        if ($password !== $confirmPassword) {
            $errorMsg = "Passwords do not match!";
        } else if (strlen($password) < 8) {
            $errorMsg = "Password must be at least 8 characters long!";
        } else {
            $updateFields[] = "password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
            $types .= 's';
        }
    }

    // If there are fields to update and no errors
    if (!empty($updateFields) && empty($errorMsg)) {
        $query = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE userID = ?";
        $params[] = $userID;
        $types .= 'i';

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $successMsg = "Profile updated successfully!";
        } else {
            $errorMsg = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT name, email, role FROM users WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickShop - Profile Settings</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/profile.css">
</head>
<body>
<div class="sidebar">
    <div class="logo">QuickShop</div>
    <ul class="nav-links">
        <li><a href="admin/dashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
        <li><a href="orders.php"><i class="fas fa-shopping-bag"></i>My Orders</a></li>
        <li><a href="products.php"><i class="fas fa-box"></i>Products</a></li>
        <li><a href="profile.php" class="active"><i class="fas fa-user"></i>Profile</a></li>
        <li><a href="../actions/logout.php" onclick="event.preventDefault(); logoutUser();">
                <i class="fas fa-sign-out-alt"></i>Logout
            </a></li>    </ul>
</div>

<div class="main-content">
    <div class="profile-section">
        <h2 class="section-title">Profile Settings</h2>

        <?php if ($successMsg): ?>
            <div class="alert alert-success" id="successAlert"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-error" id="errorAlert"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <form class="profile-form" id="profileForm" method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userData['name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <input type="text" id="role" value="<?php echo htmlspecialchars($userData['role'] ?? ''); ?>" disabled>
            </div>

            <div class="form-group">
                <label for="password">Change Password</label>
                <input type="password" id="password" name="password" placeholder="Enter new password">
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm New Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm new password">
            </div>

            <div class="action-buttons">
                <button type="submit" class="save-button">Save Changes</button>
                <button type="button" class="cancel-button" onclick="window.location.href='dashboard.php'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>

    function logoutUser() {
        // Clear sessions (usually handled on server side)
        fetch('../actions/logout.php', {
            method: 'POST'
        }).then(response => {
            if (response.ok) {
                // Redirect to login page after logout
                window.location.href = 'login.php';
            }
        });
    }

    // Handle alert messages
    document.addEventListener('DOMContentLoaded', function() {
        const successAlert = document.getElementById('successAlert');
        const errorAlert = document.getElementById('errorAlert');

        if (successAlert) {
            setTimeout(() => {
                successAlert.style.display = 'none';
            }, 3000);
        }

        if (errorAlert) {
            setTimeout(() => {
                errorAlert.style.display = 'none';
            }, 3000);
        }
    });

    // Add active class to current nav item
    const navLinks = document.querySelectorAll('.nav-links a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
</script>
<script type="text/javascript">
    (function() {
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    })();
</script>
</body>
</html>
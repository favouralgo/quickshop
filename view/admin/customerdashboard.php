<?php
require_once 'auth_middleware.php';
include '../../db/db_connect.php';

// Check if user is a customer
checkUserRole(['customer']);

// Get user ID and verify it matches the logged-in user
$userID = getUserId();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Get user's name with prepared statement
$user_query = "SELECT name FROM users WHERE userID = ? AND role = 'customer'";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);

if (!$user_data) {
    // If user not found or not a customer, redirect to login
    header("Location: ../login.php");
    exit();
}

$user_name = $user_data['name'];

// Count total orders for this user
$total_orders_query = "SELECT COUNT(*) as count FROM orders WHERE userID = ?";
$stmt = mysqli_prepare($conn, $total_orders_query);
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$total_orders = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'] ?? 0;

// Calculate total amount spent by user
$total_spent_query = "SELECT SUM(total_amount) as total FROM orders WHERE userID = ?";
$stmt = mysqli_prepare($conn, $total_spent_query);
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$total_spent = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0;

// Get recent orders with item count
$recent_orders_query = "
    SELECT o.orderID, o.date, o.total_amount,
           (SELECT COUNT(*) FROM orderdetails WHERE orderID = o.orderID) as item_count
    FROM orders o
    WHERE o.userID = ?
    ORDER BY o.date DESC
    LIMIT 3";
$stmt = mysqli_prepare($conn, $recent_orders_query);
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$recent_orders = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickShop - Customer Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/customers.css">
</head>
<body>
<div class="sidebar">
    <div class="logo">QuickShop</div>
    <ul class="nav-links">
        <li><a href="customerdashboard.php" class="active"><i class="fas fa-home"></i>Dashboard</a></li>
        <?php if (canViewProducts()): ?>
        <li><a href="../products.php"><i class="fas fa-box"></i>Products</a></li>
        <?php endif; ?>
        <?php if (canViewOwnOrders()): ?>
        <li><a href="../customer_orders.php"><i class="fas fa-shopping-bag"></i>My Orders</a></li>
        <?php endif; ?>
        <li><a href="../profile.php"><i class="fas fa-user"></i>Profile</a></li>
        <li><a href="../../actions/logout.php" onclick="event.preventDefault(); logoutUser();">
                <i class="fas fa-sign-out-alt"></i>Logout
            </a></li>
    </ul>
</div>

<div class="main-content">
    <div class="welcome-banner">
        <h2>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h2>
        <p>Track your orders and discover new products</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="title">Total Orders made</div>
            <div class="value"><?php echo number_format($total_orders); ?></div>
        </div>
        <div class="stat-card">
            <div class="title">Total amount you spent</div>
            <div class="value">$<?php echo number_format($total_spent, 2); ?></div>
        </div>
    </div>

    <div class="orders-section">
        <h2 class="section-title">Recent Orders You Placed</h2>
        <?php if (canViewOwnOrders()): ?>
        <a href="../customer_orders.php" class="action-button">View All Orders</a>
        <?php endif; ?>
        <table class="orders-table">
            <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($order['orderID']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['date'])); ?></td>
                        <td><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No orders found</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function logoutUser() {
        fetch('../../actions/logout.php', {
            method: 'POST',
            credentials: 'same-origin'
        }).then(response => {
            if (response.ok) {
                window.location.href = '../login.php';
            }
        }).catch(error => {
            console.error('Logout error:', error);
        });
    }

    // Add active class to current nav item
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.nav-links a');
        const currentPath = window.location.pathname;
        
        navLinks.forEach(link => {
            if (currentPath.includes(link.getAttribute('href'))) {
                link.classList.add('active');
            }
            
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href') === '#') {
                    e.preventDefault();
                }
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    });
</script>

<?php mysqli_close($conn); ?>
</body>
</html>
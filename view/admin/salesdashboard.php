<?php
session_start();
// Database connection
include '../../db/db_connect.php';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get today's date in Y-m-d format
$today = date('Y-m-d');

// Query for today's orders count
$today_orders_query = "SELECT COUNT(*) as today_orders 
                      FROM orders 
                      WHERE DATE(date) = '$today'";
$today_orders_result = $conn->query($today_orders_query);
$today_orders = $today_orders_result->fetch_assoc()['today_orders'];

// Query for today's revenue
$today_revenue_query = "SELECT SUM(total_amount) as today_revenue 
                       FROM orders 
                       WHERE DATE(date) = '$today'";
$today_revenue_result = $conn->query($today_revenue_query);
$today_revenue = $today_revenue_result->fetch_assoc()['today_revenue'];
$today_revenue = $today_revenue ?: 0; // If null, set to 0

// Query for total orders
$total_orders_query = "SELECT COUNT(*) as total_orders FROM orders";
$total_orders_result = $conn->query($total_orders_query);
$total_orders = $total_orders_result->fetch_assoc()['total_orders'];

// Fetch recent orders with products
$recent_orders_query = "
    SELECT o.orderID, u.name as customer_name, 
           GROUP_CONCAT(p.name SEPARATOR ', ') as products,
           o.date, o.total_amount 
    FROM orders o 
    JOIN users u ON o.userID = u.userID 
    JOIN orderDetails oi ON o.orderID = oi.orderID
    JOIN products p ON oi.productID = p.productID
    GROUP BY o.orderID
    ORDER BY o.date DESC 
    LIMIT 3";
$recent_orders = mysqli_query($conn, $recent_orders_query);

// Fetch latest products
$latest_products_query = "
    SELECT productID, name, description, price, quantity 
    FROM products 
    LIMIT 3";
$latest_products = mysqli_query($conn, $latest_products_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickShop - Sales Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sales.css">
</head>
<body>
<div class="sidebar">
    <div class="logo">QuickShop</div>
    <ul class="nav-links">
        <li><a href="salesdashboard.php" class="active"><i class="fas fa-home"></i>Dashboard</a></li>
        <li><a href="../orders.php"><i class="fas fa-shopping-cart"></i>Orders</a></li>
        <li><a href="../products.php"><i class="fas fa-box"></i>Products</a></li>
        <li><a href="../profile.php"><i class="fas fa-user"></i>Profile</a></li>
        <li><a href="../../actions/logout.php" onclick="event.preventDefault(); logoutUser();">
                <i class="fas fa-sign-out-alt"></i>Logout
            </a></li>    </ul>
</div>

<div class="main-content">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="title">Today's Orders</div>
            <div class="value"><?php echo $today_orders; ?></div>
        </div>
        <div class="stat-card">
            <div class="title">Today's Revenue</div>
            <div class="value">$<?php echo number_format($today_revenue, 2); ?></div>
        </div>
        <div class="stat-card">
            <div class="title">Total Orders</div>
            <div class="value"><?php echo $total_orders; ?></div>
        </div>
    </div>

    <div class="orders-section">
        <div class="section-title">
            <span>Recent Orders</span>
            <button class="action-button">View Orders</button>
        </div>
        <table class="orders-table">
            <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Products</th>
                <th>Date</th>
                <th>Amount</th>
            </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($order['orderID']); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['products']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['date'])); ?></td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No recent orders found</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="products-section">
        <div class="section-title">
            <span>Latest Products</span>
            <button class="action-button">View All Products</button>
        </div>

        <table class="products-table">
            <thead>
            <tr>
                <th>Product ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Price</th>
                <th>Stock Status</th>
            </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($latest_products) > 0): ?>
                <?php while($product = mysqli_fetch_assoc($latest_products)): ?>
                    <tr>
                        <td>#P<?php echo htmlspecialchars($product['productID']); ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <td>
                            <?php
                            $quantity = $product['quantity'];
                            if ($quantity <= 0) {
                                echo '<span class="stock-status out-of-stock">Out of Stock</span>';
                            } elseif ($quantity <= 5) {
                                echo '<span class="stock-status low-stock">Low Stock (' . $quantity . ')</span>';
                            } else {
                                echo '<span class="stock-status in-stock">In Stock (' . $quantity . ')</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No products found</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // JavaScript function to handle logout
    function logoutUser() {
        // Clear sessions (usually handled on server side)
        fetch('../../actions/logout.php', {
            method: 'POST'
        }).then(response => {
            if (response.ok) {
                // Redirect to login page after logout
                window.location.href = '../login.php';
            }
        });
    }

    // Add active class to current nav item
    const navLinks = document.querySelectorAll('.nav-links a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Action buttons functionality
    document.querySelectorAll('.action-button').forEach(button => {
        button.addEventListener('click', function() {
            console.log('Action button clicked');
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
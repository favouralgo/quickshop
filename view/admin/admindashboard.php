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

// Fetch user statistics with NULL checks
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'] ?? 0;
$sales_personnel = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'sales'"))['count'] ?? 0;
$inventory_managers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'inventory'"))['count'] ?? 0;
$customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'customer'"))['count'] ?? 0;

// Fetch order statistics with NULL checks
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'] ?? 0;
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders"))['total'] ?? 0;

// Fetch recent orders
$recent_orders_query = "
    SELECT o.orderID, u.name as customer_name, o.date, o.total_amount 
    FROM orders o 
    JOIN users u ON o.userID = u.userID 
    ORDER BY o.date DESC 
    LIMIT 3";
$recent_orders = mysqli_query($conn, $recent_orders_query);

// Fetch latest products
$latest_products_query = "
    SELECT productID, name, description, price, quantity 
    FROM products
    LIMIT 3";
$latest_products = mysqli_query($conn, $latest_products_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickShop - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<div class="sidebar">
    <div class="logo">QuickShop</div>
    <ul class="nav-links">
        <li><a href="admindashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
        <li><a href="../users.html"><i class="fas fa-users"></i>Users</a></li>
        <li><a href="../orders.html"><i class="fas fa-shopping-cart"></i>Orders</a></li>
        <li><a href="../products.html"><i class="fas fa-box"></i>Products</a></li>
        <li><a href="../profile.php"><i class="fas fa-user"></i>Profile</a></li>
        <li><a href="../../actions/logout.php" onclick="event.preventDefault(); logoutUser();">
                <i class="fas fa-sign-out-alt"></i>Logout
            </a></li>
    </ul>
</div>

<div class="main-content">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="title">Total Users</div>
            <div class="value"><?php echo number_format($total_users); ?></div>
        </div>
        <div class="stat-card">
            <div class="title">Sales Personnel</div>
            <div class="value"><?php echo number_format($sales_personnel); ?></div>
        </div>
        <div class="stat-card">
            <div class="title">Inventory Managers</div>
            <div class="value"><?php echo number_format($inventory_managers); ?></div>
        </div>
        <div class="stat-card">
            <div class="title">Customers</div>
            <div class="value"><?php echo number_format($customers); ?></div>
        </div>
        <div class="stat-card">
            <div class="title">Total Revenue</div>
            <div class="value">$<?php echo number_format($total_revenue, 2) ?? 0; ?></div>
        </div>
        <div class="stat-card">
            <div class="title">Total Orders</div>
            <div class="value"><?php echo number_format($total_orders); ?></div>
        </div>
    </div>

    <div class="orders-section">
        <h2 class="section-title">Recent Orders</h2>
        <a href="../orders.html">
            <button class="action-button">View All Orders</button>
        </a>
        <table class="orders-table">
            <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Amount</th>
            </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($order['orderID'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown Customer'); ?></td>
                        <td><?php echo $order['date'] ? date('M d, Y', strtotime($order['date'])) : 'N/A'; ?></td>
                        <td>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No recent orders found</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="products-section">
        <div class="section-title">
            <span>Latest Products</span>
            <a href="../products.html">
                <button class="action-button">View All Products</button>
            </a>
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
                        <td>#P<?php echo htmlspecialchars($product['productID'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($product['name'] ?? 'Unknown Product'); ?></td>
                        <td><?php echo htmlspecialchars($product['description'] ?? 'No description available'); ?></td>
                        <td>$<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                        <td>
                            <?php
                            $quantity = $product['quantity'] ?? 0;
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
                    <td colspan="5" class="text-center">No products found</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
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

<?php
// Close database connection
mysqli_close($conn);
?>
</body>
</html>
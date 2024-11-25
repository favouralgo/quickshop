<?php
require_once 'auth_middleware.php';
include '../../db/db_connect.php';

// Check if user has inventory role
checkUserRole(['inventory']);

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Get critical stock level information
$low_stock_query = "SELECT COUNT(*) as low_stock_count FROM products WHERE quantity <= 5";
$low_stock_result = $conn->query($low_stock_query);
$low_stock_count = $low_stock_result->fetch_assoc()['low_stock_count'];

$out_of_stock_query = "SELECT COUNT(*) as out_of_stock_count FROM products WHERE quantity = 0";
$out_of_stock_result = $conn->query($out_of_stock_query);
$out_of_stock_count = $out_of_stock_result->fetch_assoc()['out_of_stock_count'];

// Get all products count
$total_products_query = "SELECT COUNT(*) as total_products FROM products";
$total_products_result = $conn->query($total_products_query);
$total_products = $total_products_result->fetch_assoc()['total_products'];

// Fetch products that need stock replenishment
$low_stock_products_query = "
    SELECT productID, name, description, price, quantity 
    FROM products 
    WHERE quantity <= 5 
    ORDER BY quantity ASC 
    LIMIT 5";
$low_stock_products = mysqli_query($conn, $low_stock_products_query);

// Fetch recent order details to monitor product movements
$recent_order_details_query = "
    SELECT od.orderID, p.name as product_name, 
           od.quantity as sold_quantity, 
           o.date, 
           p.quantity as current_stock,
           p.productID
    FROM orderdetails od
    JOIN orders o ON od.orderID = o.orderID
    JOIN products p ON od.productID = p.productID
    ORDER BY o.date DESC 
    LIMIT 10";
$recent_order_details = mysqli_query($conn, $recent_order_details_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickShop - Inventory Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
<div class="sidebar">
    <div class="logo">QuickShop</div>
    <ul class="nav-links">
        <li><a href="inventorydashboard.php" class="active"><i class="fas fa-home"></i>Dashboard</a></li>
        <li><a href="../products.php"><i class="fas fa-box"></i>Products</a></li>
        <li><a href="../orders.php"><i class="fas fa-shopping-cart"></i>View Orders</a></li>
        <li><a href="../profile.php"><i class="fas fa-user"></i>Profile</a></li>
        <li><a href="../../actions/logout.php" onclick="event.preventDefault(); logoutUser();">
                <i class="fas fa-sign-out-alt"></i>Logout
            </a></li>
    </ul>
</div>

<div class="main-content">
    <div class="welcome-banner">
        <h2>Inventory Management Dashboard</h2>
        <p>Monitor and manage product stock levels</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="title">Low Stock Items</div>
            <div class="value"><?php echo $low_stock_count; ?></div>
        </div>
        <div class="stat-card">
            <div class="title">Out of Stock Items</div>
            <div class="value"><?php echo $out_of_stock_count; ?></div>
        </div>
        <div class="stat-card">
            <div class="title">Total Products</div>
            <div class="value"><?php echo $total_products; ?></div>
        </div>
    </div>

    <div class="products-section">
        <div class="section-header">
            <h2 class="section-title">Critical Stock Levels</h2>
            <a href="../products.php" class="action-button">Manage Stock</a>
        </div>
        <table class="products-table">
            <thead>
            <tr>
                <th>Product ID</th>
                <th>Name</th>
                <th>Current Stock</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($low_stock_products) > 0): ?>
                <?php while($product = mysqli_fetch_assoc($low_stock_products)): ?>
                    <tr>
                        <td>#P<?php echo htmlspecialchars($product['productID']); ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo $product['quantity']; ?></td>
                        <td>
                            <?php if ($product['quantity'] <= 0): ?>
                                <span class="stock-status out-of-stock">Out of Stock</span>
                            <?php else: ?>
                                <span class="stock-status low-stock">Low Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="../products.php?edit=<?php echo $product['productID']; ?>" 
                               class="action-button">Update Stock</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No products with critical stock levels</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="orders-section">
        <div class="section-header">
            <h2 class="section-title">Recent Product Movements</h2>
            <a href="../orders.php" class="action-button">View All Orders</a>
        </div>
        <table class="orders-table">
            <thead>
            <tr>
                <th>Order ID</th>
                <th>Product</th>
                <th>Quantity Sold</th>
                <th>Date</th>
                <th>Remaining Stock</th>
            </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($recent_order_details) > 0): ?>
                <?php while($order = mysqli_fetch_assoc($recent_order_details)): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($order['orderID']); ?></td>
                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                        <td><?php echo $order['sold_quantity']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['date'])); ?></td>
                        <td>
                            <span class="<?php echo $order['current_stock'] <= 5 ? 'low-stock' : ''; ?>">
                                <?php echo $order['current_stock']; ?> units
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No recent order details found</td>
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
        });
    });
</script>
</body>
</html>
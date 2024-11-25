<?php
$pageTitle = 'Admin Dashboard';
require_once '../includes/header.php';
require_once '../includes/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrator') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();

// Get quick statistics
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_products' => $db->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'],
    'total_orders' => $db->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'],
    'total_revenue' => $db->query("SELECT SUM(total_amount) as total FROM orders")->fetch_assoc()['total']
];

// Get recent orders
$recent_orders = $db->query("SELECT orders.*, users.name FROM orders 
                            JOIN users ON orders.user_id = users.id 
                            ORDER BY orders.created_at DESC LIMIT 5");
?>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <h2><?php echo $stats['total_users']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Total Products</h5>
                <h2><?php echo $stats['total_products']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Total Orders</h5>
                <h2><?php echo $stats['total_orders']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">Total Revenue</h5>
                <h2>$<?php echo number_format($stats['total_revenue'], 2); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Orders</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo $order['name']; ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($order['status']) {
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'shipped' => 'primary',
                                                'delivered' => 'success',
                                                'cancelled' => 'danger'
                                            };
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="view_order.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-primary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$db->close();
require_once '../includes/footer.php'; 
?>
<?php
require_once 'admin/auth_middleware.php';
include '../db/db_connect.php';

// Check if user is a customer
checkUserRole(['customer']);

// Get user ID
$userID = getUserId();

// Handle AJAX requests for order details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'getOrderDetails') {
        $orderId = $_POST['orderId'];
        
        // Verify this order belongs to the customer
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE orderID = ? AND userID = ?");
        $stmt->bind_param("ii", $orderId, $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->fetch_assoc()['count'] === 0) {
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }
        
        // Get order details
        $query = "
            SELECT od.*, p.name as product_name 
            FROM orderdetails od
            JOIN products p ON od.productID = p.productID
            WHERE od.orderID = ?";
            
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'items' => $items]);
        exit;
    }
}

// Get all orders for this customer
$query = "
    SELECT o.*, 
           (SELECT COUNT(*) FROM orderdetails WHERE orderID = o.orderID) as item_count
    FROM orders o
    WHERE o.userID = ?
    ORDER BY o.date DESC";
    
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userID);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - QuickShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/customers.css">
    <style>
        .orders-container {
            padding: 20px;
        }
        
        .order-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .order-date {
            color: #666;
            font-size: 0.9em;
        }
        
        .order-total {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .view-details-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }
        
        .view-details-btn:hover {
            background: #2980b9;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
        }
        
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .order-items-table th,
        .order-items-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px;
            color: #666;
            line-height: 1.8;
        }


        .no-orders h3 {
            margin: 20px 0; 
            color: #333;
        }

        .no-orders p {
            margin-bottom: 25px; 
            color: #666;
        }

        .no-orders .browse-products-btn {
            background-color: #4CAF50; 
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none; 
            display: inline-block;
            transition: background-color 0.3s ease;
        }

        .no-orders .browse-products-btn:hover {
            background-color: #45a049; 
        }

    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">QuickShop</div>
        <ul class="nav-links">
            <li><a href="admin/customerdashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
            <?php if (canViewProducts()): ?>
            <li><a href="products.php"><i class="fas fa-box"></i>Products</a></li>
            <?php endif; ?>
            <li><a href="customer_orders.php" class="active"><i class="fas fa-shopping-bag"></i>My Orders</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="../actions/logout.php" onclick="event.preventDefault(); logoutUser();">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2>My Orders</h2>
        </div>

        <div class="orders-container">
            <?php if ($orders->num_rows > 0): ?>
                <?php while($order = $orders->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <h3>Order #<?php echo htmlspecialchars($order['orderID']); ?></h3>
                                <span class="order-date">
                                    <?php echo date('F j, Y', strtotime($order['date'])); ?>
                                </span>
                            </div>
                            <div>
                                <span class="order-total">
                                    $<?php echo number_format($order['total_amount'], 2); ?>
                                </span>
                                <button class="view-details-btn" onclick="viewOrderDetails(<?php echo $order['orderID']; ?>)">
                                    View Details
                                </button>
                            </div>
                        </div>
                        <div>
                            <?php echo $order['item_count']; ?> item(s)
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-cart fa-3x"></i>
                    <h3>No Orders Yet</h3>
                    <p>Start shopping to see your orders here!</p>
                    <a href="products.php" class="browse-products-btn">Browse Products</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal" id="orderDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Order Details</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div id="orderDetailsContent"></div>
        </div>
    </div>

    <script>
        function viewOrderDetails(orderId) {
            fetch('customer_orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=getOrderDetails&orderId=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const content = document.getElementById('orderDetailsContent');
                    let total = 0;
                    
                    content.innerHTML = `
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.items.map(item => {
                                    const itemTotal = item.quantity * item.price;
                                    total += itemTotal;
                                    return `
                                        <tr>
                                            <td>${item.product_name}</td>
                                            <td>${item.quantity}</td>
                                            <td>$${parseFloat(item.price).toFixed(2)}</td>
                                            <td>$${itemTotal.toFixed(2)}</td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align: right"><strong>Total:</strong></td>
                                    <td><strong>$${total.toFixed(2)}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                    
                    document.getElementById('orderDetailsModal').classList.add('active');
                } else {
                    alert('Error loading order details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading order details');
            });
        }

        function closeModal() {
            document.getElementById('orderDetailsModal').classList.remove('active');
        }

        function logoutUser() {
            fetch('../actions/logout.php', {
                method: 'POST',
                credentials: 'same-origin'
            }).then(response => {
                if (response.ok) {
                    window.location.href = 'login.php';
                }
            }).catch(error => {
                console.error('Logout error:', error);
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>
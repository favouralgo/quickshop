<?php
require_once 'admin/auth_middleware.php';
include '../db/db_connect.php';

// Check if user can at least view orders
checkUserRole(['administrator', 'sales', 'inventory']);

// Get user's role and permissions
$userRole = getUserRole();
$canManageOrders = canManageOrders();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (!$canManageOrders) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                    exit;
                }
                
                // Start transaction
                $conn->begin_transaction();
                try {
                    // Insert into orders table
                    $stmt = $conn->prepare("INSERT INTO orders (userID, date, total_amount) VALUES (?, ?, ?)");
                    $stmt->bind_param("isd", $_POST['userID'], $_POST['date'], $_POST['total_amount']);
                    $stmt->execute();
                    $orderId = $conn->insert_id;

                    // Insert order items
                    $stmt = $conn->prepare("INSERT INTO orderdetails (orderID, productID, quantity, price) VALUES (?, ?, ?, ?)");
                    foreach ($_POST['items'] as $item) {
                        $stmt->bind_param("iiid", $orderId, $item['productId'], $item['quantity'], $item['price']);
                        $stmt->execute();
                    }

                    $conn->commit();
                    echo json_encode(['success' => true, 'orderId' => $orderId]);
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                break;

            case 'update':
                if (!$canManageOrders) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                    exit;
                }

                $conn->begin_transaction();
                try {
                    // Update order
                    $stmt = $conn->prepare("UPDATE orders SET date = ?, total_amount = ? WHERE orderID = ?");
                    $stmt->bind_param("sdi", $_POST['date'], $_POST['total_amount'], $_POST['orderId']);
                    $stmt->execute();

                    // Delete existing items
                    $stmt = $conn->prepare("DELETE FROM orderdetails WHERE orderID = ?");
                    $stmt->bind_param("i", $_POST['orderId']);
                    $stmt->execute();

                    // Insert new items
                    $stmt = $conn->prepare("INSERT INTO orderdetails (orderID, productID, quantity, price) VALUES (?, ?, ?, ?)");
                    foreach ($_POST['items'] as $item) {
                        $stmt->bind_param("iiid", $_POST['orderId'], $item['productId'], $item['quantity'], $item['price']);
                        $stmt->execute();
                    }

                    $conn->commit();
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                break;

            case 'delete':
                if (!$canManageOrders) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                    exit;
                }

                $conn->begin_transaction();
                try {
                    // Delete order items first
                    $stmt = $conn->prepare("DELETE FROM orderdetails WHERE orderID = ?");
                    $stmt->bind_param("i", $_POST['orderId']);
                    $stmt->execute();

                    // Delete order
                    $stmt = $conn->prepare("DELETE FROM orders WHERE orderID = ?");
                    $stmt->bind_param("i", $_POST['orderId']);
                    $stmt->execute();

                    $conn->commit();
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                break;

            case 'fetch':
                // Fetch orders with user details
                $query = "SELECT o.*, u.name as customerName, u.email as customerEmail 
                         FROM orders o 
                         JOIN users u ON o.userID = u.userID 
                         ORDER BY o.orderID DESC";
                $result = $conn->query($query);
                $orders = [];
                
                if ($result) {
                    while ($order = $result->fetch_assoc()) {
                        // Fetch order items
                        $itemsQuery = "SELECT od.*, p.name 
                                     FROM orderdetails od 
                                     JOIN products p ON od.productID = p.productID 
                                     WHERE od.orderID = ?";
                        $stmt = $conn->prepare($itemsQuery);
                        $stmt->bind_param("i", $order['orderID']);
                        $stmt->execute();
                        $itemsResult = $stmt->get_result();
                        
                        $order['items'] = [];
                        while ($item = $itemsResult->fetch_assoc()) {
                            $order['items'][] = $item;
                        }
                        
                        $orders[] = $order;
                    }
                }
                
                echo json_encode(['success' => true, 'orders' => $orders]);
                break;
        }
        exit;
    }
}

// Fetch initial products for dropdown
$productsQuery = "SELECT productID as id, name, price FROM products WHERE quantity > 0";
$productsResult = $conn->query($productsQuery);
$products = [];
if ($productsResult) {
    while ($row = $productsResult->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickShop - Orders Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/orders.css">
</head>
<body>
    <div class="sidebar">
        <div class="logo">QuickShop</div>
        <ul class="nav-links">
            <?php if ($userRole === 'administrator'): ?>
                <li><a href="admin/admindashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i>Users</a></li>
            <?php elseif ($userRole === 'sales'): ?>
                <li><a href="admin/salesdashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
            <?php elseif ($userRole === 'inventory'): ?>
                <li><a href="admin/dashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
            <?php endif; ?>
            
            <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i>Orders</a></li>
            <?php if (canViewProducts()): ?>
                <li><a href="products.php"><i class="fas fa-box"></i>Products</a></li>
            <?php endif; ?>
            <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="actions-bar">
            <div class="search-box">
                <input type="text" placeholder="Search orders..." id="searchInput">
                <i class="fas fa-search"></i>
            </div>
            <?php if ($canManageOrders): ?>
            <button class="add-order-btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i> New Order
            </button>
            <?php endif; ?>
        </div>

        <div class="orders-table-container">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($canManageOrders): ?>
    <!-- Add/Edit Order Modal -->
    <div class="modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">New Order</h2>
                <button class="close-modal" onclick="closeModal('orderModal')">&times;</button>
            </div>
            <form id="orderForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="customerName">Customer Name</label>
                        <input type="text" id="customerName" required>
                    </div>
                    <div class="form-group">
                        <label for="customerEmail">Customer Email</label>
                        <input type="email" id="customerEmail" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="orderDate">Order Date</label>
                        <input type="date" id="orderDate" required>
                    </div>
                </div>
                
                <h3>Order Items</h3>
                <button type="button" class="add-item-btn" onclick="addOrderItem()">
                    <i class="fas fa-plus"></i> Add Item
                </button>
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="orderItemsBody">
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Total Amount:</strong></td>
                            <td id="orderTotal">$0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="modal-footer">
                    <button type="button" class="modal-btn cancel-btn" onclick="closeModal('orderModal')">Cancel</button>
                    <button type="submit" class="modal-btn save-btn">Save Order</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- View Order Modal -->
    <div class="modal" id="viewOrderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Order Details</h2>
                <button class="close-modal" onclick="closeModal('viewOrderModal')">&times;</button>
            </div>
            <div id="orderDetails">
                <!-- Order details will be populated here -->
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel-btn" onclick="closeModal('viewOrderModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        const canManageOrders = <?php echo json_encode($canManageOrders); ?>;
        const products = <?php echo json_encode($products); ?>;
        let orders = [];
        let currentOrderId = null;

        function fetchOrders() {
            fetch('orders.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=fetch'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    orders = data.orders;
                    initializeTable();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function createOrderRow(order) {
            const row = document.createElement('tr');
            const formattedDate = new Date(order.date).toLocaleDateString();

            let actionsHtml = `
                <button class="action-btn view-btn" onclick="openViewModal(${order.orderID})">
                    <i class="fas fa-eye"></i> View
                </button>
            `;

            if (canManageOrders) {
                actionsHtml += `
                    <button class="action-btn edit-btn" onclick="openEditModal(${order.orderID})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="action-btn delete-btn" onclick="deleteOrder(${order.orderID})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                `;
            }

            row.innerHTML = `
                <td>#${order.orderID}</td>
                <td>${formattedDate}</td>
                <td>${order.customerName}</td>
                <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                <td>
                    <div class="action-buttons">
                        ${actionsHtml}
                    </div>
                </td>
            `;
            
            return row;
        }

        function initializeTable() {
            const tableBody = document.getElementById('ordersTableBody');
            tableBody.innerHTML = '';
            orders.forEach(order => {
                tableBody.appendChild(createOrderRow(order));
            });
        }

        // Keep your existing modal functions (openModal, closeModal)
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            if (modalId === 'orderModal') {
                resetForm();
            }
        }

        function openAddModal() {
            currentOrderId = null;
            document.getElementById('modalTitle').textContent = 'New Order';
            document.getElementById('orderForm').reset();
            document.getElementById('orderItemsBody').innerHTML = '';
            document.getElementById('orderDate').valueAsDate = new Date();
            openModal('orderModal');
        }

        function openEditModal(orderId) {
            currentOrderId = orderId;
            const order = orders.find(o => o.orderID == orderId);
            
            document.getElementById('modalTitle').textContent = 'Edit Order';
            document.getElementById('customerName').value = order.customerName;
            document.getElementById('customerEmail').value = order.customerEmail;
            document.getElementById('orderDate').value = order.date;
            
            document.getElementById('orderItemsBody').innerHTML = '';
            order.items.forEach(item => {
                addOrderItem(item);
            });
            
            openModal('orderModal');
        }

        function openViewModal(orderId) {
            const order = orders.find(o => o.orderID == orderId);
            const formattedDate = new Date(order.date).toLocaleDateString();
            
            const orderDetails = document.getElementById('orderDetails');
            orderDetails.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <h3>Order #${order.orderID}</h3>
                    <p><strong>Date:</strong> ${formattedDate}</p>
                    <p><strong>Customer:</strong> ${order.customerName}</p>
                    <p><strong>Email:</strong> ${order.customerEmail}</p>
                </div>
                
                <h3>Order Items</h3>
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
                        ${order.items.map(item => `
                            <tr>
                                <td>${item.name}</td>
                                <td>${item.quantity}</td>
                                <td>$${parseFloat(item.price).toFixed(2)}</td>
                                <td>$${(item.quantity * item.price).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Total Amount:</strong></td>
                            <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                        </tr>
                    </tfoot>
                </table>
            `;
            
            openModal('viewOrderModal');
        }

        // Keep your existing item management functions
        function addOrderItem(item = null) {
            const tbody = document.getElementById('orderItemsBody');
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>
                    <select class="product-select" onchange="updateItemPrice(this)" required>
                        <option value="">Select Product</option>
                        ${products.map(product => `
                            <option value="${product.id}" 
                                    data-price="${product.price}"
                                    ${item && item.productID == product.id ? 'selected' : ''}>
                                ${product.name}
                            </option>
                        `).join('')}
                    </select>
                </td>
                <td>
                    <input type="number" class="quantity-input" 
                           value="${item ? item.quantity : 1}" 
                           min="1" 
                           onchange="updateItemTotal(this)" required>
                </td>
                <td>
                    <input type="number" class="price-input" 
                           value="${item ? item.price : ''}" 
                           step="0.01" 
                           onchange="updateItemTotal(this)" required>
                </td>
                <td class="item-total">$0.00</td>
                <td>
                    <button type="button" class="remove-item-btn" onclick="removeOrderItem(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
            
            if (item) {
                const priceInput = row.querySelector('.price-input');
                priceInput.value = item.price;
                updateItemTotal(priceInput);
            }
        }

        // Keep your existing update functions
        function updateItemPrice(select) {
            const row = select.closest('tr');
            const price = select.options[select.selectedIndex].dataset.price;
            row.querySelector('.price-input').value = price;
            updateItemTotal(select);
        }

        function updateItemTotal(element) {
            const row = element.closest('tr');
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const total = quantity * price;
            
            row.querySelector('.item-total').textContent = `$${total.toFixed(2)}`;
            updateOrderTotal();
        }

        function updateOrderTotal() {
            const totals = Array.from(document.getElementsByClassName('item-total'))
                .map(el => parseFloat(el.textContent.replace('$', '')) || 0);
            
            const orderTotal = totals.reduce((sum, total) => sum + total, 0);
            document.getElementById('orderTotal').textContent = `$${orderTotal.toFixed(2)}`;
        }

        function removeOrderItem(button) {
            const row = button.closest('tr');
            row.remove();
            updateOrderTotal();
        }

        function resetForm() {
            document.getElementById('orderForm').reset();
            document.getElementById('orderItemsBody').innerHTML = '';
            updateOrderTotal();
            currentOrderId = null;
        }

        // Form submission
        if (canManageOrders) {
            document.getElementById('orderForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const items = Array.from(document.getElementById('orderItemsBody').getElementsByTagName('tr'))
                    .map(row => ({
                        productId: row.querySelector('.product-select').value,
                        quantity: parseInt(row.querySelector('.quantity-input').value),
                        price: parseFloat(row.querySelector('.price-input').value)
                    }));

                const totalAmount = items.reduce((sum, item) => sum + (item.quantity * item.price), 0);
                
                const orderData = new URLSearchParams({
                    action: currentOrderId ? 'update' : 'add',
                    date: document.getElementById('orderDate').value,
                    total_amount: totalAmount,
                    items: JSON.stringify(items)
                });

                if (currentOrderId) {
                    orderData.append('orderId', currentOrderId);
                }

                fetch('orders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: orderData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(`Order ${currentOrderId ? 'updated' : 'added'} successfully!`, 'success');
                        closeModal('orderModal');
                        fetchOrders();
                    } else {
                        showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error: ' + error, 'error');
                });
            });
        }

        // Delete order
        function deleteOrder(orderId) {
            if (confirm('Are you sure you want to delete this order?')) {
                fetch('orders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&orderId=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Order deleted successfully!', 'success');
                        fetchOrders();
                    } else {
                        showNotification('Error deleting order: ' + (data.error || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error: ' + error, 'error');
                });
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const filteredOrders = orders.filter(order => 
                order.customerName.toLowerCase().includes(searchTerm) ||
                order.orderID.toString().includes(searchTerm) ||
                order.customerEmail.toLowerCase().includes(searchTerm)
            );
            
            const tableBody = document.getElementById('ordersTableBody');
            tableBody.innerHTML = '';
            filteredOrders.forEach(order => {
                tableBody.appendChild(createOrderRow(order));
            });
        });

        // Notification system
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);

            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.backgroundColor = type === 'success' ? '#4CAF50' : '#f44336';
            notification.style.color = 'white';
            notification.style.padding = '15px 25px';
            notification.style.borderRadius = '5px';
            notification.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            notification.style.zIndex = '1000';
            notification.style.display = 'flex';
            notification.style.alignItems = 'center';
            notification.style.gap = '10px';
            notification.style.animation = 'slideIn 0.5s ease-out';

            // Add animation keyframes
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);

            setTimeout(() => {
                notification.style.animation = 'slideOut 0.5s ease-out';
                notification.style.animationFillMode = 'forwards';
                setTimeout(() => {
                    document.body.removeChild(notification);
                    document.head.removeChild(style);
                }, 500);
            }, 3000);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            Array.from(modals).forEach(modal => {
                if (event.target === modal) {
                    closeModal(modal.id);
                }
            });
        };

        // Initialize orders table on page load
        document.addEventListener('DOMContentLoaded', fetchOrders);
    </script>
</body>
</html>
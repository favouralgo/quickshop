<?php
require_once 'admin/auth_middleware.php';
include '../db/db_connect.php';

// Check if user can at least view products
checkUserRole(['administrator', 'inventory', 'sales', 'customer']);

// Get user's role and permissions
$userRole = getUserRole();
$canManageProducts = canManageProducts();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (!$canManageProducts) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                    exit;
                }
                $name = $_POST['name'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $quantity = $_POST['stock'];

                $stmt = $conn->prepare("INSERT INTO products (name, description, price, quantity) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssii", $name, $description, $price, $quantity);
                $success = $stmt->execute();

                echo json_encode(['success' => $success, 'error' => $conn->error]);
                break;

            case 'update':
                if (!$canManageProducts) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                    exit;
                }
                $id = $_POST['id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $quantity = $_POST['stock'];

                $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ? WHERE productID = ?");
                $stmt->bind_param("ssiii", $name, $description, $price, $quantity, $id);
                $success = $stmt->execute();

                echo json_encode(['success' => $success, 'error' => $conn->error]);
                break;

            case 'delete':
                if (!$canManageProducts) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                    exit;
                }
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM products WHERE productID = ?");
                $stmt->bind_param("i", $id);
                $success = $stmt->execute();

                echo json_encode(['success' => $success, 'error' => $conn->error]);
                break;

            case 'fetch':
                $query = "SELECT * FROM products ORDER BY productID DESC";
                $result = $conn->query($query);
                $products = [];
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $products[] = $row;
                    }
                }
                
                echo json_encode(['success' => true, 'products' => $products]);
                break;
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickShop - Products Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products.css">
</head>
<body>
    <div class="sidebar">
        <div class="logo">QuickShop</div>
        <ul class="nav-links">
            <?php if ($userRole === 'administrator'): ?>
                <li><a href="admin/admindashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i>Users</a></li>
            <?php elseif ($userRole === 'inventory'): ?>
                <li><a href="admin/dashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
            <?php elseif ($userRole === 'sales'): ?>
                <li><a href="admin/salesdashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
            <?php elseif ($userRole === 'customer'): ?>
                <li><a href="admin/customerdashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
            <?php endif; ?>
            
            <?php if (canViewOrders()): ?>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i>Orders</a></li>
            <?php endif; ?>
            
            <li><a href="products.php" class="active"><i class="fas fa-box"></i>Products</a></li>
            <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="actions-bar">
            <div class="search-box">
                <input type="text" placeholder="Search products..." id="searchInput">
                <i class="fas fa-search"></i>
            </div>
            <?php if ($canManageProducts): ?>
            <button class="add-product-btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add Product
            </button>
            <?php endif; ?>
        </div>

        <div class="products-table-container">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <?php if ($canManageProducts): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($canManageProducts): ?>
    <!-- Add/Edit Product Modal -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add New Product</h2>
                <button class="close-modal" onclick="closeModal('productModal')">&times;</button>
            </div>
            <form id="productForm">
                <div class="form-group">
                    <label for="productName">Product Name</label>
                    <input type="text" id="productName" required>
                </div>
                <div class="form-group">
                    <label for="productDescription">Description</label>
                    <textarea id="productDescription" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="productPrice">Price ($)</label>
                    <input type="number" id="productPrice" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="productStock">Stock Quantity</label>
                    <input type="number" id="productStock" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn cancel-btn" onclick="closeModal('productModal')">Cancel</button>
                    <button type="submit" class="modal-btn save-btn">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal delete-modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete Product</h2>
                <button class="close-modal" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <p>Are you sure you want to delete this product? This action cannot be undone.</p>
            <div class="modal-footer">
                <button class="modal-btn cancel-btn" onclick="closeModal('deleteModal')">Cancel</button>
                <button class="modal-btn confirm-delete-btn" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        const canManageProducts = <?php echo json_encode($canManageProducts); ?>;
        let products = [];
        let currentProductId = null;

        // Fetch products from server
        function fetchProducts() {
            fetch('products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=fetch'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    products = data.products;
                    initializeTable();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function createProductRow(product) {
            const row = document.createElement('tr');
            
            let stockStatus = '';
            if (product.quantity === 0) {
                stockStatus = '<span class="stock-status out-of-stock">Out of Stock</span>';
            } else if (product.quantity <= 10) {
                stockStatus = '<span class="stock-status low-stock">Low Stock</span>';
            } else {
                stockStatus = '<span class="stock-status in-stock">In Stock</span>';
            }

            row.innerHTML = `
                <td>#${product.productID}</td>
                <td>${product.name}</td>
                <td>${product.description}</td>
                <td>$${parseFloat(product.price).toFixed(2)}</td>
                <td>${product.quantity}</td>
                <td>${stockStatus}</td>
                ${canManageProducts ? `
                <td>
                    <div class="action-buttons">
                        <button class="action-btn edit-btn" onclick="openEditModal(${product.productID})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="action-btn delete-btn" onclick="openDeleteModal(${product.productID})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </td>
                ` : ''}
            `;
            
            return row;
        }

        function initializeTable() {
            const tableBody = document.getElementById('productsTableBody');
            tableBody.innerHTML = '';
            products.forEach(product => {
                tableBody.appendChild(createProductRow(product));
            });
        }

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            if (modalId === 'productModal') {
                resetForm();
            }
        }

        function openAddModal() {
            currentProductId = null;
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('productForm').reset();
            openModal('productModal');
        }

        function openEditModal(productId) {
            currentProductId = productId;
            const product = products.find(p => p.productID === productId);
            
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('productName').value = product.name;
            document.getElementById('productDescription').value = product.description;
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productStock').value = product.quantity;
            
            openModal('productModal');
        }

        function openDeleteModal(productId) {
            currentProductId = productId;
            openModal('deleteModal');
        }

        function resetForm() {
            document.getElementById('productForm').reset();
            currentProductId = null;
        }

        function confirmDelete() {
            if (currentProductId) {
                fetch('products.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&id=${currentProductId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeModal('deleteModal');
                        showNotification('Product deleted successfully!', 'success');
                        fetchProducts();
                    } else {
                        showNotification('Error deleting product: ' + (data.error || 'Unknown error'), 'error');
                    }
                });
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const tableBody = document.getElementById('productsTableBody');
            tableBody.innerHTML = '';
            
            const filteredProducts = products.filter(product => 
                product.name.toLowerCase().includes(searchTerm) ||
                product.description.toLowerCase().includes(searchTerm)
            );
            
            filteredProducts.forEach(product => {
                tableBody.appendChild(createProductRow(product));
            });
        });

        // Form submission handling
        if (canManageProducts) {
            document.getElementById('productForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const productData = {
                    name: document.getElementById('productName').value,
                    description: document.getElementById('productDescription').value,
                    price: document.getElementById('productPrice').value,
                    stock: document.getElementById('productStock').value,
                    action: currentProductId ? 'update' : 'add'
                };

                if (currentProductId) {
                    productData.id = currentProductId;
                }

                fetch('products.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(productData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(`Product ${currentProductId ? 'updated' : 'added'} successfully!`, 'success');
                        closeModal('productModal');
                        fetchProducts();
                    } else {
                        showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
                    }
                });
            });
        }
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

            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.5s ease-out';
                notification.style.animationFillMode = 'forwards';
                setTimeout(() => {
                    document.body.removeChild(notification);
                    document.head.removeChild(style);
                }, 500);
            }, 3000);
        }

        // Input validation
        function validateNumberInput(input) {
            input.addEventListener('input', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
            });
        }

        validateNumberInput(document.getElementById('productPrice'));
        validateNumberInput(document.getElementById('productStock'));

        // Initialize the table on page load
        document.addEventListener('DOMContentLoaded', fetchProducts);

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            Array.from(modals).forEach(modal => {
                if (event.target === modal) {
                    closeModal(modal.id);
                }
            });
        };
    </script>
</body>
</html>
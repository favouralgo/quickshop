<?php
session_start();

// Check if user is logged in and is an administrator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrator') {
    header("Location: login.php");
    exit();
}

include '../db/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'addUser':
                $name = $_POST['name'];
                $email = $_POST['email'];
                $role = $_POST['role'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Default password: "password123"
                
                // Check if email already exists
                $checkStmt = $conn->prepare("SELECT userID FROM users WHERE email = ?");
                $checkStmt->bind_param("s", $email);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo json_encode(['success' => false, 'error' => 'Email already exists']);
                    exit;
                }
                
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $password, $role);
                $success = $stmt->execute();
                
                echo json_encode(['success' => $success, 'error' => $conn->error]);
                exit;

            case 'updateRole':
                $userId = $_POST['userId'];
                $role = $_POST['role'];
                
                // Check if trying to modify the last administrator
                if ($_POST['currentRole'] === 'administrator' && $role !== 'administrator') {
                    $adminCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'administrator'")->fetch_assoc()['count'];
                    if ($adminCount <= 1) {
                        echo json_encode(['success' => false, 'error' => 'Cannot remove the last administrator']);
                        exit;
                    }
                }
                
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE userID = ?");
                $stmt->bind_param("si", $role, $userId);
                $success = $stmt->execute();
                
                echo json_encode(['success' => $success, 'error' => $conn->error]);
                exit;
                
            case 'deleteUser':
                $userId = $_POST['userId'];
                
                // Prevent deleting the last administrator
                $checkStmt = $conn->prepare("SELECT role FROM users WHERE userID = ?");
                $checkStmt->bind_param("i", $userId);
                $checkStmt->execute();
                $userRole = $checkStmt->get_result()->fetch_assoc()['role'];
                
                if ($userRole === 'administrator') {
                    $adminCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'administrator'")->fetch_assoc()['count'];
                    if ($adminCount <= 1) {
                        echo json_encode(['success' => false, 'error' => 'Cannot delete the last administrator']);
                        exit;
                    }
                }
                
                $stmt = $conn->prepare("DELETE FROM users WHERE userID = ?");
                $stmt->bind_param("i", $userId);
                $success = $stmt->execute();
                
                echo json_encode(['success' => $success, 'error' => $conn->error]);
                exit;
        }
    }
}

// Get all users with additional security
$query = "SELECT userID, name, email, role FROM users ORDER BY userID DESC";
$result = $conn->query($query);
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickShop - Users Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/users.css">
    <style>
        /* Additional styles for notifications and loading states */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease-out;
        }
        
        .notification.success { background-color: #4CAF50; }
        .notification.error { background-color: #f44336; }
        
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        .add-user-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-left: auto;
        }

        .add-user-btn:hover {
            background-color: #45a049;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal .password-group {
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="logo">QuickShop</div>
        <ul class="nav-links">
            <li><a href="admin/admindashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
            <li><a href="users.php" class="active"><i class="fas fa-users"></i>Users</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i>Orders</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i>Products</a></li>
            <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="search-section">
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Search users...">
                <select class="filter-select">
                    <option value="">All Roles</option>
                    <option value="administrator">Administrator</option>
                    <option value="sales">Sales Personnel</option>
                    <option value="inventory">Inventory Manager</option>
                    <option value="customer">Customer</option>
                </select>
                <button class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2 class="section-title">Users Management</h2>
                <button class="add-user-btn" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Add New User
                </button>
            </div>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr data-userid="<?php echo htmlspecialchars($user['userID']); ?>" 
                        data-role="<?php echo htmlspecialchars($user['role']); ?>">
                        <td><?php echo htmlspecialchars($user['userID']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="role-badge role-<?php echo strtolower($user['role']); ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn edit-btn" onclick="openModal('edit', <?php echo $user['userID']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn delete-btn" onclick="deleteUser(<?php echo $user['userID']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New User</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="userForm">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" required>
                        <option value="administrator">Administrator</option>
                        <option value="sales">Sales Personnel</option>
                        <option value="inventory">Inventory Manager</option>
                        <option value="customer">Customer</option>
                    </select>
                </div>
                <div class="form-group password-group" style="display: none;">
                    <label for="password">Password</label>
                    <input type="password" id="password">
                    <small>Leave blank to keep current password</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn cancel-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="modal-btn save-btn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        const modalTitle = document.getElementById('modalTitle');
        const userForm = document.getElementById('userForm');
        const passwordGroup = document.querySelector('.password-group');

        function validateForm() {
            const email = document.getElementById('email').value;
            const name = document.getElementById('name').value;
            
            if (!email.includes('@')) {
                showNotification('Please enter a valid email address', 'error');
                return false;
            }
            
            if (name.length < 2) {
                showNotification('Name must be at least 2 characters long', 'error');
                return false;
            }
            
            return true;
        }

        function setLoading(isLoading) {
            const saveBtn = document.querySelector('.save-btn');
            if (isLoading) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            } else {
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'Save';
            }
        }

        function openModal(type, userId = null) {
            modal.style.display = 'block';
            modalTitle.textContent = type === 'add' ? 'Add New User' : 'Edit User';
            passwordGroup.style.display = type === 'add' ? 'block' : 'none';
            
            if (type === 'add') {
                userForm.reset();
                delete userForm.dataset.userId;
            } else if (type === 'edit' && userId) {
                const row = document.querySelector(`tr[data-userid="${userId}"]`);
                const name = row.querySelector('td:nth-child(2)').textContent;
                const email = row.querySelector('td:nth-child(3)').textContent;
                const role = row.querySelector('.role-badge').textContent;

                document.getElementById('name').value = name;
                document.getElementById('email').value = email;
                document.getElementById('role').value = role.toLowerCase();
                
                userForm.dataset.userId = userId;
                userForm.dataset.currentRole = row.dataset.role;
            }
        }

        function closeModal() {
            modal.style.display = 'none';
            userForm.reset();
            delete userForm.dataset.userId;
        }

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

            setTimeout(() => {
                notification.style.animation = 'slideOut 0.5s ease-out';
                notification.style.animationFillMode = 'forwards';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 500);
            }, 3000);
        }

        userForm.onsubmit = function(e) {
            e.preventDefault();
            if (!validateForm()) return;

            const userId = this.dataset.userId;
            const role = document.getElementById('role').value;
            const currentRole = this.dataset.currentRole;
            
            if (userId) {
                if (currentRole === 'administrator' && role !== 'administrator') {
                    if (!confirm('Warning: You are about to remove administrator privileges. Are you sure?')) {
                        return;
                    }
                }
                updateUserRole(userId, role, currentRole);
            } else {
                addUser();
            }
        };

        function updateUserRole(userId, role, currentRole) {
            setLoading(true);
            fetch('users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=updateRole&userId=${userId}&role=${role}&currentRole=${currentRole}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('User updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                showNotification('Error: ' + error, 'error');
            })
            .finally(() => {
                setLoading(false);
                closeModal();
            });
        }

        function addUser() {
            setLoading(true);
            const formData = new URLSearchParams({
                action: 'addUser',
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                role: document.getElementById('role').value,
                password: document.getElementById('password').value || 'password123'
            });

            fetch('users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('User added successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                showNotification('Error: ' + error, 'error');
            })
            .finally(() => {
                setLoading(false);
                closeModal();
            });
        }

        function deleteUser(userId) {
            const row = document.querySelector(`tr[data-userid="${userId}"]`);
            const role = row.dataset.role;
            
            if (role === 'administrator') {
                if (!confirm('Warning: You are about to delete an administrator. Are you sure?')) {
                    return;
                }
            } else if (!confirm('Are you sure you want to delete this user?')) {
                return;
            }

            fetch('users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=deleteUser&userId=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('User deleted successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                showNotification('Error: ' + error, 'error');
            });
        }

        // Initialize tooltips and search functionality
        const searchInput = document.querySelector('.search-input');
        const filterSelect = document.querySelector('.filter-select');
        const searchBtn = document.querySelector('.search-btn');

        searchBtn.onclick = function() {
            const searchTerm = searchInput.value.toLowerCase();
            const filterRole = filterSelect.value;
            const tableRows = document.querySelectorAll('.users-table tbody tr');

            tableRows.forEach(row => {
                const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const role = row.querySelector('.role-badge').textContent.toLowerCase();

                const matchesSearch = name.includes(searchTerm) || 
                                   email.includes(searchTerm);
                const matchesFilter = filterRole === '' || 
                                    role.includes(filterRole.toLowerCase());

                row.style.display = matchesSearch && matchesFilter ? '' : 'none';
            });
        };

        searchInput.addEventListener('input', () => searchBtn.click());
        filterSelect.addEventListener('change', () => searchBtn.click());

        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        };

        // Initialize tooltips for action buttons
        const actionButtons = document.querySelectorAll('.action-btn');
        actionButtons.forEach(button => {
            button.title = button.textContent.trim();
        });
    </script>
</body>
</html>
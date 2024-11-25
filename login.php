<?php
$pageTitle = 'Login';
require_once 'includes/header.php';
require_once 'includes/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new Database();
    $email = $db->escape($_POST['email']);
    $password = $_POST['password'];
    
    $result = $db->query("SELECT * FROM users WHERE email = '$email'");
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirect based on role
            switch($user['role']) {
                case 'administrator':
                    header('Location: admin/dashboard.php');
                    break;
                case 'sales':
                    header('Location: sales/dashboard.php');
                    break;
                case 'inventory':
                    header('Location: inventory/dashboard.php');
                    break;
                default:
                    header('Location: index.php');
            }
            exit();
        }
    }
    $error = 'Invalid email or password';
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Login</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
                <p class="mt-3">Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
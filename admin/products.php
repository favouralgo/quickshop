<?php
$pageTitle = 'Manage Products';
require_once '../includes/header.php';
require_once '../includes/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrator') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$message = '';

// Handle product deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($db->query("DELETE FROM products WHERE id = $id")) {
        $message = '<div class="alert alert-success">Product deleted successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to delete product.</div>';
    }
}

// Get all products
$products = $db->query("SELECT * FROM products ORDER BY name");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Products</h2>
    <a href="add_product.php" class="btn btn-success">Add New Product</a>
</div>

<?php echo $message; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <img src="../assets/images/<?php echo $product['image']; ?>" 
                                     alt="<?php echo $product['name']; ?>"
                                     style="width: 50px; height: 50px; object-fit: cover;">
                            </td>
                            <td><?php echo $product['name']; ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['quantity']; ?></td>
                            <td>
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-primary btn-sm">Edit</a>
                                <a href="products.php?delete=<?php echo $product['id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$db->close();
require_once '../includes/footer.php'; 
?>
<?php
$pageTitle = 'Add Product';
require_once '../includes/header.php';
require_once '../includes/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrator') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $db->escape($_POST['name']);
    $description = $db->escape($_POST['description']);
    $price = $db->escape($_POST['price']);
    $quantity = $db->escape($_POST['quantity']);
    
    // Handle image upload
    $image = 'default.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = uniqid() . '.' . $filetype;
            if (move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/' . $new_filename)) {
                $image = $new_filename;
            }
        }
    }
    
    $sql = "INSERT INTO products (name, description, price, quantity, image) 
            VALUES ('$name', '$description', '$price', '$quantity', '$image')";
    
    if ($db->query($sql)) {
        $message = '<div class="alert alert-success">Product added successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to add product.</div>';
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Add New Product</h4>
            </div>
            <div class="card-body">
                <?php echo $message; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="image" name="image">
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
$db->close();
require_once '../includes/footer.php'; 
?>
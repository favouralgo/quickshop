<?php
$pageTitle = 'Products';
require_once 'includes/header.php';
require_once 'includes/database.php';

$db = new Database();
$result = $db->query("SELECT * FROM products WHERE quantity > 0 ORDER BY name");
?>

<h2 class="mb-4">Our Products</h2>

<div class="row">
    <?php while ($product = $result->fetch_assoc()): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <img src="assets/images/<?php echo $product['image']; ?>" class="card-img-top" alt="<?php echo $product['name']; ?>">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $product['name']; ?></h5>
                    <p class="card-text"><?php echo substr($product['description'], 0, 100); ?>...</p>
                    <p class="card-text">
                        <strong class="text-primary">$<?php echo number_format($product['price'], 2); ?></strong>
                    </p>
                    <p class="card-text">
                        <small class="text-muted">In stock: <?php echo $product['quantity']; ?></small>
                    </p>
                </div>
                <div class="card-footer bg-white">
                    <form action="cart.php" method="POST" class="d-flex justify-content-between align-items-center">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>" class="form-control w-25">
                        <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php
$db->close();
require_once 'includes/footer.php';
?>
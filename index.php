<?php
$pageTitle = 'Welcome';
require_once 'includes/header.php';
require_once 'includes/database.php';

$db = new Database();
$result = $db->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 6");
?>

<div class="jumbotron bg-light p-5 rounded">
    <h1 class="display-4">Welcome to QuickShop</h1>
    <p class="lead">Find the best products at the best prices.</p>
    <hr class="my-4">
    <p>Start shopping now and discover our amazing deals!</p>
    <a class="btn btn-primary btn-lg" href="products.php" role="button">Shop Now</a>
</div>

<h2 class="my-4">Featured Products</h2>
<div class="row">
    <?php while ($product = $result->fetch_assoc()): ?>
        <div class="col-md-4 mb-4">
            <div class="card">
                <img src="assets/images/<?php echo $product['image']; ?>" class="card-img-top" alt="<?php echo $product['name']; ?>">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $product['name']; ?></h5>
                    <p class="card-text"><?php echo substr($product['description'], 0, 100); ?>...</p>
                    <p class="card-text"><strong>$<?php echo number_format($product['price'], 2); ?></strong></p>
                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php
$db->close();
require_once 'includes/footer.php';
?>
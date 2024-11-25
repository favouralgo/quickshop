<?php
$pageTitle = 'Shopping Cart';
require_once 'includes/header.php';
require_once 'includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
}

// Update cart
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $product_id => $quantity) {
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
}

// Remove from cart
if (isset($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$product_id]);
}

$total = 0;
?>

<h2 class="mb-4">Shopping Cart</h2>

<?php if (empty($_SESSION['cart'])): ?>
    <div class="alert alert-info">Your cart is empty.</div>
    <a href="products.php" class="btn btn-primary">Continue Shopping</a>
<?php else: ?>
    <form method="POST" action="">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $product_id => $quantity):
                        $result = $db->query("SELECT * FROM products WHERE id = $product_id");
                        $product = $result->fetch_assoc();
                        $subtotal = $product['price'] * $quantity;
                        $total += $subtotal;
                    ?>
                        <tr>
                            <td><?php echo $product['name']; ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td>
                                <input type="number" name="quantities[<?php echo $product_id; ?>]" 
                                       value="<?php echo $quantity; ?>" min="0" max="<?php echo $product['quantity']; ?>" 
                                       class="form-control" style="width: 100px">
                            </td>
                            <td>$<?php echo number_format($subtotal, 2); ?></td>
                            <td>
                                <a href="cart.php?remove=<?php echo $product_id; ?>" 
                                   class="btn btn-danger btn-sm">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="d-flex justify-content-between">
            <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
            <div>
                <button type="submit" name="update_cart" class="btn btn-primary">Update Cart</button>
                <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
            </div>
        </div>
    </form>
<?php endif; ?>

<?php
$db->close();
require_once 'includes/footer.php';
?>
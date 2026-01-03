<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
$user_id = $_SESSION['user_id'];

/* ================= ADD TO CART ================= */
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity   = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    $stmt = $conn->prepare(
        "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?"
    );
    $stmt->execute([$user_id, $product_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $stmt = $conn->prepare(
            "UPDATE cart SET quantity = quantity + ? 
             WHERE user_id = ? AND product_id = ?"
        );
        $stmt->execute([$quantity, $user_id, $product_id]);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO cart (user_id, product_id, quantity)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$user_id, $product_id, $quantity]);
    }

    header("Location: cart.php");
    exit();
}

/* ================= UPDATE QUANTITY ================= */
if (isset($_POST['update_quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity   = (int)$_POST['quantity'];

    if ($quantity > 0) {
        $stmt = $conn->prepare(
            "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?"
        );
        $stmt->execute([$quantity, $user_id, $product_id]);
    }

    header("Location: cart.php");
    exit();
}

/* ================= REMOVE ITEM ================= */
if (isset($_POST['remove_from_cart'])) {
    $product_id = (int)$_POST['product_id'];

    $stmt = $conn->prepare(
        "DELETE FROM cart WHERE user_id = ? AND product_id = ?"
    );
    $stmt->execute([$user_id, $product_id]);

    header("Location: cart.php");
    exit();
}

/* ================= FETCH CART ================= */
$stmt = $conn->prepare("
    SELECT c.product_id, c.quantity, p.name, p.price, p.image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_cost = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Cart</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f8f9fa;
}
.container {
    max-width: 900px;
    margin: 40px auto;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
}
.cart-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px;
    margin-bottom: 15px;
    border-bottom: 1px solid #ddd;
}
.cart-item img {
    width: 100px;
    border-radius: 5px;
}
.item-details {
    flex: 1;
    margin-left: 20px;
}
.item-actions form {
    display: inline-block;
}
.quantity {
    width: 60px;
}
button {
    padding: 6px 10px;
    cursor: pointer;
}
.total-cost {
    text-align: center;
    font-size: 22px;
    margin-top: 20px;
}
.cart-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}
.cart-actions a {
    text-decoration: none;
    background: green;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
}
</style>
</head>

<body>

<div class="container">
<h2>Your Cart</h2>

<?php if (empty($cart_items)) : ?>
    <p>Your cart is empty.</p>
<?php else : ?>

<?php foreach ($cart_items as $item) :
    $subtotal = $item['price'] * $item['quantity'];
    $total_cost += $subtotal;
?>
<div class="cart-item">
    <img src="../images/<?= htmlspecialchars($item['image']) ?>">
    
    <div class="item-details">
        <b><?= htmlspecialchars($item['name']) ?></b><br>
        $<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?>
    </div>

    <div class="item-actions">
        <form method="POST">
            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" class="quantity">
            <button type="submit" name="update_quantity">Update</button>
        </form>

        <form method="POST">
            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
            <button type="submit" name="remove_from_cart">Remove</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<div class="total-cost">
    Total: $<?= number_format($total_cost, 2) ?>
</div>

<div class="cart-actions">
    <a href="../index.php">Back to Shop</a>
    <a href="checkout.php">Proceed to Checkout</a>
</div>

<?php endif; ?>

</div>
</body>
</html>

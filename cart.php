<?php
session_start();
include 'config.php'; // Database connection

// Check if user is logged in as buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle adding to cart (via GET from products.php)
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $product_id = intval($_GET['add']);
    // Check if product exists and has stock
    $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        if ($product['quantity'] > 0) {
            // Add to cart (increment quantity if already in cart)
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]++;
            } else {
                $_SESSION['cart'][$product_id] = 1;
            }
        } else {
            $errors[] = "Product out of stock.";
        }
    } else {
        $errors[] = "Product not found.";
    }
    $stmt->close();
    header("Location: cart.php"); // Redirect to avoid re-adding on refresh
    exit();
}

// Handle updating/removing from cart (via POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update'])) {
        foreach ($_POST['quantity'] as $product_id => $qty) {
            $qty = intval($qty);
            if ($qty > 0) {
                $_SESSION['cart'][$product_id] = $qty;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }
    } elseif (isset($_POST['remove']) && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        unset($_SESSION['cart'][$product_id]);
    } elseif (isset($_POST['checkout'])) {
        // Process checkout
        if (empty($_SESSION['cart'])) {
            $errors[] = "Cart is empty.";
        } else {
            $total = 0;
            $order_items = [];
            foreach ($_SESSION['cart'] as $product_id => $qty) {
                $stmt = $conn->prepare("SELECT price, quantity FROM products WHERE id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    if ($product['quantity'] >= $qty) {
                        $subtotal = $product['price'] * $qty;
                        $total += $subtotal;
                        $order_items[] = ['id' => $product_id, 'qty' => $qty, 'subtotal' => $subtotal];
                        // Reduce stock
                        $new_qty = $product['quantity'] - $qty;
                        $stmt2 = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                        $stmt2->bind_param("ii", $new_qty, $product_id);
                        $stmt2->execute();
                        $stmt2->close();
                    } else {
                        $errors[] = "Insufficient stock for product ID $product_id.";
                    }
                } else {
                    $errors[] = "Product ID $product_id not found.";
                }
                $stmt->close();
            }
            if (empty($errors)) {
                // Insert orders
                foreach ($order_items as $item) {
                    $stmt = $conn->prepare("INSERT INTO orders (buyer_id, product_id, quantity, total) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiid", $user_id, $item['id'], $item['qty'], $item['subtotal']);
                    $stmt->execute();
                    $stmt->close();
                }
                $_SESSION['cart'] = []; // Clear cart
                $success = "Order placed successfully! Total: $" . number_format($total, 2);
                // TODO: Integrate payment (e.g., redirect to Stripe)
            }
        }
    }
}

// Fetch cart details for display
$cart_items = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    $placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
    $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($_SESSION['cart'])), ...array_keys($_SESSION['cart']));
    $stmt->execute();
    $result = $stmt->get_result();
    while ($product = $result->fetch_assoc()) {
        $product_id = $product['id'];
        $qty = $_SESSION['cart'][$product_id];
        $subtotal = $product['price'] * $qty;
        $total += $subtotal;
        $cart_items[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $qty,
            'subtotal' => $subtotal
        ];
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cart - Farmer Market</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Your Cart</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! <a href="logout.php">Logout</a> | <a href="index.php">Back to Home</a></p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <p>Your cart is empty. <a href="index.php">Browse products</a>.</p>
        <?php else: ?>
            <form method="POST" action="cart.php">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td><?php if ($item['image']): ?><img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Product" style="width: 50px; height: 50px; object-fit: cover;"><?php endif; ?></td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td><input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="form-control" style="width: 80px;"></td>
                                <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                                <td>
                                    <button type="submit" name="remove" value="1" class="btn btn-danger btn-sm" onclick="return confirm('Remove this item?');">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">Remove
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">total</th>
                            <th>$<?php echo number_format($total, 2); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
                <button type="submit" name="update" class="btn btn-primary">Update Cart</button>
                <button type="submit" name="checkout" class="btn btn-success ml-2" onclick="return confirm('Proceed to checkout?');">Checkout</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = false;
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$upload_dir = 'uploads/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

/* ===========================
   DELETE PRODUCT (Farmer) - NEW
=========================== */
if ($user_role == 'farmer' && isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    
    // Ensure the farmer can only delete their own product
    $stmt_delete = $conn->prepare("DELETE FROM products WHERE id = ? AND farmer_id = ?");
    $stmt_delete->bind_param("ii", $product_id, $user_id);
    $stmt_delete->execute();
    $stmt_delete->close();
}

/* ===========================
   UPDATE QUANTITY (Farmer)
=========================== */
if ($user_role == 'farmer' && isset($_POST['update_quantity'])) {
    $product_id = intval($_POST['product_id']);
    $new_quantity = floatval($_POST['new_quantity']);

    if ($new_quantity >= 0) {
        $stmt_update = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ? AND farmer_id = ?");
        $stmt_update->bind_param("dii", $new_quantity, $product_id, $user_id);
        $stmt_update->execute();
        $stmt_update->close();
    }
}

/* ===========================
   GET USER TASIL
=========================== */
$stmt_user = $conn->prepare("SELECT tasil FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();
$user_tasil = $user_data['tasil'] ?? '';
$stmt_user->close();

/* ===========================
   ADD PRODUCT (Farmer)
=========================== */
if ($user_role == 'farmer' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $quantity = floatval($_POST['quantity']);
    $image_path = null;

    if (empty($name)) $errors[] = "Product name is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if ($price <= 0) $errors[] = "Price must be greater than 0.";
    if ($quantity < 0) $errors[] = "Quantity cannot be negative.";

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['image/jpeg','image/png','image/gif'];
        $max_size = 5 * 1024 * 1024;

        if (in_array($_FILES['image']['type'], $allowed) && $_FILES['image']['size'] <= $max_size) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_name = uniqid('product_') . "." . $ext;
            $image_path = $upload_dir . $new_name;
            move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO products (farmer_id, name, description, price, quantity, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdis", $user_id, $name, $description, $price, $quantity, $image_path);
        $stmt->execute();
        $stmt->close();
        $success = true;
    }
}

/* ===========================
   FETCH PRODUCTS
=========================== */
if ($user_role == 'farmer') {
    $stmt = $conn->prepare("SELECT * FROM products WHERE farmer_id = ?");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare("
        SELECT p.*, u.name AS farmer_name 
        FROM products p 
        JOIN users u ON p.farmer_id = u.id 
        WHERE u.tasil = ?
    ");
    $stmt->bind_param("s", $user_tasil);
}

$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Products - freshvegies</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<nav class="navbar navbar-light bg-light shadow-sm">
    <span class="navbar-brand mb-0 h4">freshvegies</span>
    <div class="ml-auto">
        <span class="mr-3">Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
        <a href="login.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-4">

<?php if ($user_role == 'farmer'): ?>
    <h3>Add New Product</h3>
    <?php if ($success): ?>
        <div class="alert alert-success">Product added successfully!</div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group"><label>Product Name</label><input type="text" class="form-control" name="name" required></div>
        <div class="form-group"><label>Description</label><textarea class="form-control" name="description" required></textarea></div>
        <div class="form-group"><label>Price (RS)</label><input type="number" step="0.01" class="form-control" name="price" required></div>
        <div class="form-group"><label>Quantity (KG)</label><input type="number" step="0.1" class="form-control" name="quantity" required></div>
        <div class="form-group"><label>Product Image</label><input type="file" class="form-control-file" name="image"></div>
        <button type="submit" class="btn btn-primary">Add Product</button>
    </form>
<?php endif; ?>

<h3 class="mt-5">Available Products</h3>

<?php if (empty($products)): ?>
    <p>No products available.</p>
<?php else: ?>
    <div class="row">
        <?php foreach ($products as $product): ?>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" style="height:200px;object-fit:cover;">
                    <?php endif; ?>

                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <p><strong>Price:</strong> RS <?php echo number_format($product['price'],2); ?></p>
                        <p><strong>Quantity:</strong> <?php echo $product['quantity']; ?> KG</p>

                        <?php if ($user_role == 'farmer'): ?>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <div class="input-group">
                                    <input type="number" step="0.1" name="new_quantity" class="form-control" required placeholder="New Qty">
                                    <div class="input-group-append">
                                        <button class="btn btn-warning" name="update_quantity">Update</button>
                                    </div>
                                </div>
                            </form>

                            <form method="POST" class="mt-2" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="delete_product" class="btn btn-outline-danger btn-block btn-sm">
                                    Delete Product
                                </button>
                            </form>
                            
                        <?php else: ?>
                            <p><strong>Farmer:</strong> <?php echo htmlspecialchars($product['farmer_name']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div>
</body>
</html>
```
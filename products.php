
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


if ($user_role == 'farmer' && isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    
    // Ensure the farmer can only delete their own product
    $stmt_delete = $conn->prepare("DELETE FROM products WHERE id = ? AND farmer_id = ?");
    $stmt_delete->bind_param("ii", $product_id, $user_id);
    $stmt_delete->execute();
    $stmt_delete->close();
}

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


$stmt_user = $conn->prepare("SELECT tasil FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();
$user_tasil = $user_data['tasil'] ?? '';
$stmt_user->close();

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
    <style>
        body {
            background: linear-gradient(135deg, #4CAF50, #8BC34A);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(90deg, #2E7D32, #43A047) !important;
            padding: 12px 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 24px;
            color: #ffffff !important;
            letter-spacing: 1px;
        }
        .navbar span {
            color: #e8f5e9 !important;
            font-weight: 500;
        }
        .navbar .btn {
            border-color: #ffffff;
            color: #ffffff;
        }
        .navbar .btn:hover {
            background-color: #ffffff;
            color: #2e7d32;
        }
        .container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 12px;
            margin-top: 30px;
        }
        h3 {
            font-weight: 600;
            margin-bottom: 20px;
            color: #2e7d32;
        }
        .card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background-color: #ffffff;
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .card img {
            border-bottom: 1px solid #eee;
        }
        .card-body {
            padding: 15px;
        }
        .card-body h5 {
            font-weight: 600;
            color: #2e7d32;
        }
        .card-body p {
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
        }
        .card-body strong {
            color: #000;
        }
        .btn-primary {
            background-color: #4CAF50;
            border: none;
            border-radius: 8px;
            transition: 0.3s;
        }
        .btn-primary:hover {
            background-color: #2E7D32;
        }
        .btn-warning {
            background-color: #FF9800;
            border: none;
        }
        .btn-warning:hover {
            background-color: #F57C00;
        }
        .btn-outline-danger {
            border-color: #F44336;
            color: #F44336;
        }
        .btn-outline-danger:hover {
            background-color: #F44336;
            color: #ffffff;
        }
        .page-logo {
            height: 80px;
            width: auto;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-light shadow-sm">
    <a class="navbar-brand d-flex align-items-center" href="products.php">
        <img src="uploads/freshvegies logo.jpg" alt="Freshvegies logo" class="page-logo mr-2">
        freshvegies
    </a>
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
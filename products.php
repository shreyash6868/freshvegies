<?php
session_start();
include 'config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = false;
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$upload_dir = 'uploads/'; // Folder for images; create this in your project root

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle farmer adding products
if ($user_role == 'farmer' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $image_path = null;

    // Validation
    if (empty($name)) $errors[] = "Product name is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if ($price <= 0) $errors[] = "Price must be greater than 0.";
    if ($quantity < 0) $errors[] = "Quantity cannot be negative.";

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $file_type = $_FILES['image']['type'];
        $file_size = $_FILES['image']['size'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid('product_', true) . '.' . $file_ext; // Unique filename

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG, or GIF images are allowed.";
        } elseif ($file_size > $max_size) {
            $errors[] = "Image size must be less than 5MB.";
        } else {
            $image_path = $upload_dir . $new_filename;
            if (!move_uploaded_file($file_tmp, $image_path)) {
                $errors[] = "Failed to upload image.";
            }
        }
    } // Image is optional; no error if not uploaded

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO products (farmer_id, name, description, price, quantity, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdis", $user_id, $name, $description, $price, $quantity, $image_path);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Failed to add product. Try again.";
            // Delete uploaded file if DB insert fails
            if ($image_path && file_exists($image_path)) unlink($image_path);
        }
        $stmt->close();
    }
}

// Fetch products: Farmers see their own; Buyers see all
if ($user_role == 'farmer') {
    $stmt = $conn->prepare("SELECT * FROM products WHERE farmer_id = ?");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare("SELECT p.*, u.name AS farmer_name FROM products p JOIN users u ON p.farmer_id = u.id");
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products - Farmer Market</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Products</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! <a href="logout.php">Logout</a></p>

        <?php if ($user_role == 'farmer'): ?>
            <h3>Add New Product</h3>
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
                <div class="alert alert-success">Product added successfully!</div>
            <?php endif; ?>
            <form method="POST" action="products.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Product Name:</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea class="form-control" id="description" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price ($):</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" required>
                </div>
                <div class="form-group">
                    <label for="image">Product Image (optional):</label>
                    <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                </div>
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
                        <div class="card">
                            <?php if ($product['image']): ?>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="Product Image" style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                                <p><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
                                <p><strong>Quantity:</strong> <?php echo $product['quantity']; ?></p>
                                <?php if ($user_role == 'buyer'): ?>
                                    <p><strong>Farmer:</strong> <?php echo htmlspecialchars($product['farmer_name']); ?></p>
                                    <a href="cart.php?add=<?php echo $product['id']; ?>" class="btn btn-success">Add to Cart</a>
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
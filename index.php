<?php
session_start();
include 'config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'];
$user_name = $_SESSION['user_name'];

// Fetch products for display (all for buyers; farmer's own for farmers/admins if needed, but show all for simplicity)
$stmt = $conn->prepare("SELECT p.*, u.name AS farmer_name FROM products p JOIN users u ON p.farmer_id = u.id");
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
    <title>Home - Farmer Market</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="index.php">Farmer Market</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item active">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <?php if ($user_role == 'buyer'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">Cart</a>
                    </li>
                <?php elseif ($user_role == 'farmer'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">My Products</a>
                    </li>
                <?php elseif ($user_role == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">Admin Dashboard</a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="navbar-text">Welcome, <?php echo htmlspecialchars($user_name); ?>!</span>
                </li>
                <li class="nav-item">
               <b> <a class="nav-link" href="login.php">Logout</a> </b>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <h1>Welcome to Farmer Market</h1>
        <p>Buy fresh vegetables directly from local farmers!</p>

        <?php if ($user_role == 'buyer'): ?>
            <h2>Available Products</h2>
            <?php if (empty($products)): ?>
                <p>No products available at the moment.</p>
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
                                    <p><strong>Farmer:</strong> <?php echo htmlspecialchars($product['farmer_name']); ?></p>
                                    <a href="cart.php?add=<?php echo $product['id']; ?>" class="btn btn-success">Add to Cart</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php elseif ($user_role == 'farmer'): ?>
            <p>As a farmer, you can <a href="products.php">add and manage your products</a>.</p>
        <?php elseif ($user_role == 'admin'): ?>
            <p>As an admin, you can <a href="admin.php">manage users and oversee the platform</a>.</p>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
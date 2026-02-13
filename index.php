<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['user_name'];

/* ===============================
   GET USER TASIL
================================ */
$stmt_user = $conn->prepare("SELECT tasil FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();
$user_tasil = $user_data['tasil'] ?? '';
$stmt_user->close();

/* ===============================
   FETCH PRODUCTS
================================ */
if ($user_role == 'buyer') {

    $stmt = $conn->prepare("
        SELECT p.*, 
               u.name AS farmer_name, 
               u.phone AS farmer_phone, 
               u.location AS farmer_location
        FROM products p 
        JOIN users u ON p.farmer_id = u.id
        WHERE u.tasil = ?
    ");
    $stmt->bind_param("s", $user_tasil);

} elseif ($user_role == 'admin') {

    $stmt = $conn->prepare("
        SELECT p.*, 
               u.name AS farmer_name, 
               u.phone AS farmer_phone, 
               u.location AS farmer_location
        FROM products p 
        JOIN users u ON p.farmer_id = u.id
    ");

} else {

    $stmt = $conn->prepare("
        SELECT p.*, 
               u.name AS farmer_name, 
               u.phone AS farmer_phone, 
               u.location AS farmer_location
        FROM products p 
        JOIN users u ON p.farmer_id = u.id
        WHERE 1=0
    ");
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
    <title>Home - freshvegies</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<nav class="navbar navbar-light bg-light">
    <span class="navbar-brand">freshvegies</span>
    <div>
        <span class="mr-3">Welcome, <?php echo htmlspecialchars($user_name); ?>!</span>
        <a href="login.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-5">
    <h2>Available Products</h2>

    <?php if (empty($products)): ?>
        <p>No products available at the moment.</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                 class="card-img-top" 
                                 style="height:200px;object-fit:cover;">
                        <?php endif; ?>

                        <div class="card-body">
                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p><?php echo htmlspecialchars($product['description']); ?></p>
                            <p><strong>Price:</strong> RS <?php echo number_format($product['price'],2); ?></p>
                            <p><strong>Quantity:</strong> <?php echo $product['quantity']; ?></p>
                            <p><strong>Farmer:</strong> <?php echo htmlspecialchars($product['farmer_name']); ?></p>

                            <button 
                                class="btn btn-primary mt-2 contact-btn"
                                data-name="<?php echo htmlspecialchars($product['farmer_name']); ?>"
                                data-phone="<?php echo htmlspecialchars($product['farmer_phone']); ?>"
                                data-location="<?php echo htmlspecialchars($product['farmer_location']); ?>"
                                data-toggle="modal"
                                data-target="#contactModal">
                                Contact Farmer
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Farmer Contact Details</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p><strong>Name:</strong> <span id="modalName"></span></p>
        <p>
            <strong>Phone:</strong> 
            <span id="modalPhone"></span>
            <button class="btn btn-sm btn-outline-secondary ml-2" onclick="copyPhone()">Copy</button>
        </p>
        <p><strong>Location:</strong> <span id="modalLocation"></span></p>
        <small id="copyMessage" class="text-success" style="display:none;">Copied!</small>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let currentPhone = "";

document.querySelectorAll('.contact-btn').forEach(button => {
    button.addEventListener('click', function() {
        document.getElementById('modalName').textContent = this.dataset.name;
        document.getElementById('modalPhone').textContent = this.dataset.phone;
        document.getElementById('modalLocation').textContent = this.dataset.location;
        currentPhone = this.dataset.phone;
        document.getElementById('copyMessage').style.display = "none";
    });
});

function copyPhone() {
    navigator.clipboard.writeText(currentPhone).then(function() {
        document.getElementById('copyMessage').style.display = "inline";
    });
}
</script>

</body>
</html>

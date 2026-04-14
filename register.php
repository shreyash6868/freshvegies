<?php
session_start();
include 'config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $location = trim($_POST['location']);
    $tasil = trim($_POST['tasil']);   // ✅ NEW

    // Validation
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($phone) || !preg_match('/^\+?[0-9]{10,15}$/', $phone)) 
        $errors[] = "Valid phone number is required (e.g., +1234567890).";
    if (empty($password) || strlen($password) < 6) 
        $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm_password) 
        $errors[] = "Passwords do not match.";
    if (!in_array($role, ['buyer', 'farmer'])) 
        $errors[] = "Invalid role selected.";
    if (empty($location)) 
        $errors[] = "Location is required.";
    if (empty($tasil)) 
        $errors[] = "Tasil is required.";   // ✅ NEW VALIDATION

    if (empty($errors)) {

        // Check if phone already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Phone number already registered.";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // ✅ INSERT WITH TASIL
            $stmt = $conn->prepare("INSERT INTO users (name, phone, password, role, location, tasil) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $phone, $hashed_password, $role, $location, $tasil);

            if ($stmt->execute()) {
                $success = true;
                header("Location: login.php?registered=1");
                exit();
            } else {
                $errors[] = "Registration failed. Try again.";
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>

   <link rel="stylesheet" href="register.css">

    <title>Register - Farmer Market</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="register-wrapper">
    <div class="register-card">

        <h2>Create Account</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Name</label>
                <input type="text" class="form-control" name="name" required>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" class="form-control" name="confirm_password" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select class="form-control" name="role">
                    <option value="buyer">Buyer</option>
                    <option value="farmer">Farmer</option>
                </select>
            </div>

            <div class="form-group">
                <label>Location</label>
                <input type="text" class="form-control" name="location" required>
            </div>

            <div class="form-group">
                <label>Tasil</label>
                <input type="text" class="form-control" name="tasil" required>
            </div>

            <button type="submit" class="btn btn-primary">Register</button>

        </form>

        <div class="register-footer">
            Already have an account? <a href="login.php">Login</a>
        </div>

    </div>
</div>

</body>
</html>

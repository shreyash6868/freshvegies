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

    // Validation
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($phone) || !preg_match('/^\+?[0-9]{10,15}$/', $phone)) $errors[] = "Valid phone number is required (e.g., +1234567890).";
    if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
    if (!in_array($role, ['buyer', 'farmer'])) $errors[] = "Invalid role selected.";
    if (empty($location)) $errors[] = "Location is required.";

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
            $stmt = $conn->prepare("INSERT INTO users (name, phone, password, role, location) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $phone, $hashed_password, $role, $location);
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Farmer Market</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Register</h2>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">Registration successful! <a href="login.php">Login here</a></div>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="+1234567890" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="buyer" <?php echo (($_POST['role'] ?? '') == 'buyer') ? 'selected' : ''; ?>>Buyer</option>
                    <option value="farmer" <?php echo (($_POST['role'] ?? '') == 'farmer') ? 'selected' : ''; ?>>Farmer</option>
                </select>
            </div>
            <div class="form-group">
                <label for="location">Location:</label>
                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" placeholder="e.g., City, State" required>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
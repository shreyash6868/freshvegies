<?php 
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') { 
    $phone = mysqli_real_escape_string($conn, $_POST['phone']); 
    $password = $_POST['password']; 

    $sql = "SELECT id, name, password, role FROM users WHERE phone = '$phone'"; 
    $result = $conn->query($sql); 

    if ($result->num_rows == 1) { 
        $user = $result->fetch_assoc(); 

        if (password_verify($password, $user['password'])) { 
            $_SESSION['user_id'] = $user['id']; 
            $_SESSION['user_name'] = $user['name']; 
            $_SESSION['role'] = $user['role']; 

            if ($user['role'] == 'farmer') { 
                header("Location: products.php"); 
            } elseif ($user['role'] == 'buyer') { 
                header("Location: index.php"); 
            } elseif ($user['role'] == 'admin') { 
                header("Location: admin.php"); 
            }
            exit();
        } else { 
            $error = "Invalid password."; 
        }
    } else { 
        $error = "Phone number not found."; 
    } 
}
$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Farmer Market</title>

<!-- Bootstrap FIRST -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

<!-- Your CSS AFTER Bootstrap -->
<link rel="stylesheet" href="login.css">

<style>
/* Force green farming theme if CSS not loading properly */
body {
    margin: 0;
    min-height: 100vh;
    background: linear-gradient(to bottom, #2e7d32, #a5d6a7) !important;
}

.btn-primary {
    background-color: #388e3c !important;
    border: none !important;
}

.btn-primary:hover {
    background-color: #2e7d32 !important;
}

.login-card {
    background: #f1f8e9 !important;
}
</style>

</head>

<body>

<div class="login-wrapper">
    <div class="login-card">
        <img src="uploads/freshvegies logo.jpg" alt="Freshvegies logo" class="page-logo">
        <h2>Login</h2> 

        <?php 
        if (isset($error)) {
            echo "<div class='alert alert-danger'>$error</div>"; 
        }
        ?>

        <form method="POST" action="login.php"> 
            <div class="form-group">
                <label for="phone">Phone Number:</label> 
                <input type="text" class="form-control" id="phone" name="phone" required> 
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

        <p class="register-text">
            Don't have an account? <a href="register.php">Register here</a>
        </p>

    </div> 
</div>

</body>
</html>
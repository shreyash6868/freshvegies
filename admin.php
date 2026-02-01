<?php
session_start();
include 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        // Add user logic
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $location = trim($_POST['location']);

        // Validation
        if (empty($name)) $errors[] = "Name is required.";
        if (empty($phone) || !preg_match('/^\+?[0-9]{10,15}$/', $phone)) $errors[] = "Valid phone number is required.";
        if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
        if (!in_array($role, ['buyer', 'farmer', 'admin'])) $errors[] = "Invalid role selected.";
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
                    $success = "User added successfully.";
                } else {
                    $errors[] = "Failed to add user. Try again.";
                }
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_user'])) {
        // Delete user logic
        $user_id_to_delete = intval($_POST['user_id']);
        // Prevent deleting self
        if ($user_id_to_delete == $_SESSION['user_id']) {
            $errors[] = "You cannot delete your own account.";
        } else {
            // Delete user (ensure foreign keys handle related data)
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id_to_delete);
            if ($stmt->execute()) {
                $success = "User deleted successfully.";
            } else {
                $errors[] = "Fail to delete user.";
            }
            $stmt->close();
        }
    }
}

// Fetch and display existing users ordered by role, with optional search
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $sql = "SELECT id, name, phone, role, location FROM users WHERE phone LIKE ? OR location LIKE ? ORDER BY role";
    $stmt = $conn->prepare($sql);
    $search_param = '%' . $search . '%';
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $sql = "SELECT id, name, phone, role, location FROM users ORDER BY role";
    $result = $conn->query($sql);
    $users = $result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Farmer Market</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Admin Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! <a href="logout.php">Logout</a></p>

        <h3>Add New User</h3>
        <button type="button" class="btn btn-primary mb-3" onclick="toggleAddUserForm()">Add User</button>

        <div id="addUserForm" style="display: none;">
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
            <form method="POST" action="admin.php">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="+1234567890" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" class="form-control" id="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="buyer">Buyer</option>
                        <option value="farmer">Farmer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" class="form-control" id="location" name="location" placeholder="e.g., City, State" required>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
            </form>
        </div>

        <h3 class="mt-5">Search Users</h3>
        <form method="GET" action="admin.php" class="form-inline mb-3">
            <input type="text" name="search" class="form-control mr-2" placeholder="Search by phone or location" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="admin.php" class="btn btn-link">Clear</a>
        </form>

        <h3>Existing Users Grouped by Role</h3>
        <div class="mb-3">
            <button type="button" class="btn btn-outline-primary" onclick="showRole('farmer')">Farmers</button>
            <button type="button" class="btn btn-outline-primary" onclick="showRole('buyer')">Buyers</button>
            <button type="button" class="btn btn-outline-primary" onclick="showRole('admin')">Admins</button>
            <button type="button" class="btn btn-outline-secondary" onclick="showAllRoles()">Show All</button>
        </div>

        <?php
        // Group users by role
        $grouped_users = [];
        foreach ($users as $user) {
            $grouped_users[$user['role']][] = $user;
        }

        foreach ($grouped_users as $role => $users_in_role): ?>
            <div id="<?php echo $role; ?>-section" style="display: block;">
                <h4><?php echo ucfirst(htmlspecialchars($role)) . 's'; ?></h4>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users_in_role as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo htmlspecialchars($user['location']); ?></td>
                                <td>
                                    <form method="POST" action="admin.php" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    function toggleAddUserForm() {
        var form = document.getElementById('addUserForm');
        form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
    }

    function showRole(role) {
        // Hide all sections
        document.getElementById('farmer-section').style.display = 'none';
        document.getElementById('buyer-section').style.display = 'none';
        document.getElementById('admin-section').style.display = 'none';
        // Show selected role
        document.getElementById(role + '-section').style.display = 'block';
    }

    function showAllRoles() {
        // Show all sections
        document.getElementById('farmer-section').style.display = 'block';
        document.getElementById('buyer-section').style.display = 'block';
        document.getElementById('admin-section').style.display = 'block';
    }
    </script>
</body>
</html>
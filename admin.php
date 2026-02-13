<?php
session_start();
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    /* =========================
       ADD USER
    ========================== */
    if (isset($_POST['add_user'])) {

        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $location = trim($_POST['location']);
        $tasil = trim($_POST['tasil']);

        if (empty($name)) $errors[] = "Name is required.";
        if (empty($phone) || !preg_match('/^\+?[0-9]{10,15}$/', $phone))
            $errors[] = "Valid phone number is required.";
        if (empty($password) || strlen($password) < 6)
            $errors[] = "Password must be at least 6 characters.";
        if (!in_array($role, ['buyer', 'farmer', 'admin']))
            $errors[] = "Invalid role selected.";
        if (empty($location)) $errors[] = "Location is required.";
        if (empty($tasil)) $errors[] = "Tasil is required.";

        if (empty($errors)) {

            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = "Phone number already registered.";
            } else {

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users (name, phone, password, role, location, tasil) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $name, $phone, $hashed_password, $role, $location, $tasil);

                if ($stmt->execute()) {
                    $success = "User added successfully.";
                } else {
                    $errors[] = "Failed to add user.";
                }
            }
            $stmt->close();
        }
    }

    /* =========================
       DELETE USER
    ========================== */
    if (isset($_POST['delete_user'])) {

        $user_id_to_delete = intval($_POST['user_id']);

        if ($user_id_to_delete == $_SESSION['user_id']) {
            $errors[] = "You cannot delete your own account.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id_to_delete);

            if ($stmt->execute()) {
                $success = "User deleted successfully.";
            } else {
                $errors[] = "Failed to delete user.";
            }
            $stmt->close();
        }
    }
}

/* =========================
   FETCH USERS
========================= */
$search = $_GET['search'] ?? '';

if (!empty($search)) {

    $sql = "SELECT id, name, phone, role, location, tasil FROM users 
            WHERE phone LIKE ? OR location LIKE ? OR tasil LIKE ?
            ORDER BY role";

    $stmt = $conn->prepare($sql);
    $search_param = '%' . $search . '%';
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} else {

    $result = $conn->query("SELECT id, name, phone, role, location, tasil FROM users ORDER BY role");
    $users = $result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Farmer Market</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
    <h2>Admin Dashboard</h2>
    <p>
        Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> |
        <a href="login.php">Logout</a>
    </p>

    <!-- ADD USER BUTTON -->
    <h3>Add New User</h3>
    <button class="btn btn-primary mb-3" onclick="toggleForm()">
        Add User
    </button>

    <!-- ADD USER FORM (HIDDEN) -->
    <div id="addUserForm" style="display:none;">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
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
                <label>Role</label>
                <select class="form-control" name="role" required>
                    <option value="buyer">Buyer</option>
                    <option value="farmer">Farmer</option>
                    <option value="admin">Admin</option>
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

            <button type="submit" name="add_user" class="btn btn-success">
                Save User
            </button>
        </form>

        <hr>
    </div>

    <!-- SEARCH -->
    <h3>Search Users</h3>
    <form method="GET" class="form-inline mb-3">
        <input type="text" name="search" class="form-control mr-2"
               placeholder="Search by phone, location or tasil"
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-secondary">Search</button>
        <a href="admin.php" class="btn btn-link">Clear</a>
    </form>

    <!-- USER TABLE -->
    <h3>Existing Users</h3>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Location</th>
                <th>Tasil</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                    <td><?php echo htmlspecialchars($user['location']); ?></td>
                    <td><?php echo htmlspecialchars($user['tasil']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="delete_user"
                                class="btn btn-danger btn-sm"
                                onclick="return confirm('Delete this user?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function toggleForm() {
    var form = document.getElementById("addUserForm");
    if (form.style.display === "none") {
        form.style.display = "block";
    } else {
        form.style.display = "none";
    }
}
</script>

</body>
</html>

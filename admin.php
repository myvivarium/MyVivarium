<?php
session_start();
require 'dbcon.php';

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Check if the user is logged in and has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admin users to the index page
    header("Location: index.php");
    exit;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST requests for user status and role updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

    // Initialize query variables
    $query = "";

    // Determine the action to take: approve, set to pending, delete user, set role to admin or user
    switch ($action) {
        case 'approve':
            $query = "UPDATE users SET status='approved' WHERE username=?";
            break;
        case 'pending':
            $query = "UPDATE users SET status='pending' WHERE username=?";
            break;
        case 'delete':
            $query = "DELETE FROM users WHERE username=?";
            break;
        case 'admin':
            $query = "UPDATE users SET role='admin' WHERE username=?";
            break;
        case 'user':
            $query = "UPDATE users SET role='user' WHERE username=?";
            break;
        default:
            die('Invalid action');
    }

    // Execute the prepared statement if a valid action is set
    if (!empty($query)) {
        $statement = mysqli_prepare($con, $query);
        if ($statement) {
            mysqli_stmt_bind_param($statement, "s", $username);
            mysqli_stmt_execute($statement);
            mysqli_stmt_close($statement);
        } else {
            // Log error and handle it gracefully
            error_log("Database error: " . mysqli_error($con));
            die('Database error');
        }
    }
}

// Fetch all users from the database
$query = "SELECT * FROM users";
$result = mysqli_query($con, $query);

require 'header.php';
mysqli_close($con);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Management | <?php echo htmlspecialchars($labName); ?></title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .main-content {
            justify-content: center;
            align-items: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="main-content">
            <h1>User Management</h1>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                            <td>
                                <form action="admin.php" method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($row['username']); ?>">

                                    <?php if ($row['status'] === 'pending') { ?>
                                        <button type="submit" class="btn btn-success btn-sm" name="action" value="approve">Approve</button>
                                    <?php } elseif ($row['status'] === 'approved') { ?>
                                        <button type="submit" class="btn btn-secondary btn-sm" name="action" value="pending">Deactivate</button>
                                    <?php } ?>

                                    <?php if ($row['role'] === 'user') { ?>
                                        <button type="submit" class="btn btn-warning btn-sm" name="action" value="admin">Make Admin</button>
                                    <?php } elseif ($row['role'] === 'admin') { ?>
                                        <button type="submit" class="btn btn-info btn-sm" name="action" value="user">Make User</button>
                                    <?php } ?>

                                    <button type="submit" class="btn btn-danger btn-sm" name="action" value="delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>

</html>

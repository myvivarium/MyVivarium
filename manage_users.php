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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .main-content {
            justify-content: center;
            align-items: center;
        }
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .action-buttons .btn {
            flex: 1 1 auto;
        }
        @media (max-width: 576px) {
            .table th, .table td {
                display: block;
                width: 100%;
            }
            .table thead {
                display: none;
            }
            .table tr {
                margin-bottom: 15px;
            }
            .table td::before {
                content: attr(data-label);
                font-weight: bold;
                text-transform: uppercase;
                margin-bottom: 5px;
                display: block;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="main-content">
            <h1 class="text-center">User Management</h1>
            <div class="table-responsive">
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
                                <td data-label="Username"><?php echo htmlspecialchars($row['username']); ?></td>
                                <td data-label="Status"><?php echo htmlspecialchars($row['status']); ?></td>
                                <td data-label="Role"><?php echo htmlspecialchars($row['role']); ?></td>
                                <td data-label="Actions">
                                    <form action="manage_users.php" method="post" class="action-buttons">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($row['username']); ?>">

                                        <?php if ($row['status'] === 'pending') { ?>
                                            <button type="submit" class="btn btn-success btn-sm" name="action" value="approve"><i class="fas fa-check"></i></button>
                                        <?php } elseif ($row['status'] === 'approved') { ?>
                                            <button type="submit" class="btn btn-secondary btn-sm" name="action" value="pending"><i class="fas fa-ban"></i></button>
                                        <?php } ?>

                                        <?php if ($row['role'] === 'user') { ?>
                                            <button type="submit" class="btn btn-warning btn-sm" name="action" value="admin"><i class="fas fa-user-shield"></i></button>
                                        <?php } elseif ($row['role'] == 'admin') { ?>
                                            <button type="submit" class="btn btn-info btn-sm" name="action" value="user"><i class="fas fa-user"></i></button>
                                        <?php } ?>

                                        <button type="submit" class="btn btn-danger btn-sm" name="action" value="delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>

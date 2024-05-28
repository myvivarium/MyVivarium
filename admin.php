<?php
session_start();
require 'dbcon.php';

// Check if the user is logged in and has admin role
if ($_SESSION['role'] !== 'admin') {
    // Redirect non-admin users to the index page
    header("Location: index.php");
    exit;
}

// Handle POST requests for user status updates
if (isset($_POST['username']) && isset($_POST['action'])) {
    $username = $_POST['username'];
    $action = $_POST['action'];

    // Initialize query variables
    $query = "";

    // Determine the action to take: approve, set to pending, or delete user
    if ($action === 'approve') {
        $query = "UPDATE users SET status='approved' WHERE username=?";
    } elseif ($action === 'pending') {
        $query = "UPDATE users SET status='pending' WHERE username=?";
    } elseif ($action === 'delete') {
        $query = "DELETE FROM users WHERE username=?";
    }

    // Execute the prepared statement if a valid action is set
    if (!empty($query)) {
        $statement = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($statement, "s", $username);
        mysqli_stmt_execute($statement);
        mysqli_stmt_close($statement);
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
    <!-- Meta tags and Bootstrap CSS -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin | <?php echo htmlspecialchars($labName); ?></title>
    <!-- Custom styles -->
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
        <div class="row align-items-center">
            <!-- Main content area -->
            <main class="col-md-12">
                <div class="container mt-5">
                    <h4 class="mb-3">Approve Users</h4>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Username/Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loop through each user and display their data -->
                                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['name']); ?></td>
                                        <td><?= htmlspecialchars($row['position']); ?></td>
                                        <td><?= htmlspecialchars($row['username']); ?></td>
                                        <td><?= htmlspecialchars($row['role']); ?></td>
                                        <td><?= htmlspecialchars($row['status']); ?></td>
                                        <td>
                                            <!-- Form for user status update -->
                                            <form action="admin.php" method="post">
                                                <input type="hidden" name="username" value="<?= htmlspecialchars($row['username']); ?>">
                                                <button type="submit" class="btn btn-success btn-sm" name="action" value="approve">Approve</button>
                                                <button type="submit" class="btn btn-secondary btn-sm" name="action" value="pending">Pending</button>
                                                <button type="submit" class="btn btn-danger btn-sm" name="action" value="delete">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Include footer -->
    <?php include 'footer.php'; ?>

</body>

</html>

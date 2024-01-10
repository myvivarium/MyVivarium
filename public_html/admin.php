<?php
session_start();
require 'dbcon.php';

// Check if the user is logged in as admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php"); // Redirect to index.php if not logged in as admin
    exit;
}

if (isset($_POST['username']) && isset($_POST['action'])) {
    $username = $_POST['username'];
    $action = $_POST['action'];

    $query = "";
    $status = "";

    if ($action === 'approve') {
        $query = "UPDATE users SET status='approved' WHERE username=?";
        $status = "approved";
    } elseif ($action === 'pending') {
        $query = "UPDATE users SET status='pending' WHERE username=?";
        $status = "pending";
    } elseif ($action === 'delete') {
        $query = "DELETE FROM users WHERE username=?";
    }

    if (!empty($query)) {
        $statement = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($statement, "s", $username);
        mysqli_stmt_execute($statement);
        mysqli_stmt_close($statement);
    }
}

$query = "SELECT * FROM users";
$result = mysqli_query($con, $query);

mysqli_close($con);

require 'header.php';
?>

<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <!-- Bootstrap JS for Dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <title>Admin</title>

    <style>
        /* General Styles */
        body {
            margin: 0;
            padding: 0;
        }

        /* Center Main Content */
        .main-content {
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row align-items-center">
            <!-- Main content -->
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
                                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                    <tr>
                                        <td><?= $row['name']; ?></td>
                                        <td><?= $row['position']; ?></td>
                                        <td><?= $row['username']; ?></td>
                                        <td><?= $row['role']; ?></td>
                                        <td><?= $row['status']; ?></td>
                                        <td>
                                        <form action="admin.php" method="post">
                                                <input type="hidden" name="username" value="<?= $row['username']; ?>">
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

    <?php include 'footer.php'; ?>

</body>
</html>


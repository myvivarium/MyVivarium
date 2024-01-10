<?php
// Start the session
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_username'])) {
    header("Location: adminlogin.php"); // Redirect to admin login page if not logged in
    exit;
}

// Require dbcon.php to connect to the database
require 'dbcon.php';

// Handle dropping a student from the approved table
if (isset($_POST['student_action']) && isset($_POST['username'])) {
    $username = $_POST['username'];

    // Delete the student's row from the udayton_requests table
    $query_drop_student = "DELETE FROM udayton_requests WHERE username=?";
    $statement_drop_student = mysqli_prepare($con, $query_drop_student);
    mysqli_stmt_bind_param($statement_drop_student, "s", $username);
    mysqli_stmt_execute($statement_drop_student);
    mysqli_stmt_close($statement_drop_student);
}

// Get the list of approved students from the udayton_requests table
$query_approved_students = "SELECT * FROM udayton_requests WHERE status='approved'";
$result_approved_students = mysqli_query($con, $query_approved_students);

function isActivePage($pageName) {
    return basename($_SERVER['PHP_SELF']) == $pageName;
}


?>

<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

    <title>Approved Students and External Admins</title>

    <style>
        /* Including previous styles for a consistent look */
        .top-bar {
            background-color: #343a40;
            padding: 8px 0;
            text-align: center;
            color: #ffffff;
            font-weight: 400;
            font-size: 24px;
            padding-left: 16px;
            z-index: 2;
        }

        .top-bar .navbar {
            background: none;
        }

        .top-bar .navbar-toggler-icon {
            background-color: #ecf0f1;
        }

        .top-bar .navbar-brand {
            color: #ffffff !important;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1;
            padding: 60px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background: none;
        }

        .sidebar::before {
            content: "";
            position: absolute;
            top: 8px;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #f8f9fa;
            z-index: -1;
        }

        .nav-link {
            color: #333;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .nav-link:hover {
            color: #007bff;
        }

        .nav-link.active {
            color: #007bff;
        }

        .nav-icons {
            width: 20px;
            margin-right: 10px;
        }
    </style>
    
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg top-bar">
    <a class="navbar-brand" href="#">MyVivarium</a>
</nav>

    <!-- Sidebar -->
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
    <div class="position-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo isActivePage('adminlanding.php') ? 'active' : ''; ?> d-flex align-items-center" aria-current="page" href="adminlanding.php">
                    <i class="fas fa-tachometer-alt nav-icons"></i>
                    <span>Admin Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo isActivePage('aaeinternal.php') ? 'active' : ''; ?> d-flex align-items-center" href="aaeinternal.php">
                    <i class="fas fa-user-check nav-icons"></i>
                    Approve EA Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo isActivePage('EA_students.php') ? 'active' : ''; ?> d-flex align-items-center" href="EA_students.php">
                    <i class="fas fa-user-times nav-icons"></i>
                    Drop/View EA Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo isActivePage('logout.php') ? 'active' : ''; ?> d-flex align-items-center" href="logout.php">
                    <i class="fas fa-sign-out-alt nav-icons"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

    <!-- Main content -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="container mt-5">

        <!-- Approved Students Table -->
        <h4 class="mb-3">Approved Students</h4>
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Username/Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result_approved_students as $row_approved_student) { ?>
                        <tr>
                            <td><?= $row_approved_student['name']; ?></td>
                            <td><?= $row_approved_student['role']; ?></td>
                            <td><?= $row_approved_student['username']; ?></td>
                            <td>
                                <form action="EA_students.php" method="post">
                                    <input type="hidden" name="username" value="<?= $row_approved_student['username']; ?>">
                                    <input type="hidden" name="student_action" value="drop">
                                    <button type="submit" class="btn btn-danger" name="drop_student">Drop</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</main>


    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p"
    crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/js/all.min.js"></script>
</body>

</html>

<?php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

global $username;
session_start();
require 'dbcon.php';

// Check if the user is already logged in
if (isset($_SESSION['admin_username'])) {
    header("Location: adminlanding.php"); // Redirect to admin landing page if already logged in
    exit;
}

// Handle admin login form submission
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if the user is an internal university admin
    $queryInternal = "SELECT * FROM udaytonadmin WHERE username=? AND password=?";
    $statementInternal = mysqli_prepare($con, $queryInternal);
    mysqli_stmt_bind_param($statementInternal, "ss", $username, $password);
    mysqli_stmt_execute($statementInternal);

    // Fetch the result
    $resultInternal = mysqli_stmt_get_result($statementInternal);

    // Check the number of rows returned by the query for internal admin
    $numRowsInternal = mysqli_num_rows($resultInternal);

    if ($numRowsInternal == 1) {
        // Internal admin login successful, store the username in the session
        $_SESSION['admin_username'] = $username;
        header("Location: adminlanding.php"); // Redirect to internal admin landing page after successful login
        exit;
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>



<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f5f5f5;
        }

        .top-bar {
            background-color: #343a40; /* Adjusted color to match Bootstrap's bg-dark */
            padding: 8px 0; /* Adjusted padding for height */
            text-align: center;
            color: #ffffff;
            font-weight: 400;
            font-size: 24px;
            padding-left: 16px; /* Added padding-left for positioning to the right */
        }


        .top-bar .navbar {
            background: none; /* Remove the default Bootstrap background */
        }

        .top-bar .navbar-toggler-icon {
            background-color: #ecf0f1; /* Adjust the hamburger icon color to be white-ish */
        }

        .top-bar .navbar-brand {
            color: #ffffff !important; /* Ensure the color is white-ish and overriding other styles */
        }


        .card {
            border-radius: 15px;
            overflow: hidden;
            background-color: #ecf0f1;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary {
            transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out;
            background-color: #2c3e50;
            color: #ecf0f1;
        }

        .btn-secondary:hover {
            background-color: #34495e;
            color: #bdc3c7;
        }
    </style>

    <title>Admin Login - MyVivarium</title>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg top-bar">
        <a class="navbar-brand" href="index.php">MyVivarium</a>
    </nav>


    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-center">Admin Login</h4>
                        <?php if (isset($error_message)) { ?>
                        <div class="alert alert-danger" role="alert">
                            <?= $error_message; ?>
                        </div>
                        <?php } ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">University Email</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" name="login">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Go Back Button -->
    <div class="container mt-3">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <a href="index.php" class="btn btn-secondary">Go Back</a>
            </div>
        </div>
    </div>

    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

</body>

</html>

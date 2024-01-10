<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require 'dbcon.php';

// Check if the user is already logged in
if (isset($_SESSION['admin_username'])) {
    header("Location: adminlanding.php"); // Redirect to admin landing page if already logged in
    exit;
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle admin signup form submission
if (isset($_POST['signup'])) {
    $name = $_POST['name'];
    $role = $_POST['role'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Insert the signup request into the external_university_admin table with status 'pending'
    $query = "INSERT INTO udayton_requests (name, role, username, password, status) VALUES (?, ?, ?, ?, 'pending')";
    $statement = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($statement, "ssss", $name, $role, $username, $password);
    mysqli_stmt_execute($statement);

    // Check if the query executed successfully
    if (!$statement) {
        die('Error executing the query: ' . mysqli_error($con));
    }

    // Signup request submitted successfully, show a success message or redirect to a success page
    // For simplicity, we'll redirect to the same page with a success parameter in the URL
    header("Location: internalstudentsignup.php?success=true");
    exit;
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
            background-color: #ecf0f1;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s; /* Added transition for hover effect */
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }



        .btn-secondary {
            background-color: #2c3e50; /* Using a neutral color for secondary button */
            color: #ecf0f1;
            transition: background-color 0.3s ease-in-out;
        }

        .btn-secondary:hover {
            background-color: #34495e;
            color: #bdc3c7;
        }
        
    </style>

    <title>Internal University Signup</title>
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
                        <h4 class="card-title text-center">Internal University Signup</h4>
                        <?php if (isset($_GET['success']) && $_GET['success'] === 'true') { ?>
                        <div class="alert alert-success" role="alert">
                            Signup request submitted successfully. Please wait for approval from the admin.
                        </div>
                        <?php } ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" class="form-control" id="role" name="role" required>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">University Email</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" name="signup">Signup</button>
                        </form>
                    </div>
                </div>
                <div class="mt-3 text-center">
                    <a href="index.php" class="btn btn-secondary">Go Back</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>
</html>


<?php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

global $username;
session_start();
require 'dbcon.php';

// Check if the user is already logged in
if (isset($_SESSION['name'])) {
    // After login
    if (isset($_GET['redirect'])) {
        $url = urldecode($_GET['redirect']);
        header("Location: $url");
        exit;
    } else {
        // Redirect to default page
        header("Location: home.php");
        exit;
    }
}

// Handle admin login form submission
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch hashed password and salt from the database based on username
    $query = "SELECT * FROM users WHERE username=? AND status='approved'";
    $statement = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($statement, "s", $username);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);

    if ($row = mysqli_fetch_assoc($result)) {
        $storedHashedPassword = $row['password'];

        // Verify the entered password against the stored hash
        if (password_verify($password, $storedHashedPassword)) {
            // Passwords match, login successful
            $_SESSION['name'] = $row['name'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            if (isset($_GET['redirect'])) {
                $url = urldecode($_GET['redirect']);
                header("Location: $url");
                exit;
            } else {
                // Redirect to default page
                header("Location: home.php");
                exit;
            }
        } else {
            // Password verification failed, set error message
            $error_message = "Login failed due to the wrong password.";
        }
    } else {
        // No user found with the provided username, set error message
        $error_message = "Login failed due to the wrong username.";
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sathyanesan Lab's Vivarium</title>

    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        .carousel img {
            height: 300px;
            object-fit: cover;
            width: 100%;
        }

        .login-form {
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0px 3px 10px rgba(0, 0, 0, 0.1);
        }

        feature-box {
            transition: transform .2s, box-shadow .2s;
            border-radius: 10px;
            padding: 30px;
            background-color: white;
            box-shadow: 0px 3px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin: 50px 0px 50px 0px;
        }

        .feature-box h3 {
            margin-top: 0;
            color: #007bff;
            /* Matching Bootstrap's primary color for consistency */
        }

        .feature-box p {
            margin-bottom: 0;
        }

        /* Header and Footer Styling */
        .header-footer {
            background-color: #343a40;
            padding: 20px 0;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }

        .header-footer h2,
        .header-footer p {
            margin: 0;
            color: #333;
        }

        .forgot-password-link {
            text-align: right;
            margin-left: 100px;
            /* Adjust margin as needed for spacing */
        }
    </style>
</head>

<body>

    <!-- Header with Lab Name -->
    <header class="bg-dark text-white text-center py-3">
        <h1>Sathyanesan Lab's Vivarium</h1>
    </header>

    <!-- Main Content -->
    <div class="container mt-4">
        <div style="margin: 50px 0px 0px 50px;" class="row">
            <!-- Slideshow Column -->
            <div class="col-md-6">
                <div id="labCarousel" class="carousel slide" data-ride="carousel">
                    <!-- Slideshow Images -->
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img class="d-block w-100" src="images/DSC_0536.JPG" alt="Image 1">
                        </div>
                        <div class="carousel-item">
                            <img class="d-block w-100" src="images/DSC_0537.JPG" alt="Image 2">
                        </div>
                        <div class="carousel-item">
                            <img class="d-block w-100" src="images/DSC_0539.JPG" alt="Image 3">
                        </div>
                        <div class="carousel-item">
                            <img class="d-block w-100" src="images/DSC_0540.JPG" alt="Image 4">
                        </div>
                        <div class="carousel-item">
                            <img class="d-block w-100" src="images/DSC_0560.JPG" alt="Image 7">
                        </div>
                        <div class="carousel-item">
                            <img class="d-block w-100" src="images/DSC_0562.JPG" alt="Image 8">
                        </div>
                        <div class="carousel-item">
                            <img class="d-block w-100" src="images/DSC_0586.JPG" alt="Image 11">
                        </div>
                        <div class="carousel-item">
                            <img class="d-block w-100" src="images/DSC_0593.JPG" alt="Image 12">
                        </div>
                        <div class="carousel-item">
                            <img class="d-block w-100" src="images/DSC_0607.JPG" alt="Image 13">
                        </div>
                        <div class="carousel-item">
                            <img class="d-block w-100" src="images/DSC_0623.JPG" alt="Image 14">
                        </div>
                        <div class="carousel-item">
                            <img class="d-block w-100" src="images/DSC_0658.JPG" alt="Image 15">
                        </div>
                        <div class="carousel-item">
                            <img class="d-block w-100" src="images/DSC_0665.JPG" alt="Image 516">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Form Column -->
            <div class="col-md-6">
                <div class="login-form">
                    <h3>Login</h3>
                    <!-- Display error message if set -->
                    <?php if (isset($error_message)) { ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php } ?>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="username">Username or Email Address</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary" name="login">Login</button>
                        <!-- Signup Link -->
                        <a href="signup.php" class="btn btn-secondary">Sign Up</a>
                        <!-- Forgot Password Link -->
                        <a href="forgot_password.php" class="forgot-password-link">Forgot Password?</a>
                    </form>
                </div>
            </div>

        </div>

        <!-- New Row for Unique Features -->
        <div class="row mt-4">
            <div style="margin:50px 0px 50px 0px;" class="col-md-12">
                <h2 class="text-center">Welcome to MyVivarium.online</h2>
                <p class="text-center italic">Elevate Your Research with IoT-Enhanced Colony Management</p>

                <!-- Feature Box 1 -->
                <div style="margin:50px 0px 50px 0px;" class="col-md-6 mb-6 mx-auto feature-box text-center">
                    <h3>Real-Time Environmental Monitoring</h3>
                    <p>Gain unparalleled insights into the conditions of your vivarium. Our IoT sensors continuously track temperature and humidity levels, ensuring a stable and controlled environment for your research animals.</p>
                </div>

                <!-- Feature Box 2 -->
                <div style="margin:50px 0px 50px 0px;" class="col-md-6 mb-6 mx-auto feature-box text-center">
                    <h3>Effortless Cage and Mouse Tracking</h3>
                    <p>Seamlessly monitor every cage and mouse in your facility. No more manual record-keeping or confusion.</p>
                </div>

                <!-- Feature Box 3 -->
                <div style="margin:50px 0px 50px 0px;" class="col-md-6 mb-6 mx-auto feature-box text-center">
                    <h3>Security and Compliance</h3>
                    <p>Rest easy knowing your data is secure and compliant with industry regulations. We prioritize data integrity and confidentiality.</p>
                </div>
            </div>
        </div>

    </div>

    <?php include 'footer.php'; ?>

    <!-- Bootstrap and jQuery JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
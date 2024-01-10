<?php
session_start();
require 'dbcon.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['ea_username'])) {
    header("Location: EAstudentlogin.php");
    exit;
}

// Extract domain from the email
$email_domain = substr(strrchr($_SESSION['ea_username'], "@"), 1);

// Verify the email domain is udayton.edu, otherwise redirect or provide an error
if ($email_domain !== "udayton.edu") {
    die("Access denied. Only udayton.edu email domains are allowed.");
}

// Set table names directly
$holdingTable = "holdingcage";
$matingTable = "matingcage";

// Fetch counts from the holding table
$result = $con->query("SELECT COUNT(*) AS count FROM " . $holdingTable);
if ($result === false) {
    die("Database query failed: " . $con->error);
}
$row = $result->fetch_assoc();
$holdingCount = $row['count'];

// Fetch counts from the mating table
$result = $con->query("SELECT COUNT(*) AS count FROM " . $matingTable);
if ($result === false) {
    die("Database query failed: " . $con->error);
}
$row = $result->fetch_assoc();
$matingCount = $row['count'];

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
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

    <title>Student Landing</title>
    <style>
        .top-bar {
            background-color: #343a40; /* Adjusted color to match Bootstrap's bg-dark */
            padding: 8px 0; /* Adjusted padding for height */
            text-align: center;
            color: #ffffff;
            font-weight: 400;
            font-size: 24px;
            padding-left: 16px; /* Added padding-left for positioning to the right */
            z-index: 2;
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

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1;  /* We can keep this positive now */
            padding: 60px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }

        .sidebar::before {
            content: "";
            position: absolute;
            top: 8px;  /* Adjust this according to the height of your top-bar + desired offset */
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #f8f9fa;  /* Assuming the sidebar's background color is this, adjust as needed */
            z-index: -1;  /* This will place the background behind the clickable elements but still above the top-bar */
        }


        .nav-link {
            color: #333;
            font-weight: 500;
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
        @media (max-width: 767.98px) {
    .sidebar:not(.show) {
        display: none;
    }
}

    </style>
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg top-bar">
    <a class="navbar-brand" href="#">MyVivarium</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link active d-flex align-items-center" aria-current="page" href="EAstudentlanding.php">
                            <i class="fas fa-tachometer-alt nav-icons"></i>
                            <span>Student Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="logout.php">
                            <i class="fas fa-sign-out-alt nav-icons"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Welcome, <?php echo $_SESSION['ea_username']; ?></h1>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <p>This is the Student Landing Page.</p>
                    <p>You can choose from the sidebar options or use the buttons below.</p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="home.php" class="btn btn-primary me-md-2">Holding Cage</a>
                        <a href="mating.php" class="btn btn-primary">Mating Cage</a>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card text-center">
                            <div class="card-header bg-primary text-white">
                                Holding Cage
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $holdingCount; ?></h5>
                                <p class="card-text">Total Entries</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card text-center">
                            <div class="card-header bg-primary text-white">
                                Mating Cage
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $matingCount; ?></h5>
                                <p class="card-text">Total Entries</p>
                            </div>
        </main>
        <?php include 'whiteboard.php'; ?>
    </div>
</div>

<!-- Option 1: Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p"
    crossorigin="anonymous"></script>

<!-- Font Awesome JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/js/all.min.js"></script>
<script>
    const sidebarToggleBtn = document.querySelector('.navbar-toggler');
    const sidebar = document.getElementById('sidebar');

    sidebarToggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('show');
    });
</script>
</body>
</html>

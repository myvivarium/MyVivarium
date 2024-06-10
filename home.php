<?php

/**
 * Home Page
 * 
 * This script serves as the home page for the web application. It displays a welcome message to the logged-in user, 
 * along with statistics on holding and breeding cages, and provides links to their respective dashboards. 
 * Additionally, it includes a section for general notes.
 * 
 * Author: [Your Name]
 * Date: [Date]
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';

// Check if the user is logged in, redirect to login page if not logged in
if (!isset($_SESSION['name'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit;
}

// Fetch the counts for holding and breeding cages
$holdingCountResult = $con->query("SELECT COUNT(*) AS count FROM hc_basic");
$holdingCountRow = $holdingCountResult->fetch_assoc();
$holdingCount = $holdingCountRow['count'];

$matingCountResult = $con->query("SELECT COUNT(*) AS count FROM bc_basic");
$matingCountRow = $matingCountResult->fetch_assoc();
$matingCount = $matingCountRow['count'];

// Include the header file
require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Meta tags for character encoding and responsive design -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Home | <?php echo htmlspecialchars($labName); ?></title>

    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
        }

        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Display session messages if any -->
        <?php include('message.php'); ?>
        <br>
        <div class="row align-items-center">
            <!-- Welcome message with user information -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>
                    <span style="font-size: smaller; color: #555; border-bottom: 2px solid #ccc; padding: 0 5px;">
                        [<?php echo htmlspecialchars($_SESSION['position']); ?>]
                    </span>
                </h2>
            </div>

            <!-- Display stats for Holding Cage and Breeding Cage -->
            <div class="card">
                <div class="card-body">
                    <div class="row mt-4">
                        <!-- Holding Cage Stats -->
                        <div class="col-md-6">
                            <div class="card text-center">
                                <div class="card-header bg-primary text-white">
                                    <a href="hc_dash.php" style="color: white; text-decoration: none;">Holding Cage</a>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $holdingCount; ?></h5>
                                    <p class="card-text">Total Entries</p>
                                </div>
                            </div>
                        </div>
                        <!-- Breeding Cage Stats -->
                        <div class="col-md-6">
                            <div class="card text-center">
                                <div class="card-header bg-primary text-white">
                                    <a href="bc_dash.php" style="color: white; text-decoration: none;">Breeding Cage</a>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $matingCount; ?></h5>
                                    <p class="card-text">Total Entries</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Display sticky notes section -->
            <div style="margin-top: 50px;">
                <h2><?php echo htmlspecialchars($labName); ?> - General Notes</h2>
                <?php include 'nt_app.php'; ?> <!-- Include the note application file -->
            </div>
        </div>
        <!-- Include the footer file -->
        <?php include 'footer.php'; ?>
    </div>

    <script>
        function adjustFooter() {
            const footer = document.getElementById('footer');
            const mainContent = document.querySelector('.main-content');
            const container = document.querySelector('.container');

            // Remove inline styles to calculate natural height
            footer.style.position = 'relative';
            footer.style.bottom = 'auto';

            const containerHeight = container.offsetHeight;
            const windowHeight = window.innerHeight;

            // If content is shorter than viewport, fix the footer at the bottom
            if (containerHeight < windowHeight) {
                footer.style.position = 'absolute';
                footer.style.bottom = '0';
            } else {
                footer.style.position = 'relative';
                footer.style.bottom = 'auto';
            }
        }

        window.addEventListener('load', adjustFooter);
        window.addEventListener('resize', adjustFooter);
    </script>
</body>

</html>
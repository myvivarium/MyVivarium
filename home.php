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

// Fetch the task stats for the logged-in user
$userId = $_SESSION['user_id'];
$taskStatsQuery = "
    SELECT 
        COUNT(*) AS total, 
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending
    FROM tasks
    WHERE FIND_IN_SET('$userId', assigned_to)
";
$taskStatsResult = $con->query($taskStatsQuery);
$taskStatsRow = $taskStatsResult->fetch_assoc();
$totalTasks = $taskStatsRow['total'] ?? 0;
$completedTasks = $taskStatsRow['completed'] ?? 0;
$inProgressTasks = $taskStatsRow['in_progress'] ?? 0;
$pendingTasks = $taskStatsRow['pending'] ?? 0;

// Set completedTasks, inProgressTasks, and pendingTasks to zero if totalTasks is zero
if ($totalTasks == 0) {
    $completedTasks = 0;
    $inProgressTasks = 0;
    $pendingTasks = 0;
}

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

        .main-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }
    </style>

</head>

<body>
    <div class="main-content content">
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
            <h2 class="mt-4">Cages Summary</h2>
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

            <!-- Display Task Stats for Logged-in User -->
            <h2 class="mt-4">Summary of Your Tasks</h2>
            <div class="card" style="margin-top: 20px;">
                <div class="card-body">
                    <div class="row mt-4">
                        <!-- Total Tasks -->
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-header bg-info text-white">
                                    <a href="manage_tasks.php?filter=assigned_to_me" style="color: white; text-decoration: none;">Total Tasks</a>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $totalTasks; ?></h5>
                                    <p class="card-text">Total</p>
                                </div>
                            </div>
                        </div>
                        <!-- Completed Tasks -->
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-header bg-success text-white">
                                    <a href="manage_tasks.php?search=completed&filter=assigned_to_me" style="color: white; text-decoration: none;">Completed</a>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $completedTasks; ?></h5>
                                    <p class="card-text">Completed</p>
                                </div>
                            </div>
                        </div>
                        <!-- In Progress Tasks -->
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-header bg-warning text-white">
                                    <a href="manage_tasks.php?search=in+progress&filter=assigned_to_me" style="color: white; text-decoration: none;">In Progress</a>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $inProgressTasks; ?></h5>
                                    <p class="card-text">In Progress</p>
                                </div>
                            </div>
                        </div>
                        <!-- Pending Tasks -->
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-header bg-danger text-white">
                                    <a href="manage_tasks.php?search=pending&filter=assigned_to_me" style="color: white; text-decoration: none;">Pending</a>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $pendingTasks; ?></h5>
                                    <p class="card-text">Pending</p>
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
    </div>
    <!-- Include the footer file -->
    <?php include 'footer.php'; ?>

    <script>
        function adjustFooter() {
            const footer = document.getElementById('footer');
            const container = document.querySelector('.top-container');
            const header = document.querySelector('.header');
            const navcontainer = document.querySelector('.nav-container');

            // Reset footer styles to compute natural height
            footer.style.position = 'relative';
            footer.style.bottom = 'auto';
            footer.style.width = '100%';

            // Calculate the height occupied by header and nav container
            const headerHeight = header ? header.offsetHeight : 0;
            const navcontainerHeight = navcontainer ? navcontainer.offsetHeight : 0;
            const footerHeight = footer.offsetHeight;

            // Calculate available space minus header and nav container
            const availableSpace = window.innerHeight - headerHeight - navcontainerHeight;

            // Calculate total content height
            const contentHeight = container.scrollHeight + footerHeight;

            // Adjust footer position based on content height and available space
            if (contentHeight < availableSpace) {
                footer.style.position = 'absolute';
                footer.style.bottom = '0';
            } else {
                footer.style.position = 'relative';
            }
        }

        // Adjust footer on page load and window resize
        window.addEventListener('load', adjustFooter);
        window.addEventListener('resize', adjustFooter);
    </script>


</body>

</html>
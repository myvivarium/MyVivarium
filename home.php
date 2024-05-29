<?php
session_start();
require 'dbcon.php';

// Check if the user is logged in
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

// Include the header
require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Home | <?php echo htmlspecialchars($labName); ?></title>

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
        <!-- Display session messages if any -->
        <?php include('message.php'); ?>
        <br>
        <div class="row align-items-center">
            <!-- Welcome message with user information -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Welcome, <?php echo $_SESSION['name']; ?> <span>, [<?php echo $_SESSION['position']; ?>]</span></h2>
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
                <h2><?php echo htmlspecialchars($labName); ?> - Lab Sticky Notes</h2>
                <?php include 'nt_app.php'; ?>
            </div>
        </div>
    </div>

    <!-- Include the footer -->
    <?php include 'footer.php'; ?>

</body>

</html>

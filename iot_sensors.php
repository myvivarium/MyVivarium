<?php

/**
 * IOT Sensors Page
 * 
 * This script displays the IOT sensor data for different rooms in the lab. The data is shown using iframes 
 * that load content from specified URLs stored in the database. The user must be logged in to access this page.
 * 
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit; // Exit to ensure no further code is executed
}

// Query to fetch the IoT sensor links from the settings table
$dataQuery = "SELECT name, value FROM settings WHERE name IN ('r1_temp', 'r1_humi', 'r1_illu', 'r1_pres', 'r2_temp', 'r2_humi', 'r2_illu', 'r2_pres')";
$dataResult = mysqli_query($con, $dataQuery);

// Initialize sensor variables
$r1_temp = $r1_humi = $r1_illu = $r1_pres = $r2_temp = $r2_humi = $r2_illu = $r2_pres = "";

while ($row = mysqli_fetch_assoc($dataResult)) {
    switch ($row['name']) {
        case 'r1_temp':
            $r1_temp = $row['value'];
            break;
        case 'r1_humi':
            $r1_humi = $row['value'];
            break;
        case 'r1_illu':
            $r1_illu = $row['value'];
            break;
        case 'r1_pres':
            $r1_pres = $row['value'];
            break;
        case 'r2_temp':
            $r2_temp = $row['value'];
            break;
        case 'r2_humi':
            $r2_humi = $row['value'];
            break;
        case 'r2_illu':
            $r2_illu = $row['value'];
            break;
        case 'r2_pres':
            $r2_pres = $row['value'];
            break;
    }
}

// Include the header file
require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IOT Sensors | <?php echo htmlspecialchars($labName); ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

    <!-- Inline CSS for styling -->
    <style>
        /* Basic styling for body */
        body {
            margin: 0;
            padding: 0;
        }

        /* Styling for iframe container */
        .iframe-container {
            position: relative;
            width: 100%;
            height: auto;
            margin-top: 20px;
        }

        /* Styling for iframes */
        .iframe {
            width: 100%;
            height: 300px;
            border: none;
        }
    </style>
</head>

<body>
    <div class="container mt-4 content">
        <!-- Section for Room 1 IOT Sensors -->
        <?php if (!empty($r1_temp) || !empty($r1_humi) || !empty($r1_illu) || !empty($r1_pres)) : ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h2><?php echo htmlspecialchars($labName); ?> - Room 1 IOT Sensors</h2>
                </div>

                <div class="col-md-6 mb-4">
                    <iframe class="iframe" src="<?php echo htmlspecialchars($r1_temp); ?>"></iframe>
                </div>
                <div class="col-md-6 mb-4">
                    <iframe class="iframe" src="<?php echo htmlspecialchars($r1_humi); ?>"></iframe>
                </div>
                <div class="col-md-6 mb-4">
                    <iframe class="iframe" src="<?php echo htmlspecialchars($r1_illu); ?>"></iframe>
                </div>
                <div class="col-md-6 mb-4">
                    <iframe class="iframe" src="<?php echo htmlspecialchars($r1_pres); ?>"></iframe>
                </div>
            </div>
        <?php endif; ?>

        <!-- Section for Room 2 IOT Sensors -->
        <?php if (!empty($r2_temp) || !empty($r2_humi) || !empty($r2_illu) || !empty($r2_pres)) : ?>
            <div class="row">
                <div class="col-12">
                    <h2><?php echo htmlspecialchars($labName); ?> - Room 2 IOT Sensors</h2>
                </div>

                <div class="col-md-6 mb-4">
                    <iframe class="iframe" src="<?php echo htmlspecialchars($r2_temp); ?>"></iframe>
                </div>
                <div class="col-md-6 mb-4">
                    <iframe class="iframe" src="<?php echo htmlspecialchars($r2_humi); ?>"></iframe>
                </div>
                <div class="col-md-6 mb-4">
                    <iframe class="iframe" src="<?php echo htmlspecialchars($r2_illu); ?>"></iframe>
                </div>
                <div class="col-md-6 mb-4">
                    <iframe class="iframe" src="<?php echo htmlspecialchars($r2_pres); ?>"></iframe>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Include the footer file -->
    <?php include 'footer.php'; ?>
</body>

</html>
<?php
session_start();
require 'dbcon.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['name'])) {
    header("Location: index.php"); // Redirect to admin login page if not logged in
    exit;
}

// Query to fetch the iot sensors link
$dataQuery = "SELECT * FROM data LIMIT 1";
$dataResult = mysqli_query($con, $dataQuery);

if ($datarow = mysqli_fetch_assoc($dataResult)) {
    $r1_temp = $datarow['r1_temp'];
    $r1_humi = $datarow['r1_humi'];
    $r1_illu = $datarow['r1_illu'];
    $r1_pres = $datarow['r1_pres'];
    $r2_temp = $datarow['r2_temp'];
    $r2_humi = $datarow['r2_humi'];
    $r2_illu = $datarow['r2_illu'];
    $r2_pres = $datarow['r2_pres'];
}

require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>IOT Sensors | <?php echo htmlspecialchars($labName); ?></title>

    <style>
        body {
            margin: 0;
            padding: 0;
        }

        .iframe-container {
            position: relative;
            width: 100%;
            height: 800px;
        }

        .iframe-top-left,
        .iframe-top-right,
        .iframe-bottom-left,
        .iframe-bottom-right {
            position: absolute;
            width: 50%;
            height: 50%;
            border: none;
            margin: 50px 0px;
        }

        .iframe-top-left {
            top: 0;
            left: 0;
        }

        .iframe-top-right {
            top: 0;
            right: 0;
        }

        .iframe-bottom-left {
            bottom: 0;
            left: 0;
        }

        .iframe-bottom-right {
            bottom: 0;
            right: 0;
        }
    </style>
</head>

<body>

    <div class="container">
        <div style="margin-top: 50px;">
            <h2><?php echo htmlspecialchars($labName); ?> - IOT Sensors</h2>
        </div>

        <!-- Your embedded iframe -->
        <div class="iframe-container">
            <!-- Top Left Iframe -->
            <iframe class="iframe-top-left" src="<?php echo $r1_temp; ?>" width="450" height="300" frameborder="0"></iframe>

            <!-- Top Right Iframe -->
            <iframe class="iframe-top-right" src="<?php echo $r1_humi; ?>" width="450" height="300" frameborder="0"></iframe>

            <!-- Bottom Left Iframe -->
            <iframe class="iframe-bottom-left" src="<?php echo $r1_illu; ?>" width="450" height="300" frameborder="0"></iframe>

            <!-- Bottom Right Iframe -->
            <iframe class="iframe-bottom-right" src="<?php echo $r1_pres; ?>" width="450" height="300" frameborder="0"></iframe>
        </div>

        <div style="margin-top: 50px;">
            <h2><?php echo htmlspecialchars($labName); ?> - Room 2 IOT Sensors</h2>
        </div>

        <!-- Embedded iframe container for Room 2 -->
        <div class="iframe-container">
            <!-- Top Left Iframe for Room 2 Temperature -->
            <iframe class="iframe-top-left" src="<?php echo $r2_temp; ?>" width="450" height="300" frameborder="0"></iframe>

            <!-- Top Right Iframe for Room 2 Humidity -->
            <iframe class="iframe-top-right" src="<?php echo $r2_humi; ?>" width="450" height="300" frameborder="0"></iframe>

            <!-- Bottom Left Iframe for Room 2 Illuminance -->
            <iframe class="iframe-bottom-left" src="<?php echo $r2_illu; ?>" width="450" height="300" frameborder="0"></iframe>

            <!-- Bottom Right Iframe for Room 2 Pressure -->
            <iframe class="iframe-bottom-right" src="<?php echo $r2_pres; ?>" width="450" height="300" frameborder="0"></iframe>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>

</html>
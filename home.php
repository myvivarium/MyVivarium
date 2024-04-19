<?php
session_start();
require 'dbcon.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['name'])) {
    header("Location: index.php"); // Redirect to admin login page if not logged in
    exit;
}
$result = $con->query("SELECT COUNT(*) AS count FROM hc_basic");
$row = $result->fetch_assoc();
$holdingCount = $row['count'];

$result = $con->query("SELECT COUNT(*) AS count FROM bc_basic");
$row = $result->fetch_assoc();
$matingCount = $row['count'];

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

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <title>Home | <?php echo htmlspecialchars($labName); ?></title>

    <style>
        /* General Styles */
        body {
            margin: 0;
            padding: 0;
        }

        /* Center Main Content */
        .main-content {
            justify-content: center;
            align-items: center;
        }

        /* Container for iframes */
        .iframe-container {
            position: relative;
            width: 100%;
            height: 800px;
        }

        /* Individual iframe positions */
        .iframe-top-left,
        .iframe-top-right,
        .iframe-bottom-left,
        .iframe-bottom-right {
            position: absolute;
            width: 50%;
            height: 50%;
            border: none;
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
        <div class="row align-items-center">

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Welcome,
                    <?php echo $_SESSION['name']; ?><span>, [<?php echo $_SESSION['position']; ?>]</span>
                </h2>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card text-center">
                                <div class="card-header bg-primary text-white">
                                    <a href="hc_dash.php" style="color: white; text-decoration: none;">Holding Cage</a>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php echo $holdingCount; ?>
                                    </h5>
                                    <p class="card-text">Total Entries</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card text-center">
                                <div class="card-header bg-primary text-white">
                                    <a href="bc_dash.php" style="color: white; text-decoration: none;">Breeding Cage</a>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php echo $matingCount; ?>
                                    </h5>
                                    <p class="card-text">Total Entries</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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

            <?php
            // Check if the temperature data for Room 2 is not null
            if (!is_null($r2_temp) || !is_null($r2_humi) || !is_null($r2_illu) || !is_null($r2_pres)) {
            ?>

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

            <?php
            }
            ?>


            <div style="margin-top: 50px;">
                <h2><?php echo htmlspecialchars($labName); ?> - Lab Sticky Notes</h2>
                <button class="add-note-btn" onclick="togglePopup()">Add Note</button>
                <?php include 'nt_app.php'; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>

</html>
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

require 'header.php';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Bootstrap JS for Dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <title>Home</title>

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

            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Welcome,
                    <?php echo $_SESSION['name']; ?><span>, [
                        <?php echo $_SESSION['role']; ?>]
                    </span>
                </h2>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card text-center">
                                <div class="card-header bg-primary text-white">
                                    Holding Cage
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
                                    Mating Cage
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
                <h2>My Vivarium - IOT Sensors</h2>
            </div>

            <!-- Your embedded iframe -->
            <div class="iframe-container">
                <!-- Top Left Iframe -->
                <iframe class="iframe-top-left"
                    src="https://sensor.sathyanesanlab-iot.work/d-solo/e5cd9da9-01e9-4e72-b022-a8c39ba0a1e5/myvivarium-sensor-data?orgId=1&from=now-6h&to=now&refresh=5s&theme=light&panelId=2"
                    width="450" height="300" frameborder="0"></iframe>

                <!-- Top Right Iframe -->
                <iframe class="iframe-top-right"
                    src="https://sensor.sathyanesanlab-iot.work/d-solo/e5cd9da9-01e9-4e72-b022-a8c39ba0a1e5/myvivarium-sensor-data?orgId=1&from=now-6h&to=now&refresh=5s&theme=light&panelId=1"
                    width="450" height="300" frameborder="0"></iframe>

                <!-- Bottom Left Iframe -->
                <iframe class="iframe-bottom-left"
                    src="https://sensor.sathyanesanlab-iot.work/d-solo/e5cd9da9-01e9-4e72-b022-a8c39ba0a1e5/myvivarium-sensor-data?&orgId=1&from=now-6h&to=now&refresh=5s&theme=light&panelId=3"
                    width="450" height="300" frameborder="0"></iframe>

                <!-- Bottom Right Iframe -->
                <iframe class="iframe-bottom-right"
                    src="https://sensor.sathyanesanlab-iot.work/d-solo/e5cd9da9-01e9-4e72-b022-a8c39ba0a1e5/myvivarium-sensor-data?&orgId=1&from=now-6h&to=now&refresh=5s&theme=light&panelId=4"
                    width="450" height="300" frameborder="0"></iframe>
            </div>

            <div style="margin-top: 50px;">
                <h2>My Vivarium - Lab Sticky Notes</h2>
                <button class="add-note-btn" onclick="togglePopup()">Add Note</button>
                <?php include 'nt_app.php'; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>

</html>
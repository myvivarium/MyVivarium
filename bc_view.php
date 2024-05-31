<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the breedingcage record with the specified ID
    $query = "SELECT * FROM bc_basic WHERE `cage_id` = '$id'";
    $result = mysqli_query($con, $query);

    // Fetch files associated with the specified cage ID
    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

    // Check if the breedingcage record exists
    if (mysqli_num_rows($result) === 1) {
        $breedingcage = mysqli_fetch_assoc($result);
    } else {
        $_SESSION['message'] = 'Invalid ID.';
        header("Location: bc_dash.php");
        exit();
    }
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: bc_dash.php");
    exit();
}

require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>

    <title>View Breeding Cage | <?php echo htmlspecialchars($labName); ?></title>

    <script>
        function showQrCodePopup(cageId) {
            // Create the popup window
            var popup = window.open("", "QR Code for Cage " + cageId, "width=400,height=400");

            // URL to generate the QR code image
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://myvivarium.online/bc_view.php?id=' + cageId;

            // HTML content for the popup, including a dynamic title and the QR code image
            var htmlContent = `
            <html>
            <head>
                <title>QR Code for Cage ${cageId}</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding-top: 40px; }
                    h1 { color: #333; }
                    img { margin-top: 20px; }
                </style>
            </head>
            <body>
                <h1>QR Code for Cage ${cageId}</h1>
                <img src="${qrUrl}" alt="QR Code for Cage ${cageId}" />
            </body>
            </html>
        `;

            // Write the HTML content to the popup document
            popup.document.write(htmlContent);
            popup.document.close(); // Close the document for further writing
        }

        function goBack() {
            window.history.back();
        }
    </script>


    <style>
        .container {
            max-width: 800px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: auto;
        }

        .table-wrapper {
            padding: 10px;
        }

        .table-wrapper table {
            width: 100%;
            border: 1px solid #000;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-wrapper th,
        .table-wrapper td {
            border: 1px solid gray;
            padding: 8px;
            text-align: left;
        }

        .table-wrapper th:nth-child(1),
        .table-wrapper td:nth-child(1) {
            width: 25%;
        }

        .table-wrapper th:nth-child(2),
        .table-wrapper td:nth-child(2) {
            width: 25%;
        }

        .table-wrapper th:nth-child(3),
        .table-wrapper td:nth-child(3) {
            width: 50%;
        }

        span {
            font-size: 12pt;
            line-height: 1;
            display: inline-block;
        }

        .note-app-container {
            margin-top: 20px;
            padding: 20px;
            background-color: #e9ecef;
            border-radius: 8px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>

<body>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h4>View Breeding Cage <?= htmlspecialchars($breedingcage['cage_id']); ?></h4>
                <div class="action-buttons">
                    <a href="javascript:void(0);" onclick="goBack()" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Go Back">
                        <i class="fas fa-arrow-circle-left"></i>
                    </a>
                    <a href="bc_edit.php?id=<?= rawurlencode($breedingcage['cage_id']); ?>" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Edit Cage">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="javascript:void(0);" onclick="showQrCodePopup('<?= rawurlencode($breedingcage['cage_id']); ?>')" class="btn btn-success btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="QR Code">
                        <i class="fas fa-qrcode"></i>
                    </a>
                    <a  href="javascript:void(0);" onclick="window.print()" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Print Cage">
                        <i class="fas fa-print"></i>
                    </a>
                    </button>
                </div>
            </div>
            <br>
            <div class="table-wrapper">
                <table class="table table-bordered" id="mouseTable">
                    <tr>
                        <th>Cage #:</th>
                        <td><?= htmlspecialchars($breedingcage['cage_id']); ?></td>
                    </tr>
                    <tr>
                        <th>PI Name</th>
                        <td><?= htmlspecialchars($breedingcage['pi_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Cross</th>
                        <td><?= htmlspecialchars($breedingcage['cross']); ?></td>
                    </tr>
                    <tr>
                        <th>IACUC</th>
                        <td><?= htmlspecialchars($breedingcage['iacuc']); ?></td>
                    </tr>
                    <tr>
                        <th>User</th>
                        <td><?= htmlspecialchars($breedingcage['user']); ?></td>
                    </tr>
                    <tr>
                        <th>Male ID</th>
                        <td><?= htmlspecialchars($breedingcage['male_id']); ?></td>
                    </tr>
                    <tr>
                        <th>Male DOB</th>
                        <td><?= htmlspecialchars($breedingcage['male_dob']); ?></td>
                    </tr>
                    <tr>
                        <th>Female ID</th>
                        <td><?= htmlspecialchars($breedingcage['female_id']); ?></td>
                    </tr>
                    <tr>
                        <th>Female DOB</th>
                        <td><?= htmlspecialchars($breedingcage['female_dob']); ?></td>
                    </tr>
                    <tr>
                        <th>Remarks</th>
                        <td><?= htmlspecialchars($breedingcage['remarks']); ?></td>
                    </tr>
                </table>

                <!-- Separator -->
                <hr class="mt-4 mb-4" style="border-top: 3px solid #000;">

                <!-- Display Files Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Manage Files</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    while ($file = $files->fetch_assoc()) {
                                        $file_path = htmlspecialchars($file['file_path']);
                                        $file_name = htmlspecialchars($file['file_name']);
                                        $file_id = intval($file['id']);

                                        echo "<tr>";
                                        echo "<td>$file_name</td>";
                                        echo "<td><a href='$file_path' download='$file_name' class='btn btn-sm btn-outline-primary'><i class='fas fa-cloud-download-alt'></i></a></td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <br>
                <?php
                    $_GET['view'] = 'yes';
                    include 'bcltr_dash.php';
                ?>
            </div>
        </div>

        <!-- Note App Highlight -->
        <div class="note-app-container">
            <?php include 'nt_app.php'; ?>
        </div>
    </div>

    <br>
    <?php include 'footer.php'; ?>

</body>

</html>
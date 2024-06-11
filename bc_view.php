<?php

/**
 * View Breeding Cage Details
 *
 * This script displays detailed information about a specific breeding cage identified by its cage ID.
 * It retrieves data from the database, including basic information, associated files, and litter details.
 * The script ensures that only logged-in users can access the page and provides options for editing, printing, and viewing a QR code.
 *
 * Author: [Your Name]
 * Date: [Date]
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php with the current URL for redirection after login
if (!isset($_SESSION['username'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit; // Exit to ensure no further code is executed
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the breeding cage record with the specified ID
    $query = "SELECT bc.*, pi.initials AS pi_initials, pi.name AS pi_name 
              FROM bc_basic bc 
              LEFT JOIN users pi ON bc.pi_name = pi.id 
              WHERE bc.cage_id = '$id'";
    $result = mysqli_query($con, $query);

    // Fetch files associated with the specified cage ID
    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

    // Fetch the breeding cage litter records with the specified ID
    $query3 = "SELECT * FROM bc_litter WHERE `cage_id` = '$id'";
    $litters = mysqli_query($con, $query3);

    // Check if the breeding cage record exists
    if (mysqli_num_rows($result) === 1) {
        $breedingcage = mysqli_fetch_assoc($result);

        // If PI name is null, re-query the bc_basic table without the join
        if (is_null($breedingcage['pi_name'])) {
            $queryBasic = "SELECT * FROM bc_basic WHERE `cage_id` = '$id'";
            $resultBasic = mysqli_query($con, $queryBasic);

            if (mysqli_num_rows($resultBasic) === 1) {
                $breedingcage = mysqli_fetch_assoc($resultBasic);
                $breedingcage['pi_initials'] = 'NA'; // Set empty initials
                $breedingcage['pi_name'] = 'NA'; // Set empty PI name
            } else {
                // If the re-query also fails, set an error message and redirect to the dashboard
                $_SESSION['message'] = 'Error fetching the cage details.';
                header("Location: bc_dash.php");
                exit();
            }
        }
    } else {
        // If the record does not exist, set an error message and redirect to the dashboard
        $_SESSION['message'] = 'Invalid ID.';
        header("Location: bc_dash.php");
        exit();
    }
} else {
    // If the ID parameter is missing, set an error message and redirect to the dashboard
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: bc_dash.php");
    exit();
}

function getUserDetailsByIds($con, $userIds)
{
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $query = "SELECT id, initials, name FROM users WHERE id IN ($placeholders)";
    $stmt = $con->prepare($query);
    $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
    $stmt->execute();
    $result = $stmt->get_result();
    $userDetails = [];
    while ($row = $result->fetch_assoc()) {
        $userDetails[$row['id']] = htmlspecialchars($row['initials'] . ' [' . $row['name'] . ']');
    }
    $stmt->close();
    return $userDetails;
}

// Explode the user IDs if they are comma-separated
$userIds = array_map('intval', explode(',', $breedingcage['user']));

// Fetch the user details based on IDs
$userDetails = getUserDetailsByIds($con, $userIds);

// Prepare a string to display user details
$userDisplay = [];
foreach ($userIds as $userId) {
    if (isset($userDetails[$userId])) {
        $userDisplay[] = $userDetails[$userId];
    } else {
        $userDisplay[] = htmlspecialchars($userId);
    }
}
$userDisplayString = implode(', ', $userDisplay);

// Include the header file
require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <title>View Breeding Cage | <?php echo htmlspecialchars($labName); ?></title>

    <script>
        // Function to display a QR code popup for the cage
        function showQrCodePopup(cageId) {
            var popup = window.open("", "QR Code for Cage " + cageId, "width=400,height=400");
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://myvivarium.online/bc_view.php?id=' + cageId;
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
            popup.document.write(htmlContent);
            popup.document.close();
        }

        // Function to navigate back to the previous page
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
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .table-wrapper th:nth-child(1),
        .table-wrapper td:nth-child(1) {
            width: 30%;
        }

        .table-wrapper th:nth-child(2),
        .table-wrapper td:nth-child(2) {
            width: 70%;
        }

        .remarks-column {
            max-width: 400px;
            word-wrap: break-word;
            overflow-wrap: break-word;
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

        .btn-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .btn-icon i {
            font-size: 16px;
            margin: 0;
        }

        @media (max-width: 768px) {

            .table-wrapper th,
            .table-wrapper td {
                padding: 12px 8px;
            }

            .table-wrapper th,
            .table-wrapper td {
                text-align: center;
            }
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
                    <a href="javascript:void(0);" onclick="window.print()" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Print Cage">
                        <i class="fas fa-print"></i>
                    </a>
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
                        <td><?= htmlspecialchars($breedingcage['pi_initials'] . ' [' . $breedingcage['pi_name'] . ']'); ?></td>
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
                        <td><?= $userDisplayString; ?></td>
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
                        <td class="remarks-column"><?= htmlspecialchars($breedingcage['remarks']); ?></td>
                    </tr>
                </table>

                <!-- Separator -->
                <hr class="mt-4 mb-4" style="border-top: 3px solid #000;">

                <!-- Display Files Section -->
                <div class="card mt-4">

                    <div class="card-header">
                        <h4>Manage Files</h4>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($file = $files->fetch_assoc()) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($file['file_name']); ?></td>
                                        <td><a href="<?= htmlspecialchars($file['file_path']); ?>" download="<?= htmlspecialchars($file['file_name']); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-cloud-download-alt"></i></a></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>


                </div>

                <!-- Litter Details Section -->
                <div class="card mt-4">

                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Litter Details - <?= htmlspecialchars($id) ?>
                        </h4>
                    </div>

                    <?php while ($litter = mysqli_fetch_assoc($litters)) : ?>
                        <div class="table-wrapper">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th>DOM</th>
                                        <td><?= htmlspecialchars($litter['dom'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Litter DOB</th>
                                        <td><?= htmlspecialchars($litter['litter_dob'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Pups Alive</th>
                                        <td><?= htmlspecialchars($litter['pups_alive'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Pups Dead</th>
                                        <td><?= htmlspecialchars($litter['pups_dead'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Pups Male</th>
                                        <td><?= htmlspecialchars($litter['pups_male'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Pups Female</th>
                                        <td><?= htmlspecialchars($litter['pups_female'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Remarks</th>
                                        <td class="remarks-column"><?= htmlspecialchars($litter['remarks'] ?? ''); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endwhile; ?>

                </div>

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
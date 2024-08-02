<?php

/**
 * View Breeding Cage 
 *
 * This script displays detailed information about a specific breeding cage identified by its cage ID.
 * It retrieves data from the database, including basic information, associated files, and litter details.
 * The script ensures that only logged-in users can access the page and provides options for editing, printing, and viewing a QR code.
 *
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

// Query to get lab data (URL) from the settings table
$labQuery = "SELECT value FROM settings WHERE name = 'url' LIMIT 1";
$labResult = mysqli_query($con, $labQuery);

// Default value if the query fails or returns no result
$url = "";
if ($row = mysqli_fetch_assoc($labResult)) {
    $url = $row['value'];
}

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the breeding cage record with the specified ID
    $query = "SELECT b.*, c.remarks AS remarks, pi.initials AS pi_initials, pi.name AS pi_name
          FROM breeding b
          LEFT JOIN cages c ON b.cage_id = c.cage_id
          LEFT JOIN users pi ON c.pi_name = pi.id
          WHERE b.cage_id = '$id'";
    $result = mysqli_query($con, $query);

    // Fetch files associated with the specified cage ID
    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

    // Fetch the breeding cage litter records with the specified ID
    $query3 = "SELECT * FROM litters WHERE `cage_id` = '$id'";
    $litters = mysqli_query($con, $query3);

    // Check if the breeding cage record exists
    if (mysqli_num_rows($result) === 1) {
        $breedingcage = mysqli_fetch_assoc($result);

        // Fetch IACUC codes associated with the cage
        $iacucQuery = "SELECT ci.iacuc_id, i.file_url 
                        FROM cage_iacuc ci 
                        LEFT JOIN iacuc i ON ci.iacuc_id = i.iacuc_id
                        WHERE ci.cage_id = '$id'";
        $iacucResult = mysqli_query($con, $iacucQuery);
        $iacucLinks = [];
        while ($row = mysqli_fetch_assoc($iacucResult)) {
            if (!empty($row['file_url'])) {
                $iacucLinks[] = "<a href='" . htmlspecialchars($row['file_url']) . "' target='_blank'>" . htmlspecialchars($row['iacuc_id']) . "</a>";
            } else {
                $iacucLinks[] = htmlspecialchars($row['iacuc_id']);
            }
        }
        $iacucDisplayString = implode(', ', $iacucLinks);
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

// Fetch user IDs associated with the cage
$userIdsQuery = "SELECT cu.user_id 
                 FROM cage_users cu 
                 WHERE cu.cage_id = '$id'";
$userIdsResult = mysqli_query($con, $userIdsQuery);
$userIds = [];
while ($row = mysqli_fetch_assoc($userIdsResult)) {
    $userIds[] = $row['user_id'];
}

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

// Fetch the maintenance logs for the current cage
$maintenanceQuery = "
    SELECT m.timestamp, u.name AS user_name, m.comments 
    FROM maintenance m
    JOIN users u ON m.user_id = u.id
    WHERE m.cage_id = ?
    ORDER BY m.timestamp DESC";

$stmtMaintenance = $con->prepare($maintenanceQuery);
$stmtMaintenance->bind_param("s", $id); // Assuming $id holds the current cage_id
$stmtMaintenance->execute();
$maintenanceLogs = $stmtMaintenance->get_result();

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
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://' + $url + '/bc_view.php?id=' + cageId;
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
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page') || 1;
            const search = urlParams.get('search') || '';
            window.location.href = 'bc_dash.php?page=' + page + '&search=' + encodeURIComponent(search);
        }
    </script>

    <style>
        body {
            background: none !important;
            background-color: transparent !important;
        }

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

    <div class="container content mt-4">
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
                    <!-- Button to mange tasks for the cage -->
                    <a href="manage_tasks.php?id=<?= rawurlencode($breedingcage['cage_id']); ?>" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Manage Tasks">
                        <i class="fas fa-tasks"></i>
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
                        <td><?= $iacucDisplayString; ?></td>
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


                <div class="card mt-4">
                    <div class="card-header d-flex flex-column flex-md-row justify-content-between">
                        <h4>Maintenance Log for Cage ID: <?= htmlspecialchars($id ?? 'Unknown'); ?></h4>
                        <div class="action-icons mt-3 mt-md-0">
                            <!-- Maintenance button with tooltip -->
                            <a href="maintenance.php?from=bc_dash" class="btn btn-warning btn-icon" data-toggle="tooltip" data-placement="top" title="Add Maintenance Record">
                                <i class="fas fa-wrench"></i>
                            </a>
                            <a href="bc_edit.php?id=<?= rawurlencode($breedingcage['cage_id']); ?>" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Edit Cage">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($maintenanceLogs->num_rows > 0) : ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 25%;">Date</th>
                                            <th style="width: 25%;">User</th>
                                            <th style="width: 50%;">Comment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($log = $maintenanceLogs->fetch_assoc()) : ?>
                                            <tr>
                                                <td style="width: 25%;"><?= htmlspecialchars($log['timestamp'] ?? ''); ?></td>
                                                <td style="width: 25%;"><?= htmlspecialchars($log['user_name'] ?? 'Unknown'); ?></td>
                                                <td style="width: 50%;"><?= htmlspecialchars($log['comments'] ?? 'No comment'); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <p>No maintenance records found for this cage.</p>
                        <?php endif; ?>
                    </div>
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
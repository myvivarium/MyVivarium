<?php

/**
 * View Holding Cage
 * 
 * This script displays detailed information about a specific holding cage, including related files and notes. 
 * It also provides options to view, edit, print the cage information, and generate a QR code for the cage.
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

    // Fetch the holding cage record with the specified ID
    $query = "SELECT hc.*, pi.initials AS pi_initials, pi.name AS pi_name, s.str_name, s.str_url 
                FROM hc_basic hc 
                LEFT JOIN users pi ON hc.pi_name = pi.id 
                LEFT JOIN strain s ON hc.strain = s.str_id
                WHERE hc.cage_id = '$id'";
    $result = mysqli_query($con, $query);

    // Fetch files related to the cage ID
    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

    // Check if the record exists
    if (mysqli_num_rows($result) === 1) {
        $holdingcage = mysqli_fetch_assoc($result);

        // Handle null or unmatched strain by setting default values
        if (is_null($holdingcage['str_name']) || empty($holdingcage['str_name'])) {
            $holdingcage['str_name'] = 'Unknown Strain';
            $holdingcage['str_url'] = '#'; // Set a placeholder or default URL
        }

        // Handle null or unmatched PI name by re-querying the hc_basic table without the join
        if (is_null($holdingcage['pi_name'])) {
            $queryBasic = "SELECT * FROM hc_basic WHERE `cage_id` = '$id'";
            $resultBasic = mysqli_query($con, $queryBasic);

            if (mysqli_num_rows($resultBasic) === 1) {
                $holdingcage = mysqli_fetch_assoc($resultBasic);
                $holdingcage['pi_initials'] = 'NA'; // Set default initials
                $holdingcage['pi_name'] = 'NA'; // Set default PI name
            } else {
                // If the re-query also fails, set an error message and redirect to the dashboard
                $_SESSION['message'] = 'Error fetching the cage details.';
                header("Location: hc_dash.php");
                exit();
            }
        }

        // Fetch the mouse data related to this cage
        $mouseQuery = "SELECT * FROM mouse WHERE cage_id = '$id'";
        $mouseResult = mysqli_query($con, $mouseQuery);
        $mice = mysqli_fetch_all($mouseResult, MYSQLI_ASSOC);
    } else {
        // If no record exists, set an error message and redirect to the dashboard
        $_SESSION['message'] = 'Invalid ID.';
        header("Location: hc_dash.php");
        exit();
    }
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: hc_dash.php");
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
$userIds = array_map('intval', explode(',', $holdingcage['user']));

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
    <title>View Holding Cage | <?php echo htmlspecialchars($labName); ?></title>

    <script>
        // Function to show QR code popup for the cage
        function showQrCodePopup(cageId) {
            // Create the popup window
            var popup = window.open("", "QR Code for Cage " + cageId, "width=400,height=400");

            // URL to generate the QR code image
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://' + <?php echo json_encode($url); ?> + '/hc_view.php?id=' + cageId;

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

        // Function to go back to the previous page
        function goBack() {
            window.history.back();
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
    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div class="container content mt-4">
        <div class="card">
            <div class="card-header">
                <h4>View Holding Cage <?= htmlspecialchars($holdingcage['cage_id']); ?></h4>
                <div class="action-buttons">
                    <!-- Button to go back to the previous page -->
                    <a href="javascript:void(0);" onclick="goBack()" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Go Back">
                        <i class="fas fa-arrow-circle-left"></i>
                    </a>
                    <!-- Button to edit the cage -->
                    <a href="hc_edit.php?id=<?= rawurlencode($holdingcage['cage_id']); ?>" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Edit Cage">
                        <i class="fas fa-edit"></i>
                    </a>
                    <!-- Button to mange tasks for the cage -->
                    <a href="manage_tasks.php?id=<?= rawurlencode($holdingcage['cage_id']); ?>" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Manage Tasks">
                        <i class="fas fa-tasks"></i>
                    </a>
                    <!-- Button to show QR code for the cage -->
                    <a href="javascript:void(0);" onclick="showQrCodePopup('<?= rawurlencode($holdingcage['cage_id']); ?>')" class="btn btn-success btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="QR Code">
                        <i class="fas fa-qrcode"></i>
                    </a>
                    <!-- Button to print the cage details -->
                    <a href="javascript:void(0);" onclick="window.print()" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Print Cage">
                        <i class="fas fa-print"></i>
                    </a>
                </div>
            </div>
            <br>
            <div class="table-wrapper">
                <!-- Table to display holding cage details -->
                <table class="table table-bordered">
                    <tr>
                        <th>Cage #:</th>
                        <td><?= htmlspecialchars($holdingcage['cage_id']); ?></td>
                    </tr>
                    <tr>
                        <th>PI Name</th>
                        <td><?= htmlspecialchars($holdingcage['pi_initials'] . ' [' . $holdingcage['pi_name'] . ']'); ?></td>
                    </tr>
                    <tr>
                        <th>Strain</th>
                        <td>
                            <a href="<?= htmlspecialchars($holdingcage['str_url']); ?>" target="_blank">
                                <?= htmlspecialchars($holdingcage['strain']); ?> | <?= htmlspecialchars($holdingcage['str_name']); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>IACUC</th>
                        <td><?= htmlspecialchars($holdingcage['iacuc']); ?></td>
                    </tr>
                    <tr>
                        <th>User</th>
                        <td><?= $userDisplayString; ?></td>
                    </tr>
                    <tr>
                        <th>Qty</th>
                        <td><?= htmlspecialchars($holdingcage['qty']); ?></td>
                    </tr>
                    <tr>
                        <th>DOB</th>
                        <td><?= htmlspecialchars($holdingcage['dob']); ?></td>
                    </tr>
                    <tr>
                        <th>Sex</th>
                        <td><?= htmlspecialchars($holdingcage['sex']); ?></td>
                    </tr>
                    <tr>
                        <th>Parent Cage</th>
                        <td><?= htmlspecialchars($holdingcage['parent_cg']); ?></td>
                    </tr>
                    <tr>
                        <th>Remarks</th>
                        <td><?= htmlspecialchars($holdingcage['remarks']); ?></td>
                    </tr>
                </table>

                <!-- Display details for each mouse in the cage -->
                <?php if (!empty($mice)) : ?>
                    <?php foreach ($mice as $index => $mouse) : ?>
                        <h4>Mouse #<?= $index + 1; ?></h4>
                        <table class="table table-bordered">
                            <tr>
                                <th>Mouse ID</th>
                                <th>Genotype</th>
                                <th>Notes</th>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars($mouse['mouse_id']); ?></td>
                                <td><?= htmlspecialchars($mouse['genotype']); ?></td>
                                <td><?= htmlspecialchars($mouse['notes']); ?></td>
                            </tr>
                        </table>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No mice data available for this cage.</p>
                <?php endif; ?>
            </div>

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
                                // Loop to display files related to the cage
                                while ($file = $files->fetch_assoc()) {
                                    $file_path = htmlspecialchars($file['file_path']);
                                    $file_name = htmlspecialchars($file['file_name']);
                                    $file_id = intval($file['id']);

                                    echo "<tr>";
                                    echo "<td>$file_name</td>";
                                    echo "<td><a href='$file_path' download='$file_name' class='btn btn-sm btn-outline-primary'> <i class='fas fa-cloud-download-alt fa-sm'></i></a></td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <br>
        </div>

        <!-- Note App Highlight -->
        <div class="note-app-container">
            <?php include 'nt_app.php'; ?> <!-- Include the note application file -->
        </div>
    </div>

    <br>
    <?php include 'footer.php'; ?> <!-- Include the footer file -->

</body>

</html>
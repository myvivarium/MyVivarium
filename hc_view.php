<?php

/**
 * View Holding Cage
 * 
 * This script displays detailed information about a specific holding cage, including related files and notes. 
 * It also provides options to view, edit, print the cage information, and generate a QR code for the cage.
 * 
 */

session_start();
require 'dbcon.php';

// Check if the user is not logged in
if (!isset($_SESSION['username'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get lab URL
$labQuery = "SELECT value FROM settings WHERE name = 'url' LIMIT 1";
$labResult = mysqli_query($con, $labQuery);
$url = "";
if ($row = mysqli_fetch_assoc($labResult)) {
    $url = $row['value'];
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "SELECT h.*, pi.initials AS pi_initials, pi.name AS pi_name, s.*, c.quantity, c.remarks
              FROM holding h
              LEFT JOIN cages c ON h.cage_id = c.cage_id
              LEFT JOIN users pi ON c.pi_name = pi.id 
              LEFT JOIN strains s ON h.strain = s.str_id
              WHERE h.cage_id = '$id'";
    $result = mysqli_query($con, $query);

    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

    if (mysqli_num_rows($result) === 1) {
        $holdingcage = mysqli_fetch_assoc($result);

        if (is_null($holdingcage['str_name']) || empty($holdingcage['str_name'])) {
            $holdingcage['str_id'] = 'NA';
            $holdingcage['str_name'] = 'Unknown Strain';
            $holdingcage['str_url'] = '#';
        }

        if (is_null($holdingcage['pi_name'])) {
            $queryBasic = "SELECT * FROM holding WHERE `cage_id` = '$id'";
            $resultBasic = mysqli_query($con, $queryBasic);
            if (mysqli_num_rows($resultBasic) === 1) {
                $holdingcage = mysqli_fetch_assoc($resultBasic);
                $holdingcage['pi_initials'] = 'NA';
                $holdingcage['pi_name'] = 'NA';
            } else {
                $_SESSION['message'] = 'Error fetching the cage details.';
                header("Location: hc_dash.php");
                exit();
            }
        }

        $iacucQuery = "SELECT GROUP_CONCAT(iacuc_id SEPARATOR ', ') AS iacuc_ids FROM cage_iacuc WHERE cage_id = '$id'";
        $iacucResult = mysqli_query($con, $iacucQuery);
        $iacucRow = mysqli_fetch_assoc($iacucResult);
        $iacucCodes = [];
        if (!empty($iacucRow['iacuc_ids'])) {
            $iacucCodes = explode(',', $iacucRow['iacuc_ids']);
        }

        $iacucLinks = [];
        foreach ($iacucCodes as $iacucCode) {
            $iacucCode = trim($iacucCode);
            $iacucQuery = "SELECT file_url FROM iacuc WHERE iacuc_id = '$iacucCode'";
            $iacucResult = mysqli_query($con, $iacucQuery);
            if ($iacucResult && mysqli_num_rows($iacucResult) === 1) {
                $iacucRow = mysqli_fetch_assoc($iacucResult);
                if (!empty($iacucRow['file_url'])) {
                    $iacucLinks[] = "<a href='" . htmlspecialchars($iacucRow['file_url']) . "' target='_blank'>" . htmlspecialchars($iacucCode) . "</a>";
                } else {
                    $iacucLinks[] = htmlspecialchars($iacucCode);
                }
            } else {
                $iacucLinks[] = htmlspecialchars($iacucCode);
            }
        }

        $iacucDisplayString = implode(', ', $iacucLinks);

        $userQuery = "SELECT user_id FROM cage_users WHERE cage_id = '$id'";
        $userResult = mysqli_query($con, $userQuery);
        $userIds = [];
        while ($userRow = mysqli_fetch_assoc($userResult)) {
            $userIds[] = $userRow['user_id'];
        }

        $userDetails = getUserDetailsByIds($con, $userIds);

        $userDisplay = [];
        foreach ($userIds as $userId) {
            if (isset($userDetails[$userId])) {
                $userDisplay[] = $userDetails[$userId];
            } else {
                $userDisplay[] = htmlspecialchars($userId);
            }
        }
        $userDisplayString = implode(', ', $userDisplay);

        $mouseQuery = "SELECT * FROM mice WHERE cage_id = '$id'";
        $mouseResult = mysqli_query($con, $mouseQuery);
        $mice = mysqli_fetch_all($mouseResult, MYSQLI_ASSOC);
    } else {
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

require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Holding Cage | <?php echo htmlspecialchars($labName); ?></title>

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

        .popup-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border: 2px solid #000;
            z-index: 1000;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
            width: 80%;
            max-width: 800px;
        }

        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div class="container content mt-4">
        <div class="card">
            <div class="card-header">
                <h4>View Holding Cage <?= htmlspecialchars($holdingcage['cage_id']); ?></h4>
                <div class="action-buttons">
                    <a href="javascript:void(0);" onclick="goBack()" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Go Back">
                        <i class="fas fa-arrow-circle-left"></i>
                    </a>
                    <a href="hc_edit.php?id=<?= rawurlencode($holdingcage['cage_id']); ?>" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Edit Cage">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="manage_tasks.php?id=<?= rawurlencode($holdingcage['cage_id']); ?>" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Manage Tasks">
                        <i class="fas fa-tasks"></i>
                    </a>
                    <a href="javascript:void(0);" onclick="showQrCodePopup('<?= rawurlencode($holdingcage['cage_id']); ?>')" class="btn btn-success btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="QR Code">
                        <i class="fas fa-qrcode"></i>
                    </a>
                    <a href="javascript:void(0);" onclick="window.print()" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Print Cage">
                        <i class="fas fa-print"></i>
                    </a>
                </div>
            </div>
            <br>
            <div class="table-wrapper">
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
                            <a href="javascript:void(0);" onclick="viewStrainDetails(
                                '<?= htmlspecialchars($holdingcage['str_id'] ?? 'NA'); ?>', 
                                '<?= htmlspecialchars($holdingcage['str_name'] ?? 'Unknown Name'); ?>', 
                                '<?= htmlspecialchars($holdingcage['str_aka'] ?? ''); ?>', 
                                '<?= htmlspecialchars($holdingcage['str_url'] ?? '#'); ?>', 
                                '<?= htmlspecialchars($holdingcage['str_rrid'] ?? ''); ?>', 
                                `<?= htmlspecialchars($holdingcage['str_notes'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE) ?>`)">
                                <?= htmlspecialchars($holdingcage['str_id'] ?? 'NA'); ?> | <?= htmlspecialchars($holdingcage['str_name'] ?? 'Unknown Name'); ?>
                            </a>
                        </td>
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
                        <th>Qty</th>
                        <td><?= htmlspecialchars($holdingcage['quantity']); ?></td>
                    </tr>
                    <tr>
                        <th>DOB</th>
                        <td><?= htmlspecialchars($holdingcage['dob']); ?></td>
                    </tr>
                    <tr>
                        <th>Sex</th>
                        <td><?= htmlspecialchars(ucfirst($holdingcage['sex'])); ?></td>
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

            <hr class="mt-4 mb-4" style="border-top: 3px solid #000;">

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

            <div class="card mt-4">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between">
                    <h4>Maintenance Log for Cage ID: <?= htmlspecialchars($id ?? 'Unknown'); ?></h4>
                    <div class="action-icons mt-3 mt-md-0">
                        <!-- Maintenance button with tooltip -->
                        <a href="maintenance.php?from=hc_dash" class="btn btn-warning btn-icon" data-toggle="tooltip" data-placement="top" title="Add Maintenance Record">
                            <i class="fas fa-wrench"></i>
                        </a>
                        <a href="hc_edit.php?id=<?= rawurlencode($holdingcage['cage_id']); ?>" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Edit Cage">
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

        <div class="note-app-container">
            <?php include 'nt_app.php'; ?>
        </div>
    </div>

    <br>
    <?php include 'footer.php'; ?>

    <!-- Popup form for viewing strain details -->
    <div class="popup-overlay" id="viewPopupOverlay"></div>
    <div class="popup-form" id="viewPopupForm">
        <h4 id="viewFormTitle">Strain Details</h4>
        <div class="form-group">
            <strong for="view_strain_id">Strain ID:</strong>
            <p id="view_strain_id"></p>
        </div>
        <div class="form-group">
            <strong for="view_strain_name">Strain Name:</strong>
            <p id="view_strain_name"></p>
        </div>
        <div class="form-group">
            <strong for="view_strain_aka">Common Names:</strong>
            <p id="view_strain_aka"></p>
        </div>
        <div class="form-group">
            <strong for="view_strain_url">Strain URL:</strong>
            <p><a href="#" id="view_strain_url" target="_blank"></a></p>
        </div>
        <div class="form-group">
            <strong for="view_strain_rrid">Strain RRID:</strong>
            <p id="view_strain_rrid"></p>
        </div>
        <div class="form-group">
            <strong for="view_strain_notes">Notes:</strong>
            <p id="view_strain_notes"></p>
        </div>
        <div class="form-buttons">
            <button type="button" class="btn btn-secondary" onclick="closeViewForm()">Close</button>
        </div>
    </div>

    <script>
        function showQrCodePopup(cageId) {
            var popup = window.open("", "QR Code for Cage " + cageId, "width=400,height=400");
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://' + <?php echo json_encode($url); ?> + '/hc_view.php?id=' + encodeURIComponent(cageId);

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

        function goBack() {
            window.history.back();
        }

        function viewStrainDetails(id, name, aka, url, rrid, notes) {
            document.getElementById('view_strain_id').innerText = id;
            document.getElementById('view_strain_name').innerText = name;
            document.getElementById('view_strain_aka').innerText = aka;
            document.getElementById('view_strain_url').innerText = url;
            document.getElementById('view_strain_rrid').innerText = rrid;
            document.getElementById('view_strain_notes').innerHTML = notes.replace(/\n/g, '<br>');
            document.getElementById('viewPopupOverlay').style.display = 'block';
            document.getElementById('viewPopupForm').style.display = 'block';
            document.getElementById('view_strain_url').href = url; // Set the href for the URL link
        }

        function closeViewForm() {
            document.getElementById('viewPopupOverlay').style.display = 'none';
            document.getElementById('viewPopupForm').style.display = 'none';
        }
    </script>
</body>

</html>
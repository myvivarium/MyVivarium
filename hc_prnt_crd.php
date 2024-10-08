<?php

/**
 * Printable Holding Cage Cards
 * 
 * This script generates a printable view of holding cage cards. Each card includes detailed information 
 * about a holding cage, such as PI name, strain, IACUC, user, quantity, DOB, sex, parent cage, and mouse 
 * details. The script handles multiple cage IDs passed via URL parameters, generates QR codes for each cage, 
 * and arranges the cards in a 2x2 grid layout suitable for printing.
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
    $ids = explode(',', $_GET['id']); // Split the ID parameter into an array of IDs
    $holdingcages = [];

    foreach ($ids as $id) {
        // Fetch the holding cage record with the specified ID
        $query = "SELECT h.*, pi.name AS pi_name, c.quantity as qty, h.dob, h.sex, h.parent_cg, s.str_name 
                  FROM holding h
                  LEFT JOIN cages c ON h.cage_id = c.cage_id
                  LEFT JOIN users pi ON c.pi_name = pi.id 
                  LEFT JOIN strains s ON h.strain = s.str_id
                  WHERE h.cage_id = '$id'";
        $result = mysqli_query($con, $query);

        // If a valid record is found, add it to the holdingcages array
        if (mysqli_num_rows($result) === 1) {
            $holdingcage = mysqli_fetch_assoc($result);

            // Fetch mouse data for this cage, limit to first 5 records
            $mouseQuery = "SELECT mouse_id, genotype 
                           FROM mice 
                           WHERE cage_id = '$id' 
                           LIMIT 5";
            $mouseResult = mysqli_query($con, $mouseQuery);
            $mouseData = mysqli_fetch_all($mouseResult, MYSQLI_ASSOC);

            // Fetch IACUC data
            $iacucQuery = "SELECT GROUP_CONCAT(i.iacuc_id SEPARATOR ', ') AS iacuc_ids 
                           FROM cage_iacuc ci
                           JOIN iacuc i ON ci.iacuc_id = i.iacuc_id
                           WHERE ci.cage_id = '$id'";
            $iacucResult = mysqli_query($con, $iacucQuery);
            $iacucRow = mysqli_fetch_assoc($iacucResult);
            $holdingcage['iacuc'] = $iacucRow['iacuc_ids'] ?? 'N/A';

            // Fetch user initials for this cage
            $userQuery = "SELECT u.initials
                          FROM cage_users cu
                          JOIN users u ON cu.user_id = u.id
                          WHERE cu.cage_id = '$id'";
            $userResult = mysqli_query($con, $userQuery);
            $userInitials = [];
            while ($userRow = mysqli_fetch_assoc($userResult)) {
                $userInitials[] = htmlspecialchars($userRow['initials']);
            }
            $holdingcage['user_initials'] = implode(', ', $userInitials);

            // Append the mouse data to the holding cage record
            $holdingcage['mice'] = $mouseData;

            // Add the holding cage with mouse data to the array
            $holdingcages[] = $holdingcage;
        } else {
            // Set an error message for an invalid ID and redirect to the dashboard
            $_SESSION['message'] = "Invalid ID: $id";
            header("Location: hc_dash.php");
            exit();
        }
    }
} else {
    // Set an error message if the ID parameter is missing and redirect to the dashboard
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: hc_dash.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags for character encoding and viewport settings -->
    <meta charset="utf-8">
    <title>Printable 2x2 Card Table</title>
    <style>
        /* Set up page size and margins for printing */
        @page {
            size: letter landscape;
            margin: 0;
            padding: 0;
        }

        /* Print styles */
        @media print {
            body {
                margin: 0;
                color: #000;
            }
        }

        /* General styles for body and HTML elements */
        body,
        html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
            display: grid;
            place-items: center;
        }

        span {
            font-size: 8pt;
            padding: 0px;
            line-height: 1;
            display: inline-block;
        }

        table {
            box-sizing: border-box;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
            border-spacing: 0;
        }

        table#cageA tr td,
        table#cageB tr td {
            border: 1px solid black;
            box-sizing: border-box;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
            border-spacing: 0;
        }

        table#cageB tr:first-child td {
            border-top: none;
        }
    </style>
</head>

<body>
    <!-- Main table to hold the 2x2 card layout -->
    <table style="width: 10in; height: 6in; border-collapse: collapse; border: 1px dashed #D3D3D3;">
        <?php foreach ($holdingcages as $index => $holdingcage) : ?>

            <!-- Start a new row for every two cages -->
            <?php if ($index % 2 === 0) : ?>
                <tr style="height: 3in; border: 1px dashed #D3D3D3; vertical-align:top;">
                <?php endif; ?>

                <!-- Each cell contains a holding cage card -->
                <td style="width: 5in; border: 1px dashed #D3D3D3;">
                    <table border="1" style="width: 5in; height: 1.5in;" id="cageA">
                        <tr>
                            <td colspan="3" style="width: 100%; text-align:center;">
                                <span style="font-weight: bold; font-size: 10pt; text-transform: uppercase; padding:3px;">
                                    Holding Cage - # <?= $holdingcage["cage_id"] ?> </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:40%;">
                                <span style="font-weight: bold; padding:3px; text-transform: uppercase;">PI Name:</span>
                                <span><?= htmlspecialchars($holdingcage["pi_name"]); ?></span>
                            </td>
                            <td style="width:40%;">
                                <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Strain:</span>
                                <span><?= htmlspecialchars($holdingcage["strain"]); ?></span>
                            </td>
                            <td rowspan="4" style="width:20%; text-align:center;">
                                <img src="<?php echo "https://api.qrserver.com/v1/create-qr-code/?size=75x75&data=https://" . $url . "/hc_view.php?id=" . $holdingcage["cage_id"] . "&choe=UTF-8"; ?>" alt="QR Code">
                            </td>
                        </tr>
                        <tr>
                            <td style="width:40%;">
                                <span style="font-weight: bold; padding:3px; text-transform: uppercase;">IACUC:</span>
                                <span><?= htmlspecialchars($holdingcage["iacuc"]); ?></span>
                            </td>
                            <td style="width:40%;">
                                <span style="font-weight: bold; padding:3px; text-transform: uppercase;">User:</span>
                                <span><?= htmlspecialchars($holdingcage['user_initials']); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:40%;">
                                <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Qty:</span>
                                <span><?= htmlspecialchars($holdingcage["qty"]); ?></span>
                            </td>
                            <td style="width:40%;">
                                <span style="font-weight: bold; padding:3px; text-transform: uppercase;">DOB:</span>
                                <span><?= htmlspecialchars($holdingcage["dob"]); ?></span>
                            </td>
                        </tr>
                        <tr style="border-bottom: none;">
                            <td style="width:40%;">
                                <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Sex:</span>
                                <span><?= htmlspecialchars(ucfirst($holdingcage["sex"])); ?></span>
                            </td>
                            <td style="width:40%;">
                                <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Parent Cage:</span>
                                <span><?= htmlspecialchars($holdingcage["parent_cg"]); ?></span>
                            </td>
                        </tr>
                    </table>
                    <table border="1" style="width: 5in; height: 1.5in; border-top: none;" id="cageB">
                        <tr>
                            <td style="width:40%;">
                                <span style="font-weight: bold; padding:3px; text-transform: uppercase; border-top: none; text-align:center;">Mouse ID</span>
                            </td>
                            <td style="width:60%;">
                                <span style="font-weight: bold; padding:3px; text-transform: uppercase; border-top: none; text-align:center;">Genotype</span>
                            </td>
                        </tr>
                        <?php foreach (range(1, 5) as $i) : ?>
                            <tr>
                                <td style="width:40%; padding:3px;">
                                    <span><?= isset($holdingcage['mice'][$i - 1]['mouse_id']) ? htmlspecialchars($holdingcage['mice'][$i - 1]['mouse_id']) : '' ?></span>
                                </td>
                                <td style="width:60%; padding:3px;">
                                    <span><?= isset($holdingcage['mice'][$i - 1]['genotype']) ? htmlspecialchars($holdingcage['mice'][$i - 1]['genotype']) : '' ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </td>

                <!-- Close the row after every two cages or at the end of the list -->
                <?php if ($index % 2 === 1 || $index === count($holdingcages) - 1) : ?>
                </tr>
            <?php endif; ?>

        <?php endforeach; ?>
    </table>
</body>

</html>

<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php with the current URL for redirection after login
if (!isset($_SESSION['name'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit;
}

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the holding cage record with the specified ID
    $query = "SELECT * FROM hc_basic WHERE `cage_id` = '$id'";
    $result = mysqli_query($con, $query);

    // Fetch files related to the cage ID
    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

    // Check if the record exists
    if (mysqli_num_rows($result) === 1) {
        $holdingcage = mysqli_fetch_assoc($result);
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

require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <title>View Holding Cage | <?php echo htmlspecialchars($labName); ?></title>
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
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h4>View Holding Cage <?= htmlspecialchars($holdingcage['cage_id']); ?></h4>
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
                        <td><?= htmlspecialchars($holdingcage['pi_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Strain</th>
                        <td><?= htmlspecialchars($holdingcage['strain']); ?></td>
                    </tr>
                    <tr>
                        <th>IACUC</th>
                        <td><?= htmlspecialchars($holdingcage['iacuc']); ?></td>
                    </tr>
                    <tr>
                        <th>User</th>
                        <td><?= htmlspecialchars($holdingcage['user']); ?></td>
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

                <?php for ($i = 1; $i <= $holdingcage['qty']; $i++): ?>
                    <h4>Mouse #<?= $i; ?></h4>
                    <table class="table table-bordered">
                        <tr>
                            <th>Mouse ID</th>
                            <th>Genotype</th>
                            <th>Notes</th>
                        </tr>
                        <tr>
                            <td><?= htmlspecialchars($holdingcage["mouse_id_$i"]); ?></td>
                            <td><?= htmlspecialchars($holdingcage["genotype_$i"]); ?></td>
                            <td><?= htmlspecialchars($holdingcage["notes_$i"]); ?></td>
                        </tr>
                    </table>
                <?php endfor; ?>
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
            <?php include 'nt_app.php'; ?>
        </div>
    </div>

    <br>
    <?php include 'footer.php'; ?>

</body>

</html>

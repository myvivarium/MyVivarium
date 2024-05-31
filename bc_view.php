<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php with a redirect parameter
if (!isset($_SESSION['name'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit;
}

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

    <style>
        .container {
            max-width: 800px;
            background-color: lightgrey;
            padding: 20px;
            border-radius: 8px;
        }

        .table-wrapper {
            padding: 10px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-wrapper table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-wrapper th,
        .table-wrapper td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .table-wrapper th {
            background-color: #f2f2f2;
        }

        .card {
            margin-top: 20px;
        }

        .btn {
            margin-top: 10px;
        }

        .card-header h4 {
            margin-bottom: 0;
        }

        .table-responsive {
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div class="container mt-4">
        <?php include('message.php'); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>View Breeding Cage <?= htmlspecialchars($breedingcage['cage_id']); ?></h4>
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

                        <!-- Display Files Section -->
                        <br>
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

                        <?php include 'bcltr_dash.php'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="bc_dash.php" class="btn btn-secondary">Go Back</a>
            <button class="btn btn-secondary" onclick="togglePopup()">Add Note</button>
        </div>

        <?php include 'nt_app.php'; ?>
    </div>

    <br>
    <?php include 'footer.php'; ?>

</body>

</html>

<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
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

    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

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
        .table-wrapper {
            padding: 10px 10px 10px 10px;
        }

        .table-wrapper table {
            width: 100%;
            border: 1px solid #000000;
            /* Outer border color */
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-wrapper th,
        .table-wrapper td {
            border: 1px solid gray;
            /* Inner border color */
            padding: 8px;
            text-align: left;
        }

        span {
            font-size: 12pt;
            padding: 0px;
            line-height: 1;
            display: inline-block;
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
                        <h4>View Breeding Cage
                            <?= $breedingcage['cage_id']; ?>
                        </h4>
                    </div>
                    <br>
                    <div class="table-wrapper">
                        <table class="table table-bordered" id="mouseTable">
                            <tr>
                                <th><span>Cage #: </span></th>
                                <td><span>
                                        <?= $breedingcage['cage_id']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>PI Name</span></th>
                                <td><span>
                                        <?= $breedingcage['pi_name']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>Cross</span></th>
                                <td><span>
                                        <?= $breedingcage['cross']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>IACUC</span></th>
                                <td><span>
                                        <?= $breedingcage['iacuc']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>User</span></th>
                                <td><span>
                                        <?= $breedingcage['user']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>Male ID</span></th>
                                <td><span>
                                        <?= $breedingcage['male_id']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>Male DOB</span></th>
                                <td><span>
                                        <?= $breedingcage['male_dob']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>Female ID</span></th>
                                <td><span>
                                        <?= $breedingcage['female_id']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>Female DOB</span></th>
                                <td><span>
                                        <?= $breedingcage['female_dob']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>Remarks</span></th>
                                <td><span>
                                        <?= $breedingcage['remarks']; ?>
                                    </span></td>
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
                                            // Assuming $files is fetched from the database
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
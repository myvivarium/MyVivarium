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

    // Fetch the holdingcage record with the specified ID
    $query = "SELECT * FROM hc_basic WHERE `cage_id` = '$id'";
    $result = mysqli_query($con, $query);

    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

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
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <title>View Holding Cage | <?php echo htmlspecialchars($labName); ?></title>

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
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>View Holding Cage
                            <?= $holdingcage['cage_id']; ?>
                        </h4>
                    </div>
                    <br>
                    <div class="table-wrapper">
                        <table class="table table-bordered" id="mouseTable">
                            <tr>
                                <th><span>Cage #: </span></th>
                                <td><span>
                                        <?= $holdingcage['cage_id']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>PI Name</span></th>
                                <td><span>
                                        <?= $holdingcage['pi_name']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>Strain</span></th>
                                <td><span>
                                        <?= $holdingcage['strain']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>IACUC</span></th>
                                <td><span>
                                        <?= $holdingcage['iacuc']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>User</span></th>
                                <td><span>
                                        <?= $holdingcage['user']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>Qty</span></th>
                                <td><span>
                                        <?= $holdingcage['qty']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>DOB</span></th>
                                <td><span>
                                        <?= $holdingcage['dob']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>Sex</span></th>
                                <td><span>
                                        <?= $holdingcage['sex']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>Parent Cage</span></th>
                                <td><span>
                                        <?= $holdingcage['parent_cg']; ?>
                                    </span></td>
                            </tr>
                            <tr>
                                <th><span>Remarks</span></th>
                                <td><span>
                                        <?= $holdingcage['remarks']; ?>
                                    </span></td>
                            </tr>
                        </table>

                        <h4>Mouse #1</h4>

                        <table class="table table-bordered" id="mouseTable">
                            <tr>
                                <th><span>Mouse ID</span></th>
                                <th><span>Genotype</span></th>
                                <th><span>Notes</span></th>
                            </tr>
                            <tr>
                                <td><span>
                                        <?= $holdingcage['mouse_id_1']; ?>
                                    </span></td>
                                <td><span>
                                        <?= $holdingcage['genotype_1']; ?>
                                    </span></td>
                                <td><span>
                                        <?= $holdingcage['notes_1']; ?>
                                    </span></td>
                            </tr>
                        </table>

                        <h4>Mouse #2</h4>

                        <table class="table table-bordered" id="mouseTable">
                            <tr>
                                <th><span>Mouse ID</span></th>
                                <th><span>Genotype</span></th>
                                <th><span>Notes</span></th>
                            </tr>
                            <tr>
                                <td><span>
                                        <?= $holdingcage['mouse_id_2']; ?>
                                    </span></td>
                                <td><span>
                                        <?= $holdingcage['genotype_2']; ?>
                                    </span></td>
                                <td><span>
                                        <?= $holdingcage['notes_2']; ?>
                                    </span></td>
                            </tr>
                        </table>

                        <h4>Mouse #3</h4>

                        <table class="table table-bordered" id="mouseTable">
                            <tr>
                                <th><span>Mouse ID</span></th>
                                <th><span>Genotype</span></th>
                                <th><span>Notes</span></th>
                            </tr>
                            <tr>
                                <td><span>
                                        <?= $holdingcage['mouse_id_3']; ?>
                                    </span></td>
                                <td><span>
                                        <?= $holdingcage['genotype_3']; ?>
                                    </span></td>
                                <td><span>
                                        <?= $holdingcage['notes_3']; ?>
                                    </span></td>
                            </tr>
                        </table>

                        <h4>Mouse #4</h4>

                        <table class="table table-bordered" id="mouseTable">
                            <tr>
                                <th><span>Mouse ID</span></th>
                                <th><span>Genotype</span></th>
                                <th><span>Notes</span></th>
                            </tr>
                            <tr>
                                <td><span>
                                        <?= $holdingcage['mouse_id_4']; ?>
                                    </span></td>
                                <td><span>
                                        <?= $holdingcage['genotype_4']; ?>
                                    </span></td>
                                <td><span>
                                        <?= $holdingcage['notes_4']; ?>
                                    </span></td>
                            </tr>
                        </table>

                        <h4>Mouse #5</h4>

                        <table class="table table-bordered" id="mouseTable">
                            <tr>
                                <th><span>Mouse ID</span></th>
                                <th><span>Genotype</span></th>
                                <th><span>Notes</span></th>
                            </tr>
                            <tr>
                                <td><span>
                                        <?= $holdingcage['mouse_id_5']; ?>
                                    </span></td>
                                <td><span>
                                        <?= $holdingcage['genotype_5']; ?>
                                    </span></td>
                                <td><span>
                                        <?= $holdingcage['notes_5']; ?>
                                    </span></td>
                            </tr>
                        </table>
                    </div>

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
                                        // Assuming $files is fetched from the database
                                        while ($file = $files->fetch_assoc()) {
                                            $file_path = htmlspecialchars($file['file_path']);
                                            $file_name = htmlspecialchars($file['file_name']);
                                            $file_id = intval($file['id']);

                                            echo "<tr>";
                                            echo "<td>$file_name</td>";
                                            echo "<td>
                                                    <a href='<?= $file_path ?>' download='<?= $file_name ?>' class='btn btn-sm btn-outline-primary'> <i class='fas fa-cloud-download-alt fa-sm'></i></a>
                                                    </td>";
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
            </div>
        </div>

        <div style="text-align: center;">
            <a href="hc_dash.php" class="btn btn-secondary">Go Back</a>
            <button class="btn btn-secondary" onclick="togglePopup()">Add Note</button>
        </div>

        <?php include 'nt_app.php'; ?>
    </div>

    <br>
    <?php include 'footer.php'; ?>

</body>

</html>
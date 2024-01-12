<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the breedingcage record with the specified ID
    $query = "SELECT * FROM bc_basic WHERE `cage_id` = '$id'";
    $result = mysqli_query($con, $query);

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
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Bootstrap JS for Dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <title>View Breeding Cage</title>

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
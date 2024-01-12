<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Fetch the distinct cage IDs from the database
$query = "SELECT DISTINCT `cage_id` FROM hc_basic";
$result = mysqli_query($con, $query);

// Handle the search filter
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = urldecode($_GET['search']); // Decode the search parameter
    $query = "SELECT * FROM hc_basic";
    if (!empty($searchQuery)) {
        $query .= " WHERE `cage_id` LIKE '%$searchQuery%'";
    }
    $result = mysqli_query($con, $query);
}

require 'header.php';
?>

<!-- Start of the HTML -->
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


    <title>Dashboard | Holding Cage</title>

    <style>
        /* General Styles */
        body {
            margin: 0;
            padding: 0;
        }

        /* Table Wrapper Styling */
        .table-wrapper {
            margin-bottom: 50px;
        }

        .table-wrapper table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-wrapper th,
        .table-wrapper td {
            border: 1px solid #ddd;
            /* Lighter border for a more modern look */
            padding: 8px;
            text-align: left;
        }

        /* Button Styling */
        .btn-back,
        .btn-logout {
            padding: 10px 20px;
            border-radius: 30px;
            transition: background-color 0.2s, transform 0.2s;
        }

        .btn-back {
            background-color: #007BFF;
            color: white;
        }

        .btn-back:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .btn-back:active,
        .btn-secondary:active {
            transform: scale(0.95);
        }

        .btn-back.fixed {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .btn-secondary:hover {
            background-color: #FF4500;
        }
    </style>
</head>

<body>

    <div class="container mt-4">
        <?php include('message.php'); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <!-- Holding Cage Header -->
                    <div class="card-header">
                        <h4>Holding Cage Dashboard
                            <a href="hc_addn.php" class="btn btn-primary float-end">Add New Holding Cage</a>
                        </h4>
                    </div>

                    <div class="card-body">
                        <!-- Holding Cage Search Box -->
                        <form method="GET" action="">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Enter cage ID" name="search"
                                    value="<?= htmlspecialchars($searchQuery) ?>">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>

                        <div class="table-wrapper">
                            <table class="table table-bordered" id="mouseTable">
                                <thead>
                                    <th>Cage ID</th>
                                    <th>Strain</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </thead>
                                <tbody>
                                    <?php
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $cageID = $row['cage_id'];
                                        $query = "SELECT * FROM hc_basic WHERE `cage_id` = '$cageID'";
                                        $cageResult = mysqli_query($con, $query);
                                        while ($holdingcage = mysqli_fetch_assoc($cageResult)) {
                                            ?>
                                            <tr>
                                                <td rowspan="<?= mysqli_num_rows($cageResult); ?>">
                                                    <?= $holdingcage['cage_id']; ?>
                                                </td>
                                                <td>
                                                    <?= $holdingcage['strain']; ?>
                                                </td>
                                                <td>
                                                    <?= $holdingcage['remarks']; ?>
                                                </td>
                                                <td>
                                                    <a href="hc_view.php?id=<?= rawurlencode($holdingcage['cage_id']); ?>"
                                                        class="btn btn-primary">View</a>
                                                    <a href="hc_prnt.php?id=<?= rawurlencode($holdingcage['cage_id']); ?>"
                                                        class="btn btn-success">Print</a>
                                                    <a href="hc_edit.php?id=<?= rawurlencode($holdingcage['cage_id']); ?>"
                                                        class="btn btn-secondary">Edit</a>
                                                    <a href="hc_drop.php?id=<?= rawurlencode($holdingcage['cage_id']); ?>"
                                                        class="btn btn-danger">Delete</a>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (isset($_GET['search'])): ?>
                            <div style="text-align: center;">
                                <a href="hc_dash.php" class="btn btn-secondary">Go Back To Holding Cage Dashboard</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>
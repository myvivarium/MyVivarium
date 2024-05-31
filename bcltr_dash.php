<?php
require 'dbcon.php';

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the breedingcage litter record with the specified ID
    $query = "SELECT * FROM bc_litter WHERE `cage_id` = '$id'";
    $result = mysqli_query($con, $query);
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: bc_dash.php");
    exit();
}
?>

<!-- Start of the HTML -->
<!doctype html>
<html lang="en">

<head>
    <!-- Include Bootstrap and Font Awesome for Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        /* General Styles */
        body {
            margin: 0;
            padding: 0;
        }

        /* Main Container Styling */
        .container {
            max-width: 800px;
            background-color: lightgrey;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        /* Table Wrapper Styling */
        .table-wrapper {
            margin-bottom: 50px;
        }

        .table-wrapper table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

    <div class="container">
        <?php include('message.php'); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <!-- Breeding Cage Header -->
                    <div class="card-header">
                        <h4>Litter Details for the Cage <?= htmlspecialchars($id) ?>
                            <a href="bcltr_addn.php?id=<?= rawurlencode($id) ?>" class="btn btn-primary float-end">Add New Litter Data</a>
                        </h4>
                    </div>

                    <div class="card-body">

                        <?php
                        while ($litter = mysqli_fetch_assoc($result)) {
                            ?>
                            <div class="table-wrapper">
                                <table class="table table-bordered" id="mouseTable">
                                    <thead>
                                        <tr>
                                            <th>DOM</th>
                                            <th>Litter DOB</th>
                                            <th>Pups Alive</th>
                                            <th>Pups Dead</th>
                                            <th>Pups Male</th>
                                            <th>Pups Female</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?= htmlspecialchars($litter['dom']); ?></td>
                                            <td><?= htmlspecialchars($litter['litter_dob']); ?></td>
                                            <td><?= htmlspecialchars($litter['pups_alive']); ?></td>
                                            <td><?= htmlspecialchars($litter['pups_dead']); ?></td>
                                            <td><?= htmlspecialchars($litter['pups_male']); ?></td>
                                            <td><?= htmlspecialchars($litter['pups_female']); ?></td>
                                            <td>
                                                <a href="bcltr_edit.php?id=<?= rawurlencode($litter['id']); ?>" class="btn btn-secondary">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="bcltr_drop.php?id=<?= rawurlencode($litter['id']); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?');">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <table class="table table-bordered mt-2">
                                    <thead>
                                        <tr>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?= htmlspecialchars($litter['remarks']); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <?php
                        }
                        ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>

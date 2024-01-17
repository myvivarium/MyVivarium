<?php
require 'dbcon.php';

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

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Bootstrap JS for Dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

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

                    <!-- Breeding Cage Header -->
                    <div class="card-header">
                        <h4>Litter Details for the Cage
                            <?= $id ?>
                            <a href="bcltr_addn.php?id=<?= rawurlencode($id) ?>" class="btn btn-primary float-end">Add
                                New Litter Data</a>
                        </h4>
                    </div>

                    <div class="card-body">

                        <div class="table-wrapper">
                            <table class="table table-bordered" id="mouseTable">
                                <thead>
                                    <th>DOM</th>
                                    <th>Litter DOB</th>
                                    <th>Pups Alive</th>
                                    <th>Pups Dead</th>
                                    <th>Pups Male</th>
                                    <th>Pups Female</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </thead>
                                <tbody>
                                    <?php
                                    while ($litter = mysqli_fetch_assoc($result)) {
                                        ?>
                                        <tr>
                                            <td>
                                                <?= $litter['dom']; ?>
                                            </td>
                                            <td>
                                                <?= $litter['litter_dob']; ?>
                                            </td>
                                            <td>
                                                <?= $litter['pups_alive']; ?>
                                            </td>
                                            <td>
                                                <?= $litter['pups_dead']; ?>
                                            </td>
                                            <td>
                                                <?= $litter['pups_male']; ?>
                                            </td>
                                            <td>
                                                <?= $litter['pups_female']; ?>
                                            </td>
                                            <td>
                                                <?= $litter['remarks']; ?>
                                            </td>
                                            <td>
                                                <!-- Edit Button -->
                                                <a href="bcltr_edit.php?id=<?= rawurlencode($litter['id']); ?>"
                                                    class="btn btn-secondary">
                                                    <i class="fa fa-edit"></i>
                                                </a>

                                                <!-- Delete Button -->
                                                <a href="bcltr_drop.php?id=<?= rawurlencode($litter['id']); ?>"
                                                    class="btn btn-danger">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </td>

                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
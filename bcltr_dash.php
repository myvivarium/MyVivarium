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
        body {
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: auto;
        }

        .table-wrapper {
            margin-bottom: 50px;
            overflow-x: auto;
            /* Enable horizontal scrolling on small screens */
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

        .btn-sm {
            margin-right: 5px;
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

        .action-icons a {
            margin-right: 10px;
        }

        .action-icons a:last-child {
            margin-right: 0;
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
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="card">
            <!-- Breeding Cage Header -->
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Litter Details for the Cage <?= htmlspecialchars($id) ?>
                    <div class="action-icons">
                        <a href="bc_addn.php" class="btn btn-primary btn-icon" data-toggle="tooltip" data-placement="top" title="Add New Cage">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                </h4>
            </div>

            <div class="card-body">
                <?php while ($litter = mysqli_fetch_assoc($result)) { ?>
                    <div class="table-wrapper">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>DOM</th>
                                    <td><?= htmlspecialchars($litter['dom'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th>Litter DOB</th>
                                    <td><?= htmlspecialchars($litter['litter_dob'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th>Pups Alive</th>
                                    <td><?= htmlspecialchars($litter['pups_alive'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th>Pups Dead</th>
                                    <td><?= htmlspecialchars($litter['pups_dead'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th>Pups Male</th>
                                    <td><?= htmlspecialchars($litter['pups_male'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th>Pups Female</th>
                                    <td><?= htmlspecialchars($litter['pups_female'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th>Remarks</th>
                                    <td><?= htmlspecialchars($litter['remarks'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th>Action</th>
                                    <td>
                                        <a href="bcltr_edit.php?id=<?= rawurlencode($litter['id']) ?>" class="btn btn-secondary">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <a href="bcltr_drop.php?id=<?= rawurlencode($litter['id']) ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?');">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

</body>

</html>
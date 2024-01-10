<?php
session_start();
require 'dbcon.php';

// Fetch the distinct cage IDs from the database
$query = "SELECT DISTINCT `cage id` FROM matingcage";
$result = mysqli_query($con, $query);

// Handle the search filter
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = urldecode($_GET['search']); // Decode the search parameter
    $query = "SELECT * FROM matingcage";
    if (!empty($searchQuery)) {
        $query .= " WHERE `Male id` LIKE '%$searchQuery%' OR `cage id` LIKE '%$searchQuery%' OR `female id` LIKE '%$searchQuery'";
    }
    $result = mysqli_query($con, $query);
}


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

    <title>Bio Lab</title>

    <style>
        .table-wrapper {
            margin-bottom: 50px;
        }

        .table-wrapper table {
            width: 100%;
            border: 1px solid #000000; /* Outer border color */
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-wrapper th,
        .table-wrapper td {
            border: 1px solid gray; /* Inner border color */
            padding: 8px;
            text-align: left;
        }

        .table-wrapper th:first-child,
        .table-wrapper td:first-child {
            border-left: none; /* Remove left border for first column */
        }

        .table-wrapper th:last-child,
        .table-wrapper td:last-child {
            /* Remove right border for last column */
        }

        .table-wrapper tr:first-child th,
        .table-wrapper tr:first-child td {
            border-top: none; /* Remove top border for first row */
        }

        .table-wrapper tr:last-child th,
        .table-wrapper tr:last-child td {
            border-bottom: none; /* Remove bottom border for last row */
        }

        .btn-back {
    background-color: #007BFF;
    color: white;
    padding: 10px 20px;
    border-radius: 30px;
    transition: background-color 0.2s, transform 0.2s;
}

.btn-back:hover {
    background-color: #0056b3;
    color: white;  // ensuring the text color remains white on hover
    transform: scale(1.05);
}

.btn-back:active {
    transform: scale(0.95);
}

.btn-back.fixed {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.btn-logout {
    background-color: #FF6347;  /* Tomato color for distinction */
    color: white;
    padding: 10px 20px;
    border-radius: 30px;
    transition: background-color 0.2s, transform 0.2s;
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 90px;
    transform: translateX(20px) translateY(-20px);
    padding-left: 7px;   /* decrease this value to move text to the left */
    padding-right: 20px;  /* increase this value to move text to the left */

}

.btn-logout:hover {
    background-color: #FF4500;  /* Darker shade for hover effect */
}

.btn-logout i {
    margin-right: 8px;  /* Space between the icon and the text */
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
                        <h4>Mating Cage Details
                            <a href="matingadd.php" class="btn btn-primary float-end">Add New Mating Cage</a>
                        </h4>
                    </div>

                    <div class="card-body">

                        <form method="GET" action="">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Enter mouse ID or cage ID" name="search" value="<?= htmlspecialchars($searchQuery) ?>">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>

                        <?php
                        while ($row = mysqli_fetch_assoc($result)) {
                            $cageID = $row['cage id'];
                            $query = "SELECT * FROM matingcage WHERE `cage id` = '$cageID'";
                            $cageResult = mysqli_query($con, $query);
                            ?>

                            <div class="table-wrapper">
                                <table class="table table-bordered" id="mouseTable">
                                    <?php
                                    $firstRow = true;
                                    while ($matingcage = mysqli_fetch_assoc($cageResult)) {
                                        if ($firstRow) {
                                    ?>
                                            <tr>
                                                <th>Cage ID</th>
                                                <td rowspan="<?= mysqli_num_rows($cageResult); ?>"><?= $matingcage['cage id']; ?></td>
                                                <th>Action</th>
                                                <td rowspan="<?= mysqli_num_rows($cageResult); ?>">
                                                    <a href="matingedit.php?id=<?= $matingcage['cage id']; ?>" class="btn btn-primary">Edit</a>
                                                    <a href="matingdelete.php?id=<?= $matingcage['cage id']; ?>" class="btn btn-danger">Delete</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>PI Name</th>
                                                <td><?= $matingcage['pi name']; ?></td>
                                                <th>Cross</th>
                                                <td><?= $matingcage['cross']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>IACUC</th>
                                                <td><?= $matingcage['IACUC']; ?></td>
                                                <th>User</th>
                                                <td><?= $matingcage['user']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Male ID</th>
                                                <td><?= $matingcage['male id']; ?></td>
                                                <th>DOB</th>
                                                <td><?= $matingcage['male DOB']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Female ID</th>
                                                <td><?= $matingcage['female id']; ?></td>
                                                <th>DOB</th>
                                                <td><?= $matingcage['female DOB']; ?></td>
                                            </tr>

                                            <tr>
                                                <th>DOM</th>
                                                <th>Litter DOB</th>
                                                <th>Pups (A/D)</th>
                                                <th colspan="0">Male</th>
                                                <th>Female</th>
                                                <th>Remarks</th>
                                            </tr>
                                        <?php
                                            $firstRow = false;
                                        }
                                        $DOM = explode(',', $matingcage['DOM']);
                                        $litterDOB = explode(',', $matingcage['litter DOB']);
                                        $pupsAD = explode(',', $matingcage['pups ad']);
                                        $male = explode(',', $matingcage['male']);
                                        $female = explode(',', $matingcage['female']);
                                        $remarks = explode(',', $matingcage['remarks']);

                                        $rowCount = max(count($DOM), count($litterDOB), count($pupsAD), count($male), count($female), count($remarks));

                                        for ($i = 0; $i < $rowCount; $i++) {
                                            $currentDOM = isset($DOM[$i]) ? trim($DOM[$i]) : '';
                                            $currentLitterDOB = isset($litterDOB[$i]) ? trim($litterDOB[$i]) : '';
                                            $currentPupsAD = isset($pupsAD[$i]) ? trim($pupsAD[$i]) : '';
                                            $currentMale = isset($male[$i]) ? trim($male[$i]) : '';
                                            $currentFemale = isset($female[$i]) ? trim($female[$i]) : '';
                                            $currentRemarks = isset($remarks[$i]) ? trim($remarks[$i]) : '';
                                        ?>
                                            <tr>
                                                <td><?= $currentDOM; ?></td>
                                                <td><?= $currentLitterDOB; ?></td>
                                                <td><?= $currentPupsAD; ?></td>
                                                <td><?= $currentMale; ?></td>
                                                <td><?= $currentFemale; ?></td>
                                                <td><?= $currentRemarks; ?></td>
                                            </tr>
                                        <?php
                                        }
                                    }
                                    ?>
                                </table>
                            </div>
                        <?php
                        }
                        ?>

                        <?php if (isset($_GET['search'])) : ?>
                            <div style="text-align: center;">
                                <a href="mating.php" class="btn btn-secondary">Go Back To Mating Cage</a>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

        <div style="position: fixed; top: 20px; right: 20px; z-index: 1001;">
    <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<?php
$backURL = "adminlanding.php";  // Default to admin URL

if(isset($_SESSION['admin_username'])) {
    $backURL = "adminlanding.php";
} elseif(isset($_SESSION['ea_username'])) {
    $backURL = "EAstudentlanding.php";
}
?>
<a href="<?php echo $backURL; ?>" class="btn-back fixed">Go Back</a>

</body>

</html>

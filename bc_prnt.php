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

    // Fetch the holdingcage record with the specified ID
    $query = "SELECT * FROM bc_basic WHERE `cage_id` = '$id'";
    $result = mysqli_query($con, $query);

    $query1 = "SELECT * FROM bc_litter WHERE `cage_id` = '$id'";
    $result1 = mysqli_query($con, $query1);

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
?>

<!doctype html>
<html lang="en">

<head>

        <!-- Required meta tags -->
        <meta charset="utf-8">

    <title>Print Breeding Cage | <?php echo htmlspecialchars($id); ?></title>

    <style>
        * {
            margin: 0;
            padding: 0;
        }

        span {
            font-size: 8pt;
            padding: 0px;
            line-height: 1;
            display: inline-block;
        }

        .table tr td {
            line-height: 1px;
        }
    </style>

</head>

<body>

    <div class="container">
        <br>
        <table style="margin: 50px 0px 0px 0px; width: 5in; height: 1.5 in;"
            class="table table-bordered border-dark align-middle" id="mouseTable">
            <tr>
                <td style="width:30%;">
                    <span style="font-weight: bold;">Breeding Cage Card</span>
                </td>
                <td style="width:30%;">
                    <span style="font-weight: bold;">Cage #: </span>
                    <span>
                        <?= $breedingcage['cage_id']; ?>
                    </span>
                </td>
                <td rowspan="5" style="width:40%; text-align:center;">
                    <img src="<?php echo "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=https://myvivarium.online/bc_view.php?id=" . $breedingcage['cage_id'] . "&choe=UTF-8"; ?>"
                        alt="QR Code">
                </td>
            </tr>
            <tr>
                <td style="width:30%;">
                    <span style="font-weight: bold;">PI Name: </span>
                    <span>
                        <?= $breedingcage['pi_name']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span style="font-weight: bold;">Cross: </span>
                    <span>
                        <?= $breedingcage['cross']; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="width:30%;">
                    <span style="font-weight: bold;">IACUC: </span>
                    <span>
                        <?= $breedingcage['iacuc']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span style="font-weight: bold;">User: </span>
                    <span>
                        <?= $breedingcage['user']; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="width:30%;">
                    <span style="font-weight: bold;">Male ID: </span>
                    <span>
                        <?= $breedingcage['male_id']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span style="font-weight: bold;">Male DOB: </span>
                    <span>
                        <?= $breedingcage['male_dob']; ?>
                    </span>
                </td>
            </tr>
            <tr style="border-bottom: none;">
                <td style="width:30%;">
                    <span style="font-weight: bold;">Female ID: </span>
                    <span>
                        <?= $breedingcage['female_id']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span style="font-weight: bold;">Female DOB: </span>
                    <span>
                        <?= $breedingcage['female_dob']; ?>
                    </span>
                </td>
            </tr>
        </table>

        <table style="margin: 0px; width: 5in;" class="table table-bordered border-dark align-middle" id="mouseTable">
            <tr>
                <td style="width:20%;">
                    <span style="font-weight: bold;">DOM</span>
                </td>
                <td style="width:20%;">
                    <span style="font-weight: bold;">Litter DOB</span>
                </td>
                <td style="width:10%;">
                    <span style="font-weight: bold;">Pups Alive</span>
                </td>
                <td style="width:10%;">
                    <span style="font-weight: bold;">Pups Dead</span>
                </td>
                <td style="width:10%;">
                    <span style="font-weight: bold;">Pups Male</span>
                </td>
                <td style="width:10%;">
                    <span style="font-weight: bold;">Pups Female</span>
                </td>
                <td style="width:20%;">
                    <span style="font-weight: bold;">Remarks</span>
                </td>
            </tr>
            <?php
            while ($litter = mysqli_fetch_assoc($result1)) {
                ?>
                <tr>
                    <td style="width:20%;">
                        <span>
                            <?= $litter['dom']; ?>
                        </span>
                    </td>
                    <td style="width:20%;">
                        <span>
                            <?= $litter['litter_dob']; ?>
                        </span>
                    </td>
                    <td style="width:10%;">
                        <span>
                            <?= $litter['pups_alive']; ?>
                        </span>
                    </td>
                    <td style="width:10%;">
                        <span>
                            <?= $litter['pups_dead']; ?>
                        </span>
                    </td>
                    <td style="width:10%;">
                        <span>
                            <?= $litter['pups_male']; ?>
                        </span>
                    </td>
                    <td style="width:10%;">
                        <span>
                            <?= $litter['pups_female']; ?>
                        </span>
                    </td>
                    <td style="width:20%;">
                        <!-- <span>
                            <?= $litter['remarks']; ?>
                        </span> -->
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>

    </div>

</body>

</html>
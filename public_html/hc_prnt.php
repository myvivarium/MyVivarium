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
    $query = "SELECT * FROM hc_basic WHERE `cage_id` = '$id'";
    $result = mysqli_query($con, $query);

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

    <title>View Holding Cage</title>

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
                    <span style="font-weight: bold;">Holding Cage Card</span>
                </td>
                <td style="width:30%;">
                    <span style="font-weight: bold;">Cage #: </span>
                    <span>
                        <?= $holdingcage['cage_id']; ?>
                    </span>
                </td>
                <td rowspan="5" style="width:40%; text-align:center;">
                    <img src="<?php echo "https://chart.googleapis.com/chart?chs=120x120&cht=qr&chl=https://myvivarium.online/hc_view.php?id=" . $holdingcage['cage_id'] . "&choe=UTF-8"; ?>"
                        alt="QR Code">
                </td>
            </tr>
            <tr>
                <td style="width:30%;">
                    <span style="font-weight: bold;">PI Name: </span>
                    <span>
                        <?= $holdingcage['pi_name']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span style="font-weight: bold;">Strain: </span>
                    <span>
                        <?= $holdingcage['strain']; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="width:30%;">
                    <span style="font-weight: bold;">IACUC: </span>
                    <span>
                        <?= $holdingcage['iacuc']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span style="font-weight: bold;">User: </span>
                    <span>
                        <?= $holdingcage['user']; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="width:30%;">
                    <span style="font-weight: bold;">Qty: </span>
                    <span>
                        <?= $holdingcage['qty']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span style="font-weight: bold;">DOB: </span>
                    <span>
                        <?= $holdingcage['dob']; ?>
                    </span>
                </td>
            </tr>
            <tr style="border-bottom: none;">
                <td style="width:30%;">
                    <span style="font-weight: bold;">Sex: </span>
                    <span>
                        <?= $holdingcage['sex']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span style="font-weight: bold;">Parent Cage: </span>
                    <span>
                        <?= $holdingcage['parent_cg']; ?>
                    </span>
                </td>
            </tr>
        </table>

        <table style="margin: 0px; width: 5in;" class="table table-bordered border-dark align-middle" id="mouseTable">
            <tr>
                <td style="width:30%;">
                    <span style="font-weight: bold;">Mouse ID:</span>
                </td>
                <td style="width:30%;">
                    <span style="font-weight: bold;">Genotype:</span>
                </td>
                <td style="width:40%;">
                    <span style="font-weight: bold;">Notes:</span>
                </td>
            </tr>
            <tr>
                <td style="width:30%;">
                    <span>
                        <?= $holdingcage['mouse_id_1']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span>
                        <?= $holdingcage['genotype_1']; ?>
                    </span>
                </td>
                <td style="width:40%;">
                    <span>
                        <?= $holdingcage['notes_1']; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="width:30%;">
                    <span>
                        <?= $holdingcage['mouse_id_2']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span>
                        <?= $holdingcage['genotype_2']; ?>
                    </span>
                </td>
                <td style="width:40%;">
                    <span>
                        <?= $holdingcage['notes_2']; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="width:30%;">
                    <span>
                        <?= $holdingcage['mouse_id_3']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span>
                        <?= $holdingcage['genotype_3']; ?>
                    </span>
                </td>
                <td style="width:40%;">
                    <span>
                        <?= $holdingcage['notes_3']; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="width:30%;">
                    <span>
                        <?= $holdingcage['mouse_id_4']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span>
                        <?= $holdingcage['genotype_4']; ?>
                    </span>
                </td>
                <td style="width:40%;">
                    <span>
                        <?= $holdingcage['notes_4']; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="width:30%;">
                    <span>
                        <?= $holdingcage['mouse_id_5']; ?>
                    </span>
                </td>
                <td style="width:30%;">
                    <span>
                        <?= $holdingcage['genotype_5']; ?>
                    </span>
                </td>
                <td style="width:40%;">
                    <span>
                        <?= $holdingcage['notes_5']; ?>
                    </span>
                </td>
            </tr>
        </table>

    </div>

</body>

</html>
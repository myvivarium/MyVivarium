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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Printable 2x2 Card Table</title>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
        }

        span {
            font-size: 8pt;
            padding: 0px;
            line-height: 1;
            display: inline-block;
        }

        table {
            border: 1px solid grey;
            box-sizing: border-box;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
            border-spacing: 0;
        }

        th,
        td {
            border: 1px solid grey;
            box-sizing: border-box;
            border-collapse: collapse;
        }
    </style>
</head>

<body>
    <table style="width: 10in;height: 6in;border-collapse: collapse;margin: 1.25in 0.50in;">
        <tr style="height: 3in;">
            <td style="width: 5in;vertical-align:top">

                <!--Cage1-->
                <table border="1" style="width: 5in;height: 1.5in;" id="cage1A">
                    <tr>
                        <td style="width: 40%;"> <span style="font-weight: bold; font-size: 10pt; text-transform: uppercase;">Holding Cage Card</span> </td>
                        <td style="width:40%;"> <span style="font-weight: bold;">Cage #: </span> <span> <?= $holdingcage["cage_id"] ?> </span> </td>
                        <td rowspan="5" style="width:20%; text-align:center;"> <img src="<?php echo "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=https://myvivarium.online/hc_view.php?id=" . $holdingcage["cage_id"] . "&choe=UTF-8"; ?>" alt="QR Code"> </td>
                    </tr>
                    <tr>
                        <td style="width:40%;"> <span style="font-weight: bold;">PI Name: </span> <span> <?= $holdingcage["pi_name"] ?> </span> </td>
                        <td style="width:40%;"> <span style="font-weight: bold;">Strain: </span> <span> <?= $holdingcage["strain"] ?> </span> </td>
                    </tr>
                    <tr>
                        <td style="width:40%;"> <span style="font-weight: bold;">IACUC: </span> <span> <?= $holdingcage["iacuc"] ?> </span> </td>
                        <td style="width:40%;"> <span style="font-weight: bold;">User: </span> <span> <?= $holdingcage["user"] ?> </span> </td>
                    </tr>
                    <tr>
                        <td style="width:40%;"> <span style="font-weight: bold;">Qty: </span> <span> <?= $holdingcage["qty"] ?> </span> </td>
                        <td style="width:40%;"> <span style="font-weight: bold;">DOB: </span> <span> <?= $holdingcage["dob"] ?> </span> </td>
                    </tr>
                    <tr style="border-bottom: none;">
                        <td style="width:40%;"> <span style="font-weight: bold;">Sex: </span> <span> <?= $holdingcage["sex"] ?> </span> </td>
                        <td style="width:40%;"> <span style="font-weight: bold;">Parent Cage: </span> <span> <?= $holdingcage["parent_cg"] ?> </span> </td>
                    </tr>
                </table>

                <table border="1" style="width: 5in;height: 1.5in;" id="cage1B">
                    <tr>
                        <td style="width:40%;"> <span style="font-weight: bold;">Mouse ID:</span> </td>
                        <td style="width:60%;"> <span style="font-weight: bold;">Genotype:</span> </td>
                    </tr>
                    <tr>
                        <td style="width:40%;"> <span> <?= $holdingcage["mouse_id_1"] ?> </span> </td>
                        <td style="width:60%;"> <span> <?= $holdingcage["genotype_1"] ?> </span> </td>
                    </tr>
                    <tr>
                        <td style="width:40%;"> <span> <?= $holdingcage["mouse_id_2"] ?> </span> </td>
                        <td style="width:60%;"> <span> <?= $holdingcage["genotype_2"] ?> </span> </td>
                    </tr>
                    <tr>
                        <td style="width:40%;"> <span> <?= $holdingcage["mouse_id_3"] ?> </span> </td>
                        <td style="width:60%;"> <span> <?= $holdingcage["genotype_3"] ?> </span> </td>
                    </tr>
                    <tr>
                        <td style="width:40%;"> <span> <?= $holdingcage["mouse_id_4"] ?> </span> </td>
                        <td style="width:60%;"> <span> <?= $holdingcage["genotype_4"] ?> </span> </td>
                    </tr>
                    <tr>
                        <td style="width:40%;"> <span> <?= $holdingcage["mouse_id_5"] ?> </span> </td>
                        <td style="width:60%;"> <span> <?= $holdingcage["genotype_5"] ?> </span> </td>
                    </tr>
                </table>

            </td>
            <td>Card 2</td>
        </tr>
        <tr>
            <td>Card 3</td>
            <td>Card 4</td>
        </tr>
    </table>
</body>

</html>
<?php
session_start();
require 'dbcon.php';

$labQuery = "SELECT * FROM data LIMIT 1";
$labResult = mysqli_query($con, $labQuery);

if ($row = mysqli_fetch_assoc($labResult)) {
    $url = $row['url'];
}

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $ids = explode(',', $_GET['id']);
    $holdingcages = [];

    foreach ($ids as $id) {
        // Fetch the holdingcage record with the specified ID
        $query = "SELECT * FROM hc_basic WHERE `cage_id` = '$id'";
        $result = mysqli_query($con, $query);

        if (mysqli_num_rows($result) === 1) {
            $holdingcages[] = mysqli_fetch_assoc($result);
        } else {
            $_SESSION['message'] = "Invalid ID: $id";
            header("Location: hc_dash.php");
            exit();
        }
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
            box-sizing: border-box;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
            border-spacing: 0;
        }

        th,
        td {
            border: 1px solid black;
            box-sizing: border-box;
            border-collapse: collapse;
        }
    </style>
</head>

<body>
    <table style="width: 10in; height: 6in; border-collapse: collapse; margin: 1.25in 0.50in; border: 1px dashed grey;">
        <?php foreach ($holdingcages as $index => $holdingcage): ?>
        
            <?php if ($index % 2 === 0): ?>
                <tr style="height: 3in; border: 1px dashed grey; vertical-align:top;">
            <?php endif; ?>
        
            <td style="width: 5in; border: 1px dashed grey;">
                <!--Cage <?= $index + 1 ?>-->
                <table border="1" style="width: 5in; height: 1.5in;" id="cage<?= $index + 1 ?>A">
                    <tr>
                        <td colspan="3" style="width: 100%; text-align:center;">
                            <span style="font-weight: bold; font-size: 10pt; text-transform: uppercase; padding:3px;"> Holding Cage - # <?= $holdingcage["cage_id"] ?> </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">PI Name:</span>
                            <span><?= $holdingcage["pi_name"] ?></span>
                        </td>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Strain:</span>
                            <span><?= $holdingcage["strain"] ?></span>
                        </td>
                        <td rowspan="4" style="width:20%; text-align:center;">
                            <img src="<?php echo "https://api.qrserver.com/v1/create-qr-code/?size=75x75&data=https://".$url."/hc_view.php?id=".$holdingcage["cage_id"]."&choe=UTF-8"; ?>" alt="QR Code">
                        </td>
                    </tr>
                    <tr>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">IACUC:</span>
                            <span><?= $holdingcage["iacuc"] ?></span>
                        </td>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">User:</span>
                            <span><?= $holdingcage["user"] ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Qty:</span>
                            <span><?= $holdingcage["qty"] ?></span>
                        </td>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">DOB:</span>
                            <span><?= $holdingcage["dob"] ?></span>
                        </td>
                    </tr>
                    <tr style="border-bottom: none;">
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Sex:</span>
                            <span><?= $holdingcage["sex"] ?></span>
                        </td>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Parent Cage:</span>
                            <span><?= $holdingcage["parent_cg"] ?></span>
                        </td>
                    </tr>
                </table>

                <table border="1" style="width: 5in; height: 1.5in;" id="cage<?= $index + 1 ?>B">
                    <tr>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Mouse ID</span>
                        </td>
                        <td style="width:60%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Genotype</span>
                        </td>
                    </tr>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <tr>
                        <td style="width:40%; padding:3px;">
                            <span><?= $holdingcage["mouse_id_$i"] ?></span>
                        </td>
                        <td style="width:60%; padding:3px;">
                            <span><?= $holdingcage["genotype_$i"] ?></span>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </table>
            </td>
            
            <?php if ($index % 2 === 1 || $index === count($holdingcages) - 1): ?>
                </tr>
            <?php endif; ?>

        <?php endforeach; ?>
    </table>
</body>

</html>
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
    $breedingcages = [];

    foreach ($ids as $id) {
        // Fetch the breeding cage record with the specified ID
        $query = "SELECT * FROM bc_basic WHERE `cage_id` = '$id'";
        $result = mysqli_query($con, $query);

        if (mysqli_num_rows($result) === 1) {
            $breedingcage = mysqli_fetch_assoc($result);

            // Fetch the latest 5 associated litter records for this breeding cage
            $query1 = "SELECT * FROM bc_litter WHERE `cage_id` = '$id' ORDER BY `dom` DESC LIMIT 5";
            $result1 = mysqli_query($con, $query1);
            $litters = [];
            while ($litter = mysqli_fetch_assoc($result1)) {
                $litters[] = $litter;
            }

            // Store the breeding cage and its litters
            $breedingcage['litters'] = $litters;
            $breedingcages[] = $breedingcage;
        } else {
            $_SESSION['message'] = "Invalid ID: $id";
            header("Location: bc_dash.php");
            exit();
        }
    }
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: bc_dash.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Printable 2x2 Card Table</title>
    <style>
        @page {
            size: letter landscape;
            margin: 0;
            padding: 0;
        }

        /* print styles */
        @media print {

            body {
                margin: 0;
                color: #000;
                background-color: #fff;
            }

        }

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

        table#cageA tr td,
        table#cageB tr td {
            border: 1px solid black;
            box-sizing: border-box;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
            border-spacing: 0;
        }

        table#cageB tr:first-child td {
            border-top: none;
        }
    </style>
</head>

<body>
    <table style="width: 10in; height: 6in; border-collapse: collapse; margin: 1in 0.50in 1in 0.50in; border: 1px dashed #D3D3D3;">
        <?php foreach ($breedingcages as $index => $breedingcage): ?>

        <?php if ($index % 2 === 0): ?>
        <tr style="height: 3in; border: 1px dashed #D3D3D3; vertical-align:top;">
            <?php endif; ?>

            <td style="width: 5in; border: 1px dashed #D3D3D3;">
                <!--Cage <?= $index + 1 ?>-->
                <table border="1" style="width: 5in; height: 1.5in;" id="cageA">
                    <tr>
                        <td colspan="3" style="width: 100%; text-align:center;">
                            <span style="font-weight: bold; font-size: 10pt; text-transform: uppercase; padding:3px;">
                                Breeding Cage - # <?= $breedingcage["cage_id"] ?> </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">PI Name:</span>
                            <span><?= $breedingcage["pi_name"] ?></span>
                        </td>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Cross:</span>
                            <span><?= $breedingcage["cross"] ?></span>
                        </td>
                        <td rowspan="4" style="width:20%; text-align:center;">
                            <img src="<?php echo "https://api.qrserver.com/v1/create-qr-code/?size=75x75&data=https://".$url."/bc_view.php?id=".$breedingcage["cage_id"]."&choe=UTF-8"; ?>" alt="QR Code">
                        </td>
                    </tr>
                    <tr>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">IACUC:</span>
                            <span><?= $breedingcage["iacuc"] ?></span>
                        </td>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">User:</span>
                            <span><?= $breedingcage["user"] ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Male ID:</span>
                            <span><?= $breedingcage["male_id"] ?></span>
                        </td>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Male DOB:</span>
                            <span><?= $breedingcage["male_dob"] ?></span>
                        </td>
                    </tr>
                    <tr style="border-bottom: none;">
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Female ID:</span>
                            <span><?= $breedingcage["female_id"] ?></span>
                        </td>
                        <td style="width:40%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase;">Female DOB:</span>
                            <span><?= $breedingcage["female_dob"] ?></span>
                        </td>
                    </tr>
                </table>
                <table border="1" style="width: 5in; height: 1.5in; border-top: none;" id="cageB">
                    <tr>
                        <td style="width:25%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase; border-top: none;">DOM</span>
                        </td>
                        <td style="width:25%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase; border-top: none;">Litter DOB</span>
                        </td>
                        <td style="width:12.5%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase; border-top: none;">Pups Alive</span>
                        </td>
                        <td style="width:12.5%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase; border-top: none;">Pups Dead</span>
                        </td>
                        <td style="width:12.5%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase; border-top: none;">Pups Male</span>
                        </td>
                        <td style="width:12.5%;">
                            <span style="font-weight: bold; padding:3px; text-transform: uppercase; border-top: none;">Pups Female</span>
                        </td>
                    </tr>
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <tr>
                        <td style="width:25%; padding:3px;">
                            <span><?= isset($breedingcage['litters'][$i]['dom']) ? $breedingcage['litters'][$i]['dom'] : '' ?></span>
                        </td>
                        <td style="width:25%; padding:3px;">
                            <span><?= isset($breedingcage['litters'][$i]['litter_dob']) ? $breedingcage['litters'][$i]['litter_dob'] : '' ?></span>
                        </td>
                        <td style="width:12.5%; padding:3px;">
                            <span><?= isset($breedingcage['litters'][$i]['pups_alive']) ? $breedingcage['litters'][$i]['pups_alive'] : '' ?></span>
                        </td>
                        <td style="width:12.5%; padding:3px;">
                            <span><?= isset($breedingcage['litters'][$i]['pups_dead']) ? $breedingcage['litters'][$i]['pups_dead'] : '' ?></span>
                        </td>
                        <td style="width:12.5%; padding:3px;">
                            <span><?= isset($breedingcage['litters'][$i]['pups_male']) ? $breedingcage['litters'][$i]['pups_male'] : '' ?></span>
                        </td>
                        <td style="width:12.5%; padding:3px;">
                            <span><?= isset($breedingcage['litters'][$i]['pups_female']) ? $breedingcage['litters'][$i]['pups_female'] : '' ?></span>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </table>
            </td>

            <?php if ($index % 2 === 1 || $index === count($breedingcages) - 1): ?>
        </tr>
        <?php endif; ?>

        <?php endforeach; ?>
    </table>
</body>

</html>

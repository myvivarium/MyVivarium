<?php
session_start();
require 'dbcon.php';

// Fetch the first record from the data table
$labQuery = "SELECT * FROM data LIMIT 1";
$labResult = mysqli_query($con, $labQuery);

if ($row = mysqli_fetch_assoc($labResult)) {
    $url = $row['url'];
}

// Redirect to index.php if the user is not logged in
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $ids = explode(',', $_GET['id']);
    $breedingcages = [];

    foreach ($ids as $id) {
        // Prepare and execute the query to fetch the breeding cage record
        $query = $con->prepare("SELECT * FROM bc_basic WHERE cage_id = ?");
        $query->bind_param("s", $id);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows === 1) {
            $breedingcage = $result->fetch_assoc();

            // Prepare and execute the query to fetch the latest 5 associated litter records
            $query1 = $con->prepare("SELECT * FROM bc_litter WHERE cage_id = ? ORDER BY dom DESC LIMIT 5");
            $query1->bind_param("s", $id);
            $query1->execute();
            $result1 = $query1->get_result();
            $litters = [];
            while ($litter = $result1->fetch_assoc()) {
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
        }

        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
        }

        table {
            width: 10in;
            height: 6in;
            border-collapse: collapse;
            margin: 1.25in 0.50in;
            border: 1px dashed #D3D3D3;
            box-sizing: border-box;
        }

        td {
            width: 5in;
            height: 3in;
            vertical-align: top;
            border: 1px dashed #D3D3D3;
            box-sizing: border-box;
        }

        table.inner-table {
            width: 100%;
            height: 1.5in;
            border-collapse: collapse;
        }

        table.inner-table td {
            border: 1px solid black;
            padding: 3px;
        }

        table.inner-table img {
            width: 75px;
            height: 75px;
        }

        .cage-header {
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            text-transform: uppercase;
        }

        .cage-details span {
            font-size: 8pt;
            display: block;
            padding: 3px 0;
        }

        .litter-details span {
            font-size: 8pt;
            display: block;
            padding: 3px 0;
        }
    </style>
</head>
<body>
    <table>
        <?php foreach ($breedingcages as $index => $breedingcage): ?>
        <?php if ($index % 2 === 0): ?><tr><?php endif; ?>
            <td>
                <table class="inner-table">
                    <tr>
                        <td colspan="3" class="cage-header">
                            Breeding Cage - #<?= $breedingcage["cage_id"] ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="cage-details">
                            <span><strong>PI Name:</strong> <?= $breedingcage["pi_name"] ?></span>
                            <span><strong>Cross:</strong> <?= $breedingcage["cross"] ?></span>
                        </td>
                        <td class="cage-details">
                            <span><strong>IACUC:</strong> <?= $breedingcage["iacuc"] ?></span>
                            <span><strong>User:</strong> <?= $breedingcage["user"] ?></span>
                        </td>
                        <td rowspan="4" style="text-align: center;">
                            <img src="<?= "https://api.qrserver.com/v1/create-qr-code/?size=75x75&data=https://$url/bc_view.php?id=".$breedingcage["cage_id"]."&choe=UTF-8" ?>" alt="QR Code">
                        </td>
                    </tr>
                    <tr>
                        <td class="cage-details">
                            <span><strong>Male ID:</strong> <?= $breedingcage["male_id"] ?></span>
                            <span><strong>Male DOB:</strong> <?= $breedingcage["male_dob"] ?></span>
                        </td>
                        <td class="cage-details">
                            <span><strong>Female ID:</strong> <?= $breedingcage["female_id"] ?></span>
                            <span><strong>Female DOB:</strong> <?= $breedingcage["female_dob"] ?></span>
                        </td>
                    </tr>
                </table>
                <table class="inner-table">
                    <tr>
                        <td><span><strong>DOM</strong></span></td>
                        <td><span><strong>Litter DOB</strong></span></td>
                        <td><span><strong>Pups Alive</strong></span></td>
                        <td><span><strong>Pups Dead</strong></span></td>
                        <td><span><strong>Pups Male</strong></span></td>
                        <td><span><strong>Pups Female</strong></span></td>
                    </tr>
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <tr>
                        <td class="litter-details"><?= isset($breedingcage['litters'][$i]['dom']) ? $breedingcage['litters'][$i]['dom'] : '' ?></td>
                        <td class="litter-details"><?= isset($breedingcage['litters'][$i]['litter_dob']) ? $breedingcage['litters'][$i]['litter_dob'] : '' ?></td>
                        <td class="litter-details"><?= isset($breedingcage['litters'][$i]['pups_alive']) ? $breedingcage['litters'][$i]['pups_alive'] : '' ?></td>
                        <td class="litter-details"><?= isset($breedingcage['litters'][$i]['pups_dead']) ? $breedingcage['litters'][$i]['pups_dead'] : '' ?></td>
                        <td class="litter-details"><?= isset($breedingcage['litters'][$i]['pups_male']) ? $breedingcage['litters'][$i]['pups_male'] : '' ?></td>
                        <td class="litter-details"><?= isset($breedingcage['litters'][$i]['pups_female']) ? $breedingcage['litters'][$i]['pups_female'] : '' ?></td>
                    </tr>
                    <?php endfor; ?>
                </table>
            </td>
        <?php if ($index % 2 === 1 || $index === count($breedingcages) - 1): ?></tr><?php endif; ?>
        <?php endforeach; ?>
    </table>
</body>
</html>

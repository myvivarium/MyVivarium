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

    <title>Print Holding Cage QR code | <?php echo htmlspecialchars($id); ?></title>

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
                <td rowspan="5" style="width:40%; text-align:center;">
                    <img src="<?php echo "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=https://myvivarium.online/hc_view.php?id=" . $id . "&choe=UTF-8"; ?>"
                        alt="QR Code">
                </td>
            </tr>
        </table>

    </div>

</body>

</html>
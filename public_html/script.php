<?php
session_start();
require 'dbcon.php';

// Fetch the distinct cage IDs from the database
$query = "SELECT DISTINCT `cage id` FROM holdingcage";
$result = mysqli_query($con, $query);

// Handle the search filter
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = urldecode($_GET['search']); // Decode the search parameter
    $query = "SELECT * FROM holdingcage";
    if (!empty($searchQuery)) {
        $query .= " WHERE `mouse id` LIKE '%$searchQuery%' OR `cage id` LIKE '%$searchQuery%'";
    }
    $result = mysqli_query($con, $query);
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Holding Cage Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

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
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Holding Cage Details
                            <a href="add.php" class="btn btn-primary float-end">Add New Cage Holding</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Enter mouse ID or cage ID"
                                    name="search" value="<?= htmlspecialchars($searchQuery) ?>">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                        <!-- ... Rest of your table content ... -->

                        <?php
                        while ($row = mysqli_fetch_assoc($result)) {
                            $cageID = $row['cage id'];
                            $query = "SELECT * FROM holdingcage WHERE `cage id` = '$cageID'";
                            $cageResult = mysqli_query($con, $query);
                            ?>

                            <!-- ... Rest of your table content ... -->

                            <?php
                            $firstRow = true;
                            while ($holdingcage = mysqli_fetch_assoc($cageResult)) {
                                if ($firstRow) {
                                    ?>

                                    <!-- ... Rest of your table content ... -->

                                    <!-- Button to generate QR code -->
                                    <button class="btn btn-success"
                                        onclick="generateQRCode('<?= $holdingcage['cage id']; ?>')">Generate QR
                                        Code</button>

                                    <!-- ... Rest of your table content ... -->

                                    <?php
                                    $firstRow = false;
                                }
                                // Rest of the loop
                            }
                            ?>

                            <!-- ... Rest of your table content ... -->

                        <?php
                        }
                        ?>

                        <!-- QR Code Modal -->
                        <div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                            aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLabel">QR Code for Cage ID</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <div id="qrcode"></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- JavaScript code for generating QR codes and opening the modal -->
                        <script>
                            function generateQRCode(cageId) {
                                const qrcode = new QRCode(document.getElementById("qrcode"), {
                                    text: cageId,
                                    width: 128,
                                    height: 128
                                });

                                const modal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
                                modal.show();
                            }
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Fetch the distinct cage IDs from the database
$query = "SELECT DISTINCT `cage_id` FROM hc_basic";
$result = mysqli_query($con, $query);

// Handle the search filter
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = urldecode($_GET['search']); // Decode the search parameter
    $query = "SELECT * FROM hc_basic";
    if (!empty($searchQuery)) {
        $query .= " WHERE `cage_id` LIKE '%$searchQuery%'";
    }
    $result = mysqli_query($con, $query);
}

require 'header.php';
?>

<!-- Start of the HTML -->
<!doctype html>
<html lang="en">

<head>

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <script>
        function confirmDeletion(id) {
            var confirmDelete = confirm("Are you sure you want to delete this cage - '" + id + "'?");
            if (confirmDelete) {
                // If confirmed, redirect to the PHP script with the ID and a confirm flag
                window.location.href = "hc_drop.php?id=" + id + "&confirm=true";
            }
        }
    </script>

    <script>
        function showQrCodePopup(cageId) {
            // Create the popup window
            var popup = window.open("", "QR Code for Cage " + cageId, "width=400,height=400");

            // URL to generate the QR code image
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://myvivarium.online/hc_view.php?id=' + cageId;

            // HTML content for the popup, including a dynamic title and the QR code image
            var htmlContent = `
            <html>
            <head>
                <title>QR Code for Cage ${cageId}</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding-top: 40px; }
                    h1 { color: #333; }
                    img { margin-top: 20px; }
                </style>
            </head>
            <body>
                <h1>QR Code for Cage ${cageId}</h1>
                <img src="${qrUrl}" alt="QR Code for Cage ${cageId}" />
            </body>
            </html>
        `;

            // Write the HTML content to the popup document
            popup.document.write(htmlContent);
            popup.document.close(); // Close the document for further writing
        }
    </script>


    <title>Dashboard Holding Cage | <?php echo htmlspecialchars($labName); ?></title>

    <style>
        /* General Styles */
        body {
            margin: 0;
            padding: 0;
        }

        /* Table Wrapper Styling */
        .table-wrapper {
            margin-bottom: 50px;
        }

        .table-wrapper table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-wrapper th,
        .table-wrapper td {
            border: 1px solid #ddd;
            /* Lighter border for a more modern look */
            padding: 8px;
            text-align: left;
        }

        /* Button Styling */
        .btn-back,
        .btn-logout {
            padding: 10px 20px;
            border-radius: 30px;
            transition: background-color 0.2s, transform 0.2s;
        }

        .btn-back {
            background-color: #007BFF;
            color: white;
        }

        .btn-back:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .btn-back:active,
        .btn-secondary:active {
            transform: scale(0.95);
        }

        .btn-back.fixed {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .btn-secondary:hover {
            background-color: #FF4500;
        }
    </style>
</head>

<body>

    <div class="container mt-4">
        <?php include('message.php'); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <!-- Holding Cage Header -->
                    <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Holding Cage Dashboard</h4>
                    <div>
                        <a href="hc_addn.php" class="btn btn-primary">Add New Cage</a>
                        <a href="hc_slct_crd.php" class="btn btn-success">Print Cage Card</a>
                    </div>
                    </div>


                    <div class="card-body">
                        <!-- Holding Cage Search Box -->
                        <form method="GET" action="">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Enter Cage ID" name="search" value="<?= htmlspecialchars($searchQuery) ?>">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>

                        <div class="table-wrapper">
                            <table class="table table-bordered" id="mouseTable">
                                <thead>
                                    <th>Cage ID</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </thead>
                                <tbody>
                                    <?php
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $cageID = $row['cage_id'];
                                        $query = "SELECT * FROM hc_basic WHERE `cage_id` = '$cageID'";
                                        $cageResult = mysqli_query($con, $query);
                                        while ($holdingcage = mysqli_fetch_assoc($cageResult)) {
                                    ?>
                                            <tr>
                                                <td rowspan="<?= mysqli_num_rows($cageResult); ?>">
                                                    <?= $holdingcage['cage_id']; ?>
                                                </td>
                                                <td>
                                                    <?= $holdingcage['remarks']; ?>
                                                </td>
                                                <td>
                                                    <a href="hc_view.php?id=<?= rawurlencode($holdingcage['cage_id']); ?>" class="btn btn-primary">View</a>
                                                    <!--<a href="hc_prnt.php?id=<?= rawurlencode($holdingcage['cage_id']); ?>" class="btn btn-success">Print</a>-->
                                                    <a href="javascript:void(0);" onclick="showQrCodePopup('<?= rawurlencode($holdingcage['cage_id']); ?>')" class="btn btn-success">QR</a>
                                                    <a href="hc_edit.php?id=<?= rawurlencode($holdingcage['cage_id']); ?>" class="btn btn-secondary">Edit</a>
                                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') : ?>
                                                        <a href="#" onclick="confirmDeletion('<?php echo $holdingcage['cage_id']; ?>')" class="btn btn-danger">Delete</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (isset($_GET['search'])) : ?>
                            <div style="text-align: center;">
                                <a href="hc_dash.php" class="btn btn-secondary">Go Back To Holding Cage Dashboard</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>

    <!-- Modal HTML -->
    <div id="qrCodeModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <img id="qrCodeImage" src="" alt="QR Code" style="width:100%; max-width:400px;">
        </div>
    </div>
</body>

</html>
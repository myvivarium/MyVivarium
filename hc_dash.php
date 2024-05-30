<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <script>
        // Confirm deletion function
        function confirmDeletion(id) {
            var confirmDelete = confirm("Are you sure you want to delete this cage - '" + id + "'?");
            if (confirmDelete) {
                window.location.href = "hc_drop.php?id=" + id + "&confirm=true";
            }
        }

        // Show QR code popup function
        function showQrCodePopup(cageId) {
            var popup = window.open("", "QR Code for Cage " + cageId, "width=400,height=400");
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://myvivarium.online/hc_view.php?id=' + cageId;
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
            popup.document.write(htmlContent);
            popup.document.close();
        }

        // Fetch data function
        function fetchData(page = 1, search = '') {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'hc_fetch_data.php?page=' + page + '&search=' + encodeURIComponent(search), true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    document.getElementById('tableBody').innerHTML = response.tableRows;
                    document.getElementById('paginationLinks').innerHTML = response.paginationLinks;
                }
            };
            xhr.send();
        }

        // Search function
        function searchCages() {
            var searchQuery = document.getElementById('searchInput').value;
            fetchData(1, searchQuery);
        }

        document.addEventListener('DOMContentLoaded', function () {
            fetchData();
        });
    </script>

    <title>Dashboard Holding Cage | <?php echo htmlspecialchars($labName); ?></title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

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
            padding: 8px;
            text-align: left;
        }

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

        @media (max-width: 768px) {
            .table-wrapper table, .table-wrapper th, .table-wrapper td {
                display: block;
                width: 100%;
            }

            .table-wrapper th, .table-wrapper td {
                box-sizing: border-box;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <?php include('message.php'); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Holding Cage Dashboard</h4>
                        <div>
                            <a href="hc_addn.php" class="btn btn-primary">Add New Cage</a>
                            <a href="hc_slct_crd.php" class="btn btn-success">Print Cage Card</a>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Holding Cage Search Box -->
                        <div class="input-group mb-3">
                            <input type="text" id="searchInput" class="form-control" placeholder="Enter Cage ID" onkeyup="searchCages()">
                            <button class="btn btn-primary" type="button" onclick="searchCages()">Search</button>
                        </div>

                        <div class="table-wrapper" id="tableContainer">
                            <table class="table table-bordered" id="mouseTable">
                                <thead>
                                    <tr>
                                        <th>Cage ID</th>
                                        <th>Remarks</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <!-- Table rows will be inserted here by JavaScript -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center" id="paginationLinks">
                                <!-- Pagination links will be inserted here by JavaScript -->
                            </ul>
                        </nav>

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
</body>

</html>

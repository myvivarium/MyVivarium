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

    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <script>
        // Confirm deletion function
        function confirmDeletion(id) {
            var confirmDelete = confirm("Are you sure you want to delete this cage - '" + id + "'?");
            if (confirmDelete) {
                window.location.href = "hc_drop.php?id=" + id + "&confirm=true";
            }
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
            overflow-x: auto; /* Enable horizontal scrolling on small screens */
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

        .action-icons a {
            margin-right: 10px;
            display: inline-block;
        }

        .action-icons a:last-child {
            margin-right: 0;
        }

        .btn-icon {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-icon i {
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .table-wrapper th, .table-wrapper td {
                padding: 12px 8px;
            }

            .table-wrapper th, .table-wrapper td {
                text-align: center;
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
                                        <th style="width: 50%;">Cage ID</th>
                                        <th style="width: 50%;">Action</th>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>

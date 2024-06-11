<?php

/**
 * Breeding Cage Dashboard Script
 *
 * This script displays a dashboard for managing breeding cages. It starts a session, checks if the user is logged in,
 * and includes the necessary header and database connection files. The HTML part of the script includes the structure
 * for displaying breeding cages, search functionality, and actions such as adding a new cage or printing cage cards.
 * The script uses JavaScript for handling search, pagination, and confirmation dialogs.
 *
 * Author: [Your Name]
 * Date: [Date]
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php with the current URL for redirection after login
if (!isset($_SESSION['username'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit; // Exit to ensure no further code is executed
}

// Include the header file
require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags for responsive design -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Bootstrap for tooltips and responsive design -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

    <script>
        // Initialize tooltips
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });

        // Confirm deletion function
        function confirmDeletion(id) {
            var confirmDelete = confirm("Are you sure you want to delete this cage - '" + id + "'?");
            if (confirmDelete) {
                window.location.href = "bc_drop.php?id=" + id + "&confirm=true";
            }
        }

        // Fetch data function
        function fetchData(page = 1, search = '') {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'bc_fetch_data.php?page=' + page + '&search=' + encodeURIComponent(search), true);
            xhr.onload = function() {
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

        // Fetch initial data when the document is loaded
        document.addEventListener('DOMContentLoaded', function() {
            fetchData();
        });
    </script>

    <title>Dashboard Breeding Cage | <?php echo htmlspecialchars($labName); ?></title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .table-wrapper {
            margin-bottom: 50px;
            overflow-x: auto;
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

        .btn-sm {
            margin-right: 5px;
        }

        .btn-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .btn-icon i {
            font-size: 16px;
            margin: 0;
        }

        .action-icons a {
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .action-icons a:last-child {
            margin-right: 0;
        }

        @media (max-width: 768px) {

            .table-wrapper th,
            .table-wrapper td {
                padding: 12px 8px;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="container content mt-4">
        <!-- Include message for user notifications -->
        <?php include('message.php'); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-center">
                        <h4>Breeding Cage Dashboard</h4>
                        <div class="action-icons mt-3 mt-md-0">
                            <!-- Add new cage button with tooltip -->
                            <a href="bc_addn.php" class="btn btn-primary btn-icon" data-toggle="tooltip" data-placement="top" title="Add New Cage">
                                <i class="fas fa-plus"></i>
                            </a>
                            <!-- Print cage card button with tooltip -->
                            <a href="bc_slct_crd.php" class="btn btn-success btn-icon" data-toggle="tooltip" data-placement="top" title="Print Cage Card">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>
                    </div>


                    <div class="card-body">
                        <!-- Breeding Cage Search Box -->
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
    <!-- Include footer -->
    <?php include 'footer.php'; ?>

    <!-- Bootstrap and jQuery for tooltips -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>

</html>
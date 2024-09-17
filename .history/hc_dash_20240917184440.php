<?php

/**
 * Holding Cage Dashboard Script
 *
 * This script displays the holding cage dashboard for logged-in users. It includes functionalities such as
 * adding new cages, printing cage cards, searching cages, and pagination. The page content is dynamically
 * loaded using JavaScript and AJAX.
 *
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
    <!-- Required meta tags for character encoding and responsive design -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Holding Cage | <?php echo htmlspecialchars($labName); ?></title>

    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Bootstrap for tooltips and styling -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

    <style>
        /* Styles */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 1000px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

        .table-wrapper th {
            cursor: pointer;
        }

        .table-wrapper th a {
            color: inherit;
            text-decoration: none;
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
        <!-- Include message file for displaying messages -->
        <?php include('message.php'); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-center">
                        <h4>Holding Cage Dashboard</h4>
                        <div class="action-icons mt-3 mt-md-0">
                            <!-- Add new cage button with tooltip -->
                            <a href="hc_addn.php" class="btn btn-primary btn-icon" data-toggle="tooltip" data-placement="top" title="Add New Cage">
                                <i class="fas fa-plus"></i>
                            </a>
                            <!-- Print cage card button with tooltip -->
                            <a href="hc_slct_crd.php" class="btn btn-success btn-icon" data-toggle="tooltip" data-placement="top" title="Print Cage Card">
                                <i class="fas fa-print"></i>
                            </a>
                            <!-- Maintenance button with tooltip -->
                            <a href="maintenance.php?from=hc_dash" class="btn btn-warning btn-icon" data-toggle="tooltip" data-placement="top" title="Cage Maintenance">
                                <i class="fas fa-wrench"></i>
                            </a>
                            <!-- Manage Tasks Overview button with tooltip -->
                            <a href="manage_tasks_overview.php" class="btn btn-secondary btn-icon" data-toggle="tooltip" data-placement="top" title="Manage Tasks Overview">
                                <i class="fas fa-tasks"></i>
                            </a>
                        </div>
                    </div>


                    <div class="card-body">
                        <!-- Holding Cage Search Box -->
                        <div class="input-group mb-3">
                            <input type="text" id="searchInput" class="form-control" placeholder="Enter Cage ID" onkeyup="searchCages()"> <!-- Call search function on keyup -->
                            <button class="btn btn-primary" type="button" onclick="searchCages()">Search</button>
                        </div>

                        <!-- Optional Field Selection -->
                        <div class="form-group">
                            <label for="optionalFieldSelect">Select Field to Display:</label>
                            <select id="optionalFieldSelect" class="form-control" onchange="fetchData(1, document.getElementById('searchInput').value)">
                                <option value="">None</option>
                                <option value="age">Age</option>
                                <option value="strain">Strain</option>
                                <option value="iacuc">IACUC</option>
                                <option value="qty">Quantity</option>
                                <option value="sex">Sex</option>
                            </select>
                        </div>

                        <div class="table-wrapper" id="tableContainer">
                            <table class="table table-bordered" id="mouseTable">
                                <thead id="tableHead">
                                    <!-- Headers will be inserted dynamically -->
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
    <?php include 'footer.php'; ?> <!-- Include footer file -->

    <!-- Bootstrap and jQuery for tooltips -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>

    <!-- JavaScript Code -->
    <script>
        // Initialize tooltips when the document is ready
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });

        // Confirm deletion function with a dialog
        function confirmDeletion(id) {
            var confirmDelete = confirm("Are you sure you want to delete cage - '" + id + "' and related mouse data?");
            if (confirmDelete) {
                window.location.href = "hc_drop.php?id=" + id + "&confirm=true"; // Redirect to deletion script
            }
        }

        // Variables for sorting
        var currentSortField = '';
        var currentSortOrder = 'ASC';

        // Fetch data function to load data dynamically
        function fetchData(page = 1, search = '') {
            var selectedField = document.getElementById('optionalFieldSelect').value;

            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'hc_fetch_data.php?page=' + page +
                '&search=' + encodeURIComponent(search) +
                '&field=' + encodeURIComponent(selectedField) +
                '&sortField=' + encodeURIComponent(currentSortField) +
                '&sortOrder=' + encodeURIComponent(currentSortOrder), true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.tableRows && response.paginationLinks) {
                            document.getElementById('tableBody').innerHTML = response.tableRows; // Insert table rows
                            document.getElementById('paginationLinks').innerHTML = response.paginationLinks; // Insert pagination links
                            document.getElementById('searchInput').value = search; // Preserve search input

                            // Adjust table headers
                            updateTableHeaders(response.selectedField);

                            // Update the URL with current parameters
                            const newUrl = new URL(window.location.href);
                            newUrl.searchParams.set('page', page);
                            newUrl.searchParams.set('search', search);
                            newUrl.searchParams.set('field', selectedField);
                            window.history.replaceState({
                                path: newUrl.href
                            }, '', newUrl.href);
                        } else {
                            console.error('Invalid response format:', response);
                        }
                    } catch (e) {
                        console.error('Error parsing JSON response:', e);
                    }
                } else {
                    console.error('Request failed. Status:', xhr.status);
                }
            };
            xhr.onerror = function() {
                console.error('Request failed. An error occurred during the transaction.');
            };
            xhr.send();
        }

        // Update table headers dynamically
        function updateTableHeaders(selectedField) {
            var tableHeaderRow = document.getElementById('tableHeaderRow');
            if (!tableHeaderRow) {
                tableHeaderRow = document.createElement('tr');
                tableHeaderRow.id = 'tableHeaderRow';
                var thead = document.getElementById('tableHead');
                thead.appendChild(tableHeaderRow);
            } else {
                tableHeaderRow.innerHTML = '';
            }

            // Cage ID header with sorting
            var thCageID = document.createElement('th');
            thCageID.style.width = selectedField ? '30%' : '50%';
            thCageID.innerHTML = '<a href="#" onclick="sortTable(\'cage_id\')">Cage ID</a>';
            tableHeaderRow.appendChild(thCageID);

            // Optional field header with sorting
            if (selectedField) {
                var thOptional = document.createElement('th');
                thOptional.style.width = '30%';
                var headerText = selectedField.replace('_', ' ').toUpperCase();
                var sortField = getSortField(selectedField);
                thOptional.innerHTML = '<a href="#" onclick="sortTable(\'' + sortField + '\')">' + headerText + '</a>';
                tableHeaderRow.appendChild(thOptional);
            }

            // Action header
            var thAction = document.createElement('th');
            thAction.style.width = selectedField ? '40%' : '50%';
            thAction.textContent = 'Action';
            tableHeaderRow.appendChild(thAction);
        }

        // Function to map selectedField to sortField
        function getSortField(selectedField) {
            switch (selectedField) {
                case 'age':
                    return 'dob';
                case 'strain':
                    return 'strain';
                case 'iacuc':
                    return 'iacuc_codes';
                case 'qty':
                    return 'quantity';
                case 'sex':
                    return 'sex';
                default:
                    return 'cage_id';
            }
        }

        // Sorting function
        function sortTable(field) {
            if (currentSortField === field) {
                currentSortOrder = currentSortOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                currentSortField = field;
                currentSortOrder = 'ASC';
            }
            fetchData(1, document.getElementById('searchInput').value);
        }

        // Search function to initiate data fetch based on search query
        function searchCages() {
            var searchQuery = document.getElementById('searchInput').value;
            fetchData(1, searchQuery);
        }

        // Fetch initial data when the DOM content is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page') || 1;
            const search = urlParams.get('search') || '';
            const field = urlParams.get('field') || '';
            document.getElementById('optionalFieldSelect').value = field;
            fetchData(page, search);
        });
    </script>

    <!-- Bootstrap JS and dependencies (optional) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>

</html>

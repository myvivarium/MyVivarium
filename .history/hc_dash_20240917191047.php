<?php
/**
 * Holding Cage Dashboard Script
 * 
 * This script displays the holding cage dashboard for logged-in users. It includes functionalities such as 
 * adding new cages, printing cage cards, searching cages, sorting, and pagination. The page content is dynamically
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

// Fetch the lab name from settings or session
// Assuming $labName is set somewhere, otherwise define a default
$labName = isset($_SESSION['lab_name']) ? $_SESSION['lab_name'] : 'My Vivarium Lab';

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
    
    <!-- Custom styles -->
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 1200px; /* Increased width for more space */
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

            .row.mb-3 > div {
                margin-bottom: 15px;
            }
        }
    </style>

    <!-- jQuery (required for Bootstrap tooltips) -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <!-- Bootstrap JS for tooltips -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

    <!-- Custom JavaScript for fetching data, sorting, and searching -->
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Fetch initial data with parameters from URL
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page') || 1;
            const sortFeature = urlParams.get('sort_feature') || '';
            const sortOrder = urlParams.get('sort_order') || 'ASC';
            const searchFeature = urlParams.get('search_feature') || '';
            const searchValue = urlParams.get('search_value') || '';

            // Set the sort and search controls based on URL parameters
            $('#sortFeature').val(sortFeature);
            $('#sortOrder').val(sortOrder);
            $('#searchFeature').val(searchFeature);
            $('#searchValue').val(searchValue);

            fetchData(page, sortFeature, sortOrder, searchFeature, searchValue);
        });

        // Confirm deletion function with a dialog
        function confirmDeletion(id) {
            var confirmDelete = confirm("Are you sure you want to delete cage - '" + id + "' and related mouse data?");
            if (confirmDelete) {
                window.location.href = "hc_drop.php?id=" + encodeURIComponent(id) + "&confirm=true"; // Redirect to deletion script
            }
        }

        // Fetch data function to load data dynamically with additional parameters for sorting and search
        function fetchData(page = 1, sortFeature = '', sortOrder = 'ASC', searchFeature = '', searchValue = '') {
            // Show loading indicator (optional)
            $('#tableBody').html('<tr><td colspan="7" class="text-center">Loading...</td></tr>');
            $('#paginationLinks').html('');

            $.ajax({
                url: 'hc_fetch_data.php',
                type: 'GET',
                data: {
                    page: page,
                    sort_feature: sortFeature,
                    sort_order: sortOrder,
                    search_feature: searchFeature,
                    search_value: searchValue
                },
                dataType: 'json',
                success: function(response) {
                    if (response.tableRows && response.paginationLinks) {
                        $('#tableBody').html(response.tableRows);
                        $('#paginationLinks').html(response.paginationLinks);
                        // Reinitialize tooltips
                        $('[data-toggle="tooltip"]').tooltip();
                    } else {
                        console.error('Invalid response format:', response);
                        $('#tableBody').html('<tr><td colspan="7" class="text-center">An error occurred while fetching data.</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Request failed:', status, error);
                    $('#tableBody').html('<tr><td colspan="7" class="text-center">An error occurred while fetching data.</td></tr>');
                }
            });
        }

        // Apply Sorting
        function applySorting() {
            var sortFeature = $('#sortFeature').val();
            var sortOrder = $('#sortOrder').val();
            fetchData(1, sortFeature, sortOrder, '', ''); // Reset search
        }

        // Apply Advanced Search
        function applySearch() {
            var searchFeature = $('#searchFeature').val();
            var searchValue = $('#searchValue').val();
            fetchData(1, '', 'ASC', searchFeature, searchValue); // Reset sorting
        }

        // Optionally, implement debounced search
        var debounceTimer;
        function searchCagesDebounced() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                applySearch();
            }, 500); // Adjust delay as needed
        }
    </script>
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
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Sorting Controls -->
                        <div class="row mb-3">
                            <!-- Sorting Options -->
                            <div class="col-md-4">
                                <label for="sortFeature">Sort By:</label>
                                <select id="sortFeature" class="form-control">
                                    <option value="">-- Select Feature --</option>
                                    <option value="age">Age</option>
                                    <option value="strain">Strain</option>
                                    <option value="iacuc">IACUC</option>
                                    <option value="mice_quantity">Mice Quantity</option>
                                    <option value="sex">Sex</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="sortOrder">Sort Order:</label>
                                <select id="sortOrder" class="form-control">
                                    <option value="ASC">Ascending</option>
                                    <option value="DESC">Descending</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button class="btn btn-secondary" onclick="applySorting()">Apply Sorting</button>
                            </div>
                        </div>

                        <!-- Advanced Search Controls -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="searchFeature">Search By:</label>
                                <select id="searchFeature" class="form-control">
                                    <option value="">-- Select Feature --</option>
                                    <option value="cage_id">Cage ID</option>
                                    <option value="age">Age</option>
                                    <option value="strain">Strain</option>
                                    <option value="iacuc">IACUC</option>
                                    <option value="mice_quantity">Mice Quantity</option>
                                    <option value="sex">Sex</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="searchValue">Search Value:</label>
                                <input type="text" id="searchValue" class="form-control" placeholder="Enter search value" onkeyup="searchCagesDebounced()">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button class="btn btn-secondary" onclick="applySearch()">Apply Search</button>
                            </div>
                        </div>

                        <div class="table-wrapper" id="tableContainer">
                            <table class="table table-bordered" id="mouseTable">
                                <thead>
                                    <tr>
                                        <th>Cage ID</th>
                                        <th>Age (Days)</th>
                                        <th>Strain</th>
                                        <th>IACUC</th>
                                        <th>Mice Quantity</th>
                                        <th>Sex</th>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?> <!-- Include footer file -->
</body>

</html>

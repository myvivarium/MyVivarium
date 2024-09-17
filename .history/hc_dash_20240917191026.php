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

        // Fetch data function to load data dynamically with add

<?php

/**
 * Fetch Holding Cage Data Script
 * 
 * This script handles fetching and displaying holding cage data with pagination and search functionality. 
 * It generates JSON output containing the table rows and pagination links, which can be dynamically 
 * inserted into an HTML page via JavaScript.
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
    header("Location: index.php");
    exit; // Exit to ensure no further code is executed
}

// Pagination variables
$limit = 15; // Number of entries to show in a page.
$page = isset($_GET['page']) ? $_GET['page'] : 1; // Current page number, default is 1
$offset = ($page - 1) * $limit; // Calculate offset for the SQL query

// Handle the search filter
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = urldecode($_GET['search']); // Decode the search parameter
}

// Fetch the distinct cage IDs with pagination
$query = "SELECT DISTINCT `cage_id` FROM hc_basic";
if (!empty($searchQuery)) {
    $query .= " WHERE `cage_id` LIKE '%$searchQuery%'"; // Add search filter to the query if present
}
$totalResult = mysqli_query($con, $query); // Execute the query to get the total number of records
$totalRecords = mysqli_num_rows($totalResult); // Get the total number of records
$totalPages = ceil($totalRecords / $limit); // Calculate the total number of pages

$query .= " LIMIT $limit OFFSET $offset"; // Add pagination to the query
$result = mysqli_query($con, $query); // Execute the query with pagination

// Generate the table rows
$tableRows = '';
while ($row = mysqli_fetch_assoc($result)) {
    $cageID = $row['cage_id'];
    $query = "SELECT * FROM hc_basic WHERE `cage_id` = '$cageID'"; // Fetch all records for the current cage ID
    $cageResult = mysqli_query($con, $query);
    $numRows = mysqli_num_rows($cageResult);
    $firstRow = true;
    while ($holdingcage = mysqli_fetch_assoc($cageResult)) {
        $tableRows .= '<tr>';
        if ($firstRow) {
            $tableRows .= '<td style="width: 50%;">' . htmlspecialchars($holdingcage['cage_id']) . '</td>'; // Display cage ID only once per group
            $firstRow = false;
        }
        $tableRows .= '<td class="action-icons" style="width: 50%; white-space: nowrap;">
                        <a href="hc_view.php?id=' . rawurlencode($holdingcage['cage_id']) . '" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="View Cage"><i class="fas fa-eye"></i></a>
                        <a href="hc_edit.php?id=' . rawurlencode($holdingcage['cage_id']) . '" class="btn btn-secondary btn-sm btn-icon"><i class="fas fa-edit" data-toggle="tooltip" data-placement="top" title="Edit Cage"></i></a>';
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $tableRows .= '<a href="#" onclick="confirmDeletion(\'' . htmlspecialchars($holdingcage['cage_id']) . '\')" class="btn btn-danger btn-sm btn-icon"><i class="fas fa-trash" data-toggle="tooltip" data-placement="top" title="Delete Cage"></i></a>';
        }
        $tableRows .= '</td></tr>';
    }
}

// Generate the pagination links
$paginationLinks = '';
for ($i = 1; $i <= $totalPages; $i++) {
    $activeClass = ($i == $page) ? 'active' : ''; // Highlight the active page
    $paginationLinks .= '<li class="page-item ' . $activeClass . '"><a class="page-link" href="javascript:void(0);" onclick="fetchData(' . $i . ')">' . $i . '</a></li>';
}

// Return the generated table rows and pagination links as a JSON response
echo json_encode([
    'tableRows' => $tableRows,
    'paginationLinks' => $paginationLinks
]);

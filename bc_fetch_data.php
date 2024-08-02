<?php

/**
 * Breeding Cage Pagination and Search Script
 *
 * This script handles the pagination and search functionality for breeding cages.
 * It starts a session, includes the database connection, handles search filters,
 * fetches cage data with pagination, and generates HTML for table rows and pagination links.
 * The generated HTML is returned as a JSON response.
 *
 */

// Start a new session or resume the existing session
session_start();

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection
require 'dbcon.php';

// Start output buffering
ob_start();

// Check if the user is not logged in, redirect them to index.php with the current URL for redirection after login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit; // Exit to ensure no further code is executed
}

// Fetch user role and ID from session
$userRole = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

// Pagination variables
$limit = 10; // Number of entries to show in a page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number, default to 1
$offset = ($page - 1) * $limit; // Offset for the SQL query

// Handle the search filter
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = mysqli_real_escape_string($con, urldecode($_GET['search'])); // Decode and escape the search parameter
}

// Fetch the distinct cage IDs with pagination
$query = "SELECT DISTINCT `cage_id` FROM breeding";
if (!empty($searchQuery)) {
    $query .= " WHERE `cage_id` LIKE '%$searchQuery%'"; // Add search filter to the query
}
$totalResult = mysqli_query($con, $query); // Execute the query to get the total number of records
$totalRecords = mysqli_num_rows($totalResult); // Get the total number of records
$totalPages = ceil($totalRecords / $limit); // Calculate the total number of pages

$query .= " LIMIT $limit OFFSET $offset"; // Add pagination to the query
$result = mysqli_query($con, $query); // Execute the query to get the paginated records

// Generate the table rows
$tableRows = '';
while ($row = mysqli_fetch_assoc($result)) {
    $cageID = $row['cage_id']; // Get the cage ID
    $query = "SELECT * FROM breeding WHERE `cage_id` = '$cageID'"; // Fetch all records for the current cage ID
    $cageResult = mysqli_query($con, $query); // Execute the query
    $numRows = mysqli_num_rows($cageResult); // Get the number of rows for the cage ID
    $firstRow = true; // Flag to check if it is the first row for the cage ID

    while ($breedingcage = mysqli_fetch_assoc($cageResult)) {
        $tableRows .= '<tr>';
        if ($firstRow) {
            $tableRows .= '<td style="width: 50%;">' . htmlspecialchars($breedingcage['cage_id']) . '</td>'; // Display cage ID only once per group
            $firstRow = false;
        }
        $tableRows .= '<td class="action-icons" style="width: 50%; white-space: nowrap;">
                        <a href="bc_view.php?id=' . rawurlencode($breedingcage['cage_id']) . '&page=' . $page . '&search=' . urlencode($searchQuery) . '" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="View Cage"><i class="fas fa-eye"></i></a>
                        <a href="manage_tasks.php?id=' . rawurlencode($breedingcage['cage_id']) . '&page=' . $page . '&search=' . urlencode($searchQuery) . '" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Manage Tasks"><i class="fas fa-tasks"></i></a>';
                        
        // Check if the user is an admin or assigned to this cage
        $assignedUsers = explode(',', $breedingcage['user']);
        if ($userRole === 'admin' || in_array($currentUserId, $assignedUsers)) {
            $tableRows .= '<a href="bc_edit.php?id=' . rawurlencode($breedingcage['cage_id']) . '&page=' . $page . '&search=' . urlencode($searchQuery) . '" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Edit Cage"><i class="fas fa-edit"></i></a>
                           <a href="#" onclick="confirmDeletion(\'' . htmlspecialchars($breedingcage['cage_id']) . '\')" class="btn btn-danger btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Delete Cage"><i class="fas fa-trash"></i></a>';
        }
        $tableRows .= '</td></tr>';
    }
}

// Generate the pagination links
$paginationLinks = '';
for ($i = 1; $i <= $totalPages; $i++) {
    $activeClass = ($i == $page) ? 'active' : ''; // Highlight the active page
    $paginationLinks .= '<li class="page-item ' . $activeClass . '"><a class="page-link" href="javascript:void(0);" onclick="fetchData(' . $i . ', \'' . htmlspecialchars($searchQuery, ENT_QUOTES) . '\')">' . $i . '</a></li>';
}

// Clear the output buffer to avoid sending unwanted output before JSON
ob_end_clean();

// Return the table rows and pagination links as a JSON response
header('Content-Type: application/json');
echo json_encode([
    'tableRows' => $tableRows,
    'paginationLinks' => $paginationLinks
]);

?>

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

// Disable error display in production (errors logged to server logs)
error_reporting(E_ALL);
ini_set('display_errors', 0);

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

// Fetch the distinct cage IDs with pagination using prepared statements
if (!empty($searchQuery)) {
    $searchPattern = '%' . $searchQuery . '%';
    // Query with search filter
    $totalQuery = "SELECT DISTINCT `cage_id` FROM breeding WHERE `cage_id` LIKE ?";
    $stmtTotal = $con->prepare($totalQuery);
    $stmtTotal->bind_param("s", $searchPattern);
    $stmtTotal->execute();
    $totalResult = $stmtTotal->get_result();
    $totalRecords = $totalResult->num_rows;
    $totalPages = ceil($totalRecords / $limit);
    $stmtTotal->close();

    // Query with pagination
    $query = "SELECT DISTINCT `cage_id` FROM breeding WHERE `cage_id` LIKE ? LIMIT ? OFFSET ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("sii", $searchPattern, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Query without search filter
    $totalQuery = "SELECT DISTINCT `cage_id` FROM breeding";
    $totalResult = mysqli_query($con, $totalQuery);
    $totalRecords = mysqli_num_rows($totalResult);
    $totalPages = ceil($totalRecords / $limit);

    $query = "SELECT DISTINCT `cage_id` FROM breeding LIMIT ? OFFSET ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Generate the table rows
$tableRows = '';
while ($row = mysqli_fetch_assoc($result)) {
    $cageID = $row['cage_id']; // Get the cage ID
    // Use prepared statement to fetch records for the current cage ID
    $cageQuery = "SELECT * FROM breeding WHERE `cage_id` = ?";
    $stmtCage = $con->prepare($cageQuery);
    $stmtCage->bind_param("s", $cageID);
    $stmtCage->execute();
    $cageResult = $stmtCage->get_result();
    $numRows = $cageResult->num_rows; // Get the number of rows for the cage ID
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
    $stmtCage->close();
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

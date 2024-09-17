<?php

/**
 * Holding Cage Pagination, Search, Sorting, and Custom Column Script
 * 
 * This script handles fetching and displaying holding cage data with pagination, search, sorting, and custom column selection.
 * It generates JSON output containing the table rows and pagination links, which can be dynamically inserted into an HTML page via JavaScript.
 * 
 */

// Start a new session or resume the existing session
session_start();

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit; // Exit to ensure no further code is executed
}

// Fetch user role and ID from session
$userRole = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

// Pagination variables
$limit = 10; // Number of entries to show in a page.
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number, default is 1
$offset = ($page - 1) * $limit; // Calculate offset for the SQL query

// Handle search filter
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = mysqli_real_escape_string($con, urldecode($_GET['search']));
}

// Handle sorting
$sortBy = isset($_GET['sortBy']) ? mysqli_real_escape_string($con, $_GET['sortBy']) : 'cage_id';
$sortOrder = isset($_GET['sortOrder']) && $_GET['sortOrder'] === 'desc' ? 'DESC' : 'ASC';

// Handle custom column selection
$columns = isset($_GET['columns']) ? explode(',', $_GET['columns']) : [];

// Fetch the distinct cage IDs with pagination
$query = "SELECT DISTINCT `cage_id` FROM holding";
if (!empty($searchQuery)) {
    $query .= " WHERE `cage_id` LIKE '%$searchQuery%'"; // Add search filter if present
}
$query .= " ORDER BY $sortBy $sortOrder LIMIT $limit OFFSET $offset"; // Add sorting and pagination
$result = mysqli_query($con, $query);

// Generate the table rows
$tableRows = '';
while ($row = mysqli_fetch_assoc($result)) {
    $cageID = $row['cage_id']; // Get the cage ID
    $tableRows .= '<tr>';
    $tableRows .= '<td style="width: 20%;">' . htmlspecialchars($cageID) . '</td>'; // Cage ID

    // Actions column
    $tableRows .= '<td class="action-icons" style="width: 80%; white-space: nowrap;">
                    <a href="hc_view.php?id=' . rawurlencode($cageID) . '" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="View Cage">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="manage_tasks.php?id=' . rawurlencode($cageID) . '" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Manage Tasks">
                        <i class="fas fa-tasks"></i>
                    </a>';

    // Check if the user is an admin or assigned to this cage
    $queryUserAssignment = "SELECT user FROM holding WHERE `cage_id` = '$cageID'";
    $userAssignmentResult = mysqli_query($con, $queryUserAssignment);
    $userAssignmentRow = mysqli_fetch_assoc($userAssignmentResult);
    $assignedUsers = explode(',', $userAssignmentRow['user']);
    if ($userRole === 'admin' || in_array($currentUserId, $assignedUsers)) {
        $tableRows .= '<a href="hc_edit.php?id=' . rawurlencode($cageID) . '" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Edit Cage">
                            <i class="fas fa-edit"></i>
                       </a>
                       <a href="#" onclick="confirmDeletion(\'' . htmlspecialchars($cageID) . '\')" class="btn btn-danger btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Delete Cage">
                            <i class="fas fa-trash"></i>
                       </a>';
    }
    $tableRows .= '</td></tr>';
}

// Generate the pagination links
$totalQuery = "SELECT COUNT(DISTINCT `cage_id`) AS total FROM holding";
$totalResult = mysqli_query($con, $totalQuery);
$totalRecords = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($totalRecords / $limit);

$paginationLinks = '';
for ($i = 1; $i <= $totalPages; $i++) {
    $activeClass = ($i == $page) ? 'active' : '';
    $paginationLinks .= '<li class="page-item ' . $activeClass . '">
                            <a class="page-link" href="javascript:void(0);" onclick="fetchData(' . $i . ', \'' . htmlspecialchars($searchQuery, ENT_QUOTES) . '\')">
                                ' . $i . '
                            </a>
                         </li>';
}

// Clear the output buffer and avoid any unexpected output before JSON
ob_end_clean();

// Return the generated table rows and pagination links as a JSON response
header('Content-Type: application/json');
echo json_encode([
    'tableRows' => $tableRows,
    'paginationLinks' => $paginationLinks
]);

?>

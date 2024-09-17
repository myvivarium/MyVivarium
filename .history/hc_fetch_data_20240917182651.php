<?php

/**
 * Holding Cage Pagination and Search Script
 * 
 * This script handles fetching and displaying holding cage data with pagination and search functionality. 
 * It generates JSON output containing the table rows and pagination links, which can be dynamically 
 * inserted into an HTML page via JavaScript.
 * 
 */

// Start a new session or resume the existing session
session_start();

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
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
$limit = 10; // Number of entries to show in a page.
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number, default is 1
$offset = ($page - 1) * $limit; // Calculate offset for the SQL query

// Handle the search filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchParam = '%' . $search . '%';

// Handle optional field
$field = isset($_GET['field']) ? $_GET['field'] : '';

// Handle sorting parameters
$sortField = isset($_GET['sortField']) ? $_GET['sortField'] : 'cage_id';
$sortOrder = isset($_GET['sortOrder']) && strtoupper($_GET['sortOrder']) === 'DESC' ? 'DESC' : 'ASC';

// Validate sortField to prevent SQL injection
$allowedSortFields = ['cage_id', 'dob', 'strain_id', 'iacuc', 'quantity', 'sex'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'cage_id';
}

// Prepare the SQL query
$fieldsToSelect = "cage_id";

// Include additional field if selected
switch ($field) {
    case 'age':
        $fieldsToSelect .= ", dob";
        break;
    case 'strain_id':
        $fieldsToSelect .= ", strain_id";
        break;
    case 'iacuc':
        $fieldsToSelect .= ", iacuc";
        break;
    case 'qty':
        $fieldsToSelect .= ", quantity";
        break;
    case 'sex':
        $fieldsToSelect .= ", sex";
        break;
    default:
        // Do nothing
        break;
}

// Prepare the base query for counting total records
$countQuery = "SELECT COUNT(DISTINCT cage_id) as total FROM holding";
$params = [];
$types = '';

if (!empty($search)) {
    $countQuery .= " WHERE cage_id LIKE ?";
    $params[] = $searchParam;
    $types .= 's';
}

$countStmt = $con->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Prepare the main query
$mainQuery = "SELECT DISTINCT $fieldsToSelect FROM holding";
$params = [];
$types = '';

if (!empty($search)) {
    $mainQuery .= " WHERE cage_id LIKE ?";
    $params[] = $searchParam;
    $types .= 's';
}

// Add ORDER BY clause
$mainQuery .= " ORDER BY $sortField $sortOrder";

// Add LIMIT and OFFSET
$mainQuery .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $con->prepare($mainQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Generate the table rows
$tableRows = '';
while ($row = $result->fetch_assoc()) {
    $tableRows .= '<tr>';
    $tableRows .= '<td>' . htmlspecialchars($row['cage_id']) . '</td>';

    // Include the optional field if selected
    if ($field) {
        $additionalData = '';
        switch ($field) {
            case 'age':
                // Calculate age in total days
                if (!empty($row['dob'])) {
                    $dob = new DateTime($row['dob']);
                    $now = new DateTime();
                    $ageInterval = $dob->diff($now);
                    $totalDays = $ageInterval->days; // Total number of days
                    $additionalData = $totalDays . ' Days';
                } else {
                    $additionalData = 'Unknown';
                }
                break;
            case 'strain_id':
                $additionalData = htmlspecialchars($row['strain']);
                break;
            case 'iacuc':
                $additionalData = htmlspecialchars($row['iacuc']);
                break;
            case 'qty':
                $additionalData = htmlspecialchars($row['quantity']);
                break;
            case 'sex':
                $additionalData = htmlspecialchars(ucfirst($row['sex']));
                break;
            default:
                $additionalData = '';
                break;
        }
        $tableRows .= '<td>' . $additionalData . '</td>';
    }

    // Add action buttons
    $tableRows .= '<td>';
    // View Cage button
    $tableRows .= '<a href="hc_view.php?id=' . urlencode($row['cage_id']) . '" class="btn btn-info btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="View Cage">';
    $tableRows .= '<i class="fas fa-eye"></i></a>';
    // Manage Tasks button
    $tableRows .= '<a href="manage_tasks.php?id=' . urlencode($row['cage_id']) . '" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Manage Tasks">';
    $tableRows .= '<i class="fas fa-tasks"></i></a>';
    // Edit Cage button
    $tableRows .= '<a href="hc_edit.php?id=' . urlencode($row['cage_id']) . '" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Edit Cage">';
    $tableRows .= '<i class="fas fa-edit"></i></a>';
    // Delete Cage button
    $tableRows .= '<a href="#" onclick="confirmDeletion(\'' . htmlspecialchars($row['cage_id']) . '\')" class="btn btn-danger btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Delete Cage">';
    $tableRows .= '<i class="fas fa-trash-alt"></i></a>';
    $tableRows .= '</td>';

    $tableRows .= '</tr>';
}

// Generate the pagination links
$paginationLinks = '';
for ($i = 1; $i <= $totalPages; $i++) {
    $activeClass = ($i == $page) ? 'active' : ''; // Highlight the active page
    $paginationLinks .= '<li class="page-item ' . $activeClass . '"><a class="page-link" href="javascript:void(0);" onclick="fetchData(' . $i . ', \'' . htmlspecialchars($search, ENT_QUOTES) . '\')">' . $i . '</a></li>';
}

// Clear the output buffer and avoid any unexpected output before JSON
ob_end_clean();

// Return the generated table rows and pagination links as a JSON response
header('Content-Type: application/json');
echo json_encode([
    'tableRows' => $tableRows,
    'paginationLinks' => $paginationLinks,
    'selectedField' => $field
]);

?>

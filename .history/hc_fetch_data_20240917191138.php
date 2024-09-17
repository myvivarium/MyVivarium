<?php

/**
 * Holding Cage Pagination, Sorting, and Search Script
 * 
 * This script handles fetching and displaying holding cage data with pagination, sorting, and advanced search functionality. 
 * It generates JSON output containing the table rows and pagination links, which can be dynamically 
 * inserted into an HTML page via JavaScript.
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
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit; // Calculate offset for the SQL query

// Initialize variables for sorting and search
$sortFeature = isset($_GET['sort_feature']) ? $_GET['sort_feature'] : '';
$sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
$searchFeature = isset($_GET['search_feature']) ? $_GET['search_feature'] : '';
$searchValue = isset($_GET['search_value']) ? $_GET['search_value'] : '';

// Validate and sanitize inputs
$allowedSortFeatures = ['age', 'strain', 'iacuc', 'mice_quantity', 'sex'];
$allowedSortOrders = ['ASC', 'DESC'];
$allowedSearchFeatures = ['cage_id', 'age', 'strain', 'iacuc', 'mice_quantity', 'sex'];

// Default to empty or ASC if invalid
if (!in_array($sortFeature, $allowedSortFeatures)) {
    $sortFeature = '';
}

if (!in_array($sortOrder, $allowedSortOrders)) {
    $sortOrder = 'ASC';
}

if (!in_array($searchFeature, $allowedSearchFeatures)) {
    $searchFeature = '';
}

// Base query with necessary JOINs to include related data
$baseQuery = "FROM cages 
             LEFT JOIN holding ON cages.cage_id = holding.cage_id 
             LEFT JOIN strains ON holding.strain = strains.str_id 
             LEFT JOIN cage_iacuc ON cages.cage_id = cage_iacuc.cage_id 
             LEFT JOIN iacuc ON cage_iacuc.iacuc_id = iacuc.iacuc_id 
             LEFT JOIN mice ON cages.cage_id = mice.cage_id";

// Apply search filter
$whereClause = "";
$searchClause = "";
$params = [];
$types = '';
if (!empty($searchFeature) && !empty($searchValue)) {
    switch ($searchFeature) {
        case 'cage_id':
            $searchClause = "cages.cage_id LIKE ?";
            $params[] = '%' . $searchValue . '%';
            $types .= 's';
            break;
        case 'age':
            if (is_numeric($searchValue)) {
                // Calculate age in days
                $searchClause = "DATEDIFF(CURDATE(), holding.dob) = ?";
                $params[] = (int)$searchValue;
                $types .= 'i';
            }
            break;
        case 'strain':
            $searchClause = "strains.str_name LIKE ?";
            $params[] = '%' . $searchValue . '%';
            $types .= 's';
            break;
        case 'iacuc':
            $searchClause = "iacuc.iacuc_title LIKE ?";
            $params[] = '%' . $searchValue . '%';
            $types .= 's';
            break;
        case 'mice_quantity':
            if (is_numeric($searchValue)) {
                // Subquery to count mice per cage
                $searchClause = "(SELECT COUNT(*) FROM mice WHERE mice.cage_id = cages.cage_id) = ?";
                $params[] = (int)$searchValue;
                $types .= 'i';
            }
            break;
        case 'sex':
            $searchValueLower = strtolower($searchValue);
            if (in_array($searchValueLower, ['male', 'female'])) {
                $searchClause = "holding.sex = ?";
                $params[] = $searchValueLower;
                $types .= 's';
            }
            break;
    }
    if (!empty($searchClause)) {
        $whereClause = "WHERE " . $searchClause;
    }
}

// Apply sorting
$orderClause = "";
if (!empty($sortFeature)) {
    switch ($sortFeature) {
        case 'age':
            $orderClause = "ORDER BY DATEDIFF(CURDATE(), holding.dob) $sortOrder";
            break;
        case 'strain':
            $orderClause = "ORDER BY strains.str_name $sortOrder";
            break;
        case 'iacuc':
            $orderClause = "ORDER BY iacuc.iacuc_title $sortOrder";
            break;
        case 'mice_quantity':
            $orderClause = "ORDER BY (SELECT COUNT(*) FROM mice WHERE mice.cage_id = cages.cage_id) $sortOrder";
            break;
        case 'sex':
            $orderClause = "ORDER BY holding.sex $sortOrder";
            break;
    }
} else {
    // Default sorting
    $orderClause = "ORDER BY cages.cage_id ASC";
}

// Total records for pagination
$countQuery = "SELECT COUNT(DISTINCT cages.cage_id) as total " . $baseQuery . " " . $whereClause;
$countStmt = $con->prepare($countQuery);
if ($countStmt && !empty($searchClause)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = 0;
if ($countRow = $countResult->fetch_assoc()) {
    $totalRecords = $countRow['total'];
}
$totalPages = ceil($totalRecords / $limit);
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

// Fetch the records with pagination
$fetchQuery = "SELECT 
                cages.cage_id, 
                holding.dob, 
                holding.sex, 
                strains.str_name, 
                iacuc.iacuc_title,
                COUNT(mice.id) as mice_count
               " . $baseQuery . " " . $whereClause . " 
               GROUP BY cages.cage_id 
               $orderClause 
               LIMIT ? OFFSET ?";

// Prepare the statement
$fetchStmt = $con->prepare($fetchQuery);

// Bind parameters
if ($fetchStmt) {
    if (!empty($searchClause)) {
        // Add limit and offset to params
        $fetchStmt->bind_param($types . 'ii', ...$params, $limit, $offset);
    } else {
        $fetchStmt->bind_param('ii', $limit, $offset);
    }
    $fetchStmt->execute();
    $fetchResult = $fetchStmt->get_result();
} else {
    // Handle prepare error
    // Return empty results or error
    echo json_encode([
        'tableRows' => '<tr><td colspan="7" class="text-center">Error fetching data.</td></tr>',
        'paginationLinks' => ''
    ]);
    exit;
}

// Generate the table rows
$tableRows = '';
if ($fetchResult->num_rows > 0) {
    while ($row = $fetchResult->fetch_assoc()) {
        $cageID = htmlspecialchars($row['cage_id']);
        // Calculate age in days
        $dob = $row['dob'];
        if ($dob) {
            $ageDays = (int)( (strtotime(date('Y-m-d')) - strtotime($dob)) / (60 * 60 * 24) );
        } else {
            $ageDays = 'N/A';
        }
        $strain = !empty($row['str_name']) ? htmlspecialchars($row['str_name']) : 'N/A';
        $iacuc = !empty($row['iacuc_title']) ? htmlspecialchars($row['iacuc_title']) : 'N/A';
        $miceQuantity = (int)$row['mice_count'];
        $sex = !empty($row['sex']) ? ucfirst(htmlspecialchars($row['sex'])) : 'N/A';

        $tableRows .= '<tr>';
        $tableRows .= '<td>' . $cageID . '</td>';
        $tableRows .= '<td>' . $ageDays . '</td>';
        $tableRows .= '<td>' . $strain . '</td>';
        $tableRows .= '<td>' . $iacuc . '</td>';
        $tableRows .= '<td>' . $miceQuantity . '</td>';
        $tableRows .= '<td>' . $sex . '</td>';
        $tableRows .= '<td>';
        $tableRows .= '<div class="action-icons" style="white-space: nowrap;">';
        $tableRows .= '<a href="hc_view.php?id=' . rawurlencode($cageID) . '&page=' . $page . '&sort_feature=' . urlencode($sortFeature) . '&sort_order=' . urlencode($sortOrder) . '&search_feature=' . urlencode($searchFeature) . '&search_value=' . urlencode($searchValue) . '" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="View Cage"><i class="fas fa-eye"></i></a>';
        $tableRows .= '<a href="manage_tasks.php?id=' . rawurlencode($cageID) . '&page=' . $page . '&sort_feature=' . urlencode($sortFeature) . '&sort_order=' . urlencode($sortOrder) . '&search_feature=' . urlencode($searchFeature) . '&search_value=' . urlencode($searchValue) . '" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Manage Tasks"><i class="fas fa-tasks"></i></a>';

        // Fetch assigned users for this cage (from cage_users table)
        $assignedUsersQuery = "SELECT user_id FROM cage_users WHERE cage_id = ?";
        $assignedUsersStmt = $con->prepare($assignedUsersQuery);
        if ($assignedUsersStmt) {
            $assignedUsersStmt->bind_param('s', $cageID);
            $assignedUsersStmt->execute();
            $assignedUsersResult = $assignedUsersStmt->get_result();
            $assignedUsers = [];
            while ($userRow = $assignedUsersResult->fetch_assoc()) {
                $assignedUsers[] = $userRow['user_id'];
            }
            $assignedUsersStmt->close();
        }

        // Check if the user is an admin or assigned to this cage
        if ($userRole === 'admin' || in_array($currentUserId, $assignedUsers)) {
            $tableRows .= '<a href="hc_edit.php?id=' . rawurlencode($cageID) . '&page=' . $page . '&sort_feature=' . urlencode($sortFeature) . '&sort_order=' . urlencode($sortOrder) . '&search_feature=' . urlencode($searchFeature) . '&search_value=' . urlencode($searchValue) . '" class="btn btn-secondary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Edit Cage"><i class="fas fa-edit"></i></a>';
            $tableRows .= '<a href="#" onclick="confirmDeletion(\'' . $cageID . '\')" class="btn btn-danger btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Delete Cage"><i class="fas fa-trash"></i></a>';
        }

        $tableRows .= '</div>';
        $tableRows .= '</td>';
        $tableRows .= '</tr>';
    }
} else {
    $tableRows .= '<tr><td colspan="7" class="text-center">No cages found matching the criteria.</td></tr>';
}

// Generate the pagination links
$paginationLinks = '';
if ($totalPages > 1) {
    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = ($i == $page) ? 'active' : '';
        $paginationLinks .= '<li class="page-item ' . $activeClass . '"><a class="page-link" href="javascript:void(0);" onclick="fetchData(' . $i . ', \'' . htmlspecialchars($sortFeature, ENT_QUOTES) . '\', \'' . htmlspecialchars($sortOrder, ENT_QUOTES) . '\', \'' . htmlspecialchars($searchFeature, ENT_QUOTES) . '\', \'' . htmlspecialchars($searchValue, ENT_QUOTES) . '\')">' . $i . '</a></li>';
    }
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

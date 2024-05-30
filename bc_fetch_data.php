<?php
session_start();
require 'dbcon.php';

// Pagination variables
$limit = 15; // Number of entries to show in a page.
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle the search filter
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = urldecode($_GET['search']); // Decode the search parameter
}

// Fetch the distinct cage IDs with pagination
$query = "SELECT DISTINCT `cage_id` FROM bc_basic";
if (!empty($searchQuery)) {
    $query .= " WHERE `cage_id` LIKE '%$searchQuery%'";
}
$totalResult = mysqli_query($con, $query);
$totalRecords = mysqli_num_rows($totalResult);
$totalPages = ceil($totalRecords / $limit);

$query .= " LIMIT $limit OFFSET $offset";
$result = mysqli_query($con, $query);

// Generate the table rows
$tableRows = '';
while ($row = mysqli_fetch_assoc($result)) {
    $cageID = $row['cage_id'];
    $query = "SELECT * FROM bc_basic WHERE `cage_id` = '$cageID'";
    $cageResult = mysqli_query($con, $query);
    $numRows = mysqli_num_rows($cageResult);
    $firstRow = true;
    while ($breedingcage = mysqli_fetch_assoc($cageResult)) {
        $tableRows .= '<tr>';
        if ($firstRow) {
            $tableRows .= '<td style="width: 50%;">' . htmlspecialchars($breedingcage['cage_id']) . '</td>';
            $firstRow = false;
        }
        $tableRows .= '<td class="action-icons" style="width: 50%; white-space: nowrap;">
                        <a href="bc_view.php?id=' . rawurlencode($breedingcage['cage_id']) . '" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="View Cage"><i class="fas fa-eye"></i></a>
                        <a href="bc_edit.php?id=' . rawurlencode($breedingcage['cage_id']) . '" class="btn btn-secondary btn-sm btn-icon"><i class="fas fa-edit" data-toggle="tooltip" data-placement="top" title="Edit Cage"></i></a>';
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $tableRows .= '<a href="#" onclick="confirmDeletion(\'' . htmlspecialchars($breedingcage['cage_id']) . '\')" class="btn btn-danger btn-sm btn-icon"><i class="fas fa-trash" data-toggle="tooltip" data-placement="top" title="Delete Cage"></i></a>';
        }
        $tableRows .= '</td></tr>';
    }
}

// Generate the pagination links
$paginationLinks = '';
for ($i = 1; $i <= $totalPages; $i++) {
    $activeClass = ($i == $page) ? 'active' : '';
    $paginationLinks .= '<li class="page-item ' . $activeClass . '"><a class="page-link" href="javascript:void(0);" onclick="fetchData(' . $i . ')">' . $i . '</a></li>';
}

echo json_encode([
    'tableRows' => $tableRows,
    'paginationLinks' => $paginationLinks
]);
?>
<?php
session_start();
require 'dbcon.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "SELECT * FROM bc_litter WHERE `id` = '$id'";
    $result = mysqli_query($con, $query);
    $litter = mysqli_fetch_assoc($result);
    $cage_id = $litter['cage_id'];

    // Prepared statement to prevent SQL Injection
    $deleteQuery = $con->prepare("DELETE FROM bc_litter WHERE `id` = ?");
    $deleteQuery->bind_param("i", $id); // 'i' specifies that the id is an integer

    if ($deleteQuery->execute()) {
        $_SESSION['message'] = 'Litter data deleted successfully.';
        // Assuming you want to redirect to a page after deletion
        header("Location: bc_view.php?id=" . rawurlencode($cage_id));
        exit();
    } else {
        // Handle error, e.g., deletion failed
        $_SESSION['message'] = 'Error occurred in deletion.';
        header("Location: bc_view.php?id=" . rawurlencode($cage_id));
        exit();
    }
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: bc_view.php?id=" . rawurlencode($cage_id));
    exit();
}

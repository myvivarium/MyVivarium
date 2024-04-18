<?php
session_start();
require 'dbcon.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $fileQuery = "SELECT * FROM files WHERE id = '$id'";
    $file = $con->query($fileQuery)->fetch_assoc();
    if (file_exists($file['file_path'])) {
        unlink($file['file_path']); // Delete the file from the server
        $delete = "DELETE FROM files WHERE id = '$id'";
        $con->query($delete);
        $url = urldecode($_GET['url']).".php?id=".$id;
        header("Location: $url");
    }
}

?>
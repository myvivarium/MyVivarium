<?php
session_start();
require 'dbcon.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $cageID = $_POST['cageID'];
    $piName = $_POST['piName'];
    $strain = $_POST['strain'];
    $iacuc = $_POST['iacuc'];
    $user = $_POST['user'];
    $qty = $_POST['qty'];
    $dob = $_POST['dob'];
    $sex = $_POST['sex'];
    $parentCage = $_POST['parentCage'];
    $mouseIDs = $_POST['mouseIDs'];
    $genotypes = $_POST['genotypes'];
    $maintanenceNotes = $_POST['maintanenceNotes']; // Added this line

    // Insert new cage holding into the database
    $query = "INSERT INTO holdingcage (`cage id`, `pi name`, `strain`, `IACUC`, `user`, `qty`, `DOB`, `sex`, `parentcage`, `mouse id`, `genotype`, `MaintanenceNotes`)
                VALUES ('$cageID', '$piName', '$strain', '$iacuc', '$user', '$qty', '$dob', '$sex', '$parentCage', '$mouseIDs', '$genotypes', '$maintanenceNotes')";
    $result = mysqli_query($con, $query);

    // Check if the insertion was successful
    if ($result) {
        $_SESSION['success'] = "New cage holding added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add new cage holding.";
    }

    // Redirect back to the main page
    header("Location: home.php");
    exit();
}
?>


<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

    <title>Add New Cage Holding</title>

</head>

<body>

    <div class="container mt-4">
        <h4>Add New Cage Holding</h4>

        <?php include('message.php'); ?>

        <form method="POST">

            <div class="mb-3">
                <label for="cageID" class="form-label">Cage ID</label>
                <input type="text" class="form-control" id="cageID" name="cageID" required>
            </div>

            <div class="mb-3">
                <label for="piName" class="form-label">PI Name</label>
                <input type="text" class="form-control" id="piName" name="piName" required>
            </div>

            <div class="mb-3">
                <label for="strain" class="form-label">Strain</label>
                <input type="text" class="form-control" id="strain" name="strain" required>
            </div>

            <div class="mb-3">
                <label for="iacuc" class="form-label">IACUC</label>
                <input type="text" class="form-control" id="iacuc" name="iacuc" required>
            </div>

            <div class="mb-3">
                <label for="user" class="form-label">User</label>
                <input type="text" class="form-control" id="user" name="user" required>
            </div>

            <div class="mb-3">
                <label for="qty" class="form-label">Qty</label>
                <input type="number" class="form-control" id="qty" name="qty" required>
            </div>

            <div class="mb-3">
                <label for="dob" class="form-label">DOB</label>
                <input type="date" class="form-control" id="dob" name="dob" required>
            </div>

            <div class="mb-3">
                <label for="sex" class="form-label">Sex</label>
                <input type="text" class="form-control" id="sex" name="sex" required>
            </div>

            <div class="mb-3">
                <label for="parentCage" class="form-label">Parent Cage</label>
                <input type="text" class="form-control" id="parentCage" name="parentCage" required>
            </div>

            <div class="mb-3">
                <label for="mouseIDs" class="form-label">Mouse ID (Separated by comma)</label>
                <input type="text" class="form-control" id="mouseIDs" name="mouseIDs" required>
            </div>

            <div class="mb-3">
                <label for="genotypes" class="form-label">Genotype/Notes (Separated by comma)</label>
                <input type="text" class="form-control" id="genotypes" name="genotypes" required>
            </div>
            <div class="mb-3">
                <label for="maintanenceNotes" class="form-label">Maintenance Notes</label>
                <textarea class="form-control" id="maintanenceNotes" name="maintanenceNotes" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Add</button>

        </form>
        <div style="text-align: center;">
            <a href="home.php" class="btn btn-secondary">Go Back</a>
        </div>
    </div>

</body>

</html>

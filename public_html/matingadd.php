<?php
session_start();
require 'dbcon.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $cageID = $_POST['cageID'];
    $piName = $_POST['pi_name'];
    $cross = $_POST['cross'];
    $iacuc = $_POST['iacuc'];
    $user = $_POST['user'];
    $maleID = $_POST['male_id'];
    $maleDOB = $_POST['male_DOB'];
    $DOM = $_POST['DOM'];
    $femaleID = $_POST['female_id'];
    $femaleDOB = $_POST['female_DOB'];
    $litterDOB = $_POST['litter_DOB'];
    $pupsAD = $_POST['pups_ad'];
    $male = $_POST['male'];
    $female = $_POST['female'];
    $remarks = $_POST['remarks'];

    // Insert new cage holding into the database
    $query = "INSERT INTO matingcage (`cage id`, `pi name`, `cross`, `IACUC`, `user`, `male id`, `male DOB`, `DOM`, `female id`, `female DOB`, `litter DOB`, `pups ad`, `male`, `female`, `remarks`)
                VALUES ('$cageID', '$piName', '$cross', '$iacuc', '$user', '$maleID', '$maleDOB', '$DOM', '$femaleID', '$femaleDOB', '$litterDOB', '$pupsAD', '$male', '$female', '$remarks')";
    $result = mysqli_query($con, $query);

    // Check if the insertion was successful
    if ($result) {
        $_SESSION['success'] = "New cage holding added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add new cage holding.";
    }

    // Redirect back to the main page
    header("Location: mating.php");
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

    <title>Add New Mating Cage </title>

</head>

<body>

    <div class="container mt-4">
        <h4>Add New Mating Holding</h4>

        <?php include('message.php'); ?>

        <form method="POST">

            <div class="mb-3">
                <label for="cageID" class="form-label">Cage ID</label>
                <input type="text" class="form-control" id="cageID" name="cageID" required>
            </div>

            <div class="mb-3">
                <label for="pi_name" class="form-label">PI Name</label>
                <input type="text" class="form-control" id="pi_name" name="pi_name" required>
            </div>

            <div class="mb-3">
                <label for="cross" class="form-label">Cross</label>
                <input type="text" class="form-control" id="cross" name="cross" required>
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
                <label for="male_id" class="form-label">Male ID</label>
                <input type="text" class="form-control" id="male_id" name="male_id" required>
            </div>

            <div class="mb-3">
                <label for="male_DOB" class="form-label">Male DOB</label>
                <input type="date" class="form-control" id="male_DOB" name="male_DOB" required>
            </div>

            <div class="mb-3">
                <label for="female_id" class="form-label">female ID</label>
                <input type="text" class="form-control" id="female_id" name="female_id" required>
            </div>

            <div class="mb-3">
                <label for="female_DOB" class="form-label">Female DOB</label>
                <input type="date" class="form-control" id="female_DOB" name="female_DOB" required>
            </div>

            <div class="mb-3">
                <label for="DOM" class="form-label">DOM (Separate with Commas)</label>
                <input type="text" class="form-control" id="DOM" name="DOM" required>
            </div>
            <div class="mb-3">
                <label for="litter_DOB" class="form-label">Litter DOB (Separate with Commas)</label>
                <input type="text" class="form-control" id="litter_DOB" name="litter_DOB" required>
            </div>
            <div class="mb-3">
                <label for="pups_ad" class="form-label">Pups (A/D) (Separate with Commas)</label>
                <input type="text" class="form-control" id="pups_ad" name="pups_ad" required>
            </div>
            <div class="mb-3">
                <label for="male" class="form-label">Male (Separate with Commas)</label>
                <input type="text" class="form-control" id="male" name="male" required>
            </div>
            <div class="mb-3">
                <label for="female" class="form-label">Female (Separate with Commas)</label>
                <input type="text" class="form-control" id="female" name="female" required>
            </div>
            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks (Separate with Commas)</label>
                <input type="text" class="form-control" id="remarks" name="remarks" required>
            </div>

            <button type="submit" class="btn btn-primary">Add</button>

        </form>
        <div style="text-align: center;">
            <a href="mating.php" class="btn btn-secondary">Go Back</a>
        </div>
    </div>

</body>

</html>

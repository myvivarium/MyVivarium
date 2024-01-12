<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Query to retrieve options where role is 'PI'
$query1 = "SELECT name FROM users WHERE position = 'Principal Investigator' AND status = 'approved'";
$result1 = $con->query($query1);

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the breedingcage record with the specified ID
    $query = "SELECT * FROM bc_basic WHERE `cage_id` = '$id'";
    $result = mysqli_query($con, $query);

    if (mysqli_num_rows($result) === 1) {
        $breedingcage = mysqli_fetch_assoc($result);

        // Process the form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Retrieve form data
            // Retrieve and sanitize form data
            $cage_id = mysqli_real_escape_string($con, $_POST['cage_id']);
            $pi_name = mysqli_real_escape_string($con, $_POST['pi_name']);
            $cross = mysqli_real_escape_string($con, $_POST['cross']);
            $iacuc = mysqli_real_escape_string($con, $_POST['iacuc']);
            $user = mysqli_real_escape_string($con, $_POST['user']);
            $male_id = mysqli_real_escape_string($con, $_POST['male_id']);
            $female_id = mysqli_real_escape_string($con, $_POST['female_id']);
            $male_dob = mysqli_real_escape_string($con, $_POST['male_dob']);
            $female_dob = mysqli_real_escape_string($con, $_POST['female_dob']);
            $remarks = mysqli_real_escape_string($con, $_POST['remarks']);

            // Prepare the update query with placeholders
            $updateQuery = $con->prepare("UPDATE bc_basic SET 
                                    `cage_id` = ?, 
                                    `pi_name` = ?, 
                                    `cross` = ?, 
                                    `iacuc` = ?, 
                                    `user` = ?, 
                                    `male_id` = ?, 
                                    `female_id` = ?, 
                                    `male_dob` = ?, 
                                    `female_dob` = ?, 
                                    `remarks` = ? 
                                    WHERE `cage_id` = ?");

            // Bind parameters
            $updateQuery->bind_param("sssssssssss", $cage_id, $pi_name, $cross, $iacuc, $user, $male_id, $female_id, $male_dob, $female_dob, $remarks, $id);

            // Execute the statement and check if it was successful
            if ($updateQuery->execute()) {
                $_SESSION['message'] = 'Entry updated successfully.';
            } else {
                $_SESSION['error'] = 'Update failed: ' . $updateQuery->error;
            }

            // Close the prepared statement
            $updateQuery->close();

            header("Location: bc_dash.php");
            exit();
        }
    } else {
        $_SESSION['message'] = 'Invalid ID.';
        header("Location: bc_dash.php");
        exit();
    }
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: bc_dash.php");
    exit();
}

require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <script>
        function goBack() {
            window.history.back();
            //window.location.href = 'specific_php_file.php';
        }
    </script>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Bootstrap JS for Dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <title>Edit Breeding Cage</title>

</head>

<body>

    <div class="container mt-4">

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Breeding Cage</h4>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="bc_edit.php?id=<?= $id; ?>">

                            <div class="mb-3">
                                <label for="cage_id" class="form-label">Cage ID</label>
                                <input type="text" class="form-control" id="cage_id" name="cage_id" value="<?= $breedingcage['cage_id']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="pi_name" class="form-label">PI Name</label>
                                <select class="form-control" id="pi_name" name="pi_name" required>
                                    <option value="<?= htmlspecialchars($breedingcage['pi_name']); ?>" selected><?= htmlspecialchars($breedingcage['pi_name']); ?></option>
                                    <?php
                                    while ($row = $result1->fetch_assoc()) {
                                        if ($row['name'] != $breedingcage['pi_name']) {
                                            echo "<option value='" . htmlspecialchars($row['name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="cross" class="form-label">Cross</label>
                                <input type="text" class="form-control" id="cross" name="cross" value="<?= $breedingcage['cross']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="iacuc" class="form-label">IACUC</label>
                                <input type="text" class="form-control" id="iacuc" name="iacuc" value="<?= $breedingcage['iacuc']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="user" class="form-label">User</label>
                                <input type="text" class="form-control" id="user" name="user" value="<?= $breedingcage['user']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="male_id" class="form-label">Male ID</label>
                                <input type="text" class="form-control" id="male_id" name="male_id" value="<?= $breedingcage['male_id']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="female_id" class="form-label">Female ID</label>
                                <input type="text" class="form-control" id="female_id" name="female_id" value="<?= $breedingcage['female_id']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="male_dob" class="form-label">Male DOB</label>
                                <input type="date" class="form-control" id="male_dob" name="male_dob" value="<?= $breedingcage['male_dob']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="female_dob" class="form-label">Female DOB</label>
                                <input type="date" class="form-control" id="female_dob" name="female_dob" value="<?= $breedingcage['female_dob']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <input type="text" class="form-control" id="remarks" name="remarks" value="<?= $breedingcage['remarks']; ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-primary" onclick="goBack()">Go Back</button>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br>
    <?php include 'footer.php'; ?>
</body>

</html>
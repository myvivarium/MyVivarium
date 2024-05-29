<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve form data
        $cage_id = $id; // Assuming $id is already sanitized
        $dom = $_POST['dom'];
        $litter_dob = $_POST['litter_dob'] ?: NULL;
        $pups_alive = $_POST['pups_alive'];
        $pups_dead = $_POST['pups_dead'];
        $pups_male = $_POST['pups_male'];
        $pups_female = $_POST['pups_female'];
        $remarks = $_POST['remarks'];

        // Prepare the insert query with placeholders 
        $query1 = $con->prepare("INSERT INTO bc_litter (`cage_id`, `dom`, `litter_dob`, `pups_alive`, `pups_dead`, `pups_male`, `pups_female`, `remarks`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        // Bind parameters
        $query1->bind_param("ssssssss", $cage_id, $dom, $litter_dob, $pups_alive, $pups_dead, $pups_male, $pups_female, $remarks);

        // Execute the statement and check if it was successful
        if ($query1->execute()) {
            $_SESSION['message'] = "New litter data added successfully.";
        } else {
            $_SESSION['error'] = "Failed to add new litter data: " . $query1->error;
        }

        // Close the prepared statement
        $query1->close();

        // Redirect back to the main page
        header("Location: bc_view.php?id=" . rawurlencode($id));
        exit();
    }
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: bc_view.php?id=" . rawurlencode($id));
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

    <title>Add New Litter Data | <?php echo htmlspecialchars($labName); ?></title>

</head>

<body>

    <div class="container mt-4">
        <h4>Add New Litter Data
            <?= $id ?>
        </h4>

        <?php include('message.php'); ?>

        <form method="POST">

            <div class="mb-3">
                <label for="dom" class="form-label">DOM</label>
                <input type="date" class="form-control" id="dom" name="dom" required>
            </div>

            <div class="mb-3">
                <label for="litter_dob" class="form-label">Litter DOB</label>
                <input type="date" class="form-control" id="litter_dob" name="litter_dob">
            </div>

            <div class="mb-3">
                <label for="pups_alive" class="form-label">Pups Alive</label>
                <input type="number" class="form-control" id="pups_alive" name="pups_alive" required min="0" step="1">
            </div>

            <div class="mb-3">
                <label for="pups_dead" class="form-label">Pups Dead</label>
                <input type="number" class="form-control" id="pups_dead" name="pups_dead" required min="0" step="1">
            </div>

            <div class="mb-3">
                <label for="pups_male" class="form-label">Pups Male</label>
                <input type="number" class="form-control" id="pups_male" name="pups_male" required min="0" step="1">
            </div>

            <div class="mb-3">
                <label for="pups_female" class="form-label">Pups Female</label>
                <input type="number" class="form-control" id="pups_female" name="pups_female" required min="0" step="1">
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <input type="text" class="form-control" id="remarks" name="remarks">
            </div>

            <button type="submit" class="btn btn-primary">Add Data</button>
            <button type="button" class="btn btn-primary" onclick="goBack()">Go Back</button>

        </form>

    </div>

    <br>
    <?php include 'footer.php'; ?>
</body>

</html>
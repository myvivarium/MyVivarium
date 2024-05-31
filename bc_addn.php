<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Query to retrieve options where role is 'PI'
$query = "SELECT name FROM users WHERE position = 'Principal Investigator' AND status = 'approved'";
$result = $con->query($query);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $cage_id = $_POST['cage_id'];
    $pi_name = $_POST['pi_name'];
    $cross = $_POST['cross'];
    $iacuc = $_POST['iacuc'];
    $user = $_POST['user'];
    $male_id = $_POST['male_id'];
    $female_id = $_POST['female_id'];
    $male_dob = $_POST['male_dob'];
    $female_dob = $_POST['female_dob'];
    $remarks = $_POST['remarks'];

    // Check if the cage_id already exists
    $check_query = $con->prepare("SELECT * FROM bc_basic WHERE cage_id = ?");
    $check_query->bind_param("s", $cage_id);
    $check_query->execute();
    $check_result = $check_query->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = "Cage ID '$cage_id' already exists. Please use a different Cage ID.";
    } else {
        // Prepare the insert query with placeholders
        $insert_query = $con->prepare("INSERT INTO bc_basic (`cage_id`, `pi_name`, `cross`, `iacuc`, `user`, `male_id`, `female_id`, `male_dob`, `female_dob`, `remarks`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Bind parameters
        $insert_query->bind_param("ssssssssss", $cage_id, $pi_name, $cross, $iacuc, $user, $male_id, $female_id, $male_dob, $female_dob, $remarks);

        // Execute the statement and check if it was successful
        if ($insert_query->execute()) {
            $_SESSION['message'] = "New breeding cage added successfully.";
        } else {
            $_SESSION['error'] = "Failed to add new breeding cage.";
        }

        // Close the prepared statement
        $insert_query->close();
    }

    // Close the check query prepared statement
    $check_query->close();

    // Redirect back to the main page
    header("Location: bc_dash.php");
    exit();
}

require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <title>Add New Breeding Cage | <?php echo htmlspecialchars($labName); ?></title>
    <style>
        .container {
            max-width: 800px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: auto;
        }
    </style>
    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</head>

<body>

    <div class="container mt-4">
        <h4>Add New Breeding Cage</h4>

        <?php include('message.php'); ?>

        <form method="POST">

            <div class="mb-3">
                <label for="cage_id" class="form-label">Cage ID</label>
                <input type="text" class="form-control" id="cage_id" name="cage_id" required>
            </div>

            <div class="mb-3">
                <label for="pi_name" class="form-label">PI Name</label>
                <select class="form-control" id="pi_name" name="pi_name" required>
                    <option value="" disabled selected>Select PI</option>
                    <?php
                    // Populate dropdown with options from the database
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . htmlspecialchars($row['name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="cross" class="form-label">Cross</label>
                <input type="text" class="form-control" id="cross" name="cross" required>
            </div>

            <div class="mb-3">
                <label for="iacuc" class="form-label">IACUC</label>
                <input type="text" class="form-control" id="iacuc" name="iacuc">
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
                <label for="female_id" class="form-label">Female ID</label>
                <input type="text" class="form-control" id="female_id" name="female_id" required>
            </div>

            <div class="mb-3">
                <label for="male_dob" class="form-label">Male DOB</label>
                <input type="date" class="form-control" id="male_dob" name="male_dob" required>
            </div>

            <div class="mb-3">
                <label for="female_dob" class="form-label">Female DOB</label>
                <input type="date" class="form-control" id="female_dob" name="female_dob" required>
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <input type="text" class="form-control" id="remarks" name="remarks">
            </div>

            <button type="submit" class="btn btn-primary">Add Cage</button>
            <button type="button" class="btn btn-secondary" onclick="goBack()">Go Back</button>

        </form>

    </div>

    <br>
    <?php include 'footer.php'; ?>
</body>

</html>

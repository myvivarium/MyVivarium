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
    $strain = $_POST['strain'];
    $iacuc = $_POST['iacuc'];
    $user = $_POST['user'];
    $qty = $_POST['qty'];
    $dob = $_POST['dob'];
    $sex = $_POST['sex'];
    $parent_cg = $_POST['parent_cg'];
    $remarks = $_POST['remarks'];
    $mouse_id_1 = $_POST['mouse_id_1'];
    $genotype_1 = $_POST['genotype_1'];
    $notes_1 = $_POST['notes_1'];
    $mouse_id_2 = $_POST['mouse_id_2'];
    $genotype_2 = $_POST['genotype_2'];
    $notes_2 = $_POST['notes_2'];
    $mouse_id_3 = $_POST['mouse_id_3'];
    $genotype_3 = $_POST['genotype_3'];
    $notes_3 = $_POST['notes_3'];
    $mouse_id_4 = $_POST['mouse_id_4'];
    $genotype_4 = $_POST['genotype_4'];
    $notes_4 = $_POST['notes_4'];
    $mouse_id_5 = $_POST['mouse_id_5'];
    $genotype_5 = $_POST['genotype_5'];
    $notes_5 = $_POST['notes_5'];

    // Check if the cage_id already exists
    $check_query = "SELECT * FROM hc_basic WHERE cage_id = '$cage_id'";
    $check_result = mysqli_query($con, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // Cage_id already exists, throw an error
        $_SESSION['message'] = "Cage ID '$cage_id' already exists. Please use a different Cage ID.";
    } else {
        // Prepare the SQL statement with placeholders
        $query1 = "INSERT INTO hc_basic (`cage_id`, `pi_name`, `strain`, `iacuc`, `user`, `qty`, `dob`, `sex`, `parent_cg`, `remarks`, `mouse_id_1`, `genotype_1`, `notes_1`, `mouse_id_2`, `genotype_2`, `notes_2`, `mouse_id_3`, `genotype_3`, `notes_3`, `mouse_id_4`, `genotype_4`, `notes_4`, `mouse_id_5`, `genotype_5`, `notes_5`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $con->prepare($query1);

        // Bind parameters
        $stmt->bind_param("sssssisssssssssssssssssss", $cage_id, $pi_name, $strain, $iacuc, $user, $qty, $dob, $sex, $parent_cg, $remarks, $mouse_id_1, $genotype_1, $notes_1, $mouse_id_2, $genotype_2, $notes_2, $mouse_id_3, $genotype_3, $notes_3, $mouse_id_4, $genotype_4, $notes_4, $mouse_id_5, $genotype_5, $notes_5);

        // Execute the statement
        $result1 = $stmt->execute();

        // Check if the insertion was successful
        if ($result1) {
            $_SESSION['message'] = "New holding cage added successfully.";
        } else {
            $_SESSION['message'] = "Failed to add new holding cage: ";
        }

        // Close the prepared statement
        $stmt->close();
    }

    // Redirect back to the main page
    header("Location: hc_dash.php");
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Bootstrap JS for Dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <title>Add New Holding Cage | <?php echo htmlspecialchars($labName); ?></title>

</head>

<body>

    <div class="container mt-4">
        <h4>Add New Holding Cage</h4>

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
                        echo "<option value='" . $row['name'] . "'>" . $row['name'] . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="strain" class="form-label">Strain</label>
                <input type="text" class="form-control" id="strain" name="strain" required>
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
                <label for="qty" class="form-label">Qty</label>
                <select class="form-control" id="qty" name="qty" required>
                    <option value="" disabled selected>Select Number</option>
                    <?php
                    // Generate options dynamically
                    for ($i = 1; $i <= 5; $i++) {
                        echo "<option value=\"$i\">$i</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="dob" class="form-label">DOB</label>
                <input type="date" class="form-control" id="dob" name="dob" required>
            </div>

            <div class="mb-3">
                <label for="sex" class="form-label">Sex</label>
                <select class="form-control" id="sex" name="sex" required>
                    <option value="" disabled selected>Select Sex</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="parent_cg" class="form-label">Parent Cage</label>
                <input type="text" class="form-control" id="parent_cg" name="parent_cg" required>
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <input type="text" class="form-control" id="remarks" name="remarks">
            </div>

            <h4>Mouse #1</h4>
            <div class="mb-3">
                <label for="mouse_id_1" class="form-label">Mouse ID</label>
                <input type="text" class="form-control" id="mouse_id_1" name="mouse_id_1">
            </div>

            <div class="mb-3">
                <label for="genotype_1" class="form-label">Genotype</label>
                <input type="text" class="form-control" id="genotype_1" name="genotype_1">
            </div>

            <div class="mb-3">
                <label for="notes_1" class="form-label">Maintenance Notes</label>
                <input type="text" class="form-control" id="notes_1" name="notes_1">
            </div>

            <h4>Mouse #2</h4>
            <div class="mb-3">
                <label for="mouse_id_2" class="form-label">Mouse ID</label>
                <input type="text" class="form-control" id="mouse_id_2" name="mouse_id_2">
            </div>

            <div class="mb-3">
                <label for="genotype_2" class="form-label">Genotype</label>
                <input type="text" class="form-control" id="genotype_2" name="genotype_2">
            </div>

            <div class="mb-3">
                <label for="notes_2" class="form-label">Maintenance Notes</label>
                <input type="text" class="form-control" id="notes_2" name="notes_2">
            </div>

            <h4>Mouse #3</h4>
            <div class="mb-3">
                <label for="mouse_id_3" class="form-label">Mouse ID</label>
                <input type="text" class="form-control" id="mouse_id_3" name="mouse_id_3">
            </div>

            <div class="mb-3">
                <label for="genotype_3" class="form-label">Genotype</label>
                <input type="text" class="form-control" id="genotype_3" name="genotype_3">
            </div>

            <div class="mb-3">
                <label for="notes_3" class="form-label">Maintenance Notes</label>
                <input type="text" class="form-control" id="notes_3" name="notes_3">
            </div>

            <h4>Mouse #4</h4>
            <div class="mb-3">
                <label for="mouse_id_4" class="form-label">Mouse ID</label>
                <input type="text" class="form-control" id="mouse_id_4" name="mouse_id_4">
            </div>

            <div class="mb-3">
                <label for="genotype_4" class="form-label">Genotype</label>
                <input type="text" class="form-control" id="genotype_4" name="genotype_4">
            </div>

            <div class="mb-3">
                <label for="notes_4" class="form-label">Maintenance Notes</label>
                <input type="text" class="form-control" id="notes_4" name="notes_4">
            </div>

            <h4>Mouse #5</h4>
            <div class="mb-3">
                <label for="mouse_id_5" class="form-label">Mouse ID</label>
                <input type="text" class="form-control" id="mouse_id_5" name="mouse_id_5">
            </div>

            <div class="mb-3">
                <label for="genotype_5" class="form-label">Genotype</label>
                <input type="text" class="form-control" id="genotype_5" name="genotype_5">
            </div>

            <div class="mb-3">
                <label for="notes_5" class="form-label">Maintenance Notes</label>
                <input type="text" class="form-control" id="notes_5" name="notes_5">
            </div>

            <button type="submit" class="btn btn-primary">Add Cage</button>
            <button type="button" class="btn btn-primary" onclick="goBack()">Go Back</button>

        </form>

        <div style="text-align: center;">
            <a href="hc_dash.php" class="btn btn-secondary">Dashboard</a>
        </div>
    </div>

    <br>
    <?php include 'footer.php'; ?>
</body>

</html>
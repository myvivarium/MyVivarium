<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Query to retrieve options where role is 'Principal Investigator'
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
    $mouse_data = [];

    for ($i = 1; $i <= 5; $i++) {
        $mouse_data[] = [
            'mouse_id' => $_POST["mouse_id_$i"] ?? null,
            'genotype' => $_POST["genotype_$i"] ?? null,
            'notes' => $_POST["notes_$i"] ?? null
        ];
    }

    // Check if the cage_id already exists in hc_basic or bc_basic
    $check_query_hc = "SELECT * FROM hc_basic WHERE cage_id = '$cage_id'";
    $check_query_bc = "SELECT * FROM bc_basic WHERE cage_id = '$cage_id'";
    $check_result_hc = mysqli_query($con, $check_query_hc);
    $check_result_bc = mysqli_query($con, $check_query_bc);

    if (mysqli_num_rows($check_result_hc) > 0 || mysqli_num_rows($check_result_bc) > 0) {
        // Cage_id already exists, throw an error
        $_SESSION['message'] = "Cage ID '$cage_id' already exists. Please use a different Cage ID.";
    } else {
        // Prepare the SQL statement with placeholders
        $query1 = "INSERT INTO hc_basic 
        (`cage_id`, `pi_name`, `strain`, `iacuc`, `user`, `qty`, `dob`, `sex`, `parent_cg`, `remarks`, 
        `mouse_id_1`, `genotype_1`, `notes_1`, `mouse_id_2`, `genotype_2`, `notes_2`, `mouse_id_3`, 
        `genotype_3`, `notes_3`, `mouse_id_4`, `genotype_4`, `notes_4`, `mouse_id_5`, `genotype_5`, `notes_5`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $con->prepare($query1);

        // Bind parameters
        $stmt->bind_param(
            "sssssisssssssssssssssssss",
            $cage_id, $pi_name, $strain, $iacuc, $user, $qty, $dob, $sex, $parent_cg, $remarks,
            $mouse_data[0]['mouse_id'], $mouse_data[0]['genotype'], $mouse_data[0]['notes'],
            $mouse_data[1]['mouse_id'], $mouse_data[1]['genotype'], $mouse_data[1]['notes'],
            $mouse_data[2]['mouse_id'], $mouse_data[2]['genotype'], $mouse_data[2]['notes'],
            $mouse_data[3]['mouse_id'], $mouse_data[3]['genotype'], $mouse_data[3]['notes'],
            $mouse_data[4]['mouse_id'], $mouse_data[4]['genotype'], $mouse_data[4]['notes']
        );

        // Execute the statement
        $result1 = $stmt->execute();

        // Check if the insertion was successful
        if ($result1) {
            $_SESSION['message'] = "New holding cage added successfully.";
        } else {
            $_SESSION['message'] = "Failed to add new holding cage.";
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
        }

        function showMouseFields() {
            var qty = document.getElementById('qty').value;
            for (var i = 1; i <= 5; i++) {
                document.getElementById('mouse_fields_' + i).style.display = i <= qty ? 'block' : 'none';
            }
        }
    </script>

    <title>Add New Holding Cage | <?php echo htmlspecialchars($labName); ?></title>
</head>

<body>
    <div class="container mt-4" style="max-width: 600px; background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
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
                <select class="form-control" id="qty" name="qty" required onchange="showMouseFields()">
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

            <?php for ($i = 1; $i <= 5; $i++): ?>
                <div id="mouse_fields_<?php echo $i; ?>" style="display: none;">
                    <h4>Mouse #<?php echo $i; ?></h4>
                    <div class="mb-3">
                        <label for="mouse_id_<?php echo $i; ?>" class="form-label">Mouse ID</label>
                        <input type="text" class="form-control" id="mouse_id_<?php echo $i; ?>" name="mouse_id_<?php echo $i; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="genotype_<?php echo $i; ?>" class="form-label">Genotype</label>
                        <input type="text" class="form-control" id="genotype_<?php echo $i; ?>" name="genotype_<?php echo $i; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="notes_<?php echo $i; ?>" class="form-label">Maintenance Notes</label>
                        <input type="text" class="form-control" id="notes_<?php echo $i; ?>" name="notes_<?php echo $i; ?>">
                    </div>
                </div>
            <?php endfor; ?>

            <button type="submit" class="btn btn-primary">Add Cage</button>
            <button type="button" class="btn btn-secondary" onclick="goBack()">Go Back</button>

        </form>
    </div>

    <br>
    <?php include 'footer.php'; ?>
</body>

</html>

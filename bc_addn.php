<?php
session_start();
require 'dbcon.php';

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect to index.php if the user is not logged in
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Query to retrieve options where role is 'Principal Investigator'
$query = "SELECT name FROM users WHERE position = 'Principal Investigator' AND status = 'approved'";
$result = $con->query($query);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

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

    // Check if the cage_id already exists in either bc_basic or hc_basic
    $check_query_bc = $con->prepare("SELECT * FROM bc_basic WHERE cage_id = ?");
    $check_query_bc->bind_param("s", $cage_id);
    $check_query_bc->execute();
    $check_result_bc = $check_query_bc->get_result();

    $check_query_hc = $con->prepare("SELECT * FROM hc_basic WHERE cage_id = ?");
    $check_query_hc->bind_param("s", $cage_id);
    $check_query_hc->execute();
    $check_result_hc = $check_query_hc->get_result();

    if ($check_result_bc->num_rows > 0 || $check_result_hc->num_rows > 0) {
        $_SESSION['message'] = "Cage ID '$cage_id' already exists. Please use a different Cage ID.";
    } else {
        // Prepare the insert query with placeholders
        $insert_query = $con->prepare("INSERT INTO bc_basic (`cage_id`, `pi_name`, `cross`, `iacuc`, `user`, `male_id`, `female_id`, `male_dob`, `female_dob`, `remarks`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Bind parameters
        $insert_query->bind_param("ssssssssss", $cage_id, $pi_name, $cross, $iacuc, $user, $male_id, $female_id, $male_dob, $female_dob, $remarks);

        // Execute the statement and check if it was successful
        if ($insert_query->execute()) {
            $_SESSION['message'] = "New breeding cage added successfully.";

            // Handle litter data insertion
            if (isset($_POST['dom'])) {
                $dom = $_POST['dom'];
                $litter_dob = $_POST['litter_dob'];
                $pups_alive = $_POST['pups_alive'];
                $pups_dead = $_POST['pups_dead'];
                $pups_male = $_POST['pups_male'];
                $pups_female = $_POST['pups_female'];
                $litter_remarks = $_POST['remarks'];

                // Loop through each litter entry and insert into the database
                for ($i = 0; $i < count($dom); $i++) {
                    $insert_litter_query = $con->prepare("INSERT INTO bc_litter (`cage_id`, `dom`, `litter_dob`, `pups_alive`, `pups_dead`, `pups_male`, `pups_female`, `remarks`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                    // Bind parameters for each litter entry
                    $insert_litter_query->bind_param("ssssssss", $cage_id, $dom[$i], $litter_dob[$i], $pups_alive[$i], $pups_dead[$i], $pups_male[$i], $pups_female[$i], $litter_remarks[$i]);

                    // Execute the statement and check if it was successful
                    if ($insert_litter_query->execute()) {
                        $_SESSION['message'] .= " Litter data added successfully.";
                    } else {
                        $_SESSION['message'] .= " Failed to add litter data: " . $insert_litter_query->error;
                    }

                    // Close the prepared statement for litter data
                    $insert_litter_query->close();
                }
            }
        } else {
            $_SESSION['message'] = "Failed to add new breeding cage.";
        }

        // Close the prepared statement for cage data
        $insert_query->close();
    }

    // Close the check query prepared statements
    $check_query_bc->close();
    $check_query_hc->close();

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

        function adjustTextareaHeight(element) {
            element.style.height = "auto";
            element.style.height = (element.scrollHeight) + "px";
        }

        function addLitter() {
            const litterDiv = document.createElement('div');
            litterDiv.className = 'litter-entry';

            litterDiv.innerHTML = `
            <hr>
            <div class="mb-3">
                <label for="dom[]" class="form-label">DOM</label>
                <input type="date" class="form-control" name="dom[]" required>
            </div>
            <div class="mb-3">
                <label for="litter_dob[]" class="form-label">Litter DOB</label>
                <input type="date" class="form-control" name="litter_dob[]">
            </div>
            <div class="mb-3">
                <label for="pups_alive[]" class="form-label">Pups Alive</label>
                <input type="number" class="form-control" name="pups_alive[]" required min="0" step="1">
            </div>
            <div class="mb-3">
                <label for="pups_dead[]" class="form-label">Pups Dead</label>
                <input type="number" class="form-control" name="pups_dead[]" required min="0" step="1">
            </div>
            <div class="mb-3">
                <label for="pups_male[]" class="form-label">Pups Male</label>
                <input type="number" class="form-control" name="pups_male[]" required min="0" step="1">
            </div>
            <div class="mb-3">
                <label for="pups_female[]" class="form-label">Pups Female</label>
                <input type="number" class="form-control" name="pups_female[]" required min="0" step="1">
            </div>
            <div class="mb-3">
                <label for="remarks[]" class="form-label">Remarks</label>
                <textarea class="form-control" name="remarks[]" oninput="adjustTextareaHeight(this)"></textarea>
            </div>
            <button type="button" class="btn btn-danger" onclick="removeLitter(this)">Remove</button>
        `;

            document.getElementById('litterEntries').appendChild(litterDiv);
        }

        function removeLitter(element) {
            element.parentElement.remove();
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
                <textarea class="form-control" id="remarks" name="remarks" oninput="adjustTextareaHeight(this)"></textarea>
            </div>

            <!-- Litter Data Section -->
            <div class="mt-4">
                <h5>Litter Data</h5>
                <div id="litterEntries">
                    <!-- Litter entries will be added here dynamically -->
                </div>
                <button type="button" class="btn btn-success mt-3" onclick="addLitter()">Add Litter Entry</button>
            </div>

            <br>

            <button type="submit" class="btn btn-primary">Add Cage</button>
            <button type="button" class="btn btn-secondary" onclick="goBack()">Go Back</button>

        </form>
    </div>

    <br>
    <?php include 'footer.php'; ?>
</body>

</html>
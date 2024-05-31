<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the breedingcage record with the specified ID
    $query = $con->prepare("SELECT * FROM bc_litter WHERE `id` = ?");
    $query->bind_param("s", $id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $breedingcage = $result->fetch_assoc();
        $cage_id = $breedingcage['cage_id']; // Store cage_id for later use

        // Process the form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Retrieve form data
            $dom = $_POST['dom'];
            $litter_dob = $_POST['litter_dob'];
            $pups_alive = $_POST['pups_alive'];
            $pups_dead = $_POST['pups_dead'];
            $pups_male = $_POST['pups_male'];
            $pups_female = $_POST['pups_female'];
            $remarks = $_POST['remarks'];

            // Check if litter_dob is provided
            $litter_dob_provided = !empty($litter_dob);

            // Prepare the update query with placeholders
            $updateQuery = $con->prepare("UPDATE bc_litter SET
                `cage_id` = ?, 
                `dom` = ?, 
                " . ($litter_dob_provided ? "`litter_dob` = ?, " : "") . " 
                `pups_alive` = ?, 
                `pups_dead` = ?, 
                `pups_male` = ?, 
                `pups_female` = ?, 
                `remarks` = ? 
                WHERE `id` = ?");

            // Bind parameters
            if ($litter_dob_provided) {
                $updateQuery->bind_param("sssiiiiss", $cage_id, $dom, $litter_dob, $pups_alive, $pups_dead, $pups_male, $pups_female, $remarks, $id);
            } else {
                $updateQuery->bind_param("ssiiiiss", $cage_id, $dom, $pups_alive, $pups_dead, $pups_male, $pups_female, $remarks, $id);
            }

            // Execute the statement and check if it was successful
            if ($updateQuery->execute()) {
                $_SESSION['message'] = 'Litter data updated successfully.';
            } else {
                $_SESSION['message'] = 'Update failed: ' . $updateQuery->error;
            }

            // Close the prepared statement
            $updateQuery->close();

            // Redirect back to the main page
            header("Location: bc_view.php?id=" . rawurlencode($cage_id));
            exit();
        }
    } else {
        $_SESSION['message'] = 'Invalid ID.';
        header("Location: bc_view.php?id=" . rawurlencode($cage_id));
        exit();
    }
    $query->close();
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: bc_view.php");
    exit();
}

require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <title>Edit Litter Data | <?php echo htmlspecialchars($labName); ?></title>

    <style>
        body {
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-label {
            font-weight: bold;
        }

        .btn-primary {
            margin-right: 10px;
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
    </script>
</head>

<body>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Litter Data</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="bcltr_edit.php?id=<?= htmlspecialchars($id); ?>">

                            <div class="mb-3">
                                <label for="dom" class="form-label">DOM</label>
                                <input type="date" class="form-control" id="dom" name="dom" value="<?= htmlspecialchars($breedingcage['dom']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="litter_dob" class="form-label">Litter DOB</label>
                                <input type="date" class="form-control" id="litter_dob" name="litter_dob" value="<?= htmlspecialchars($breedingcage['litter_dob']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="pups_alive" class="form-label">Pups Alive</label>
                                <input type="number" class="form-control" id="pups_alive" name="pups_alive" value="<?= htmlspecialchars($breedingcage['pups_alive']); ?>" required min="0" step="1">
                            </div>

                            <div class="mb-3">
                                <label for="pups_dead" class="form-label">Pups Dead</label>
                                <input type="number" class="form-control" id="pups_dead" name="pups_dead" value="<?= htmlspecialchars($breedingcage['pups_dead']); ?>" required min="0" step="1">
                            </div>

                            <div class="mb-3">
                                <label for="pups_male" class="form-label">Pups Male</label>
                                <input type="number" class="form-control" id="pups_male" name="pups_male" value="<?= htmlspecialchars($breedingcage['pups_male']); ?>" required min="0" step="1">
                            </div>

                            <div class="mb-3">
                                <label for="pups_female" class="form-label">Pups Female</label>
                                <input type="number" class="form-control" id="pups_female" name="pups_female" value="<?= htmlspecialchars($breedingcage['pups_female']); ?>" required min="0" step="1">
                            </div>

                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <input type="text" class="form-control" id="remarks" name="remarks" value="<?= htmlspecialchars($breedingcage['remarks']); ?>">
                                <textarea class="form-control" id="remarks" name="remarks" oninput="adjustTextareaHeight(this)" value="<?= htmlspecialchars($breedingcage['remarks']); ?>"></textarea>

                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-secondary" onclick="goBack()">Go Back</button>

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

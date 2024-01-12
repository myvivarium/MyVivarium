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
    $query = "SELECT * FROM bc_litter WHERE `id` = '$id'";
    $result = mysqli_query($con, $query);

    if (mysqli_num_rows($result) > 0) {
        $breedingcage = mysqli_fetch_assoc($result);

        // Process the form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Retrieve form data
            // Retrieve and sanitize form data
            $cage_id = $breedingcage['cage_id'];
            $dom = mysqli_real_escape_string($con, $_POST['dom']);
            $litter_dob = mysqli_real_escape_string($con, $_POST['litter_dob']);
            $pups_alive = mysqli_real_escape_string($con, $_POST['pups_alive']);
            $pups_dead = mysqli_real_escape_string($con, $_POST['pups_dead']);
            $pups_male = mysqli_real_escape_string($con, $_POST['pups_male']);
            $pups_female = mysqli_real_escape_string($con, $_POST['pups_female']);
            $remarks = mysqli_real_escape_string($con, $_POST['remarks']);

            // Update the breedingcage record
            $updateQuery = "UPDATE bc_litter SET
                `cage_id` = '$cage_id',
                `dom` = '$dom',
                `litter_dob` = '$litter_dob',
                `pups_alive` = '$pups_alive',
                `pups_dead` = '$pups_dead',
                `pups_male` = '$pups_male',
                `pups_female` = '$pups_female',
                `remarks` = '$remarks'
                WHERE `id` = '$id'";

            mysqli_query($con, $updateQuery);

            $_SESSION['message'] = 'Litter data updated successfully.';
            header("Location: bc_view.php?id=" . rawurlencode($cage_id));
            exit();
        }
    } else {
        $_SESSION['message'] = 'Invalid ID.';
        header("Location: bc_view.php?id=" . rawurlencode($cage_id));
        exit();
    }
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: bc_view.php?id=" . rawurlencode($cage_id));
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
                        <h4>Edit Litter Data</h4>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="bcltr_edit.php?id=<?= $id; ?>">

                            <div class="mb-3">
                                <label for="dom" class="form-label">DOM</label>
                                <input type="date" class="form-control" id="dom" name="dom" value="<?= $breedingcage['dom']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="litter_dob" class="form-label">Litter DOB</label>
                                <input type="date" class="form-control" id="litter_dob" name="litter_dob" value="<?= $breedingcage['litter_dob']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="pups_alive" class="form-label">Pups Alive</label>
                                <input type="number" class="form-control" id="pups_alive" name="pups_alive" value="<?= $breedingcage['pups_alive']; ?>" required min="0" step="1">
                            </div>

                            <div class="mb-3">
                                <label for="pups_dead" class="form-label">Pups Dead</label>
                                <input type="number" class="form-control" id="pups_dead" name="pups_dead" value="<?= $breedingcage['pups_dead']; ?>" required min="0" step="1">
                            </div>

                            <div class="mb-3">
                                <label for="pups_male" class="form-label">Pups Male</label>
                                <input type="number" class="form-control" id="pups_male" name="pups_male" value="<?= $breedingcage['pups_male']; ?>" required min="0" step="1">
                            </div>

                            <div class="mb-3">
                                <label for="pups_female" class="form-label">Pups Female</label>
                                <input type="number" class="form-control" id="pups_female" name="pups_female" value="<?= $breedingcage['pups_female']; ?>" required min="0" step="1">
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
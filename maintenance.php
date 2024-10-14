<?php

/**
 * Maintenance Management Script
 * 
 * This script handles the addition of maintenance records, allowing for the selection of cages and optional comments.
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';

// Regenerate session ID to prevent session fixation
// session_regenerate_id(true);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is not logged in, redirect them to index.php with the current URL for redirection after login
if (!isset($_SESSION['username'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit; // Exit to ensure no further code is executed
}

// Generate a CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Capture the 'from' query parameter for redirection purposes
$redirectFrom = isset($_GET['from']) ? $_GET['from'] : 'hc_dash';
$_SESSION['redirect_from'] = $redirectFrom;

// Query to retrieve cage IDs
$cageQuery = "SELECT cage_id FROM cages";
$cageResult = $con->query($cageQuery);

// Initialize an array to hold all cage options
$cageOptions = [];
while ($cageRow = $cageResult->fetch_assoc()) {
    $cageOptions[] = htmlspecialchars($cageRow['cage_id']);
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    // Retrieve and sanitize form data
    $selectedCages = $_POST['cage_id'];
    $comments = $_POST['comments'];
    $userId = $_SESSION['user_id']; // Assume user session is started and user_id is stored in session

    $stmt = $con->prepare("INSERT INTO maintenance (cage_id, user_id, comments) VALUES (?, ?, ?)");

    foreach ($selectedCages as $index => $cage_id) {
        $comment = !empty($comments[$index]) ? $comments[$index] : null;
        $stmt->bind_param("sis", $cage_id, $userId, $comment);
        $stmt->execute();
    }

    // Set success message and redirect based on the originating page
    $_SESSION['message'] = 'Maintenance records added successfully.';
    $redirectUrl = $redirectFrom === 'bc_dash' ? 'bc_dash.php' : 'hc_dash.php';
    header("Location: $redirectUrl");
    exit();
}

// Include the header file
require 'header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cage Maintenance</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
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

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-buttons {
            display: flex;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        .form-control {
            margin-bottom: 1rem;
        }

        .action-icons a {
            margin-right: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <div class="container content mt-4">

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Add Cage Maintenance Record</h4>
                        <div class="action-buttons">
                            <!-- Button to go back to the previous page -->
                            <a href="javascript:void(0);" onclick="goBack()" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Go Back">
                                <i class="fas fa-arrow-circle-left"></i>
                            </a>
                            <!-- Button to save the form -->
                            <a href="javascript:void(0);" onclick="document.getElementById('editForm').submit();" class="btn btn-success btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Save">
                                <i class="fas fa-save"></i>
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="mb-3">
                                <label for="cage_id" class="form-label">Select Cages (multi select)</label>
                                <select id="cage_id" name="cage_id[]" multiple="multiple" style="width: 100%;" required>
                                    <?php foreach ($cageOptions as $cage_id) : ?>
                                        <option value="<?php echo $cage_id; ?>"><?php echo $cage_id; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="comments-section"></div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function goBack() {
            window.history.back();
        }

        $(document).ready(function() {
            $('#cage_id').select2();

            $('#cage_id').on('change', function() {
                let selectedCages = $(this).val();
                let commentsSection = $('#comments-section');
                commentsSection.empty();

                if (selectedCages.length > 0) {
                    selectedCages.forEach(function(cage_id, index) {
                        commentsSection.append(`
                            <div class="mb-3">
                                <label for="comments[${index}]" class="form-label">Comment for ${cage_id}:</label>
                                <textarea name="comments[]" class="form-control" placeholder="Optional comment"></textarea>
                                <input type="hidden" name="cage_id_hidden[]" value="${cage_id}">
                            </div>
                        `);
                    });
                }
            });
        });
    </script>

    <?php include 'footer.php'; ?> <!-- Include footer file -->
</body>

</html>
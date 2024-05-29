<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start or resume the session
}

// Include your database connection file
require 'dbcon.php';

// Check if the user is logged in
if (!isset($_SESSION['name'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit;
}

$currentUserId = $_SESSION['username']; // Assuming 'username' is the user's identifier

// Retrieve user's sticky notes
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM nt_data WHERE cage_id = ? ORDER BY created_at DESC";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $id);
} else {
    $sql = "SELECT * FROM nt_data WHERE cage_id IS NULL ORDER BY created_at DESC";
    $stmt = $con->prepare($sql);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sticky Notes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }


        .sticky-notes-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start; /* Adjust alignment as needed */
        }

        .sticky-note {
            background-color: #fff8b3;
            border: 1px solid #e6d381;
            padding: 15px;
            margin-bottom: 15px; /* This can be reduced or removed if using gap in Flexbox */
            border-radius: 15px;
            position: relative;
            width: 300px; /* Adjust the width as needed */
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .timestamp {
            display: block;
            font-size: 12px;
            color: #888;
        }

        .userid {
            display: block;
            font-size: 12px;
            color: blue;
        }

        .close-btn {
            cursor: pointer;
            position: absolute;
            top: 5px;
            right: 5px;
            font-weight: bold;
            color: #888;
        }

        .close-btn:hover {
            color: #555;
        }

        .add-note-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
            margin-bottom: 15px;
        }

        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            z-index: 1000;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        #addNoteForm {
            display: flex;
            flex-direction: column;
            width: 380px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
            overflow-y: hidden;
        }

        #note_text {
            height: 100px;
            margin-bottom: 10px;
            resize: none;
            background-color: #fff8b3;
        }

        #addNoteForm button {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
        }

        #addNoteForm button:hover {
            background-color: #45a049;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }

        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>

<body>
    <div class="container" style="margin: 50px 0px;">
            <div id="message"></div> <!-- Added this div for displaying messages -->
            <button class="add-note-btn" onclick="togglePopup()">Add Sticky Note</button>

            <div class="popup" id="addNotePopup">
                <span class="close-btn" onclick="togglePopup()">X</span>
                <form id="addNoteForm" method="post">
                    <?php if (isset($_GET['id'])): ?>
                        <label for="cage_id">For Cage ID:
                            <?= htmlspecialchars($_GET['id']); ?>
                        </label>
                        <input type="hidden" id="cage_id" name="cage_id" value="<?= htmlspecialchars($_GET['id']); ?>">
                    <?php endif; ?>
                    <textarea id="note_text" name="note_text" placeholder="Type your sticky note here..." required></textarea>
                    <button type="submit" name="add_note">Add Note</button>
                </form>
            </div>

            <div class="overlay" id="overlay" onclick="togglePopup()"></div>

            <div class="sticky-notes-container"> <!-- Add this wrapper -->
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="sticky-note" id="note-<?= $row['id']; ?>">
                        <?php if ($currentUserId == $row['user_id']): ?>
                            <span class="close-btn" onclick="removeNote(<?php echo $row['id']; ?>)">X</span>
                        <?php endif; ?>
                        <span class="userid">
                            <?php echo htmlspecialchars($row['user_id']); ?>
                        </span>
                        <p>
                            <?php echo nl2br(htmlspecialchars($row['note_text'])); ?>
                        </p>
                        <span class="timestamp">
                            <?php echo htmlspecialchars($row['created_at']); ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

    <script>
        function togglePopup() {
            var popup = document.getElementById("addNotePopup");
            var overlay = document.getElementById("overlay");

            if (popup.style.display === "block") {
                popup.style.display = "none";
                overlay.style.display = "none";
            } else {
                popup.style.display = "block";
                overlay.style.display = "block";
            }
        }

        // Submit form using AJAX
        $('#addNoteForm').submit(function (e) {
            e.preventDefault();
            var formData = $(this).serialize();

            $.ajax({
                type: 'POST',
                url: 'nt_add.php',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    togglePopup(); // Close the popup after successful submission
                    var messageDiv = $('#message');
                    if (response.success) {
                        messageDiv.html('<div class="alert alert-success">' + response.message + '</div>');
                    } else {
                        messageDiv.html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                    // Delay before reloading the page
                    setTimeout(function () {
                        location.reload(); // Reload the page to display the new note
                    }, 1000); // 1-second delay
                },
                error: function (error) {
                    console.log('Error:', error);
                }
            });
        });

        // Remove note using AJAX
        function removeNote(noteId) {
            $.ajax({
                type: 'POST',
                url: 'nt_rmv.php',
                data: {
                    note_id: noteId
                },
                dataType: 'json',
                success: function (response) {
                    var messageDiv = $('#message');
                    if (response.success) {
                        messageDiv.html('<div class="alert alert-success">' + response.message + '</div>');
                        $('#note-' + noteId).remove(); // Remove the note from the DOM
                        // Delay before reloading the page
                        setTimeout(function () {
                            location.reload(); // Reload the page to refresh the notes
                        }, 1000); // 1-second delay
                    } else {
                        messageDiv.html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                },
                error: function (error) {
                    console.log('Error:', error);
                }
            });
        }
    </script>
</body>

</html>

<?php
// Close the database connection
$stmt->close();
$con->close();
?>

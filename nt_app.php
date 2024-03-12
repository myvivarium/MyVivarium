<?php
// Include your database connection file
require 'dbcon.php';

// session_start();

// Check if the user is logged in
if (!isset($_SESSION['name'])) {
    header("Location: index.php"); // Redirect to admin login page if not logged in
    exit;
}

// Retrieve user's sticky notes
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM nt_data WHERE `cage_id` = '$id' ORDER BY created_at DESC";
} else {
    $sql = "SELECT * FROM nt_data WHERE cage_id IS NULL ORDER BY created_at DESC";
}
$stmt = $con->prepare($sql);
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

        .sticky-note {
            background-color: #fff8b3;
            border: 1px solid #e6d381;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 15px;
            position: relative;
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
            transition: height 0.3s;
            overflow-y: hidden;
            /* Hide vertical scrollbar initially */
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
            transition: background-color 0.3s;
        }

        #addNoteForm button:hover {
            background-color: #45a049;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>

<body>
    <div class="container" style="max-width: 800px; margin: 50px auto;">


        <div class="popup" id="addNotePopup">
            <span class="close-btn" onclick="togglePopup()">X</span>
            <form id="addNoteForm" method="post" action="nt_add.php">
                <?php if (isset($_GET['id'])): ?>
                    <label for="cage_id">For Cage ID:
                        <?= $_GET['id']; ?>
                    </label>
                    <input type="hidden" id="cage_id" name="cage_id" value="<?= $_GET['id']; ?>"
                        style="display: inline-block; vertical-align: middle;">
                <?php endif; ?>
                <textarea id="note_text" name="note_text" placeholder="Type your sticky note here..."
                    required></textarea>
                <button type="submit" name="add_note">Add Note</button>
            </form>
        </div>

        <div class="overlay" id="overlay" onclick="togglePopup()"></div>

        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="sticky-note">
                <span class="close-btn" onclick="removeNote(<?php echo $row['id']; ?>)">X</span>
                <span class="userid">
                    <?php echo $row['user_id']; ?>
                </span>
                <p>
                    <?php echo nl2br($row['note_text']); ?>
                </p>
                <span class="timestamp">
                    <?php echo $row['created_at']; ?>
                </span>
            </div>
        <?php endwhile; ?>

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
                success: function () {
                    togglePopup(); // Close the popup after successful submission
                    // Optionally, you can reload the notes here
                    location.reload();
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
                success: function () {
                    $('#note-' + noteId).remove(); // Remove the note from the DOM
                    // Optionally, you can reload the notes here
                    location.reload();
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
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'dbcon.php';

    $noteId = $_POST['note_id'];
    $noteText = $_POST['note_text'];

    // Update the note in the database
    $sql = "UPDATE nt_data SET note_text = ? WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('si', $noteText, $noteId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Note updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update note.']);
    }

    $stmt->close();
    $con->close();
}
?>

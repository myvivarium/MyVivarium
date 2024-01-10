<?php
require 'dbcon.php';



// Handle the delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note_id'])) {
    $note_id = $_POST['delete_note_id'];
    $stmt = $con->prepare("DELETE FROM sticky_notes WHERE id = ?");
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    $stmt->close();
    exit;
}

$email = ''; 

if (isset($_SESSION['admin_username'])) {
    $email = $_SESSION['admin_username'];
} elseif (isset($_SESSION['ea_username'])) {
    $email = $_SESSION['ea_username'];
}

if (!$email) {
    die("No email available. Ensure the user is logged in.");
}

$email_domain = array_pop(explode('@', $email));

// Modify the INSERT code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_content'])) {
    $note_content = $_POST['note_content'];
    $created_at = date("Y-m-d H:i:s"); 
    $stmt = $con->prepare("INSERT INTO sticky_notes (email_domain, note_content, created_at, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email_domain, $note_content, $created_at, $email);
    $stmt->execute();
    $stmt->close();
    exit; 
}

$result = $con->query("SELECT id, note_content, created_at, created_by FROM sticky_notes WHERE email_domain = '$email_domain'");
?>

<div id="whiteboard" class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div style="max-width: 85%; margin: 20px auto; background-color: #eee; padding: 20px; border: 1px solid #ccc;">
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="sticky-note">
                <span><?= htmlspecialchars($row['note_content']) ?></span>
                <br>
                <small class="text-muted"><?= $row['created_at'] . " by " . $row['created_by'] ?></small>
                <button data-note-id="<?= $row['id'] ?>" class="btn btn-danger btn-sm delete-btn">Delete</button>
            </div>
        <?php endwhile; ?>
        
        <form id="addNoteForm" method="post">
            <textarea name="note_content" required></textarea>
            <button type="submit">Add Note</button>
        </form>
    </div>
</div>

<style>
    #whiteboard {
        background-color: #eee;
        padding: 20px;
        border: 1px solid #ccc;
    }

    .sticky-note {
        background-color: #ffeb3b;
        padding: 10px;
        margin: 10px;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
</style>

<script>
    // AJAX functionality for adding notes
    document.getElementById('addNoteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        fetch('', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(() => {
            location.reload();
        });
    });

    // AJAX functionality for deleting notes without page reload
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            let noteId = this.dataset.noteId;
            let formData = new FormData();
            formData.append('delete_note_id', noteId);
            fetch('', { method: 'POST', body: formData })
            .then(response => response.text())
            .then(() => {
                // Remove the sticky note element from the DOM
                e.target.closest('.sticky-note').remove();
            });
        });
    });
</script>

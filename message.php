<?php

/**
 * Display Session Message
 * 
 * This script checks if there's a message set in the session and displays it as a Bootstrap alert. 
 * The message is then unset from the session after being displayed.
 * 
 * Author: [Your Name]
 * Date: [Date]
 */

// Check if there's a message set in the session
if (isset($_SESSION['message'])) :
?>

    <!-- Bootstrap alert message -->
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Hey! <?= htmlspecialchars($_SESSION['name']); ?>,</strong> <?= htmlspecialchars($_SESSION['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

<?php
    // Unset the message after displaying it
    unset($_SESSION['message']);
endif;
?>
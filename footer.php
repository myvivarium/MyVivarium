<!--
    This code defines the styling and content for the footer section of a webpage.
    The footer is styled with a dark background and centered white text. It dynamically displays
    the current year and lab name, ensuring special characters in the lab name are properly escaped for security.
-->

<!-- Footer Section -->

<style>
    .header-footer {
        background-color: #343a40;
        margin: 0;
        padding: 10px 0;
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }

    .footer-text {
        color: white;
        margin: 0;
    }

    @media (max-width: 768px) {
        .header-footer {
            padding: 15px 0;
        }
    }
</style>

<!-- Footer content -->
<div class="header-footer">
    <!-- Dynamic footer text displaying the current year and lab name, with HTML special characters escaped -->
    <p class="footer-text">&copy; <?php echo date("Y"); ?>
        <?php
        if (isset($labName)) {
            echo htmlspecialchars($labName);
        } else {
            echo "My Vivarium";
        }
        ?>. All rights reserved.</p>
</div>
<!-- Footer Section -->

<!--
    This code defines the styling and content for the footer section of a webpage.
    The footer is styled with a dark background and centered white text. It dynamically displays
    the current year and lab name, ensuring special characters in the lab name are properly escaped for security.
-->

<style>
    html,
    body {
        margin: 0;
        padding: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .content {
        flex: 1;
    }

    footer {
        background-color: #343a40;
        padding: 20px 0;
        text-align: center;
        color: white;
        box-sizing: border-box;
        height: 60px;
        width: 100%;
    }

    .footer-text {
        margin: 0;
    }
</style>

<!-- Footer content -->
<footer>
    <!-- Dynamic footer text displaying the current year and lab name, with HTML special characters escaped -->
    <p class="footer-text">&copy; <?php echo date("Y"); ?>
        <?php
        if (isset($labName)) {
            echo htmlspecialchars($labName);
        } else {
            echo "My Vivarium";
        }
        ?>. All rights reserved.</p>
</footer>
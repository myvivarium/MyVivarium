<!--
    This code defines the styling and content for the footer section of a webpage.
    The footer is styled with a dark background and centered white text. It dynamically displays
    the current year and lab name, ensuring special characters in the lab name are properly escaped for security.
-->

<!-- Footer Section -->

<style>
    /* Header and Footer Styling */
    
    /* Styling for the header and footer section */
    .header-footer {
        background-color: #343a40; /* Dark grey background color */
        padding: 20px 0; /* Vertical padding for top and bottom */
        text-align: center; /* Center align the text */
        width: 100%; /* Full width */
        box-sizing: border-box; /* Include padding and border in the element's total width and height */
    }

    /* Styling for the footer text */
    .footer-text {
        color: white; /* White text color */
        margin: 0; /* Remove default margin */
    }
</style>

<!-- Footer content -->
<div class="header-footer">
    <!-- Dynamic footer text displaying the current year and lab name, with HTML special characters escaped -->
    <p class="footer-text">&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($labName); ?>. All rights reserved.</p>
</div>

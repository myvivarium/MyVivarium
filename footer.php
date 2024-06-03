<!-- 
    Footer Section

    This section defines the footer of the webpage, which includes dynamic content such as the current year and the lab name.
    The footer is styled with a dark background and centered white text. The PHP code dynamically generates the current year
    and escapes special characters in the lab name to ensure security.

    Author: [Your Name]
    Date: [Date]
-->

<style>
    /* Header and Footer Styling */

    /* This style applies to both the header and footer sections */
    .header-footer {
        background-color: #343a40; /* Set background color to dark grey */
        padding: 20px 0; /* Add padding to the top and bottom */
        text-align: center; /* Center the text horizontally */
        width: 100%; /* Make the section span the full width of the page */
        box-sizing: border-box; /* Include padding and border in the element's total width and height */
    }

    /* This style applies to the text within the footer */
    .footer-text {
        color: white; /* Set text color to white */
        margin: 0; /* Remove default margin */
    }
</style>

<div class="header-footer">
    <!--
        This paragraph contains the footer text.
        The PHP code inside the paragraph dynamically inserts the current year and the lab name.
        The htmlspecialchars function is used to convert special characters to HTML entities to prevent security issues.
    -->
    <p class="footer-text">
        &copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($labName); ?>. All rights reserved.
    </p>
</div>

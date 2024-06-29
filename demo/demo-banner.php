<!DOCTYPE html>
<html lang="en">

<head>
    <!--
        Demo Website Warning Banner

        This HTML file displays a fixed warning banner at the top of the webpage to indicate that the site is a demo
        and for testing purposes only. The banner is styled to be highly visible with a red background and white text.

        Styles:
        - .demo-warning: Styles the warning banner to be fixed at the top, with a red background, white text, and
          a shadow effect for visibility.
        - .header: Adds a margin to the top of the header to avoid overlap with the fixed banner.
    -->

    <style>
        /* Style for the demo warning banner */
        .demo-warning {
            position: fixed; /* Fixes the banner at the top */
            top: 0;
            left: 0;
            width: 100%; /* Full width of the viewport */
            height: 30px; /* Fixed height */
            font-size: medium; /* Medium font size */
            background-color: #dc3545; /* Red background color */
            color: #ffffff; /* White text color */
            text-align: center; /* Center the text */
            vertical-align: middle; /* Vertical alignment */
            padding-top: 2px; /* Padding at the top */
            z-index: 1000; /* Ensures the banner stays on top */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Shadow effect */
        }

        /* Adds a margin to the top of the header to avoid overlap with the fixed banner */
        .header {
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <!-- Demo warning banner -->
    <div class="demo-warning">
        <span style="font-family: 'Arial', sans-serif;">DEMO WEBSITE: FOR TESTING PURPOSES ONLY.</span>
    </div>
</body>

</html>

<!DOCTYPE html>
<html lang="en">

<head>
    <!--
        Demo Credentials Display

        This HTML and CSS code snippet displays the demo username and password in a highlighted div box 
        at the bottom left corner of the index.php page. The box is styled to stand out with a light red 
        background and dark red text, ensuring that the credentials are easily noticeable.

        Styles:
        - .demo-credentials: Styles the credentials box with a light red background, dark red text, border, 
          border-radius, padding, margin, and a shadow effect.
        - .demo-credentials p: Styles the paragraphs inside the credentials box with no margin and Arial font.
    -->

    <style>
        /* Style for the demo credentials display box */
        .demo-credentials {
            background-color: #f8d7da; /* Light red background color */
            color: #721c24; /* Dark red text color */
            border: 1px solid #f5c6cb; /* Light red border color */
            border-radius: 5px; /* Rounded corners */
            padding: 10px; /* Padding inside the box */
            margin: 10px 0; /* Margin outside the box */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Shadow effect */
        }

        /* Style for paragraphs inside the demo credentials box */
        .demo-credentials p {
            margin: 0; /* Remove default margin */
            font-family: 'Arial', sans-serif; /* Arial font family */
        }
    </style>
</head>

<body>
    <!-- Demo credentials display box -->
    <div class="demo-credentials">
        <p><strong>DEMO Admin Credentials</strong></p>
        <p><strong>Email:</strong> admin@myvivarium.online <strong>Password:</strong> password</p>
    </div>
</body>

</html>

<!DOCTYPE html>
<html lang="en">

<head>
    <!--
        Demo Website Warning Banner

        This HTML file displays a fixed warning banner at the top of the webpage to indicate that the site is a demo
        and for testing purposes only. The banner is styled to be highly visible with a red background and white text.
    -->

    <style>
        .demo-warning {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 30px;
            font-size: medium;
            background-color: #dc3545;
            color: #ffffff;
            text-align: center;
            vertical-align: middle;
            padding-top: 2px;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

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
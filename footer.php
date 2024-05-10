<!-- Footer Section -->

<style>
    /* Header and Footer Styling */
    html,
    body {
        height: 100%;
        margin: 0;
        padding: 0;
    }

    .body-content {
        min-height: 100%;
        position: relative;
        padding-bottom: 50px;
        /* Height of the footer */
    }

    .header-footer {
        background-color: #343a40;
        color: white;
        padding: 20px 0;
        text-align: center;
        width: 100%;
        position: absolute;
        bottom: 0;
        left: 0;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const footer = document.querySelector('.header-footer');
        const content = document.querySelector('.body-content');
        const viewportHeight = window.innerHeight;

        function adjustFooter() {
            if (content.offsetHeight < viewportHeight) {
                footer.style.position = 'absolute';
                footer.style.bottom = '0';
                footer.style.left = '0';
            } else {
                footer.style.position = 'static';
            }
        }

        adjustFooter();
        window.onresize = adjustFooter; // Adjust on window resize
    });
</script>

<div class="header-footer">
    <p style="color:white">&copy;
        <?php echo date("Y"); ?> <?php echo htmlspecialchars($labName); ?>. All rights reserved.
    </p>
</div>
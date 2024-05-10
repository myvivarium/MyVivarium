<!-- Footer Section -->

<style>
    /* Header and Footer Styling */
    html, body {
    height: 100%;
    margin: 0;
}

body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.site-content {
    flex: 1;
}

.header-footer {
    background-color: #343a40;
    color: white;
    padding: 20px 0;
    text-align: center;
}

/* Additional footer styles */
.header-footer p {
    margin: 0;  /* Removes default paragraph margins */
}

</style>
<div class="header-footer">
    <p style="color:white">&copy;
        <?php echo date("Y"); ?> <?php echo htmlspecialchars($labName); ?>. All rights reserved.
    </p>
</div>
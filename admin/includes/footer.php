<?php
// File: /admin/includes/footer.php
// ---
// All inline styles and scripts have been moved to external files.
?>
    </main> <footer class="admin-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date("Y"); ?> DailyFix Admin Panel. All Rights Reserved.</p>
        </div>
    </footer>

    <script defer src="assets/js/admin_app.js"></script>
    <script>
    // Wait for the entire page to be fully loaded
    window.onload = function() {
        const loader = document.getElementById('page-loader');
        if (loader) {
        // Add the 'hidden' class to fade it out
        loader.classList.add('hidden');
        }
    };
    </script>
</body>
</html>
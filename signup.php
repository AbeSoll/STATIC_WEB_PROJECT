<?php if (isset($_SESSION['signup_success']) && $_SESSION['signup_success']): ?>
    <script>
        // Show the popup on page load
        document.addEventListener('DOMContentLoaded', () => {
            const popup = document.getElementById('signup-success-popup');
            popup.classList.remove('hidden');

            // Redirect after 3 seconds
            setTimeout(() => {
                popup.classList.add('hidden');
            }, 3000);
        });
    </script>
    <?php unset($_SESSION['signup_success']); // Remove flag after showing popup ?>
<?php endif; ?>

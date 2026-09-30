<?php
require_once __DIR__ . '/../includes/auth.php';

// Logout only accepts POST with a CSRF token, so another site can't log the admin out with a link.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf();
logout();

start_session();
flash('success', 'You have been logged out.');
redirect('login.php');

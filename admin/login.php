<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
    } elseif (attempt_login($username, $password)) {
        redirect('index.php');
    } else {
        $error = 'Incorrect username or password.';
    }
}

admin_header('Log in');
?>
        <div class="login-box">
            <h1><?= e(APP_NAME) ?> Admin</h1>

<?php if ($error): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
<?php endif; ?>

            <form method="post" action="login.php">
                <?= csrf_field() ?>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>

                <button type="submit">Log in</button>
            </form>
        </div>
<?php
admin_footer();

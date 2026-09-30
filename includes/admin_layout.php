<?php
// Shared page frame for admin pages. Usage:
//   admin_header('Page title');
//   ... page content ...
//   admin_footer();

function admin_header(string $title): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> · <?= e(APP_NAME) ?> Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<?php if (is_logged_in()): ?>
    <header class="topbar">
        <a class="brand" href="index.php"><?= e(APP_NAME) ?> Admin</a>
        <div class="topbar-user">
            <span>Logged in as <strong><?= e(current_admin_username()) ?></strong></span>
            <form method="post" action="logout.php">
                <?= csrf_field() ?>
                <button type="submit" class="button-link">Log out</button>
            </form>
        </div>
    </header>
<?php endif; ?>
    <main class="container">
<?php foreach (take_flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
<?php
}

function admin_footer(): void
{
    ?>
    </main>
</body>
</html>
<?php
}

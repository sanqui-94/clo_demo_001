<?php
// Shared page frame for admin pages. Usage:
//   admin_header('Page title');
//   ... page content ...
//   admin_footer();

function admin_header(string $title): void
{
    ?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> · <?= e(APP_NAME) ?> <?= e(t('admin.title_suffix')) ?></title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="index.php"><?= e(APP_NAME) ?> <?= e(t('admin.title_suffix')) ?></a>
        <div class="topbar-user">
<?php if (is_logged_in()): ?>
            <span><?= e(t('admin.logged_in_as')) ?> <strong><?= e(current_admin_username()) ?></strong></span>
            <form method="post" action="logout.php">
                <?= csrf_field() ?>
                <button type="submit" class="button-link"><?= e(t('admin.logout')) ?></button>
            </form>
<?php endif; ?>
            <?= lang_switch() ?>
        </div>
    </header>
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

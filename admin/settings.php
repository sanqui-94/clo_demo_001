<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_login();

const MIN_PASSWORD_LENGTH = 10;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Passwords are never trimmed, echoed back into the form, or logged.
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = db()->prepare('SELECT password_hash FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $hash = $stmt->fetchColumn();

    if ($current === '') {
        $errors['current_password'] = t('settings.current_required');
    } elseif ($hash === false || !password_verify($current, $hash)) {
        $errors['current_password'] = t('settings.current_wrong');
    }

    if (mb_strlen($new) < MIN_PASSWORD_LENGTH) {
        $errors['new_password'] = t('settings.too_short', ['min' => MIN_PASSWORD_LENGTH]);
    } elseif (!isset($errors['current_password']) && $new === $current) {
        $errors['new_password'] = t('settings.same_as_current');
    }

    if (!isset($errors['new_password']) && $new !== $confirm) {
        $errors['confirm_password'] = t('settings.mismatch');
    }

    if (!$errors) {
        $stmt = db()->prepare('UPDATE admin_users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);

        // New session ID after a credential change, like after login.
        session_regenerate_id(true);
        flash('success', t('settings.changed'));
        redirect('settings.php');
    }
}

admin_header(t('settings.title'));
?>
        <h1><?= e(t('settings.title')) ?></h1>

        <section class="form-card">
            <h2><?= e(t('settings.password_heading')) ?></h2>

            <?= form_errors_alert($errors) ?>

            <form method="post" action="settings.php" novalidate>
                <?= csrf_field() ?>
                <!-- Lets password managers know which account this is. -->
                <input type="text" name="username" value="<?= e(current_admin_username()) ?>" autocomplete="username" hidden>

                <label for="current_password"><?= e(t('settings.current')) ?></label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password" required<?= field_error_attrs($errors, 'current_password') ?>>
                <?= field_error($errors, 'current_password') ?>

                <label for="new_password"><?= e(t('settings.new')) ?></label>
                <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="<?= MIN_PASSWORD_LENGTH ?>" required<?= field_error_attrs($errors, 'new_password') ?>>
                <?= field_error($errors, 'new_password') ?>
                <p class="form-hint"><?= e(t('settings.new_hint', ['min' => MIN_PASSWORD_LENGTH])) ?></p>

                <label for="confirm_password"><?= e(t('settings.confirm')) ?></label>
                <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required<?= field_error_attrs($errors, 'confirm_password') ?>>
                <?= field_error($errors, 'confirm_password') ?>

                <button type="submit"><?= e(t('settings.submit')) ?></button>
            </form>
        </section>
<?php
admin_footer();

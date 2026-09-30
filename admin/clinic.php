<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_login();

// The site describes a single clinic, so the table holds at most one row.
$clinic = db()->query('SELECT * FROM clinic ORDER BY id LIMIT 1')->fetch() ?: null;

$fields = ['name', 'description', 'phone', 'email', 'address'];
$values = [];
foreach ($fields as $field) {
    $values[$field] = $clinic[$field] ?? '';
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($fields as $field) {
        $values[$field] = trim($_POST[$field] ?? '');
    }

    if ($values['name'] === '') {
        $errors['name'] = t('clinic.name_required');
    }
    if ($values['email'] !== '' && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = t('clinic.email_invalid');
    }

    if (!$errors) {
        // Optional fields are stored as NULL when left empty.
        $params = [
            $values['name'],
            $values['description'] ?: null,
            $values['phone'] ?: null,
            $values['email'] ?: null,
            $values['address'] ?: null,
        ];

        if ($clinic) {
            $stmt = db()->prepare(
                'UPDATE clinic SET name = ?, description = ?, phone = ?, email = ?, address = ?,
                 updated_at = CURRENT_TIMESTAMP WHERE id = ?'
            );
            $stmt->execute([...$params, $clinic['id']]);
        } else {
            $stmt = db()->prepare('INSERT INTO clinic (name, description, phone, email, address) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute($params);
        }

        flash('success', t('clinic.saved'));
        redirect('clinic.php');
    }
}

admin_header(t('clinic.title'));
?>
        <h1><?= e(t('clinic.title')) ?></h1>

        <?= form_errors_alert($errors) ?>

        <form method="post" action="clinic.php" class="form-card" novalidate>
            <?= csrf_field() ?>

            <label for="name"><?= e(t('clinic.name')) ?> <span class="required">*</span></label>
            <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" required<?= field_error_attrs($errors, 'name') ?>>
            <?= field_error($errors, 'name') ?>

            <label for="description"><?= e(t('clinic.description')) ?></label>
            <textarea id="description" name="description" rows="5"><?= e($values['description']) ?></textarea>

            <label for="phone"><?= e(t('clinic.phone')) ?></label>
            <input type="tel" id="phone" name="phone" value="<?= e($values['phone']) ?>" autocomplete="tel">

            <label for="email"><?= e(t('clinic.email')) ?></label>
            <input type="email" id="email" name="email" value="<?= e($values['email']) ?>" autocomplete="email"<?= field_error_attrs($errors, 'email') ?>>
            <?= field_error($errors, 'email') ?>

            <label for="address"><?= e(t('clinic.address')) ?></label>
            <textarea id="address" name="address" rows="3"><?= e($values['address']) ?></textarea>

            <p class="form-hint"><span class="required">*</span> <?= e(t('admin.required_hint')) ?></p>

            <button type="submit"><?= e(t('admin.save')) ?></button>
        </form>
<?php
admin_footer();

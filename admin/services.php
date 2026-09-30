<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_login();

function find_service(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

// ?edit=ID shows the edit form for that service; otherwise the form adds a new one.
$editing = null;
if (isset($_GET['edit'])) {
    $editing = find_service((int) $_GET['edit']);
    if (!$editing) {
        flash('error', t('services.not_found'));
        redirect('services.php');
    }
}

$values = [
    'name'        => $editing['name'] ?? '',
    'description' => $editing['description'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM services WHERE id = ?');
        $stmt->execute([$id]);
        flash($stmt->rowCount() ? 'success' : 'error', t($stmt->rowCount() ? 'services.deleted' : 'services.not_found'));
        redirect('services.php');
    }

    if ($action === 'save') {
        $values['name'] = trim($_POST['name'] ?? '');
        $values['description'] = trim($_POST['description'] ?? '');

        if ($values['name'] === '') {
            $errors['name'] = t('services.name_required');
        }

        if (!$errors) {
            if ($id) {
                $stmt = db()->prepare(
                    'UPDATE services SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
                );
                $stmt->execute([$values['name'], $values['description'] ?: null, $id]);
                flash($stmt->rowCount() ? 'success' : 'error', t($stmt->rowCount() ? 'services.updated' : 'services.not_found'));
            } else {
                $stmt = db()->prepare('INSERT INTO services (name, description) VALUES (?, ?)');
                $stmt->execute([$values['name'], $values['description'] ?: null]);
                flash('success', t('services.added'));
            }

            redirect('services.php');
        }
    }
}

$services = db()->query('SELECT id, name, description FROM services ORDER BY name COLLATE NOCASE')->fetchAll();

admin_header(t('services.title'));
?>
        <h1><?= e(t('services.title')) ?></h1>

        <section class="form-card">
            <h2><?= e(t($editing ? 'services.edit_heading' : 'services.add_heading')) ?></h2>

            <?= form_errors_alert($errors) ?>

            <form method="post" action="services.php<?= $editing ? '?edit=' . (int) $editing['id'] : '' ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
<?php if ($editing): ?>
                <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
<?php endif; ?>

                <label for="name"><?= e(t('services.name')) ?> <span class="required">*</span></label>
                <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" required<?= field_error_attrs($errors, 'name') ?>>
                <?= field_error($errors, 'name') ?>

                <label for="description"><?= e(t('services.description')) ?></label>
                <textarea id="description" name="description" rows="4"><?= e($values['description']) ?></textarea>

                <p class="form-hint"><span class="required">*</span> <?= e(t('admin.required_hint')) ?></p>

                <div class="form-actions">
                    <button type="submit"><?= e(t($editing ? 'admin.save' : 'services.add')) ?></button>
<?php if ($editing): ?>
                    <a href="services.php"><?= e(t('admin.cancel')) ?></a>
<?php endif; ?>
                </div>
            </form>
        </section>

        <h2><?= e(t('services.list_heading')) ?></h2>

<?php if (!$services): ?>
        <p class="empty"><?= e(t('services.empty')) ?></p>
<?php else: ?>
        <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col"><?= e(t('services.name')) ?></th>
                    <th scope="col"><?= e(t('services.description')) ?></th>
                    <th scope="col"><span class="visually-hidden"><?= e(t('admin.actions')) ?></span></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($services as $service): ?>
                <tr<?= $editing && $editing['id'] === $service['id'] ? ' class="is-editing"' : '' ?>>
                    <td><?= e($service['name']) ?></td>
                    <td class="muted"><?= nl2br(e($service['description'])) ?></td>
                    <td class="row-actions">
                        <a href="services.php?edit=<?= (int) $service['id'] ?>"><?= e(t('admin.edit')) ?></a>
                        <form method="post" action="services.php" data-confirm="<?= e(t('services.confirm_delete', ['name' => $service['name']])) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                            <button type="submit" class="button-link danger"><?= e(t('admin.delete')) ?></button>
                        </form>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
        </div>
<?php endif; ?>
<?php
admin_footer();

<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_login();

function find_specialty(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM specialties WHERE id = ?');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

/**
 * Whether another specialty already uses this name. Names are compared ignoring case, like the UNIQUE column.
 */
function specialty_name_taken(string $name, int $exceptId): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM specialties WHERE name = ? COLLATE NOCASE AND id != ?');
    $stmt->execute([$name, $exceptId]);

    return (int) $stmt->fetchColumn() > 0;
}

// ?edit=ID shows the edit form for that specialty; otherwise the form adds a new one.
$editing = null;
if (isset($_GET['edit'])) {
    $editing = find_specialty((int) $_GET['edit']);
    if (!$editing) {
        flash('error', t('specialties.not_found'));
        redirect('specialties.php');
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

    // Deleting a specialty unlinks its doctors (ON DELETE CASCADE) but leaves the doctors themselves alone.
    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM specialties WHERE id = ?');
        $stmt->execute([$id]);
        flash($stmt->rowCount() ? 'success' : 'error', t($stmt->rowCount() ? 'specialties.deleted' : 'specialties.not_found'));
        redirect('specialties.php');
    }

    if ($action === 'save') {
        $values['name'] = trim($_POST['name'] ?? '');
        $values['description'] = trim($_POST['description'] ?? '');

        if ($values['name'] === '') {
            $errors['name'] = t('specialties.name_required');
        } elseif (specialty_name_taken($values['name'], $id)) {
            $errors['name'] = t('specialties.name_taken');
        }

        if (!$errors) {
            if ($id) {
                $stmt = db()->prepare(
                    'UPDATE specialties SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
                );
                $stmt->execute([$values['name'], $values['description'] ?: null, $id]);
                flash($stmt->rowCount() ? 'success' : 'error', t($stmt->rowCount() ? 'specialties.updated' : 'specialties.not_found'));
            } else {
                $stmt = db()->prepare('INSERT INTO specialties (name, description) VALUES (?, ?)');
                $stmt->execute([$values['name'], $values['description'] ?: null]);
                flash('success', t('specialties.added'));
            }

            redirect('specialties.php');
        }
    }
}

// Counts every linked doctor, inactive ones included, so the number matches the doctors admin.
$specialties = db()->query(
    'SELECT s.id, s.name, s.description, COUNT(ds.doctor_id) AS doctor_count
     FROM specialties s
     LEFT JOIN doctor_specialties ds ON ds.specialty_id = s.id
     GROUP BY s.id
     ORDER BY s.name COLLATE NOCASE'
)->fetchAll();

admin_header(t('specialties.title'));
?>
        <h1><?= e(t('specialties.title')) ?></h1>

        <section class="form-card">
            <h2><?= e(t($editing ? 'specialties.edit_heading' : 'specialties.add_heading')) ?></h2>

            <?= form_errors_alert($errors) ?>

            <form method="post" action="specialties.php<?= $editing ? '?edit=' . (int) $editing['id'] : '' ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
<?php if ($editing): ?>
                <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
<?php endif; ?>

                <label for="name"><?= e(t('specialties.name')) ?> <span class="required">*</span></label>
                <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" placeholder="<?= e(t('specialties.name_placeholder')) ?>" required<?= field_error_attrs($errors, 'name') ?>>
                <?= field_error($errors, 'name') ?>

                <label for="description"><?= e(t('specialties.description')) ?></label>
                <textarea id="description" name="description" rows="4"><?= e($values['description']) ?></textarea>

                <p class="form-hint"><span class="required">*</span> <?= e(t('admin.required_hint')) ?></p>
                <p class="form-hint"><?= e(t('specialties.assign_hint')) ?></p>

                <div class="form-actions">
                    <button type="submit"><?= e(t($editing ? 'admin.save' : 'specialties.add')) ?></button>
<?php if ($editing): ?>
                    <a href="specialties.php"><?= e(t('admin.cancel')) ?></a>
<?php endif; ?>
                </div>
            </form>
        </section>

        <h2><?= e(t('specialties.list_heading')) ?></h2>

<?php if (!$specialties): ?>
        <p class="empty"><?= e(t('specialties.empty')) ?></p>
<?php else: ?>
        <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col"><?= e(t('specialties.name')) ?></th>
                    <th scope="col"><?= e(t('specialties.description')) ?></th>
                    <th scope="col"><?= e(t('specialties.doctors')) ?></th>
                    <th scope="col"><span class="visually-hidden"><?= e(t('admin.actions')) ?></span></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($specialties as $specialty): ?>
                <tr<?= $editing && $editing['id'] === $specialty['id'] ? ' class="is-editing"' : '' ?>>
                    <td><?= e($specialty['name']) ?></td>
                    <td class="muted"><?= nl2br(e($specialty['description'])) ?></td>
                    <td><?= (int) $specialty['doctor_count'] ?></td>
                    <td class="row-actions">
                        <a href="specialties.php?edit=<?= (int) $specialty['id'] ?>"><?= e(t('admin.edit')) ?></a>
                        <form method="post" action="specialties.php" data-confirm="<?= e(t('specialties.confirm_delete', ['name' => $specialty['name']])) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $specialty['id'] ?>">
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

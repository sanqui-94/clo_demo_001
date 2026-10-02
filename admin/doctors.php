<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/photos.php';

require_login();

function find_doctor(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM doctors WHERE id = ?');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

/**
 * Ids of the specialties linked to a doctor.
 */
function doctor_specialty_ids(int $doctorId): array
{
    $stmt = db()->prepare('SELECT specialty_id FROM doctor_specialties WHERE doctor_id = ?');
    $stmt->execute([$doctorId]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Replaces all of a doctor's specialty links with $specialtyIds.
 */
function save_doctor_specialties(int $doctorId, array $specialtyIds): void
{
    db()->prepare('DELETE FROM doctor_specialties WHERE doctor_id = ?')->execute([$doctorId]);

    $stmt = db()->prepare('INSERT INTO doctor_specialties (doctor_id, specialty_id) VALUES (?, ?)');
    foreach ($specialtyIds as $specialtyId) {
        $stmt->execute([$doctorId, $specialtyId]);
    }
}

// ?edit=ID shows the edit form for that doctor; otherwise the form adds a new one.
$editing = null;
if (isset($_GET['edit'])) {
    $editing = find_doctor((int) $_GET['edit']);
    if (!$editing) {
        flash('error', t('doctors.not_found'));
        redirect('doctors.php');
    }
}
$formUrl = 'doctors.php' . ($editing ? '?edit=' . (int) $editing['id'] : '');

if (post_too_large()) {
    flash('error', t('photo.too_large', ['max' => photo_max_size_label()]));
    redirect($formUrl);
}

$values = [
    'name'  => $editing['name'] ?? '',
    'title' => $editing['title'] ?? '',
    'bio'   => $editing['bio'] ?? '',
];
$specialties = db()->query('SELECT id, name FROM specialties ORDER BY name COLLATE NOCASE')->fetchAll();
$selectedSpecialties = $editing ? doctor_specialty_ids((int) $editing['id']) : [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    // Deactivating hides the doctor from the public site but keeps the record, so it can be restored.
    if ($action === 'deactivate' || $action === 'restore') {
        $sql = $action === 'deactivate'
            ? 'UPDATE doctors SET is_active = 0, deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
            : 'UPDATE doctors SET is_active = 1, deleted_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?';
        $stmt = db()->prepare($sql);
        $stmt->execute([$id]);

        if ($stmt->rowCount()) {
            flash('success', t($action === 'deactivate' ? 'doctors.deactivated' : 'doctors.restored'));
        } else {
            flash('error', t('doctors.not_found'));
        }
        redirect('doctors.php');
    }

    if ($action === 'save') {
        foreach (array_keys($values) as $field) {
            $values[$field] = trim($_POST[$field] ?? '');
        }

        if ($values['name'] === '') {
            $errors['name'] = t('doctors.name_required');
        }

        // Ignores ids that aren't real specialties, e.g. one deleted while this form was open.
        $selectedSpecialties = array_values(array_intersect(
            array_column($specialties, 'id'),
            array_map('intval', (array) ($_POST['specialties'] ?? []))
        ));

        $photo = $_FILES['photo'] ?? null;
        $hasPhoto = $photo && $photo['error'] !== UPLOAD_ERR_NO_FILE;
        if ($hasPhoto && ($photoError = photo_upload_error($photo))) {
            $errors['photo'] = $photoError;
        }

        $existing = $id ? find_doctor($id) : null;
        if ($id && !$existing) {
            flash('error', t('doctors.not_found'));
            redirect('doctors.php');
        }

        $photoPath = $existing['photo_path'] ?? null;
        if (!$errors && $hasPhoto) {
            $photoPath = store_photo($photo);
            if ($photoPath === null) {
                $errors['photo'] = t('photo.save_failed');
            }
        }

        if (!$errors) {
            $params = [$values['name'], $values['title'] ?: null, $values['bio'] ?: null, $photoPath];

            db()->beginTransaction();
            if ($existing) {
                $stmt = db()->prepare(
                    'UPDATE doctors SET name = ?, title = ?, bio = ?, photo_path = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
                );
                $stmt->execute([...$params, $id]);
            } else {
                $stmt = db()->prepare('INSERT INTO doctors (name, title, bio, photo_path) VALUES (?, ?, ?, ?)');
                $stmt->execute($params);
                $id = (int) db()->lastInsertId();
            }
            save_doctor_specialties($id, $selectedSpecialties);
            db()->commit();

            if ($existing) {
                // The old photo is only removed once the new one is saved and recorded.
                if ($photoPath !== $existing['photo_path']) {
                    delete_photo($existing['photo_path']);
                }
                flash('success', t('doctors.updated'));
            } else {
                flash('success', t('doctors.added'));
            }

            redirect('doctors.php');
        }
    }
}

// The ', ' separator lists each doctor's specialties in one column.
$doctors = db()->query(
    "SELECT d.id, d.name, d.title, d.photo_path, d.is_active,
            (SELECT GROUP_CONCAT(name, ', ') FROM (
                SELECT s.name FROM doctor_specialties ds
                JOIN specialties s ON s.id = ds.specialty_id
                WHERE ds.doctor_id = d.id
                ORDER BY s.name COLLATE NOCASE
            )) AS specialty_names
     FROM doctors d
     ORDER BY d.is_active DESC, d.name COLLATE NOCASE"
)->fetchAll();

admin_header(t('doctors.title'));
?>
        <h1><?= e(t('doctors.title')) ?></h1>

        <section class="form-card">
            <h2><?= e(t($editing ? 'doctors.edit_heading' : 'doctors.add_heading')) ?></h2>

            <?= form_errors_alert($errors) ?>

            <form method="post" action="<?= e($formUrl) ?>" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
<?php if ($editing): ?>
                <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
<?php endif; ?>

                <label for="name"><?= e(t('doctors.name')) ?> <span class="required">*</span></label>
                <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" required<?= field_error_attrs($errors, 'name') ?>>
                <?= field_error($errors, 'name') ?>

                <label for="title"><?= e(t('doctors.doctor_title')) ?></label>
                <input type="text" id="title" name="title" value="<?= e($values['title']) ?>" placeholder="<?= e(t('doctors.title_placeholder')) ?>">

                <fieldset class="checkbox-group">
                    <legend><?= e(t('doctors.specialties')) ?></legend>
<?php if ($specialties): ?>
<?php foreach ($specialties as $specialty): ?>
                    <label class="checkbox">
                        <input type="checkbox" name="specialties[]" value="<?= (int) $specialty['id'] ?>"<?= in_array($specialty['id'], $selectedSpecialties, true) ? ' checked' : '' ?>>
                        <?= e($specialty['name']) ?>
                    </label>
<?php endforeach; ?>
<?php else: ?>
                    <p class="form-hint"><?= e(t('doctors.no_specialties')) ?> <a href="specialties.php"><?= e(t('doctors.go_to_specialties')) ?></a></p>
<?php endif; ?>
                </fieldset>

                <label for="bio"><?= e(t('doctors.bio')) ?></label>
                <textarea id="bio" name="bio" rows="6"><?= e($values['bio']) ?></textarea>

                <label for="photo"><?= e(t('doctors.photo')) ?></label>
<?php if (!empty($editing['photo_path'])): ?>
                <img class="photo-preview" src="../public/<?= e($editing['photo_path']) ?>" alt="<?= e(t('doctors.current_photo')) ?>">
<?php endif; ?>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp"<?= field_error_attrs($errors, 'photo') ?>>
                <?= field_error($errors, 'photo') ?>
                <p class="form-hint">
                    <?= e(t('doctors.photo_hint', ['max' => photo_max_size_label()])) ?>
<?php if (!empty($editing['photo_path'])): ?>
                    <?= e(t('doctors.photo_replace_hint')) ?>
<?php endif; ?>
<?php if ($errors && !empty($_FILES['photo']['name'])): ?>
                    <?= e(t('doctors.photo_reselect_hint')) ?>
<?php endif; ?>
                </p>

                <p class="form-hint"><span class="required">*</span> <?= e(t('admin.required_hint')) ?></p>

                <div class="form-actions">
                    <button type="submit"><?= e(t($editing ? 'admin.save' : 'doctors.add')) ?></button>
<?php if ($editing): ?>
                    <a href="doctors.php"><?= e(t('admin.cancel')) ?></a>
<?php endif; ?>
                </div>
            </form>
        </section>

        <h2><?= e(t('doctors.list_heading')) ?></h2>

<?php if (!$doctors): ?>
        <p class="empty"><?= e(t('doctors.empty')) ?></p>
<?php else: ?>
        <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col"><span class="visually-hidden"><?= e(t('doctors.photo')) ?></span></th>
                    <th scope="col"><?= e(t('doctors.name')) ?></th>
                    <th scope="col"><?= e(t('doctors.doctor_title')) ?></th>
                    <th scope="col"><?= e(t('doctors.specialties')) ?></th>
                    <th scope="col"><?= e(t('doctors.status')) ?></th>
                    <th scope="col"><span class="visually-hidden"><?= e(t('admin.actions')) ?></span></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($doctors as $doctor): ?>
                <tr class="<?= $doctor['is_active'] ? '' : 'is-inactive' ?><?= $editing && $editing['id'] === $doctor['id'] ? ' is-editing' : '' ?>">
                    <td>
<?php if ($doctor['photo_path']): ?>
                        <img class="thumb" src="../public/<?= e($doctor['photo_path']) ?>" alt="">
<?php else: ?>
                        <span class="thumb thumb-empty" aria-hidden="true"></span>
<?php endif; ?>
                    </td>
                    <td><?= e($doctor['name']) ?></td>
                    <td class="muted"><?= e($doctor['title']) ?></td>
                    <td class="muted"><?= e($doctor['specialty_names']) ?></td>
                    <td>
                        <span class="badge <?= $doctor['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                            <?= e(t($doctor['is_active'] ? 'doctors.active' : 'doctors.inactive')) ?>
                        </span>
                    </td>
                    <td class="row-actions">
                        <a href="doctors.php?edit=<?= (int) $doctor['id'] ?>"><?= e(t('admin.edit')) ?></a>
                        <form method="post" action="doctors.php"<?= $doctor['is_active'] ? ' data-confirm="' . e(t('doctors.confirm_deactivate', ['name' => $doctor['name']])) . '"' : '' ?>>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="<?= $doctor['is_active'] ? 'deactivate' : 'restore' ?>">
                            <input type="hidden" name="id" value="<?= (int) $doctor['id'] ?>">
                            <button type="submit" class="button-link<?= $doctor['is_active'] ? ' danger' : '' ?>">
                                <?= e(t($doctor['is_active'] ? 'doctors.deactivate' : 'doctors.restore')) ?>
                            </button>
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

<?php
require_once __DIR__ . '/../includes/public_layout.php';

// One row per specialty and active doctor; specialties without active doctors come back once with no doctor.
// Deactivated doctors are filtered in the JOIN, not the WHERE, so their specialties still show.
$rows = db()->query(
    'SELECT s.id, s.name, s.description,
            d.id AS doctor_id, d.name AS doctor_name, d.title AS doctor_title, d.photo_path
     FROM specialties s
     LEFT JOIN doctor_specialties ds ON ds.specialty_id = s.id
     LEFT JOIN doctors d ON d.id = ds.doctor_id AND d.is_active = 1
     ORDER BY s.name COLLATE NOCASE, s.id, d.name COLLATE NOCASE'
)->fetchAll();

$specialties = [];
foreach ($rows as $row) {
    $specialties[$row['id']] ??= ['name' => $row['name'], 'description' => $row['description'], 'doctors' => []];

    if ($row['doctor_id'] !== null) {
        $specialties[$row['id']]['doctors'][] = [
            'id'         => $row['doctor_id'],
            'name'       => $row['doctor_name'],
            'title'      => $row['doctor_title'],
            'photo_path' => $row['photo_path'],
        ];
    }
}

public_header(t('specialties_page.title'), t('specialties_page.meta_description', ['name' => clinic_name()]), 'specialties');
?>
        <section class="page-intro">
            <div class="wrap">
                <h1><?= e(t('specialties_page.title')) ?></h1>
                <p class="lead"><?= e(t('specialties_page.intro')) ?></p>
            </div>
        </section>

        <section class="section section-tight">
            <div class="wrap">
<?php if (!$specialties): ?>
                <p class="muted"><?= e(t('specialties_page.empty')) ?></p>
<?php else: ?>
<?php foreach ($specialties as $id => $specialty): ?>
                <section class="specialty" id="specialty-<?= (int) $id ?>" aria-labelledby="specialty-<?= (int) $id ?>-heading">
                    <h2 id="specialty-<?= (int) $id ?>-heading"><?= e($specialty['name']) ?></h2>
<?php if ($specialty['description']): ?>
                    <div class="specialty-description"><?= text_paragraphs($specialty['description']) ?></div>
<?php endif; ?>
<?php if ($specialty['doctors']): ?>
                    <ul class="specialty-doctors">
<?php foreach ($specialty['doctors'] as $doctor): ?>
                        <li>
                            <a class="specialty-doctor" href="doctor.php?id=<?= (int) $doctor['id'] ?>">
                                <?= doctor_photo($doctor, 'specialty-doctor-photo') ?>
                                <span>
                                    <strong><?= e($doctor['name']) ?></strong>
<?php if ($doctor['title']): ?>
                                    <span class="doctor-title"><?= e($doctor['title']) ?></span>
<?php endif; ?>
                                </span>
                            </a>
                        </li>
<?php endforeach; ?>
                    </ul>
<?php else: ?>
                    <p class="muted"><?= e(t('specialties_page.no_doctors')) ?></p>
<?php endif; ?>
                </section>
<?php endforeach; ?>
<?php endif; ?>
            </div>
        </section>
<?php
public_footer();

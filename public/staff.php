<?php
require_once __DIR__ . '/../includes/public_layout.php';

// Deactivated doctors stay in the database but are never shown on the public site.
$doctors = db()->query(
    'SELECT id, name, title, bio, photo_path FROM doctors WHERE is_active = 1 ORDER BY name COLLATE NOCASE'
)->fetchAll();

public_header(t('staff.title'), t('staff.meta_description', ['name' => clinic_name()]), 'staff');
?>
        <section class="page-intro">
            <div class="wrap">
                <h1><?= e(t('staff.title')) ?></h1>
                <p class="lead"><?= e(t('staff.intro')) ?></p>
            </div>
        </section>

        <section class="section section-tight">
            <div class="wrap">
<?php if (!$doctors): ?>
                <p class="muted"><?= e(t('staff.empty')) ?></p>
<?php else: ?>
                <ul class="doctor-grid">
<?php foreach ($doctors as $doctor): ?>
                    <li class="doctor-card">
                        <?= doctor_photo($doctor, 'doctor-card-photo') ?>

                        <div class="doctor-card-body">
                            <h2><a href="doctor.php?id=<?= (int) $doctor['id'] ?>"><?= e($doctor['name']) ?></a></h2>
<?php if ($doctor['title']): ?>
                            <p class="doctor-title"><?= e($doctor['title']) ?></p>
<?php endif; ?>
<?php if ($doctor['bio']): ?>
                            <p class="doctor-card-bio"><?= e(excerpt($doctor['bio'], 140)) ?></p>
<?php endif; ?>
                            <span class="doctor-card-more" aria-hidden="true"><?= e(t('staff.view_profile')) ?> →</span>
                        </div>
                    </li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
            </div>
        </section>
<?php
public_footer();

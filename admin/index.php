<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_login();

$sections = [
    ['clinic.php',      t('dashboard.clinic'),      t('dashboard.clinic_desc')],
    ['services.php',    t('dashboard.services'),    t('dashboard.services_desc')],
    ['specialties.php', t('dashboard.specialties'), t('dashboard.specialties_desc')],
    ['doctors.php',     t('dashboard.doctors'),     t('dashboard.doctors_desc')],
    ['schedules.php',   t('dashboard.schedules'),   t('dashboard.schedules_desc')],
    ['settings.php',    t('dashboard.settings'),    t('dashboard.settings_desc')],
];

admin_header(t('dashboard.title'));
?>
        <h1><?= e(t('dashboard.title')) ?></h1>
        <ul class="card-grid">
<?php foreach ($sections as [$href, $label, $description]): ?>
            <li>
                <a class="card" href="<?= e($href) ?>">
                    <strong><?= e($label) ?></strong>
                    <span><?= e($description) ?></span>
                </a>
            </li>
<?php endforeach; ?>
        </ul>
<?php
admin_footer();

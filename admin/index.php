<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_login();

$sections = [
    ['clinic.php',    'Clinic info', 'Name, description, and contact details.'],
    ['services.php',  'Services',    'Add, edit, and remove the services offered.'],
    ['doctors.php',   'Doctors',     'Manage doctor profiles and photos.'],
    ['schedules.php', 'Schedules',   'Set each doctor\'s weekly availability.'],
    ['settings.php',  'Settings',    'Change your password.'],
];

admin_header('Dashboard');
?>
        <h1>Dashboard</h1>
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

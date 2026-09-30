<?php
require_once __DIR__ . '/../includes/public_layout.php';
require_once __DIR__ . '/../includes/schedules.php';

// Deactivated doctors get the same "not found" page as ids that never existed.
$stmt = db()->prepare('SELECT id, name, title, bio, photo_path FROM doctors WHERE id = ? AND is_active = 1');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$doctor = $stmt->fetch();

if (!$doctor) {
    http_response_code(404);
    public_header(t('doctor.not_found_title'), '', 'staff');
    ?>
        <section class="page-intro">
            <div class="wrap">
                <h1><?= e(t('doctor.not_found_title')) ?></h1>
                <p class="lead"><?= e(t('doctor.not_found')) ?></p>
                <p><a class="button" href="staff.php"><?= e(t('doctor.see_team')) ?></a></p>
            </div>
        </section>
<?php
    public_footer();
    exit;
}

$clinic = clinic_info();
$phone = $clinic['phone'] ?? '';
$email = $clinic['email'] ?? '';
$scheduleGroups = group_schedule_days(doctor_schedule((int) $doctor['id']));
$mailto = $email !== ''
    ? 'mailto:' . $email . '?subject=' . rawurlencode(t('doctor.mail_subject', ['name' => $doctor['name']]))
    : '';

// Without an online booking system, booking means calling (or else emailing) the clinic.
$bookingUrl = BOOKING_URL ?: ($phone !== '' ? phone_href($phone) : $mailto);

$description = $doctor['bio']
    ? excerpt($doctor['bio'], 155)
    : t('doctor.meta_description', ['name' => $doctor['name'], 'clinic' => clinic_name()]);

public_header($doctor['name'], $description, 'staff');
?>
        <div class="wrap doctor-page">
            <p class="back-link"><a href="staff.php">← <?= e(t('doctor.back')) ?></a></p>

            <article class="doctor-profile">
                <?= doctor_photo($doctor, 'doctor-profile-photo') ?>

                <div class="doctor-profile-body">
                    <h1><?= e($doctor['name']) ?></h1>
<?php if ($doctor['title']): ?>
                    <p class="doctor-title"><?= e($doctor['title']) ?></p>
<?php endif; ?>
<?php if ($doctor['bio']): ?>
                    <div class="doctor-bio"><?= text_paragraphs($doctor['bio']) ?></div>
<?php endif; ?>

                    <section class="schedule-card" aria-labelledby="schedule-heading">
                        <h2 id="schedule-heading"><?= e(t('doctor.schedule_heading')) ?></h2>
<?php if ($scheduleGroups): ?>
                        <table class="schedule-table">
                            <tbody>
<?php foreach ($scheduleGroups as $group): ?>
                                <tr>
                                    <th scope="row"><?= e(format_day_range($group['from'], $group['to'])) ?></th>
                                    <td>
<?php foreach ($group['slots'] as $slot): ?>
                                        <span class="schedule-slot"><?= e(format_slots([$slot])) ?></span>
<?php endforeach; ?>
                                    </td>
                                </tr>
<?php endforeach; ?>
                            </tbody>
                        </table>
<?php if ($bookingUrl !== ''): ?>
                        <div class="schedule-actions">
<?php if (BOOKING_URL): ?>
                            <a class="button" href="<?= e($bookingUrl) ?>" target="_blank" rel="noopener">
                                <?= e(t('doctor.book')) ?><span class="visually-hidden"> <?= e(t('site.new_tab')) ?></span>
                            </a>
<?php else: ?>
                            <a class="button" href="<?= e($bookingUrl) ?>"><?= e(t('doctor.book')) ?></a>
<?php if ($phone !== ''): ?>
                            <span class="muted"><?= e(t('doctor.book_by_phone', ['phone' => $phone])) ?></span>
<?php endif; ?>
<?php endif; ?>
                        </div>
<?php endif; ?>
<?php else: ?>
                        <p class="schedule-empty"><strong><?= e(t('doctor.no_schedule')) ?></strong> <?= e(t('doctor.no_schedule_hint')) ?></p>
<?php if ($mailto !== '' || $phone !== ''): ?>
                        <div class="schedule-actions">
<?php if ($mailto !== ''): ?>
                            <a class="button" href="<?= e($mailto) ?>"><?= e(t('doctor.contact')) ?></a>
<?php endif; ?>
<?php if ($phone !== ''): ?>
                            <a class="button<?= $mailto !== '' ? ' button-secondary' : '' ?>" href="<?= e(phone_href($phone)) ?>"><?= e(t('home.call', ['phone' => $phone])) ?></a>
<?php endif; ?>
                        </div>
<?php endif; ?>
<?php endif; ?>
                    </section>
                </div>
            </article>
        </div>
<?php
public_footer();

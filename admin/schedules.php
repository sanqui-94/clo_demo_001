<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/schedules.php';

require_login();

/**
 * The week the form is drawn from: [day => ['enabled' => bool, 'slots' => [['start' => .., 'end' => ..], ...]]].
 */
function week_from_schedule(array $schedule): array
{
    $week = [];
    foreach (WEEKDAYS as $day) {
        $week[$day] = ['enabled' => isset($schedule[$day]), 'slots' => $schedule[$day] ?? []];
    }

    return $week;
}

/**
 * Accepts 'H:MM', 'HH:MM' or 'HH:MM:SS' and returns 'HH:MM', or null if it isn't a valid time of day.
 */
function normalize_time(string $value): ?string
{
    if (!preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $m) || (int) $m[1] > 23 || (int) $m[2] > 59) {
        return null;
    }

    return sprintf('%02d:%02d', $m[1], $m[2]);
}

/**
 * Reads and validates the submitted week. Returns [$week, $errors].
 * Unchecked days are saved as unavailable, whatever their times say. Slots left completely empty are dropped.
 * Error keys are 'day-N' (a checked day with no slots) and 'day-N-slot-I' (one slot).
 */
function read_week_form(array $input): array
{
    $week = [];
    $errors = [];

    foreach (WEEKDAYS as $day) {
        $dayInput = is_array($input[$day] ?? null) ? $input[$day] : [];
        $enabled = !empty($dayInput['enabled']);
        $slots = [];

        if ($enabled && is_array($dayInput['slots'] ?? null)) {
            foreach ($dayInput['slots'] as $slot) {
                $start = is_string($slot['start'] ?? null) ? trim($slot['start']) : '';
                $end = is_string($slot['end'] ?? null) ? trim($slot['end']) : '';
                if ($start !== '' || $end !== '') {
                    $slots[] = ['start' => normalize_time($start) ?? $start, 'end' => normalize_time($end) ?? $end];
                }
            }
        }

        $dayErrors = [];
        foreach ($slots as $i => $slot) {
            if (normalize_time($slot['start']) === null || normalize_time($slot['end']) === null) {
                $dayErrors["day-$day-slot-$i"] = t('schedules.time_invalid');
            } elseif ($slot['start'] >= $slot['end']) {
                $dayErrors["day-$day-slot-$i"] = t('schedules.time_order');
            }
        }

        // Overlaps are only checked once every slot of the day is valid on its own.
        if (!$dayErrors) {
            $byStart = $slots;
            uasort($byStart, fn(array $a, array $b) => strcmp($a['start'], $b['start']));
            $previous = null;
            foreach ($byStart as $i => $slot) {
                if ($previous !== null && $slot['start'] < $previous['end']) {
                    $dayErrors["day-$day-slot-$i"] = t('schedules.overlap');
                }
                $previous = $slot;
            }
        }

        if ($enabled && !$slots) {
            $dayErrors["day-$day"] = t('schedules.day_needs_slot');
        }

        $week[$day] = ['enabled' => $enabled, 'slots' => $slots];
        $errors += $dayErrors;
    }

    return [$week, $errors];
}

/**
 * Replaces the doctor's whole schedule with the given week.
 */
function save_schedule(int $doctorId, array $week): void
{
    $pdo = db();
    $pdo->beginTransaction();

    $pdo->prepare('DELETE FROM doctor_schedules WHERE doctor_id = ?')->execute([$doctorId]);

    $insert = $pdo->prepare('INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)');
    foreach ($week as $day => $dayValues) {
        foreach ($dayValues['enabled'] ? $dayValues['slots'] : [] as $slot) {
            $insert->execute([$doctorId, $day, $slot['start'], $slot['end']]);
        }
    }

    $pdo->commit();
}

/**
 * One start/end row of the form. $index is '__INDEX__' in the <template> the "Add time slot" button copies.
 */
function slot_row(int $day, int|string $index, array $slot, array $errors): string
{
    $field = "day-$day-slot-$index";
    $name = "days[$day][slots][$index]";
    $errorAttrs = field_error_attrs($errors, $field);

    return '<div class="slot">'
        . '<div class="slot-times">'
        . '<span class="time-field">'
        . '<label for="' . $field . '-start">' . e(t('schedules.from')) . '</label>'
        . '<input type="time" id="' . $field . '-start" name="' . $name . '[start]" value="' . e($slot['start']) . '"' . $errorAttrs . '>'
        . '</span>'
        . '<span class="time-field">'
        . '<label for="' . $field . '-end">' . e(t('schedules.to')) . '</label>'
        . '<input type="time" id="' . $field . '-end" name="' . $name . '[end]" value="' . e($slot['end']) . '"' . $errorAttrs . '>'
        . '</span>'
        . '<button type="button" class="button-link danger" data-remove-slot>' . e(t('schedules.remove_slot')) . '</button>'
        . '</div>'
        . field_error($errors, $field)
        . '</div>';
}

$doctors = db()->query('SELECT id, name, title FROM doctors WHERE is_active = 1 ORDER BY name COLLATE NOCASE')->fetchAll();

// ?doctor=ID picks whose schedule the form edits. Only active doctors can be picked.
$doctor = null;
if (($_GET['doctor'] ?? '') !== '') {
    foreach ($doctors as $candidate) {
        if ((int) $candidate['id'] === (int) $_GET['doctor']) {
            $doctor = $candidate;
        }
    }
    if (!$doctor) {
        flash('error', t('schedules.doctor_not_found'));
        redirect('schedules.php');
    }
}

$formUrl = $doctor ? 'schedules.php?doctor=' . (int) $doctor['id'] : 'schedules.php';
$savedSchedule = $doctor ? doctor_schedule((int) $doctor['id']) : [];
$week = week_from_schedule($savedSchedule);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!$doctor) {
        flash('error', t('schedules.doctor_not_found'));
        redirect('schedules.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'clear') {
        db()->prepare('DELETE FROM doctor_schedules WHERE doctor_id = ?')->execute([(int) $doctor['id']]);
        flash('success', t('schedules.cleared', ['name' => $doctor['name']]));
        redirect($formUrl);
    }

    if ($action === 'save') {
        [$week, $errors] = read_week_form(is_array($_POST['days'] ?? null) ? $_POST['days'] : []);

        if (!$errors) {
            save_schedule((int) $doctor['id'], $week);
            flash('success', t('schedules.saved', ['name' => $doctor['name']]));
            redirect($formUrl);
        }
    }
}

admin_header(t('schedules.title'));
?>
        <h1><?= e(t('schedules.title')) ?></h1>

<?php if (!$doctors): ?>
        <p class="empty">
            <?= e(t('schedules.no_doctors')) ?>
            <a href="doctors.php"><?= e(t('schedules.go_to_doctors')) ?></a>
        </p>
<?php else: ?>
        <form method="get" action="schedules.php" class="doctor-picker">
            <label for="doctor"><?= e(t('schedules.doctor')) ?></label>
            <div class="inline-field">
                <select id="doctor" name="doctor">
                    <option value=""><?= e(t('schedules.choose_doctor')) ?></option>
<?php foreach ($doctors as $option): ?>
                    <option value="<?= (int) $option['id'] ?>"<?= $doctor && $doctor['id'] === $option['id'] ? ' selected' : '' ?>>
                        <?= e($option['name']) ?><?= $option['title'] ? ' · ' . e($option['title']) : '' ?>
                    </option>
<?php endforeach; ?>
                </select>
                <button type="submit"><?= e(t('schedules.show')) ?></button>
            </div>
        </form>
<?php endif; ?>

<?php if ($doctor): ?>
        <section class="form-card">
            <h2><?= e(t('schedules.edit_heading', ['name' => $doctor['name']])) ?></h2>
            <p class="form-hint"><?= e(t('schedules.form_hint')) ?></p>

            <?= form_errors_alert($errors) ?>

            <form method="post" action="<?= e($formUrl) ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">

<?php foreach ($week as $day => $dayValues): ?>
                <fieldset class="day" data-day>
                    <legend>
                        <label class="day-toggle">
                            <input type="checkbox" name="days[<?= $day ?>][enabled]" value="1"<?= $dayValues['enabled'] ? ' checked' : '' ?> data-day-toggle<?= field_error_attrs($errors, "day-$day") ?>>
                            <?= e(t("days.$day")) ?>
                        </label>
                    </legend>
                    <?= field_error($errors, "day-$day") ?>
                    <div class="slots"<?= $dayValues['enabled'] ? '' : ' hidden' ?>>
                        <div class="slot-list">
<?php foreach ($dayValues['slots'] ?: [['start' => '', 'end' => '']] as $i => $slot): ?>
                            <?= slot_row($day, $i, $slot, $errors) ?>

<?php endforeach; ?>
                        </div>
                        <button type="button" class="button-link" data-add-slot>+ <?= e(t('schedules.add_slot')) ?></button>
                    </div>
                    <template><?= slot_row($day, '__INDEX__', ['start' => '', 'end' => ''], []) ?></template>
                </fieldset>
<?php endforeach; ?>

                <div class="form-actions">
                    <button type="submit"><?= e(t('schedules.save')) ?></button>
                    <a href="schedules.php"><?= e(t('admin.cancel')) ?></a>
                </div>
            </form>

<?php if ($savedSchedule): ?>
            <form method="post" action="<?= e($formUrl) ?>" class="clear-schedule" data-confirm="<?= e(t('schedules.confirm_clear', ['name' => $doctor['name']])) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="button-link danger"><?= e(t('schedules.clear')) ?></button>
            </form>
<?php endif; ?>
        </section>

        <script>
            // Checking a day shows its time slots; "Add time slot" copies the day's <template> row.
            document.querySelectorAll('[data-day]').forEach(function (day) {
                var toggle = day.querySelector('[data-day-toggle]');
                var slots = day.querySelector('.slots');
                var list = day.querySelector('.slot-list');
                var template = day.querySelector('template');
                // Each new row needs an index not used by any other row of this day.
                var nextIndex = list.children.length;

                function addSlot() {
                    list.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(nextIndex++)));
                    list.lastElementChild.querySelector('input').focus();
                }

                toggle.addEventListener('change', function () {
                    slots.hidden = !toggle.checked;
                    if (toggle.checked && !list.children.length) {
                        addSlot();
                    }
                });

                day.querySelector('[data-add-slot]').addEventListener('click', addSlot);

                // Removing a day's last slot marks the day as unavailable.
                list.addEventListener('click', function (event) {
                    if (!event.target.matches('[data-remove-slot]')) {
                        return;
                    }
                    event.target.closest('.slot').remove();
                    if (!list.children.length) {
                        toggle.checked = false;
                        slots.hidden = true;
                        toggle.focus();
                    }
                });
            });
        </script>
<?php endif; ?>

<?php if ($doctors): ?>
        <h2><?= e(t('schedules.list_heading')) ?></h2>

        <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col"><?= e(t('schedules.doctor')) ?></th>
                    <th scope="col"><?= e(t('schedules.availability')) ?></th>
                    <th scope="col"><span class="visually-hidden"><?= e(t('admin.actions')) ?></span></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($doctors as $listed): ?>
<?php $schedule = doctor_schedule((int) $listed['id']); ?>
                <tr class="<?= $doctor && $doctor['id'] === $listed['id'] ? 'is-editing' : '' ?>">
                    <td><?= e($listed['name']) ?></td>
                    <td>
<?php if (!$schedule): ?>
                        <span class="muted"><?= e(t('schedules.none')) ?></span>
<?php else: ?>
                        <ul class="schedule-summary">
<?php foreach ($schedule as $day => $slots): ?>
                            <li><strong><?= e(t("days_short.$day")) ?></strong> <?= e(format_slots($slots)) ?></li>
<?php endforeach; ?>
                        </ul>
<?php endif; ?>
                    </td>
                    <td class="row-actions">
                        <a href="schedules.php?doctor=<?= (int) $listed['id'] ?>"><?= e(t('schedules.edit')) ?></a>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
        </div>
<?php endif; ?>
<?php
admin_footer();

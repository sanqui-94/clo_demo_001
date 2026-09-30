<?php
// Doctors' weekly schedules: loading and formatting. Used by the admin schedules page and the public doctor page.
//
// Days follow ISO-8601 (1 = Monday ... 7 = Sunday). Times are 'HH:MM' in 24h, as stored in doctor_schedules.

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

const WEEKDAYS = [1, 2, 3, 4, 5, 6, 7];

/**
 * A doctor's time slots grouped by day: [day => [['start' => '09:00', 'end' => '13:00'], ...]].
 * Days without slots are left out. Slots are sorted by start time.
 */
function doctor_schedule(int $doctorId): array
{
    $stmt = db()->prepare(
        'SELECT day_of_week, start_time, end_time FROM doctor_schedules WHERE doctor_id = ? ORDER BY day_of_week, start_time'
    );
    $stmt->execute([$doctorId]);

    $schedule = [];
    foreach ($stmt as $row) {
        $schedule[(int) $row['day_of_week']][] = ['start' => $row['start_time'], 'end' => $row['end_time']];
    }

    return $schedule;
}

/**
 * A stored 'HH:MM' time in the current language's clock, e.g. '15:00' in Spanish or '3:00 PM' in English.
 */
function format_time(string $time): string
{
    $parsed = DateTime::createFromFormat('!H:i', $time);

    return $parsed ? $parsed->format(t('time.format')) : $time;
}

/**
 * One day's slots as text, e.g. '9:00 – 13:00, 15:00 – 18:00'.
 * Each range stays on one line (non-breaking spaces, plus a word joiner because browsers may break after '–');
 * lines only wrap after a comma.
 */
function format_slots(array $slots): string
{
    $ranges = array_map(fn(array $slot) => format_time($slot['start']) . "\u{a0}–\u{2060}\u{a0}" . format_time($slot['end']), $slots);

    return implode(', ', $ranges);
}

/**
 * Merges consecutive days that have exactly the same slots, so Monday to Friday 9:00–17:00 shows as one line.
 * $schedule is doctor_schedule()'s result. Returns [['from' => 1, 'to' => 5, 'slots' => [...]], ...].
 */
function group_schedule_days(array $schedule): array
{
    $groups = [];

    foreach ($schedule as $day => $slots) {
        $last = array_key_last($groups);
        if ($last !== null && $groups[$last]['to'] === $day - 1 && $groups[$last]['slots'] === $slots) {
            $groups[$last]['to'] = $day;
        } else {
            $groups[] = ['from' => $day, 'to' => $day, 'slots' => $slots];
        }
    }

    return $groups;
}

/**
 * A group's days as text, e.g. 'Lunes' or 'Lunes – Viernes'.
 */
function format_day_range(int $from, int $to): string
{
    return $from === $to ? t("days.$from") : t("days.$from") . ' – ' . t("days.$to");
}

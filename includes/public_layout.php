<?php
// Shared page frame for the public site. Usage:
//   public_header('Page title', 'Meta description', 'home');
//   ... page content ...
//   public_footer();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * The clinic's details from the admin, or null if they haven't been filled in yet.
 */
function clinic_info(): ?array
{
    static $clinic = false;

    if ($clinic === false) {
        $clinic = db()->query('SELECT * FROM clinic ORDER BY id LIMIT 1')->fetch() ?: null;
    }

    return $clinic;
}

/**
 * The clinic's name, or APP_NAME until one is set in the admin.
 */
function clinic_name(): string
{
    return clinic_info()['name'] ?? APP_NAME;
}

/**
 * A tel: link target for a phone number as typed, e.g. '+34 910 123 456' -> 'tel:+34910123456'.
 */
function phone_href(string $phone): string
{
    return 'tel:' . preg_replace('/[^\d+]/', '', $phone);
}

/**
 * Initials of the first two words of a doctor's name, skipping titles like "Dr." or "Dra.".
 * First two rather than first and last because Spanish names usually end in two surnames:
 * "Dra. Ana García López" -> "AG".
 */
function doctor_initials(string $name): string
{
    $words = array_filter(preg_split('/\s+/u', trim($name)), fn(string $word) => !str_ends_with($word, '.'));
    $initials = array_map(fn(string $word) => mb_substr($word, 0, 1), array_slice(array_values($words), 0, 2));

    return mb_strtoupper(implode('', $initials));
}

/**
 * The doctor's photo, or a block with their initials if they have none.
 * The alt text is empty because the doctor's name is always shown next to it.
 */
function doctor_photo(array $doctor, string $class): string
{
    if (!empty($doctor['photo_path'])) {
        return '<img class="' . e($class) . '" src="' . e($doctor['photo_path']) . '" alt="">';
    }

    return '<span class="' . e($class) . ' photo-placeholder" aria-hidden="true">' . e(doctor_initials($doctor['name'])) . '</span>';
}

/**
 * $title is the page's own name ('' on the homepage, which uses the clinic name alone).
 * $current is the nav item to mark as the current page: 'home' or 'staff'.
 */
function public_header(string $title, string $description, string $current = ''): void
{
    $fullTitle = $title === '' ? clinic_name() : $title . ' · ' . clinic_name();
    $navItems = [
        'home'  => ['index.php', t('nav.home')],
        'staff' => ['staff.php', t('nav.staff')],
    ];
    ?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
<?php if ($description !== ''): ?>
    <meta name="description" content="<?= e($description) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
<?php endif; ?>
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(clinic_name()) ?>">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <a class="skip-link" href="#main"><?= e(t('site.skip_link')) ?></a>
    <header class="site-header">
        <div class="wrap site-header-inner">
            <a class="site-name" href="index.php"><?= e(clinic_name()) ?></a>
            <nav class="site-nav" aria-label="<?= e(t('site.nav_label')) ?>">
<?php foreach ($navItems as $key => [$href, $label]): ?>
                <a href="<?= e($href) ?>"<?= $key === $current ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
            </nav>
            <?= lang_switch() ?>
        </div>
    </header>
    <main id="main">
<?php
}

function public_footer(): void
{
    $clinic = clinic_info();
    ?>
    </main>
    <footer class="site-footer">
        <div class="wrap site-footer-inner">
            <p class="site-footer-name"><?= e(clinic_name()) ?></p>
<?php if (!empty($clinic['phone']) || !empty($clinic['email'])): ?>
            <p>
<?php if (!empty($clinic['phone'])): ?>
                <a href="<?= e(phone_href($clinic['phone'])) ?>"><?= e($clinic['phone']) ?></a>
<?php endif; ?>
<?php if (!empty($clinic['email'])): ?>
                <a href="mailto:<?= e($clinic['email']) ?>"><?= e($clinic['email']) ?></a>
<?php endif; ?>
            </p>
<?php endif; ?>
            <p class="site-footer-copy"><?= e(t('site.copyright', ['year' => date('Y'), 'name' => clinic_name()])) ?></p>
        </div>
    </footer>
</body>
</html>
<?php
}

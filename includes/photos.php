<?php
// Saving and removing uploaded doctor photos.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/i18n.php';

// Accepted image types and the extension each is saved with.
const PHOTO_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

/**
 * Checks an uploaded photo from $_FILES. Returns an error message, or null if it's fine.
 */
function photo_upload_error(array $file): ?string
{
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > MAX_PHOTO_BYTES) {
        return t('photo.too_large', ['max' => photo_max_size_label()]);
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return t('photo.upload_failed');
    }

    // Check the file's actual contents, not the name or the type the browser claims.
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(PHOTO_TYPES[$mime]) || getimagesize($file['tmp_name']) === false) {
        return t('photo.invalid_type');
    }

    return null;
}

/**
 * Moves a checked upload into the photo folder under a new random name.
 * Returns the path to store in the database (relative to public/), or null if the move failed.
 */
function store_photo(array $file): ?string
{
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    // Random name: never reuses the uploader's file name, so it can't overwrite or guess other files.
    $name = bin2hex(random_bytes(16)) . '.' . PHOTO_TYPES[$mime];

    if (!move_uploaded_file($file['tmp_name'], DOCTOR_PHOTO_DIR . '/' . $name)) {
        return null;
    }

    return DOCTOR_PHOTO_PATH_PREFIX . $name;
}

/**
 * Deletes a stored photo. Only touches files inside the photo folder.
 */
function delete_photo(?string $path): void
{
    if (!$path || !str_starts_with($path, DOCTOR_PHOTO_PATH_PREFIX)) {
        return;
    }

    $file = DOCTOR_PHOTO_DIR . '/' . basename($path);
    if (is_file($file)) {
        unlink($file);
    }
}

/**
 * True if the request body was bigger than PHP's post_max_size. PHP then drops all fields and files,
 * so the form would otherwise fail with a confusing "invalid form" error.
 */
function post_too_large(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)
        && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

function photo_max_size_label(): string
{
    return round(MAX_PHOTO_BYTES / 1024 / 1024, 1) . ' MB';
}

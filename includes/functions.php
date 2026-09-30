<?php
// Small helpers shared by admin and public pages.

require_once __DIR__ . '/i18n.php';

/**
 * Escapes a value for safe output in HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sends the browser to another page and stops the script.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Stores a one-time message to show on the next page load. Requires an active session.
 * $type is 'success' or 'error'.
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Returns and clears the pending flash messages.
 */
function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

/**
 * Plain text typed in the admin as HTML paragraphs: blank lines start a new paragraph, single line breaks are kept.
 */
function text_paragraphs(?string $text): string
{
    $paragraphs = preg_split('/\R\s*\R/', trim($text ?? ''), -1, PREG_SPLIT_NO_EMPTY);

    return implode("\n", array_map(fn(string $p) => '<p>' . nl2br(e(trim($p)), false) . '</p>', $paragraphs));
}

/**
 * The start of a text, cut at a word boundary and ending in '…' if shortened. Line breaks become spaces.
 */
function excerpt(?string $text, int $maxLength): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text ?? ''));
    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }

    $cut = mb_substr($text, 0, $maxLength);
    $lastSpace = mb_strrpos($cut, ' ');

    return rtrim($lastSpace ? mb_substr($cut, 0, $lastSpace) : $cut, " ,.;:") . '…';
}

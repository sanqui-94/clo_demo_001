<?php
// Small helpers shared by admin and public pages.

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

<?php
// Small helpers shared by admin and public pages.

/**
 * Escapes a value for safe output in HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

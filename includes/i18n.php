<?php
// Interface translations (buttons, labels, messages). Content entered in the admin is not translated.
//
// Texts live in includes/lang/{es,en}.php as 'key' => 'text'. Print them with t('key').
// Visitors switch language with ?lang=es or ?lang=en; the choice is remembered in a cookie.

require_once __DIR__ . '/config.php';

const LANG_COOKIE = 'lang';

/**
 * The language of the current request: the cookie if valid, otherwise DEFAULT_LANG.
 */
function current_lang(): string
{
    static $lang = null;

    if ($lang === null) {
        $cookie = $_COOKIE[LANG_COOKIE] ?? '';
        $lang = in_array($cookie, SUPPORTED_LANGS, true) ? $cookie : DEFAULT_LANG;
    }

    return $lang;
}

/**
 * Translates a key into the current language. Placeholders like {name} are replaced from $params.
 * Falls back to the default language, then to the key itself, so a missing text is visible but harmless.
 */
function t(string $key, array $params = []): string
{
    static $dictionaries = [];

    foreach ([current_lang(), DEFAULT_LANG] as $lang) {
        $dictionaries[$lang] ??= require __DIR__ . "/lang/$lang.php";

        if (isset($dictionaries[$lang][$key])) {
            $text = $dictionaries[$lang][$key];
            foreach ($params as $name => $value) {
                $text = str_replace('{' . $name . '}', (string) $value, $text);
            }

            return $text;
        }
    }

    return $key;
}

/**
 * URL of the current page with ?lang= set, for the ES | EN switch.
 */
function lang_switch_url(string $lang): string
{
    $query = $_GET;
    $query['lang'] = $lang;

    return strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($query);
}

/**
 * The ES | EN links. The current language is shown as plain text, not a link.
 */
function lang_switch(): string
{
    $items = [];

    foreach (SUPPORTED_LANGS as $lang) {
        $label = e(t("lang.$lang"));
        $items[] = $lang === current_lang()
            ? '<strong aria-current="true">' . $label . '</strong>'
            : '<a href="' . e(lang_switch_url($lang)) . '" hreflang="' . $lang . '" lang="' . $lang . '">' . $label . '</a>';
    }

    return '<nav class="lang-switch" aria-label="' . e(t('lang.switch_label')) . '">' . implode(' | ', $items) . '</nav>';
}

/**
 * If the URL has a valid ?lang=, remembers it in a cookie and reloads the page without it,
 * so the address bar stays clean and refreshing doesn't resubmit anything.
 */
function handle_lang_switch(): void
{
    $lang = $_GET['lang'] ?? null;

    if ($lang === null || PHP_SAPI === 'cli' || $_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }

    if (in_array($lang, SUPPORTED_LANGS, true)) {
        setcookie(LANG_COOKIE, $lang, [
            'expires'  => time() + 365 * 24 * 60 * 60,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    $query = $_GET;
    unset($query['lang']);
    $path = strtok($_SERVER['REQUEST_URI'], '?');

    header('Location: ' . $path . ($query ? '?' . http_build_query($query) : ''));
    exit;
}

handle_lang_switch();

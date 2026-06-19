<?php
defined('MOODLE_INTERNAL') || die();

function theme_codex_get_main_scss_content($theme) {
    global $CFG;
    require_once($CFG->dirroot . '/theme/moove/lib.php');

    $moovetheme = theme_config::load('moove');
    $scss = '';
    if (function_exists('theme_moove_get_main_scss_content')) {
        $scss .= theme_moove_get_main_scss_content($moovetheme);
    } else {
        $scss .= file_get_contents($CFG->dirroot . '/theme/moove/scss/preset/default.scss');
    }

    $scss .= "\n" . file_get_contents(__DIR__ . '/scss/post.scss');
    return $scss;
}

/**
 * Extra SCSS callback for codex.
 *
 * Codex does not expose the user-configurable SCSS / header image / login bg
 * image settings that Moove's theme_moove_get_extra_scss() expects on the
 * theme config object. Returning an empty string here prevents Moodle from
 * falling back to Moove's extra_scss callback with our incompatible theme
 * config, which would otherwise emit "Undefined property: stdClass::$scss"
 * notices on every page render.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_codex_get_extra_scss($theme) {
    return '';
}

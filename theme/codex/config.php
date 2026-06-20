<?php
defined('MOODLE_INTERNAL') || die();
$THEME->name = 'codex';
$THEME->parents = ['moove', 'boost'];
$THEME->sheets = [];
$THEME->enable_dock = false;
$THEME->yuicssmodules = array();
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->scss = function($theme) {
    return theme_codex_get_main_scss_content($theme);
};
$THEME->extrascsscallback = function($theme) {
    return theme_codex_get_extra_scss($theme);
};
$THEME->extrascsscallback = function($theme) {
    return theme_codex_get_extra_scss($theme);
};
$THEME->layouts = [
    'incourse' => [
        'file' => 'incourse.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['langmenu' => false],
    ],
    'standard' => [
        'file' => 'incourse.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['langmenu' => false],
    ],
    'mydashboard' => [
        'file' => 'dashboard.php',
        'regions' => [],
        'options' => ['nonavbar' => true, 'langmenu' => false],
    ],
    'login' => [
        'file' => 'login.php',
        'regions' => [],
    ],
    'frontpage' => [
        'file' => 'login.php',
        'regions' => [],
        'options' => ['nonavbar' => true, 'langmenu' => false],
    ],
    'codex_app' => [
        'file' => 'codex_app.php',
        'regions' => [],
        'options' => ['nonavbar' => true, 'langmenu' => false, 'nocustommenu' => true],
    ],
];

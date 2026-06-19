<?php
defined('MOODLE_INTERNAL') || die();
global $CFG, $USER;
require_once($CFG->dirroot . '/local/codex/lib.php');

// Lucide-style stroke icons, currentColor for proper hover/active state.
$icon = function($name) {
    $svgs = [
        'home'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5L12 4l9 7.5"/><path d="M5 10v9a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1v-9"/></svg>',
        'subjects'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4.5A2.5 2.5 0 016.5 2H20v15H6.5a2.5 2.5 0 000 5H20v-5"/><path d="M8 7h8M8 11h6"/></svg>',
        'assessments' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 3h6a1 1 0 011 1v2H8V4a1 1 0 011-1z"/><path d="M9 12l2 2 4-4"/><path d="M9 17h5"/></svg>',
        'interactive' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M10 8.5l5 3.5-5 3.5v-7z" fill="currentColor" stroke="none"/></svg>',
        'settings'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
        'logout'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
    ];
    return isset($svgs[$name]) ? $svgs[$name] : '';
};

$logourl = $CFG->wwwroot . '/theme/codex/pix/logo.png';

$items = [
    ['key' => 'home',        'label' => 'Home',                   'icon' => $icon('home'),        'url' => $CFG->wwwroot . '/my/'],
    ['key' => 'subjects',    'label' => 'Subjects',               'icon' => $icon('subjects'),    'url' => $CFG->wwwroot . '/local/codex/subjects.php'],
    ['key' => 'assessments', 'label' => 'Assessments',            'icon' => $icon('assessments'), 'url' => $CFG->wwwroot . '/local/codex/assessments.php'],
    ['key' => 'interactive', 'label' => 'Interactive Activities', 'icon' => $icon('interactive'), 'url' => $CFG->wwwroot . '/local/codex/interactive.php'],
    ['key' => 'settings',    'label' => 'Settings',               'icon' => $icon('settings'),    'url' => $CFG->wwwroot . '/user/edit.php'],
    ['key' => 'logout',      'label' => 'Logout',                 'icon' => $icon('logout'),      'url' => $CFG->wwwroot . '/login/logout.php?sesskey=' . sesskey()],
];

// Auto-detect active state from URL if not explicitly set by the page.
if (empty($active)) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/my/') !== false || $uri === '/' || strpos($uri, '/my?') !== false) {
        $active = 'home';
    } elseif (strpos($uri, '/local/codex/assessments.php') !== false || strpos($uri, '/mod/quiz/') !== false) {
        $active = 'assessments';
    } elseif (strpos($uri, '/local/codex/interactive.php') !== false
          || strpos($uri, '/mod/h5pactivity/') !== false
          || strpos($uri, '/mod/hvp/') !== false
          || strpos($uri, '/mod/scorm/') !== false
          || strpos($uri, '/mod/lesson/') !== false
          || strpos($uri, '/mod/lti/') !== false) {
        $active = 'interactive';
    } elseif (strpos($uri, '/local/codex/') !== false
          || strpos($uri, '/mod/') !== false
          || strpos($uri, '/course/view.php') !== false) {
        $active = 'subjects';
    } else {
        $active = '';
    }
}
?>
<aside class="codex-sidebar">
    <div class="codex-sidebar-logo"><img src="<?php echo $logourl; ?>" alt="Codex"></div>
    <nav class="codex-sidebar-nav">
        <?php foreach ($items as $it): ?>
            <a href="<?php echo $it['url']; ?>" class="codex-nav-item <?php echo $active === $it['key'] ? 'is-active active' : ''; ?>">
                <span class="codex-nav-icon"><?php echo $it['icon']; ?></span>
                <span class="codex-nav-label"><?php echo $it['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
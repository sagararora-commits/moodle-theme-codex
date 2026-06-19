<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

global $DB, $CFG, $OUTPUT, $PAGE, $USER;

$courseid   = required_param('courseid', PARAM_INT);
$sectionnum = required_param('sectionnum', PARAM_INT);
$course = get_course($courseid);

$ctx = context_course::instance($courseid);
if (!is_enrolled($ctx, $USER) && !has_capability('moodle/course:view', $ctx)) {
    print_error('You are not enrolled in this course.');
}

$PAGE->set_url('/local/codex/chapter_view.php', ['courseid' => $courseid, 'sectionnum' => $sectionnum]);
$PAGE->set_context($ctx);
$PAGE->set_pagelayout('codex_app');
$PAGE->set_title(format_string($course->fullname) . ' — Chapter ' . $sectionnum);

$modinfo = get_fast_modinfo($course);
$sectioninfo = $modinfo->get_section_info($sectionnum);
if (!$sectioninfo) {
    print_error('Invalid chapter number.');
}

$coursename  = format_string($course->fullname);
$palette     = local_codex_palette_for($coursename . ' ' . $course->shortname);
$chaptername = $sectioninfo->name ?: "Chapter $sectionnum";

// Categorize each mod into one of: videos / resources / interactive / assessments / other
function local_codex_categorize($modname) {
    if ($modname === 'page') return 'videos';
    if (in_array($modname, ['resource', 'folder', 'url', 'book'])) return 'resources';
    if (in_array($modname, ['quiz', 'assign', 'feedback', 'choice'])) return 'assessments';
    if (in_array($modname, ['h5pactivity', 'hvp', 'scorm', 'lti', 'lesson'])) return 'interactive';
    return 'other';
}

function local_codex_mod_label($modname) {
    $labels = [
        'page' => 'Video', 'resource' => 'PDF', 'url' => 'Link', 'book' => 'Book', 'folder' => 'Folder',
        'quiz' => 'Quiz', 'assign' => 'Assignment', 'feedback' => 'Feedback', 'choice' => 'Poll',
        'h5pactivity' => 'Interactive', 'hvp' => 'Interactive', 'scorm' => 'SCORM',
        'lesson' => 'Lesson', 'lti' => 'External Tool', 'forum' => 'Forum', 'glossary' => 'Glossary',
        'wiki' => 'Wiki', 'workshop' => 'Workshop', 'chat' => 'Chat', 'survey' => 'Survey',
        'data' => 'Database',
    ];
    return $labels[$modname] ?? ucfirst($modname);
}

function local_codex_mod_icon($modname) {
    switch ($modname) {
        case 'resource':
            // PDF / file icon
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
        case 'folder':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
        case 'url':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
        case 'page':
            // Video player icon
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><polygon points="10 9 16 12 10 15 10 9" fill="currentColor"/></svg>';
        case 'book':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>';
        case 'quiz':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>';
        case 'assign':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>';
        case 'h5pactivity': case 'hvp': case 'scorm': case 'lti':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>';
        case 'lesson':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>';
        case 'forum':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>';
    }
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
}

// 5 buckets, displayed in this order
$groups = [
    'videos'      => ['label' => 'Videos',             'items' => []],
    'resources'   => ['label' => 'Teacher Resources',  'items' => []],
    'interactive' => ['label' => 'Interactive Content','items' => []],
    'assessments' => ['label' => 'Assessments',        'items' => []],
    'other'       => ['label' => 'Other',              'items' => []],
];

if (!empty($sectioninfo->sequence)) {
    $cmids = array_filter(explode(',', $sectioninfo->sequence));
    foreach ($cmids as $cmid) {
        try {
            $cm = $modinfo->get_cm($cmid);
            if (!$cm || !$cm->uservisible) continue;
            if ($cm->modname === 'label') continue;

            $category = local_codex_categorize($cm->modname);
            $groups[$category]['items'][] = (object)[
                'name'    => format_string($cm->name),
                'modname' => $cm->modname,
                'label'   => local_codex_mod_label($cm->modname),
                'icon'    => local_codex_mod_icon($cm->modname),
                'url'     => (string)(new moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id])),
            ];
        } catch (Exception $e) {
            continue;
        }
    }
}

$firstname = !empty($USER->firstname) ? format_string($USER->firstname) : 'there';
$lastname  = !empty($USER->lastname)  ? format_string($USER->lastname)  : '';
$initials  = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
// Category (class) lookup for breadcrumb.
$classname = '';
$classid   = 0;
if (!empty($course->category)) {
    $cat = $DB->get_record('course_categories', ['id' => $course->category], 'id, name');
    if ($cat) {
        $classname = format_string($cat->name);
        $classid   = $cat->id;
    }
}
$is_admin   = is_siteadmin();
$is_teacher = has_capability('moodle/course:update', $ctx);
$role_label = $is_admin ? 'Admin' : ($is_teacher ? 'Teacher' : 'Student');
$logourl    = $OUTPUT->image_url('logo', 'theme_codex');
$chapter_display_num = str_pad($sectionnum, 2, '0', STR_PAD_LEFT);

echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $CFG->wwwroot; ?>/local/codex/styles/course.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="<?php echo $CFG->wwwroot; ?>/local/codex/styles/chapter.css?v=<?php echo time(); ?>" />
    <?php echo $OUTPUT->standard_head_html(); ?>
</head>
<body <?php echo $OUTPUT->body_attributes('codex-course codex-chapter'); ?>>
<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div class="codex-app">
    <?php $active = 'subjects'; include $CFG->dirroot . '/local/codex/sidebar.php'; ?>


    <div class="codex-main-wrap">
        <header class="codex-topbar">
            <div class="codex-searchbar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" placeholder="Search anything..." />
            </div>
            <a class="codex-icon-btn" href="<?php echo $CFG->wwwroot; ?>/message/index.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/></svg></a>
            <a class="codex-user-chip" href="<?php echo $CFG->wwwroot; ?>/user/profile.php">
                <div class="codex-avatar"><?php echo $initials; ?></div>
                <div class="codex-user-meta">
                    <span class="codex-user-name"><?php echo $firstname; ?></span>
                    <span class="codex-user-role"><?php echo $role_label; ?></span>
                </div>
                <svg class="codex-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </a>
        </header>

        <main class="codex-main">

            <?php
            $crumbs = [['label' => 'Subjects', 'url' => $CFG->wwwroot . '/local/codex/subjects.php']];
            if (!empty($classname) && !empty($classid)) {
                $crumbs[] = ['label' => $classname, 'url' => $CFG->wwwroot . '/local/codex/class_view.php?catid=' . $classid];
            }
            $crumbs[] = ['label' => $coursename, 'url' => $CFG->wwwroot . '/local/codex/course_dashboard.php?id=' . $courseid];
            $crumbs[] = ['label' => 'Chapter ' . $sectionnum];
            echo local_codex_breadcrumb($crumbs);
            ?>

            <section class="codex-chapter-hero palette-<?php echo $palette; ?>">
                <div class="codex-chapter-num-large"><?php echo $chapter_display_num; ?></div>
                <div class="codex-chapter-hero-text">
                    <div class="codex-chapter-eyebrow"><?php echo $coursename; ?></div>
                    <h1><?php echo format_string($chaptername); ?></h1>
                    <div class="codex-chapter-meta">
                        <span><strong><?php echo count($groups['videos']['items']); ?></strong> Videos</span>
                        <span class="dot-sep"></span>
                        <span><strong><?php echo count($groups['resources']['items']); ?></strong> Resources</span>
                        <span class="dot-sep"></span>
                        <span><strong><?php echo count($groups['assessments']['items']); ?></strong> Assessments</span>
                    </div>
                </div>
            </section>

            <?php
            $any_items = false;
            foreach ($groups as $key => $group):
                if (empty($group['items'])) continue;
                $any_items = true;
            ?>
            <section class="codex-resource-group">
                <div class="codex-section-header">
                    <h2><?php echo $group['label']; ?> <span class="codex-count">(<?php echo count($group['items']); ?>)</span></h2>
                </div>
                <div class="codex-resource-list">
                    <?php foreach ($group['items'] as $item): ?>
                    <a class="codex-resource-card palette-<?php echo $palette; ?>" href="<?php echo $item->url; ?>">
                        <div class="codex-resource-icon"><?php echo $item->icon; ?></div>
                        <div class="codex-resource-body">
                            <div class="codex-resource-name"><?php echo $item->name; ?></div>
                            <div class="codex-resource-tag"><?php echo $item->label; ?></div>
                        </div>
                        <svg class="codex-resource-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endforeach; ?>

            <?php if (!$any_items): ?>
            <?php echo local_codex_empty_state("content", "Nothing here yet", "Your teacher hasn't added content to this chapter. Check back soon — videos, readings, and activities will appear here.", "", ""); ?>
            <?php endif; ?>

        </main>
    </div>
</div>

<div style="display:none;"><?php echo $OUTPUT->main_content(); ?></div>
</body>
</html>

<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

global $DB, $CFG, $OUTPUT, $PAGE, $USER;

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);

$ctx = context_course::instance($courseid);
if (!has_capability('moodle/course:view', $ctx)) {
    print_error('cannotviewcourse');
}

$PAGE->set_url('/local/codex/course_dashboard.php', ['id' => $courseid]);
$PAGE->set_context($ctx);
$PAGE->set_pagelayout('codex_app');
$PAGE->set_title(format_string($course->fullname));

$coursename = format_string($course->fullname);
$palette    = local_codex_palette_for($coursename . ' ' . $course->shortname);

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

$modinfo = get_fast_modinfo($course);
$sections = $modinfo->get_section_info_all();
$chapters = [];
$total_videos = 0;
$total_resources = 0;
$total_assessments = 0;

foreach ($sections as $sectionnum => $section) {
    if ($sectionnum == 0) continue;
    if (!$section->uservisible) continue;

    $videos = 0;
    $resources = 0;
    $assessments = 0;

    if (!empty($section->sequence)) {
        $cmids = array_filter(explode(',', $section->sequence));
        foreach ($cmids as $cmid) {
            try {
                $cm = $modinfo->get_cm($cmid);
                if (!$cm || !$cm->uservisible) continue;
                if ($cm->modname === 'label') continue;
                switch ($cm->modname) {
                    case 'page':
                        $videos++;
                        break;
                    case 'quiz':
                    case 'assign':
                    case 'feedback':
                    case 'choice':
                        $assessments++;
                        break;
                    default:
                        $resources++;
                }
            } catch (Exception $e) {
                continue;
            }
        }
    }

    $total_videos      += $videos;
    $total_resources   += $resources;
    $total_assessments += $assessments;

    $chapters[] = (object)[
        'num'         => $sectionnum,
        'name'        => $section->name ?: "Chapter $sectionnum",
        'videos'      => $videos,
        'resources'   => $resources,
        'assessments' => $assessments,
    ];
}

$firstname = !empty($USER->firstname) ? format_string($USER->firstname) : 'there';
$lastname  = !empty($USER->lastname)  ? format_string($USER->lastname)  : '';
$initials  = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));

$is_admin = is_siteadmin();
$is_teacher = has_capability('moodle/course:update', $ctx);
$role_label = $is_admin ? 'Admin' : ($is_teacher ? 'Teacher' : 'Student');

$logourl = $OUTPUT->image_url('logo', 'theme_codex');

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
    <?php echo $OUTPUT->standard_head_html(); ?>
</head>
<body <?php echo $OUTPUT->body_attributes('codex-course'); ?>>
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
            $crumbs[] = ['label' => $coursename];
            echo local_codex_breadcrumb($crumbs);
            ?>


            <section class="codex-course-hero palette-<?php echo $palette; ?>">
                <div class="codex-course-hero-text">
                    <h1><?php echo $coursename; ?></h1>
                    <p>Build strong foundations with engaging content and activities</p>
                    <div class="codex-course-stats">
                        <div class="codex-stat">
                            <div class="codex-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
                            <div>
                                <div class="codex-stat-num"><?php echo count($chapters); ?></div>
                                <div class="codex-stat-label">Chapters</div>
                            </div>
                        </div>
                        <div class="codex-stat">
                            <div class="codex-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><polygon points="10 9 16 12 10 15 10 9" fill="currentColor"/></svg></div>
                            <div>
                                <div class="codex-stat-num"><?php echo $total_videos; ?></div>
                                <div class="codex-stat-label">Videos</div>
                            </div>
                        </div>
                        <div class="codex-stat">
                            <div class="codex-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                            <div>
                                <div class="codex-stat-num"><?php echo $total_resources; ?></div>
                                <div class="codex-stat-label">Resources</div>
                            </div>
                        </div>
                        <div class="codex-stat">
                            <div class="codex-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
                            <div>
                                <div class="codex-stat-num"><?php echo $total_assessments; ?></div>
                                <div class="codex-stat-label">Assessments</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="codex-course-hero-illustration"><?php echo local_codex_subject_svg($palette); ?></div>
            </section>

            <div class="codex-section-header">
                <h2>Chapters</h2>
                <?php if ($is_teacher || $is_admin): ?>
                <a class="codex-view-all" href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=<?php echo $courseid; ?>">Open in Moodle Editor →</a>
                <?php endif; ?>
            </div>

            <?php if (empty($chapters)): ?>
                <?php echo local_codex_empty_state("chapters", "No chapters yet", "This course doesn't have any chapters published. Check back soon — your teacher is on it.", $CFG->wwwroot . "/my/", "Back to dashboard"); ?>
            <?php else: ?>
            <div class="codex-chapters-grid">
                <?php foreach ($chapters as $i => $chap): ?>
                <a class="codex-chapter-card palette-<?php echo $palette; ?>"
                   href="<?php echo $CFG->wwwroot; ?>/local/codex/chapter_view.php?courseid=<?php echo $courseid; ?>&sectionnum=<?php echo $chap->num; ?>">
                    <div class="codex-chapter-num-badge"><?php echo str_pad($i + 1, 2, '0', STR_PAD_LEFT); ?></div>
                    <h4 class="codex-chapter-name"><?php echo format_string($chap->name); ?></h4>
                    <div class="codex-chapter-stats">
                        <div class="codex-chapter-stat"><span class="dot vid"></span><?php echo $chap->videos; ?> Videos</div>
                        <div class="codex-chapter-stat"><span class="dot res"></span><?php echo $chap->resources; ?> Resources</div>
                        <div class="codex-chapter-stat"><span class="dot ass"></span><?php echo $chap->assessments; ?> Assessments</div>
                    </div>
                    <div class="codex-chapter-cta">Open Chapter →</div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<div style="display:none;"><?php echo $OUTPUT->main_content(); ?></div>
<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>

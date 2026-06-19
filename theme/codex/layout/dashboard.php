<?php
defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot . '/local/codex/lib.php');
global $USER, $OUTPUT, $CFG, $PAGE, $DB, $SITE;


// Site admins → fall through to moove for full editing UI
if (is_siteadmin()) {
    require($CFG->dirroot . '/theme/moove/layout/columns2.php');
    return;
}
$bodyclasses = $PAGE->bodyclasses . ' codex-dashboard';

// ============ Greeting ============
$hour = (int) date('H');
if ($hour < 12)      { $greeting = 'Good Morning'; }
elseif ($hour < 17)  { $greeting = 'Good Afternoon'; }
else                 { $greeting = 'Good Evening'; }

$firstname = !empty($USER->firstname) ? format_string($USER->firstname) : 'there';
$lastname  = !empty($USER->lastname)  ? format_string($USER->lastname)  : '';
$initials  = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
$logourl   = $OUTPUT->image_url('logo', 'theme_codex');

// ============ Role detection ============
// Look across the user's enrolled courses; if they hold editingteacher anywhere -> teacher.
$is_teacher = false;
$is_admin   = is_siteadmin();
$mycourses  = enrol_get_my_courses('*', 'visible DESC, sortorder ASC');

if (!$is_admin) {
    foreach ($mycourses as $c) {
        $ctx = context_course::instance($c->id);
        if (has_capability('moodle/course:update', $ctx)) {
            $is_teacher = true;
            break;
        }
    }
}
$role_label = $is_admin ? 'Admin' : ($is_teacher ? 'Teacher' : 'Student');
$continue_label = $is_teacher || $is_admin ? 'Continue Teaching' : 'Continue Learning';
$cta_label      = $is_teacher || $is_admin ? 'Continue Teaching' : 'Continue Learning';

// ============ Palette / illustration mapping (Option A) ============
function codex_icon_for($palette) {
    switch ($palette) {
        case 'math':    return 'π';
        case 'science': return '🌱';
        case 'english': return 'Aa';
        case 'evs':     return '🌍';
        case 'social':  return '🌐';
    }
    return '•';
}

function codex_subject_svg($palette) {
    switch ($palette) {
        case 'math':
            return '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect x="10" y="70" width="80" height="8" rx="2" fill="#f59e0b" transform="rotate(-8 50 74)"/><polygon points="55,30 75,60 35,60" fill="#fb923c" opacity="0.85"/><text x="48" y="55" font-family="Fraunces,serif" font-size="28" font-weight="700" fill="#c2410c">π</text></svg>';
        case 'science':
            return '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M40 30 L40 45 L25 75 Q25 82 35 82 L65 82 Q75 82 75 75 L60 45 L60 30 Z" fill="#65a30d" opacity="0.85"/><rect x="38" y="25" width="24" height="6" rx="1" fill="#4d7c0f"/><circle cx="40" cy="65" r="3" fill="white" opacity="0.7"/><circle cx="55" cy="70" r="2" fill="white" opacity="0.7"/><ellipse cx="50" cy="55" rx="13" ry="2" fill="#4d7c0f"/></svg>';
        case 'english':
            return '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect x="20" y="70" width="65" height="10" rx="2" fill="#ec4899"/><rect x="25" y="58" width="55" height="12" rx="2" fill="#f472b6"/><rect x="22" y="46" width="60" height="12" rx="2" fill="#db2777"/><text x="45" y="40" font-family="Fraunces,serif" font-size="26" font-weight="700" fill="#be185d">Aa</text></svg>';
        case 'evs':
            return '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="55" r="28" fill="#16a34a"/><path d="M28 50 Q40 45 50 50 Q60 55 72 50" fill="#15803d" opacity="0.7"/><path d="M30 65 Q42 70 55 65 Q65 60 72 65" fill="#15803d" opacity="0.7"/><path d="M50 30 Q35 22 32 12 Q42 14 50 30" fill="#22c55e"/><path d="M50 30 Q65 22 68 12 Q58 14 50 30" fill="#16a34a"/></svg>';
        case 'social':
            return '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="55" cy="50" r="28" fill="#3b82f6"/><path d="M40 42 Q45 38 52 40 Q58 42 55 50 Q50 55 42 52 Q38 48 40 42" fill="#16a34a"/><path d="M62 45 Q70 43 75 48 Q72 55 65 55 Q60 52 62 45" fill="#16a34a"/><ellipse cx="55" cy="50" rx="28" ry="10" fill="none" stroke="#1d4ed8" stroke-width="0.8" opacity="0.5"/><rect x="52" y="78" width="6" height="10" fill="#92400e"/><ellipse cx="55" cy="89" rx="14" ry="3" fill="#92400e"/></svg>';
    }
    return '';
}

// ============ My Subjects (real Moodle data) ============
$subjects = [];
foreach ($mycourses as $course) {
    // Skip site course
    if ($course->id == SITEID) continue;
    if (!$course->visible) continue;
    
    // Count chapters = sections (excluding section 0/General)
    $section_count = $DB->count_records('course_sections', ['course' => $course->id]);
    $chapters = max(0, $section_count - 1);
    
    $name = format_string($course->fullname);
    $palette = local_codex_palette_for($name . ' ' . $course->shortname);
    
    $subjects[] = [
        'id'       => $course->id,
        'name'     => $name,
        'chapters' => $chapters,
        'palette'  => $palette,
    ];
}

// ============ Continue Teaching/Learning (from log store) ============
$continue_items = [];
try {
    // Get last 3 distinct course_module_viewed events for this user
    $sql = "SELECT l.id, l.courseid, l.contextinstanceid as cmid, l.timecreated, c.fullname as coursename
            FROM {logstore_standard_log} l
            JOIN {course} c ON c.id = l.courseid
            WHERE l.userid = :userid
              AND l.eventname LIKE '%course_module_viewed%'
              AND l.courseid > 0
            ORDER BY l.timecreated DESC
            LIMIT 30";
    $logs = $DB->get_records_sql($sql, ['userid' => $USER->id]);
    
    $seen_courses = [];
    foreach ($logs as $log) {
        if (count($continue_items) >= 3) break;
        if (isset($seen_courses[$log->courseid])) continue; // one per course
        $seen_courses[$log->courseid] = true;
        
        // Find the section this cm belongs to
        $cm = $DB->get_record('course_modules', ['id' => $log->cmid]);
        $section_name = 'Recent activity';
        $section_num  = 0;
        if ($cm) {
            $section = $DB->get_record('course_sections', ['id' => $cm->section]);
            if ($section) {
                $section_name = !empty($section->name) ? format_string($section->name) : ('Section ' . $section->section);
                $section_num  = $section->section;
            }
        }
        
        $coursename = format_string($log->coursename);
        $palette = local_codex_palette_for($coursename);
        
        // Estimate progress: % of cms in this section the user has viewed (rough heuristic for now)
        $pct = 0;
        if ($cm && $section) {
            $cm_ids_in_section = array_filter(explode(',', $section->sequence ?: ''));
            $total_cms = count($cm_ids_in_section);
            if ($total_cms > 0) {
                list($insql, $inparams) = $DB->get_in_or_equal($cm_ids_in_section, SQL_PARAMS_NAMED);
                $inparams['userid'] = $USER->id;
                $viewed = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT contextinstanceid) FROM {logstore_standard_log}
                     WHERE userid = :userid AND eventname LIKE '%course_module_viewed%'
                       AND contextinstanceid $insql",
                    $inparams
                );
                $pct = min(100, (int) round(($viewed / $total_cms) * 100));
            }
        }
        
        $continue_items[] = [
            'subject' => $coursename,
            'chapter' => $section_name,
            'num'     => $section_num > 0 ? 'Chapter ' . $section_num : '',
            'pct'     => $pct,
            'icon'    => codex_icon_for($palette),
            'palette' => $palette,
            'courseurl' => $CFG->wwwroot . '/local/codex/course_dashboard.php?id=' . $log->courseid,
        ];
    }
} catch (Exception $e) {
    // Log query may fail on some installs; degrade gracefully.
    $continue_items = [];
}

// ============ Classes Today (calendar events for the user today) ============
$today_start = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
$today_end   = $today_start + 86400;
$classes_today = 0;
try {
    $course_ids = array_map(function($c){ return $c->id; }, $mycourses);
    if (!empty($course_ids)) {
        list($insql, $inparams) = $DB->get_in_or_equal($course_ids, SQL_PARAMS_NAMED);
        $inparams['start'] = $today_start;
        $inparams['end']   = $today_end;
        $inparams['userid'] = $USER->id;
        $classes_today = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {event}
             WHERE timestart >= :start AND timestart < :end
               AND (userid = :userid OR courseid $insql)",
            $inparams
        );
    }
} catch (Exception $e) {
    $classes_today = 0;
}

// ============ Notifications count ============
$notif_count = 0;
if (function_exists('message_count_unread_messages')) {
    $notif_count = (int) message_count_unread_messages($USER);
}

echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
    <?php echo $OUTPUT->standard_head_html(); ?>
</head>
<body <?php echo $OUTPUT->body_attributes($bodyclasses); ?>>
<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div class="codex-app">

    <?php $active = 'home'; include $CFG->dirroot . '/local/codex/sidebar.php'; ?>

    <div class="codex-main-wrap">
        <header class="codex-topbar">
            <a class="codex-icon-btn" href="<?php echo $CFG->wwwroot; ?>/message/index.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/></svg>
                <?php if ($notif_count > 0): ?><span class="codex-badge"><?php echo $notif_count; ?></span><?php endif; ?>
            </a>
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
            <section class="codex-hero">
                <div class="codex-hero-content">
                    <h1><?php echo $greeting; ?>, <?php echo $firstname; ?>! 👋</h1>
                    <p>Ready for today's classes?</p>
                    <a class="codex-btn-white" href="<?php echo $CFG->wwwroot; ?>/my/"><?php echo $cta_label; ?> →</a>
                </div>
                <div class="codex-classes-card">
                    <div class="codex-classes-label">You have</div>
                    <div class="codex-classes-num"><?php echo $classes_today; ?></div>
                    <div class="codex-classes-label">Classes Today</div>
                </div>
            </section>

            <?php if (!empty($continue_items)): ?>
            <div class="codex-section-header">
                <h2><?php echo $continue_label; ?></h2>
                <a class="codex-view-all" href="<?php echo $CFG->wwwroot; ?>/my/">View All →</a>
            </div>

            <div class="codex-continue-grid">
                <?php foreach ($continue_items as $item): ?>
                <a class="codex-continue-card" href="<?php echo $item['courseurl']; ?>" style="text-decoration:none; color:inherit; display:block;">
                    <div class="codex-continue-top">
                        <div class="codex-continue-icon palette-<?php echo $item['palette']; ?>"><?php echo $item['icon']; ?></div>
                        <div class="codex-continue-meta">
                            <div class="codex-continue-subject"><?php echo $item['subject']; ?></div>
                            <div class="codex-continue-chapter"><?php echo $item['chapter']; ?></div>
                            <?php if (!empty($item['num'])): ?>
                            <div class="codex-continue-num"><?php echo $item['num']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="codex-progress-row">
                        <div class="codex-progress-bar"><div class="codex-progress-fill palette-<?php echo $item['palette']; ?>" style="width:<?php echo $item['pct']; ?>%"></div></div>
                        <div class="codex-progress-pct"><?php echo $item['pct']; ?>% Completed</div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="codex-section-header">
                <h2>My Subjects</h2>
            </div>

            <?php if (empty($subjects)):
                $sys_context = context_system::instance();
                $is_teacher = has_capability('moodle/course:update', $sys_context);
                if ($is_teacher) {
                    echo local_codex_empty_state(
                        'courses',
                        'No classes assigned yet',
                        "You haven't been enrolled in any courses to teach. Talk to your school admin to get set up — once you're enrolled, your classes will appear here.",
                        $CFG->wwwroot . '/local/codex/subjects.php',
                        'Browse all subjects'
                    );
                } else {
                    echo local_codex_empty_state(
                        'courses',
                        'No subjects yet',
                        "You're not enrolled in any subjects yet. Your school is setting things up — check back soon, or browse what's available.",
                        $CFG->wwwroot . '/local/codex/subjects.php',
                        'Browse subjects'
                    );
                }
            ?>
            <?php else: ?>
            <div class="codex-subjects-grid">
                <?php foreach ($subjects as $sub): ?>
                <a class="codex-subject-card palette-<?php echo $sub['palette']; ?>" href="<?php echo $CFG->wwwroot; ?>/local/codex/course_dashboard.php?id=<?php echo $sub['id']; ?>" style="text-decoration:none;">
                    <h3><?php echo $sub['name']; ?></h3>
                    <div class="codex-chapter-count"><?php echo $sub['chapters']; ?> Chapters</div>
                    <div class="codex-subject-illustration"><?php echo codex_subject_svg($sub['palette']); ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<div style="display:none;"><?php echo $OUTPUT->main_content(); ?></div>
<?php echo $OUTPUT->standard_footer_html(); ?>
<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>

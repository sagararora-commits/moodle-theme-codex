<?php
defined('MOODLE_INTERNAL') || die();

global $USER, $OUTPUT, $CFG, $PAGE, $COURSE, $SITE, $DB;

require_once($CFG->dirroot . '/local/codex/lib.php');

// === Admin users + management URLs → fall through to parent (moove) layout ===
$current_url = $_SERVER['REQUEST_URI'] ?? '';
$is_admin_page = (
    strpos($current_url, '/user/index.php') !== false ||
    strpos($current_url, '/user/view.php') !== false ||
    strpos($current_url, '/admin/') !== false ||
    strpos($current_url, '/enrol/') !== false ||
    strpos($current_url, '/group/') !== false ||
    strpos($current_url, '/grade/') !== false ||
    strpos($current_url, '/backup/') !== false ||
    strpos($current_url, '/report/') !== false ||
    strpos($current_url, '/course/edit') !== false ||
    strpos($current_url, '/course/management') !== false ||
    strpos($current_url, '/cohort/') !== false ||
    strpos($current_url, '/badges/') !== false ||
    strpos($current_url, '/mod/') !== false && strpos($current_url, 'edit') !== false ||
    strpos($current_url, '/question/') !== false
);
// Site admins ALWAYS get the moove layout — they need full editing UI
if (is_siteadmin() || $is_admin_page) {
    require($CFG->dirroot . '/theme/moove/layout/incourse.php');
    return;
}
// === End admin bypass ===



$bodyclasses = $PAGE->bodyclasses . ' codex-incourse';

$firstname = !empty($USER->firstname) ? format_string($USER->firstname) : 'there';
$lastname  = !empty($USER->lastname)  ? format_string($USER->lastname)  : '';
$initials  = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
$logourl   = $OUTPUT->image_url('logo', 'theme_codex');

// Role detection at course level (if we have a course context)
$is_admin = is_siteadmin();
$is_teacher = false;
$courseid = 0;
$coursename = '';
$palette = 'math';

if (isset($COURSE) && $COURSE && $COURSE->id != SITEID) {
    $courseid = $COURSE->id;
    $coursename = format_string($COURSE->fullname);
    $palette = local_codex_palette_for($coursename . ' ' . $COURSE->shortname);
    $ctx = context_course::instance($courseid);
    if (has_capability('moodle/course:update', $ctx)) {
        $is_teacher = true;
    }
}
$role_label = $is_admin ? 'Admin' : ($is_teacher ? 'Teacher' : 'Student');

// Try to figure out section number if we're inside a course module
$sectionnum = 0;
if (!empty($PAGE->cm) && !empty($PAGE->cm->sectionnum)) {
    $sectionnum = (int) $PAGE->cm->sectionnum;
}

?>
<!DOCTYPE html>
<?php echo $OUTPUT->doctype(); ?>
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
<body <?php echo $OUTPUT->body_attributes($bodyclasses); ?>>
<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div class="codex-app">

    <?php $active = 'subjects'; include $CFG->dirroot . '/local/codex/sidebar.php'; ?>

    <div class="codex-main-wrap">
        <header class="codex-topbar">
            <div class="codex-searchbar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" placeholder="Search anything..." />
            </div>
            <a class="codex-icon-btn" href="<?php echo $CFG->wwwroot; ?>/message/index.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/></svg>
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

        <main class="codex-main codex-incourse-main">

            <?php if ($courseid > 0): ?>
            <nav class="codex-breadcrumb">
                <a href="<?php echo $CFG->wwwroot; ?>/my/">Home</a>
                <span class="sep">›</span>
                <a href="<?php echo $CFG->wwwroot; ?>/local/codex/course_dashboard.php?id=<?php echo $courseid; ?>"><?php echo $coursename; ?></a>
                <?php if ($sectionnum > 0): ?>
                <span class="sep">›</span>
                <a href="<?php echo $CFG->wwwroot; ?>/local/codex/chapter_view.php?courseid=<?php echo $courseid; ?>&sectionnum=<?php echo $sectionnum; ?>">Chapter <?php echo $sectionnum; ?></a>
                <?php endif; ?>
                <span class="sep">›</span>
                <span class="current"><?php echo $OUTPUT->page_title(); ?></span>
            </nav>
            <?php endif; ?>

            <div class="codex-incourse-content">
                <?php
                // ===== Codex completion banner (quiz / H5P review pages) =====
                $is_quiz_review = strpos($current_url, '/mod/quiz/review.php')  !== false;
                $is_quiz_summary = false; // summary is PRE-submit, not post-submit
                $is_h5p_review  = strpos($current_url, '/mod/h5pactivity/report.php') !== false
                                || strpos($current_url, '/mod/h5pactivity/view.php')   !== false && !empty($_GET['attemptid']);
                $show_banner = $is_quiz_review || $is_quiz_summary || $is_h5p_review;

                // Try to read score for quizzes (best-effort, fails silently)
                $banner_score = '';
                $banner_total = '';
                $banner_pct   = null;
                if ($is_quiz_review && !empty($_GET['attempt'])) {
                    try {
                        $attemptid = (int) $_GET['attempt'];
                        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], 'id, quiz, sumgrades, state');
                        if ($attempt && $attempt->state === 'finished') {
                            $quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], 'id, grade, sumgrades, name');
                            if ($quiz && $quiz->sumgrades > 0) {
                                $banner_pct = (int) round(($attempt->sumgrades / $quiz->sumgrades) * 100);
                                $banner_score = number_format($attempt->sumgrades, 1);
                                $banner_total = number_format($quiz->sumgrades, 1);
                            }
                        }
                    } catch (Exception $e) { /* silent */ }
                }

                // Build back link to chapter view
                $back_url = $CFG->wwwroot . '/my/';
                $back_label = 'Back to dashboard';
                if (!empty($courseid) && !empty($sectionnum)) {
                    $back_url = $CFG->wwwroot . '/local/codex/chapter_view.php?courseid=' . (int)$courseid . '&sectionnum=' . (int)$sectionnum;
                    $back_label = 'Back to chapter';
                } elseif (!empty($courseid)) {
                    $back_url = $CFG->wwwroot . '/local/codex/course_dashboard.php?id=' . (int)$courseid;
                    $back_label = 'Back to subject';
                }

                if ($show_banner):
                    // Tone: encouraging but neutral if no score, celebratory if good, gentle if low
                    $headline = 'Well done!';
                    $subline  = 'You\'ve completed this activity.';
                    $tone     = 'neutral';
                    if ($banner_pct !== null) {
                        if ($banner_pct >= 80) { $headline = 'Excellent work!'; $subline = 'You scored '.$banner_score.' / '.$banner_total.' &mdash; that\'s '.$banner_pct.'%.'; $tone = 'great'; }
                        elseif ($banner_pct >= 50) { $headline = 'Nice effort!'; $subline = 'You scored '.$banner_score.' / '.$banner_total.' ('.$banner_pct.'%). Review what you got wrong below.'; $tone = 'good'; }
                        else { $headline = 'Good try.'; $subline = 'You scored '.$banner_score.' / '.$banner_total.' ('.$banner_pct.'%). Look through the feedback below and try again when you\'re ready.'; $tone = 'low'; }
                    }
                ?>
                <div class="codex-completion-banner codex-tone-<?php echo $tone; ?>">
                    <div class="codex-completion-icon" aria-hidden="true">
                        <?php if ($tone === 'great' || $tone === 'good'): ?>
                            <svg width="56" height="56" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="22" fill="#fff" stroke="currentColor" stroke-width="2.5"/><path d="M15 24l6 6 12-12" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                        <?php elseif ($tone === 'low'): ?>
                            <svg width="56" height="56" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="22" fill="#fff" stroke="currentColor" stroke-width="2.5"/><path d="M16 28c2-3 5-5 8-5s6 2 8 5" stroke="currentColor" stroke-width="3" stroke-linecap="round" fill="none"/><circle cx="18" cy="19" r="2" fill="currentColor"/><circle cx="30" cy="19" r="2" fill="currentColor"/></svg>
                        <?php else: ?>
                            <svg width="56" height="56" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="22" fill="#fff" stroke="currentColor" stroke-width="2.5"/><path d="M24 14v12M24 32v.5" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="codex-completion-text">
                        <h2><?php echo $headline; ?></h2>
                        <p><?php echo $subline; ?></p>
                    </div>
                    <a class="codex-completion-cta" href="<?php echo $back_url; ?>">
                        <?php echo $back_label; ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 6 15 12 9 18"/></svg>
                    </a>
                </div>
                <?php endif; ?>
                <?php echo $OUTPUT->main_content(); ?>
            </div>

        </main>
    </div>
</div>

<?php echo $OUTPUT->standard_footer_html(); ?>
<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>

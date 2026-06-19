<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

global $DB, $CFG, $OUTPUT, $PAGE, $USER;

$PAGE->set_url('/local/codex/subjects.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('codex_app');
$PAGE->set_title('Subjects — Codex');
$PAGE->set_heading('Subjects');
$PAGE->add_body_class('codex-dashboard');

$categories = $DB->get_records_sql(
    "SELECT cc.id, cc.name, cc.idnumber, cc.coursecount
     FROM {course_categories} cc
     WHERE cc.visible = 1 AND cc.coursecount > 0
     ORDER BY cc.sortorder ASC"
);

echo $OUTPUT->header();
?>
<main class="codex-app-main">
    <header class="codex-page-header">
        <div>
            <h1 class="codex-page-title">Browse Subjects</h1>
            <p class="codex-page-sub">Pick a class to explore subjects, chapters and content.</p>
        </div>
    </header>

    <div class="codex-class-grid">
        <?php if (empty($categories)): ?>
            <?php echo local_codex_empty_state("classes", "No classes available", "There are no classes set up yet. Please contact your school administrator.", "", ""); ?>
        <?php endif; ?>
        <?php foreach ($categories as $cat): ?>
            <?php
            $palette = local_codex_palette_for($cat->name);
            $url = $CFG->wwwroot . '/local/codex/class_view.php?catid=' . $cat->id;
            ?>
            <a href="<?php echo $url; ?>" class="codex-class-card"
               <?php $pcolors = local_codex_palette_colors($palette); ?>style="--accent: <?php echo $pcolors['accent']; ?>; --soft: <?php echo $pcolors['soft']; ?>;">
                <div class="codex-class-card-illo">
                    <?php echo function_exists('local_codex_subject_svg') ? local_codex_subject_svg($palette) : ''; ?>
                </div>
                <div class="codex-class-card-body">
                    <div class="codex-class-card-name"><?php echo format_string($cat->name); ?></div>
                    <div class="codex-class-card-meta"><?php echo $cat->coursecount; ?> subjects</div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</main>
<?php
echo $OUTPUT->footer();

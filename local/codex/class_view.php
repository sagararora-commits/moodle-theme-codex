<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

global $DB, $CFG, $OUTPUT, $PAGE, $USER;

$catid = required_param('catid', PARAM_INT);
$category = $DB->get_record('course_categories', ['id' => $catid, 'visible' => 1], '*', MUST_EXIST);

$PAGE->set_url('/local/codex/class_view.php', ['catid' => $catid]);
$PAGE->set_context(context_coursecat::instance($catid));
$PAGE->set_pagelayout('codex_app');
$PAGE->set_title($category->name . ' — Codex');
$PAGE->set_heading($category->name);
$PAGE->add_body_class('codex-dashboard');

$courses = $DB->get_records('course', ['category' => $catid, 'visible' => 1], 'sortorder ASC',
    'id, shortname, fullname, summary');

echo $OUTPUT->header();
?>
<main class="codex-app-main">
    <header class="codex-page-header">
        <div>
            <?php
            echo local_codex_breadcrumb([
                ['label' => 'Subjects', 'url' => $CFG->wwwroot . '/local/codex/subjects.php'],
                ['label' => format_string($category->name)],
            ]);
            ?>
            <h1 class="codex-page-title"><?php echo format_string($category->name); ?></h1>
            <p class="codex-page-sub">Pick a subject to view chapters and content.</p>
        </div>
    </header>

    <div class="codex-subjects-grid">
        <?php if (empty($courses)): ?>
            <?php echo local_codex_empty_state("subjects", "No subjects yet", "This class doesn't have any subjects set up. Check back soon.", $CFG->wwwroot . "/local/codex/subjects.php", "Back to classes"); ?>
        <?php endif; ?>
        <?php foreach ($courses as $c): ?>
            <?php
            $palette = local_codex_palette_for($c->fullname . ' ' . $c->shortname);
            $url = $CFG->wwwroot . '/local/codex/course_dashboard.php?id=' . $c->id;
            $modinfo = get_fast_modinfo($c->id);
            $chapter_count = 0;
            foreach ($modinfo->get_section_info_all() as $s) {
                if ($s->section > 0 && $s->visible) $chapter_count++;
            }
            ?>
            <a href="<?php echo $url; ?>" class="codex-subject-card"
               <?php $pcolors = local_codex_palette_colors($palette); ?>style="--accent: <?php echo $pcolors['accent']; ?>; --soft: <?php echo $pcolors['soft']; ?>; background: <?php echo $pcolors['soft']; ?>;">
                <div class="codex-subject-card-body">
                    <div class="codex-subject-card-name"><?php echo format_string($c->fullname); ?></div>
                    <div class="codex-subject-card-meta"><?php echo $chapter_count; ?> chapters</div>
                </div>
                <div class="codex-subject-card-illo">
                    <?php echo function_exists('local_codex_subject_svg') ? local_codex_subject_svg($palette) : ''; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</main>
<?php
echo $OUTPUT->footer();

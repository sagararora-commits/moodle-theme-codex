<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

global $OUTPUT, $PAGE, $CFG;

$PAGE->set_url('/local/codex/assessments.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('codex_app');
$PAGE->set_title('Assessments — Codex');
$PAGE->set_heading('Assessments');
$PAGE->add_body_class('codex-dashboard');

// Build hierarchy: course -> section -> quizzes.
$courses = local_codex_visible_courses();
$tree = [];
foreach ($courses as $c) {
    $modinfo = get_fast_modinfo($c->id);
    $chapters = [];
    foreach ($modinfo->get_section_info_all() as $section) {
        $quizzes = [];
        $cmids = $modinfo->sections[$section->section] ?? [];
        foreach ($cmids as $cmid) {
            $cm = $modinfo->cms[$cmid];
            if ($cm->modname === 'quiz' && $cm->uservisible) {
                $quizzes[] = [
                    'name' => format_string($cm->name),
                    'url'  => $CFG->wwwroot . '/mod/quiz/view.php?id=' . $cm->id,
                ];
            }
        }
        if (!empty($quizzes)) {
            $chapters[] = [
                'name' => $section->name ?: ('Chapter ' . $section->section),
                'quizzes' => $quizzes,
            ];
        }
    }
    if (!empty($chapters)) {
        $palette = local_codex_palette_for($c->fullname . ' ' . $c->shortname);
        $tree[] = [
            'id' => $c->id,
            'name' => format_string($c->fullname),
            'palette_key' => $palette, 'palette' => local_codex_palette_colors($palette),
            'chapters' => $chapters,
        ];
    }
}

echo $OUTPUT->header();
$active = 'assessments';
?>
<main class="codex-app-main">
    <header class="codex-page-header">
        <div>
            <h1 class="codex-page-title">Assessments</h1>
            <p class="codex-page-sub">All quizzes across your subjects, organised by chapter.</p>
        </div>
        <div class="codex-search">
            <input type="text" id="codex-quiz-search" placeholder="Search quizzes, chapters, subjects…">
        </div>
    </header>

    <div class="codex-tree" id="codex-tree">
        <?php if (empty($tree)): ?>
            <?php echo local_codex_empty_state("quizzes", "No quizzes yet", "Quizzes will show up here once your teachers publish them. In the meantime, browse your subjects.", $CFG->wwwroot . "/local/codex/subjects.php", "Browse subjects"); ?>
        <?php endif; ?>
        <?php foreach ($tree as $subj): ?>
            <details class="codex-subject" open
                     style="--accent: <?php echo $subj['palette']['accent']; ?>; --soft: <?php echo $subj['palette']['soft']; ?>;">
                <summary class="codex-subject-head">
                    <span class="codex-subject-dot"></span>
                    <span class="codex-subject-name"><?php echo $subj['name']; ?></span>
                    <span class="codex-count">
                        <?php echo array_sum(array_map(function($ch) { return count($ch["quizzes"]); }, $subj["chapters"])); ?> quizzes
                    </span>
                </summary>
                <?php foreach ($subj['chapters'] as $ch): ?>
                    <div class="codex-chapter">
                        <div class="codex-chapter-head"><?php echo format_string($ch['name']); ?></div>
                        <ul class="codex-quiz-list">
                            <?php foreach ($ch['quizzes'] as $q): ?>
                                <li>
                                    <a href="<?php echo $q['url']; ?>" class="codex-quiz-item">
                                        <span class="codex-quiz-icon">📝</span>
                                        <span class="codex-quiz-name"><?php echo $q['name']; ?></span>
                                        <span class="codex-quiz-cta">Open →</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </details>
        <?php endforeach; ?>
    </div>
</main>

<script>
(function() {
    var input = document.getElementById('codex-quiz-search');
    var tree = document.getElementById('codex-tree');
    if (!input || !tree) return;
    input.addEventListener('input', function() {
        var q = this.value.trim().toLowerCase();
        tree.querySelectorAll('.codex-subject').forEach(function(subj) {
            var anySubjectMatch = false;
            subj.querySelectorAll('.codex-chapter').forEach(function(ch) {
                var chapterName = ch.querySelector('.codex-chapter-head').textContent.toLowerCase();
                var anyQuizMatch = false;
                ch.querySelectorAll('.codex-quiz-item').forEach(function(qi) {
                    var name = qi.querySelector('.codex-quiz-name').textContent.toLowerCase();
                    var match = !q || name.includes(q) || chapterName.includes(q);
                    qi.closest('li').style.display = match ? '' : 'none';
                    if (match) anyQuizMatch = true;
                });
                ch.style.display = anyQuizMatch ? '' : 'none';
                if (anyQuizMatch) anySubjectMatch = true;
            });
            var subjName = subj.querySelector('.codex-subject-name').textContent.toLowerCase();
            if (q && subjName.includes(q)) anySubjectMatch = true;
            subj.style.display = anySubjectMatch ? '' : 'none';
            if (q) subj.setAttribute('open', '');
        });
    });
})();
</script>
<?php
echo $OUTPUT->footer();

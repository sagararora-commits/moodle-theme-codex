<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

global $OUTPUT, $PAGE, $CFG;

$PAGE->set_url('/local/codex/interactive.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('codex_app');
$PAGE->set_title('Interactive Activities — Codex');
$PAGE->set_heading('Interactive Activities');
$PAGE->add_body_class('codex-dashboard');

$INTERACTIVE_MODS = ['h5pactivity', 'hvp', 'scorm', 'lti', 'lesson'];
$ICONS = [
    'h5pactivity' => '⚡', 'hvp' => '⚡', 'scorm' => '🎯', 'lti' => '🔗', 'lesson' => '📖',
];
$LABELS = [
    'h5pactivity' => 'H5P', 'hvp' => 'H5P', 'scorm' => 'SCORM', 'lti' => 'External', 'lesson' => 'Lesson',
];

$courses = local_codex_visible_courses();
$tree = [];
foreach ($courses as $c) {
    $modinfo = get_fast_modinfo($c->id);
    $chapters = [];
    foreach ($modinfo->get_section_info_all() as $section) {
        $items = [];
        $cmids = $modinfo->sections[$section->section] ?? [];
        foreach ($cmids as $cmid) {
            $cm = $modinfo->cms[$cmid];
            if (in_array($cm->modname, $INTERACTIVE_MODS) && $cm->uservisible) {
                $items[] = [
                    'name'  => format_string($cm->name),
                    'url'   => $CFG->wwwroot . '/mod/' . $cm->modname . '/view.php?id=' . $cm->id,
                    'icon'  => $ICONS[$cm->modname] ?? '⚡',
                    'label' => $LABELS[$cm->modname] ?? ucfirst($cm->modname),
                ];
            }
        }
        if (!empty($items)) {
            $chapters[] = [
                'name'  => $section->name ?: ('Chapter ' . $section->section),
                'items' => $items,
            ];
        }
    }
    if (!empty($chapters)) {
        $palette = local_codex_palette_for($c->fullname . ' ' . $c->shortname);
        $tree[] = [
            'name'     => format_string($c->fullname),
            'palette_key' => $palette, 'palette' => local_codex_palette_colors($palette),
            'chapters' => $chapters,
        ];
    }
}

echo $OUTPUT->header();
$active = 'interactive';
?>
<main class="codex-app-main">
    <header class="codex-page-header">
        <div>
            <h1 class="codex-page-title">Interactive Activities</h1>
            <p class="codex-page-sub">H5P, SCORM, Lessons and external tools across your subjects.</p>
        </div>
        <div class="codex-search">
            <input type="text" id="codex-int-search" placeholder="Search activities, chapters, subjects…">
        </div>
    </header>

    <div class="codex-tree" id="codex-tree">
        <?php if (empty($tree)): ?>
            <?php echo local_codex_empty_state("interactive", "No activities yet", "Interactive lessons and games will appear here as teachers add them.", $CFG->wwwroot . "/local/codex/subjects.php", "Browse subjects"); ?>
        <?php endif; ?>
        <?php foreach ($tree as $subj): ?>
            <details class="codex-subject" open
                     style="--accent: <?php echo $subj['palette']['accent']; ?>; --soft: <?php echo $subj['palette']['soft']; ?>;">
                <summary class="codex-subject-head">
                    <span class="codex-subject-dot"></span>
                    <span class="codex-subject-name"><?php echo $subj['name']; ?></span>
                    <span class="codex-count">
                        <?php echo array_sum(array_map(function($ch) { return count($ch["items"]); }, $subj["chapters"])); ?> activities
                    </span>
                </summary>
                <?php foreach ($subj['chapters'] as $ch): ?>
                    <div class="codex-chapter">
                        <div class="codex-chapter-head"><?php echo format_string($ch['name']); ?></div>
                        <ul class="codex-quiz-list">
                            <?php foreach ($ch['items'] as $it): ?>
                                <li>
                                    <a href="<?php echo $it['url']; ?>" class="codex-quiz-item">
                                        <span class="codex-quiz-icon"><?php echo $it['icon']; ?></span>
                                        <span class="codex-quiz-name"><?php echo $it['name']; ?></span>
                                        <span class="codex-quiz-type"><?php echo $it['label']; ?></span>
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
    var input = document.getElementById('codex-int-search');
    var tree = document.getElementById('codex-tree');
    if (!input || !tree) return;
    input.addEventListener('input', function() {
        var q = this.value.trim().toLowerCase();
        tree.querySelectorAll('.codex-subject').forEach(function(subj) {
            var anySubjectMatch = false;
            subj.querySelectorAll('.codex-chapter').forEach(function(ch) {
                var chapterName = ch.querySelector('.codex-chapter-head').textContent.toLowerCase();
                var anyMatch = false;
                ch.querySelectorAll('.codex-quiz-item').forEach(function(qi) {
                    var name = qi.querySelector('.codex-quiz-name').textContent.toLowerCase();
                    var match = !q || name.includes(q) || chapterName.includes(q);
                    qi.closest('li').style.display = match ? '' : 'none';
                    if (match) anyMatch = true;
                });
                ch.style.display = anyMatch ? '' : 'none';
                if (anyMatch) anySubjectMatch = true;
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

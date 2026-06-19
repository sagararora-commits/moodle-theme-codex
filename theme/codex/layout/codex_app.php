<?php
// Codex App layout — sidebar + main content. Used by local/codex/*.php pages.
defined('MOODLE_INTERNAL') || die();

$bodyclasses = ['codex-dashboard', 'codex-app-layout'];

echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html(); ?>
</head>
<body <?php echo $OUTPUT->body_attributes($bodyclasses); ?>>
<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div class="codex-shell">
    <?php
    // Sidebar (active state set by the including page via $active before render).
    $active = isset($PAGE->cm) ? '' : ($active ?? '');
    include $CFG->dirroot . '/local/codex/sidebar.php';
    ?>
    <div class="codex-content">
        <?php echo $OUTPUT->main_content(); ?>
    </div>
</div>

<?php echo $OUTPUT->standard_footer_html(); ?>
<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>

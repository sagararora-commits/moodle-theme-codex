<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/accesslib.php');

global $DB;

$shortname = 'codexviewer';
$existing = $DB->get_record('role', ['shortname' => $shortname]);

if ($existing) {
    echo "Role '$shortname' already exists (id={$existing->id}). Updating capabilities...\n";
    $roleid = $existing->id;
} else {
    $roleid = create_role(
        'Codex Viewer', $shortname,
        'Read-only browse + attempt access across the full catalog.',
        'student'
    );
    echo "Created role 'Codex Viewer' (id=$roleid)\n";
    set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
}

$capabilities = [
    'moodle/course:view'              => CAP_ALLOW,
    'moodle/course:viewhiddencourses' => CAP_ALLOW,
    'moodle/category:viewcourselist'  => CAP_ALLOW,
    'mod/quiz:view'                   => CAP_ALLOW,
    'mod/quiz:attempt'                => CAP_ALLOW,
    'mod/resource:view'               => CAP_ALLOW,
    'mod/page:view'                   => CAP_ALLOW,
    'mod/url:view'                    => CAP_ALLOW,
    'mod/book:view'                   => CAP_ALLOW,
    'mod/folder:view'                 => CAP_ALLOW,
    'mod/h5pactivity:view'            => CAP_ALLOW,
    'mod/lesson:view'                 => CAP_ALLOW,
    'mod/scorm:view'                  => CAP_ALLOW,
];

$syscontext = context_system::instance();
foreach ($capabilities as $cap => $perm) {
    if (get_capability_info($cap)) {
        assign_capability($cap, $perm, $roleid, $syscontext->id, true);
    } else {
        echo "Skip (cap not installed): $cap\n";
    }
}
echo "Capabilities set.\n";

$users = $DB->get_records_select('user', 'deleted = 0 AND username != ?', ['guest'], '', 'id');
$count = 0;
foreach ($users as $u) {
    if (!user_has_role_assignment($u->id, $roleid, $syscontext->id)) {
        role_assign($roleid, $u->id, $syscontext->id);
        $count++;
    }
}
echo "Role assigned to $count new user(s).\n";

accesslib_clear_all_caches_for_unit_testing();
echo "Done.\n";

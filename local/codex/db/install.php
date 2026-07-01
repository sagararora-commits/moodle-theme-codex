<?php
function xmldb_local_codex_install() {
    global $DB;
    $role = $DB->get_record('role', ['shortname' => 'codexviewer']);
    if (!$role) {
        return true;
    }
    $syscontext = context_system::instance();
    $existing = $DB->get_record('role_capabilities', [
        'contextid'  => $syscontext->id,
        'roleid'     => $role->id,
        'capability' => 'moodle/course:viewhiddenactivities',
    ]);
    if (!$existing) {
        assign_capability('moodle/course:viewhiddenactivities', CAP_ALLOW, $role->id, $syscontext->id);
    }
    return true;
}

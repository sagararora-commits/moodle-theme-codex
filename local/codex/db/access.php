<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/codex:manage' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
        ],
    ],
];

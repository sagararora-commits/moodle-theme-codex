<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Map a course name to a color palette key.
 * Centralizes the logic so dashboard, course view, and chapter view all agree.
 */
function local_codex_palette_for($name) {
    $n = strtolower($name);
    if (preg_match('/\b(social|sst|history|geography|civics|political)\b/', $n))    return 'social';
    if (preg_match('/\b(environment|environmental|evs)\b/', $n))                    return 'evs';
    if (preg_match('/\b(english|literature|grammar|language|hindi|sanskrit|urdu)\b/', $n)
        || preg_match('/(हिंदी|व्याकरण|संस्कृत)/u', $n))                                  return 'english';
    if (preg_match('/\b(science|physics|chemistry|biology|sci)\b/', $n))            return 'science';
    if (preg_match('/\b(math|maths|mathematics|algebra|geometry|arithmetic)\b/', $n)
        || preg_match('/(गणित)/u', $n))                                                return 'math';
    // Fallback: rotate through 5 palettes deterministically by name hash.
    $palettes = ['math', 'science', 'english', 'evs', 'social'];
    $idx = abs(crc32($name)) % count($palettes);
    return $palettes[$idx];
}

/**
 * Return single-glyph icon for a palette key.
 */
function local_codex_icon_for($palette) {
    switch ($palette) {
        case 'math':    return 'π';
        case 'science': return '🧪';
        case 'english': return 'Aa';
        case 'evs':     return '🌱';
        case 'social':  return '🌐';
    }
    return '•';
}

/**
 * Return inline SVG illustration for a palette key.
 */
function local_codex_subject_svg($palette) {
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

/**
 * Numbered chapter card illustration (small variant for chapter cards on Mockup 3).
 */
function local_codex_chapter_svg($index, $palette) {
    $colors = [
        'math'    => ['#fb923c', '#c2410c'],
        'science' => ['#65a30d', '#4d7c0f'],
        'english' => ['#ec4899', '#be185d'],
        'evs'     => ['#22c55e', '#15803d'],
        'social'  => ['#3b82f6', '#1d4ed8'],
    ];
    $c = $colors[$palette] ?? $colors['math'];
    // Generic chapter illustration: stack of blocks with the chapter number.
    return '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect x="25" y="55" width="50" height="30" rx="4" fill="'.$c[0].'" opacity="0.9"/><rect x="32" y="40" width="36" height="18" rx="3" fill="'.$c[1].'" opacity="0.7"/><text x="50" y="76" font-family="Fraunces,serif" font-size="18" font-weight="700" fill="white" text-anchor="middle">'.str_pad($index, 2, '0', STR_PAD_LEFT).'</text></svg>';
}

function local_codex_role_label() {
    global $USER;
    if (is_siteadmin()) return 'Admin';
    // Manager role check (custom role we'll define, shortname 'codexmanager').
    if ($manager = $GLOBALS['DB']->get_record('role', ['shortname' => 'codexmanager'])) {
        if (user_has_role_assignment($USER->id, $manager->id)) return 'Manager';
    }
    // Editing teacher anywhere = Teacher.
    $teacherrole = $GLOBALS['DB']->get_record('role', ['shortname' => 'editingteacher']);
    if ($teacherrole && $GLOBALS['DB']->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $teacherrole->id])) {
        return 'Teacher';
    }
    return 'Student';
}

function local_codex_visible_courses() {
    // Returns courses the current user should see in Assessments/Interactive lists.
    // Teacher: assigned courses only. Manager: all courses in their category. Admin: all.
    global $USER, $DB;
    if (is_siteadmin()) {
        return get_courses('all', 'c.sortorder ASC', 'c.id, c.shortname, c.fullname, c.category');
    }
    // Manager scoped to category.
    $managerrole = $DB->get_record('role', ['shortname' => 'codexmanager']);
    if ($managerrole) {
        $assignments = $DB->get_records('role_assignments', ['userid' => $USER->id, 'roleid' => $managerrole->id]);
        $catids = [];
        foreach ($assignments as $a) {
            $ctx = context::instance_by_id($a->contextid, IGNORE_MISSING);
            if ($ctx && $ctx->contextlevel == CONTEXT_COURSECAT) $catids[] = $ctx->instanceid;
        }
        if (!empty($catids)) {
            list($insql, $params) = $DB->get_in_or_equal($catids);
            return $DB->get_records_sql(
                "SELECT id, shortname, fullname, category FROM {course}
                 WHERE category $insql AND visible = 1 ORDER BY sortorder ASC", $params);
        }
    }
    // Teacher / default: enrolled courses.
    return enrol_get_my_courses('id, shortname, fullname, category', 'fullname ASC');
}

/**
 * Render a friendly empty state with illustration + optional CTA.
 *
 * @param string $variant  One of: courses, chapters, content, quizzes, interactive, classes, subjects
 * @param string $title    Headline
 * @param string $message  Supporting copy
 * @param string $cta_url  Optional CTA href (empty for none)
 * @param string $cta_label Optional CTA label
 * @return string HTML
 */
function local_codex_empty_state($variant, $title, $message, $cta_url = '', $cta_label = '') {
    $illustrations = [
        'courses' => '<svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="20" y="30" width="80" height="60" rx="8" fill="#FFF1EE" stroke="#E8312A" stroke-width="2"/><rect x="32" y="44" width="56" height="6" rx="3" fill="#E8312A" opacity="0.4"/><rect x="32" y="58" width="40" height="6" rx="3" fill="#E8312A" opacity="0.25"/><rect x="32" y="72" width="48" height="6" rx="3" fill="#E8312A" opacity="0.25"/><circle cx="92" cy="32" r="10" fill="#1C5A7A"/><path d="M88 32l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'chapters' => '<svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M30 25h40a8 8 0 018 8v54a8 8 0 01-8 8H30V25z" fill="#FFF1EE" stroke="#E8312A" stroke-width="2"/><path d="M30 25v70" stroke="#E8312A" stroke-width="2"/><rect x="40" y="40" width="28" height="4" rx="2" fill="#E8312A" opacity="0.4"/><rect x="40" y="52" width="22" height="4" rx="2" fill="#E8312A" opacity="0.25"/><rect x="40" y="64" width="26" height="4" rx="2" fill="#E8312A" opacity="0.25"/></svg>',
        'content' => '<svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="25" y="30" width="50" height="65" rx="6" fill="#FFF1EE" stroke="#E8312A" stroke-width="2"/><rect x="45" y="35" width="50" height="65" rx="6" fill="#fff" stroke="#1C5A7A" stroke-width="2"/><rect x="55" y="48" width="30" height="4" rx="2" fill="#1C5A7A" opacity="0.4"/><rect x="55" y="60" width="24" height="4" rx="2" fill="#1C5A7A" opacity="0.25"/><rect x="55" y="72" width="28" height="4" rx="2" fill="#1C5A7A" opacity="0.25"/></svg>',
        'quizzes' => '<svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="60" cy="60" r="38" fill="#FFF1EE" stroke="#E8312A" stroke-width="2"/><path d="M52 52a8 8 0 1116 0c0 5-8 6-8 12" stroke="#E8312A" stroke-width="3" stroke-linecap="round"/><circle cx="60" cy="78" r="2.5" fill="#E8312A"/></svg>',
        'interactive' => '<svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="60" cy="60" r="38" fill="#FFF1EE" stroke="#E8312A" stroke-width="2"/><path d="M52 46l22 14-22 14V46z" fill="#E8312A"/></svg>',
        'classes' => '<svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 50l40-20 40 20-40 20-40-20z" fill="#FFF1EE" stroke="#E8312A" stroke-width="2" stroke-linejoin="round"/><path d="M35 58v18c0 4 11 8 25 8s25-4 25-8V58" stroke="#1C5A7A" stroke-width="2" stroke-linecap="round"/><path d="M95 50v22" stroke="#E8312A" stroke-width="2" stroke-linecap="round"/></svg>',
        'subjects' => '<svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="28" y="28" width="28" height="28" rx="4" fill="#FFF1EE" stroke="#E8312A" stroke-width="2"/><rect x="64" y="28" width="28" height="28" rx="4" fill="#fff" stroke="#1C5A7A" stroke-width="2"/><rect x="28" y="64" width="28" height="28" rx="4" fill="#fff" stroke="#1C5A7A" stroke-width="2"/><rect x="64" y="64" width="28" height="28" rx="4" fill="#FFF1EE" stroke="#E8312A" stroke-width="2"/></svg>',
    ];
    $illo = isset($illustrations[$variant]) ? $illustrations[$variant] : $illustrations['content'];

    $cta_html = '';
    if (!empty($cta_url) && !empty($cta_label)) {
        $cta_html = '<a class="codex-empty-cta" href="' . htmlspecialchars($cta_url, ENT_QUOTES) . '">' . htmlspecialchars($cta_label) . ' <span aria-hidden="true">&rarr;</span></a>';
    }

    $css = '<style>
.codex-empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:48px 24px;background:#fff;border:1px dashed #ECEFF1;border-radius:16px;min-height:280px;}
.codex-empty-state .codex-empty-illo{width:120px;height:120px;margin-bottom:20px;}
.codex-empty-state .codex-empty-illo svg{width:100%;height:100%;}
.codex-empty-state h3{font-family:Fraunces,Georgia,serif;font-weight:600;font-size:22px;color:#1C2A30;margin:0 0 8px;}
.codex-empty-state p{font-family:"DM Sans","Plus Jakarta Sans",system-ui,sans-serif;font-size:15px;color:#5A6B73;margin:0 0 20px;max-width:380px;line-height:1.5;}
.codex-empty-state .codex-empty-cta{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#E8312A;color:#fff;text-decoration:none;border-radius:999px;font-family:"DM Sans",system-ui,sans-serif;font-weight:600;font-size:14px;transition:transform .15s ease,box-shadow .15s ease;}
.codex-empty-state .codex-empty-cta:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(232,49,42,0.25);color:#fff;text-decoration:none;}
</style>';

    return $css . '<div class="codex-empty-state"><div class="codex-empty-illo">' . $illo . '</div><h3>' . htmlspecialchars($title) . '</h3><p>' . htmlspecialchars($message) . '</p>' . $cta_html . '</div>';
}

/**
 * Render a breadcrumb trail.
 * @param array $items  List of items, each ['label'=>'...', 'url'=>'...' (optional, last item is "current" if url omitted)]
 * @return string HTML
 */
function local_codex_breadcrumb(array $items) {
    if (empty($items)) return '';
    $out = '<nav class="codex-breadcrumb" aria-label="Breadcrumb">';
    $last = count($items) - 1;
    foreach ($items as $i => $it) {
        $label = htmlspecialchars($it['label']);
        $is_last = ($i === $last);
        if ($is_last || empty($it['url'])) {
            $out .= '<span class="current">' . $label . '</span>';
        } else {
            $out .= '<a href="' . htmlspecialchars($it['url'], ENT_QUOTES) . '">' . $label . '</a>';
        }
        if (!$is_last) {
            $out .= '<span class="sep" aria-hidden="true">›</span>';
        }
    }
    $out .= '</nav>';
    return $out;
}


/**
 * Return ['accent' => '#hex', 'soft' => '#hex'] for a palette key.
 * Defaults to the codex teal pair (matches SCSS fallbacks).
 */
function local_codex_palette_colors($palette) {
    $map = [
        'math'    => ['accent' => '#fb923c', 'soft' => '#fff4e6'],
        'science' => ['accent' => '#65a30d', 'soft' => '#ecfccb'],
        'english' => ['accent' => '#ec4899', 'soft' => '#fce7f3'],
        'evs'     => ['accent' => '#22c55e', 'soft' => '#dcfce7'],
        'social'  => ['accent' => '#3b82f6', 'soft' => '#dbeafe'],
    ];
    return $map[$palette] ?? ['accent' => '#1C5A7A', 'soft' => '#EAF2F6'];
}

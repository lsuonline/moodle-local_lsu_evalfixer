<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Report showing what effects the configured scheduled task will have.
 *
 * @package local_lsu_evalfixer
 * @copyright 2024
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/format/lib.php');

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$config = get_config('local_lsu_evalfixer');

$searchurl = trim($config->searchurl ?? '');
$onlyempty = (bool)($config->onlyempty ?? true);
$action = $config->action ?? 'replace';
$sectionnamesearch = trim($config->sectionnamesearch ?? '');
$replacementurl = trim($config->replacementurl ?? '');
$sectionnameoverride = trim($config->sectionname ?? '');
$emptyavailability = '{"op":"&","c":[],"showc":[]}';

$PAGE->set_url('/local/lsu_evalfixer/report.php');
$PAGE->set_title(get_string('report_title', 'local_lsu_evalfixer'));
$PAGE->set_heading(get_string('report_title', 'local_lsu_evalfixer'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report_title', 'local_lsu_evalfixer'));

if (empty($searchurl)) {
    echo $OUTPUT->notification('Search URL must be configured to run the report.');
    echo $OUTPUT->footer();
    exit;
}

$htmlsearch = htmlspecialchars($searchurl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$matchsql = '(' . $DB->sql_like('cs.summary', ':rawurl', false) . ' OR ' .
    $DB->sql_like('cs.summary', ':htmlurl', false) . ')';
$params = [
    'rawurl' => '%' . $DB->sql_like_escape($searchurl) . '%',
    'htmlurl' => '%' . $DB->sql_like_escape($htmlsearch) . '%',
    'siteid' => SITEID,
];

$sql = "SELECT cs.id, cs.course, cs.section, cs.name as sectionname, cs.summary, cs.sequence, cs.availability, cs.component,
               c.fullname, c.shortname
          FROM {course_sections} cs
          JOIN {course} c ON cs.course = c.id
         WHERE cs.course <> :siteid AND $matchsql
      ORDER BY c.fullname ASC, cs.section ASC";

$sections = $DB->get_records_sql($sql, $params);

$table = new html_table();
$table->head = [
    get_string('report_course', 'local_lsu_evalfixer'),
    get_string('report_section', 'local_lsu_evalfixer'),
    get_string('report_action', 'local_lsu_evalfixer')
];
$table->data = [];

// To reduce format loading on big sites, cache whether we can delete section in a course.
$courseformatcache = [];

foreach ($sections as $section) {
    $namematches = true;
    if ($sectionnamesearch !== '' && stripos($section->sectionname ?? '', $sectionnamesearch) === false) {
        $namematches = false;
    }

    $qualifies = $section->sequence === '' &&
        ($section->availability === null || $section->availability === $emptyavailability);
    
    $args = new stdClass();
    $args->oldurl = format_string($searchurl);
    $args->newurl = format_string($replacementurl);
    $args->oldname = format_string($section->sectionname ?? '');
    $args->newname = format_string($sectionnameoverride);
    
    if (!$namematches) {
        $proposedaction = get_string('action_update_url_only', 'local_lsu_evalfixer', $args);
    } else {
        if ($onlyempty && !$qualifies) {
            $proposedaction = get_string('action_skipped_empty', 'local_lsu_evalfixer');
        } else {
            $candelete = false;
            if ($action === 'delete' && $qualifies && (int)$section->section > 0 &&
                    empty($section->component) &&
                    !$DB->record_exists('course_modules', ['section' => $section->id])) {
                
                if (!isset($courseformatcache[$section->course])) {
                    $course = $DB->get_record('course', ['id' => $section->course], '*', MUST_EXIST);
                    $courseformatcache[$section->course] = course_get_format($course);
                }
                $candelete = $courseformatcache[$section->course]->can_delete_section($section->section);
            }

            if ($candelete) {
                $proposedaction = get_string('action_will_delete', 'local_lsu_evalfixer');
            } else if ($action === 'delete') {
                // Attempt to delete but it would be refused
                $proposedaction = get_string('action_skipped_format_new', 'local_lsu_evalfixer', $args);
            } else {
                $proposedaction = get_string('action_update_url_name', 'local_lsu_evalfixer', $args);
            }
        }
    }

    $courselink = html_writer::link(new moodle_url('/course/view.php', ['id' => $section->course]), format_string($section->fullname));
    
    // Some sections don't have a name, so display generic text linked to the section
    $sectionnametext = trim($section->sectionname ?? '') !== '' ? format_string($section->sectionname) : 'Section ' . $section->section;
    $sectionlink = html_writer::link(new moodle_url('/course/section.php', ['id' => $section->id]), $sectionnametext);

    $table->data[] = [
        $courselink,
        $sectionlink,
        $proposedaction
    ];
}

if (empty($table->data)) {
    echo $OUTPUT->notification('No sections found matching the current settings.');
} else {
    echo html_writer::table($table);
}

echo $OUTPUT->footer();

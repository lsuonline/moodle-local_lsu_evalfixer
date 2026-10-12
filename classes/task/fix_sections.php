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
 * LSU evaluation fixer.
 * @package local_lsu_evalfixer
 * @copyright 2026 Robert Russo
 * @copyright 2026 Louisiana State University
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_lsu_evalfixer\task;

defined('MOODLE_INTERNAL') || die();

/** Moodle requires a class for scheduled tasks. */
class fix_sections extends \core\task\scheduled_task {
    /** @var string Literal URL to find. */
    private $search;
    /** @var string Literal replacement URL. */
    private $replacement;
    /** @var string Section name override. */
    private $name;
    /** @var string Either replace or delete. */
    private $action;
    /** @var bool Whether this execution is a preview. */
    private $dryrun;
    /** @var bool Whether the exact empty-section filter limits all updates. */
    private $onlyempty;
    /** @var string Section name search filter. */
    private $sectionnamesearch;
    /** @var string Exact permitted non-NULL availability value. */
    private $emptyavailability = '{"op":"&","c":[],"showc":[]}';
    /** @var string Search URL as stored in HTML. */
    private $htmlsearch;
    /** @var string Replacement URL as stored in HTML. */
    private $htmlreplacement;
    /** @var array Raw and HTML replacement pairs used in a single pass. */
    private $replacements;
    /** @var string Regex pattern used to match full URLs containing the search string. */
    private $regexpattern;
    /** @var string Regex pattern used to match HTML-encoded full URLs containing the search string. */
    private $htmlregexpattern;
    /** @var int Errors in the current run. */
    private $errors = 0;
    /** @var int Successfully changed sections in the current run. */
    private $changed = 0;

    /** @return string Task label. */
    public function get_name() {
        return get_string('taskname', 'local_lsu_evalfixer');
    }

    /** Read settings, find courses, then process their sections in order. */
    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/format/lib.php');

        // Read and validate the settings before changing anything.
        $config = get_config('local_lsu_evalfixer');
        if (empty($config->enabled)) {
            mtrace('LSU evaluation fixer is disabled.');
            return;
        }
        $this->search = trim($config->searchurl ?? '');
        // Strip http:// or https:// from search if present.
        $this->search = preg_replace('#^https?://#i', '', $this->search);

        $this->replacement = trim($config->replacementurl ?? '');
        $this->name = trim($config->sectionname ?? '');
        $this->action = $config->action ?? 'replace';
        $this->dryrun = (bool)($config->dryrun ?? true);
        $this->onlyempty = (bool)($config->onlyempty ?? true);
        $this->sectionnamesearch = trim($config->sectionnamesearch ?? '');

        $this->validate_settings();

        // Keep another copy for links stored with &amp; in HTML summaries.
        $this->htmlsearch = htmlspecialchars($this->search, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->htmlreplacement = htmlspecialchars($this->replacement, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->replacements = [$this->search => $this->replacement, $this->htmlsearch => $this->htmlreplacement];

        // Define regex patterns to capture full URLs around the search string.
        // It captures anything that is not a space, quote, or HTML bracket.
        $quotemeta = preg_quote($this->search, '/');
        $this->regexpattern = '/https?:\/\/[^\s"\'<>]*' . $quotemeta . '[^\s"\'<>]*/i';
        $htmlquotemeta = preg_quote($this->htmlsearch, '/');
        $this->htmlregexpattern = '/https?:\/\/[^\s"\'<>]*' . $htmlquotemeta . '[^\s"\'<>]*/i';

        $matchsql = '(' . $DB->sql_like('summary', ':rawurl', true) . ' OR ' .
            $DB->sql_like('summary', ':htmlurl', true) . ')';
        $params = [
            'rawurl' => '%' . $DB->sql_like_escape($this->search) . '%',
            'htmlurl' => '%' . $DB->sql_like_escape($this->htmlsearch) . '%',
            'siteid' => SITEID,
        ];

        // Use Moodle's lock API to prevent overlapping runs of this plugin.
        $factory = \core\lock\lock_config::get_lock_factory('local_lsu_evalfixer');
        $lock = $factory->get_lock('process_sections', 0);
        if (!$lock) {
            mtrace('Another evaluation fixer run is active.');
            return;
        }
        $this->errors = 0;
        $this->changed = 0;
        try {
            // Only IDs are held here, not all section summaries.
            $courses = $DB->get_records_sql('SELECT DISTINCT course FROM {course_sections}
                WHERE course <> :siteid AND ' . $matchsql . ' ORDER BY course', $params);

            // Finish one course before moving to the next.
            foreach ($courses as $candidate) {
                $this->process_course((int)$candidate->course);
            }
        } finally {
            $lock->release();
        }
        mtrace('Finished. Sections changed: ' . $this->changed . '; errors: ' . $this->errors . '.');
        if ($this->errors > 0) {
            throw new \moodle_exception('runfailed', 'local_lsu_evalfixer', '', $this->errors);
        }
    }

    /**
     * Reject incomplete settings, unsafe URL schemes and replacements that would
     * grow on every run. Both actions need a name and replacement for the fallback.
     *
     * @throws \moodle_exception Before any course is modified.
     */
    private function validate_settings(): void {
        // Validate search URL (should not be empty, no spaces or special chars).
        if ($this->search === '' || preg_match('/[\x00-\x20<>"\']/', $this->search)) {
            throw new \moodle_exception('invalidsettings', 'local_lsu_evalfixer', '',
                'Search URL must not be empty and cannot contain spaces or quotes.');
        }

        // Validate replacement URL (must be valid absolute HTTP(S) URL).
        $scheme = strtolower((string)parse_url($this->replacement, PHP_URL_SCHEME));
        if (!filter_var($this->replacement, FILTER_VALIDATE_URL) ||
                ($scheme !== 'http' && $scheme !== 'https') || preg_match('/[\x00-\x20<>"\']/', $this->replacement)) {
            throw new \moodle_exception('invalidsettings', 'local_lsu_evalfixer', '',
                'Replacement URL must be a valid absolute HTTP(S) URL.');
        }

        if (strpos($this->replacement, $this->search) !== false) {
            throw new \moodle_exception('invalidsettings', 'local_lsu_evalfixer', '',
                'Replacement URL must not equal or contain the search URL.');
        }
        if ($this->name === '' || \core_text::strlen($this->name) > 255 || strip_tags($this->name) !== $this->name) {
            throw new \moodle_exception('invalidsettings', 'local_lsu_evalfixer', '',
                'Name must be nonempty plain text, at most 255 characters.');
        }
        if ($this->action !== 'replace' && $this->action !== 'delete') {
            throw new \moodle_exception('invalidsettings', 'local_lsu_evalfixer', '', 'Unknown action.');
        }

    }

    /**
     * Update matching sections in one course and always clear its caches afterward.
     * A section failure is logged; later sections still run. Counts are accumulated
     * for the final task status. Dry run skips all writes and explicit cache clearing.
     *
     * @param int $courseid Course to process.
     */
    private function process_course(int $courseid): void {
        global $DB;
        try {
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (!$course) {
                return;
            }
            $ids = $DB->get_records('course_sections', ['course' => $courseid], 'section DESC', 'id');
            foreach ($ids as $id) {
                try {
                    $section = $DB->get_record('course_sections', ['id' => $id->id, 'course' => $courseid]);
                    if (!$section || (strpos($section->summary ?? '', $this->search) === false &&
                            strpos($section->summary ?? '', $this->htmlsearch) === false)) {
                        continue;
                    }

                    $namematches = true;
                    if ($this->sectionnamesearch !== '' && stripos($section->name ?? '', $this->sectionnamesearch) === false) {
                        $namematches = false;
                    }

                    $qualifies = $section->sequence === '' &&
                        ($section->availability === null || $section->availability === $this->emptyavailability);

                    // Find all full URLs that will be replaced for logging purposes.
                    $foundurls = [];
                    if (preg_match_all($this->regexpattern, $section->summary ?? '', $matches)) {
                        $foundurls = array_merge($foundurls, $matches[0]);
                    }
                    if (preg_match_all($this->htmlregexpattern, $section->summary ?? '', $matches)) {
                        $foundurls = array_merge($foundurls, $matches[0]);
                    }
                    $foundurls = array_unique($foundurls);
                    $urlstext = !empty($foundurls) ? implode(', ', $foundurls) : $this->search;

                    $label = 'Course ' . $courseid . ', section ID ' . $section->id;

                    // A deletion attempt must pass every possible safeguard.
                    $candelete = false;
                    // Deletion only happens if name matches AND qualifications are met.
                    if ($namematches && $this->action === 'delete' && $qualifies && (int)$section->section > 0 &&
                            empty($section->component) &&
                            !$DB->record_exists('course_modules', ['section' => $section->id])) {
                        $candelete = course_get_format($course)->can_delete_section($section->section);
                    }
                    if ($this->dryrun) {
                        if ($candelete) {
                            mtrace($label . ': WOULD TRY NON-FORCED DELETE; UPDATE IF REFUSED.');
                        } else if ($namematches) {
                            mtrace($label . ': WOULD UPDATE URL from ' . $urlstext . ' to ' . $this->replacement . ' AND NAME from ' . ($section->name ?? 'default') . ' to ' . $this->name . '.');
                        } else {
                            mtrace($label . ': WOULD UPDATE URL ONLY from ' . $urlstext . ' to ' . $this->replacement . '.');
                        }
                        continue;
                    }
                    if ($candelete) {
                        rebuild_course_cache($courseid, true);
                        try {
                            // Never clear the summary to make deletion possible. Never force deletion.
                            $deleted = course_delete_section($course, $section, false, false);
                        } catch (\Throwable $e) {
                            $deleted = false;
                            mtrace($label . ': deletion failed; trying update: ' . $e->getMessage());
                        }
                        if ($deleted) {
                            $this->changed++;
                            mtrace($label . ': DELETED.');
                            continue;
                        }
                        mtrace($label . ': deletion refused; trying update.');
                    }

                    // Update directly, or fall back here after a refused deletion. Re-read so the fallback never uses a pre-deletion snapshot.
                    $section = $DB->get_record('course_sections', ['id' => $id->id, 'course' => $courseid]);
                    if (!$section || (strpos($section->summary ?? '', $this->search) === false &&
                            strpos($section->summary ?? '', $this->htmlsearch) === false)) {
                        continue;
                    }

                    $foundurls = [];
                    if (preg_match_all($this->regexpattern, $section->summary ?? '', $matches)) {
                        $foundurls = array_merge($foundurls, $matches[0]);
                    }
                    if (preg_match_all($this->htmlregexpattern, $section->summary ?? '', $matches)) {
                        $foundurls = array_merge($foundurls, $matches[0]);
                    }
                    $foundurls = array_unique($foundurls);
                    $urlstext = !empty($foundurls) ? implode(', ', $foundurls) : $this->search;

                    $namematches = true;
                    if ($this->sectionnamesearch !== '' && stripos($section->name ?? '', $this->sectionnamesearch) === false) {
                        $namematches = false;
                    }

                    // Uses preg_replace to replace the full extracted URL with the new URL
                    $summary = $section->summary ?? '';
                    $summary = preg_replace($this->regexpattern, $this->replacement, $summary);
                    $summary = preg_replace($this->htmlregexpattern, $this->htmlreplacement, $summary);

                    $updatename = $namematches ? $this->name : $section->name;

                    if ($summary === $section->summary && $updatename === $section->name) {
                        continue;
                    }
                    rebuild_course_cache($courseid, true);
                    course_update_section($course, $section, ['summary' => $summary, 'name' => $updatename]);
                    $this->changed++;
                    if ($namematches) {
                        mtrace($label . ': UPDATED URL from ' . $urlstext . ' to ' . $this->replacement . ' AND NAME from ' . ($section->name ?? 'default') . ' to ' . $this->name . '.');
                    } else {
                        mtrace($label . ': UPDATED URL ONLY from ' . $urlstext . ' to ' . $this->replacement . '.');
                    }
                } catch (\Throwable $e) {
                    $this->errors++;
                    mtrace('Course ' . $courseid . ', section ID ' . $id->id . ': ERROR: ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            $this->errors++;
            mtrace('Course ' . $courseid . ': ERROR: ' . $e->getMessage());
        } finally {
            // Clear this course's caches even if a section failed.
            if (!$this->dryrun) {
                try {
                    rebuild_course_cache($courseid, true);
                    mtrace('Course ' . $courseid . ': caches cleared.');
                } catch (\Throwable $e) {
                    $this->errors++;
                    mtrace('Course ' . $courseid . ': CACHE ERROR: ' . $e->getMessage());
                }
            }
        }
    }
}

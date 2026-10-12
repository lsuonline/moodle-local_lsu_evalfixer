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
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LSU course evaluations';
$string['enabled'] = 'Enable scheduled processing';
$string['enabled_desc'] = 'Allow the scheduled task to run. Disabled by default.';
$string['dryrun'] = 'Dry run';
$string['dryrun_desc'] = 'Report proposed actions without changing sections or clearing course caches.';
$string['searchurl'] = 'URL to find';
$string['searchurl_desc'] = 'Required absolute HTTP(S) URL. Case-sensitive literal substring. Enter the original URL, not HTML encoding. Normal HTML-escaped matches are also recognised.';
$string['replacementurl'] = 'Replacement URL';
$string['replacementurl_desc'] = 'Required in both modes. Absolute HTTP(S) URL that must not equal or contain the URL to find.';
$string['redirectdelay'] = 'Redirect Delay';
$string['redirectdelay_desc'] = 'Delay in seconds before redirecting. Maximum 15 seconds. Default 0.';
$string['sectionname'] = 'Section name override';
$string['sectionname_desc'] = 'Required in both modes. Plain text, at most 255 characters. Applied to every matching section that is updated.';
$string['onlyempty'] = 'Only target empty, unrestricted sections';
$string['onlyempty_desc'] = 'Require sequence to be exactly empty and availability to be NULL or exactly {"op":"&","c":[],"showc":[]}. Other strings or equivalent JSON do not qualify. When enabled this restricts deletion attempts. Deletion always requires these conditions.';
$string['sectionnamesearch'] = 'Section name search filter';
$string['sectionnamesearch_desc'] = 'If specified, only sections whose current name contains this text will be affected. Leave empty to evaluate all sections.';
$string['action'] = 'Action';
$string['action_desc'] = 'Deletion is never forced. If a targeted section cannot be deleted, update its URL and name instead. Sections outside the enabled filter are untouched.';
$string['replace'] = 'Replace URL and rename';
$string['delete'] = 'Try deletion; otherwise replace URL and rename';
$string['taskname'] = 'Fix LSU evaluation course sections';
$string['privacy:metadata'] = 'This plugin stores only site configuration and no separate personal data.';
$string['runfailed'] = 'Section maintenance encountered {$a} error(s). See the task log.';
$string['invalidsettings'] = 'Invalid evaluation fixer settings: {$a}';
$string['reportlink_heading'] = 'Evaluation Fixer Report';
$string['reportlink_desc'] = '<a href="{$a}">View report of affected sections</a> (Based on currently saved settings)';
$string['report_title'] = 'Evaluation Fixer Effect Report';
$string['report_course'] = 'Course';
$string['report_section'] = 'Section';
$string['report_action'] = 'Proposed Action';
$string['action_will_delete'] = 'Will delete';
$string['action_will_update'] = 'Will update URL and name';
$string['action_update_url_only'] = 'Will update URL from "{$a->oldurl}" to "{$a->newurl}"';
$string['action_update_url_name'] = 'Will update URL from "{$a->oldurl}" to "{$a->newurl}" and name from "{$a->oldname}" to "{$a->newname}"';
$string['action_skipped_empty'] = 'Skipped (Does not meet empty/unrestricted requirement)';
$string['action_skipped_empty_new'] = 'Will update URL from "{$a->oldurl}" to "{$a->newurl}" and name from "{$a->oldname}" to "{$a->newname}" (Does not meet empty/unrestricted requirement for deletion)';
$string['action_skipped_format'] = 'Will update URL and name (Deletion refused by course format/restrictions)';
$string['action_skipped_format_new'] = 'Will update URL from "{$a->oldurl}" to "{$a->newurl}" and name from "{$a->oldname}" to "{$a->newname}" (Deletion refused by course format/restrictions)';

$string['evaltooldeprecated']       = 'The legacy course evaluation tool is no longer in use. It has been retired and replaced by a newer, more robust evaluation system.';
$string['newtool']                  = 'New Evaluation Tool';
$string['newtoollink']              = 'Visit the new LSU Evaluation Tool';
$string['newtoolurl']               = 'https://thenewcourseevaltool.com';
$string['contactadmin']             = 'If you have questions, please contact your site administrator.';

$string['missingreplacementurl']    = 'The site administrator has not set the replacement URL. Please contact them.';
$string['redirectingtonewtool']     = 'You are being redirected to the new course evaluation tool at {$a}.';
$string['redirecting_in']           = 'You will be redirected in <span id="redirect-countdown">{$a->time}</span> seconds. <br> Destination: <a href="{$a->url}">{$a->url}</a>';

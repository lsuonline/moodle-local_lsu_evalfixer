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

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_lsu_evalfixer', get_string('pluginname', 'local_lsu_evalfixer'));
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configcheckbox(
            'local_lsu_evalfixer/enabled', get_string('enabled', 'local_lsu_evalfixer'),
            get_string('enabled_desc', 'local_lsu_evalfixer'), 0));

        $settings->add(new admin_setting_configcheckbox(
            'local_lsu_evalfixer/dryrun', get_string('dryrun', 'local_lsu_evalfixer'),
            get_string('dryrun_desc', 'local_lsu_evalfixer'), 1));

        $settings->add(new admin_setting_configtext(
            'local_lsu_evalfixer/searchurl', get_string('searchurl', 'local_lsu_evalfixer'),
            get_string('searchurl_desc', 'local_lsu_evalfixer'), '', PARAM_RAW_TRIMMED));

        $settings->add(new admin_setting_configtext(
            'local_lsu_evalfixer/replacementurl', get_string('replacementurl', 'local_lsu_evalfixer'),
            get_string('replacementurl_desc', 'local_lsu_evalfixer'), '', PARAM_RAW_TRIMMED));

        $settings->add(new admin_setting_configtext(
            'local_lsu_evalfixer/sectionname', get_string('sectionname', 'local_lsu_evalfixer'),
            get_string('sectionname_desc', 'local_lsu_evalfixer'), '', PARAM_RAW_TRIMMED));

        $settings->add(new admin_setting_configcheckbox(
            'local_lsu_evalfixer/onlyempty', get_string('onlyempty', 'local_lsu_evalfixer'),
            get_string('onlyempty_desc', 'local_lsu_evalfixer'), 1));

        $settings->add(new admin_setting_configselect(
            'local_lsu_evalfixer/action', get_string('action', 'local_lsu_evalfixer'),
            get_string('action_desc', 'local_lsu_evalfixer'), 'replace', [
                'replace' => get_string('replace', 'local_lsu_evalfixer'),
                'delete' => get_string('delete', 'local_lsu_evalfixer'),
            ]));
    }
}

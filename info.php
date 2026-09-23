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

require_once(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Get the new url.
$newurl = get_string('newtoolurl', 'local_lsu_evalfixer');

admin_externalpage_setup('local_lsu_evalfixer');   // matches the page identifier in version.php

$PAGE->set_url(new moodle_url('/admin/tool/lsu_evalfixer/info.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_lsu_evalfixer'));
$PAGE->set_heading(get_string('pluginname', 'local_lsu_evalfixer'));

echo $OUTPUT->header();
?>
<div class="tool-lsu-evalfixer-info" style="max-width: 800px; margin: 2rem auto;">
    <!-- LSU branding (replace colors if your brand palette changes) -->
    <div class="lsu-header" style="
        background:#003366;            /* LSU primary blue */
        color:#fff;
        padding:1rem 1.5rem;
        border-radius:6px;
        margin-bottom:1.5rem;
        text-align:center;
        font-size:1.4rem;
        font-weight:600;
    ">
        <?php echo get_string('pluginname', 'local_lsu_evalfixer'); ?>
    </div>

    <!-- Main content -->
    <div class="card" style="border:1px solid #e0e0e0; border-radius:6px;">
        <div class="card-body" style="padding:1.5rem;">
            <p style="font-size:1.1rem; line-height:1.6;">
                <?php echo get_string('evaltooldeprecated', 'local_lsu_evalfixer'); ?>
            </p>

            <hr style="margin:1.5rem 0;">

            <p>
                <strong><?php echo get_string('newtool', 'local_lsu_evalfixer'); ?>:</strong>
                <a href="<?php echo new moodle_url($newurl); ?>">
                    <?php echo get_string('newtoollink', 'local_lsu_evalfixer'); ?>
                </a>
            </p>

            <p>
                <?php echo get_string('contactadmin', 'local_lsu_evalfixer'); ?>
            </p>
        </div>
    </div>
</div>
<?php
echo $OUTPUT->footer();

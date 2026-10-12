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
 * Redirect script for LSU evaluation fixer.
 *
 * @package    local_lsu_evalfixer
 * @copyright  2026 Robert Russo
 * @copyright  2026 Louisiana State University
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Ensure the user is logged in.
require_login();

// Retrieve the replacement URL from the plugin configuration.
$replacementurl = get_config('local_lsu_evalfixer', 'replacementurl');

// Retrieve the redirect delay, ensure it is between 0 and 15 seconds.
$redirectdelay = get_config('local_lsu_evalfixer', 'redirectdelay');
$delay = max(0, min(15, (int)$redirectdelay));

// Validate the replacement URL.
if (empty($replacementurl) || !filter_var($replacementurl, FILTER_VALIDATE_URL)) {
    $targeturl = new moodle_url('/');
    $message = get_string('missingreplacementurl', 'local_lsu_evalfixer');
    $notiftype = \core\output\notification::NOTIFY_ERROR;
} else {
    $targeturl = new moodle_url($replacementurl);
    $message = get_string('redirectingtonewtool', 'local_lsu_evalfixer', $replacementurl);
    $notiftype = \core\output\notification::NOTIFY_INFO;
}

if ($delay == 0) {
    // For 0 delay, use Moodle's built-in immediate redirect.
    redirect($targeturl, $message, null, $notiftype);
}

// For delays > 0, output a page with a countdown.
$PAGE->set_url(new moodle_url('/local/lsu_evalfixer/redirect.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_lsu_evalfixer'));
$PAGE->set_heading(get_string('pluginname', 'local_lsu_evalfixer'));

// Add meta refresh fallback.
$meta = '<meta http-equiv="refresh" content="' . $delay . ';url=' . $targeturl->out() . '">';
$PAGE->requires->data_for_js('redirectdata', [
    'url' => $targeturl->out(false),
    'delay' => $delay
]);
$PAGE->requires->js_amd_inline("
    require(['jquery'], function($) {
        var data = redirectdata;
        var countdown = data.delay;
        var url = data.url;
        var timer = setInterval(function() {
            countdown--;
            $('#redirect-countdown').text(countdown);
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = url;
            }
        }, 1000);
    });
");

echo $OUTPUT->header();
// Include the meta refresh tag as a robust fallback.
echo $meta;
echo $OUTPUT->notification($message, $notiftype);

$a = new stdClass();
$a->time = $delay;
$a->url = $targeturl->out();
echo \html_writer::tag('div', get_string('redirecting_in', 'local_lsu_evalfixer', $a), ['class' => 'mt-3 mb-3 text-center']);

echo $OUTPUT->footer();

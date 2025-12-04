<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * class for site to manually anonymize users. No logic
 *
 * @package     tool_user_anonymizer
 * @copyright   2025 Ramona Rommel <ramona.rommel@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_user_anonymizer\anonymizer;

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url(new moodle_url('/tool/user_anonymizer/trigger_anonymization.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('anonymize_now', 'tool_user_anonymizer'));
$PAGE->set_heading(get_string('anonymize_now', 'tool_user_anonymizer'));

$anonymize = $USER->id;
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);

$returnurl = new moodle_url('/admin/tool/user_anonymizer/trigger_anonymization.php');

if ($confirm != md5($anonymize)) {
    echo $OUTPUT->header();

    $confirmurl = new moodle_url('/admin/tool/user_anonymizer/trigger_anonymization.php', [
        'confirm' => md5($anonymize),
        'anonymize' => $anonymize,
        'sesskey' => sesskey(),
    ]);

    $denyurl = new moodle_url('/admin/search.php');
    $denyurl->set_anchor('linkmodules');

    echo $OUTPUT->confirm(
        get_string('anonymization_confirm_text', 'tool_user_anonymizer'),
        new single_button($confirmurl, get_string('yes')),
        $denyurl
    );

    echo $OUTPUT->footer();
    die;
}
echo $OUTPUT->header();
echo $OUTPUT->notification(get_string('anonymization_running', 'tool_user_anonymizer'), 'notifymessage');

if (!confirm_sesskey()) {
    throw new moodle_exception('invalidsesskey');
}

try {
    anonymizer::manual_anonymization();
    echo $OUTPUT->notification(get_string('anonymization_done', 'tool_user_anonymizer'), 'notifysuccess');
} catch (Exception $e) {
    echo $OUTPUT->notification("Fehler bei der Anonymisierung: " . $e->getMessage(), 'notifyproblem');
}

echo $OUTPUT->footer();

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
 * Plugin strings are defined here.
 *
 * @package     tool_deleted_user_anonymizer
 * @category    string
 * @copyright   2025 oncampus GmbH <support@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['anonymization_confirm_text'] = 'Are you sure you want to anonymize the data of all deleted users? This action cannot be undone.';
$string['anonymization_done'] = 'Anonymization of deleted users will be performed during the next scheduled run.';
$string['anonymization_running'] = 'Anonymization has been scheduled.';
$string['anonymize_manually_desc'] = 'Manual anonymization of deleted user accounts';
$string['anonymize_now'] = 'Immediate anonymization of all previously deleted users';
$string['anonymize_warning'] = 'Clicking the button will immediately anonymize already deleted users. Please proceed with caution!';
$string['delay'] = 'Delay';
$string['delay_desc'] = 'Days between deletion and anonymization of user data';
$string['event_anonymization_triggered'] = 'Anonymization triggered.';
$string['plugin_setting'] = 'Delay for anonymizing user data';
$string['pluginname'] = 'Anonymization of user data after deletion';
$string['privacy:metadata'] = 'This plugin only uses data of deleted users to anonymize them.';
$string['scheduled_anonymization'] = 'Scheduled anonymization';
$string['start_anonymization'] = 'Start anonymization';

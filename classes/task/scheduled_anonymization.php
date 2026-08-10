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

namespace tool_deleted_user_anonymizer\task;

use coding_exception;
use core\task\scheduled_task;
use dml_exception;
use dml_missing_record_exception;
use tool_deleted_user_anonymizer\anonymizer;
use moodle_exception;
use stdClass;
use tool_deleted_user_anonymizer\event\anonymization_triggered;

/**
 * Returns the name of the scheduled task.
 *
 * This string is shown in the admin interface under
 * "Scheduled tasks".
 *
 * @package    tool_deleted_user_anonymizer
 * @copyright  2025 Ramona Rommel <ramona.rommel@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduled_anonymization extends scheduled_task {
    /**
     * Executes the scheduled task.
     *
     * Instantiates the anonymizer and calls the cron logic.
     *
     * @return string Localized name of the task.
     * @throws moodle_exception If something goes wrong during anonymization.
     */
    public function get_name(): string {
        return get_string('scheduled_anonymization', 'tool_deleted_user_anonymizer');
    }

    /**
     * Anonymizes all users that are scheduled for anonymization or already deleted.
     *
     * Retrieves users from the anonymization table whose anonymization date has passed.
     * Each user will be anonymized renamed, and updated in the database.
     * Entries will afterward be deleted from the helper table.
     *
     * @return void
     * @throws coding_exception If Moodle core functions fail
     * @throws dml_exception If a DB query fails
     * @throws moodle_exception If a user is missing or invalid
     * @throws dml_missing_record_exception If user doesn't exist
     */
    public function execute(): void {
        global $DB;

        // Get all user data from tool_users_anonymize database-table.
        $entries = $DB->get_records_select(
            'tool_deleted_user_anonymizer',
            'anonymizedate <= :now',
            ['now' => time()]
        );

        foreach ($entries as $entry) {
            try {
                $user = $DB->get_record('user', ['id' => $entry->userid, 'deleted' => 1], '*', MUST_EXIST);
            } catch (dml_missing_record_exception $e) {
                mtrace("User with ID {$entry->userid} not found or not deleted.");
                continue;
            }

            $userandomname = get_config('tool_deleted_user_anonymizer', 'userandomname');
            $time = time();

            if ($userandomname) {
                // Having identical usernames, even if deleted, is still a big nono so make sure we don't have that problem.
                do {
                    $name = anonymizer::get_random_adjective();
                    $lastname = anonymizer::get_random_animal();
                    $username = $name . $lastname . $time;
                } while ($DB->record_exists('user', ['username' => $username]));
            } else {
                $name = get_string('default_firstname', 'tool_deleted_user_anonymizer');
                $lastname = get_string('default_lastname', 'tool_deleted_user_anonymizer');
                $username = 'anonymized_user_' . $user->id;
            }

            $record = new stdClass();
            $record->id = $user->id;
            $record->firstname = $name;
            $record->lastname = $lastname;
            $record->username = $username;
            $record->phone1 = '';
            $record->phone2 = '';
            $record->institution = '';
            $record->department = '';
            $record->address = '';
            $record->city = '';
            $record->state = '';
            $record->picture = 0;
            $record->description = '';
            $record->lastnamephonetic = '';
            $record->firstnamephonetic = '';
            $record->middlename = '';
            $record->alternatename = '';
            $record->moodlenetprofile = '';
            $record->timemodified = $time;

            $DB->update_record('user', $record);

            $event = anonymization_triggered::create([
                'context' => \context_system::instance(),
                'userid' => $user->id,
                'objectid' => $user->id,
            ]);
            $event->trigger();

            // Remove Entry after anonymization.
            $DB->delete_records('tool_deleted_user_anonymizer', ['userid' => $user->id]);
        }
    }
}

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

namespace tool_user_anonymizer;

use context_system;
use core\event\user_deleted;
use dml_missing_record_exception;
use tool_user_anonymizer\event\anonymization_triggered;
use moodle_exception;
use coding_exception;
use dml_exception;

/**
 * Handles user anonymization logic.
 *
 * @package    tool_user_anonymizer
 * @copyright  2025 Ramona Rommel <ramona.rommel@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class anonymizer {
    /**
     * Returns a random adjective from a JSON word list.
     *
     * Reads a local JSON file containing adjectives, decodes it,
     * and returns one randomly selected adjective.
     *
     * @return string Random adjective from list
     * @throws moodle_exception If file is missing or list is empty
     */
    public static function get_random_adjective(): string {
        $path = __DIR__ . '/data/adjectives.json';
        if (!is_readable($path)) {
            throw new moodle_exception('missingfile', 'error', '', null, 'Missing adjectives.json');
        }

        $json = file_get_contents($path);
        $list = json_decode($json, true);

        if (empty($list)) {
            throw new moodle_exception('empty List. No adjectives found.');
        }

        return $list[array_rand($list)];
    }

    /**
     * Returns a random animal from a JSON word list.
     *
     * Reads a local JSON file containing animals, decodes it,
     * and returns one randomly selected animal.
     *
     * @return string Random adjective from list
     * @throws moodle_exception If file is missing or list is empty
     */
    public static function get_random_animal(): string {
        $path = __DIR__ . '/data/animals.json';
        if (!is_readable($path)) {
            throw new moodle_exception('missingfile', 'error', '', null, 'Missing animals.json');
        }

        $json = file_get_contents($path);
        $list = json_decode($json, true);

        if (empty($list)) {
            throw new moodle_exception('empty List. No animals found.');
        }
        return $list[array_rand($list)];
    }

    /**
     * Manually triggers the anonymization process for a given user ID.
     *
     * Creates and fires the 'anonymization_triggered' event for the specified user,
     * and then schedules users for anonymization.
     *
     * @param int $idtriggered The ID of the user for whom anonymization is triggered.
     * @throws coding_exception If the event cannot be created or triggered properly.
     * @throws dml_exception If a database error occurs while scheduling users.
     */
    public static function manual_anonymization(): void {
        global $DB;

        $deletedusers = $DB->get_records('user', ['deleted' => 1]);
        $anonymizedate = time();

        foreach ($deletedusers as $user) {
            $userid = $user->id;

            $event = anonymization_triggered::create([
                'context' => \context_system::instance(),
                'userid' => $userid,
                'objectid' => $userid,
            ]);
            $event->trigger();

            $record = (object)[
                'userid' => $userid,
                'anonymizedate' => $anonymizedate,
            ];
            $DB->insert_record('tool_user_anonymizer', $record);
        }
    }

    /**
     * Handles user_deleted event to schedule user anonymization.
     *
     * This method is triggered automatically when a user is deleted in Moodle.
     * It checks if the user is actually marked as deleted and schedules
     * the anonymization process based on the configured delay.
     *
     * @param user_deleted $event The user_deleted event object.
     * @throws dml_exception If database operations fail.
     */
    public static function anonymize_deleted_user(user_deleted $event): void {
        global $DB;

        try {
            $user = $DB->get_record('user', ['id' => $event->objectid, 'deleted' => 1], '*', MUST_EXIST);
        } catch (dml_missing_record_exception $e) {
            mtrace("User with ID {$event->objectid} not found or not deleted.");
            return;
        }

        $delaydays = get_config('tool_user_anonymizer', 'delay');
        $delaydays = $delaydays !== false ? (int)$delaydays : 0;
        $anonymizedate = strtotime("+$delaydays days");

        $record = (object)[
            'userid' => $user->id,
            'anonymizedate' => $anonymizedate,
        ];

        $DB->insert_record('tool_user_anonymizer', $record);
    }
}

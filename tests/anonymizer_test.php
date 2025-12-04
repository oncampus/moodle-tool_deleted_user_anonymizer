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

use advanced_testcase;
use coding_exception;
use context_system;
use dml_exception;
use Exception;
use tool_user_anonymizer\event\anonymization_triggered;
use tool_user_anonymizer\task\scheduled_anonymization;
use moodle_exception;

/**
 * Tests for all functions related to anonymizer.
 *
 * @package   tool_user_anonymizer
 * @copyright 2025 Ramona Rommel <ramona.rommel@oncampus.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class anonymizer_test extends advanced_testcase {
    /**
     * Tests whether the anonymization_triggered event is correctly triggered and logged.
     *
     * @covers \tool_user_anonymizer\event\anonymization_triggered
     *
     * @throws coding_exception If user creation or event triggering fails internally.
     * @throws dml_exception If database error occurs.
     */
    public function test_anonymization_triggered(): void {
        global $USER;

        $this->resetAfterTest();

        // Test-User erstellen.
        $this->setUser($this->getDataGenerator()->create_user());

        // Event auslösen.
        $event = anonymization_triggered::create([
            'objectid' => $USER->id,
            'userid' => $USER->id,
            'context' => context_system::instance(),
        ]);
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();

        $this->assertCount(1, $events);
        $triggered = $events[0];

        $this->assertInstanceOf(anonymization_triggered::class, $triggered);
        $this->assertEquals($USER->id, $triggered->userid);
        $this->assertEquals('user', $triggered->objecttable);
        $this->assertEquals('c', $triggered->crud);
    }

    /**
     * Creates test data JSON files for adjectives and animals used in anonymization.
     *
     * This method generates `adjectives.json` and `animals.json` in the plugin's
     * data directory, containing sample values for testing purposes.
     *
     * @covers anonymizer::get_random_adjective
     * @covers anonymizer::get_random_animal
     *
     * @throws Exception If file creation or writing fails.
     */
    private function create_test_data_json(): void {
        // Testdaten für Adjektive.
        $adjectives = ['Happy', 'Blue', 'Crazy'];
        $animals = ['Tiger', 'Penguin', 'Elephant'];

        $datadir = __DIR__ . '/../classes/tests/data';
        if (!is_dir($datadir)) {
            mkdir($datadir, 0777, true);
        }

        file_put_contents($datadir . '/adjectives.json', json_encode($adjectives));
        file_put_contents($datadir . '/animals.json', json_encode($animals));
    }

    /**
     * Runs the anonymization process for all users whose anonymizedate is due.
     *
     * Retrieves entries from the tool_user_anonymizer table where the
     * anonymizedate is not null and has passed, fetches the corresponding
     * deleted users, anonymizes them by replacing personal data, and removes
     * the anonymizer entry after completion.
     *
     * @covers \tool_user_anonymizer\task\scheduled_anonymization::execute
     *
     * @throws moodle_exception If required data files (adjectives.json or animals.json) are missing or unreadable.
     * @throws Exception If file creation or writing fails.
     */
    public function test_run_user_anonymizer(): void {
        global $DB;

        $this->resetAfterTest();

        // Dummy Daten für adjective/animal.
        $this->create_test_data_json();

        // 1. Gelöschten Nutzer erstellen.
        $user = $this->getDataGenerator()->create_user(['deleted' => 1]);

        // 2. Eintrag in tool_user_anonymizer mit anonymizedate in der Vergangenheit.
        $DB->insert_record('tool_user_anonymizer', (object)[
            'userid' => $user->id,
            'anonymizedate' => time() - 60,
        ]);

        // 3. anonymisierung durchführen.
        $task = new scheduled_anonymization();
        $task->execute();

        // 4. User erneut aus DB holen.
        $anon = $DB->get_record('user', ['id' => $user->id]);

        // 5. Sicherstellen, dass der Name ersetzt wurde.
        $this->assertNotEquals($user->firstname, $anon->firstname);
        $this->assertNotEquals($user->lastname, $anon->lastname);
        $this->assertNotEquals($user->username, $anon->username);

        // 6. Sicherstellen, dass Felder geleert wurden.
        $this->assertEmpty($anon->phone1);
        $this->assertEmpty($anon->city);
        $this->assertEmpty($anon->description);

        // 7. Sicherstellen, dass der Anonymisierungseintrag entfernt wurde.
        $exists = $DB->record_exists('tool_user_anonymizer', ['userid' => $user->id]);
        $this->assertFalse($exists);
    }

    /**
     * Tests if manual anonymization correctly schedules the user for anonymization
     * in table tool_user_anonymization
     *
     * @covers \anonymizer::manual_anonymization
     *
     * @throws dml_exception If database error occurs
     * @throws coding_exception
     */
    public function test_manual_anonymization_adds_user_to_table(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['deleted' => 1]);

        set_config('delay', 3, 'tool_user_anonymizer');

        anonymizer::manual_anonymization();

        $record = $DB->get_record('tool_user_anonymizer', ['userid' => $user->id]);
        $this->assertNotEmpty($record);
        $expected = strtotime('+3 days');
        $this->assertGreaterThanOrEqual($expected - 5, $record->anonymizedate);
        $this->assertLessThanOrEqual($expected + 5, $record->anonymizedate);
    }

    /**
     * Tests that the user_deleted event triggers scheduling for anonymization.
     *
     * @covers \tool_user_anonymizer\anonymizer::anonymize_deleted_user
     *
     * @throws dml_exception If database error occurs.
     * @throws coding_exception
     */
    public function test_user_deleted_event_schedules_user(): void {
        global $DB;

        $this->resetAfterTest();

        // Verzögerung setzen.
        set_config('delay', 2, 'tool_user_anonymizer');

        // User erstellen und sofort löschen.
        $user = $this->getDataGenerator()->create_user();
        delete_user($user);

        $record = $DB->get_record('tool_user_anonymizer', ['userid' => $user->id]);
        $this->assertNotEmpty($record);

        $expected = strtotime('+2 days');
        $this->assertGreaterThanOrEqual($expected - 5, $record->anonymizedate);
        $this->assertLessThanOrEqual($expected + 5, $record->anonymizedate);
    }
}

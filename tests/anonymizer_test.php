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

namespace tool_deleted_user_anonymizer;

use advanced_testcase;
use coding_exception;
use context_system;
use dml_exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use tool_deleted_user_anonymizer\event\anonymization_triggered;
use tool_deleted_user_anonymizer\task\scheduled_anonymization;
use moodle_exception;

/**
 * Tests for all functions related to anonymizer.
 *
 * @package   tool_deleted_user_anonymizer
 * @copyright 2025 Ramona Rommel <ramona.rommel@oncampus.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(anonymization_triggered::class)]
#[CoversMethod(anonymizer::class, 'manual_anonymization')]
#[CoversMethod(anonymizer::class, 'anonymize_deleted_user')]
#[CoversMethod(anonymizer::class, 'get_random_animal')]
#[CoversMethod(anonymizer::class, 'get_random_adjective')]
#[CoversMethod(scheduled_anonymization::class, 'execute')]
final class anonymizer_test extends advanced_testcase {
    /**
     * Tests whether the anonymization_triggered event is correctly triggered and logged.
     *
     * @throws coding_exception If user creation or event triggering fails internally.
     * @throws dml_exception If database error occurs.
     */
    public function test_anonymization_triggered(): void {
        global $USER;

        $this->resetAfterTest();

        // Create test user.
        $this->setUser($this->getDataGenerator()->create_user());

        // Trigger event.
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
     * Tests that the scheduled task anonymizes a deleted user whose anonymizedate is due.
     *
     * Verifies that name and username are replaced, personal fields are cleared,
     * the anonymization_triggered event is fired and the entry in
     * tool_deleted_user_anonymizer is removed afterwards.
     *
     * @throws dml_exception If database error occurs.
     * @throws coding_exception
     * @throws moodle_exception If the word lists (adjectives.json or animals.json) are missing or empty.
     */
    public function test_run_user_anonymizer(): void {
        global $DB;

        $this->resetAfterTest();

        // 1. Create deleted user.
        $user = $this->getDataGenerator()->create_user(
            [
                'deleted' => 1,
                'phone1' => '00012345',
                'city' => 'Luebeck',
                'description' => 'Epic description',
            ]
        );
        // The generator deletes the user via delete_user(), so the observer already scheduled it. Start clean.
        $DB->delete_records('tool_deleted_user_anonymizer', ['userid' => $user->id]);

        // 2. Add entry to tool_deleted_user_anonymizer with anonymizedate in the past.
        $DB->insert_record('tool_deleted_user_anonymizer', (object)[
            'userid' => $user->id,
            'anonymizedate' => time() - 60,
        ]);

        // 3. Run anonymization.
        $sink = $this->redirectEvents();
        $task = new scheduled_anonymization();
        $task->execute();
        $events = array_filter($sink->get_events(), fn($e) => $e instanceof anonymization_triggered);
        $sink->close();

        // 4. Fetch user from DB again.
        $anon = $DB->get_record('user', ['id' => $user->id]);

        // 5. Ensure that the name has been replaced.
        $this->assertNotEquals($user->firstname, $anon->firstname);
        $this->assertNotEquals($user->lastname, $anon->lastname);
        $this->assertNotEquals($user->username, $anon->username);

        // 6. Ensure that fields have been cleared.
        $this->assertEmpty($anon->phone1);
        $this->assertEmpty($anon->city);
        $this->assertEmpty($anon->description);

        // 7. Ensure that the anonymization entry has been removed.
        $exists = $DB->record_exists('tool_deleted_user_anonymizer', ['userid' => $user->id]);
        $this->assertFalse($exists);

        // 8. Ensure that the anonymization_triggered event has been fired for the user.
        $this->assertCount(1, $events);
        $this->assertEquals($user->id, reset($events)->objectid);
    }

    /**
     * Tests if manual anonymization correctly schedules the user for anonymization
     * in table tool_deleted_user_anonymizer and fires the anonymization_triggered event.
     *
     * @throws dml_exception If database error occurs
     * @throws coding_exception
     */
    public function test_manual_anonymization_adds_user_to_table(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['deleted' => 1]);
        // The generator deletes the user via delete_user(), so the observer already scheduled it. Start clean.
        $DB->delete_records('tool_deleted_user_anonymizer', ['userid' => $user->id]);

        $sink = $this->redirectEvents();
        anonymizer::manual_anonymization();
        $events = array_filter($sink->get_events(), fn($e) => $e instanceof anonymization_triggered);
        $sink->close();

        // Manual anonymization takes effect immediately, without the configured delay.
        $record = $DB->get_record('tool_deleted_user_anonymizer', ['userid' => $user->id]);
        $this->assertNotEmpty($record);
        $this->assertEqualsWithDelta(time(), $record->anonymizedate, 5);

        $this->assertCount(1, $events);
        $this->assertEquals($user->id, reset($events)->objectid);
    }

    /**
     * Tests that the user_deleted event triggers scheduling for anonymization.
     *
     * @throws dml_exception If database error occurs.
     * @throws coding_exception
     */
    public function test_user_deleted_event_schedules_user(): void {
        global $DB;

        $this->resetAfterTest();

        // Set delay.
        set_config('delay', 2, 'tool_deleted_user_anonymizer');

        // Create user and delete immediately.
        $user = $this->getDataGenerator()->create_user();
        delete_user($user);

        $record = $DB->get_record('tool_deleted_user_anonymizer', ['userid' => $user->id]);
        $this->assertNotEmpty($record);

        $expected = strtotime('+2 days');
        $this->assertEqualsWithDelta($expected, $record->anonymizedate, 5);
    }
}

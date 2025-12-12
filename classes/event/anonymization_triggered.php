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

namespace tool_deleted_user_anonymizer\event;

use coding_exception;
use core\event\base;
use moodle_url;

/**
 * Event: Anonymization was triggered by a user.
 *
 * @package   tool_deleted_user_anonymizer
 * @copyright 2025 Ramona Rommel <ramona.rommel@oncampus.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class anonymization_triggered extends base {
    /**
     * Initializes internal event data.
     *
     * Sets CRUD operation type, education level, and related table.
     */
    protected function init(): void {
        $this->data['crud']        = 'c';
        $this->data['edulevel']    = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'user';
    }

    /**
     * Returns the localized name of the event.
     *
     * Used in reports and admin interfaces.
     *
     * @return string The event name.
     * @throws coding_exception If the language string is missing or invalid.
     */
    public static function get_name(): string {
        return get_string('event_anonymization_triggered', 'tool_deleted_user_anonymizer');
    }

    /**
     * Returns a description of what happened.
     *
     * @return string Event description.
     * @throws coding_exception
     */
    public function get_description(): string {
        return get_string('anonymization_triggered_desc', 'tool_deleted_user_anonymizer', $this->userid);
    }

    /**
     * Returns the URL related to the event.
     *
     * This is typically used in event reports/logs to link to the origin of the action.
     *
     * @return moodle_url URL to the anonymization script.
     */
    public function get_url(): moodle_url {
        return new moodle_url('/admin/tool/deleted_user_anonymizer/trigger_anonymization.php');
    }
}

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
 * Plugin upgrade steps are defined here.
 *
 * @package     tool_deleted_user_anonymizer
 * @category    upgrade
 * @copyright   2025 Ramona Rommel <ramona.rommel@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * dummy function for future usages
 *
 * @param int $oldversion The version number the plugin is upgrading from.
 * @return bool True on successful upgrade.
 */
function xmldb_tool_deleted_user_anonymizer_upgrade(int $oldversion): bool {
    return true;
}

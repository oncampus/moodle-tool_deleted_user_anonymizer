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

namespace tool_user_anonymizer\privacy;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API for the tool_user_anonymizer plugin.
 * @package     tool_user_anonymizer
 * @copyright   2025 oncampus GmbH <support@oncampus.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class provider
 * Implements the privacy interfaces required for data handling within the plugin.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Returns metadata about this plugin.
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'tool_user_anonymizer',
            [
                'userid' => 'privacy:metadata:tool_user_anonymizer:userid',
                'anonymizedate' => 'privacy:metadata:tool_user_anonymizer:anonymousdate',
            ],
            'privacy:metadata:tool_user_anonymizer'
        );

        return $collection;
    }

    /**
     * Returns a list of contexts that contain user information for the specified user.
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "SELECT c.id
                    FROM {context} c
                        INNER JOIN {user} u ON u.id = c.instanceid
                        INNER JOIN {tool_user_anonymizer} r ON r.userid = u.id
                    WHERE c.contextlevel = :contextlevel AND r.userid = :userid";
        $params = [
            'contextlevel' => CONTEXT_USER,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get all users in the specified context that have data to export.
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {

        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }
        $params = ['userid' => $context->instanceid];

        $sql = "SELECT r.userid
                FROM {tool_user_anonymizer} r
                WHERE r.userid = :userid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_USER) {
                continue;
            }
            $userid = $contextlist->get_user()->id;

            $record = $DB->get_record('tool_user_anonymizer', ['userid' => $userid]);
            if (!$record) {
                continue;
            }
            $recordeduserinfo = (object)[
                'userid' => $record->userid,
                'anonymizedate' => $record->anonymizedate,
            ];

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'tool_user_anonymizer')],
                $recordeduserinfo
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     * @param \context $context
     * @return void
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }

        $userid = $context->instanceid;
        if (!$userid) {
            return;
        }

        $DB->delete_records('tool_user_anonymizer', ['userid' => $userid]);
    }

    /**
     * Delete all data for the specified user, in the specified contexts.
     * @param approved_contextlist $contextlist
     * @return void
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_USER) {
                $DB->delete_records('tool_user_anonymizer', ['userid' => $userid]);
                break;
            }
        }
    }

    /**
     * Deletes data associated with the specified list of approved users.
     * @param approved_userlist $userlist An approved user list containing context and user IDs to delete data for.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        $userids = $userlist->get_userids();
        if (!empty($userids)) {
            [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('tool_user_anonymizer', "userid $insql", $params);
        }
    }
}

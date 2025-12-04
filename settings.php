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
 * Plugin administration pages are defined here.
 *
 * @package     tool_user_anonymizer
 * @copyright   2025 oncampus GmbH <support@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$confirm = optional_param('confirm', '', PARAM_BOOL);
$anonymize = optional_param('anonymize', '', PARAM_BOOL);

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'tool_user_anonymizer',
        new lang_string('plugin_setting', 'tool_user_anonymizer')
    );

    $ADMIN->add('tools', $settings);

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext(
            'tool_user_anonymizer/delay',
            get_string('delay', 'tool_user_anonymizer'),
            get_string('delay_desc', 'tool_user_anonymizer'),
            0,
            PARAM_INT
        ));
    }

    $ADMIN->add('tools', new admin_externalpage(
        'tool_user_anonymizer/anonymize_now',
        get_string('anonymize_now', 'tool_user_anonymizer'),
        new moodle_url('/admin/tool/user_anonymizer/trigger_anonymization.php'),
        'moodle/site:config'
    ));
}

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
 * @copyright   2025 Ramona Rommel <ramona.rommel@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['anonymization_confirm_text'] = 'Bist du sicher, dass du die Daten aller gelöschten Nutzer anonymisieren möchtest? Diese Aktion kann nicht rückgängig gemacht werden.';
$string['anonymization_done'] = 'Anonymisierung gelöschter User wird beim nächsten Lauf durchgeführt.';
$string['anonymization_running'] = 'Anonymisierung wird vorgemerkt.';
$string['anonymization_triggered_desc'] = 'Der Nutzer mit der id \'{$a}\' hat die Anonymisierung aller gelöschten Nutzer ausgelöst.';
$string['anonymize_manually_desc'] = 'Manuelle Anonymisierung gelöschter Nutzerkonten';
$string['anonymize_now'] = 'Sofortige Anonymisierung aller bisher gelöschten User';
$string['anonymize_warning'] = 'Bei Klick auf den Button erfolgt eine sofortige Anonymisierung von bereits gelöschten Nutzer*innen. Bitte mit Bedacht starten!';
$string['delay'] = 'Verzögerung';
$string['delay_desc'] = 'Tage zwischen Löschung und Anonymisierung der Userdaten';
$string['event_anonymization_triggered'] = 'Anonymisierung aller gelöschten Nutzer ausgelöst.';
$string['plugin_setting'] = 'Verzögerung der Anonymisierung von Nutzerdaten';
$string['pluginname'] = 'Anonymisierung von Nutzerdaten nach Löschung';
$string['privacy:metadata'] = 'Dieses Plugin nutzt nur Daten von gelöschten Nutzern, um diese zu anonymisieren.';
$string['scheduled_anonymization'] = 'Geplante Anonymisierung';
$string['start_anonymization'] = 'Starte Anonymisierung';

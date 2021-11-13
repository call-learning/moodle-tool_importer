<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * tool_dataprivacy plugin upgrade code
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function to upgrade tool_importer.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 * @throws ddl_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_tool_importer_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021310300) {

        // Define table tool_importer_logs to be created.
        $table = new xmldb_table('tool_importer_logs');

        // Adding fields to table tool_importer_logs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('linenumber', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('messagecode', XMLDB_TYPE_CHAR, '254', null, null, null, null);
        $table->add_field('module', XMLDB_TYPE_CHAR, '254', null, null, null, null);
        $table->add_field('additionalinfo', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fieldname', XMLDB_TYPE_CHAR, '254', null, null, null, null);
        $table->add_field('level', XMLDB_TYPE_INTEGER, '4', null, null, null, null);
        $table->add_field('origin', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('importid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table tool_importer_logs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for tool_importer_logs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Importer savepoint reached.
        upgrade_plugin_savepoint(true, 2021310300, 'tool', 'importer');
    }
    if ($oldversion < 2022010100) {
        // Define field validationstep to be added to tool_importer_logs.
        $table = new xmldb_table('tool_importer_logs');
        $field = new xmldb_field('validationstep', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'importid');

        // Conditionally launch add field validationstep.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Importer savepoint reached.
        upgrade_plugin_savepoint(true, 2022010100, 'tool', 'importer');
    }
    return true;
}

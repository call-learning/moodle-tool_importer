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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tool_importer\local;

use core\persistent;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class import_log
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_log extends persistent {

    /**
     * Related table
     */
    const TABLE = 'tool_importer_logs';

    /**
     * Create an instance of this class.
     *
     * @param int $id If set, this is the id of an existing record, used to load the data.
     * @param stdClass|null $record If set will be passed to from_record.
     */
    public function __construct($id = 0, stdClass $record = null) {
        if (!empty($record) && !empty($record->type) && is_string($record->type)) {
            $record->level = log_levels::convert_level_name_to_level_number($record->type) ?? log_levels::LEVEL_INFO;
        }
        parent::__construct($id, $record);
    }

    /**
     * Usual properties definition for a persistent
     *
     * @return array|array[]
     * @throws \coding_exception
     */
    protected static function define_properties() {
        return array(
            'linenumber' => array(
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ),
            'messagecode' => array(
                'type' => PARAM_TEXT,
                'null' => NULL_NOT_ALLOWED,
            ),
            'module' => array(
                'type' => PARAM_TEXT,
            ),
            'additionalinfo' => array(
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED
            ),
            'fieldname' => array(
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED
            ),
            'level' => array(
                'type' => PARAM_INT,
                'default' => log_levels::LEVEL_WARNING,
                'choices' => array(
                    log_levels::LEVEL_INFO,
                    log_levels::LEVEL_WARNING,
                    log_levels::LEVEL_ERROR,
                ),
                'format' => [
                    'choices' => [
                        log_levels::LEVEL_INFO => get_string('log:level:info', 'tool_importer'),
                        log_levels::LEVEL_WARNING => get_string('log:level:warning', 'tool_importer'),
                        log_levels::LEVEL_ERROR => get_string('log:level:error', 'tool_importer'),
                    ]
                ]
            ),
            'origin' => array(
                'type' => PARAM_TEXT,
            ),
            'importid' => array(
                'type' => PARAM_INT,
            ),
            'validationstep' => array(
                'type' => PARAM_BOOL,
                'default' => false,
                'null' => NULL_ALLOWED
            ),
        );
    }

    /**
     * Get message (human readable)
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_full_message() {
        $record = $this->to_record();
        $json = json_encode($this->get('additionalinfo'));
        return "$record->messagecode ({$record->level}: line {$record->linenumber}
        {$record->fieldname} - $json";
    }

    /**
     * From generic exception
     *
     * @param \moodle_exception $e
     * @param array $overrides contains at least the values for importid, level and module
     * @return import_log
     */
    public static function from_exception(\moodle_exception $e, array $overrides) {
        $importloginfo = new stdClass();
        $importloginfo->messagecode = $e->errorcode;
        $importloginfo->origin = $overrides['origin'] ?? 'unknown';
        $importloginfo->module = $e->module ?? ($overrides['module'] ?? 'tool_importer');
        $importloginfo->level = $e->level ?? ($overrides['level'] ?? log_levels::LEVEL_ERROR);
        $importloginfo->linenumber = $e->linenumber ?? ($overrides['linenumber'] ?? 0);
        $importloginfo->fieldname = $e->fieldname ?? '';
        $importloginfo->importid = $overrides['importid'];
        $importloginfo->additionalinfo = $e->a ?? '';
        return new import_log(0, $importloginfo);
    }
}

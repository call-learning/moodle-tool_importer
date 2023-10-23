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

namespace tool_importer\local\logs;

use coding_exception;
use core\persistent;
use stdClass;
use tool_importer\local\log_levels;

/**
 * Class import_log
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_log_entity extends persistent implements import_log_entity_interface {

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
        $record->additionalinfo =
                is_string($record->additionalinfo) ? $record->additionalinfo : json_encode($record->additionalinfo);
        parent::__construct($id, $record);

    }

    /**
     * Usual properties definition for a persistent
     *
     * @return array|array[]
     * @throws coding_exception
     */
    protected static function define_properties() {
        return [
                'linenumber' => [
                        'type' => PARAM_INT,
                        'null' => NULL_NOT_ALLOWED,
                ],
                'messagecode' => [
                        'type' => PARAM_TEXT,
                        'null' => NULL_NOT_ALLOWED,
                ],
                'module' => [
                        'type' => PARAM_TEXT,
                ],
                'additionalinfo' => [
                        'type' => PARAM_TEXT,
                        'default' => '',
                        'null' => NULL_ALLOWED,
                ],
                'fieldname' => [
                        'type' => PARAM_TEXT,
                        'default' => '',
                        'null' => NULL_ALLOWED,
                ],
                'level' => [
                        'type' => PARAM_INT,
                        'default' => log_levels::LEVEL_WARNING,
                        'choices' => [
                                log_levels::LEVEL_INFO,
                                log_levels::LEVEL_WARNING,
                                log_levels::LEVEL_ERROR,
                        ],
                        'format' => [
                                'choices' => [
                                        log_levels::LEVEL_INFO => get_string('log:level:info', 'tool_importer'),
                                        log_levels::LEVEL_WARNING => get_string('log:level:warning', 'tool_importer'),
                                        log_levels::LEVEL_ERROR => get_string('log:level:error', 'tool_importer'),
                                ],
                        ],
                ],
                'origin' => [
                        'type' => PARAM_TEXT,
                ],
                'importid' => [
                        'type' => PARAM_INT,
                ],
                'validationstep' => [
                        'type' => PARAM_BOOL,
                        'default' => false,
                        'null' => NULL_ALLOWED,
                ],
        ];
    }

    /**
     * Get message (human-readable)
     *
     * @return string
     * @throws coding_exception
     */
    public function get_full_message() {
        $record = $this->to_record();
        $data = $this->get('additionalinfo');
        if (is_object($data)) {
            $data = $data->info ?? '';
        }
        $message = get_string($record->messagecode, $record->module, $data);

        return get_string('importlog:message',
                'tool_importer',
                (object) [
                        'line' => $record->linenumber,
                        'message' => $message,
                        'level' => strtoupper(log_levels::to_displayable_string($record->level)),
                        'fieldname' => $record->fieldname,
                ]);
    }

    /**
     * Is an error
     *
     * Difference between error and other errors is that it will stop entirely the importation / validation.
     * @return bool
     */
    public function is_error() {
        return $this->get('level') == log_levels::LEVEL_ERROR;
    }

    /**
     * Add more info to the log (and encode it if to be stored in db)
     *
     * @param object|string $value
     */
    protected function set_additionalinfo($value) {
        $this->raw_set('additionalinfo', is_string($value) ? $value : json_encode($value));
    }

    /**
     * Get additional info and decode it from the db
     *
     * @return mixed
     */
    protected function get_additionalinfo() {
        $data = $this->raw_get('additionalinfo');
        $json = json_decode($data);
        return $json ?? $data;
    }
}

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

/**
 * Local utils function for import routines and other.
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

    const TABLE = 'tool_importer_logs';

    /**
     * Create an instance of this class.
     *
     * @param int $id If set, this is the id of an existing record, used to load the data.
     * @param stdClass $record If set will be passed to {@link self::from_record()}.
     */
    public function __construct($id = 0, stdClass $record = null) {
        if (!empty($record) && !empty($record->type) && is_string($record->type)) {
            $converter = array_flip(static::LEVEL_TO_SN);
            $typeclean = trim(strtolower($record->type));
            if (!empty($converter[$typeclean])) {
                $record->level = $converter[$typeclean];
            } else {
                $record->level = self::LEVEL_INFO;
            }
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
            ),
            'fieldname' => array(
                'type' => PARAM_TEXT,
            ),
            'level' => array(
                'type' => PARAM_INT,
                'default' => static::LEVEL_WARNING,
                'choices' => array(
                    self::LEVEL_INFO,
                    self::LEVEL_WARNING,
                    self::LEVEL_ERROR,
                ),
                'format' => [
                    'choices' => [
                        self::LEVEL_INFO => get_string('log:level:info', 'tool_importer'),
                        self::LEVEL_WARNING => get_string('log:level:warning', 'tool_importer'),
                        self::LEVEL_ERROR => get_string('log:level:error', 'tool_importer'),
                    ]
                ]
            ),
            'origin' => array(
                'type' => PARAM_TEXT,
            ),
            'importid' => array(
                'type' => PARAM_INT,
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
     * Info
     */
    const LEVEL_INFO = 0;
    /**
     * Warning
     */
    const LEVEL_WARNING = 1;
    /**
     * Error
     */
    const LEVEL_ERROR = 2;

    const LEVEL_TO_SN = [
        self::LEVEL_INFO => 'none',
        self::LEVEL_ERROR => 'error',
        self::LEVEL_WARNING => 'warning'
    ];
}


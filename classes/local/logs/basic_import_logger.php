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

use core\persistent;
use stdClass;
use tool_importer\local\log_levels;

defined('MOODLE_INTERNAL') || die();

/**
 * Class import logger
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class basic_import_logger implements import_logger {
    /**
     * Get message (human readable)
     *
     * @param $logid identifier of the log
     * @return string
     * @throws \coding_exception
     */
    public function get_full_message($logid) {
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
     * @return import_log_entity
     */
    public function log_from_exception(\moodle_exception $e, array $overrides) {
        global $CFG;
        $importloginfo = new stdClass();
        $importloginfo->messagecode = $e->errorcode;
        $importloginfo->origin = $overrides['origin'] ?? 'unknown';
        $importloginfo->module = $e->module ?? ($overrides['module'] ?? 'tool_importer');
        $importloginfo->level = $e->level ?? ($overrides['level'] ?? log_levels::LEVEL_ERROR);
        $importloginfo->linenumber = $e->linenumber ?? ($overrides['linenumber'] ?? 0);
        $importloginfo->fieldname = $e->fieldname ?? '';
        $importloginfo->importid = $overrides['importid'];
        $additionalinfo = $e->a ?? '';
        $hasdebugdeveloper = (
            isset($CFG->debugdisplay) &&
            isset($CFG->debug) &&
            $CFG->debugdisplay &&
            $CFG->debug === DEBUG_DEVELOPER
        );
        if ($hasdebugdeveloper) {
            $info = get_exception_info($e);
            if ($info) {
                $additionalinfo = (object) ['info' => $additionalinfo];
                $additionalinfo->debuginfo = $info;
            }
        }
        $importloginfo->additionalinfo = $additionalinfo;
        return new import_log_entity(0, $importloginfo);
    }

    /**
     * Get logs from filters
     *
     * @param array $filters
     * @return persistent[]
     * @throws \coding_exception
     */
    public function get_logs($filters = []) {
        return import_log_entity::get_records($filters);
    }

    /**
     * Get related persistent class
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_log_persistent_class() {
        return import_log_entity::class;
    }
}

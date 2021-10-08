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
 * Data validation exception.
 *
 * Take a processed row and make it persistent
 *
 * This class will be derived according to the type of data to be imported.
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_importer;

use tool_importer\local\import_log;

defined('MOODLE_INTERNAL') || die();

class validation_exception extends \moodle_exception {
    /**
     * @var import_log $importlog
     */
    private $importlog = null;

    /**
     * Create a validation exception
     *
     * @param string $errorcode
     * @param int $linenumber
     * @param string $fieldname
     * @param string $origin
     * @param string $importid
     * @param string $module
     * @param null $additionalinfo
     * @param int $level
     * @param null $debuginfo
     */
    public function __construct($errorcode, $linenumber, $fieldname, $origin, $importid, $module = '', $additionalinfo = null,
        $level = import_log::LEVEL_WARNING, $debuginfo = null) {
        $this->importlog = new import_log(0,(object) [
            'linenumber' => $linenumber,
            'messagecode' => $errorcode,
            'module' => $module,
            'additionalinfo' => $additionalinfo,
            'fieldname' => $fieldname,
            'level' => $level,
            'origin' => $origin,
            'importid' => $importid]);
        parent::__construct($errorcode, $module, '', $additionalinfo, $debuginfo);
    }

    /**
     * @return import_log
     */
    public function get_import_log() {
        return $this->importlog;
    }
}
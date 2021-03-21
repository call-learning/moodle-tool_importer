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

use Exception;

defined('MOODLE_INTERNAL') || die();

class import_log {
    /**
     * @var int
     */
    public $linenumber;
    /**
     * @var string
     */
    public $messagecode;

    /**
     * @var string
     */
    public $module;

    /**
     * @var string
     */
    public $additionalinfo;

    /**
     * @var string
     */
    public $fieldname;

    /**
     * @var string error, warning...
     */
    public $level;

    const TABLE = '';
    /**
     * import_error constructor.
     *
     * @param $linenumber
     * @param $fieldname
     * @param $errorcode
     * @param $errormessage
     */
    public function __construct($linenumber, $fieldname, $code, $module='tool_importer',  $additionalinfo = null, $level='error')  {
        $this->linenumber = $linenumber;
        $this->messagecode = $code;
        $this->fieldname = $fieldname;
        $this->module = $module;
        $this->additionalinfo = $additionalinfo;
        $this->level = $level;
    }

    public function get_full_message() {
        $json = json_encode($this->additionalinfo);
        return "$this->messagecode ($this->level): line $this->linenumber $this->fieldname - $json";
    }

    public function __destruct() {
        $this->persist();
    }

    protected function persist() {
        try {
            if (!empty(self::TABLE)) {
                // TODO: Persit log.
            }
        } catch(Exception $e) {
            debugging('Error when persisting log:'.$e->getMessage(), DEBUG_NORMAL, $e->getTrace());
        }
    }
}

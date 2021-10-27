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

namespace tool_importer\local\exceptions;

use tool_importer\local\log_levels;

defined('MOODLE_INTERNAL') || die();

/**
 * Data importer exception.
 *
 * Take a processed row and make it persistent
 *
 * This class will be derived according to the type of data to be imported.
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importer_exception extends \moodle_exception {
    /**
     * @var int $level information about the validation issue
     */
    public $level = log_levels::LEVEL_WARNING;

    /**
     * @var string $fieldname information
     */
    public $fieldname = "";

    /**
     * @var int $linenumber information
     */
    public $linenumber = 0;

    /**
     * Create a validation exception
     *
     * @param string $messagecode
     * @param int $rowindex
     * @param string $fieldname
     * @param string $module
     * @param null $additionalinfo
     * @param int $level
     * @param null $debuginfo
     */
    public function __construct($messagecode,
        $rowindex,
        $fieldname = '',
        $module = 'tool_importer',
        $additionalinfo = null,
        $level = log_levels::LEVEL_WARNING,
        $debuginfo = null) {
        $this->linenumber = $rowindex + 2; // We take into account the header and the fact we start at index 0, although this
        // make more sense to start row index at 1.
        $this->level = $level;
        $this->fieldname = $fieldname;
        parent::__construct($messagecode, $module, '', $additionalinfo, $debuginfo);
    }
}

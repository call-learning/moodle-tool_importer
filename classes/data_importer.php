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
 * Data importer class.
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
defined('MOODLE_INTERNAL') || die();

abstract class data_importer {
    protected $defaultvalues = [];

    /**
     * Get the field definition array
     *
     * The associative array has at least a series of column names
     * Types are derived from the field_types class
     * 'fieldname' => [ 'type' => TYPE_XXX, ...]
     *
     * @return array
     */
    public abstract function get_fields_definition();

    /**
     * Do the real import (in the persistent state/database)
     *
     * @param $row
     * @return mixed
     */
    public abstract function import_row($row);

    /**
     * Check if row is valid.
     *
     * @param $row
     * @return bool
     */
    public function check_row($row) {
        $allfields = $this->get_fields_definition();
        foreach ($allfields as $fiedname => $fieldvalue) {
            if (!isset($row[$fiedname]) && !empty($fieldvalue['required'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set default value for given column
     *
     * @param string $key column name
     * @param $value
     */
    public function set_default_value($key, $value) {
        $this->defaultvalues[$key] = $value;
    }

}
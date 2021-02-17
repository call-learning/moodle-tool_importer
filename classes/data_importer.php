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
    public function import_row($row) {
        $data = $this->raw_import($row);
        $this->after_row_imported($row, $data);
    }

    /**
     * Callback after each row is imported.
     *
     * @param $row
     * @return mixed
     */
    public function after_row_imported($row, $data) {
        // Nothing for now but can be overridden.
    }

    /**
     * Do the real import (in the persistent state/database)
     *
     * @param $row
     * @return mixed
     */
    protected abstract function raw_import($row);

    /**
     * Check if row is valid.
     *
     *
     * @param $row
     * @param $rowindex
     * @return array of import_error (with field name and errorcode) or null if no error
     */
    public function validate($row, $rowindex) {
        $allfields = $this->get_fields_definition();
        $errors = [];
        foreach ($allfields as $fieldname => $fieldvalue) {
            if (!isset($row[$fieldname]) && !empty($fieldvalue['required'])) {
                $errors[] = new \import_error($rowindex, $fieldname, 'required');
            }
        }
        return $errors;
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

    /**
     * Make sure the fields validate correctly
     *
     * @throws importer_exception
     */
    public function basic_validations($row) {
        foreach ($this->get_fields_definition() as $col => $value) {
            if (empty($value['type'])) {
                throw new importer_exception('importercolumndef', 'tool_importer', null, 'type');
            }
            $type = $value['type'];
            $required = empty($value['required']) ? false : $value['required'];
            if ($required && !isset($row[$col])) {
                throw new importer_exception('rowvaluerequired', 'tool_importer', null,
                    "{$col}:" . json_encode($row));
            } else if (!isset($row[$col])) {
                continue;
            }
            if (!field_types::is_valid($row[$col], $type)) {
                throw new importer_exception('invalidrowvalue', 'tool_importer', null,
                    "{$col}:" . json_encode($row));
            }
        }
    }
}
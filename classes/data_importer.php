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

namespace tool_importer;

use tool_importer\local\log_levels;

defined('MOODLE_INTERNAL') || die();

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
abstract class data_importer {
    /**
     * @var int $importexternalid
     */
    protected $importexternalid = 0;
    /**
     * @var array
     */
    protected $defaultvalues = [];

    /**
     * @var data_source $source
     */
    protected $source = null;

    /**
     * Current module
     *
     * @var string
     */
    protected $module = 'tool_importer';

    /**
     * data_importer constructor.
     *
     * @param data_source $source
     */
    public function __construct(data_source $source) {
        $this->source = $source;
    }

    /**
     * Get the field definition array
     *
     * The associative array has at least a series of column names
     * Types are derived from the field_types class
     * 'fieldname' => [ 'type' => TYPE_XXX, ...]
     *
     * @return array
     */
    public function get_fields_definition() {
        return $this->source->get_fields_definition();
    }

    /**
     * Do the real import (in the persistent state/database)
     *
     * @param array $row
     * @param int $rowindex
     * @return mixed
     */
    public function import_row($row, $rowindex) {
        $data = $this->raw_import($row, $rowindex);
        $this->after_row_imported($row, $data, $rowindex);
    }

    /**
     * Callback after each row is imported.
     *
     * @param array $row
     * @param mixed $data
     * @param int $rowindex
     */
    public function after_row_imported($row, $data, $rowindex) {
        // Nothing for now but can be overridden.
    }

    /**
     * Do the real import (in the persistent state/database)
     *
     * @param array $row
     * @param int $rowindex
     *
     * @return mixed
     */
    abstract protected function raw_import($row, $rowindex);

    /**
     * Check if row is valid before transformation.
     *
     * @param array $row
     * @param int $rowindex
     * @throws validation_exception
     */
    public function validate_before_transform($row, $rowindex) {
        $this->validate_from_field_definition($this->get_fields_definition(), $row, $rowindex);
    }

    /**
     * Generic method to validate field definition
     *
     * This can be used before (usually) or after field definition with a different definition array.
     *
     * @param array $fielddefinitionlist associative array of field definition the associative array has at least a series of
     * column names that should be present in the row. Types are derived from the field_types class 'fieldname' =>
     * [ 'type' => TYPE_XXX, ...]
     * @param array $row
     * @param int $rowindex
     * @throws validation_exception
     */
    protected function validate_from_field_definition($fielddefinitionlist, $row, $rowindex) {
        foreach ($fielddefinitionlist as $fieldname => $value) {
            if (empty($value['type'])) {
                throw new validation_exception('typenotspecified',
                    $rowindex,
                    $fieldname,
                    $this->module,
                    'coding error: type unspecified',
                    log_levels::LEVEL_ERROR
                );
            }
            $type = $value['type'];
            $required = empty($value['required']) ? false : $value['required'];
            if ($required && !isset($row[$fieldname])) {
                throw new validation_exception('required',
                    $rowindex,
                    $fieldname,
                    $this->module,
                    null,
                    log_levels::LEVEL_ERROR
                );
            } else if (!isset($row[$fieldname])) {
                continue;
            }
            if (!field_types::is_valid($row[$fieldname], $type)) {
                throw new validation_exception('wrongtype',
                    $rowindex,
                    $fieldname,
                    $this->module,
                    null,
                    log_levels::LEVEL_ERROR
                );
            }
        }
    }

    /**
     * Check if row is valid after transformation.
     *
     *
     * @param array $row
     * @param int $rowindex
     * @throws validation_exception
     */
    public function validate_after_transform($row, $rowindex) {
    }

    /**
     * Check if row is valid before we transform it
     * It will also change the value
     *
     * This helps to catch errors before we try to transform the row.
     *
     * @param array $row
     * @param int $rowindex
     */
    public function fix_before_transform(&$row, $rowindex) {
    }

    /**
     * Set default value for given column
     *
     * @param string $key column name
     * @param mixed $value
     */
    public function set_default_value($key, $value) {
        $this->defaultvalues[$key] = $value;
    }

    /**
     * Make sure the fields validate correctly
     * @param array $row
     * @throws importer_exception
     */
    public function basic_validations($row) {

    }

    /**
     * Get related data source
     */
    public function get_related_source() {
        return $this->source;
    }

    /**
     * Get import id
     *
     * @return int
     */
    public function get_import_id() {
        return $this->importexternalid;
    }

    /**
     * Set import id
     *
     * @param int $importid
     */
    public function set_import_id($importid) {
        $this->importexternalid = $importid;
    }
}

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

use tool_importer\local\import_log;
use tool_lpimportcsv\form\import;

defined('MOODLE_INTERNAL') || die();

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
     * @param int $rowindex
     * @param array $row
     * @return mixed
     */
    public function import_row($row, $rowindex) {
        $data = $this->raw_import($row, $rowindex);
        $this->after_row_imported($row, $data, $rowindex);
    }

    /**
     * Callback after each row is imported.
     *
     * @param int $rowindex
     * @param array $row
     * @return mixed
     */
    public function after_row_imported($row, $data, $rowindex) {
        // Nothing for now but can be overridden.
    }

    /**
     * Do the real import (in the persistent state/database)
     *
     * @param int $rowindex
     * @param array $row
     * @return mixed
     */
    protected abstract function raw_import($row, $rowindex);

    /**
     * Check if row is valid after transformation.
     *
     *
     * @param array $row
     * @param int $rowindex
     * @throw validation_exception
     */
    public function validate($row, $rowindex) {
        $allfields = $this->get_fields_definition();
        foreach ($allfields as $fieldname => $fieldvalue) {
            if (!isset($row[$fieldname])){
                if (!empty($fieldvalue['required'])) {
                    throw new validation_exception('required',
                        $rowindex,
                        $fieldname,
                        $this->source->get_source_type() . ':' . $this->source->get_source_identifier(),
                        $this->get_import_id(),
                        $this->module,
                        'validationexception',
                        import_log::LEVEL_WARNING);
                }
            } else {
                switch ($fieldvalue['type']) {
                    case field_types::TYPE_TEXT:
                        break;
                    case field_types::TYPE_INT:
                        if (!is_numeric($row[$fieldname])) {
                            throw new validation_exception('wrongtype',
                                $rowindex,
                                $fieldname,
                                $this->source->get_source_type() . ':' . $this->source->get_source_identifier(),
                                $this->get_import_id(),
                                $this->module,
                                'validationexception',
                                import_log::LEVEL_WARNING);
                        }
                        break;
                }
            }
        }
    }

    /**
     * Check if row is valid before we transform it
     * It will also change the value
     *
     * This helps to catch errors before we try to transform the row.
     *
     * @param $row
     * @param $rowindex
     */
    public function fix_before_transform(&$row, $rowindex) {
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
                throw new importer_exception('importercolumndef', 'tool_importer', 'type');
            }
            $type = $value['type'];
            $required = empty($value['required']) ? false : $value['required'];
            if ($required && !isset($row[$col])) {
                throw new importer_exception('rowvaluerequired', 'tool_importer',
                    "{$col}:" . json_encode($row));
            } else if (!isset($row[$col])) {
                continue;
            }
            if (!field_types::is_valid($row[$col], $type)) {
                throw new importer_exception('invalidrowvalue', 'tool_importer',
                    "{$col}:" . json_encode($row));
            }
        }
    }

    /**
     * @param data_source $source
     */
    public function get_related_source() {
        return $this->source;
    }

    /**
     * @return int
     */
    public function get_import_id() {
        return $this->importexternalid;
    }

    /**
     *
     * @param int $importid
     */
    public function set_import_id($importid) {
        $this->importexternalid = $importid;
    }
}
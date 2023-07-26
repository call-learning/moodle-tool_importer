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
 * Data source class.
 *
 *
 * Read data row by row
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_importer;

use Exception;
use Iterator;
use tool_importer\local\exceptions\importer_exception;

/**
 * Class data_source
 *
 * This class is an iterator and can be used as such. Read data row by row.
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class data_source implements Iterator, data_processor_mgmt_interface {
    use data_processor_mgmt_impl;

    /**
     * Iterator is valid
     *
     * @var bool
     */
    protected $isvalid = true;

    /**
     * @var array $currentvalue
     */
    protected $currentvalue = null;

    /**
     * Initialise the csv datasource.
     *
     * This will initialise the current source. This has to be called before we call current or rewind.
     *
     * @param mixed|null $options additional importer options
     * @throws importer_exception
     */
    public function init_and_check($options = null) {
        // Nothing for now.
    }

    /**
     * Get the field definition array.
     *
     * This lists the necessary fields. Other fields will be ignored.
     * The associative array has at least a series of column names
     * Types are derived from the field_types class
     * 'fieldname' => [ 'type' => TYPE_XXX, ...]
     *
     * @return array
     */
    abstract public function get_fields_definition();

    /**
     * Give a chance to the source to initalise fields before creating the field definition
     *
     * @param array $csvheaders
     * @return void
     */
    protected function setup_fields_before_defintion(array $csvheaders) {
        // Nothing for now.
    }

    /**
     * Get the total number of records
     *
     * @return mixed
     */
    abstract public function get_total_row_count();

    /**
     * Get origin
     *
     * @return string
     */
    public function get_origin() {
        return $this->get_source_type() . ':' . $this->get_source_identifier();
    }

    /**
     * Get source type
     *
     * @return string
     */
    abstract public function get_source_type();

    /**
     * Get source identifier
     *
     * @return string|null
     */
    abstract public function get_source_identifier();

    /**
     * Get next element and protect it from throwing an exception
     * This is a wrapper around the next function.
     *
     * @return void
     */
    public function next(): void {
        try {
            $this->currentvalue = $this->retrieve_next_value();
        } catch (Exception $e) {
            $this->isvalid = false;
        }
    }

    /**
     * Run the next function itself
     */
    abstract protected function retrieve_next_value();

    /**
     * Checks if current position is valid
     *
     * @link https://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be cast to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid(): bool {
        return $this->isvalid;
    }
}

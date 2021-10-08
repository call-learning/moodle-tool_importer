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
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_importer;
defined('MOODLE_INTERNAL') || die();
use Iterator;
use Matrix\Exception;

/**
 * Class data_source
 *
 * This class is an interator and can be used as such.
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class data_source implements Iterator {
    /**
     * Iterator is valid
     * @var bool
     */
    protected $isvalid = true;

    /**
     * @var array $currentvalue
     */
    protected $currentvalue = null;

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
    public abstract function get_fields_definition();

    /**
     * Get the total number of records
     * @return mixed
     */
    public abstract function get_total_row_count();

    /**
     * Get source type
     *
     * @return string
     */
    public abstract function get_source_type();

    /**
     * Get source identifier
     *
     * @return string|null
     */
    public abstract function get_source_identifier();

    /**
     * Get next element and protect it from throwing an exc
     *
     * @return false|mixed
     */
    public function next() {
        try {
            $this->currentvalue = $this->retrieve_next_value();
        } catch(\Exception $e) {
            $this->isvalid = false;
        }
        return $this->currentvalue;
    }

    /**
     * Run the next function itself
     */
    abstract protected function retrieve_next_value();

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid() {
        return $this->isvalid;
    }
}
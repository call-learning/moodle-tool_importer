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
 * Data processor class.
 *
 *
 * Transform a give row of data
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_importer;

/**
 * Class data_transformer
 *
 * Transform a row of data in another row of data.
 *
 * @package tool_importer
 */
abstract class data_transformer  implements data_processor_mgmt_interface {
    use data_processor_mgmt_impl;
    /**
     * @var array transformation definition
     */
    protected $fieldtransformerdef = array();

    /**
     * @var string separator for concatenation
     */
    protected $concatseparator = ' ';

    /**
     * data_transformer constructor.
     *
     * @param array $transformerdef definition
     * @param string $concatseparator separator for concatenation
     */
    public function __construct($transformerdef = array(), $concatseparator = ' ') {
        $this->fieldtransformerdef = $transformerdef;
        $this->concatseparator = $concatseparator;
    }

    /**
     * Get the field definition array
     *
     * The associative array has at least a series of column names
     * 'originfieldname' => [
     *      [
     *          'to' => 'destfieldname1,destfilename2',
     *          'transformcallback' => 'nameoffunctiontotranformfield'
     *                                   Transform function (before concatenation)
     *          'concatenate' => ['order'=>0]  // If a given field is assigned several times, do we concatenate
     *                                         // and in which order.
     *      ],
     *      ...
     * ]
     * - to: A field or a list of fields. This will also change the way we interpret the transformcallback
     * - transformcallback: Transform function (before concatenation), with two parameters ($value, $column)
     * - concatenate: If a given field is assigned several times, do we concatenate and in which order.
     *
     * @return array
     */
    public function get_fields_transformers() {
        return $this->fieldtransformerdef;
    }

    /**
     * Return the transformed row depending on the field transformer values.
     *
     * @param array $row an associative array (column => value)
     * @param mixed|null $options import options
     * @return mixed
     */
    abstract public function transform($row, $options = null);
}

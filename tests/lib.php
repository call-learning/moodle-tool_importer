<?php
// This file is part of Moodle - http://moodle.org/
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
 * Test for basic tools
 *
 * @package     tool_importer
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_importer\data_importer;
use tool_importer\data_source;
use tool_importer\data_transformer;

defined('MOODLE_INTERNAL') || die();

defined('MOODLE_INTERNAL') || die();

/**
 * Class inmemory_data_source
 *
 * An in memory datasource for tests
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inmemory_data_source extends data_source {

    const BASIC_DATA_ARRAY = [
        ['A', 'B', 'C', 'D'],
        ['E', 'F', 'G', 'H'],
        ['I', 'J', 'K', 'L'],
    ];

    const BASIC_FIELD_DEFINITION = [
        'col1' => [
            'type' => \tool_importer\field_types::TYPE_TEXT,
        ],
        'col2' => [
            'type' => \tool_importer\field_types::TYPE_TEXT,
        ],
        'col3' => [
            'type' => \tool_importer\field_types::TYPE_TEXT,
        ],
        'col4' => [
            'type' => \tool_importer\field_types::TYPE_TEXT,
        ]
    ];

    public $dataarray = [];
    public $fielddefinition = [];
    protected $currentrow = 0;

    public function __construct($dataarray = null, $fielddefinition = null) {
        $this->dataarray = $dataarray ?? self::BASIC_DATA_ARRAY;
        $this->fielddefinition = $fielddefinition ?? self::BASIC_FIELD_DEFINITION;
    }

    public function current() {
        $keys = array_keys($this->get_fields_definition());
        if (count($this->dataarray[$this->currentrow]) != count($keys)) {
            throw new \moodle_exception('wrongcolumnnumber', 'local_importer');
        }
        return array_combine($keys, $this->dataarray[$this->currentrow]);
    }

    public function retrieve_next_value() {
        $this->currentrow++;
        if ($this->currentrow < count($this->dataarray)) {
            return $this->current();
        } else {
            return false;
        }
    }

    public function key() {
        return $this->currentrow;
    }

    public function valid() {
        return $this->currentrow < count($this->dataarray);
    }

    public function rewind() {
        $this->currentrow = 0;
    }

    public function get_fields_definition() {
        return $this->fielddefinition;
    }

    public function get_total_row_count() {
        return 3;
    }

    public function get_source_type() {
        return 'memory';
    }

    public function get_source_identifier() {
        return 'test';
    }
}

/**
 * Class minimal transformer
 *
 * A minimal data transformer for test
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class minimal_transformer extends data_transformer {

    /**
     * Transform function
     *
     * @param array $row
     * @return array|mixed
     */
    public function transform($row) {
        $outrow = $row;
        $outrow['newcol1'] = $row['col1'];
        unset($outrow['col1']);
        return $outrow;
    }
}

/**
 * Class inmemory importer
 *
 * A minimal data transformer for test
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inmemory_importer extends data_importer {
    public $resultarray = [];

    /**
     * Raw import
     *
     * @param $row
     * @return mixed|void
     */
    public function raw_import($row, $rowindex) {
        $this->resultarray[] = $row;
    }
}
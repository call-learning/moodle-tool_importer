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
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_importer\data_source;
use tool_importer\data_transformer;
use tool_importer\data_importer;
use tool_importer\importer;

defined('MOODLE_INTERNAL') || die();

/**
 * Class inmemory_data_source
 *
 * An in memory datasource for tests
 *
 * @package tool_importer\course
 */
class inmemory_data_source extends data_source {

    public $dataarray = [
        ['A', 'B', 'C', 'D'],
        ['E', 'F', 'G', 'H'],
        ['I', 'J', 'K', 'L'],
    ];
    protected $currentrow = 0;

    public function current() {
        $keys = array_keys($this->get_fields_definition());
        return array_combine($keys, $this->dataarray[$this->currentrow]);
    }

    public function next() {
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
        return [
            'col1' => \tool_importer\field_types::TYPE_TEXT,
            'col2' => \tool_importer\field_types::TYPE_TEXT,
            'col3' => \tool_importer\field_types::TYPE_TEXT,
            'col4' => \tool_importer\field_types::TYPE_TEXT,
        ];
    }
}

/**
 * Class minimal transformer
 *
 * A minimal data transformer for test
 *
 * @package tool_importer\course
 */
class minimal_transformer extends data_transformer {

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
 * @package tool_importer\course
 */
class inmemory_importer extends data_importer {
    public $resultarray = [];

    public function get_fields_definition() {
        return [
            'newcol1' => \tool_importer\field_types::TYPE_TEXT,
            'col2' => \tool_importer\field_types::TYPE_TEXT,
            'col3' => \tool_importer\field_types::TYPE_TEXT,
            'col4' => \tool_importer\field_types::TYPE_TEXT,
        ];
    }

    public function import_row($row) {
        $this->resultarray[] = $row;
    }
}

/**
 * Test for basic tools
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class basic_tools_test extends advanced_testcase {
    /**
     * Test basic importation process
     */
    public function test_importer_basic() {
        $inmemoryimporter = new inmemory_importer();
        $importer = new importer(new inmemory_data_source(), new minimal_transformer(), $inmemoryimporter);
        $importer->import();
        $this->assertEquals(
            array(
                array(
                    'col2' => 'B',
                    'col3' => 'C',
                    'col4' => 'D',
                    'newcol1' => 'A',
                ),
                array(
                    'col2' => 'F',
                    'col3' => 'G',
                    'col4' => 'H',
                    'newcol1' => 'E',
                ),
                array(
                    'col2' => 'J',
                    'col3' => 'K',
                    'col4' => 'L',
                    'newcol1' => 'I',
                ),
            ),
            $inmemoryimporter->resultarray
        );
    }
}


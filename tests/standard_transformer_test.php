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
 * Standard transformer test
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function test_transform_callback($value, $columnname) {
    return strval($value) . 'VAL';
}

function test_transform_callback_summaryformat($value, $columnname) {
    return FORMAT_HTML;
}

/**
 * Standard transformer test
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class standard_transformer_test extends advanced_testcase {
    const ROW_DEF = array(
        'col1' => 'col1value',
        'col2' => 1234,
        'col3' => "AAAAA",
    );

    public function test_simple_column_transform() {
        $transformdef = array(
            'col1' => array(array('to' => 'newcol1')),
            'col2' => array(array('to' => 'newcol2'), array('to' => 'newcol3'))
        );
        $transformer = new \tool_importer\transformer\standard($transformdef);

        $this->assertEquals(
            array(
                'newcol1' => 'col1value',
                'newcol2' => 1234,
                'newcol3' => 1234,
                'col3' => 'AAAAA',
            ), $transformer->transform(self::ROW_DEF)
        );
    }

    public function test_simple_column_transform_with_callback() {
        $transformdef = array(
            'col1' => array(array('to' => 'newcol1', 'transformcallback' => 'test_transform_callback')),
            'col2' => array(array('to' => 'newcol2'), array('to' => 'newcol3'))
        );
        $transformer = new \tool_importer\transformer\standard($transformdef);

        $this->assertEquals(
            array(
                'newcol1' => 'col1valueVAL',
                'newcol2' => 1234,
                'newcol3' => 1234,
                'col3' => 'AAAAA',
            ), $transformer->transform(self::ROW_DEF)
        );
    }

    public function test_simple_column_transform_with_enhanced_callback() {
        $transformdef = array(
            'col1' => array(array('to' => 'summary'),
                array('to' => 'format', 'transformcallback' => 'test_transform_callback_summaryformat')),
            'col2' => array(array('to' => 'newcol2'), array('to' => 'newcol3'))
        );
        $transformer = new \tool_importer\transformer\standard($transformdef);

        $this->assertEquals(
            array(
                'newcol2' => 1234,
                'newcol3' => 1234,
                'col3' => 'AAAAA',
                'summary' => 'col1value',
                'format' => '1'
            ), $transformer->transform(self::ROW_DEF)
        );
    }

    public function test_simple_column_concat() {
        $transformdef = array(
            'col1' => array(array('to' => 'newcol1', 'concatenate' => ['order' => 0])),
            'col3' => array(array('to' => 'newcol1', 'concatenate' => ['order' => 1]))
        );
        $transformer = new \tool_importer\transformer\standard($transformdef);

        $this->assertEquals(
            array(
                'newcol1' => "col1value AAAAA",
                'col2' => 1234,
            ), $transformer->transform(self::ROW_DEF)
        );
        $transformdef = array(
            'col1' => array(array('to' => 'newcol1', 'concatenate' => ['order' => 0])),
            'col2' => array(array('to' => 'newcol1', 'concatenate' => ['order' => 1]))
        );
        $transformer = new \tool_importer\transformer\standard($transformdef);

        $this->assertEquals(
            array(
                'newcol1' => "col1value 1234",
                'col3' => "AAAAA",
            ), $transformer->transform(self::ROW_DEF)
        );
        $transformdef = array(
            'col1' => array(array('to' => 'newcol1', 'concatenate' => ['order' => 1])),
            'col2' => array(array('to' => 'newcol1', 'concatenate' => ['order' => 0]))
        );
        $transformer = new \tool_importer\transformer\standard($transformdef);

        $this->assertEquals(
            array(
                'newcol1' => "1234 col1value",
                'col3' => "AAAAA",
            ), $transformer->transform(self::ROW_DEF)
        );
    }
}
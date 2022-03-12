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
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_importer;
use advanced_testcase;
use inmemory_data_source;
use inmemory_importer;
use minimal_transformer;
use tool_importer\local\exceptions\importer_exception;
use tool_importer\local\transformer\standard;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/importer/tests/lib.php');

/**
 * Basic data importer class
 *
 */
class basic_tools_importer_class  extends \tool_importer\data_importer {
    /**
     * @var array $importedrows locally imported rows
     */
    protected $importedrows = [];
    /**
     * Get field definition
     *
     * @return array[]
     */
    public function get_fields_definition() {
        return basic_tools_test::FIELD_DEFINITION;
    }
    /**
     * Raw import
     *
     * @param array $row
     * @param int $rowindex
     * @param mixed $options
     * @return void
     */
    protected function raw_import($row, $rowindex, $options = null) {
        $this->importedrows[$rowindex] = $row;
        // Do nothing.
    }

    /**
     * Get imported data
     *
     * @return array
     */
    public function get_imported_data() {
        return $this->importedrows;
    }
}

/**
 * Test importer class
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class basic_tools_data_source extends \tool_importer\local\source\csv_data_source {
    /**
     * Get field definition
     *
     * @return array[]
     */
    public function get_fields_definition() {
        return basic_tools_test::FIELD_DEFINITION;
    }
}
/**
 * Test for basic tools
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class basic_tools_test extends advanced_testcase {
    /**
     * Test basic importation process
     *
     * @param array $datagrid input data
     * @param array $columndef
     * @param array $expected
     * @dataProvider basic_import_dataprovider
     */
    public function test_importer_basic($datagrid, $columndef, $expected) {
        $this->resetAfterTest();
        $source = new inmemory_data_source($datagrid);
        $inmemoryimporter = new inmemory_importer($source);
        $processor = new processor($source, new minimal_transformer(), $inmemoryimporter);
        $isvalid = $processor->import();
        $this->assertEquals($expected['isvalid'], $isvalid);
        $this->assertEquals($expected['result'], $inmemoryimporter->resultarray);
        $this->assertEquals($expected['importlogs'],
            array_map(function($log) {
                return array_intersect_key((array) $log->to_record(), array_flip(['linenumber', 'messagecode', 'origin']));
            }, $processor->get_logger()->get_logs())
        );
    }

    /**
     * Data provider for basic import
     *
     * @return array[]
     */
    public function basic_import_dataprovider() {
        return
            [
                'basic1' => [
                    'datagrid' => [
                        ['A', 'B', 'C', 'D'],
                        ['E', 'F', 'G', 'H'],
                        ['I', 'J', 'K', 'L'],
                    ],
                    'columndef' => [
                        'col1' => [
                            'type' => \tool_importer\field_types::TYPE_TEXT,
                            'required' => true
                        ],
                        'col2' => [
                            'type' => \tool_importer\field_types::TYPE_TEXT,
                            'required' => true
                        ],
                        'col3' => [
                            'type' => \tool_importer\field_types::TYPE_TEXT,
                            'required' => true
                        ],
                        'col4' => [
                            'type' => \tool_importer\field_types::TYPE_TEXT,
                            'required' => true
                        ],
                    ],
                    'expected' => [
                        'isvalid' => true,
                        'result' => [
                            [
                                'col2' => 'B',
                                'col3' => 'C',
                                'col4' => 'D',
                                'newcol1' => 'A',
                            ],
                            [
                                'col2' => 'F',
                                'col3' => 'G',
                                'col4' => 'H',
                                'newcol1' => 'E',
                            ],
                            [
                                'col2' => 'J',
                                'col3' => 'K',
                                'col4' => 'L',
                                'newcol1' => 'I',
                            ],
                        ],
                        'importlogs' => []
                    ]
                ],
                'missingcol' => [
                    'datagrid' => [
                        ['A', 'B', 'C', 'D'],
                        ['E', 'F', 'H'],
                        ['I', 'J', 'K', 'L'],
                    ],
                    'columndef' => [
                        'col1' => [
                            'type' => \tool_importer\field_types::TYPE_TEXT,
                            'required' => true
                        ],
                        'col2' => [
                            'type' => \tool_importer\field_types::TYPE_TEXT,
                            'required' => true
                        ],
                        'col3' => [
                            'type' => \tool_importer\field_types::TYPE_TEXT,
                            'required' => true
                        ],
                        'col4' => [
                            'type' => \tool_importer\field_types::TYPE_TEXT,
                            'required' => true
                        ],
                    ],
                    'expected' => [
                        'isvalid' => false,
                        'importlogs' => [[
                            'linenumber' => 1,
                            'messagecode' => 'wrongcolumnnumber',
                            'origin' => 'memory:test',
                        ]],
                        'result' => [
                            [
                                'col2' => 'B',
                                'col3' => 'C',
                                'col4' => 'D',
                                'newcol1' => 'A',
                            ],
                            [
                                'col2' => 'J',
                                'col3' => 'K',
                                'col4' => 'L',
                                'newcol1' => 'I',
                            ],
                        ]
                    ]
                ]

            ];
    }

    /**
     * Field definition
     */
    const FIELD_DEFINITION =
        [
            "Colonne 1" => [
                'required' => true,
                'type' => field_types::TYPE_INT,
            ],
            "Colonne 2" => [
                'required' => true,
                'type' => field_types::TYPE_TEXT
            ],
            "Colonne 3" => [
                'type' => field_types::TYPE_TEXT
            ]
        ];

    /**
     * Test basic importation process
     * @param string $filename
     * @param array $results
     * @param array $errors
     * @dataProvider basic_csv_dataprovider
     */
    public function test_importer_csv($filename, $results, $errors) {
        $this->resetAfterTest();
        if (!empty($results['exception'])) {
            $this->expectException($results['exception']);
        }

        $processor = $this->create_processor_from_params($filename);
        $isvalid = $processor->import();
        $this->assertEquals($results['isvalid'], $isvalid, $filename);
        $this->assertEquals($errors,
            array_map(function($log) {
                return array_intersect_key((array) $log->to_record(), array_flip(['linenumber', 'messagecode', 'fieldname']));
            }, $processor->get_logger()->get_logs()),
            $filename
        );
    }

    /**
     * Data provider for basic import
     *
     * @return array[]
     */
    public function basic_csv_dataprovider() {
        return [
            'Importation Ok' => [
                'filename' => 'csv_sample1.csv',
                'results' => [
                    'isvalid' => true,
                ],
                'errors' => []
            ],
            'Issue with encoding' => [
                'filename' => 'csv_sample2_wrong_encoding.csv',
                'results' => [
                    'isvalid' => false,
                    'exception' => importer_exception::class
                ],
                'errors' => [
                    [
                        'messagecode' => 'wrongencoding',
                        'linenumber' => '1',
                        'fieldname' => ''
                    ]
                ]
            ],
            'Issue with coltype' => [
                'filename' => 'csv_sample3_wrong_coltype.csv',
                'results' => [
                    'isvalid' => false,
                ],
                'errors' => [
                    [
                        'messagecode' => 'wrongtype',
                        'linenumber' => '3',
                        'fieldname' => 'Colonne 1'
                    ]
                ]
            ],
            'Issue with required column' => [
                'filename' => 'csv_sample4_colmissing.csv',
                'results' => [
                    'isvalid' => false,
                    'exception' => importer_exception::class
                ],
                'errors' => [
                    [
                        'messagecode' => 'columnmissing',
                        'linenumber' => '1',
                        'fieldname' => 'Colonne 2'
                    ]
                ]
            ],
            'Issue with specialchars' => [
                'filename' => 'csv_sample5_colwithspecialchars.csv',
                'results' => [
                    'isvalid' => true,
                ],
                'errors' => []
            ],
            'With additional column, no issue' => [
                'filename' => 'csv_sample6_additional_cols.csv',
                'results' => [
                    'isvalid' => true,
                ],
                'errors' => []
            ],
            'Issue with space' => [
                'filename' => 'csv_sample7_withspace.csv',
                'results' => [
                    'isvalid' => true,
                ],
                'errors' => []
            ],
            'Issue with filenotfound' => [
                'filename' => 'randomname.csv',
                'results' => [
                    'isvalid' => false,
                    'exception' => importer_exception::class
                ],
                'errors' => [
                    [
                        'messagecode' => 'cannotopencsvfile',
                        'linenumber' => '1',
                        'fieldname' => ''
                    ]
                ]
            ],
        ];
    }

    /**
     * Test validate
     * @param string $filename
     * @param array $results
     * @param array $errors
     * @dataProvider basic_csv_dataprovider
     */
    public function test_validate_basic($filename, $results, $errors) {
        $this->resetAfterTest();
        $processor = $this->create_processor_from_params($filename);
        $isvalid = $processor->validate();
        $this->assertEmpty($processor->get_logger()->get_logs(['validationstep' => 0]));
        $this->assertEquals($results['isvalid'], $isvalid, $filename);
        $this->assertEquals($errors,
            array_map(function($log) {
                return array_intersect_key((array) $log->to_record(), array_flip(['linenumber', 'messagecode', 'fieldname']));
            }, $processor->get_validation_log()),
            $filename
        );
    }

    /**
     * Create processor and csv importer in one go.
     *
     * @param string $filename
     * @return processor
     */
    protected function create_processor_from_params($filename) {
        global $CFG;
        $csvimporter = new basic_tools_data_source(
            $CFG->dirroot . '/admin/tool/importer/tests/fixtures/' . $filename);
        $transformer = new standard([]);

        return new processor($csvimporter,
            $transformer,
            new basic_tools_importer_class($csvimporter),
            null,
            50
        );
    }

}

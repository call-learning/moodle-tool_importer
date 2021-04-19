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
 * Course data importer test
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_importer\field_types;
use tool_importer\importer;
use tool_importer\local\importer\course_data_importer;
use tool_importer\local\transformer\standard;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the import process with a simple case
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_dataimporter_test extends advanced_testcase {

    const CSV_DEFINITION = array(
        "CodeProduit" => field_types::TYPE_TEXT,
        "IntituleProduit" => field_types::TYPE_TEXT,
        "ResumeProduit" => field_types::TYPE_TEXT
    );

    protected $csvimporter = null;

    /**
     * Setup
     */
    public function setUp() {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        // Create a template course.
        $course = $generator->create_course(['idnumber' => 'templatecourse']);
        $generator->create_module('page', ['name' => 'Page 1', 'course' => $course]);
        // Create a couple of custom fields definitions.
        $catid = $generator->create_custom_field_category([])->get('id');
        $generator->create_custom_field(['categoryid' => $catid, 'type' => 'text', 'shortname' => 'f1']);
        // @codingStandardsIgnoreStart
        // phpcs:disable
        $this->csvimporter = new class(
            $CFG->dirroot . '/admin/tool/importer/tests/fixtures/course_sample1.csv')
            extends \tool_importer\local\source\csv_data_source {
            public function get_fields_definition() {
                return course_dataimporter_test::CSV_DEFINITION;
            }
        };
        // phpcs:enable
        // @codingStandardsIgnoreEnd
    }

    /**
     * @throws dml_exception
     */
    public function test_simple_course_import() {
        global $DB;
        $csvimporter = $this->csvimporter;
        $transformdef = array(
            'CodeProduit' => array(array('to' => 'idnumber'), array('to' => 'shortname')),
            'IntituleProduit' => array(array('to' => 'fullname', 'transformcallback' => 'ucwordns')),
            'ResumeProduit' => array(array('to' => 'summary'))
        );
        $transformer = new standard($transformdef);

        $importer = new importer($csvimporter,
            $transformer,
            new course_data_importer(),
            null,
            50
        );
        $importer->import();
        $arthrocourse = $DB->get_record('course', array('idnumber' => 'AC-CHIR-ARTHRO'));
        $brachycourse = $DB->get_record('course', array('idnumber' => 'AC-CHIR-BRACHY'));
        $coudecourse = $DB->get_record('course', array('idnumber' => 'AC-CHIR-COUDE'));
        $this->assertNotEmpty($arthrocourse);
        $this->assertNotEmpty($brachycourse);
        $this->assertNotEmpty($coudecourse);
        $this->assertTrue($importer->get_data_importer()->get_import_id() === 50);
    }

    /**
     * @throws dml_exception
     */
    public function test_simple_course_with_template_import() {
        global $CFG, $DB;
        $csvimporter = $this->csvimporter;
        $transformdef = array(
            'CodeProduit' => array(array('to' => 'idnumber')),
            'IntituleProduit' => array(array('to' => 'fullname', 'transformcallback' => 'ucwordns')),
            'ResumeProduit' => array(array('to' => 'summary'))
        );
        $transformer = new standard($transformdef);

        $importer = new importer($csvimporter,
            $transformer,
            new course_data_importer(array('templatecourseidnumber' => 'templatecourse')));
        $importer->import();
        $this->runAdhocTasks(); // Make sure the import task has run.
        $arthrocourse = $DB->get_record('course', array('idnumber' => 'AC-CHIR-ARTHRO'));
        $brachycourse = $DB->get_record('course', array('idnumber' => 'AC-CHIR-BRACHY'));
        $coudecourse = $DB->get_record('course', array('idnumber' => 'AC-CHIR-COUDE'));
        $this->assertNotEmpty($arthrocourse);
        $this->assertNotEmpty($brachycourse);
        $this->assertNotEmpty($coudecourse);
        $cms = course_modinfo::instance($arthrocourse);
        $page = $cms->get_instances_of('page');
        $page = reset($page); // Get the first page.
        $this->assertNotEmpty($page);
        $this->assertEquals('Page 1', $page->name);
    }

    /**
     * @throws dml_exception
     */
    public function test_simple_course_with_customfields() {
        global $CFG, $DB;
        $csvimporter = $this->csvimporter;
        $transformdef = array(
            'CodeProduit' => array(array('to' => 'idnumber'), array('to' => 'cf_f1')),
            'IntituleProduit' => array(array('to' => 'fullname', 'transformcallback' => 'ucwordns')),
            'ResumeProduit' => array(array('to' => 'summary')),
        );
        $transformer = new standard($transformdef);

        $importer = new importer($csvimporter,
            $transformer,
            new course_data_importer());
        $importer->import();

        $arthrocourse = $DB->get_record('course', array('idnumber' => 'AC-CHIR-ARTHRO'));
        $brachycourse = $DB->get_record('course', array('idnumber' => 'AC-CHIR-BRACHY'));
        $coudecourse = $DB->get_record('course', array('idnumber' => 'AC-CHIR-COUDE'));
        $this->assertNotEmpty($arthrocourse);
        $this->assertNotEmpty($brachycourse);
        $this->assertNotEmpty($coudecourse);

        $data = \core_course\customfield\course_handler::create()->export_instance_data_object($arthrocourse->id);
        $this->assertEquals('AC-CHIR-ARTHRO', $data->f1);
    }
}
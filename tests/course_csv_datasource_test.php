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
 * Simple course import test
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class csv_data_source
 *
 * An in memory datasource for tests
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_sample1_csv extends \tool_importer\local\source\csv_data_source {

    /**
     * Get field definition
     *
     * @return array
     */
    public function get_fields_definition() {
        return array(
            "CodeProduit" => \tool_importer\field_types::TYPE_TEXT,
            "IntituleProduit" => \tool_importer\field_types::TYPE_TEXT,
            "ResumeProduit" => \tool_importer\field_types::TYPE_TEXT
        );
    }
}

/**
 * Tests the import process with a simple case
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_csv_datasource_test extends advanced_testcase {
    /**
     * Test data
     */
    const LOREM_IPSUM = '<p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit '
    . 'anim id est laborum <br/> Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu '
    . 'fugiat nulla pariatur.<br />  Etre capable de se rep&eacute;rer dans un espace clos avec un arthroscope '
    . '<br />  Maitriser la triangulation<br />  Connaitre l&rsquo;organisation pratique de la pr&eacute;paration '
    . 'd&rsquo;une intervention sous arthroscopie.<br />  Connaitre et maitriser les voies '
    . 'd&rsquo;abord arthroscopique de l&rsquo;&eacute;paule du chien<br />  Connaitre et maitriser les '
    . 'voies d&rsquo;abord arthroscopique du coude du chien<br />  Savoir rechercher les diff&eacute;rentes '
    . 'sites d&rsquo;exploration dans l&rsquo;&eacute;paule du chien<br />  Savoir rechercher les '
    . 'diff&eacute;rentes sites d&rsquo;exploration dans le coude du chien<br />  Connaitre les '
    . 'diff&eacute;rentes l&eacute;sions de l&rsquo;&eacute;paule chez le chien<br /> '
    . 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor '
    . 'incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation '
    . 'ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>';

    /**
     * Setup
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        // Create a template course.
        $generator->create_course(['idnumber' => 'templatecourse']);
    }

    /**
     * Test
     */
    public function test_simple_course_csv_import() {
        global $CFG;
        $importer = new course_sample1_csv(
            $CFG->dirroot . '/admin/tool/importer/tests/fixtures/course_sample1.csv');
        $columns = $importer->get_fields_definition();
        $this->assertEquals(
            array(
                'CodeProduit' => 'AC-CHIR-ARTHRO',
                'IntituleProduit' => 'ARTHROSCOPIE DE BASE - ARTHROSCOPIE  DIAGNOSTIC ET CHIRURGICALE DE L\'EPAULE ET DU COUDE '
                    . 'CHEZ LE CHIEN',
                'ResumeProduit' => self::LOREM_IPSUM
            ),
            $importer->current()
        );

        $this->assertEquals(
            array(
                'CodeProduit' => 'AC-CHIR-BRACHY',
                'IntituleProduit' => 'CHIRURGIE DU SYNDROME BRACHYCEPHALE',
                'ResumeProduit' => self::LOREM_IPSUM
            ),
            $importer->next()
        );
        $this->assertEquals(
            array(
                'CodeProduit' => 'AC-CHIR-COUDE',
                'IntituleProduit' => 'PATHOLOGIE ARTICULAIRE DU COUDE CHEZ LES CARNIVORES DOMESTIQUES',
                'ResumeProduit' => self::LOREM_IPSUM
            ),
            $importer->next()
        );
        $this->assertEquals(
            true,
            $importer->valid()
        );
        $this->assertNotNull(
            $importer->next()
        );
        $this->assertEquals(
            false,
            $importer->valid()
        );
    }

    /**
     * Test
     */
    public function test_empty_course_csv_import() {
        global $CFG;
        $importer = new course_sample1_csv(
            $CFG->dirroot . '/admin/tool/importer/tests/fixtures/course_empty.csv');
        $columns = $importer->get_fields_definition();
        $this->assertEquals(
            null,
            $importer->current()
        );
        $this->assertEquals(
            true,
            $importer->valid()
        );
    }

    /**
     * Test
     */
    public function test_iterator_course_csv_import() {
        global $CFG;
        $importer = new course_sample1_csv(
            $CFG->dirroot . '/admin/tool/importer/tests/fixtures/course_sample1.csv');
        $rows = [];
        foreach ($importer as $row) {
            $rows[] = $row;
        }
        $this->assertCount(3, $rows);
        $this->assertEquals(array(
            'CodeProduit' => 'AC-CHIR-ARTHRO',
            'IntituleProduit' => 'ARTHROSCOPIE DE BASE - ARTHROSCOPIE  DIAGNOSTIC ET CHIRURGICALE DE L\'EPAULE ET DU COUDE '
                . 'CHEZ LE CHIEN',
            'ResumeProduit' => self::LOREM_IPSUM
        ), $rows[0]);
    }
}

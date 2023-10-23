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

namespace tool_importer\local\importer;

use core\task\manager;
use core_course\customfield\course_handler;
use dml_exception;
use moodle_exception;
use tool_importer\data_importer;
use tool_importer\field_types;
use tool_importer\local\utils;
use tool_importer\task\course_restore_task;

/**
 * CSV Data source for courses
 *
 * Take a processed row and make it persistent
 *
 * This class will be derived according to the type of data to be imported.
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_data_importer extends data_importer {

    /**
     * @var string $cfprefix
     */
    protected $cfprefix = "";

    /**
     * data_importer constructor.
     *
     * @param mixed $defaultvals additional default values
     * @param string $customfieldsprefix
     * @throws dml_exception
     */
    public function __construct($defaultvals = null, $customfieldsprefix = "cf_") {
        global $DB;
        $defaultcategory = $DB->get_field_select('course_categories', "MIN(id)", "parent=0");
        $this->defaultvalues = [
                'idnumber' => '',
                'format' => 'topics',
                'newsitems' => 0,
                'numsections' => 5,
                'summary' => '',
                'summaryformat' => FORMAT_HTML,
                'category' => $defaultcategory,
                'startdate' => usergetmidnight(time()),
        ];
        if ($defaultvals) {
            $this->defaultvalues = array_merge($this->defaultvalues, $defaultvals);
        }
        $this->cfprefix = $customfieldsprefix;
    }

    /**
     * Check if row is valid before transformation.
     *
     *
     * @param array $row
     * @param int $rowindex
     * @param mixed|null $options import options
     * @throws validation_exception
     */
    public function validate_after_transform($row, $rowindex, $options = null) {
        $transformedfields = [
                'fullname' => [
                        'type' => field_types::TYPE_TEXT,
                        'required' => true,
                ],
                'shortname' => [
                        'type' => field_types::TYPE_TEXT,
                        'required' => false,
                ],
                'idnumber' => [
                        'type' => field_types::TYPE_TEXT,
                        'required' => false,
                ],
                'format' => [
                        'type' => field_types::TYPE_TEXT,
                        'required' => false,
                ],
                'newsitems' => [
                        'type' => field_types::TYPE_INT,
                        'required' => false,
                ],
                'numsections' => [
                        'type' => field_types::TYPE_INT,
                        'required' => false,
                ],
                'summary' => [
                        'type' => field_types::TYPE_TEXT,
                        'required' => false,
                ],
                'summaryformat' => [
                        'type' => field_types::TYPE_INT,
                        'required' => false,
                ],
                'category' => [
                        'type' => field_types::TYPE_INT,
                        'required' => false,
                ],
                'startdate' => [
                        'type' => field_types::TYPE_INT,
                        'required' => false,
                ],
                'templatecourseidnumber' => [
                        'type' => field_types::TYPE_TEXT,
                        'required' => false,
                ],
        ];
        $this->validate_from_field_definition($transformedfields, $row, $rowindex);
    }

    /**
     * Update or create a course
     *
     * @param array $row associative array storing the record
     * @param int $rowindex
     * @param mixed|null $options import options
     * @return mixed|void
     * @throws importer_exception
     */
    protected function raw_import($row, $rowindex, $options = null) {
        global $DB;
        $existingcourse = !empty($row['idnumber']) && (
                $DB->record_exists('course', ['idnumber' => $row['idnumber']]));
        $course = null;
        if ($existingcourse) {
            $course = $DB->get_record('course', ['idnumber' => $row['idnumber']]);
            $this->update_course($row, $course);
        } else {
            $course = $this->create_course($row);
        }

        // Now the customfields.
        $handler = course_handler::create($course->id);
        $coursedatafields = $handler->get_instance_data($course->id, true);
        $context = $handler->get_instance_context($course->id);
        foreach ($row as $col => $value) {
            if (strncmp($col, $this->cfprefix, strlen($this->cfprefix)) === 0) {
                $cfname = substr($col, strlen($this->cfprefix));
                foreach ($coursedatafields as $fid => $datafield) {
                    $field = $datafield->get_field();
                    if ($field->get('shortname') == $cfname) {
                        // If select or multiselect, then the value is an integer.
                        // Here we try to match without case to avoid issue with bad input data.
                        if (method_exists($field, 'get_options_array')) {
                            $optionarray = $field::get_options_array($field);
                            $indexvalue = 0;
                            foreach ($optionarray as $index => $optionvalue) {
                                if (utils::compare_ws_accents($value, $optionvalue) === 0) {
                                    $indexvalue = $index;
                                    break;
                                }
                            }
                            $value = $indexvalue;
                        }
                        $datafield->set('value', $value);
                        $datafield->set($datafield->datafield(), $value);
                        $datafield->set('contextid', $context->id);
                        $datafield->save();
                        $course->{'cf_' . $field->get('shortname')} = $value; // We augment the course object with customfields.
                    }
                }
            }
        }
        return $course;
    }

    /**
     * Update existing course
     *
     * @param object $record
     * @param object $existingrecord
     * @return object $course
     * @throws moodle_exception
     */
    protected function update_course($record, $existingrecord) {
        global $CFG;
        require_once("$CFG->dirroot/course/lib.php");
        $this->set_default_values($record);
        $record = array_merge((array) $existingrecord, $record); // Add the recordid and other set records.
        update_course((object) $record);
        $this->restore_from_template_course($record, $existingrecord);
        return (object) $record;
    }

    /**
     * Set default values for record
     *
     * @param object $record
     * @throws dml_exception
     */
    protected function set_default_values(&$record) {
        $defaults = $this->defaultvalues;
        $defaults['shortname'] = empty($record['fullname']) ? '' :
                strtoupper(preg_replace('/[\s\W]+/', '', $record['fullname']));
        $record = array_merge($defaults, $record);
    }

    /**
     * Restore from a given course template
     *
     * @param object $record
     * @param object $course
     * @throws moodle_exception
     */
    protected function restore_from_template_course($record, $course) {
        global $DB, $CFG;
        // Restore the template course if it exists.
        if (!empty($record['templatecourseidnumber'])) {
            $templatecourse = $DB->get_record('course', ['idnumber' => $record['templatecourseidnumber']]);
            // TODO: use an adhoc task to do that.
            if ($templatecourse) {
                $courserestoretask = new course_restore_task();
                $courserestoretask->set_custom_data([
                        'templatecourseid' => $templatecourse->id,
                        'courseid' => $course->id,
                ]);
                $courserestoretask->set_userid(get_admin()->id);
                manager::queue_adhoc_task($courserestoretask);
            }
        }
    }

    /**
     * Create course
     *
     * @param object $record
     * @return object $course
     * @throws moodle_exception
     */
    protected function create_course($record) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/course/lib.php");
        $this->set_default_values($record);
        $course = create_course((object) $record);
        $this->restore_from_template_course($record, $course);
        return $course;
    }

}

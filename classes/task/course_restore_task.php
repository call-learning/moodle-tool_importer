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

namespace tool_importer\task;
/**
 * Adhoc task to import a course
 *
 * As this can be a long process, this is better to use an adhoc task
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_restore_task extends \core\task\adhoc_task {
    /**
     * Execute task
     */
    public function execute() {
        global $CFG;
        $coursedata = $this->get_custom_data();
        require_once($CFG->dirroot . '/course/externallib.php');
        \core_course_external::import_course($coursedata->templatecourseid, $coursedata->courseid);
    }
}

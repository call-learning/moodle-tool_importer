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
namespace tool_importer\local\logs;

use core\persistent;

/**
 * Class import logger
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface import_logger {
    /**
     * From generic exception
     *
     * @param \moodle_exception $e
     * @param array $overrides contains at least the values for importid, level and module
     * @return import_log_entity
     */
    public function log_from_exception(\moodle_exception $e, array $overrides);

    /**
     * Get logs from filters
     *
     * @param array $filters
     * @return persistent[]
     * @throws \coding_exception
     */
    public function get_logs($filters = []);

    /**
     * Get related persistent class
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_log_persistent_class();
}

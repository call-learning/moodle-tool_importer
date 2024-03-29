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
 * Data processor Interface.
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_importer;

/**
 * Data processor class.
 *
 * Take a processed row and make it persistent
 *
 * This class will be derived according to the type of data to be imported.
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface data_processor_mgmt_interface {
    /**
     * Set processor
     *
     * @param processor $processor
     */
    public function set_processor(processor $processor);

    /**
     * Get import identifier
     *
     * @return processor
     */
    public function get_processor();

    /**
     * Get import identifier helper
     *
     * @return int
     */
    public function get_import_id();

    /**
     * Get related data source
     */
    public function get_source();

    /**
     * Get related data transformer
     */
    public function get_transformer();

    /**
     * Get related data importer
     */
    public function get_importer();

    /**
     * Get related data importer
     */
    public function get_logger();
}

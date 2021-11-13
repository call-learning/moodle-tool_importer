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

namespace tool_importer;
defined('MOODLE_INTERNAL') || die();

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field_types  {
    /**
     * Type INT / Numeric
     */
    const TYPE_INT = 1;
    /**
     * Type Text
     */
    const TYPE_TEXT = 3;

    /**
     * Check if it is a valid value for this given type
     *
     * @param int|string|object $value
     * @param int $type
     * @return bool
     */
    public static function is_valid($value, $type) {
        switch($type) {
            case self::TYPE_INT:
                return is_numeric($value);
            case self::TYPE_TEXT:
                return is_string($value);
        }
    }
}

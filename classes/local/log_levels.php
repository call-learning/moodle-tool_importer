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
 * Local utils function for import routines and other.
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_importer\local;

use coding_exception;
use lang_string;

/**
 * Class log_levels
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class log_levels {
    /**
     * Info
     */
    const LEVEL_INFO = 0;
    /**
     * Warning
     */
    const LEVEL_WARNING = 1;
    /**
     * Error : this should stop all import or validation process.
     */
    const LEVEL_ERROR = 2;

    /**
     * Match for level and short name
     */
    const LEVEL_TO_SN = [
            self::LEVEL_INFO => 'none',
            self::LEVEL_ERROR => 'error',
            self::LEVEL_WARNING => 'warning'
    ];

    /**
     * Convert level name to level number
     *
     * @param string $levelname
     * @return int|string|null
     */
    public static function convert_level_name_to_level_number(string $levelname) {
        $converter = array_flip(static::LEVEL_TO_SN);
        $levelclean = trim(strtolower($levelname));
        return $converter[$levelclean] ?? null;
    }

    /**
     * Human-readable version of this level
     *
     * @param int $level
     * @param string $module
     * @return lang_string|string
     * @throws coding_exception
     */
    public static function to_displayable_string(int $level, $module = 'tool_importer') {
        $levelsn = self::LEVEL_TO_SN[$level] ?? '';
        return get_string('log:level:' . $levelsn, $module);
    }
}

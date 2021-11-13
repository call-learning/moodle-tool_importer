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

namespace tool_importer\local;

use core_text;
use tool_importer\data_source;

/**
 * Local utils function for import routines and other.
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Remove accentuated character from a given string
     *
     * @param string $input
     * @return string
     */
    public static function translate_ascii($input) {
        return core_text::convert($input, 'utf-8', 'ascii');
    }

    /**
     * Compare two strings without space, on a lower case basis and without accentuated chars
     *
     * @param string $s1 first string
     * @param string $s2 second string
     * @return int  <0 if $s1 < $s2, >0 if $s1 > $s2 and 0 if $s1 = $s2
     */
    public static function compare_ws_accents($s1, $s2) {
        $s1 = str_replace(' ', '', static::translate_ascii($s1));
        $s2 = str_replace(' ', '', static::translate_ascii($s2));
        return strcasecmp($s1, $s2);
    }
}

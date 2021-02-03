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
 * Data processor class.
 *
 *
 * Transfor a give row of data
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_importer\transformer;

use core_customfield\data;
use stdClass;
use tool_importer\data_transformer;

defined('MOODLE_INTERNAL') || die();

/**
 * Class data_transformer
 *
 * Transform a row of data in another row of data.
 *
 * @package tool_importer
 */
class standard extends data_transformer {
    /**
     * Return the transformed row depending on the field transformer values.
     *
     * @param array $row an associative array (column => value)
     * @return mixed
     */
    public function transform($row, $concatseparator = ' ') {
        $resultrow = [];
        foreach ($row as $fieldname => $fieldvalue) {
            $targetfieldname = $fieldname;
            $order = 0;
            // It might return several values that will later be concatenated.
            $value = $fieldvalue;
            if (!empty($this->fieldtransformerdef[$fieldname])) {
                // There is a transformation available.
                $transformdefs = $this->fieldtransformerdef[$fieldname];
                foreach ($transformdefs as $tdef) {
                    if (!empty($tdef['to'])) {
                        $targetfieldname = $tdef['to'];
                    }
                    if (!empty($tdef['concatenate'])) {
                        $order = empty($tdef['concatenate']['order']) ? 0 : $tdef['concatenate']['order'];
                    }
                    if (!empty($tdef['transformcallback'])) {
                        $callback = $tdef['transformcallback'];
                        if (function_exists($callback)) {
                            $value = call_user_func($callback, $value, $fieldname); // This can be an array if needed.
                        }
                    }
                    if (empty($resultrow[$targetfieldname])) {
                        $resultrow[$targetfieldname] = [];
                    }
                    $resultrow[$targetfieldname][$order] = $value;
                }
            } else {
                $resultrow[$targetfieldname][$order] = $value;
            }

        }
        // Now flatten the result.
        $flatresult = [];
        foreach ($resultrow as $fieldname => $fieldvalues) {
            if (count($fieldvalues) > 1) {
                ksort($fieldvalues);
                $flatresult[$fieldname] = implode($concatseparator, $fieldvalues);
            } else {
                $flatresult[$fieldname] = reset($fieldvalues);
            }
        }
        return $flatresult;
    }
}
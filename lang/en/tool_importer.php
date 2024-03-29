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
 * Plugin strings are defined here.
 *
 * @package     tool_importer
 * @category    string
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Data Importer';
$string['process:progress'] = 'Processing import';
$string['log:level:warning'] = 'Warning';
$string['log:level:error'] = 'Error';
$string['log:level:info'] = 'Info';
$string['importlog:message']  = '{$a->level} (Line {$a->line}, Field:{$a->fieldname}): {$a->message}';
$string['columnmissing'] = 'Column missing';
$string['csvimporteriniterror'] = 'Intial importer error ({$a}).';
$string['wrongtype'] = 'Wrong type for field.';

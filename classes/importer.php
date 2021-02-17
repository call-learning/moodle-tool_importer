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
 * A generic importer class
 *
 * Built from datasource, transformer and importer.
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_importer;

use progress_bar;
use text_progress_trace;

defined('MOODLE_INTERNAL') || die();

/**
 * Class importer
 *
 * Basic importer routine
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importer {
    /**
     * @var data_source
     */
    protected $source;
    /**
     * @var data_transformer
     */
    protected $transformer;
    /**
     * @var data_importer
     */
    protected $importer;
    /**
     * @var mixed|null $progressbar
     */
    protected $progressbar;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * Omporter constructor.
     *
     * @param data_source $source
     * @param data_transformer $transformer
     * @param data_importer $importer
     */
    public function __construct(data_source $source, data_transformer $transformer, data_importer $importer, $progressbar = null) {
        $this->source = $source;
        $this->source->rewind();
        $this->transformer = $transformer;
        $this->importer = $importer;
        $this->progressbar = $progressbar;
    }

    /**
     * Import the whole set of entities
     */
    public function import() {
        $this->errors = [];
        $rowcount = $this->source->get_total_row_count();
        foreach ($this->source as $rowindex => $row) {
            $transformedrow = $this->transformer->transform($row);
            if ($this->progressbar) {
                if ($this->progressbar instanceof progress_bar) {
                    $this->progressbar->update(
                        $rowindex,
                        $rowcount,
                        get_string('process:progress', 'tool_importer'));
                }
                if ($this->progressbar instanceof text_progress_trace) {
                    $this->progressbar->output("$rowindex/$rowcount");
                }
            }
            $errors = $this->importer->validate($transformedrow, $rowindex);
            if (empty($errors)) {
                $this->importer->import_row($transformedrow);
            } else {
                $this->errors = array_merge($this->errors, $errors);
            }
        }
        return empty($this->errors);
    }

    /**
     * Get errors
     *
     * @return array of error with line, field and error code info.
     */
    public function get_errors() {
        return $this->errors;
    }

}
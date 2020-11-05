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
    protected $source;
    protected $transformer;
    protected $importer;

    /**
     * Omporter constructor.
     *
     * @param data_source $source
     * @param data_transformer $transformer
     * @param data_importer $importer
     */
    public function __construct(data_source $source, data_transformer $transformer, data_importer $importer) {
        $this->source = $source;
        $this->source->rewind();
        $this->transformer = $transformer;
        $this->importer = $importer;
    }

    /**
     * Import the whole set of entities
     */
    public function import() {
        foreach ($this->source as $row) {
            $transformedrow = $this->transformer->transform($row);
            if ($this->importer->check_row($transformedrow)) {
                $this->importer->import_row($transformedrow);
            }
        }
    }

}
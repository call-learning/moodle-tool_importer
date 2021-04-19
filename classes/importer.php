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
use tool_importer\local\import_log;

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
     * @var string $module
     */
    protected $module = 'tool_importer';
    /**
     * @var int number of imported rows (reset for each importation)
     */
    protected $rowimported = 0;

    /**
     * Importer constructor.
     *
     * @param data_source $source
     * @param data_transformer $transformer
     * @param data_importer $importer
     * @param progress_bar $progressbar
     * @param int $importid  (null if not set)
     * @param string $importlogclass
     */
    public function __construct(
        data_source $source,
        data_transformer $transformer,
        data_importer $importer,
        $progressbar = null,
        $importid = null,
        $importlogclass = null
    ) {
        $this->source = $source;
        $this->source->rewind();
        if ($importid != null) {
            $transformer->set_import_id($importid);
            $importer->set_import_id($importid);
        }
        $this->transformer = $transformer;
        $this->importer = $importer;
        $this->progressbar = $progressbar;
        $this->importer->set_related_source($source);

    }

    /**
     * Import the whole set of entities
     */
    public function import() {
        $rowcount = $this->source->get_total_row_count();
        $this->rowimported = 0;
        $haserrors = false;
        foreach ($this->source as $rowindex => $row) {
            try {
                $this->importer->fix_before_transform($row, $rowindex);
                $transformedrow = $this->transformer->transform($row);
                $errors = $this->importer->validate($transformedrow, $rowindex);
                if (empty($errors)) {
                    $this->importer->import_row($transformedrow, $rowindex);
                    $this->rowimported++;
                }
                $this->update_progress_bar($this->rowimported, $rowcount);
            } catch (\moodle_exception $e) {
                $haserrors = true;
                import_log::new_log($rowindex,
                    'exception',
                    $e->getMessage(),
                    import_log::LEVEL_ERROR,
                    '',
                    $this->module,
                    $this->source->get_source_type() . ':' . $this->source->get_source_identifier(),
                    $this->importer->get_import_id());
            }
        }
        return $haserrors;
    }

    protected function update_progress_bar($rowindex, $rowcount) {
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
    }
    /**
     * Get total rows
     *
     * @return mixed
     */
    public function get_total_row_count() {
        return $this->source->get_total_row_count();
    }

    /**
     * Get total rows
     *
     * @return mixed
     */
    public function get_row_imported_count() {
        return $this->rowimported;
    }

    public function set_module($modulename='tool_importer') {
        $this->module = $modulename;
    }

    /**
     * @return data_importer
     */
    public function get_data_importer() {
        return $this->importer;
    }
}
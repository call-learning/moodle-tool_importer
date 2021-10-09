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
use tool_importer\local\validation_log;

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
     * @var $validationtempfile null Validation temporary file. This is necessary as validation relies on
     * transaction, so we need to use another mean of storage.
     */
    protected $validationtempfile = null;

    /**
     * Importer constructor.
     *
     * @param data_source $source
     * @param data_transformer $transformer
     * @param data_importer $importer
     * @param progress_bar $progressbar
     * @param int $importid (null if not set)
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
        $tempfolder = make_temp_directory('tool_importer');
        $this->validationtempfile = $tempfolder . '/' . rand();
    }

    /**
     * Import the whole set of entities
     */
    public function import() {
        $rowcount = $this->source->get_total_row_count();
        $this->rowimported = 0;
        $haserrors = false;
        $rowindex = 0;
        while ($this->source->valid()) {
            try {
                $row = $this->source->current();
                $this->importer->fix_before_transform($row, $rowindex);
                $transformedrow = $this->transformer->transform($row);
                $this->importer->validate($transformedrow, $rowindex);
                $this->importer->import_row($transformedrow, $rowindex);
                $this->rowimported++;
                $this->update_progress_bar($this->rowimported, $rowcount);
            } catch (validation_exception $e) {
                $haserrors = true;
                $log = import_log::from_importer_exception($e, $this->source, $this->importer->get_import_id());
                $log->create();
            } catch (\Exception $e) {
                $haserrors = true;
                $log = import_log::from_generic_exception($e, $rowindex, $this->module, $this->source,
                    $this->importer->get_import_id());
                $log->create();
            } finally {
                $rowindex++;
                $this->source->next(); // This can lead to an exception here.
            }
        }
        return $haserrors;
    }

    /**
     * Validate the rows
     *
     * @throws \dml_transaction_exception if stansaction active
     */
    public function validate() {
        $haserrors = false;
        $rowindex = 0;
        while ($this->source->valid()) {
            try {
                $row = $this->source->current();
                $this->importer->fix_before_transform($row, $rowindex);
                $transformedrow = $this->transformer->transform($row);
                $this->importer->validate($transformedrow, $rowindex);
            } catch (validation_exception $e) {
                $haserrors = true;
                $log = validation_log::from_importer_exception($e, $this->source);
                $log->create();
            } finally {
                $rowindex++;
                $this->source->next(); // This can lead to an exception here.
            }
        }
        return $haserrors;
    }

    /**
     * Update progressbar
     *
     * @param int $rowindex
     * @param int $rowcount
     * @throws \coding_exception
     */
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

    /**
     * Set related module
     *
     * @param string $modulename
     */
    public function set_module($modulename = 'tool_importer') {
        $this->module = $modulename;
    }

    /**
     * Get the related data importer
     *
     * @return data_importer
     */
    public function get_data_importer() {
        return $this->importer;
    }
}

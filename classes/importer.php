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
        $this->importer->set_import_mode();
        $haserrors = $this->do_import();
        return $haserrors;
    }

    /**
     * Validate the rows.
     *
     * The validation log is purged before we start the validation process
     * TODO: deal with concurrency.
     *
     * @throws \dml_transaction_exception if stansaction active
     */
    public function validate() {
        $this->purge_validation_log();
        $this->importer->set_validation_mode();
        try {
            $haserrors = $this->do_import();
            $this->source->rewind();
        } catch (\moodle_exception $e) {
            $log = import_log::from_exception($e, [
                'linenumber' => 0,
                'module' => $this->module,
                'origin' => $this->source->get_origin(),
                'importid' => $this->importer->get_import_id()
            ]);
            $log->set('validationstep', !$this->importer->is_import_mode());
            $log->create();
            $haserrors = true;
        }

        return $haserrors;
    }

    /**
     * Import the whole set of entities or just validate, depending on the mode we are in.
     */
    protected function do_import() {
        $this->reset_row_imported();
        $haserrors = false;
        $rowindex = 0;
        $this->source->init_and_check();
        $this->source->rewind();
        $this->importer->init();
        while ($this->source->valid()) {
            try {
                $row = $this->source->current();
                $this->importer->fix_before_transform($row, $rowindex);
                $this->importer->validate_before_transform($row, $rowindex);
                $transformedrow = $this->transformer->transform($row);
                $this->importer->validate_after_transform($transformedrow, $rowindex);
                $this->importer->import_row($transformedrow, $rowindex);
                $this->increment_row_imported();
                $this->update_progress_bar($this->rowimported);
            } catch (\moodle_exception $e) {
                $haserrors = true;
                $log = import_log::from_exception($e, [
                    'linenumber' => $rowindex,
                    'module' => $this->module,
                    'origin' => $this->source->get_origin(),
                    'importid' => $this->importer->get_import_id()
                ]);
                $log->set('validationstep', !$this->importer->is_import_mode());
                $log->create();
            } finally {
                $rowindex++;
                $this->source->next(); // This can lead to an exception here.
            }
        }
        return $haserrors;
    }

    /**
     * Purge validation log.
     *
     * Called automatically when we validate an import
     *
     * @throws \dml_exception
     */
    public function purge_validation_log() {
        $allvalidationlogs = import_log::get_records(['validationstep' => 1, 'importid' => $this->importer->get_import_id()]);
        foreach ($allvalidationlogs as $log) {
            $log->delete();
        }
    }

    /**
     * Get validation errors
     *
     * @return import_log[]
     */
    public function get_validation_log() {
        return import_log::get_records(['validationstep' => 1, 'importid' => $this->importer->get_import_id()]);
    }

    /**
     * Update progressbar
     *
     * @param int $rowindex
     * @throws \coding_exception
     */
    protected function update_progress_bar($rowindex) {
        $rowcount = $this->source->get_total_row_count();
        if ($this->progressbar && $this->importer->is_import_mode()) {
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
     * Get total rows
     *
     * @return mixed
     */
    protected function increment_row_imported() {
        if ($this->importer->is_import_mode()) {
            $this->rowimported++;
        }
    }

    /**
     * Get total rows
     *
     * @return mixed
     */
    protected function reset_row_imported() {
        if ($this->importer->is_import_mode()) {
            $this->rowimported = 0;
        }
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

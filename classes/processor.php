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
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_importer;
defined('MOODLE_INTERNAL') || die();

use core\persistent;
use progress_bar;
use text_progress_trace;
use tool_importer\local\logs\basic_import_logger;
use tool_importer\local\logs\import_logger;

/**
 * Class importer
 *
 * Basic importer routine
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class processor {
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
     * @var int $importexternalid
     */
    private $importexternalid = 0;

    /**
     * Importer constructor.
     *
     * @param data_source $source
     * @param data_transformer $transformer
     * @param data_importer $importer
     * @param progress_bar $progressbar
     * @param int $importid (null if not set)
     * @param import_logger $importlogger
     * @param string $module
     */
    public function __construct(
        data_source $source,
        data_transformer $transformer,
        data_importer $importer,
        $progressbar = null,
        $importid = 0,
        $importlogger = null,
        $module = 'tool_importer'
    ) {
        $this->importexternalid = $importid;
        $this->source = $source;
        $this->transformer = $transformer;
        $this->importer = $importer;
        $this->progressbar = $progressbar;
        $this->transformer->set_processor($this);
        $this->source->set_processor($this);
        $this->importer->set_processor($this);
        $this->module = $module;
        if (empty($importlogger)) {
            $this->importlogger = new basic_import_logger();
        }
    }

    /**
     * Import the whole set of entities
     *
     * @param mixed|null $options additional importer options
     * @return bool true when ok, false when error
     */
    public function import($options = null) {
        $this->importer->set_import_mode();
        $haserrors = $this->do_import($options);
        return !$haserrors;
    }

    /**
     * Validate the rows.
     *
     * @param mixed $options
     *
     * The validation log is purged before we start the validation process
     * TODO: deal with concurrency.
     *
     * @return bool true when valid, false when invalid
     */
    public function validate($options = null) {
        $this->purge_validation_logs();
        $this->importer->set_validation_mode();
        try {
            $haserrors = $this->do_import($options);
            $this->source->rewind();
        } catch (\moodle_exception $e) {
            $log = $this->importlogger->log_from_exception($e, [
                'linenumber' => 0,
                'module' => $this->module,
                'origin' => $this->source->get_origin(),
                'importid' => $this->importer->get_import_id()
            ]);
            $log->set('validationstep', !$this->importer->is_import_mode());
            $log->create();
            $haserrors = true;
        }

        return !$haserrors;
    }

    /**
     * Real Import routine
     *
     * @param mixed $options
     * Import the whole set of entities or just validate, depending on the mode we are in.
     * @return bool
     */
    protected function do_import($options = null) {
        $this->reset_row_imported();
        $haserrors = false;
        $rowindex = 0;
        $this->source->init_and_check($options);
        $this->source->rewind();
        $this->importer->init($options);
        while ($this->source->valid()) {
            try {
                $row = $this->source->current();
                $this->importer->fix_before_transform($row, $rowindex, $options);
                $this->importer->validate_before_transform($row, $rowindex, $options);
                $transformedrow = $this->transformer->transform($row, $options);
                $this->importer->validate_after_transform($transformedrow, $rowindex, $options);
                $this->importer->import_row($transformedrow, $rowindex, $options);
                $this->increment_row_imported();
                $this->update_progress_bar($this->rowimported);
            } catch (\moodle_exception $e) {
                $haserrors = true;
                $log = $this->importlogger->log_from_exception($e, [
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
    public function purge_validation_logs() {
        $allvalidationlogs = $this->importlogger->get_logs(['validationstep' => 1,
            'importid' => $this->importer->get_import_id()]);
        foreach ($allvalidationlogs as $log) {
            $log->delete();
        }
    }

    /**
     * Get validation errors
     *
     * @return persistent[]
     */
    public function get_validation_log() {
        return $this->importlogger->get_logs(['validationstep' => 1,
            'importid' => $this->importer->get_import_id()]);
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
     * Get statistics in a displayable (HTML) format
     * @return string
     */
    public function get_displayable_stats() {
        return '';
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
     * Get related module name
     *
     * @return mixed|string
     */
    public function get_module() {
        return $this->module;
    }

    /**
     * Get the related data importer
     *
     * @return data_importer
     */
    public function get_importer() {
        return $this->importer;
    }

    /**
     * Get the related data source
     *
     * @return data_source
     */
    public function get_source() {
        return $this->source;
    }

    /**
     * Get the related data transformer
     *
     * @return data_transformer
     */
    public function get_transformer() {
        return $this->transformer;
    }

    /**
     * Get import identifier
     *
     * @return int
     */
    public function get_import_id() {
        return $this->importexternalid;
    }

    /**
     * Get import identifier
     *
     */
    public function get_logger() {
        return $this->importlogger;
    }
}

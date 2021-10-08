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
 * CSV Data source for courses
 *
 * Take a processed row and make it persistent
 *
 * This class will be derived according to the type of data to be imported.
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_importer\local\source;

use core\session\exception;
use csv_import_reader;
use tool_importer\data_source;
use tool_importer\importer_exception;
use tool_importer\local\utils;
use tool_importer\validation_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Class csv_data_source
 *
 * @package     tool_importer
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class csv_data_source extends data_source {

    /**
     * @var csv_import_reader|null
     */
    protected $csvimporter = null;

    /**
     * @var string $csvfilepath current file path
     */
    protected $csvfilepath = null;

    /**
     * @var int $currentrow
     */
    protected $currentrow = 0;

    /**
     * Is inited
     * @var bool
     */
    protected $isinited = false;

    /**
     * Total number of rows.
     * @var int
     */
    protected $rowcount = 0;

    /**
     * CSV columns in the right order
     * @var array
     */
    protected $csvcolumns = [];

    /**
     * csv_data_source constructor.
     *
     * @param $csvfilepath
     * @param string $separator
     * @param string $encoding
     * @param string $encoding
     * @param bool $exactcolumnname should the column name be compared on an exact basis (space and accent)
     * @throws importer_exception
     */
    public function __construct($csvfilepath, $separator = 'semicolon', $encoding = 'utf-8', $exactcolumnname=false ) {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');
        $importid = csv_import_reader::get_new_iid('upload_course_datasource');
        if (!is_file($csvfilepath)) {
            throw new importer_exception('cannotopencsvfile', 'tool_importer', $csvfilepath);
        }
        $this->csvfilepath = $csvfilepath;
        $this->csvimporter = new csv_import_reader($importid, 'upload_course_datasource');
        $content = file_get_contents($csvfilepath);
        if (!mb_detect_encoding($content, $encoding, true)) {
            throw new importer_exception('wrongencoding', 'tool_importer', (object) ['file' => $csvfilepath, 'expected'=> 'utf-8']);
        }
        $this->rowcount = $this->csvimporter->load_csv_content($content, $encoding, $separator);
        $this->rowcount = ($this->rowcount>0) ? $this->rowcount - 1: 0; // Row count minus header.
        $csvheaders = $this->csvimporter->get_columns();
        if (!$csvheaders) {
            throw new importer_exception('nocolumnsdefined', 'tool_importer', $this->csvfilepath);
        }
        foreach ($this->get_fields_definition() as $colname => $definition) {
            $found = false;
            foreach($csvheaders as $colheadername) {
                if (!$exactcolumnname && utils::compare_ws_accents(trim($colname), trim($colheadername)) === 0) {
                    $found = true;
                } else if ($exactcolumnname && $colname == $colheadername) {
                    $found = true;
                }
            }
            if (!$found  && !empty($definition['required'])) {
                throw new importer_exception('columnmissing', 'tool_importer', $colname);
            }
            $this->csvcolumns[] = $colname;
        }
    }

    /**
     * Get current value
     *
     * @return mixed|null
     */
    public function current() {
        if (!$this->isinited) {
            $this->rewind();
            $this->isvalid = true;
        }
        return $this->currentvalue;
    }

    /**
     * Get next element
     *
     * @return false|mixed
     */
    protected function retrieve_next_value() {
        $cval = $this->csvimporter->next();
        if (!$cval) {
            $this->isvalid = false;
        } else {
            $this->currentvalue = $this->get_associated_array($cval);
            $this->currentrow++;
        }
        return $this->currentvalue;
    }

    /**
     * Get associated array from current value
     *
     * @param $cval
     * @return array
     */
    protected function get_associated_array($cval) {
        if (count($cval) != count($this->csvcolumns)) {
            throw new \moodle_exception('wrongcolumnnumber', 'local_importer');
        }
        $value = array_combine($this->csvcolumns, $cval);
        $required = $this->get_fields_definition();
        return array_intersect_key($value, $required);
    }

    /**
     * Current key (row)
     *
     * @return bool|float|int|string|null
     */
    public function key() {
        return $this->currentrow;
    }

    /**
     * Check if a value has been retrieved and there is no error
     *
     * @return bool
     */
    public function valid() {
        return parent::valid() && (empty($this->csvimporter->get_error()));
    }

    /**
     * Replace the iterator on the first element
     */
    public function rewind() {
        $this->csvimporter->init();
        $this->isinited = true;
        if ($this->csvimporter->get_error()) {
            throw new importer_exception('csvimporteriniterror',
                'tool_importer', $this->csvimporter->get_error());
        }
        $this->currentvalue = $this->retrieve_next_value();
    }

    /**
     * Make sure the file is closed when this object is discarded.
     */
    public function __destruct() {
        $this->csvimporter->cleanup();
        $this->csvimporter->close();
    }

    /**
     * Get the total number of records
     *
     * @return mixed
     */
    public function get_total_row_count() {
        return $this->rowcount;
    }

    /**
     * Get source type
     *
     * @return string
     */
    public function get_source_type() {
        return 'file';
    }

    /**
     * Get source identifier
     *
     * @return string|null
     */
    public function get_source_identifier() {
        return $this->csvfilepath;
    }
}
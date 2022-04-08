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

namespace tool_importer\local\source;

use csv_import_reader;
use tool_importer\data_source;
use tool_importer\local\exceptions\importer_exception;
use tool_importer\local\log_levels;
use tool_importer\local\utils;

/**
 * CSV Data source for courses
 *
 * Take a processed row and make it persistent
 *
 * This class will be derived according to the type of data to be imported.
 *
 * @package     tool_importer
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
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
     *
     * @var bool
     */
    protected $isinited = false;

    /**
     * Total number of rows.
     *
     * @var int
     */
    protected $rowcount = 0;

    /**
     * CSV columns in the right order
     *
     * @var array
     */
    protected $csvcolumns = [];

    /**
     * Required CSV columns
     *
     * @var array
     */
    protected $requiredcolumns = [];
    /**
     * @var string $separator character
     */
    protected $separator;
    /**
     * @var string $encoding encoding
     */
    private $encoding;
    /**
     * @var bool $exactcolumnname should we check on an exact basis or we consider that a column name is the same without
     * looking at accents or spaces.
     */
    private $exactcolumnname;
    /**
     * @var string $originalfilename the original filename, used when temp files are created to load the file from draft area.
     */
    private $originalfilename;

    /**
     * csv_data_source constructor.
     *
     * @param string $csvfilepath
     * @param string $separator
     * @param string $encoding
     * @param string $originalfilename
     * @param bool $exactcolumnname should the column name be compared on an exact basis (space and accent)
     */
    public function __construct($csvfilepath, $separator = 'semicolon', $encoding = 'utf-8', $originalfilename = '',
            $exactcolumnname = false) {
        $this->csvfilepath = $csvfilepath;
        $this->separator = $separator;
        $this->encoding = $encoding;
        $this->exactcolumnname = $exactcolumnname;
        $this->originalfilename = $originalfilename;
    }

    /**
     * Initialise the csv datasource.
     *
     * This will initialise the current source. This has to be called before we call current or rewind.
     *
     * @param mixed|null $options additional importer options
     * @throws importer_exception
     */
    public function init_and_check($options = null) {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');
        $importid = csv_import_reader::get_new_iid('upload_course_datasource');
        if (!is_file($this->csvfilepath)) {
            throw new importer_exception('cannotopencsvfile',
                    -1,
                    '',
                    'tool_importer',
                    $this->csvfilepath,
                    log_levels::LEVEL_ERROR
            );
        }
        if (empty($this->csvimporter)) {
            $this->csvimporter = new csv_import_reader($importid, 'upload_course_datasource');
        }
        $this->csvimporter->init($options);
        $content = file_get_contents($this->csvfilepath);
        if (!mb_detect_encoding($content, $this->encoding, true)) {
            throw new importer_exception('wrongencoding',
                    -1,
                    '',
                    'tool_importer',
                    (object) ['expected' => $this->encoding, 'actual' => mb_detect_encoding($content)],
                    log_levels::LEVEL_ERROR);
        }
        $this->rowcount = $this->csvimporter->load_csv_content($content, $this->encoding, $this->separator);
        $this->rowcount = ($this->rowcount > 0) ? $this->rowcount - 1 : 0; // Row count minus header.
        $csvheaders = $this->csvimporter->get_columns();
        if (!$csvheaders) {
            throw new importer_exception('nocolumnsdefined', -1, '', 'tool_importer', '', log_levels::LEVEL_ERROR);
        }
        $this->setup_fields_before_defintion($csvheaders);
        foreach ($this->get_fields_definition() as $colname => $definition) {
            $foundindex = -1;
            foreach ($csvheaders as $csvheaderindex => $colheadername) {
                if (!$this->exactcolumnname && utils::compare_ws_accents(trim($colname), trim($colheadername)) === 0) {
                    $foundindex = $csvheaderindex;
                } else if ($this->exactcolumnname && $colname == $colheadername) {
                    $foundindex = $csvheaderindex;
                }
            }
            if ($foundindex == -1 && !empty($definition['required'])) {
                throw new importer_exception('columnmissing', -1, $colname, 'tool_importer', '', log_levels::LEVEL_ERROR);
            }
            if (!empty($definition['required'])) {
                $this->requiredcolumns[$foundindex] = $colname;
            }
            $this->csvcolumns[$foundindex] = $colname;
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
     * @param mixed $cval
     * @return array
     */
    protected function get_associated_array($cval) {
        $columns = $this->csvcolumns;
        if (count($cval) < count($this->requiredcolumns)) {
            throw new \moodle_exception('notenoughcolumns', 'local_importer');
        }
        $rowvals = [];
        foreach ($columns as $index => $colname) {
            if (isset($cval[$index])) {
                $rowvals[$colname] = $cval[$index];
            }
        }
        return $rowvals;
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
        if (empty($this->csvimporter)) {
            $this->init_and_check();
        }
        $this->csvimporter->init();
        $this->isinited = true;
        $this->isvalid = true;
        if ($this->csvimporter->get_error()) {
            throw new importer_exception('csvimporteriniterror',
                    $this->currentrow,
                    '',
                    'tool_importer',
                    $this->csvimporter->get_error(),
                    log_levels::LEVEL_ERROR
            );
        }
        $this->currentvalue = $this->retrieve_next_value();
    }

    /**
     * Make sure the file is closed when this object is discarded.
     */
    public function __destruct() {
        if ($this->csvimporter) {
            $this->csvimporter->cleanup();
            $this->csvimporter->close();
        }
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
        return empty($this->originalfilename) ? $this->csvfilepath : $this->originalfilename;
    }
}

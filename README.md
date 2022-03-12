# Data Importer #

[![Test: Status](https://github.com/call-learning/moodle-tool_importer/actions/workflows/ci.yml/badge.svg)](https://github.com/call-learning/moodle-tool_importer/actions/workflows/ci.yml)
[![CI Tests (Linting)](https://github.com/call-learning/moodle-tool_importer/actions/workflows/lint.yml/badge.svg)](https://github.com/call-learning/moodle-tool_importer/actions/workflows/lint.yml)

Generic data importer library. It will for example help to import and sync courses data in a more
configurable way that bulk course import tool.

    * Import from existing database or API
    * Import from CSV (and some day Excel)

The principle is as follow:

For each Row of the input data (we suppose that the input data is obtained row
by row like in a SQL table or CSV file):

                 ---------------                       ----------------        ----------------
    Row Set ===> | data_source |  ===> Single Row ===> | Transformer  |   ===> | Importer     | => Moodle entity (update or create)
                 ---------------                       ----------------        ----------------
    
The tool will allow to configure the source/destination field for each import.
This is right now a work in progress but the idea behind this plugin is:
* To define a set of source for importation
* Configure importation and fields that are imported (and where)
* Define what is happening of the entity does not exist (we update or create ?)
...

### Datasource

The datasource is basically an interator. A sample implementation is given
in the source/csv_datasource.php class. This will allow to read and parse
 a csv file.
The returned row is an associative array with the value of the identified
columns (columns are identified by get_fields_definition()).
For now no check is done on the datatype but it is planned later to have several
checks depending on the given datatype.


### Transformer

The transformer allow to add / transform a column field value. The
transformation definition consist in an associative array indexed by
the datasource column name.
The transformation definition is as follow:
* An associative array of array defining the column toward which we will copy
the original value. For example ['row1'=> [['to'=>'myrow'], ['to'=> 'myrow2']], will copy
value of row1 into two new row myrow and myrow2.
* We can add new parameters, for example 'transformcallback' is the name of an
accessible function having two parameters ($value, $columnname) that will return 
a new value for this field. For example ['row1'=> [['to'=>'myrow', 'transformcallback'=>'ucwords']]
will transform the content of myrow into capitalised version of row1
* We can also concatenate the content of a given row. In this case we use the
attribute 'concatenate' => ['order'=>0]. The order is used when several row
are concatenated together (for example row1 + row2 is concatenated into crow), and
we want to keep the right order in the concatenation.
For example ['row1'=> [['to'=>'myrow', 'concatenate'=>['order'=>0]]],  ['row2'=> [['to'=>'myrow', 'concatenate'=>['order'=>1]]

### Data importer

The data importer does the real job of importing the row into a Moodle entity
(can be course or other set of tables, ...).
For the course importer if a field starts with cf_... we will import the
value in the matching custom field.

## License ##

2021 CALL Learning <laurent@call-learning.fr>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.

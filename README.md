# Data Importer #

(Still work in progress)

Generic data importer. Currently allows to import and sync course data in a more
configurable way that bulk course import tool.

    * Import from existing database or API (work in progress)
    * Import from CSV

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


## License ##

2020 CALL Learning <laurent@call-learning.fr>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.

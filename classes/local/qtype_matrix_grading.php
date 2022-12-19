<?php
// This file is part of Moodle - http://moodle.org/
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
namespace qtype_matrix\local;
/**
 * Base class for grading types
 *
 * @abstract
 */
abstract class qtype_matrix_grading {

    public static function gradings() {
        static $result = false;
        if ($result) {
            return $result;
        }
        $result = [];
        $classlist = ['all', 'kany', 'kprime'];
        $namespace = 'qtype_matrix\\local\\grading\\';
        foreach ($classlist as $class) {
            $classname = $namespace.$class;
            $result[] = new $classname();
        }
        return $result;
    }

    public static function default_grading() {
        return self::create('kprime');
    }

    /**
     *
     * @param string $type
     * @return qtype_matrix_grading
     */
    public static function create($type) {
        static $result = [];
        if (isset($result[$type])) {
            return $result[$type];
        }
        $class = 'qtype_matrix\\local\\grading\\' . $type;
        return $result[$type] = call_user_func([$class, 'create'], $type);
    }

    public static function get_title() {
        $identifier = self::get_name();
        return \qtype_matrix::get_string($identifier);
    }

    public static function get_name() {
        return get_called_class();
    }

    public static function cell_index($name) {
        $name = str_replace('cell', '', $name);
        $result = explode('_', $name);
        return $result;
    }

    /**
     * Create the form element used to define the weight of the cell
     *
     * @param \MoodleQuickForm $form
     * @param int             $row      row number
     * @param int             $col      column number
     * @param bool            $multiple whether the question allows multiple answers
     * @return object
     */
    public function create_cell_element($form, $row, $col, $multiple) {
        $cellname = $this->cell_name($row, $col, $multiple);
        if ($multiple) {
            return $form->createElement('checkbox', $cellname, 'label');
        } else {
            return $form->createElement('radio', $cellname, '', '', $col);
        }
    }

    /**
     * Returns a cell name.
     * Should be a valid php and html identifier
     *
     * @param int  $row      row number
     * @param int  $col      col number
     * @param bool $multiple one answer per row or several
     *
     * @return string
     */
    public static function cell_name($row, $col, $multiple) {
        $row = $row ? $row : '0';
        $col = $col ? $col : '0';
        return $multiple ? "cell{$row}_{$col}" : "cell{$row}";
    }

    /**
     * Returns the question's grade. By default it is the average of correct questions.
     *
     * @param \qtype_matrix_question $question
     * @param array                 $answers
     * @return float
     */
    public function grade_question($question, $answers) {
        $grades = [];
        foreach ($question->rows as $row) {
            $grades[] = $this->grade_row($question, $row, $answers);
        }
        $result = array_sum($grades) / count($grades);
        $result = min(1, $result);
        $result = max(0, $result);
        return $result;
    }

    /**
     * Grade a specific row
     *
     * @param \qtype_matrix_question $question
     * @param object                $row
     * @param array                 $answers
     * @return float
     */
    public function grade_row($question, $row, $answers) {
        return 0;
    }

    /**
     * validate
     *
     * @param array $data the raw form data
     *
     * @return array of errors
     */
    public function validation($data) {
        return [];
    }

    protected function col_count($data) {
        return count($data['cols_shorttext']);
    }

    protected function row_count($data) {
        return count($data['rows_shorttext']);
    }
}

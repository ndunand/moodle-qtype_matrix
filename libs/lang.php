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

/**
 *  Helper class for strings
 */
class lang {

    const COMPONENT = 'qtype_matrix';

    public static function use_dnd_ui() {
        return self::get('use_dnd_ui');
    }

    public static function get($identifier, $a = null) {
        return get_string($identifier, self::COMPONENT, $a);
    }

    public static function shuffle_answers() {
        return self::get('shuffleanswers');
    }

    public static function must_define_1_by_1() {
        return self::get('mustdefine1by1');
    }

    public static function multiple_allowed() {
        return self::get('multipleallowed');
    }

    public static function grade_method() {
        return self::get('grademethod');
    }

    public static function col_description() {
        return self::get('cols_description');
    }

    public static function row_feedback() {
        return self::get('rows_feedback');
    }

    public static function row_long() {
        return self::get('rows_description');
    }

    public static function true_() {
        return self::get('true');
    }

    public static function false_() {
        return self::get('false');
    }

    public static function one_answer_per_row() {
        return self::get('oneanswerperrow');
    }

}

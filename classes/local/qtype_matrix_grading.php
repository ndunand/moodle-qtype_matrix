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

use coding_exception;
use qtype_matrix_question;

/**
 * Base class for grading types
 *
 * @abstract
 */
abstract class qtype_matrix_grading {

    const VALID_GRADINGS = ['all', 'kany', 'kprime', 'difference'];
    /**
     * @return array
     * @uses \qtype_matrix\local\grading\kany
     * @uses \qtype_matrix\local\grading\kprime
     * @uses \qtype_matrix\local\grading\all
     */
    public static function gradings(): array {
        static $result = false;
        if ($result !== false) {
            return $result;
        }
        $result = [];
        $classlist = self::VALID_GRADINGS;
        $namespace = 'qtype_matrix\\local\\grading\\';
        foreach ($classlist as $class) {
            $classname = $namespace . $class;
            $result[] = new $classname();
        }
        return $result;
    }

    public static function default_grading(): qtype_matrix_grading {
        return self::create('kprime');
    }

    /**
     *
     * @param string $type
     * @return qtype_matrix_grading
     */
    public static function create(string $type): qtype_matrix_grading {
        static $result = [];
        if (isset($result[$type])) {
            return $result[$type];
        }
        $class = 'qtype_matrix\\local\\grading\\' . $type;
        $grading = call_user_func([$class, 'create_grade'], $type);
        $result[$type] = $grading;
        return $grading;
    }

    /**
     * @return string
     * @throws coding_exception
     */
    public static function get_title(): string {
        return lang::get(self::get_name());
    }

    public static function get_name(): string {
        return get_called_class();
    }

    /**
     * Returns the question's grade for the given response.
     *
     * @param qtype_matrix_question $question
     * @param int[] $roworder Order of rows in this question
     * @param array                 $response
     * @return float
     */
    abstract public function grade_question(qtype_matrix_question $question, array $roworder, array $response):float;

    /**
     * Grade a specific row
     *
     * @param qtype_matrix_question $question
     * @param int $rowindex
     * @param array                 $response
     * @return float
     */
    abstract public function grade_row(qtype_matrix_question $question, int $rowindex, array $response):float;

    /**
     * validate
     *
     * @param array $data the raw form data
     * @return array of errors
     */
    public function validation(array $data): array {
        return [];
    }
}

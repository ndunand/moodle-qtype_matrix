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

namespace qtype_matrix\local\grading;

use coding_exception;
use qtype_matrix\local\interfaces\grading;
use qtype_matrix\local\lang;
use qtype_matrix\local\qtype_matrix_grading;
use qtype_matrix_question;

/**
 * Per row grading. The total grade is the average of grading received
 * for reach one of the rows.
 *
 * For a row all of the correct and none of the wrong answers must be selected
 * to get 100% otherwise 0.
 */
class all extends qtype_matrix_grading implements grading {

    const TYPE = 'all';

    public static function get_name(): string {
        return self::TYPE;
    }

    /**
     * @return string
     * @throws coding_exception
     */
    public static function get_title(): string {
        return lang::get(self::TYPE);
    }

    /**
     * Factory
     *
     * @return all
     */
    public static function create_grade(): all {
        static $result = false;
        if ($result) {
            return $result;
        }
        return $result = new self();
    }

    /**
     * Returns the question's grade. By default, it is the average of correct questions.
     *
     * @param qtype_matrix_question $question
     * @param int[] $roworder Order of rows in this question
     * @param array                 $response
     * @return float
     */
    public function grade_question(qtype_matrix_question $question, array $roworder, array $response): float {
        $grades = [];
        foreach ($roworder as $rowindex => $rowid) {
            $grades[] = $this->grade_row($question, $rowindex, $response);
        }
        $result = array_sum($grades) / count($grades);
        $result = min(1, $result);
        return max(0, $result);
    }

    /**
     * All cells of a row of an answer must match with the question's row to get point.
     *
     * @param qtype_matrix_question $question  The question to grade
     * @param int $rowindex Row to grade
     * @param array                  $response User's responses
     * @return float                            The row grade, either 0 or 1
     */
    public function grade_row(qtype_matrix_question $question, int $rowindex, array $response):float {
        // All of a row must be correct to get a point.
        foreach (array_keys($question->cols) as $colindex => $colid) {
            $cellanswer = $question->answer($rowindex, $colindex);
            $cellresponse = $question->response($response, $rowindex, $colindex);
            if ($cellanswer != $cellresponse) {
                return 0.0;
            }
        }
        return 1.0;
    }
}

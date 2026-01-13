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
 * Any correct and no wrong answer to get 100% otherwise 0
 */
class kany extends qtype_matrix_grading implements grading {

    const TYPE = 'kany';

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
     * @return kany
     */
    public static function create_grade(): kany {
        static $result = false;
        if ($result) {
            return $result;
        }
        return $result = new self();
    }

    public function grade_question(qtype_matrix_question $question, array $roworder, array $response): float {
        $numberofcorrectrows = 0.0;
        foreach ($roworder as $rowindex => $rowid) {
            $grade = $this->grade_row($question, $rowindex, $response);
            if ($grade >= 1) {
                $numberofcorrectrows++;
            }
        }
        $nrrows = count($question->rows);
        if ($numberofcorrectrows == $nrrows) {
            return 1.0;
        } else if (($nrrows - $numberofcorrectrows) == 1) {
            return 0.5;
        }
        return 0.0;
    }

    /**
     * Grade a row
     *
     * @param qtype_matrix_question $question  The question to grade
     * @param int $rowindex Row to grade
     * @param array                  $response User's responses
     * @return float                            The row grade, either 0 or 1
     */
    public function grade_row(qtype_matrix_question $question, int $rowindex, array $response): float {
        $onecorrectanswer = false;
        foreach (array_keys($question->cols) as $colindex => $colid) {
            $cellanswer = $question->answer($rowindex, $colindex);
            $cellresponse = $question->response($response, $rowindex, $colindex);
            if (!$cellanswer && $cellresponse) {
                return 0;
            }
            if ($cellanswer && $cellresponse) {
                $onecorrectanswer = true;
            }
        }
        return ($onecorrectanswer) ? 1.0 : 0.0;
    }
}

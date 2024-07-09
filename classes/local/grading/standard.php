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
 * Standardized Grading Method.
 */
class standard extends qtype_matrix_grading implements grading
{

    const TYPE = 'standard';

    public static function get_name(): string
    {
        return self::TYPE;
    }

    /**
     * @return string
     * @throws coding_exception
     */
    public static function get_title(): string
    {
        return lang::get(self::TYPE);
    }

    /**
     * Factory
     *
     * @return standard
     */
    public static function create_grade(): standard
    {
        static $result = false;
        if ($result) {
            return $result;
        }
        return $result = new self();
    }

    public function grade_question(qtype_matrix_question $question, array $answers): float
    {
        global $DB;
        $questionid = $question->id;

        // Lấy giá trị điểm chấm từ cơ sở dữ liệu.
        $options = $DB->get_record('qtype_matrix_options', array('questionid' => $questionid));
        if (!$options) {
            // Giá trị mặc định nếu không có cấu hình.
            $partialgrade1 = 0.5;
            $partialgrade2 = 0.25;
            $partialgrade3 = 0.1;
        } else {
            $partialgrade1 = $options->partialgrade1;
            $partialgrade2 = $options->partialgrade2;
            $partialgrade3 = $options->partialgrade3;
        }

        $numberofcorrectrows = 0.0;
        foreach ($question->rows as $row) {
            $grade = $this->grade_row($question, $row, $answers);
            if ($grade >= 1) {
                $numberofcorrectrows++;
            }
        }

        $totalrows = count($question->rows);

        if ($numberofcorrectrows == $totalrows) {
            return 1.0;
        } else if (($totalrows - $numberofcorrectrows) == 1) {
            return $partialgrade1;
        } else if (($totalrows - $numberofcorrectrows) == 2) {
            return $partialgrade2;
        } else if (($totalrows - $numberofcorrectrows) == 3) {
            return $partialgrade3;
        }

        // Trả về giá trị mặc định 0.0 nếu không có điều kiện nào thỏa mãn.
        return 0.0;
    }

    /**
     * Grade a row
     *
     * @param qtype_matrix_question $question  The question to grade
     * @param integer|object         $row       Row to grade
     * @param array                  $responses User's responses
     * @return float                            The row grade, either 0 or 1
     */
    public function grade_row(qtype_matrix_question $question, $row, array $responses): float
    {
        $onecorrectanswer = false;
        foreach ($question->cols as $col) {
            $answer = $question->answer($row, $col);
            $response = $question->response($responses, $row, $col);
            if (!$answer && $response) {
                return 0;
            }
            if ($answer && $response) {
                $onecorrectanswer = true;
            }
        }
        return ($onecorrectanswer) ? 1.0 : 0.0;
    }
}

<?php

/**
 * Per row grading. The total grade is the average of grading received 
 * for reach one of the rows.
 * 
 * Any correct and no wrong answer to get 100% otherwise 0
 */
class qtype_matrix_grading_kany extends qtype_matrix_grading
{

    const TYPE = 'kany';

    public static function get_name()
    {
        return self::TYPE;
    }

    public static function get_title()
    {
        return qtype_matrix::get_string(self::TYPE);
    }

    /**
     * Factory 
     *
     * @param string $type
     * @return qtype_matrix_grading_kany
     */
    public static function create($type)
    {
        static $result = false;
        if ($result) {
            return $result;
        }
        return $result = new self();
    }

    public function grade_question($question, $answers)
    {
        $numberOfCorrectRows = 0;
        foreach ($question->rows as $row) {
            $grade = $this->grade_row($question, $row, $answers);
            if ($grade >= 1) {
                $numberOfCorrectRows++;
            }
        }
        if ($numberOfCorrectRows == count($question->rows)) {
            return 1;
        } else if ((count($question->rows) - $numberOfCorrectRows) == 1) {
            return 0.5;
        }
        return 0;
    }

    /**
     * Grade a row
     * 
     * @param qtype_matrix_question $question   The question to grade
     * @param integer|object $row               Row to grade
     * @param array $responses                  User's responses
     * @return float                            The row grade, either 0 or 1
     */
    public function grade_row($question, $row, $responses)
    {
        $one_correct_answer = false;
        foreach ($question->cols as $col) {
            $answer = $question->answer($row, $col);
            $response = $question->response($responses, $row, $col);
            if (!$answer && $response) {
                return 0;
            }
            if ($answer && $response) {
                $one_correct_answer = true;
            }
        }
        return ($one_correct_answer) ? 1 : 0;
    }

    public function validation($data)
    {
        return array();
    }

}

<?php

/**
 * Per row grading. The total grade is the average of grading received 
 * for reach one of the rows.
 * 
 * For a row all of the correct and none of the wrong answers must be selected
 * to get 100% otherwise 0.
 */
class qtype_matrix_grading_all extends qtype_matrix_grading
{

    const TYPE = 'all';

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
     * @return qtype_matrix_grading_all
     */
    public static function create($type)
    {
        static $result = false;
        if ($result)
        {
            return $result;
        }
        return $result = new self();
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
        foreach ($question->cols as $col) {
            $answer = $question->answer($row, $col);
            $response = $question->response($responses, $row, $col);
            if ($answer != $response) {
                return 0;
            }
        }
        return 1;
    }

}
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
     * Grade a specific row
     * 
     * @param qtype_matrix_question     $question
     * @param object                    $row
     * @param array                     $answers
     * @return float 
     */
    public function grade_row($question, $row, $answers)
    {
        $is_row_correct = true;
        foreach ($question->cols as $col)
        {
            $is_correct = $question->is_correct($row, $col);
            $is_answered = $question->is_answered($answers, $row, $col);
            if ($is_answered != $is_correct)
            {
                $is_row_correct = false;
                break;
            }
        }
        return $is_row_correct ? 1 : 0;
    }

}
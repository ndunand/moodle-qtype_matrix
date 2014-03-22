<?php

/**
 * 
 * Total grading - i.e. including rows.
 * 
 * The student must choose all correct answers, and none of the wrong ones 
 * to get 100% otherwise he gets 0%. Including rows. 
 * If one row is wrong then the mark for the question is 0.
 *
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class qtype_matrix_grading_kprime extends qtype_matrix_grading
{

    const TYPE = 'kprime';

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
     * @return qtype_matrix_grading_kprime
     */
    public static function create()
    {
        static $result = false;
        if ($result)
        {
            return $result;
        }
        return $result = new self();
    }

    /**
     * Returns the question's grade. By default it is the average of correct questions.
     * 
     * @param qtype_matrix_question $question
     * @param array                 $answers
     * @return float 
     */
    public function grade_question($question, $answers)
    {
        foreach ($question->rows as $row)
        {
            $grade = $this->grade_row($question, $row, $answers);
            if ($grade < 1)
            {
                return 0;
            }
        }
        return 1;
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
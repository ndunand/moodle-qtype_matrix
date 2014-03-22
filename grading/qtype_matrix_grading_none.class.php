<?php

/**
 * No grading. This may be used for Likert Scales for example.
 *
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class qtype_matrix_grading_none extends qtype_matrix_grading
{

    const TYPE = 'none';

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
     * @return qtype_matrix_grading_none
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

//    /**
//     * whether this grade method requires manual intervention
//     */
//    public function is_manual_graded()
//    {
//        return true;
//    }

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
        return 0;
    }

}
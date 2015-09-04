<?php

/**
 * Base class for grading types
 * 
 * @abstract
 */
abstract class qtype_matrix_grading
{

    public static function gradings()
    {
        static $result = false;
        if ($result)
        {
            return $result;
        }
        $result = array();

        $dir = dirname(__FILE__) . '/grading';
        $files = scandir($dir);
        $files = array_diff($files, array('.', '..'));
        foreach ($files as $file)
        {
            include_once("$dir/$file");
            $class = str_replace('.class.php', '', $file);
            if (class_exists($class))
            {
                $result[] = new $class();
            }
        }
        return $result;
    }

    public static function default_grading()
    {
        return self::create('kprime');
    }

    /**
     *     
     * @param string $type
     * @return qtype_matrix_grading
     */
    public static function create($type)
    {
        static $result = array();
        if (isset($result[$type]))
        {
            return $result[$type];
        }
        $class = 'qtype_matrix_grading_' . $type;

        require_once dirname(__FILE__) . '/grading/' . $class . '.class.php';
        return $resut[$type] = call_user_func(array($class, 'create'), $type);
    }

    public static function get_name()
    {
        $class = get_called_class();
        $result = str_replace('qtype_matrix_grading_', '', $class);
        return $result;
    }

    public static function get_title()
    {
        $identifier = self::get_name();
        return qtype_matrix::get_string($identifier);
    }

    /**
     * Create the form element used to define the weight of the cell
     * 
     * @param MoodleQuickForm   $form
     * @param int $row          row number
     * @param int $col          column number
     * @param bool $multiple    whether the question allows multiple answers
     * @return object
     */
    public function create_cell_element($form, $row, $col, $multiple)
    {
        $cell_name = $this->cell_name($row, $col, $multiple);
        if ($multiple)
        {
            return $form->createElement('checkbox', $cell_name, 'label');
        }
        else
        {
            return $form->createElement('radio', $cell_name, '', '', $col);
        }
    }

    /**
     * Returns a cell name.
     * Should be a valid php and html identifier
     *
     * @param int   $row row number
     * @param int   $col col number
     * @param bool  $multiple one answer per row or several
     * 
     * @return string
     */
    public static function cell_name($row, $col, $multiple)
    {
        $row = $row ? $row : '0';
        $col = $col ? $col : '0';
        return $multiple ? "cell{$row}_{$col}" : "cell{$row}";
    }

    public static function cell_index($name)
    {
        $name = str_replace('cell', '', $name);
        $result = explode('_', $name);
        return $result;
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
        $grades = array();
        foreach ($question->rows as $row)
        {
            $grades[] = $this->grade_row($question, $row, $answers);
        }
        $result = array_sum($grades) / count($grades);
        $result = min(1, $result);
        $result = max(0, $result);
        return $result;
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
        return 0;
    }

    /**
     * validate 
     *
     * @param array $data the raw form data
     *
     * @return array of errors
     */
    public function validation($data)
    {
        return array();
    }

//    /**
//     * whether this grade method requires manual intervention
//     */
//    public function is_manual_graded()
//    {
//        return false;
//    }

    protected function col_count($data)
    {
        return count($data['cols_shorttext']);
    }

    protected function row_count($data)
    {
        return count($data['rows_shorttext']);
    }
}

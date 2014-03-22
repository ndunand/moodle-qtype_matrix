<?php

/**
 *
 * @copyright   2012 University of Geneva
 * @author      laurent.opprecht@unige.ch
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package     qtype
 * @subpackage  matrix
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Generates the output for matrix questions.
 */
class qtype_matrix_renderer extends qtype_with_combined_feedback_renderer
{

    /**
     * Generate the display of the formulation part of the question. This is the
     * area that contains the quetsion text, and the controls for students to
     * input their answers. Some question types also embed bits of feedback, for
     * example ticks and crosses, in this area.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options)
    {
        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();

        $table = new html_table();
        $table->attributes['class'] = 'matrix';

        $table->head = array();
        $table->head[] = '';
        foreach ($question->cols as $col)
        {
            $table->head[] = self::matrix_header($col);
        }

        if ($options->correctness)
        {
            $table->head[] = '';
        }

        foreach ($question->rows as $row)
        {
            $row_data = array();
            $row_data[] = self::matrix_header($row);
            foreach ($question->cols as $col)
            {
                $key = $question->key($row, $col);
                $cell_name = $qa->get_field_prefix() . $key;

                $is_readonly = $options->readonly;
                $is_checked = $question->is_answered($response, $row, $col);

                if ($question->multiple)
                {
                    $cell = self::checkbox($cell_name, $is_checked, $is_readonly);
                }
                else
                {
                    $cell = self::radio($cell_name, $col->id, $is_checked, $is_readonly);
                }
                if ($options->correctness)
                {
                    $weight = $question->weight($row, $col);
                    $cell .= $this->feedback_image($weight);
                }
                $row_data[] = $cell;
            }

            if ($options->correctness)
            {
                $row_grade = $question->grading()->grade_row($question, $row, $response);
                $feedback = $row->feedback;
                $feedback = strip_tags($feedback) ? $feedback : '';
                $row_data[] = $this->feedback_image($row_grade) . $feedback;
            }
            $table->data[] = $row_data;

            //$row_index++;
        }

        $result = $question->questiontext;
        $result .= html_writer::table($table, true);
        return $result;
    }

    public static function matrix_header($header)
    {
        $text = $header->shorttext;

        $description = $header->description;
        if (strip_tags($description))
        {
            $description = preg_replace('-^<p>-', '', $description);
            $description = preg_replace('-</p>$-', '', $description);
            $description = '<span class="description" >' . $description . '</span>';
        }
        else
        {
            $description = '';
        }

        return '<span class="title">' . $text . '</span>' . $description;
    }

    protected static function checkbox($name, $checked, $readonly)
    {
        $readonly = $readonly ? 'readonly="readonly" disabled="disabled"' : '';
        $checked = $checked ? 'checked="checked"' : '';
        return <<<EOT
        <input type="checkbox" name="$name" $checked $readonly />
EOT;
    }

    protected static function radio($name, $value, $checked, $readonly)
    {
        $readonly = $readonly ? 'readonly="readonly" disabled="disabled"' : '';
        $checked = $checked ? 'checked="checked"' : '';
        return <<<EOT
        <input type="radio" name="$name" value="$value" $checked $readonly />
EOT;
    }

}
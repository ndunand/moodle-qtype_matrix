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

namespace qtype_matrix\output;

/**
 * Generates the output for matrix questions.
 */
class renderer extends \qtype_with_combined_feedback_renderer {

    /**
     * Generate the display of the formulation part of the question. This is the
     * area that contains the question text, and the controls for students to
     * input their answers. Some question types also embed bits of feedback, for
     * example ticks and crosses, in this area.
     *
     * @param \question_attempt         $qa      the question attempt to display.
     * @param \question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    public function formulation_and_controls(\question_attempt $qa, \question_display_options $options) {
        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();

        $table = new \html_table();
        $table->attributes['class'] = 'matrix';

        if (\qtype_matrix\local\setting::allow_dnd_ui() && $question->usedndui) {
            $table->attributes['class'] .= ' uses_dndui';
        }

        $table->head = [];
        $table->head[] = '';

        $order = $question->get_order($qa);

        foreach ($question->cols as $col) {
            $table->head[] = self::matrix_header($col);
        }

        if ($options->correctness) {
            $table->head[] = '';
        }

        foreach ($order as $rowid) {

            $row = $question->rows[$rowid];
            $rowdata = [];
            $rowdata[] = self::matrix_header($row);
            foreach ($question->cols as $col) {
                $key = $question->key($row, $col);
                $cellname = $qa->get_field_prefix() . $key;

                $isreadonly = $options->readonly;
                $ischecked = $question->response($response, $row, $col);

                if ($question->multiple) {
                    $cell = self::checkbox($cellname, $ischecked, $isreadonly);
                } else {
                    $cell = self::radio($cellname, $col->id, $ischecked, $isreadonly);
                }
                if ($options->correctness) {
                    $weight = $question->weight($row, $col);
                    $cell .= $this->feedback_image($weight);
                }
                $rowdata[] = $cell;
            }

            if ($options->correctness) {
                $rowgrade = $question->grading()->grade_row($question, $row, $response);
                $feedback = $row->feedback['text'];
                $feedback = strip_tags($feedback) ? $feedback : '';
                $rowdata[] = $this->feedback_image($rowgrade) . $feedback;
            }
            $table->data[] = $rowdata;
        }
        $questiontext = $question->format_questiontext($qa);
        $result = \html_writer::tag('div', $questiontext, ['class' => 'question_text']);
        $result .= \html_writer::table($table, true);
        return $result;
    }

    public static function matrix_header($header) {
        $text = $header->shorttext;

        $description = $header->description['text'];
        if (strip_tags($description)) {
            $description = preg_replace('-^<p>-', '', $description);
            $description = preg_replace('-</p>$-', '', $description);
            $description = '<span class="description" >' . format_text($description) . '</span>';
        } else {
            $description = '';
        }

        return '<span class="title">' . format_text($text) . '</span>' . $description;
    }

    protected static function checkbox($name, $checked, $readonly) {
        $readonly = $readonly ? 'readonly="readonly" disabled="disabled"' : '';
        $checked = $checked ? 'checked="checked"' : '';
        return "<input type=\"checkbox\" name=\"$name\" $checked $readonly />";
    }

    protected static function radio($name, $value, $checked, $readonly) {
        $readonly = $readonly ? 'readonly="readonly" disabled="disabled"' : '';
        $checked = $checked ? 'checked="checked"' : '';
        return "<input type=\"radio\" name=\"$name\" value=\"$value\" $checked $readonly />";
    }

}

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

use dml_exception;
use qtype_with_combined_feedback_renderer;
use question_attempt;
use question_display_options;

/**
 * Generates the output for matrix questions.
 */
class renderer extends qtype_with_combined_feedback_renderer {

    /**
     * Generate the display of the formulation part of the question. This is the
     * area that contains the question text, and the controls for students to
     * input their answers. Some question types also embed bits of feedback, for
     * example ticks and crosses, in this area.
     *
     * @param question_attempt         $qa      the question attempt to display.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     * @throws dml_exception
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options): string {
        $matrix_formulation = new formulation_and_controls($qa, $options);
        return $this->render($matrix_formulation);
    }

    public function render_formulation_and_controls(formulation_and_controls $matrix_formulation) {
        return $this->render_from_template(
            'qtype_matrix/question', $matrix_formulation->export_for_template($this)
        );
    }

    public function feedback_image($fraction, $selected = true): string {
        return parent::feedback_image($fraction);
    }
}

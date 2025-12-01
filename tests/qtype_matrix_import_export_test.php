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

namespace qtype_matrix;

use qtype_matrix\local\qtype_matrix_grading;
use advanced_testcase;
use question_bank;
use core_question_generator;
use qformat_xml;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/format/xml/format.php');
/**
 * Unit tests for the true-false question definition class.
 *
 */
class qtype_matrix_question_import_export_test extends advanced_testcase {

    /**
     * @return void
     * @throws \moodle_exception
     */
    public function test_export_import(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $coregenerator = $this->getDataGenerator();
        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');

        // Create a course with a page that embeds a question.
        $course = $coregenerator->create_course();
        $coursecontext = \context_course::instance($course->id);
        $cat = $questiongenerator->create_question_category(['contextid' => $coursecontext->id]);
        // TODO: We could test both questions with default values for options and questions with all non-default values
        $question = $questiongenerator->create_question('matrix', 'kprime', ['category' => $cat->id]);
        $matrixquestiondata = question_bank::load_question_data($question->id);
        $xmlformat = new qformat_xml();
        $xmlformat->setCourse($course);
        $xmlformat->setQuestions([$matrixquestiondata]);
        $xmlformat->setCattofile(false);
        $xmlformat->setContexttofile(false);
        $export = $xmlformat->exportprocess(true);

        $importedquestion = $xmlformat->readquestions(explode('\n', $export))[0];
        $this->assertEquals(false, isset($importedquestion->id));
        $this->assertEquals('matrix', $importedquestion->qtype);
        $this->assertEquals($matrixquestiondata->options->grademethod, $importedquestion->grademethod);
        $this->assertEquals($matrixquestiondata->options->multiple, $importedquestion->multiple);
        $this->assertEquals($matrixquestiondata->options->shuffleanswers, $importedquestion->shuffleanswers);
        $this->assertEquals($matrixquestiondata->options->usedndui, $importedquestion->usedndui);
        $oldquestionrows = array_values($matrixquestiondata->options->rows);
        foreach ($oldquestionrows as $index => $oldquestionrow) {
            $this->assertEquals($oldquestionrow->shorttext, $importedquestion->rows_shorttext[$index]);
            $this->assertEquals($oldquestionrow->description['text'], $importedquestion->rows_description[$index]['text']);
            $this->assertEquals($oldquestionrow->description['format'], $importedquestion->rows_description[$index]['format']);
            $this->assertEquals($oldquestionrow->feedback['text'], $importedquestion->rows_feedback[$index]['text']);
            $this->assertEquals($oldquestionrow->feedback['format'], $importedquestion->rows_feedback[$index]['format']);
            $this->assertEquals(false, $importedquestion->rowid[$index]);
        }

        $oldquestioncols = array_values($matrixquestiondata->options->cols);
        foreach ($oldquestioncols as $index => $oldquestioncol) {
            $this->assertEquals($oldquestioncol->shorttext, $importedquestion->cols_shorttext[$index]);
            $this->assertEquals($oldquestioncol->description['text'], $importedquestion->cols_description[$index]['text']);
            $this->assertEquals($oldquestioncol->description['format'], $importedquestion->cols_description[$index]['format']);
            $this->assertEquals(false, $importedquestion->colid[$index]);
        }
        $oldquestionweightrows = array_values($matrixquestiondata->options->weights);
        foreach ($oldquestionweightrows as $rowindex => $oldquestionweightrow) {
            $oldquestionweightcols = array_values($oldquestionweightrow);
            foreach ($oldquestionweightcols as $colindex => $oldquestionweightcol) {
                $weightcellname = qtype_matrix_grading::cell_name($rowindex, $colindex, $importedquestion->multiple);
                $this->assertEquals($oldquestionweightcol, $importedquestion->{$weightcellname});
            }
        }
    }
}

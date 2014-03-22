<?php

/**
 * 
 * @copyright   2012 University of Geneva
 * @author      laurent.opprecht@unige.ch
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package     qtype
 * @subpackage  matrix
 */

// qtype strings
$string['addingmatrix'] = 'Adding Matrix';
$string['editingmatrix'] = 'Editing Matrix';
$string['matrix'] = 'Matrix';
$string['matrixsummary'] = 'Matrix question type';

$string['matrix_help'] = '<p>This question type allows the teacher to define the rows and columns that make up a matrix.</p>
<p>Students can select either multiple or single answers per row, depending on how the question has been configured. Each row receives a grade defined by one of the grading methods.</p>
<p>The final grade for the question is an average of their grades for each of the rows</p>';

//gradings
$string['all'] = 'All';
$string['any'] = 'Any';
$string['kprime'] = "K'";
$string['none'] = 'None';
$string['weighted'] = 'Weighted';

//strings
$string['true'] = 'True';
$string['false'] = 'False';

//form
$string['multipleallowed'] = 'Multiple responses allowed';

$string['grademethod'] = 'Grading method';
$string['grademethod_help'] = '<p>There are a few options for the grading method for matrix question types:</p>
<p>Each of these, but Kprime, relate to how each <b>row</b> is graded, with the total grade for the question being the average of all the rows. Kprime requires that all rows must be correct to get the point. If it is not the case the studend receives 0.</p>
<table>
  <tr><td><b>Kprime</b></td><td>The student must choose all correct answers, and none of the wrong ones, to get 100%, else 0%. Including rows. If one row is wrong then the mark for the question is 0.</td></tr>
  <tr><td><b>Any correct, and none wrong</b></td><td>The student must choose at least one of the correct answers, and none of the wrong ones, to get 100%, else 0%</td></tr>
  <tr><td><b>All correct, and none wrong</b></td><td>The student must choose exactly all of the correct answers, and none of the wrong ones, to get 100%, else 0%</td></tr>
  <tr><td><b>No grading</b></td><td>There is no grading used for this question (use this for Likert Scales for example)</td></tr>
  <tr><td><b>Weighted grading</b></td><td>Each cell receives a weighting, and the positive values for each row must add up to 100%</td></tr>
</table>';

//$string['renderer'] = 'Renderer';

$string['rowsheader'] = 'Matrix rows';
$string['rowsheader_desc'] = '<p>Shorttext will be used when it\'s present, with the longer text as a tooltip.<br />Be mindful of how this will be displayed</p>
<p>Students can select multiple or single columns per row, depending on how the question has been configured, and each row receives a grade, defined by one of the grading methods.</p>
<p>The final grade for the question is an average of their grades for each of the rows with the exeption of the Kprime type where all answers have to be correct.</p>';

$string['rowshort'] = 'Title';
$string['rowlong'] = 'Description';
$string['rowfeedback'] = 'Feedback';

//$string['addmorerows'] = 'Add {$a} more rows';

$string['colsheader'] = 'Matrix columns';
$string['colsheader_desc'] = '<p>Shorttext will be used when it\'s present, with the longer text as a tooltip.<br />Be mindful of how this will be displayed.</p>
<p>Students can select multiple or single columns per row, depending on how the question has been configured, and each row receives a grade, defined by one of the grading methods.</p>
<p>The final grade for the question is an average of their grades for each of the rows with the exeption of the Kprime type where all answers have to be correct.</p>';

$string['colshort'] = 'Title';
$string['collong'] = 'Description';

//$string['addmorecols'] = 'Add {$a} more columns';

$string['refresh_matrix'] = 'Refresh matrix';

//$string['updatematrix'] = 'Update matrix to reflect new options';
$string['matrixheader'] = 'Grading matrix';

$string['mustdefine1by1'] = 'You must define at least a 1 x 1 matrix; with either short or long answer defined for each row and column';
$string['mustaddupto100'] = 'The sum of all non negative weights in each row must be 100%';
$string['weightednomultiple'] = 'It doesn\'t make sense to choose weighted grading with multiple answers not allowed';
$string['oneanswerperrow'] = 'You must provide an answer for each row';

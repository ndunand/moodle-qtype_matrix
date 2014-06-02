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

$string['pluginname'] = 'Matrix/Kprime';
$string['pluginnamesummary'] = 'In matrix questions various statements regarding a common subject have to be rated correctly. In Kprime questions exactly four such statements have to be correctly rated as “true” or “false”.';
$string['pluginnameadding'] = 'Adding a Matrix/Kprime question';
$string['pluginnameediting'] = 'Editing a Matrix/Kprime question';

$string['pluginname_help'] = '<p>Matrix questions consist of an item stem such as a question or incomplete statement, and multiple answer statements, such as corresponding answers or completions. Students rate these statements as “true” or “false”. Alternatively, custom ratings for the answer statements may be defined.
Kprime questions consist of an item stem and four corresponding answer statements. For each answer statement students have to decide whether it is right or wrong.</p>';
$string['pluginname_link'] = 'question/type/matrix';

//gradings
$string['all'] = 'Subpoints';
$string['kany'] = 'Kprime';
$string['kprime'] = "Kprime1/0";

//strings
$string['true'] = 'True';
$string['false'] = 'False';

//form
$string['multipleallowed'] = 'Allow multiple responses per answer statement?';

$string['grademethod'] = 'Scoring method';
$string['grademethod_help'] = '<ul><li><b>Kprime</b>: The student receives one point, if all responses are correct, half a point if 60% ore more responses are correct, and zero points otherwise.
<li><b>Kprime1/0</b>: The student receives one point, if all responses are correct, and  zero points otherwise.

<li><b>Subpoints</b>: The student is awarded subpoints for each correct response.</ul>';//Kprime or Kprime1/0 may only be chosen if the response matrix consists of exactly four answer  statements, two response categories, and multiple answers are not allowed.';

//$string['renderer'] = 'Renderer';

$string['rowsheader'] = 'Matrix rows';
$string['rowsheader_desc'] = '<p>Shorttext will be used when it\'s present, with the longer text as a tooltip.<br />Be mindful of how this will be displayed</p>
<p>Students can select multiple or single columns per row, depending on how the question has been configured, and each row receives a grade, defined by one of the grading methods.</p>
<p>The final grade for the question is an average of their grades for each of the rows with the exeption of the Kprime type where all answers have to be correct.</p>';

$string['rowshort'] = 'Answer statement';
$string['rowlong'] = 'Description';
$string['rowfeedback'] = 'Feedback';

//$string['addmorerows'] = 'Add {$a} more rows';

$string['colsheader'] = 'Matrix columns';
$string['colsheader_desc'] = '<p>Shorttext will be used when it\'s present, with the longer text as a tooltip.<br />Be mindful of how this will be displayed.</p>
<p>Students can select multiple or single columns per row, depending on how the question has been configured, and each row receives a grade, defined by one of the grading methods.</p>
<p>The final grade for the question is an average of their grades for each of the rows with the exeption of the Kprime type where all answers have to be correct.</p>';

$string['colshort'] = 'Response';
$string['collong'] = 'Description';

//$string['addmorecols'] = 'Add {$a} more columns';

$string['refresh_matrix'] = 'Refresh response matrix';

//$string['updatematrix'] = 'Update matrix to reflect new options';
$string['matrixheader'] = 'Response matrix';

$string['mustdefine1by1'] = 'You must define at least a 1 x 1 matrix; with either short or long answer defined for each row and column';
$string['mustaddupto100'] = 'The sum of all non negative weights in each row must be 100%';
$string['weightednomultiple'] = 'It doesn\'t make sense to choose weighted grading with multiple answers not allowed';
$string['oneanswerperrow'] = 'You must provide an answer for each row';

$string['shuffleanswers'] = 'Shuffle answer statements?';
$string['shuffleanswers_help'] = 'If enabled, the order of the answer statements is randomly shuffled for each attempt, provided that “Shuffle within questions” in the activity settings is also enabled.';

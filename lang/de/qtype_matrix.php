<?php

if (!property_exists($CFG, 'qtype_matrix_show_non_kprime_gui') || $CFG->qtype_matrix_show_non_kprime_gui !== '0') {
    $string['pluginname'] = 'Matrix/Kprim';
    $string['pluginnamesummary'] = 'In Matrix-Fragen müssen verschiedene Aussagen zu einem gemeinsamen Thema bewertet werden. In Kprim-Fragen müssen dabei genau vier Aussagen als „richtig“ oder „falsch“ bewertet werden.';
    $string['pluginnameadding'] = 'Matrix/Kprim-Frage hinzufügen';
    $string['pluginnameediting'] = 'Matrix/Kprim-Frage bearbeiten';
    $string['pluginname_help'] = '<p>Matrix-Fragen bestehen aus einem Item-Stamm, z.B. eine Frage oder eine unvollständige Aussage, und mehreren zugehörigen Teilaussagen, z.B. korrespondierende Antworten und Vervollständigungen. Die Kandidaten müssen diese Teilaussagen als „richtig“ oder „falsch“ bewerten. Es können eigene oder zusätzliche Bewertungskategorien definiert werden.
Kprim-Fragen bestehen aus einem Item-Stamm und vier zugehörigen Teilaussagen. Jede Teilaussage muss als „richtig“ oder „falsch“ bewertet werden.
</p>';
    $string['grademethod_help'] = '<ul>
<li><b>Kprim</b>: Bei der Auswahl „Kprim“ erhalten Kandidaten einen Punkt, wenn alle Teilaussagen richtig bewertet wurden, einen halben Punkt, wenn eine Teilaussage falsch und die restlichen richtig bewertet wurden und null Punkte sonst.
<li><b>Kprim1/0</b>: Bei der Auswahl „Kprim“ erhalten Kandidaten einen Punkt, wenn alle Teilaussagen richtig bewertet wurden und null Punkte sonst. Die Bewertungsmethoden Kprim und Kprim1/0 sollten nur für Fragen mit genau vier Teilaussagen verwendet werden.
<li><b>Teilpunkte</b>: Bei der Auswahl „Teilpunkte“ erhalten Kandidaten Teilpunkte für jede richtige Bewertung.
</ul>';
} else {
    $string['pluginname'] = 'Kprim';
    $string['pluginnamesummary'] = 'In Kprim-Fragen müssen dabei genau vier Aussagen als „richtig“ oder „falsch“ bewertet werden.';
    $string['pluginnameadding'] = 'Kprim-Frage hinzufügen';
    $string['pluginnameediting'] = 'Kprim-Frage bearbeiten';
    $string['pluginname_help'] = '<p>Kprim-Fragen bestehen aus einem Item-Stamm und vier zugehörigen Teilaussagen. Jede Teilaussage muss als „richtig“ oder „falsch“ bewertet werden.
</p>';
    $string['grademethod_help'] = '<ul>
<li><b>Kprim</b>: Bei der Auswahl „Kprim“ erhalten Kandidaten einen Punkt, wenn alle Teilaussagen richtig bewertet wurden, einen halben Punkt, wenn eine Teilaussage falsch und die restlichen richtig bewertet wurden und null Punkte sonst.
<li><b>Kprim1/0</b>: Bei der Auswahl „Kprim“ erhalten Kandidaten einen Punkt, wenn alle Teilaussagen richtig bewertet wurden und null Punkte sonst.
<li><b>Teilpunkte</b>: Bei der Auswahl „Teilpunkte“ erhalten Kandidaten Teilpunkte für jede richtige Bewertung.
</ul>';
}


$string['pluginname_link'] = 'question/type/matrix';

//gradings
$string['all'] = 'Teilpunkte';
$string['kany'] = 'Kprim';
$string['kprime'] = "Kprim1/0";

//strings
$string['true'] = 'Richtig';
$string['false'] = 'Falsch';

//form
$string['multipleallowed'] = 'Mehrere Antworten pro Teilaussage erlauben?';

$string['grademethod'] = 'Bewertung';
//$string['renderer'] = 'Renderer';

$string['rowsheader'] = 'Matrix rows';
$string['rowsheader_desc'] = '<p>Shorttext will be used when it\'s present, with the longer text as a tooltip.<br />Be mindful of how this will be displayed</p>
<p>Students can select multiple or single columns per row, depending on how the question has been configured, and each row receives a grade, defined by one of the grading methods.</p>
<p>The final grade for the question is an average of their grades for each of the rows with the exeption of the Kprime type where all answers have to be correct.</p>';

$string['rows_shorttext'] = 'Teilaussage';
$string['rows_description'] = 'Description';
$string['rows_feedback'] = 'Feedback';

//$string['addmorerows'] = 'Add {$a} more rows';

$string['colsheader'] = 'Matrix columns';
$string['colsheader_desc'] = '<p>Shorttext will be used when it\'s present, with the longer text as a tooltip.<br />Be mindful of how this will be displayed.</p>
<p>Students can select multiple or single columns per row, depending on how the question has been configured, and each row receives a grade, defined by one of the grading methods.</p>
<p>The final grade for the question is an average of their grades for each of the rows with the exeption of the Kprime type where all answers have to be correct.</p>';

$string['cols_shorttext'] = 'Antwort';
$string['cols_description'] = 'Description';

//$string['addmorecols'] = 'Add {$a} more columns';

$string['refresh_matrix'] = 'Antwortmatrix neu laden';

//$string['updatematrix'] = 'Update matrix to reflect new options';
$string['matrixheader'] = 'Antwortmatrix';

$string['mustdefine1by1'] = 'You must define at least a 1 x 1 matrix; with either short or long answer defined for each row and column';
$string['mustaddupto100'] = 'The sum of all non negative weights in each row must be 100%';
$string['weightednomultiple'] = 'It doesn\'t make sense to choose weighted grading with multiple answers not allowed';
$string['oneanswerperrow'] = 'You must provide an answer for each row';

$string['shuffleanswers'] = 'Teilaussagen mischen?';
$string['shuffleanswers_help'] = 'Wenn aktiviert ist die Reihenfolge der Teilaussagen bei jedem Versuch zufällig, sofern die Option „In Fragen zufällig mischen“ aktiviert ist.';
$string['show_non_kprime_gui'] = 'Zeigen grafische Benutzeroberfläche für die Optionen, die nicht kprime Matrix-Optionen (mehr als vier Reihen, mehr als zwei columsn, mehrere Optionen) sind streng.';

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

global $CFG;
if (!property_exists($CFG, 'qtype_matrix_show_non_kprime_gui') || $CFG->qtype_matrix_show_non_kprime_gui !== '0') {
    $string['pluginname'] = 'Matrix/Kprim';
    $string['pluginnamesummary'] = 'In Matrix-Fragen müssen verschiedene Aussagen zu einem gemeinsamen Thema bewertet werden. In Kprim-Fragen müssen dabei genau vier Aussagen als „richtig“ oder „falsch“ bewertet werden.';
    $string['pluginnameadding'] = 'Matrix/Kprim-Frage hinzufügen';
    $string['pluginnameediting'] = 'Matrix/Kprim-Frage bearbeiten';
    $string['pluginname_help'] = '<p>Matrix-Fragen bestehen aus einem Item-Stamm, z.B. eine Frage oder eine unvollständige Aussage, und mehreren zugehörigen Teilaussagen, z.B. korrespondierende Antworten und Vervollständigungen. Die Kandidaten müssen diese Teilaussagen als „richtig“ oder „falsch“ bewerten. Es können eigene oder zusätzliche Bewertungskategorien definiert werden.
Kprim-Fragen bestehen aus einem Item-Stamm und vier zugehörigen Teilaussagen. Jede Teilaussage muss als „richtig“ oder „falsch“ bewertet werden.</p>';
    $string['grademethod_help'] = '<ul>
<li><b>Kprim</b>: Bei der Auswahl „Kprim“ erhalten Kandidaten einen Punkt, wenn alle Teilaussagen richtig bewertet wurden, einen halben Punkt, wenn eine Teilaussage falsch und die restlichen richtig bewertet wurden und null Punkte sonst.</li>
<li><b>Kprim1/0</b>: Bei der Auswahl „Kprim“ erhalten Kandidaten einen Punkt, wenn alle Teilaussagen richtig bewertet wurden und null Punkte sonst. Die Bewertungsmethoden Kprim und Kprim1/0 sollten nur für Fragen mit genau vier Teilaussagen verwendet werden.</li>
<li><b>Teilpunkte</b>: Bei der Auswahl „Teilpunkte“ erhalten Kandidaten Teilpunkte für jede richtige Bewertung.</li>
<li><b>Differenz</b>: Lernende erhalten einen Punkt abhängig davon, wie weit ihre gewählte Antwort von einem vorab definierten Wert (korrekte Antwort) abweicht. Die Formel für den Abweichungswert lautet: maximal erreichbarer Abweichungswert – (Lernendenantwort – korrekte Antwort)^2. Der Abweichungswert wird dann in einen anteiligen Punktewert zwischen 0 und 1 transformiert, wobei 1 für eine vollständig richtige Antwort steht.</li>
</ul>';
} else {
    $string['pluginname'] = 'Kprim';
    $string['pluginnamesummary'] = 'In Kprim-Fragen müssen dabei genau vier Aussagen als „richtig“ oder „falsch“ bewertet werden.';
    $string['pluginnameadding'] = 'Kprim-Frage hinzufügen';
    $string['pluginnameediting'] = 'Kprim-Frage bearbeiten';
    $string['pluginname_help'] = '<p>Kprim-Fragen bestehen aus einem Item-Stamm und vier zugehörigen Teilaussagen. Jede Teilaussage muss als „richtig“ oder „falsch“ bewertet werden.</p>';
    $string['grademethod_help'] = '<ul>
<li><b>Kprim</b>: Bei der Auswahl „Kprim“ erhalten Kandidaten einen Punkt, wenn alle Teilaussagen richtig bewertet wurden, einen halben Punkt, wenn eine Teilaussage falsch und die restlichen richtig bewertet wurden und null Punkte sonst.</li>
<li><b>Kprim1/0</b>: Bei der Auswahl „Kprim“ erhalten Kandidaten einen Punkt, wenn alle Teilaussagen richtig bewertet wurden und null Punkte sonst.</li>
<li><b>Teilpunkte</b>: Bei der Auswahl „Teilpunkte“ erhalten Kandidaten Teilpunkte für jede richtige Bewertung.</li>
<li><b>Differenz</b>: Lernende erhalten einen Punkt abhängig davon, wie weit ihre gewählte Antwort von einem vorab definierten Wert (korrekte Antwort) abweicht. Die Formel für den Abweichungswert lautet: maximal erreichbarer Abweichungswert – (Lernendenantwort – korrekte Antwort)^2. Der Abweichungswert wird dann in einen anteiligen Punktewert zwischen 0 und 1 transformiert, wobei 1 für eine vollständig richtige Antwort steht.</li>
</ul>';
}


$string['pluginname_link'] = 'question/type/matrix';

// Gradings.
$string['all'] = 'Teilpunkte';
$string['kany'] = 'Kprim';
$string['kprime'] = 'Kprim1/0';
$string['difference'] = 'Differenz';

// Strings.
$string['true'] = 'Richtig';
$string['false'] = 'Falsch';

// Form.
$string['multipleallowed'] = 'Mehrere Antworten pro Teilaussage erlauben?';

$string['grademethod'] = 'Bewertungsmethode';

$string['rowsheader'] = 'Matrix rows';
$string['rowsheader_desc'] = '<p>Shorttext will be used when it\'s present, with the longer text as a tooltip.<br />Be mindful of how this will be displayed</p>
<p>Students can select multiple or single columns per row, depending on how the question has been configured, and each row receives a grade, defined by one of the grading methods.</p>
<p>The final grade for the question is an average of their grades for each of the rows with the exeption of the Kprime type where all answers have to be correct.</p>';

$string['rows_shorttext'] = 'Teilaussage';
$string['rows_description'] = 'Description';
$string['rows_feedback'] = 'Feedback';

$string['colsheader'] = 'Matrix columns';
$string['colsheader_desc'] = '<p>Shorttext will be used when it\'s present, with the longer text as a tooltip.<br />Be mindful of how this will be displayed.</p>
<p>Students can select multiple or single columns per row, depending on how the question has been configured, and each row receives a grade, defined by one of the grading methods.</p>
<p>The final grade for the question is an average of their grades for each of the rows with the exeption of the Kprime type where all answers have to be correct.</p>';

$string['cols_shorttext'] = 'Antwort';
$string['cols_description'] = 'Description';

$string['refresh_matrix'] = 'Antwortmatrix neu laden';

$string['matrixheader'] = 'Antwortmatrix';

$string['mustdefine1by1'] = 'You must define at least a 1 x 1 matrix; with either short or long answer defined for each row and column';
$string['mustaddupto100'] = 'The sum of all non negative weights in each row must be 100%';
$string['weightednomultiple'] = 'It doesn\'t make sense to choose weighted grading with multiple answers not allowed';
$string['oneanswerperrow'] = 'You must provide an answer for each row';

$string['shuffleanswers'] = 'Teilaussagen mischen?';
$string['shuffleanswers_help'] = 'Wenn aktiviert ist die Reihenfolge der Teilaussagen bei jedem Versuch zufällig, sofern die Option „In Fragen zufällig mischen“ aktiviert ist.';
$string['show_non_kprime_gui'] = 'Zeigen grafische Benutzeroberfläche für die Optionen, die nicht kprime Matrix-Optionen (mehr als vier Reihen, mehr als zwei columsn, mehrere Optionen) sind streng.';

$string['allow_dnd_ui'] = 'Erlaube Drag&Drop in der UI';
$string['allow_dnd_ui_descr'] = 'Wenn erlaubt, haben die Lehrer die Möglichkeit, die Drag&Drop-Funktion für alle Matrix-Fragen zu aktivieren';
$string['use_dnd_ui'] = 'Drag &amp; Drop verwenden ?';
$string['privacy:metadata'] = 'The Kprime/Matrix Question Type plugin does not store any personal data.';

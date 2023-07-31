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
    $string['pluginname'] = 'Matrix/Kprime';
    $string['pluginnamesummary'] = 'Dans les questions matricielles, plusieurs affirmations concernant un sujet commun doivent être évaluées correctement. Dans les questions Kprime, il faut évaluer correctement quatre affirmations de ce type en leur attribuant la mention "vrai" ou "faux".';
    $string['pluginnameadding'] = 'Ajout d\'une question sur Matrix/Kprime';
    $string['pluginnameediting'] = 'Modifier une question sur Matrix/Kprime';
    $string['pluginname_help'] = '<p>Les questions matricielles se composent d\'un énoncé tel qu\'une question ou un énoncé incomplet, et d\'énoncés à réponses multiples, tels que les réponses correspondantes ou les compléments. Les étudiants notent ces énoncés comme "vrais" ou "faux". Il est également possible de définir des évaluations personnalisées pour les énoncés de réponse.
    Les questions de Kprime se composent d\'un énoncé et de quatre propositions de réponse correspondantes. Pour chaque énoncé de réponse, les étudiants doivent décider s\'il est vrai ou faux.</p>';
    $string['grademethod_help'] = '<ul>
<li><b>Kprime</b>: L\'élève reçoit un point si toutes les réponses sont correctes, un demi-point si une réponse est fausse et que le reste des réponses sont correctes, et zéro point sinon.</li>
<li><b>Kprime1/0</b>: L\'étudiant reçoit un point si toutes les réponses sont correctes, et zéro point dans le cas contraire. Les méthodes de notation Kprime et Kprime1/0 ne doivent être utilisées que pour les questions comportant exactement quatre propositions de réponse.</li>
<li><b>Subpoints</b>: L\'élève reçoit des sous-points pour chaque réponse correcte.</li>
<li><b>Différence</b>: L\'étudiant reçoit un point en fonction de l\'écart entre la réponse qu\'il a choisie et une valeur prédéfinie (réponse correcte). La formule pour les scores d\'écart est la suivante : valeur de l\'écart maximal atteignable - (réponse de l\'étudiant - réponse correcte)^2. Le score d\'écart est ensuite transformé en un score de crédit partiel compris entre 0 et 1, où 1 correspond à une réponse correcte.</li>
</ul>';
} else {
    $string['pluginname'] = 'Kprime';
    $string['pluginnamesummary'] = 'Dans les questions de Kprime, quatre affirmations de ce type doivent être correctement évaluées comme "vraies" ou "fausses".';
    $string['pluginnameadding'] = 'Ajout d\'une question sur Kprime';
    $string['pluginnameediting'] = 'Modifier une question sur Kprime';
    $string['pluginname_help'] = '<p>Les questions de Kprime sont composées d\'un énoncé et de quatre propositions de réponse correspondantes. Pour chaque proposition de réponse, les élèves doivent décider si elle est correcte ou incorrecte.</p>';
    $string['grademethod_help'] = '<ul>
<li><b>Kprime</b>: L\'élève reçoit un point si toutes les réponses sont correctes, un demi-point si une réponse est fausse et que le reste des réponses sont correctes, et zéro point sinon.</li>
<li><b>Kprime1/0</b>: L\'élève reçoit un point si toutes les réponses sont correctes, et zéro point dans le cas contraire.</li>
<li><b>Subpoints</b>: L\'élève reçoit des sous-points pour chaque réponse correcte.</li>
<li><b>Différence</b>: L\'étudiant reçoit un point en fonction de l\'écart entre la réponse qu\'il a choisie et une valeur prédéfinie (réponse correcte). La formule pour les scores d\'écart est la suivante : valeur de l\'écart maximal atteignable - (réponse de l\'étudiant - réponse correcte)^2. Le score d\'écart est ensuite transformé en un score de crédit partiel compris entre 0 et 1, où 1 correspond à une réponse correcte.</li>
</ul>';
}


$string['pluginname_link'] = 'question/type/matrix';

// Gradings.
$string['all'] = 'Point partiel';
$string['kany'] = 'Kprime (au moins une réponse correcte et aucune réponse fausse)  ';
$string['kprime'] = 'Kprime1/0';
$string['difference'] = 'Différence';

// Strings.
$string['true'] = 'Vraie';
$string['false'] = 'Fausse';

// Form.
$string['multipleallowed'] = 'Est-ce que plusieurs réponses sont autorisées ?';

$string['grademethod'] = 'Méthode d\'évaluation';

$string['rowsheader'] = 'Lignes';
$string['rowsheader_desc'] = "<p>Le titre est affiché en tête de ligne. La description est utilisée dans un balon d\'aide.<br/></p>
<p>Les étudiants peuvent sélectionner soit une soit plusieurs réponses par ligne en fonction de la configuration. Chaque ligne reçoit une note en fonction de la méthode d\'évaluation choisie.</p>
<p>La note finale est la moyenne des notes de chacune des lignes sauf pour le type Kprime ou il faut avoir toutes les réponses correctes pour obtenir les points.</p>";

$string['rows_shorttext'] = 'Titre';
$string['rows_description'] = 'Description';
$string['rows_feedback'] = 'Commentaires';

$string['colsheader'] = 'Colonnes';
$string['colsheader_desc'] = "<p>Le titre est affiché en-tête des colonnes. La description est affichée dans un balon d\'aide.<br/></p>
<p>Les étudiants peuvent sélectionner soit une soit plusieurs réponses par ligne en fonction de la configuration. Chaque ligne reçoit une note en fonction de la méthode d\'évaluation choisie.</p>
<p>La note finale est la moyenne des notes de chacune des lignes sauf pour le type Kprime ou il faut avoir toutes les réponses correctes pour obtenir les points.</p>";

$string['cols_shorttext'] = 'Titre';
$string['cols_description'] = 'Description';

$string['refresh_matrix'] = 'Rafraichir la matrice';

$string['matrixheader'] = 'Matrice';

$string['mustdefine1by1'] = 'Vous devez définir au minimum une matrice de 1 x 1 avec des titres pour les colonnes et les lignes.';
$string['mustaddupto100'] = 'La somme de toutes les valeurs non-négative doit être égal à 100%%';
$string['weightednomultiple'] = 'Pour choisir une méthode d\'évaluation pondérée il faut activer l\'option "réponses multiples"';
$string['oneanswerperrow'] = 'Vous devez fournir une réponse pour chaque ligne';

$string['shuffleanswers'] = 'Mélanger les réponses ?';
$string['shuffleanswers_help'] = 'Si activé, l\'ordre des réponses sera mélangé au hasard lors de chaque tentative, pour autant que le réglage du test "Mélanger les éléments des questions" soit aussi activé.';
$string['show_non_kprime_gui'] = 'Afficher l\'interface graphique pour les options qui ne sont pas strictement des options de la matrice kprime (plus de quatre lignes, plus de deux colonnes, options multiples).';

$string['allow_dnd_ui'] = 'Permettre l\'utilisation du glisser-déposer';
$string['allow_dnd_ui_descr'] = 'Si activé, les enseignants auront la possibilité d\'activer le glisser-déposer pour la réponse aux questions';
$string['use_dnd_ui'] = 'Utiliser le glisser-déposer ?';
$string['privacy:metadata'] = 'Le plugin Kprime/Matrix Question Type ne stocke aucune donnée personnelle.';

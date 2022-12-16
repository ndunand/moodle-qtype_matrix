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

// Todo: implement missing logic existing in the en/de version + add missing lang strings.

$string['addingmatrix'] = 'Ajout Matrice';
$string['editingmatrix'] = 'Modification Matrice';
$string['matrix'] = 'Matrice';
$string['matrixsummary'] = 'Type de question Matrice';
$string['pluginnameediting'] = 'Modification d\'une question Matrix/Kprime';
$string['matrix_help'] = '<p>Ce type de question permet aux enseignants de définir les lignes et les colonnes qui composent une matrice.</p>
<p>Les étudiants peuvent choisir soit une réponse par ligne soit plusieurs, selon la façon dont a été définie la question. Chaque ligne est évaluée selon la méthode d\'évaluation choisie.</p>
<p>La note finale pour la question est la moyenne des notes de chacune des lignes.</p>';

// Gradings.
$string['all'] = 'Point partiel';
$string['kany'] = "Kprime (au moins une réponse correcte et aucune réponse fausse)  ";
$string['kprime'] = "Kprime1/0";
$string['none'] = 'Pas d\'évaluation';
$string['weighted'] = 'Point partiel';

// Strings.
$string['true'] = 'Vraie';
$string['false'] = 'Fausse';

// Form.
$string['multipleallowed'] = 'Est-ce que plusieurs réponses sont autorisées ?';

$string['grademethod'] = 'Méthode d\'évaluation';
$string['grademethod_help'] = "<p>Ces méthodes concernent généralement les <b>lignes</b> sauf pour le type Kprime1/0. La note totale est la moyenne des notes pour chacune des lignes sauf pour le type Kprime ou il faut avoir toutes les réponses correctes pour obtenir les points.</p>
<ul>
    <li><b>Point partiel :</b> Une fraction de point pour chaque ligne de réponses correctes.</li>
    <li><b>Kprime (au moins une réponse correcte et aucune réponse fausse) :</b> Pour chaque ligne l'étudiant doir choisir au minimum une réponse correcte parmis celles proposées et aucune réponse fausse pour obtenir 100%. Autrement l'étudiant obtient 0%. Si seulement une ligne est fausse alors le score est de 0.0. S'il y plus d'une ligne fausse le score est de 0.</li>
    <li><b>Kprime1/0 :</b> 1 point si toutes les réponses sont correctes, 0 point autrement.</li>
</ul>";

$string['rowsheader'] = 'Lignes';
$string['rowsheaderdesc'] = "<p>Le titre est affiché en tête de ligne. La description est utilisée dans un balon d\'aide.<br/></p>
<p>Les étudiants peuvent sélectionner soit une soit plusieurs réponses par ligne en fonction de la configuration. Chaque ligne reçoit une note en fonction de la méthode d\'évaluation choisie.</p>
<p>La note finale est la moyenne des notes de chacune des lignes sauf pour le type Kprime ou il faut avoir toutes les réponses correctes pour obtenir les points.</p>";

$string['rows_shorttext'] = 'Titre';
$string['rows_description'] = 'Description';
$string['rows_feedback'] = 'Commentaires';

$string['colsheader'] = 'Colonnes';
$string['colsheaderdesc'] = "<p>Le titre est affiché en-tête des colonnes. La description est affichée dans un balon d\'aide.<br/></p>
<p>Les étudiants peuvent sélectionner soit une soit plusieurs réponses par ligne en fonction de la configuration. Chaque ligne reçoit une note en fonction de la méthode d\'évaluation choisie.</p>
<p>La note finale est la moyenne des notes de chacune des lignes sauf pour le type Kprime ou il faut avoir toutes les réponses correctes pour obtenir les points.</p>";

$string['cols_shorttext'] = 'Titre';
$string['cols_description'] = 'Description';

$string['refresh_matrix'] = 'Rafraichir la matrice';

$string['matrixheader'] = 'Matrice';

$string['mustdefine1by1'] = 'Vous devez définir au minimum une matrice de 1 x 1 avec des titres pour les colonnes et les lignes.';
$string['mustaddupto100'] = 'La somme de toutes les valeurs non-négative doit être égal à 100%%';
$string['weightednomultiple'] = 'Pour choisir une méthode d\'évaluation pondérée il faut activer l\'option "réponses multiples"';
$string['selectcorrectanswers'] = 'Définition des réponses correctes';

$string['shuffleanswers'] = 'Mélanger les réponses ?';
$string['shuffleanswers_help'] = 'Si activé, l\'ordre des réponses sera mélangé au hasard lors de chaque tentative, pour autant que le réglage du test "Mélanger les éléments des questions" soit aussi activé.';

$string['allow_dnd_ui'] = 'Permettre l\'utilisation du glisser-déposer';
$string['allow_dnd_ui_descr'] = 'Si activé, les enseignants auront la possibilité d\'activer le glisser-déposer pour la réponse aux questions';
$string['use_dnd_ui'] = 'Utiliser le glisser-déposer ?';

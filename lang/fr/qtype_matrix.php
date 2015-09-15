<?php 

// qtype strings
$string['addingmatrix'] = 'Ajout Matrice';
$string['editingmatrix'] = 'Modification Matrice';
$string['matrix'] = 'Matrice';
$string['matrixsummary'] = 'Type de question Matrice';
$string['pluginnameediting'] = 'Modification d\'une question Matrix/Kprime';
$string['matrix_help'] = '<p>Ce type de question permet aux enseignants de définir les lignes et les colonnes qui composent une matrice.</p>
<p>Les étudiants peuvent choisir soit une réponse par ligne soit plusieurs, selon la façon dont a été définie la question. Chaque ligne est évaluée selon la méthode d\'évaluation choisie.</p>
<p>La note finale pour la question est la moyenne des notes de chacune des lignes.</p>';

//gradings
$string['all'] = 'Toutes les réponses correctes et aucune réponse fausse';
$string['kany'] = "Kprime (au moins une réponse correcte et aucune réponse fausse)  ";
$string['kprime'] = "Kprime";
$string['none'] = 'Pas d\'évaluation';
$string['weighted'] = 'Point partiel';

//strings
$string['true'] = 'Vraie';
$string['false'] = 'Fausse';

// form 
$string['multipleallowed'] = 'Est-ce que plusieurs réponses sont autorisées ?';

$string['grademethod'] = 'Méthode d\'évaluation';
$string['grademethod_help'] = "<p>Il y a plusieurs méthodes d\'évaluation pour le type matrice.</p>
<p>Ces méthodes concerne généralement les <b>lignes</b> sauf pour le type Kprime La note totale est la moyenne des notes pour chacune des lignes sauf pour le type K' ou il faut avoir toutes les réponses correctes pour obtenir les points.</p>
<table>
<tr><td><b>Kprime</b></td><td>1 point si toutes les réponses sont correctes, 0 point autrement</td></tr>
<tr><td><b>Kprime (au moins une réponse correcte et aucune réponse fausse)</b></td><td>Pour chaque ligne l\'étudiant doir choisir au minimum une réponse correcte parmis celles proposées et aucune réponse fausse pour obtenir 100%. Autrement l\'étudiant obtient 0%. Si seulement une ligne est fausse alors le score est de 0.0. S\'il y plus d\'une ligne fausse le score est de 0.</td></tr>
<tr><td><b>Point partiel</b></td><td>une fraction de point pour chaque ligne de réponses correctes.</td></tr></table>";

//$string['renderer'] = 'Rendu';

$string['rowsheader'] = 'Lignes';
$string['rowsheaderdesc'] = "<p>Le titre est affiché en tête de ligne. La description est utilisée dans un balon d\'aide.<br/></p>
<p>Les étudiants peuvent sélectionner soit une soit plusieurs réponses par ligne en fonction de la configuration. Chaque ligne reçoit une note en fonction de la méthode d\'évaluation choisie.</p>
<p>La note finale est la moyenne des notes de chacune des lignes sauf pour le type Kprime ou il faut avoir toutes les réponses correctes pour obtenir les points.</p>";

$string['rows_shorttext'] = 'Titre';
$string['rows_description'] = 'Description';
$string['rows_feedback'] = 'Commentaires';

//$string['addmorerows'] = 'Ajouter {$a} ligne(s) de plus';

$string['colsheader'] = 'Colonnes';
$string['colsheaderdesc'] =  "<p>Le titre est affiché en-tête des colonnes. La description est affichée dans un balon d\'aide.<br/></p>
<p>Les étudiants peuvent sélectionner soit une soit plusieurs réponses par ligne en fonction de la configuration. Chaque ligne reçoit une note en fonction de la méthode d\'évaluation choisie.</p>
<p>La note finale est la moyenne des notes de chacune des lignes sauf pour le type Kprime ou il faut avoir toutes les réponses correctes pour obtenir les points.</p>";

$string['cols_shorttext'] = 'Titre';
$string['cols_description'] = 'Description';

//$string['addmorecols'] = 'Ajouter {$a} colonne(s) de plus';

$string['refresh_matrix'] = 'Rafraichir la matrice';

//$string['updatematrix'] = 'Mettre la matrice à jour pour refléter les nouvelles options choisies';
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

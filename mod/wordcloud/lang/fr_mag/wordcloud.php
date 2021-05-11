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

/**
 *
 * Language strings for the wordcloud module
 *
 * @package    mod
 * @subpackage wordcloud
 * @copyright  TCS 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

$string['modulename'] = 'Nuage de mots (Beta)';
$string['modulename_help'] = '';
$string['modulenameplural'] = 'Nuages de mots';
$string['pluginname'] = 'Nuage de mots (Beta)';

$string['pluginconfig'] = 'Configuration du Nuage de mots';

$string['wordmaxlenght'] = 'Longueur maximale des mots';
$string['wordmaxlenghtsetting'] = 'Limite globale de la longueur maximale des mots';

$string['maxwordsallowed'] = 'Nombre maximum de propositions par participant';
$string['maxwordsallowedsetting'] = 'Limite globale du nombre maximum de mots demandé à un participant';
$string['wordrequired'] = 'Nombre minimum de propositions par participant';

$string['allowsubmitionfrom'] = 'Autoriser les réponses à partir du';
$string['allowsubmitionupto'] = 'Autoriser les réponses jusqu\'au';

$string['name'] = 'Nom du nuage de mots';
$string['description'] = 'Description';
$string['instructions'] = 'Consigne';

$string['wordmaxlenght_undefined'] = 'La longueur maximale des mots est requise';
$string['wordmaxlenght_tolong'] = 'La longueur maximale des mots est trop longue';
$string['maxwordrequired_undefined'] = 'Le nombre de mots minimum est requis';
$string['maxwordrequired_tomany'] = 'Le nombre de mots minimum est trop grand';
$string['maxwordsallowed_undefined'] = 'Nombre de mots maximum est requis';
$string['maxwordsallowed_tomany'] = 'Le nombre de mots maximum est trop grand';
$string['maxwordrequired_bigger_than_allowed'] = 'Le nombre de mots minimum ne peut pas être inférieur au nombre de mots maximum demandés';

$string['timeend_before_start'] = 'La date de fin des réponses ne peut pas être avant le début de la période autorisée !';

$string['pluginadministration'] = 'Administration du Nuage de mots';

$string['activity_will_be_reseted'] = 'Attention, l\'activité va être réinitialisée si la longueur ou le nombre de mots requis sont modifié! La totalité des participations à cette activité seront perdues';
$string['submitions_wont_be_altered'] = 'Les propositions existantes ne seront pas impactées par la modification de cette valeur.';

$string['word_nb'] = 'Mot n°';
$string['submitword_submit'] = 'Valider mes mots';
$string['send'] = 'Envoyer';

$string['missingword'] = 'Vous devez saisir un mot !';
$string['word_already_used'] = 'Vous avez déjà saisi ce mot !';

$string['nosubmition'] = 'Aucun participant n\'a réalisé l\'activité pour l\'instant.';
$string['onesubmition'] = '{$a} participant a transmis ses propositions.';
$string['multi_submition'] = '{$a} participants ont transmis leurs propositions.';

$string['exporttoimage'] = 'Exporter l\'image';
$string['exportdata'] = 'Exporter les données';

$string['noworddeleted'] = 'Aucun mot supprimé';
$string['oneworddeleted'] = 'Un mot supprimé';
$string['nwordsdeleted'] = '{$a} mots supprimés';

$string['wordadded'] = 'Mot ajouté avec succès';
$string['wordalreadyexist'] = 'Vous avez déjà ajouté ce mot';
$string['wordisnotvalid'] = 'Le mot n\'est pas valide';
$string['wordistoolong'] = 'Le mot est trop long';

$string['oldwordnotfound'] = 'Ancien mot introuvable';

$string['wordweight'] = 'Poids du mot';
$string['addword'] = 'Ajouter un mot';
$string['add'] = 'Ajouter';
$string['word'] = 'Mot';
$string['updateword'] = 'Modifier';
$string['removeword'] = 'Supprimer';
$string['updateaword'] = 'Modifier un mot';


$string['activitenotstarted'] = 'Ce nuage de mots ne sera pas disponible avant le {$a}.';
$string['activityclosed'] = 'Nuage de mots fermé le {$a}. Merci.';

$string['student_can_submit_from'] = 'Les participants pourront répondre à partir du {$a}';
$string['student_can_submit_upto'] = 'Les participants ne pourront plus répondre à partir du {$a}';
$string['student_cant_submit_since'] = 'Les participants ne peuvent plus répondre depuis le {$a}';

$string['group'] = 'Groupe';
$string['empty_wordcloud'] = 'Ce nuage de mots est vide';

$string['canceledit'] = 'Annuler';

$string['wordusers'] = 'Participants concernés :';

$string['completionwordsgroup'] = 'Mots requis';
$string['completionwords'] = 'Le participant doit proposer le nombre minimum de mots';

$string['csv_word'] = 'Mot';
$string['csv_user'] = 'Participant';
$string['csv_date'] = 'Horodatage';

$string['privacy:metadata:wordcloud_words'] = 'Informations sur les nuages de mots.';
$string['privacy:metadata:wordcloud_words:groupid'] = 'L\'id du groupe auquel appartient l\'utilisateur.';
$string['privacy:metadata:wordcloud_words:userid'] = 'L\'id de l\'utilisateur.';
$string['privacy:metadata:wordcloud_words:word'] = 'Le mot entré par l\'utilisateur.';
$string['privacy:metadata:wordcloud_words:timecreated'] = 'Horodatage de la création de l\'enregistrement.';
$string['privacy:metadata:wordcloud_words:timemodified'] = 'Horodatage de la dernière modification de l\'enregistrement.';

$string['wordcloud:addinstance'] = 'Ajout d\'un mot au nuage de mot';
$string['wordcloud:submitword'] = 'Soumettre un mot au nuage de mot';
$string['wordcloud:manageword'] = 'Gérer les mots d\'un nuage';
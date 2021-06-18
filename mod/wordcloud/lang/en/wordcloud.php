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

$string['modulename'] = 'WordCloud';
$string['modulename_help'] = '';
$string['modulenameplural'] = 'WordClouds';
$string['pluginname'] = 'WordCloud';

$string['pluginconfig'] = 'Wordcloud configuration';

$string['resetsubmissions'] = 'Reset all wordclouds submissions';
$string['resetsubmissions_help'] = 'This will remove every words submitted in all the wordcloud of this course';

$string['wordmaxlenght'] = 'Word max lenght';
$string['wordmaxlenghtsetting'] = 'Global limit for the words lenght';

$string['maxwordsallowed'] = 'Maximum allowed words';
$string['maxwordsallowedsetting'] = 'Global setting to limit the maximum number of words an activity can ask a student';
$string['wordrequired'] = 'Required words';


$string['allowsubmitionfrom'] = 'Allow submition from';
$string['allowsubmitionupto'] = 'Allow submition up to';

$string['name'] = 'Nom du nuage de mots';
$string['description'] = 'Description';
$string['instructions'] = 'Instructions';

$string['wordmaxlenght_undefined'] = 'Word max lenght is required';
$string['wordmaxlenght_tolong'] = 'Word max lenght is to long';
$string['maxwordrequired_undefined'] = 'Number of words is required';
$string['maxwordrequired_tomany'] = 'Too many words required';
$string['maxwordsallowed_undefined'] = 'Number of words is required';
$string['maxwordsallowed_tomany'] = 'Too many words required';
$string['maxwordrequired_bigger_than_allowed'] = 'The number of required word have too be lower than allowed words';
$string['timeend_before_start'] = 'Submition end date can\'t be before the start date';

$string['pluginadministration'] = 'Wordcloud administration';

$string['activity_will_be_reseted'] = 'Warning, the activity will be reseted if the wrod max lenght or the number of required words is changed. All data will be lost';
$string['submitions_wont_be_altered'] = 'The already submitted words won\'t be altered';

$string['word_nb'] = 'Word n°';
$string['submitword_submit'] = 'Submit my words';
$string['send'] = 'Send';

$string['missingword'] = 'This word is missing';
$string['word_already_used'] = 'This word is already used!';


$string['nosubmition'] = 'No student as add words';
$string['onesubmition'] = '{$a} student submit his words';
$string['multi_submition'] = '{$a} students submit their words';


$string['exporttoimage'] = 'Export to image';
$string['exportdata'] = 'Export data';

$string['noworddeleted'] = 'No word deleted';
$string['oneworddeleted'] = 'One words deleted';
$string['nwordsdeleted'] = '{$a} words deleted';

$string['wordadded'] = 'Word added successfully';
$string['wordalreadyexist'] = 'You already add this word';
$string['word1isnotvalid'] = 'The word is not valid';
$string['wordistoolong'] = 'The word is too long';

$string['oldwordnotfound'] = 'Old word not found';
$string['newwordisthesame'] = 'The new word is the same as the old one, we can\'t rename it';

$string['wordweight'] = 'Word weight';
$string['addword'] = 'Add word';
$string['add'] = 'Add';
$string['word'] = 'Word';

$string['updateword'] = 'Update word';
$string['removeword'] = 'Remove the word';
$string['updateaword'] = 'Update a word';
$string['wordupdated'] = 'Word updated';

$string['activitenotstarted'] = 'Ce nuage de mots ne sera pas disponible avant le {$a}.';
$string['activityclosed'] = 'Nuage de mots fermé le {$a}. Merci.';


$string['student_can_submit_from'] = 'Les participants pourront répondre à partir du {$a}';
$string['student_can_submit_upto'] = 'Les participants ne pourront plus répondre à partir du {$a}';
$string['student_cant_submit_since'] = 'Les participants ne peuvent plus répondre depuis le {$a}';


$string['group'] = 'Group';
$string['empty_wordcloud'] = 'This Wordcloud is empty';

$string['canceledit'] = 'Cancel word edition';

$string['wordusers'] = 'Users that added this word :';

$string['completionwordsgroup'] = 'Required word submited';
$string['completionwords'] = 'The student have to submit words';

$string['csv_word'] = 'Word';
$string['csv_user'] = 'User';
$string['csv_date'] = 'Date';

$string['privacy:metadata:wordcloud_words'] = 'Information about the words entered by the users.';
$string['privacy:metadata:wordcloud_words:groupid'] = 'Group id';
$string['privacy:metadata:wordcloud_words:userid'] = 'User id';
$string['privacy:metadata:wordcloud_words:word'] = 'The word given by the user.';
$string['privacy:metadata:wordcloud_words:timecreated'] = 'Date and time of the record creation.';
$string['privacy:metadata:wordcloud_words:timemodified'] = 'Date and time of the record last update.';

$string['wordcloud:addinstance'] = 'Add a new wordcloud instance';
$string['wordcloud:submitword'] = 'Submit a word to a wordcloud';
$string['wordcloud:manageword'] = 'Manage words of a wordcloud';



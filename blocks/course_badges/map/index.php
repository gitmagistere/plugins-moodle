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
 * Lists all the users within a given course.
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core_user
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/blocks/course_badges/lib.php');
if (!isInteractiveActionAvailable()){
    throw new Exception(get_string('nointeractivemappluginsavailable', 'block_course_badges'));
}
require_once($CFG->dirroot.'/local/interactive_map/InteractiveMap.php');
require_once($CFG->dirroot.'/lib/badgeslib.php');
require_once($CFG->dirroot.'/blocks/course_badges/overview/Filters.php');
require_once($CFG->dirroot.'/blocks/course_badges/map/map_form.php');

$courseid = required_param('id', PARAM_INT); // This are required.
$badgeid = optional_param(map_form::BADGE_SELECT_FIELD, -1, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$ps = 24; // pagesize

// if only one badge is available, select it
$listBadge = Filters::get_list_badges($courseid);
if($badgeid < 0 && count($listBadge) == 1){
    $badgeid = reset($listBadge)->id;
}

if($courseid == SITEID){
    redirect(new moodle_url('/'));
}

$baseurl = new moodle_url('/blocks/course_badges/map/index.php', ['id' => $courseid]);

$course = $DB->get_record('course', ['id' => $courseid]);
$PAGE->set_url($baseurl);

require_login($course);

$context = context_course::instance($courseid);

// set a nice white background to the page
$PAGE->set_pagelayout('incourse');

// remove the side post region
$PAGE->add_body_class('empty-region-side-post');

$PAGE->requires->css('/blocks/course_badges/styles.css');
$PAGE->requires->css('/blocks/course_badges/map/select.css');
$PAGE->requires->css('/blocks/course_badges/map/form.css');

$innerbadge = '';
$where = '';
$params = [];

if ($badgeid > 0) {
    $innerbadge = 'INNER JOIN {badge_issued} bi ON bi.userid=u.id
    INNER JOIN {badge} b ON b.id=bi.badgeid';

    $where = ' AND b.id=:badgeid AND (b.status=:active1 OR b.status=:activelocked1) AND b.courseid=e.courseid';

    $params['active1'] = BADGE_STATUS_ACTIVE;
    $params['activelocked1'] = BADGE_STATUS_ACTIVE_LOCKED;
    $params['badgeid'] = $badgeid;
}else{
    $where = ' AND 1=0';
}

$sql =
    'SELECT 
DISTINCT u.id,
u.*, tuai.appelation_officielle, tuai.ville, tuai.coordonnee_lat, tuai.coordonnee_long, av.hashname AS picturehash,
(
    SELECT GROUP_CONCAT(CONCAT(ti.contextid,"||",t.name) SEPARATOR "&&") FROM {tag_instance} ti
    INNER JOIN {tag} t ON (t.id = ti.tagid)
    WHERE ti.itemid = u.id AND ti.itemtype = "user"
) AS interests,
(
    SELECT COUNT(bi.id)
    FROM {badge} b 
    INNER JOIN {badge_issued} bi ON bi.badgeid=b.id
    WHERE bi.userid=u.id AND b.courseid=e.courseid AND (b.status=:active OR b.status=:activelocked)
) AS badges
FROM {enrol} e 
INNER JOIN {user_enrolments} ue ON ue.enrolid=e.id
INNER JOIN {user} u ON u.id=ue.userid
LEFT JOIN {user_info_data} uid ON (uid.userid = u.id AND uid.fieldid = (SELECT id FROM {user_info_field} WHERE shortname = "rne" ))
LEFT JOIN {t_uai} tuai ON (uid.data = tuai.code_rne)
LEFT JOIN '.$CFG->centralized_dbname.'.cr_avatars av ON (av.id = u.picture)'
.$innerbadge.'
WHERE e.courseid= :courseid'.$where;


$params['courseid'] = $courseid;
$params['active'] = BADGE_STATUS_ACTIVE;
$params['activelocked'] = BADGE_STATUS_ACTIVE_LOCKED;

$participantslist = [];

if($badgeid > 0){
    $participantslist = $DB->get_records_sql($sql, $params);
}

$PAGE->set_title("$course->shortname: ".get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->add_body_class('path-user');                     // So we can style it independently.
$PAGE->set_other_editing_capability('moodle/course:manageactivities');

$interactiveMap = new InteractiveMap('participantmap');
$interactiveMap->load_css($PAGE);
$interactiveMap->load_js($PAGE);
$interactiveMap->set_view(46.5, 2, 5);
$interactiveMap->set_mapheight('500px');

$group = [];
$htmlparticipantslist = '';

foreach($participantslist AS $participant)
{
    if($participant->coordonnee_lat === null){
        continue;
    }

    $messagelink = new moodle_url('/message/index.php', array('id'=>$participant->id));

    $taglist = '<div class="tag_list hideoverlimit"><ul class="inline-list">';
    $profilelink = new moodle_url('/user/view.php', array('id'=>$participant->id, 'course'=>$courseid));
    if (strlen($participant->interests) > 5)
    {
        $i=0;
        $interests = explode('&&',$participant->interests);
        foreach ($interests AS $interesta)
        {
            $interest = explode('||', $interesta);
            if (count($interest) != 2) {continue;}

            $i++;
            if ($i > 5)
            {
                $taglist .= '<li><a href="'.$profilelink.'" target="_blank" class="label label-info">...</a></li>';
                break;
            }
            $tagurl = new moodle_url("/tag/index.php",array('tc'=>1,'tag'=>$interest[1],'from'=>$interest[0]));
            $taglist .= '<li><a href="'.$tagurl.'" target="_blank" class="label label-info">'.$interest[1].'</a></li>';

        }
    }
    $taglist .= '</ul></div>';

    $popup = html_writer::start_tag('table', [
        'border' => '0',
        'cellspacing' => '0',
        'style' => 'font-family: "open sans";'
    ]);

    $popup .= html_writer::start_tag('tr');

    // avatar column
    $picture_url = secure_url('/avatar/img.png',$participant->picturehash,$CFG->secure_link_timestamp_default);
    $popup .= html_writer::start_tag('td', ['style' => 'width: 45px; padding: 0 10px 0 0;vertical-align: initial']);
    $img = html_writer::img($picture_url, '', ['height' => '50px']);
    $popup .= html_writer::link($profilelink, $img, ['target' => '_blank']);
    $popup .= html_writer::end_tag('td');

    $popup .= html_writer::start_tag('td');
    $popup .= html_writer::span($participant->firstname.' '.$participant->lastname, 'leaflet-name');
    $popup .= html_writer::empty_tag('br');
    $popup .= html_writer::span(ucwords($participant->appelation_officielle).' - '.ucwords($participant->ville), 'leaflet-institute');
    $popup .= html_writer::empty_tag('br');

    // profil link
    $i = html_writer::tag('i', '', ['class' => 'fa fa-user', 'aria-hidden' => 'true']);
    $popup .= html_writer::link($profilelink, $i.' Voir le profil', ['target' => '_blank', 'class' => 'leaflet-viewprofil']);
    $popup .= ' | ';

    // message link
    $i = html_writer::tag('i', '', ['class' => 'fa fa-envelope-o', 'aria-hidden' => 'true']);
    $popup .= html_writer::link($messagelink, $i.' Contacter', ['target' => '_blank', 'class' => 'leaflet-contact']);

    // badges link
    $popup .= ' | ';
    $i = html_writer::tag('i', '', ['class' => 'fa fa-shield-alt', 'aria-hidden' => 'true']);
    $label = get_string('badgepopuplabel', 'local_interactive_map', $participant->badges);
    if($participant->badges > 1){
        $label .= 's';
    }
    $popup .= html_writer::link($profilelink, $i.$label, ['target' => '_blank', 'class' => 'leaflet-contact']);

    $popup .= html_writer::end_tag('td');

    $popup .= html_writer::end_tag('tr');

    $popup .= html_writer::start_tag('tr');
    $popup .= html_writer::tag('td', '<hr>', ['colspan' => '2']);
    $popup .= html_writer::end_tag('tr');

    // tags
    $popup .= html_writer::start_tag('tr');
    $popup .= html_writer::tag('td', $taglist, ['colspan' => '2']);
    $popup .= html_writer::end_tag('tr');

    $popup .= html_writer::end_tag('table');

    $popup = str_replace('"', '\\"', $popup);

    $group[] = $interactiveMap->create_marker($participant->coordonnee_lat,$participant->coordonnee_long,$popup);
}

foreach(array_slice($participantslist, $page*$ps, $ps) as $participant){
    $picture_url = secure_url('/avatar/img.png',$participant->picturehash,$CFG->secure_link_timestamp_default);
    $profilelink = new moodle_url('/user/view.php', array('id'=>$participant->id, 'course'=>$courseid));

    $username = $participant->firstname.' '.$participant->lastname;
    $img = html_writer::img($picture_url, $username, ['title' => $username]);
    $htmlparticipantslist .= html_writer::start_div('userlistuser');
    $text = $img.'<br>';
    $text .= html_writer::tag('span', $username);
    $htmlparticipantslist .= html_writer::link($profilelink, $text, ['target' => '_blank']);
    $htmlparticipantslist .= html_writer::end_div();
}


$interactiveMap->add_marker_group($group);

$interactiveMap->generate_js();

echo $OUTPUT->header();

$badgename = '';
if($badgeid > 0){
    $badgename = Filters::get_list_badges($COURSE->id)[$badgeid]->name;
}
echo encart_block_badge($badgeid, $badgename, $courseid);

$mapForm = new map_form(null, ['badgeid' => $badgeid]);
$mapForm->display();

echo $interactiveMap->getMap();

if($htmlparticipantslist){
    echo html_writer::tag('h3', get_string('userlistlabel', 'block_course_badges'), ["class" => "badgesubtitle"]);
    echo html_writer::div($htmlparticipantslist, 'userslist');

    $nbpart = count($participantslist);
    if($nbpart > $ps){
        echo html_writer::start_div('link_navigation');

        $cpage = ceil($nbpart/$ps);
        list($prev, $next) = get_nav_links($page, $cpage, $badgeid, $courseid);

        if($prev){
            echo html_writer::link($prev, get_string('previousnavlink', 'block_course_badges'), ['class' => 'prev']);
        }

        if($next){
            echo html_writer::link($next, get_string('nextnavlink', 'block_course_badges'), ['class' => 'next']);
        }

        echo html_writer::end_div();
    }

}else if($badgeid > 0){
    echo html_writer::tag('p', get_string('nouserwonthisbadge', 'block_course_badges'), ['class' => 'noresult']);
}

echo $OUTPUT->footer();

function get_nav_links($page, $cpage, $badgeid, $courseid)
{
    $result = [null, null];

    if($page < $cpage-1){
        $result[1] = new moodle_url('/blocks/course_badges/map/index.php', [
            'page' => $page+1,
            map_form::BADGE_SELECT_FIELD => $badgeid,
            'id' => $courseid
        ]);
    }

    if($page > 0){
        $result[0] = new moodle_url('/blocks/course_badges/map/index.php', [
            'page' => $page-1,
            map_form::BADGE_SELECT_FIELD => $badgeid,
            'id' => $courseid
        ]);
    }

    return $result;
}

function encart_block_badge($badgeid, $badgename, $courseid)
{
    global $OUTPUT;

    // css classes are reused from the meth add_encart_activity from $OUTPUT

    $courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);
    $title = "Retour au parcours de formation";
    $html = html_writer::link($courseurl, $title, ['class' => 'activity-encart-backButton']); //html_writer::start_div('encar-block-course-badges');

    if($badgeid > 0){
        $urlimg = get_img_url_badge($badgeid, context_course::instance($courseid)->id);
        $badgearea = html_writer::img($urlimg, '');
        $badgearea .= html_writer::span(' '.$badgename, 'badgename');
        $html .= html_writer::div($badgearea);
    }

    return html_writer::div($html, 'activity-encart topics');
}
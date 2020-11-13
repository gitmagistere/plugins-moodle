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
 * @package    mod_forum
 * @subpackage backup-moodle2
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_forum_activity_task
 */

/**
 * Structure step to restore one forum activity
 */
class restore_coursebadges_activity_structure_step extends restore_activity_structure_step
{

    protected function define_structure()
    {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('coursebadges', '/activity/coursebadges');
        if ($this->get_setting_value('badges')) {
            $paths[] = new restore_path_element('selection_badge', '/activity/coursebadges/selection_badges/selection_badge');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_coursebadges($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // insert the label record
        $newitemid = $DB->insert_record('coursebadges', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_selection_badge($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // set the id to negative to allow to retrieve it later
        $data->badgeid = $data->badgeid * -1;

        $data->coursebadgeid = $this->get_new_parentid('coursebadges');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('coursebadges_available_bdg', $data);
        $this->set_mapping('selection_badge', $oldid, $newitemid);
    }

    protected function after_restore()
    {
        global $DB;

        // here we retrieve all the negative ids
        // and map them to the correct badgeid
        // because we are sure that the mapping is available

        $id = $this->get_new_parentid('coursebadges');

        $selections = $DB->get_records_sql('SELECT * FROM {coursebadges_available_bdg} csb WHERE csb.coursebadgeid=? AND csb.badgeid < 0', [$id]);

        foreach($selections as $selection){
            $bid = $selection->badgeid*-1;

            $selection->badgeid = $this->get_mappingid('badge', $bid);

            $DB->update_record('coursebadges_available_bdg', $selection);
        }
    }


    protected function after_execute() {
        // Add label related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_coursebadges', 'intro', null);
    }
}
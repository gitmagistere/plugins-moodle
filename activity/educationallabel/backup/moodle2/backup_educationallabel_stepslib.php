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
 * Define the complete label structure for backup, with file and id annotations
 */
class backup_educationallabel_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $label = new backup_nested_element('educationallabel', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'type', 'timemodified'));

        // Build the tree
        // (love this)

        // Define sources
        $label->set_source_table('educationallabel', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations
        // (none)

        // Define file annotations
        $label->annotate_files('mod_educationallabel', 'intro', null); // This file area hasn't itemid

        // Return the root element (label), wrapped into standard activity structure
        return $this->prepare_activity_structure($label);
    }
}

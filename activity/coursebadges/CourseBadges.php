<?php
require_once($CFG->libdir.'/badgeslib.php');
require_once($CFG->libdir.'/datalib.php');

class CourseBadges{
    const VALIDATED_CHOICE_NOTIF = 2;
    const UPDATED_CHOICE_NOTIF = 1;
    const NO_NOTIF = 0;

    const ALWAYS_SHOW_RESULTS = 2;
    const SHOW_RESULTS_AFTER_RESPONSE = 1;
    const NO_RESULT = 0;

    private $id;
    private $data;
    private $userid;
    private $context;
    private $context_module;
    private $instance;


    function __construct($id = null, $data = null){
        global $USER;
        $this->id = $id;
        $this->data = $data;
        $this->userid = $USER->id;
        $this->context = $this->get_context();

        if($this->id){
            $this->instance = $this->get_course_badges_instance();
            $this->context_module = $this->get_context_module();
        }
    }

    /**
     * Function qui récupère le context du parcours
     *
     * @return \context
     */
    public function get_context() {
        global $COURSE;

        return context_course::instance($COURSE->id);
    }

    /**
     * Function qui récupère le context du module
     *
     * @return \context
     */
    public function get_context_module() {
        if (!$cm = get_coursemodule_from_instance('coursebadges', $this->id)) {
            print_error('Course Module ID was incorrect'); // NOTE this is invalid use of print_error, must be a lang string id
        }
        return context_module::instance($cm->id);
    }

    /**
     *
     * @return bool|mixed
     */
    public function get_course_badges_instance(){
        global $DB;
        try{
            return $DB->get_record('coursebadges', ['id' => $this->id]);
        } catch (moodle_exception $e){
            debugging('Error retrieving ' . $this->id . ' course badges cannot be charged: ' .
                $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Fonction qui créé une nouvelle instance de l'activité
     * @return bool|int
     */
    public function set_course_badges_instance(){
        global $DB;
        try{
            $this->data->completionvalidatedbadges = !empty($this->data->completionvalidatedbadges) ? 1 : 0;
            if($this->data->showcoursebadgesbloc == 1){
                // On vérifie l'existance d'une instance de bloc Choix de badges
                $hascoursebadgesbloc = CourseBadges::has_block_course_badges_instance();
                if(!$hascoursebadgesbloc){
                    // On créé une instance si non présente sur le parcours
                    $this->create_course_badges_block_instance();
                }
            }
            $course_badge = $DB->insert_record('coursebadges', $this->data);
            if(!empty($this->data->rightlistids)){
                $pre_select_badgeids = explode(",", $this->data->rightlistids);

                $list_pre_select = [];
                foreach ($pre_select_badgeids as $pre_select_badgeid) {
                    $selection_badges = new stdClass();
                    $selection_badges->coursebadgeid = $course_badge;
                    $selection_badges->badgeid = $pre_select_badgeid;
                    $selection_badges->timemodified = time();
                    $list_pre_select[] = $selection_badges;
                }
                $DB->insert_records('coursebadges_available_bdg', $list_pre_select);
            }
            return $course_badge;
        } catch (moodle_exception $e){
            debugging('Error retrieving ' . $this->data->name . ' course badges cannot be created: ' .
                $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Fonction de mise à jour d'une instance de l'activité coursebadges
     * @return bool
     */
    public function update_course_badges_instance(){
        global $DB;
        try{
            $this->data->id = $this->id;
            $this->data->completionsubmit = !empty($this->data->completionsubmit);
            if (!empty($this->data->completionunlocked)) {
                $this->data->completionsubmit = !empty($this->data->completionsubmit);
            }
            if($this->data->showcoursebadgesbloc == 1){
                // On vérifie l'existance d'une instance de bloc Choix de badges
                $hascoursebadgesbloc = CourseBadges::has_block_course_badges_instance();
                if(!$hascoursebadgesbloc){
                    // On créé une instance si non présente sur le parcours
                    $this->create_course_badges_block_instance();
                }
            }
            $all_user_choices = $this->get_all_user_choices_by_course_badges_instance();

            if(count($all_user_choices) == 0){
                if(!empty($this->data->rightlistids)){
                    // Suppression des choix de badges proposés
                    $DB->delete_records('coursebadges_available_bdg', ['coursebadgeid' => $this->id]);

                    // Ajout des choix proposés
                    $pre_select_badgeids = explode(",", $this->data->rightlistids);

                    $list_pre_select = [];
                    foreach ($pre_select_badgeids as $pre_select_badgeid) {
                        $selection_badges = new stdClass();
                        $selection_badges->coursebadgeid = $this->id;
                        $selection_badges->badgeid = $pre_select_badgeid;
                        $selection_badges->timemodified = time();
                        $list_pre_select[] = $selection_badges;
                    }
                    $DB->insert_records('coursebadges_available_bdg', $list_pre_select);
                }
            }
            $course_badge = $DB->update_record('coursebadges', $this->data);
            return $course_badge;
        } catch (moodle_exception $e){
            debugging('Error retrieving ' . $this->data->name . ' course badges cannot be created: ' .
                $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Fonction permettant de supprimer une instance de l'activité avec ses dépendances
     * @return bool
     */
    public function unset_course_badges_instance(){
        global $DB;
        try{
            // Suppression de tous les choix utilisateurs
            CourseBadges::delete_all_user_choices_by_course_badges_instance($this->instance->id);

            // Suppression des choix de badges proposés
            $DB->delete_records('coursebadges_available_bdg', ['coursebadgeid' => $this->id]);

            // Suppression de l'activité
            $fs = get_file_storage();
            if (!$cm = get_coursemodule_from_instance('coursebadges', $this->id)) {
                print_error('Course Module ID was incorrect'); // NOTE this is invalid use of print_error, must be a lang string id
            }
            $fs->delete_area_files($cm->id);

            $DB->delete_records('coursebadges', ['id' => $this->id]);
            return true;
        } catch (moodle_exception $e){
            debugging('Error retrieving ' . $this->data->name . ' course badges cannot be deleted: ' .
                $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Fonction qui récupère tous les badges préselectionnés de l'activité
     * @return array
     * @throws dml_exception
     */
    public function get_all_pre_select_badges_instance(){
        global $DB;

        if($this->id != null){
            $sql = "SELECT b.* 
                FROM {badge} b
                INNER JOIN {coursebadges_available_bdg} cbsd ON (b.id = cbsd.badgeid)
                WHERE cbsd.coursebadgeid = ?
                ORDER BY b.name ASC";

            return $DB->get_records_sql($sql, [$this->id]);
        }
        return [];
    }

    /**
     * Fonction qui récupère tous les badges proposés d'une activité. Dans le cas où l'utilisateur possède des
     * choix de badges, ces mêmes badges sont soustrait à cette liste.
     * @return array
     * @throws dml_exception
     */
    public function get_all_pre_select_badges_instance_by_user(){
        global $DB;

        if($this->id != null){
            $sql = "SELECT b.* 
                FROM {badge} b
                INNER JOIN {coursebadges_available_bdg} cbsd ON (b.id = cbsd.badgeid)
                WHERE cbsd.coursebadgeid = ? AND b.id NOT IN (SELECT b.id 
                    FROM {badge} b
                    INNER JOIN mdl_coursebadges_available_bdg cbsd ON (cbsd.badgeid = b.id) 
                    INNER JOIN mdl_coursebadges_usr_select_bdg cbuc ON (cbuc.selectionbadgeid = cbsd.id)
                    WHERE cbsd.coursebadgeid = ? AND cbuc.userid = ?)
                ORDER BY b.name ASC";

            return $DB->get_records_sql($sql, [$this->id, $this->id, $this->userid]);
        }
        return [];
    }

    /**
     * Fonction qui récupère tous les badges choisis d'un utilisateur dans l'activité
     * @return array
     * @throws dml_exception
     */
    public function get_all_selected_badges_instance_by_user(){
        global $DB;

        if($this->id == null) {
            return [];
        }

        $sql = "SELECT b.* 
                    FROM {badge} b
                    INNER JOIN mdl_coursebadges_available_bdg cbsd ON (cbsd.badgeid = b.id) 
                    INNER JOIN mdl_coursebadges_usr_select_bdg cbuc ON (cbuc.selectionbadgeid = cbsd.id)
                    WHERE cbsd.coursebadgeid = ? AND cbuc.userid = ?
                    ORDER BY b.name ASC";
        return $DB->get_records_sql($sql, [$this->id, $this->userid]);
    }

    /**
     * Fonction qui récupère tous les choix de badges d'un utilisateur dans l'activité
     * @return array
     * @throws dml_exception
     */
    public function get_all_user_choices_by_course_badges_instance(){
        global $DB;

        if($this->id != null){
            $sql = "SELECT cbuc.* 
                    FROM {coursebadges_usr_select_bdg} cbuc
                    INNER JOIN {coursebadges_available_bdg} cbsd ON (cbuc.selectionbadgeid = cbsd.id)
                    INNER JOIN {coursebadges} cb ON (cbsd.coursebadgeid = cb.id)
                    WHERE cb.id = ?
                    ";
            return $DB->get_records_sql($sql, [$this->id]);
        }
        return [];
    }

    /**
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function create_selected_badges_instances(){
        global $DB, $CFG;
        require_once($CFG->libdir.'/completionlib.php');

        if(!has_capability('mod/coursebadges:choose', $this->context_module)){
            \core\notification::add(get_string('notification_error_add_choice', 'coursebadges'), \core\notification::ERROR);
            return false;
        }
        if(($this->instance->allowmodificationschoice == false && !$this->get_all_selected_badges_instance_by_user())
            || $this->instance->allowmodificationschoice == true){

            if(!has_capability('mod/coursebadges:deletechoice', $this->context_module)){
                \core\notification::add(get_string('notification_error_delete_choice', 'coursebadges'), \core\notification::ERROR);
                return false;
            }
            $sql = 'DELETE
                    FROM mdl_coursebadges_usr_select_bdg
                    WHERE id IN(
                        SELECT * 
                        FROM (
                            SELECT cbuc1.id
                            FROM mdl_coursebadges_usr_select_bdg cbuc1
                            INNER JOIN mdl_coursebadges_available_bdg cbsd ON (cbuc1.selectionbadgeid = cbsd.id)
                            INNER JOIN mdl_coursebadges cb ON (cbsd.coursebadgeid = cb.id)
                            WHERE cb.id = ? AND cbuc1.userid = ?
                            ) a 
                    )';

            $DB->execute($sql, [$this->id, $this->userid]);

            $selected_ids_data = [];
            if(!empty(explode(",", $this->data->rightlistids)[0])){
                $sql = 'SELECT id FROM {coursebadges_available_bdg} WHERE badgeid IN ('.$this->data->rightlistids.') AND coursebadgeid = "'.$this->id.'"';
                $selected_ids_data = $DB->get_records_sql($sql);
            }

            $list_selected = [];
            foreach ($selected_ids_data as $selection_badge) {
                $user_choice = new stdClass();
                $user_choice->selectionbadgeid = $selection_badge->id;
                $user_choice->userid = $this->userid;
                $user_choice->timemodified = time();
                $list_selected[] = $user_choice;
            }
            if(count($list_selected) > 0){
                $DB->insert_records('coursebadges_usr_select_bdg', $list_selected);

                if (!$cm = get_coursemodule_from_instance('coursebadges', $this->id)) {
                    print_error('Course Module ID was incorrect'); // NOTE this is invalid use of print_error, must be a lang string id
                }
                // Update completion state
                $completion = new completion_info(get_course($this->instance->course));
                if ($completion->is_enabled($cm) && $this->instance->completionvalidatedbadges) {
                    $completion->update_state($cm, COMPLETION_COMPLETE);
                }
            }

            return true;
        }
        return false;
    }

    /**
     * Fonction qui récupère tous les badges obtenus d'un utilisateur dans un parcours
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_obtained_badges(){
        global $COURSE;

        return badges_get_user_badges($this->userid, $COURSE->id);
    }

    /**
     * Fonction qui récupère tous les badges disponibles d'un parcours. Si le paramétrage de l'activité a déjà
     * proposés des badges, cette fonction peut dans ce cas retourner seulement les badges non proposés.
     * @param $courseid
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_available_badges_in_course($courseid){
        global $DB;

        if (!$course = $DB->get_record('course', ['id'=> $courseid])) {
            print_error('course is misconfigured');  // NOTE As above
        }

        if($this->id){
            // On retourne la liste des badges disponibles non préselectionnées
            $sql = "SELECT b.id, b.name, b.status 
                    FROM {badge} b 
                    WHERE b.status != :deleted 
                    AND b.type = :type 
                    AND b.courseid = :courseid
                    AND b.id NOT IN (SELECT badgeid FROM {coursebadges_available_bdg} WHERE coursebadgeid = :coursebadgeid) 
                    ORDER BY name ASC";
            return $DB->get_records_sql($sql, ["deleted" => BADGE_STATUS_ARCHIVED, "type" => BADGE_TYPE_COURSE, "courseid" => $courseid, "coursebadgeid" => $this->id]);

        } else {
            // on retourne l'ensemble des badges disponibles
            return badges_get_badges(BADGE_TYPE_COURSE, $course->id, 'name', 'ASC');
        }
    }

    /**
     * Fonction qui supprime tous les choix de badges d'une activité.
     * @param $course_badge
     * @return bool
     * @throws dml_exception
     */
    public static function delete_all_user_choices_by_course_badges_instance($course_badge_id){
        global $DB;
        // Suppression de tous les choix utilisateurs
        if($course_badge_id != null){
            $sql = "DELETE cbuc.* FROM {coursebadges_usr_select_bdg} cbuc
                    INNER JOIN {coursebadges_available_bdg} cbsd ON (cbuc.selectionbadgeid = cbsd.id)
                    INNER JOIN {coursebadges} cb ON (cbsd.coursebadgeid = cb.id)
                    WHERE cb.id = ?
                    ";
            $DB->execute($sql, [$course_badge_id]);
            return true;
        }
        return false;
    }

    /**
     * Fonction qui retourne l'url de l'image d'un badge pour l'afficher dans le formulaire.
     * @param $badgeid
     * @return moodle_url
     */
    public static function get_imageurl_for_badge($badgeid){
        global $COURSE;

        $cm = context_course::instance($COURSE->id);

        $imageurl = moodle_url::make_pluginfile_url($cm->id, 'badges', 'badgeimage', $badgeid, '/', 'f1', false);
        // Appending a random parameter to image link to forse browser reload the image.
        $imageurl->param('refresh', rand(1, 10000));

        return $imageurl;
    }

    /**
     * Fonction qui détermine si un bloc course badges a bien été instancé une fois dans le parcours.
     * @return bool
     * @throws dml_exception
     */
    public static function has_block_course_badges_instance(){
        global $COURSE, $DB;

        $cm = context_course::instance($COURSE->id);
        $sql = "SELECT c.* 
                FROM {context} c
                LEFT JOIN {block_instances} bi ON (c.instanceid = bi.id)
                WHERE bi.blockname = 'course_badges'
                AND c.contextlevel = '".CONTEXT_BLOCK."'
                AND c.path LIKE '%/".$cm->id."/%'";

        $cb_blocks = $DB->get_records_sql($sql);

        if(count($cb_blocks) > 0){
            return true;
        }
        return false;
    }

    /**
     * Fonction qui vérifie si un utilisateur a bien choisi des badges dans l'activité.
     * @param $coursebadgeid
     * @return bool
     * @throws dml_exception
     */
    public static function has_selected_badges($coursebadgeid){
        global $DB, $USER;

        if($coursebadgeid == null) {
            return false;
        }

        $sql = "SELECT cbuc.* 
                    FROM mdl_coursebadges_usr_select_bdg cbuc
                    INNER JOIN mdl_coursebadges_available_bdg cbsd ON (cbuc.selectionbadgeid = cbsd.id) 
                    WHERE cbsd.coursebadgeid = ? AND cbuc.userid = ?";
        $data = $DB->get_records_sql($sql, [$coursebadgeid, $USER->id]);
        if(count($data) > 0){
            return true;
        }
        return false;
    }



    /**
     * Fonction qui génère un string contenant les identifiants de badges séparé par une virgule
     * @param $list
     * @return string
     */
    public static function generateListIds($list){
        $i = 0;
        $len = count($list);
        $str = "";
        foreach($list as $element){
            if ($i == $len - 1) {
                $str .= $element->id;
            } else {
                $str .= $element->id . ",";
            }
            $i++;
        }
        return $str;
    }

    /**
     * Fonction permettant de créer une nouvelle instance de bloc course badges
     * @return bool|int
     * @throws dml_exception
     */
    private function create_course_badges_block_instance(){
        global $DB;
        $blockinstance = new stdClass;
        $blockinstance->blockname = 'course_badges';
        $blockinstance->parentcontextid = $this->context->id;
        $blockinstance->showinsubcontexts = 0;
        $blockinstance->pagetypepattern = 'course-view-*';
        $blockinstance->subpagepattern = null;
        $blockinstance->defaultregion = 'side-pre';
        $blockinstance->defaultweight = 12;
        $blockinstance->configdata = '';
        $blockinstance->timecreated = time();
        $blockinstance->timemodified = $blockinstance->timecreated;
        $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);

        // Ensure the block context is created.
        context_block::instance($blockinstance->id);

        // If the new instance was created, allow it to do additional setup
        if ($block = block_instance('course_badges', $blockinstance)) {
            $block->instance_create();
        }
        return $blockinstance->id;
    }
}
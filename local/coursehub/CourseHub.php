<?php

class CourseHub
{
    const PLUGIN_NAME = 'coursehub';
    const PLUGIN_FULLNAME = 'local_'.self::PLUGIN_NAME;

    // course published in the "offre de formation"
    const PUBLISH_PUBLISHED = 0;

    // course published in the "offre de parcours"
    const PUBLISH_SHARED = 1;
    
    const TABLE_SLAVE = 'local_coursehub_slave';
    const TABLE_COURSE = 'local_coursehub_course';
    const TABLE_PUBLISHED = 'local_coursehub_published';
    const TABLE_TASKS = 'local_coursehub_tasks';
    const TABLE_INDEX = 'local_coursehub_index';
    const TABLE_INDEX_KEYWORDS = 'local_coursehub_index_key';
    const TABLE_INDEX_PUBLIC = 'local_coursehub_index_pub';
    const TABLE_INDEX_NOTES = 'local_coursehub_index_notes';
    
    const CONF_MOD = 'mod';
    const CONF_MOD_MASTER = 'master';
    const CONF_MOD_SLAVE = 'slave';
    const CONF_MOD_NONE = false;
    
    const SLAVE_TYPE = 'type';
    const SLAVE_TYPE_LOCAL = 'local';
    const SLAVE_TYPE_REMOTE = 'remote';
    
    const TASK_STATUS_TODO = 0;
    const TASK_STATUS_COMPLETED = 1;
    const TASK_STATUS_INPROGRESS = 2;
    const TASK_STATUS_FAILED = 3;
    
    const SLAVE_TYPES = array(
        self::SLAVE_TYPE_LOCAL,
        self::SLAVE_TYPE_REMOTE
    );
    
    const PERMISSION_ALLOWED = 1;
    const PERMISSION_DENIED = 0;
    
    const PUBLISHED_FILES_FOLDER = '/coursehub';
    
    const LOGSMOD_ECHO = 0;
    const LOGSMOD_FILE = 1;
    
    const ERROR_SLAVE_NOT_REGISTERED = -2;
    const ERROR_SLAVE_ALREADY_LINKED = -3;
    const ERROR_SLAVE_ALREADY_EXIST = -4;
    const ERROR_SLAVE_TOKEN_MISMATCH = -5;
    
    
    private static $logsmod = self::LOGSMOD_FILE;
    private static $logsfile = null;
    
    private function __construct() {}
    
    static function instance($logsmod=self::LOGSMOD_FILE) {
        self::$logsmod = $logsmod;
        if ($logsmod == self::LOGSMOD_FILE){
            if (self::$logsfile == null) {
                self::$logsfile = self::getLogFile();
            }
        }
        if (self::getMod() == self::CONF_MOD_MASTER) {
            return new CourseHubMaster();
        }else if (self::getMod() == self::CONF_MOD_SLAVE) {
            if (self::getType() == self::SLAVE_TYPE_REMOTE){
                return new CourseHubSlaveRemote();
            }else{
                return new CourseHubSlaveLocal();
            }
        }else{
            return new CourseHubSlaveNoConfig();
        }
    }
    
    static function getMod() {
        return get_config(self::PLUGIN_FULLNAME,self::CONF_MOD);
    }
    
    static function getType() {
        return get_config(self::PLUGIN_FULLNAME,self::SLAVE_TYPE);
    }
    
    static function log($msg,$source='nosource'){

        if (self::$logsmod == self::LOGSMOD_ECHO){
            echo date('Y-m-d_H:i:s__').$source.'__'.$msg."\n";
        }else{
            file_put_contents(self::$logsfile,date('Y-m-d_H:i:s__').$source.'__'.$msg."\n",FILE_APPEND);
        }
    }
    
    private static function getLogFile() {
        global $CFG,$USER;
        $logdir = $CFG->dataroot.'/logs/coursehub/';
        if (!file_exists($logdir)) {mkdir($logdir,0770,true);}
        return $logdir.'coursehub_'.time('Y-m-d_H-i-s').'_'.$USER->id.'.log';
    }
}

class CourseHubSlave // master&slave
{
    protected $data = array();
    protected $slaveid = null;
    
    private function __construct() {}
    
    
    protected function getSharedFilesFolder() {
        throw new Exception('getSharedFilesFolder() have to be defined!');
    }
    
    public function getShareFolderpath($publishid) {
        $folder = intdiv($publishid,100)*100;
        return $this->getSharedFilesFolder().'/'.$folder;
    }
    
    public function getShareFilename($publishid) {
        return str_pad($publishid, 6, '0', STR_PAD_LEFT).'.mbz';
    }
    
    
    function getIdentifiant() {
        return $this->data->identifiant;
    }
    
    function getType() {
        return $this->data->type;
    }
    
    function getName() {
        return $this->data->name;
    }
    
    function getShortname() {
        return $this->data->name;
    }
    
    function getURL() {
        return $this->data->url;
    }
    
    function getToken() {
        return $this->data->token;
    }
    
    function canPublish() {
        return ($this->data->canpublish==1);
    }
    
    function canShare() {
        return ($this->data->canshare==1);
    }
    
    function canDelete() {
        return ($this->data->candelete==1);
    }
    
    function isTrusted() {
        return ($this->data->trusted==1);
    }
    
    function isDeleted() {
        return ($this->data->deleted==1);
    }
    
    function isMaster() {
        return ($this instanceof CourseHubMaster);
    }
    
    function isSlave() {
        return ($this instanceof CourseHubSlaveLocal || $this instanceof CourseHubSlaveRemote);
    }
    
    function isLocalSlave() {
        return ($this instanceof CourseHubSlaveLocal || $this instanceof CourseHubConfSlaveLocal);
    }
    
    function isRemoteSlave() {
        return ($this instanceof CourseHubSlaveRemote || $this instanceof CourseHubConfSlaveRemote);
    }
    
    function isNoConfig() {
        return ($this instanceof CourseHubSlaveNoConfig);
    }
    
    
    function getMasterIdentifiant() {
        return $GLOBALS['CFG']->academie_name;
    }
    
    
    protected function post($url, array $post = NULL, array $options = array(), $filepath = null)
    {
        global $CFG;
        
        if ($filepath !== null){
            $post['backupfile'] = new CURLFile($filepath);
        }
        
        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POSTFIELDS => $post
        );
        
        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        
        
        if (!empty($CFG->proxyhost) && !is_proxybypass($CFG->proxyhost)) {
            if ($CFG->proxyport === '0') {
                curl_setopt($ch, CURLOPT_PROXY, $CFG->proxyhost);
            } else {
                curl_setopt($ch, CURLOPT_PROXY, $CFG->proxyhost.':'.$CFG->proxyport);
            }
        }
        
        if( ! $result = curl_exec($ch))
        {
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

}

class CourseHubConfSlave extends CourseHubSlave // master
{
    private function __construct() {}
    
    function getId() {
        return $this->data->id;
    }
}

class CourseHubMaster extends CourseHubConfSlave // master
{
    private $slaves = array();
    private $slaves_identifiant = array();
    
    function __construct() {
        $this->loadSlaves();
    }
    
    function loadSlaves() {
        global $DB;
        $this->slaves_identifiant = $this->slaves = array();
        $slaves = $DB->get_records(CourseHub::TABLE_SLAVE,null,'','id,type');
        foreach ($slaves AS $slave) {
            if ($slave->type == CourseHub::SLAVE_TYPE_LOCAL) {
                $this->slaves[$slave->id] = new CourseHubConfSlaveLocal($slave->id);
                $this->slaves_identifiant[] = $this->slaves[$slave->id]->getIdentifiant();
            }
            if ($slave->type == CourseHub::SLAVE_TYPE_REMOTE) {
                $this->slaves[$slave->id] = new CourseHubConfSlaveRemote($slave->id);
                $this->slaves_identifiant[] = $this->slaves[$slave->id]->getIdentifiant();
            }
        }
    }
    
    function addSlave($type,$slaveIdentifiant,$slaveToken=null,$slaveName=null,$slaveURL=null) {
        if ($type == CourseHub::SLAVE_TYPE_LOCAL) {
            $res = CourseHubConfSlaveLocal::addinstance($slaveIdentifiant);
            $this->loadSlaves();
            return $res;
        }else if ($type == CourseHub::SLAVE_TYPE_REMOTE) {
            $res = CourseHubConfSlaveRemote::addinstance($slaveIdentifiant,$slaveToken,$slaveName,$slaveURL);
            $this->loadSlaves();
            return $res;
        }
        return false;
    }
    
    function declareSlave($type,$slaveIdentifiant) {
        if ($type == CourseHub::SLAVE_TYPE_REMOTE) {
            $res = CourseHubConfSlaveRemote::declareinstance($slaveIdentifiant);
            $this->loadSlaves();
            return $res;
        }
        return false;
    }
    
    function deleteSlave($slaveId) {
        $slave = $GLOBALS['DB']->get_record(CourseHub::TABLE_SLAVE,array('id'=>$slaveId));
        if ($slave === false){return false;}
        if ($slave->type == CourseHub::SLAVE_TYPE_LOCAL) {
            $res = CourseHubConfSlaveLocal::deleteinstance($slave->identifiant);
            $this->loadSlaves();
            return $res;
        }else if ($slave->type == CourseHub::SLAVE_TYPE_REMOTE) {
            $res = CourseHubConfSlaveRemote::deleteinstance($slave->identifiant);
            $this->loadSlaves();
            return $res;
        }
    }
    
    protected function getSharedFilesFolder() {
        return $GLOBALS['CFG']->dataroot.CourseHub::PUBLISHED_FILES_FOLDER;
    }
    
    
    function getIdentifiant() {
        return $GLOBALS['CFG']->academie_name;
    }
    
    function getSlaves() {
        $this->loadSlaves();
        return $this->slaves;
    }
    
    function getActiveSlaves() {
        $this->loadSlaves();
        $slaves = array();
        foreach($this->slaves AS $key=>$slave) {
            if ($slave->data->deleted == 0 && $slave->data->trusted == 1){
                $slaves[$key] = $slave;
            }
        }
        return $slaves;
    }
    
    function getSlave($slaveIdentifiant) {
        $this->loadSlaves();
        foreach($this->slaves AS $slave)
        {
            if ($slave->getIdentifiant() == $slaveIdentifiant) {
                return $slave;
            }
        }
        return false;
    }
    
    function getSlaveById($slaveId) {
        $this->loadSlaves();
        return $this->slaves[$slaveId];
    }
    
    function getAvailableLocalSlaveIdentifiant($pluginMustBeInstalled=true) {
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        $identifiants = array();
        $academieconfig = get_magistere_academy_config();
        foreach ( $academieconfig AS $acaname=>$acadata )
        {
            if (!in_array($acaname, $this->slaves_identifiant))
            {
                $CourseHubVersion = databaseConnection::instance()->get($acaname)->record_exists('config_plugins',array('plugin'=>CourseHub::PLUGIN_FULLNAME,'name'=>'version'));
                
                if ( !$pluginMustBeInstalled || ($pluginMustBeInstalled && $CourseHubVersion !== false)) {
                    
                    $CourseHubMod = databaseConnection::instance()->get($acaname)->record_exists('config_plugins',array('plugin'=>CourseHub::PLUGIN_FULLNAME,'name'=>'mod'));
                    
                    if (!$CourseHubMod || $acaname == $GLOBALS['CFG']->academie_name) {
                        $aca = new stdClass();
                        $aca->name = $acaname;
                        $aca->CourseHubInstalled = ($CourseHubVersion !== false);
                        $identifiants[$acaname] = $aca;
                    }
                }
            }
        }
        ksort($identifiants);
        return $identifiants;
    }
    
    function getPublishedCourse($slaveIdentifiant,$courseid,$publishmod) {
        global $DB;
        
        $slave = $DB->get_record(CourseHub::TABLE_SLAVE,array('identifiant'=>$slaveIdentifiant));
        
        if ($slave === false){
            return false;
        }
        
        $hubcourse = $DB->get_record(CourseHub::TABLE_COURSE,array('deleted'=>0,'slaveid'=>$slave->id,'courseid'=>$courseid,'publish'=>$publishmod));
        
        return $hubcourse;
    }
    
    function getPublishedCourseById($hubcourseid) {
        return $GLOBALS['DB']->get_record(CourseHub::TABLE_COURSE,array('id'=>$hubcourseid,'deleted'=>0));
    }
    
    function getPublishedCourseIndexation($slaveIdentifiant,$courseid,$publishmod) {
        global $DB;
        
        $slave = $DB->get_record(CourseHub::TABLE_SLAVE,array('identifiant'=>$slaveIdentifiant));
        
        if ($slave === false){
            return false;
        }
        
        $hubcourse = $DB->get_record(CourseHub::TABLE_COURSE,array('deleted'=>0,'slaveid'=>$slave->id,'courseid'=>$courseid,'publish'=>$publishmod));
        
        if ($hubcourse === false){
            return false;
        }
        
        $hubcourseindex = $DB->get_record(CourseHub::TABLE_INDEX,array('publishid'=>$hubcourse->id));
        
        return $hubcourseindex;
    }
    
    function searchPublishedCourse($search,$publishmod) {
        
        $search = str_replace('"', '', $search);
        
        $search_where = '';
        if (strlen(trim($search)) > 2)
        {
            $search_words = explode(' ', trim($search));
            
            $words_compare = array();
            foreach ($search_words AS $search_word)
            {
                if (strlen($search_word) > 2)
                {
                    $words_compare[] = "CONCAT(IFNULL(name,''),' ',IFNULL(shortname,''),' ',IFNULL(summary,'')) LIKE '%".$search_word."%'";
                }
            }
            
            if (count($words_compare) > 0)
            {
                $search_where = " AND ".implode(' AND ', $words_compare);
            }
        }
        
        $sql = "SELECT
id, courseid, name AS fullname, shortname, summary, courseurl, coursestartdate, courseenddate, inscription_method, enrolstartdate, enrolenddate, isasession, enrolrole, maxparticipant, hasakey, timeexpire
FROM {".CourseHub::TABLE_COURSE."} WHERE deleted=0 AND publish=?".$search_where;
        
        return $GLOBALS['DB']->get_records_sql($sql,array($publishmod));
    }
    
    function backupCourse($courseid, $destinationfolder, $destinationfilename) {
        global $CFG;
        $ls = 'CourseHubMaster::backupCourse("'.$courseid.'","'.$destinationfolder.','.$destinationfilename.'")';
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        
        $course = $GLOBALS['DB']->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $admin = get_admin();
        
        // The array of initial backup settings.
        $backupsettings = array (
            'users' => 0,               // Include enrolled users (default = 1)
            'anonymize' => 0,           // Anonymize user information (default = 0)
            'role_assignments' => 0,    // Include user role assignments (default = 1)
            'activities' => 1,          // Include activities (default = 1)
            'blocks' => 1,              // Include blocks (default = 1)
            'filters' => 1,             // Include filters (default = 1)
            'comments' => 0,            // Include comments (default = 1)
            'userscompletion' => 0,     // Include user completion details (default = 1)
            'logs' => 0,                // Include course logs (default = 0)
            'grade_histories' => 0      // Include grade history (default = 0)
        );
        
        $bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_YES, backup::MODE_GENERAL, $admin->id);
        
        // Apply the settings to all tasks
        foreach ($bc->get_plan()->get_tasks() as $taskindex => $task) {
            $settings = $task->get_settings();
            foreach ($settings as $setting) {
                $setting->set_status(backup_setting::NOT_LOCKED);
                
                // Modify the values of the intial backup settings
                if ($taskindex == 0) {
                    foreach ($backupsettings as $key => $value) {
                        if ($setting->get_name() == $key) {
                            $setting->set_value($value);
                        }
                    }
                }
            }
        }
        
        // Set the default filename.
        $format = $bc->get_format();
        $type = $bc->get_type();
        $id = $bc->get_id();
        $users = $bc->get_plan()->get_setting('users')->get_value();
        $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
        $filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
        $bc->get_plan()->get_setting('filename')->set_value($filename);
        
        // Execution.
        $bc->finish_ui();
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination']; // May be empty if file already moved to target location.
        
        if ($file) {
            if ($file->copy_content_to($destinationfolder.'/'.$destinationfilename)) {
                $file->delete();
            } else {
                CourseHub::log('Failed to move the backup to final destination',$ls);
                return false;
            }
        }
        $bc->destroy();
        return true;
    }
    
    function copyIndexationToMaster($publishid,$courseid) {
        global $DB;
        
        $ls = 'CourseHubMaster::copyIndexationToMaster("'.$publishid.'","'.$courseid.'")';
        
        $publish = $DB->get_record(CourseHub::TABLE_COURSE,array('id'=>$publishid));
        
        if ($publish === false) {
            CourseHub::log('Publication not found',$ls);
            return false;
        }
        
        $indexation = $DB->get_record('local_indexation', array('courseid'=>$courseid));
        
        if ($indexation === false) {
            return false;
        }
        
        
        $indexation_keywords = $DB->get_records('local_indexation_keywords', array('indexationid'=>$indexation->id));
        $indexation_publics = $DB->get_records('local_indexation_public', array('indexationid'=>$indexation->id));
        $indexation_notes = $DB->get_records('local_indexation_notes', array('indexationid'=>$indexation->id));
        
        
        unset($indexation->id);
        $indexation->publishid = $publishid;
        
        $master_indexation = $DB->get_record(CourseHub::TABLE_INDEX, array('publishid'=>$publishid));
        
        $master_indexationid = 0;
        
        if ($master_indexation !== false) {
            CourseHub::log('Indexation found, updating',$ls);
            $master_indexationid = $master_indexation->id;
            
            $DB->delete_records(CourseHub::TABLE_INDEX_KEYWORDS, array('indexationid'=>$master_indexation->id));
            $DB->delete_records(CourseHub::TABLE_INDEX_PUBLIC, array('indexationid'=>$master_indexation->id));
            $DB->delete_records(CourseHub::TABLE_INDEX_NOTES, array('indexationid'=>$master_indexation->id));
            
            $indexation->id = $master_indexation->id;
            
            $DB->update_record(CourseHub::TABLE_INDEX, $indexation);
            
        }else{
            CourseHub::log('Indexation not found, insert new one',$ls);
            $master_indexationid = $DB->insert_record(CourseHub::TABLE_INDEX, $indexation);
            CourseHub::log('Indexation inserted : id='.$master_indexationid,$ls);
        }
        
        
        
        if (count($indexation_keywords)>0) {
            CourseHub::log('Indexation keywords found: '.count($indexation_keywords),$ls);
            foreach($indexation_keywords AS $indexation_keyword) {
                unset($indexation_keyword->id);
                $indexation_keyword->indexationid = $master_indexationid;
                CourseHub::log('Inserting keyword :'.print_r($indexation_keyword,true),$ls);
                $DB->insert_record(CourseHub::TABLE_INDEX_KEYWORDS, $indexation_keyword);
            }
        }
        if (count($indexation_publics)>0) {
            CourseHub::log('Indexation publics found: '.count($indexation_publics),$ls);
            foreach($indexation_publics AS $indexation_public) {
                unset($indexation_public->id);
                $indexation_public->indexationid = $master_indexationid;
                CourseHub::log('Inserting public :'.print_r($indexation_public,true),$ls);
                $DB->insert_record(CourseHub::TABLE_INDEX_PUBLIC, $indexation_public);
            }
        }
        if (count($indexation_notes)>0) {
            CourseHub::log('Indexation notes found: '.count($indexation_notes),$ls);
            foreach($indexation_notes AS $indexation_note) {
                unset($indexation_note->id);
                $indexation_note->indexationid = $master_indexationid;
                CourseHub::log('Inserting note :'.print_r($indexation_note,true),$ls);
                $DB->insert_record(CourseHub::TABLE_INDEX_NOTES, $indexation_note);
            }
        }
        
        return true;
    }
    
    function removeMasterIndexation($publishid,$courseid) {
        global $DB;
        
        $master_indexation = $DB->get_record(CourseHub::TABLE_INDEX, array('publishid'=>$publishid));
        
        if ($master_indexation === false) {
            return true;
        }
        
        $DB->delete_records(CourseHub::TABLE_INDEX_KEYWORDS, array('indexationid'=>$master_indexation->id));
        $DB->delete_records(CourseHub::TABLE_INDEX_PUBLIC, array('indexationid'=>$master_indexation->id));
        $DB->delete_records(CourseHub::TABLE_INDEX_NOTES, array('indexationid'=>$master_indexation->id));
        $DB->delete_records(CourseHub::TABLE_INDEX, array('publishid'=>$publishid));
    }
    
    function copyIndexationFromMaster($publishid,$courseid) {
        global $DB;
        
        $ls = 'CourseHubMaster::copyIndexationFromMaster("'.$publishid.'","'.$courseid.'")';
        
        $publish = $DB->get_record(CourseHub::TABLE_COURSE,array('id'=>$publishid));
        
        if ($publish === false) {
            CourseHub::log('Publication not found',$ls);
            return false;
        }
        
        $indexation = $DB->get_record(CourseHub::TABLE_INDEX, array('publishid'=>$publishid));
        
        if ($indexation === false) {
            return false;
        }
        
        
        $indexation_keywords = $DB->get_records(CourseHub::TABLE_INDEX_KEYWORDS, array('indexationid'=>$indexation->id));
        $indexation_publics = $DB->get_records(CourseHub::TABLE_INDEX_PUBLIC, array('indexationid'=>$indexation->id));
        $indexation_notes = $DB->get_records(CourseHub::TABLE_INDEX_NOTES, array('indexationid'=>$indexation->id));
        
        
        unset($indexation->id);
        $indexation->courseid = $courseid;
        
        $slave_indexation = $DB->get_record('local_indexation', array('courseid'=>$courseid));
        
        $slave_indexationid = 0;
        
        if ($slave_indexation !== false) {
            CourseHub::log('Indexation found, updating',$ls);
            $slave_indexationid = $slave_indexation->id;
            
            $DB->delete_records('local_indexation_keywords', array('indexationid'=>$slave_indexation->id));
            $DB->delete_records('local_indexation_public', array('indexationid'=>$slave_indexation->id));
            $DB->delete_records('local_indexation_notes', array('indexationid'=>$slave_indexation->id));
            
            $indexation->id = $slave_indexation->id;
            
            $DB->update_record('local_indexation', $indexation);
            
        }else{
            CourseHub::log('Indexation not found, insert new one',$ls);
            $slave_indexationid = $DB->insert_record('local_indexation', $indexation);
            CourseHub::log('Indexation inserted : id='.$slave_indexationid,$ls);
        }
        
        
        
        if (count($indexation_keywords)>0) {
            CourseHub::log('Indexation keywords found: '.count($indexation_keywords),$ls);
            foreach($indexation_keywords AS $indexation_keyword) {
                unset($indexation_keyword->id);
                $indexation_keyword->indexationid = $slave_indexationid;
                CourseHub::log('Inserting keyword :'.print_r($indexation_keyword,true),$ls);
                $DB->insert_record('local_indexation_keywords', $indexation_keyword);
            }
        }
        if (count($indexation_publics)>0) {
            CourseHub::log('Indexation publics found: '.count($indexation_publics),$ls);
            foreach($indexation_publics AS $indexation_public) {
                unset($indexation_public->id);
                $indexation_public->indexationid = $slave_indexationid;
                CourseHub::log('Inserting public :'.print_r($indexation_public,true),$ls);
                $DB->insert_record('local_indexation_public', $indexation_public);
            }
        }
        if (count($indexation_notes)>0) {
            CourseHub::log('Indexation notes found: '.count($indexation_notes),$ls);
            foreach($indexation_notes AS $indexation_note) {
                unset($indexation_note->id);
                $indexation_note->indexationid = $slave_indexationid;
                CourseHub::log('Inserting note :'.print_r($indexation_note,true),$ls);
                $DB->insert_record('local_indexation_notes', $indexation_note);
            }
        }
        
        return true;
    }
    
    function shareCourse($courseid,$userid = null) {
        global $USER, $DB;
        $ls = 'CourseHubMaster::shareCourse('.$courseid.')';
        
        if(!$this->canShare()) {
            CourseHub::log('This slave is not allowed to share a course!',$ls);
            return false;
        }
        
        if (!has_capability('local/coursehub:share', context_system::instance())) {
            CourseHub::log('The user is not allowed to share a course (local/coursehub:share)',$ls);
            return false;
        }
        
        $diffuser = $USER;
        if ($userid !== null) {
            $diffuser = $DB->get_record('user', array('id'=>$userid));
        }
        
        $course = $DB->get_record('course',array('id'=>$courseid));
        
        $slave = $DB->get_record(CourseHub::TABLE_SLAVE,array('identifiant'=>$this->getIdentifiant()));
        
        if ($slave === false){
            return false;
        }
        
        // Create coursehub record with deleted flag or update existing one
        $newCourseHub = new stdClass();
        $newCourseHub->slaveid = $slave->id;
        $newCourseHub->courseid = $courseid;
        $newCourseHub->deleted = 1;
        $newCourseHub->publish = CourseHub::PUBLISH_SHARED;
        $newCourseHub->name = $course->fullname;
        $newCourseHub->shortname = $course->shortname;
        $newCourseHub->summary = $course->summary;
        $newCourseHub->courseurl = (new moodle_url('/course/view.php',array('id'=>$courseid)))->out();
        $newCourseHub->coursestartdate = $course->startdate;
        $newCourseHub->courseenddate = $course->enddate;
        $newCourseHub->username = $diffuser->username;
        $newCourseHub->firstname = $diffuser->firstname;
        $newCourseHub->lastname = $diffuser->lastname;
        $newCourseHub->email = $diffuser->email;
        $newCourseHub->timecoursemodified = $course->timemodified;
        $newCourseHub->timecreated = time();
        $newCourseHub->timemodified = time();
        
        $roleparticipant = $DB->get_record('role', array('shortname'=>'participant'));
        $enrol = $DB->get_records('enrol', array('courseid' => $course->id, 'enrol' => 'self', 'status' => 0,'customint6' => 1,'roleid'=>$roleparticipant->id),'id DESC');
        
        if (count($enrol) > 0) {
            $enrol = array_shift($enrol);
            
            $newCourseHub->inscription_method = $enrol->enrol;
            $newCourseHub->enrolstartdate = $enrol->enrolstartdate;
            $newCourseHub->enrolenddate = $enrol->enrolenddate;
            $newCourseHub->enrolrole = 'participant';
            $newCourseHub->maxparticipant = $enrol->customint3;
            
            $categorie = $DB->get_record('course_categories',array('id'=>$course->category));
            $sessionCategorie = $DB->get_record_sql('SELECT * FROM {course_categories} WHERE name LIKE "session en auto-inscription" AND depth = 2');
            $isasession = 0;
            if(strpos($categorie->path, $sessionCategorie->path) !== false){
                $isasession = 1;
            }
            
            $newCourseHub->isasession = $isasession;
        }
        
        $publishid = 0;
        
        $oldPublish = $DB->get_record(CourseHub::TABLE_COURSE,array('slaveid'=>$newCourseHub->slaveid,'courseid'=>$courseid,'publish'=>$newCourseHub->publish));
        if ( $oldPublish !== false ) {
            $publishid = $oldPublish->id;
            $newCourseHub->id = $oldPublish->id;
            if ($oldPublish->deleted == 1) { // found deleted publication, we overwrite it
                $DB->update_record(CourseHub::TABLE_COURSE,$newCourseHub);
            }else{
                $newCourseHub->timecreated = $oldPublish->timecreated;
                $DB->update_record(CourseHub::TABLE_COURSE,$newCourseHub);
            }
        }else{
            $publishid = $DB->insert_record(CourseHub::TABLE_COURSE,$newCourseHub);
        }
        
        // get hub storage folder
        $publishFolder = $this->getShareFolderpath($publishid);
        $publishFilename = $this->getShareFilename($publishid);
        $publishFilepath = $publishFolder.'/'.$publishFilename;
        
        if (!file_exists($publishFolder)) {
            CourseHub::log('Master publish folder do not exist : '.$publishFolder,$ls);
            if (!mkdir($publishFolder,0770,true)) {
                CourseHub::log('Master publish folder creation Failed',$ls);
                return false;
            }else{
                CourseHub::log('Master publish folder creation Succeed',$ls);
            }
        }
        
        if (file_exists($publishFilepath)) {
            CourseHub::log('OLD file already exist, rename it',$ls);
            $backupfile = $publishFilepath.'.old';
            if (file_exists($backupfile)) {
                unlink($backupfile);
            }
            rename($publishFilepath, $backupfile);
        }
        
        // Do the backup in the good folder
        if ( !$this->backupCourse($courseid, $publishFolder, $publishFilename) ) {
            CourseHub::log('Backup failed',$ls);
            rename($backupfile,$publishFilepath);
            return false;
        }
        
        // copy indexation
        $this->copyIndexationToMaster($publishid, $courseid);
        
        // Update locale published course
        $newpublished = new stdClass();
        $newpublished->courseid = $newCourseHub->courseid;
        $newpublished->publish = $newCourseHub->publish;
        $newpublished->status = 1;
        $newpublished->userid = $diffuser->id;
        $newpublished->timecreated = time();
        $newpublished->timemodified = time();
        $newpublished->lastsync = time();
        
        $published = $DB->get_record(CourseHub::TABLE_PUBLISHED, array('courseid'=>$newpublished->courseid,'publish'=>$newpublished->publish));
        
        if ($published === false) {
            $DB->insert_record(CourseHub::TABLE_PUBLISHED, $newpublished);
        }else{
            $newpublished->id = $published->id;
            $newpublished->timecreated = $published->timecreated;
            $DB->update_record(CourseHub::TABLE_PUBLISHED, $newpublished);
        }
        
        // update coursehub record
        $undeletepublish = new stdClass();
        $undeletepublish->id = $publishid;
        $undeletepublish->deleted = 0;
        $DB->update_record(CourseHub::TABLE_COURSE, $undeletepublish);
        
    }
    
    function publishCourse($courseid, $isalocalsession = 0) {
        global $USER, $CFG, $DB;
        $ls = 'CourseHubMaster::publishCourse('.$courseid.')';
        
        
        if(!$this->canPublish()) {
            CourseHub::log('This slave is not allowed to publish a course!',$ls);
            return false;
        }
        
        if (!has_capability('local/coursehub:publish', context_system::instance())) {
            CourseHub::log('The user is not allowed to publish a course (local/coursehub:publish)',$ls);
            return false;
        }
        
        $course = $DB->get_record('course',array('id'=>$courseid));
        
        $slave = $DB->get_record(CourseHub::TABLE_SLAVE,array('identifiant'=>$this->getIdentifiant()));
        
        if ($slave === false){
            return false;
        }
        
        // Create coursehub record with deleted flag or update existing one
        $newCourseHub = new stdClass();
        $newCourseHub->slaveid = $slave->id;
        $newCourseHub->courseid = $courseid;
        $newCourseHub->deleted = 1;
        $newCourseHub->publish = CourseHub::PUBLISH_PUBLISHED;
        $newCourseHub->name = $course->fullname;
        $newCourseHub->shortname = $course->shortname;
        $newCourseHub->summary = $course->summary;
        $newCourseHub->courseurl = (new moodle_url('/course/view.php',array('id'=>$courseid)))->out();
        $newCourseHub->coursestartdate = $course->startdate;
        $newCourseHub->courseenddate = $course->enddate;
        $newCourseHub->username = $USER->username;
        $newCourseHub->firstname = $USER->firstname;
        $newCourseHub->lastname = $USER->lastname;
        $newCourseHub->email = $USER->email;
        $newCourseHub->timecoursemodified = $course->timemodified;
        $newCourseHub->timecreated = time();
        $newCourseHub->timemodified = time();
        
        $roleparticipant = $DB->get_record('role', array('shortname'=>'participant'));
        $enrol = $DB->get_records('enrol', array('courseid' => $course->id, 'enrol' => 'self', 'status' => 0,'customint6' => 1,'roleid'=>$roleparticipant->id),'id DESC');
        
        if (count($enrol) > 0) {
            $enrol = array_shift($enrol);
            
            $newCourseHub->inscription_method = $enrol->enrol;
            $newCourseHub->enrolstartdate = $enrol->enrolstartdate;
            $newCourseHub->enrolenddate = $enrol->enrolenddate;
            $newCourseHub->enrolrole = 'participant';
            $newCourseHub->maxparticipant = $enrol->customint3;
            
            $categorie = $DB->get_record('course_categories',array('id'=>$course->category));
            $sessionCategorie = $DB->get_record_sql('SELECT * FROM {course_categories} WHERE name LIKE "session en auto-inscription" AND depth = 2');
            $isasession = 0;
            if(strpos($categorie->path, $sessionCategorie->path) !== false){
                $isasession = 1;
            }
            
            $newCourseHub->isasession = $isasession;
            $newCourseHub->isalocalsession = $isalocalsession;
        }
        
        $publishid = 0;
        
        $oldPublish = $DB->get_record(CourseHub::TABLE_COURSE,array('slaveid'=>$newCourseHub->slaveid,'courseid'=>$courseid,'publish'=>$newCourseHub->publish));
        if ( $oldPublish !== false ) {
            $publishid = $oldPublish->id;
            $newCourseHub->id = $oldPublish->id;
            if ($oldPublish->deleted == 1) { // found deleted publication, we overwrite it
                $DB->update_record(CourseHub::TABLE_COURSE,$newCourseHub);
            }else{
                $newCourseHub->timecreated = $oldPublish->timecreated;
                $DB->update_record(CourseHub::TABLE_COURSE,$newCourseHub);
            }
        }else{
            $publishid = $DB->insert_record(CourseHub::TABLE_COURSE,$newCourseHub);
        }
        
        // copy indexation
        $this->copyIndexationToMaster($publishid, $courseid);
        
        // Update locale published course
        $newpublished = new stdClass();
        $newpublished->courseid = $newCourseHub->courseid;
        $newpublished->publish = $newCourseHub->publish;
        $newpublished->status = 1;
        $newpublished->userid = $USER->id;
        $newpublished->timecreated = time();
        $newpublished->timemodified = time();
        $newpublished->lastsync = time();
        
        $published = $DB->get_record(CourseHub::TABLE_PUBLISHED, array('courseid'=>$newpublished->courseid,'publish'=>$newpublished->publish));
        
        if ($published === false) {
            $DB->insert_record(CourseHub::TABLE_PUBLISHED, $newpublished);
        }else{
            $newpublished->id = $published->id;
            $newpublished->timecreated = $published->timecreated;
            $DB->update_record(CourseHub::TABLE_PUBLISHED, $newpublished);
        }
        
        // update coursehub record
        $undeletepublish = new stdClass();
        $undeletepublish->id = $publishid;
        $undeletepublish->deleted = 0;
        $DB->update_record(CourseHub::TABLE_COURSE, $undeletepublish);
        
    }
    
    function unpublishCourse($courseid,$publishmod) {
        global $DB;
        $ls = 'CourseHubMaster::unpublishCourse('.$courseid.','.$publishmod.')';
        
        if(!$this->canDelete()) {
            CourseHub::log('This slave is not allowed to delete a publication!',$ls);
            return false;
        }
        
        if (!has_capability('local/coursehub:unpublish', context_system::instance())) {
            CourseHub::log('The user is not allowed to delete a publication (local/coursehub:unpublish)',$ls);
            return false;
        }
        
        if ($publishmod !== CourseHub::PUBLISH_PUBLISHED && $publishmod !== CourseHub::PUBLISH_SHARED) {
            echo 'Invalid publish mod';
            return false;
        }
        
        $course = $DB->get_record('course', array('id'=>$courseid));
        
        if ($course === false) {
            echo 'Course not found';
            return false;
        }
        
        $slave = $DB->get_record(CourseHub::TABLE_SLAVE,array('identifiant'=>$this->getIdentifiant()));
        
        if ($slave === false){
            echo 'Slave not found';
            return false;
        }
        
        
        $publish = $DB->get_record(CourseHub::TABLE_COURSE,array('slaveid'=>$slave->id,'courseid'=>$courseid,'publish'=>$publishmod));
        
        
        // remove master indexation
        if ( $this->removeMasterIndexation($publish->id,$courseid) === false) {
            echo 'Indexation remove failed';
            return false;
        }
        
        // remove master file
        if ($publishmod == CourseHub::PUBLISH_SHARED) {
            $publishFolder = $this->getShareFolderpath($publish->id);
            $publishFilename = $this->getShareFilename($publish->id);
            $file = $publishFolder.'/'.$publishFilename;
            
            if (file_exists($file)) {
                unlink($file);
            }
            if (file_exists($file.'.old')) {
                unlink($file.'.old');
            }
        }
        
        // remove master publish
        $deletedPublish = new stdClass();
        $deletedPublish->id = $publish->id;
        $deletedPublish->deleted = 1;
        
        $DB->update_record(CourseHub::TABLE_COURSE,$deletedPublish);
        
        
        // remove local publish
        $DB->delete_records(CourseHub::TABLE_PUBLISHED,array('courseid'=>$courseid));
        
        
        
    }
    
    function restoreCourse($hubcourseid,$redirect=false) {
        global $DB;
        $ls = 'CourseHubMaster::restoreCourse('.$hubcourseid.','.$redirect.')';
        
        if (!has_capability('local/coursehub:restore', context_system::instance())) {
            CourseHub::log('The user is not allowed to delete a publication (local/coursehub:restore)',$ls);
            return false;
        }
        
        $hubcourse = $DB->get_record(CourseHub::TABLE_COURSE,array('id'=>$hubcourseid));
        
        if ($hubcourse === false) {
            CourseHub::log('Publication not found in hub course table',$ls);
            return false;
        }
        
        if ($hubcourse->publish != CourseHub::PUBLISH_SHARED) {
            CourseHub::log('The publication is not a shared one',$ls);
            CourseHub::log('####'.print_r($hubcourse,true).'####',$ls);
            return false;
        }
        
        $publishFolder = $this->getShareFolderpath($hubcourseid);
        $publishFilename = $this->getShareFilename($hubcourseid);
        $publishFilepath = $publishFolder.'/'.$publishFilename;
        
        if (!file_exists($publishFilepath)) {
            CourseHub::log('Publication backup file not found : ##'.$publishFilepath.'##',$ls);
            return false;
        }
        
        $backuptempdir = make_backup_temp_directory('');
        $backupfilename = sha1($hubcourseid.time()).'.mbz';
        $backuppath = $backuptempdir . '/' . $backupfilename;
        
        symlink($publishFilepath, $backuppath);
        
        $url = new moodle_url('/backup/restore.php',array('contextid'=>2,'filename'=>$backupfilename));
        
        if ($redirect) {
            redirect($url);
        }
        return $url;
        
    }
    
    function fullRestoreCourse($hubcourseid,$categoryid,$fullname,$shortname,$visible=true) {
        global $CFG, $USER, $DB;
        $ls = 'CourseHubMaster::fullRestoreCourse('.$hubcourseid.','.$categoryid.',"'.$fullname.'","'.$shortname.'",'.$visible.')';
        
        $userid = $USER->id;
        if($userid == 0){
            if(($userid = OfferCourse::get_userid_by_specific_session_cookie('efe')) == 0){
                if(($userid = OfferCourse::get_userid_by_specific_session_cookie('dne-foad')) == 0){
                    CourseHub::log('The user cannot be found before to restore this publication',$ls);
                    return false;
                }
            }
        }
        
        CourseHub::log('Beginning course restoration',$ls);
        
        if (!has_capability('local/coursehub:restore', context_system::instance())) {
            CourseHub::log('The user is not allowed to delete a publication (local/coursehub:restore)',$ls);
            return false;
        }
        
        // We first get the backupo file from the master
        
        
        $hubcourse = $DB->get_record(CourseHub::TABLE_COURSE,array('id'=>$hubcourseid));
        
        if ($hubcourse === false) {
            CourseHub::log('Publication not found in hub course table',$ls);
            return false;
        }
        
        if ($hubcourse->publish != CourseHub::PUBLISH_SHARED) {
            CourseHub::log('The publication is not a shared one',$ls);
            CourseHub::log('####'.print_r($hubcourse,true).'####',$ls);
            return false;
        }
        
        $publishFolder = $this->getShareFolderpath($hubcourseid);
        $publishFilename = $this->getShareFilename($hubcourseid);
        $publishFilepath = $publishFolder.'/'.$publishFilename;
        
        if (!file_exists($publishFilepath)) {
            CourseHub::log('Publication backup file not found : ##'.$publishFilepath.'##',$ls);
            return false;
        }
        
        $backuptempdir = make_backup_temp_directory('');
        $backupfilebase = sha1($hubcourseid.time());
        $backupfilename = $backupfilebase.'.mbz';
        $backuppath = $backuptempdir . '/' . $backupfilename;
        
        symlink($publishFilepath, $backuppath);
        
        
        /*
         $backupsettings = array (
         'users' => 0,               // Include enrolled users (default = 1)
         'anonymize' => 0,           // Anonymize user information (default = 0)
         'role_assignments' => 0,    // Include user role assignments (default = 1)
         'activities' => 1,          // Include activities (default = 1)
         'blocks' => 1,              // Include blocks (default = 1)
         'filters' => 1,             // Include filters (default = 1)
         'comments' => 0,            // Include comments (default = 1)
         'userscompletion' => 0,     // Include user completion details (default = 1)
         'logs' => 0,                // Include course logs (default = 0)
         'grade_histories' => 0      // Include grade history (default = 0)
         );
         */
        
        // The backup file is available, we unpack it
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        
        // Extract backup
        $fp = get_file_packer('application/vnd.moodle.backup');
        if (!$fp->extract_to_pathname($CFG->tempdir.'/backup/'. $backupfilename, $CFG->tempdir.'/backup/'. $backupfilebase)){
            CourseHub::log('Backup extraction failed : ##'.$CFG->tempdir.'/backup/'. $backupfilename.'## => ##'.$CFG->tempdir.'/backup/'. $backupfilebase.'##',$ls);
            return false;
        }
        
        // Create new course.
        $newcourseid = restore_dbops::create_new_course($fullname, $shortname, $categoryid);
        
        $rc = new restore_controller($backupfilebase, $newcourseid,
            backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $userid, backup::TARGET_NEW_COURSE);
        /*
         foreach ($backupsettings as $name => $value) {
         $setting = $rc->get_plan()->get_setting($name);
         if ($setting->get_status() == backup_setting::NOT_LOCKED) {
         $setting->set_value($value);
         }
         }*/
        
        CourseHub::log('Execute restoration precheck',$ls);
        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupfilebase);
                }
                
                $errorinfo = '';
                
                foreach ($precheckresults['errors'] as $error) {
                    $errorinfo .= $error;
                }
                
                if (array_key_exists('warnings', $precheckresults)) {
                    foreach ($precheckresults['warnings'] as $warning) {
                        $errorinfo .= $warning;
                    }
                }
                CourseHub::log('backupprecheckerrors : ##'.print_r($errorinfo,true).'##',$ls);
            }
        }
        CourseHub::log('Launching course restoration',$ls);
        $rc->execute_plan();
        $rc->destroy();
        CourseHub::log('Course restore completed',$ls);
        
        $course = $DB->get_record('course', array('id' => $newcourseid), '*', MUST_EXIST);
        $course->fullname = $fullname;
        $course->shortname = $shortname;
        $course->visible = $visible;
        
        CourseHub::log('Set shortname and fullname back',$ls);
        $DB->update_record('course', $course);
        
        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($backupfilebase);
        }
        
        CourseHub::log('Copy indexation from master',$ls);
        $this->copyIndexationFromMaster($hubcourseid, $course->id);
        
        CourseHub::log('Add Manual enrol',$ls);
        if(!$DB->record_exists('enrol', array('courseid' => $course->id, 'enrol' => 'manual'))) {
            $role_participant = $DB->get_record('role', array('shortname' => 'participant'));
            $enrol = new stdClass();
            $enrol->enrol = 'manual';
            $enrol->courseid = $course->id;
            $enrol->status = 0;
            $enrol->enrolperiod = 0;
            $enrol->roleid = $role_participant->id;
            $enrol->timemodified = time();
            $DB->insert_record('enrol', $enrol);
        }
        
        CourseHub::log('Ending course restoration',$ls);
        return $course->id;
        
    }
    
    
    function createShareTask($courseid) {
        if ($this->getSlave($this->getIdentifiant()) !== false) {
            return $this->getSlave($this->getIdentifiant())->createShareTask($courseid);
        }
        return false;
    }
    
    function getShareTask($courseid) {
        if ($this->getSlave($this->getIdentifiant()) !== false) {
            return $this->getSlave($this->getIdentifiant())->getShareTask($courseid);
        }
        return false;
    }
    
    function cancelShareTask($courseid) {
        if ($this->getSlave($this->getIdentifiant()) !== false) {
            return $this->getSlave($this->getIdentifiant())->cancelShareTask($courseid);
        }
        return false;
    }
    
    function getType() {
        if ($this->getSlave($this->getIdentifiant()) !== false) {
            return $this->getSlave($this->getIdentifiant())->getType();
        }
        return false;
    }
    
    function getName() {
        if ($this->getSlave($this->getIdentifiant()) !== false) {
            return $this->getSlave($this->getIdentifiant())->getName();
        }
        return false;
    }
    
    function getShortname() {
        if ($this->getSlave($this->getIdentifiant()) !== false) {
            return $this->getSlave($this->getIdentifiant())->getShortname();
        }
        return false;
    }
    
    function getURL() {
        if ($this->getSlave($this->getIdentifiant()) !== false) {
            return $this->getSlave($this->getIdentifiant())->getURL();
        }
        return false;
    }
    
    function getToken() {
        if ($this->getSlave($this->getIdentifiant()) !== false) {
            return $this->getSlave($this->getIdentifiant())->getToken();
        }
        return false;
    }
    
    function canPublish() {
        if ($this->getSlave($this->getIdentifiant()) !== false) {
            return $this->getSlave($this->getIdentifiant())->canPublish();
        }
        return false;
    }
    
    function canShare() {
        if ($this->getSlave($this->getIdentifiant()) !== false) {
            return $this->getSlave($this->getIdentifiant())->canShare();
        }
        return false;
    }
    
    function canDelete() {
        if ($this->getSlave($this->getIdentifiant()) !== false) {
            return $this->getSlave($this->getIdentifiant())->canDelete();
        }
        return false;
    }
    
    function isDeleted() {
        if ($this->getSlave($this->getIdentifiant()) !== false) {
            return $this->getSlave($this->getIdentifiant())->isDeleted();
        }
        return false;
    }
    
}

class CourseHubConfSlaveLocal extends CourseHubConfSlave // master
{
    function __construct($slaveid) {
        $this->slaveid = $slaveid;
        
        $this->data = $GLOBALS['DB']->get_record(CourseHub::TABLE_SLAVE, array('id'=>$slaveid));
    }
    
    protected function getSharedFilesFolder() {
        global $CFG;
        $datafolder = str_replace(str_replace('ac-','',$CFG->academie_name), $this->getMasterIdentifiant(), $CFG->dataroot);
        return $datafolder.CourseHub::PUBLISHED_FILES_FOLDER;
    }
    
    public function setPermission($name,$value) {
        switch ($name) {
            case 'canpublish':
                return $this->setCanPublish($value);
                break;
            case 'canshare':
                return $this->setCanShare($value);
                break;
            case 'candelete':
                return $this->setCanDelete($value);
                break;
            default:
                return false;
        }
    }
    
    public function setCanPublish($value) {
        if ($value != CourseHub::PERMISSION_ALLOWED && $value != CourseHub::PERMISSION_DENIED) {
            return false;
        }
        
        $slave = new stdClass();
        $slave->id = $this->slaveid;
        $slave->canpublish = $value;
        
        self::setConfig($this->data->identifiant, 'canpublish', $value);
        
        return $GLOBALS['DB']->update_record(CourseHub::TABLE_SLAVE, $slave);
    }
    
    public function setCanShare($value) {
        if ($value != CourseHub::PERMISSION_ALLOWED && $value != CourseHub::PERMISSION_DENIED) {
            return false;
        }
        
        $slave = new stdClass();
        $slave->id = $this->slaveid;
        $slave->canshare = $value;
        
        self::setConfig($this->data->identifiant, 'canshare', $value);
        
        return $GLOBALS['DB']->update_record(CourseHub::TABLE_SLAVE, $slave);
    }
    
    public function setCanDelete($value) {
        if ($value != CourseHub::PERMISSION_ALLOWED && $value != CourseHub::PERMISSION_DENIED) {
            return false;
        }
        
        $slave = new stdClass();
        $slave->id = $this->slaveid;
        $slave->candelete = $value;
        
        self::setConfig($this->data->identifiant, 'candelete', $value);
        
        return $GLOBALS['DB']->update_record(CourseHub::TABLE_SLAVE, $slave);
    }
    
    public static function addinstance($identifiant) {
        global $CFG,$DB;
        
        // check if identifiant is valid
        if (!array_key_exists($identifiant, get_magistere_academy_config())) {
            return false;
        }
        
        // Define new slave
        $newslave = new stdClass();
        $newslave->type = CourseHub::SLAVE_TYPE_LOCAL;
        $newslave->identifiant = $identifiant;
        $newslave->url = $CFG->magistere_domaine.'/'.$identifiant;
        if (isset($CFG->academylist[$identifiant]['name'])) {
            $newslave->name = $CFG->academylist[$identifiant]['name'];
        }else{
            $newslave->name = $identifiant;
        }
        $newslave->shortname = $identifiant;
        $newslave->token = '';
        $newslave->deleted = 0;
        $newslave->trusted = 1;
        $newslave->canpublish = 1;
        $newslave->canshare = 1;
        $newslave->candelete = 1;
        
        // Config the slave
        $slaveconfig = self::getConfig($identifiant);
        
        if ($slaveconfig !== false)
        {
            if (!array_key_exists('version', $slaveconfig)) { // If plugin is not installed
                return false;
            }
            
            if (array_key_exists(CourseHub::CONF_MOD, $slaveconfig) && $identifiant != $GLOBALS['CFG']->academie_name) { // If already configured
                if ($slaveconfig[CourseHub::CONF_MOD] == CourseHub::CONF_MOD_SLAVE && isset($slaveconfig['master_identifiant']) && $slaveconfig['master_identifiant'] == $CFG->academie_name) {
                    
                }else{
                    return false;
                }
            }
            
            if ($identifiant != $GLOBALS['CFG']->academie_name) {
                self::setConfig($identifiant, CourseHub::CONF_MOD, CourseHub::CONF_MOD_SLAVE);
            }
            self::setConfig($identifiant, 'master_identifiant', $CFG->academie_name);
            self::setConfig($identifiant, 'master_url', $CFG->wwwroot);
            
            foreach($newslave AS $key=>$value) {
                if (in_array($key, array('type','identifiant','url','name','shortname','token','canpublish','canshare','candelete'))) {
                    self::setConfig($identifiant, $key, $value);
                }
            }
        }
        
        // Add on master
        $newid = $DB->insert_record(CourseHub::TABLE_SLAVE, $newslave);
        
        self::setConfig($identifiant, 'id', $newid);
        
        return $newid;
        
    }
    
    public static function deleteinstance($identifiant) {
        global $CFG,$DB;
        
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        
        // check if identifiant is valid
        if (!array_key_exists($identifiant, get_magistere_academy_config())) {
            return false;
        }
        
        foreach(self::getConfig($identifiant) AS $name=>$value) {
            if ($name != 'version') {
                self::setConfig($identifiant, $name, null);
            }
        }
        
        $slave = $DB->get_record(CourseHub::TABLE_SLAVE, array('identifiant'=>$identifiant));
        
        if ($slave === false) {
            return false;
        }
        
        $conf = databaseConnection::instance()->get($identifiant)->get_records('config_plugins',array('plugin'=>CourseHub::PLUGIN_FULLNAME),'','name,value');
        
        
        return $DB->delete_records(CourseHub::TABLE_SLAVE, array('identifiant'=>$identifiant));
    }
    
    
    private static function purge_plugin_config($identifiant) {
        global $CFG;
        $cachepath = str_replace($CFG->academie_name,$identifiant,$CFG->dataroot).'/cache/cachestore_file/default_application/core_config/be2-cache';
        if (file_exists($cachepath)) {
            return remove_dir($cachepath);
        }
        return false;
    }
    
    /***
     * Set the config $name with the value $value in the table config_plugins of the plateforme $identifiant
     * @param String $identifiant
     * @param String $name
     * @param String $value
     * @return Bool
     */
    private static function getConfig($identifiant,$name=null)
    {
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        
        if ($name == null) {
            $conf = databaseConnection::instance()->get($identifiant)->get_records('config_plugins',array('plugin'=>CourseHub::PLUGIN_FULLNAME),'','name,value');
            if (count($conf) > 0)
            {
                $config = array();
                foreach($conf AS $c) {
                    $config[$c->name] = $c->value;
                }
                return $config;
            }
            return false;
        }else{
            $conf = databaseConnection::instance()->get($identifiant)->get_record('config_plugins',array('plugin'=>CourseHub::PLUGIN_FULLNAME,'name'=>$name),'value');
            if ($conf !== false) {
                return $conf->value;
            }
            return false;
        }
        
    }
    
    /***
     * Set the config $name with the value $value in the table config_plugins of the plateforme $identifiant
     * @param string $identifiant
     * @param string $name
     * @param string $value
     * @return boolean
     */
    private static function setConfig($identifiant,$name,$value)
    {
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $modConf = new stdClass();
        $modConf->plugin = CourseHub::PLUGIN_FULLNAME;
        $modConf->name = $name;
        $modConf->value = $value;
        if ($value === null) {
            databaseConnection::instance()->get($identifiant)->delete_records('config_plugins',array('plugin'=>CourseHub::PLUGIN_FULLNAME,'name'=>$name));
        }else if ( ( $conf = databaseConnection::instance()->get($identifiant)->get_record('config_plugins',array('plugin'=>CourseHub::PLUGIN_FULLNAME,'name'=>$name)) ) !== false) {
            $modConf->id = $conf->id;
            databaseConnection::instance()->get($identifiant)->update_record('config_plugins',$modConf);
        }else{
            databaseConnection::instance()->get($identifiant)->insert_record('config_plugins',$modConf);
        }
        return self::purge_plugin_config($identifiant);
    }
}

class CourseHubSlaveLocal extends CourseHubSlave // slave
{
    function __construct() {
        $this->data = get_config(CourseHub::PLUGIN_FULLNAME);
    }
    
    protected function getSharedFilesFolder() {
        global $CFG;
        $datafolder = str_replace(str_replace('ac-','',$CFG->academie_name), $this->getMasterIdentifiant(), $CFG->dataroot);
        return $datafolder.CourseHub::PUBLISHED_FILES_FOLDER;
    }
    
    function createShareTask($courseid) {
        global $DB, $USER;
        
        if ( $DB->get_record(CourseHub::TABLE_TASKS, array('courseid'=>$courseid,'status'=>CourseHub::TASK_STATUS_TODO)) !== false) {
            return false;
        }
        
        if ( $DB->get_record(CourseHub::TABLE_TASKS, array('courseid'=>$courseid,'status'=>CourseHub::TASK_STATUS_INPROGRESS)) !== false) {
            return false;
        }
        
        $task = new stdClass();
        $task->courseid = $courseid;
        $task->publish = $courseid;
        $task->status = CourseHub::TASK_STATUS_TODO;
        $task->userid = $USER->id;
        $task->timecreated = time();
        
        $DB->insert_record(CourseHub::TABLE_TASKS, $task);
    }
    
    function getShareTask($courseid) {
        return $GLOBALS['DB']->get_record(CourseHub::TABLE_TASKS, array('courseid'=>$courseid));
    }
    
    function cancelShareTask($courseid) {
        global $DB;
        
        if ( $DB->get_record(CourseHub::TABLE_TASKS, array('courseid'=>$courseid,'status'=>CourseHub::TASK_STATUS_TODO)) === false) {
            return false;
        }
        
        return $DB->delete_records(CourseHub::TABLE_TASKS, array('courseid'=>$courseid));
    }
    
    function getId() {
        return $this->data->id;
    }
    
    function getMaster() {
        return $this->getMasterIdentifiant();
    }
    
    function getMasterIdentifiant() {
        return $this->data->master_identifiant;
    }
    
    function getMasterURL() {
        return $this->data->master_url;
    }
    
    function backupCourse($courseid, $destinationfolder, $destinationfilename) {
        global $CFG;
        $ls = 'CourseHubSlaveLocal::backupCourse("'.$courseid.'","'.$destinationfolder.','.$destinationfilename.'")';
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        
        $course = $GLOBALS['DB']->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $admin = get_admin();
        
        // The array of initial backup settings.
        $backupsettings = array (
            'users' => 0,               // Include enrolled users (default = 1)
            'anonymize' => 0,           // Anonymize user information (default = 0)
            'role_assignments' => 0,    // Include user role assignments (default = 1)
            'activities' => 1,          // Include activities (default = 1)
            'blocks' => 1,              // Include blocks (default = 1)
            'filters' => 1,             // Include filters (default = 1)
            'comments' => 0,            // Include comments (default = 1)
            'userscompletion' => 0,     // Include user completion details (default = 1)
            'logs' => 0,                // Include course logs (default = 0)
            'grade_histories' => 0      // Include grade history (default = 0)
        );
        
        $bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_YES, backup::MODE_GENERAL, $admin->id);
        
        // Apply the settings to all tasks
        foreach ($bc->get_plan()->get_tasks() as $taskindex => $task) {
            $settings = $task->get_settings();
            foreach ($settings as $setting) {
                $setting->set_status(backup_setting::NOT_LOCKED);
                
                // Modify the values of the intial backup settings
                if ($taskindex == 0) {
                    foreach ($backupsettings as $key => $value) {
                        if ($setting->get_name() == $key) {
                            $setting->set_value($value);
                        }
                    }
                }
            }
        }
        
        // Set the default filename.
        $format = $bc->get_format();
        $type = $bc->get_type();
        $id = $bc->get_id();
        $users = $bc->get_plan()->get_setting('users')->get_value();
        $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
        $filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
        $bc->get_plan()->get_setting('filename')->set_value($filename);
        
        // Execution.
        $bc->finish_ui();
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination']; // May be empty if file already moved to target location.
        
        if ($file) {
            if ($file->copy_content_to($destinationfolder.'/'.$destinationfilename)) {
                $file->delete();
            } else {
                CourseHub::log('Failed to move the backup to final destination',$ls);
                return false;
            }
        }
        $bc->destroy();
        return true;
    }
    
    function copyIndexationToMaster($publishid,$courseid) {
        global $CFG, $DB;
        
        $ls = 'CourseHubSlaveLocal::copyIndexationToMaster("'.$publishid.'","'.$courseid.'")';
        
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $masterId = $this->getMasterIdentifiant();
        
        $publish = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_COURSE,array('id'=>$publishid));
        
        if ($publish === false) {
            CourseHub::log('Publication not found',$ls);
            return false;
        }
        
        $indexation = $DB->get_record('local_indexation', array('courseid'=>$courseid));
        
        if ($indexation === false) {
            return false;
        }
        
        
        $indexation_keywords = $DB->get_records('local_indexation_keywords', array('indexationid'=>$indexation->id));
        $indexation_publics = $DB->get_records('local_indexation_public', array('indexationid'=>$indexation->id));
        $indexation_notes = $DB->get_records('local_indexation_notes', array('indexationid'=>$indexation->id));
        
        
        unset($indexation->id);
        $indexation->publishid = $publishid;
        
        $master_indexation = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_INDEX, array('publishid'=>$publishid));
        
        $master_indexationid = 0;
        
        if ($master_indexation !== false) {
            CourseHub::log('Indexation found, updating',$ls);
            $master_indexationid = $master_indexation->id;
            
            databaseConnection::instance()->get($masterId)->delete_records(CourseHub::TABLE_INDEX_KEYWORDS, array('indexationid'=>$master_indexation->id));
            databaseConnection::instance()->get($masterId)->delete_records(CourseHub::TABLE_INDEX_PUBLIC, array('indexationid'=>$master_indexation->id));
            databaseConnection::instance()->get($masterId)->delete_records(CourseHub::TABLE_INDEX_NOTES, array('indexationid'=>$master_indexation->id));
            
            $indexation->id = $master_indexation->id;
            
            databaseConnection::instance()->get($masterId)->update_record(CourseHub::TABLE_INDEX, $indexation);
            
        }else{
            CourseHub::log('Indexation not found, insert new one',$ls);
            $master_indexationid = databaseConnection::instance()->get($masterId)->insert_record(CourseHub::TABLE_INDEX, $indexation);
            CourseHub::log('Indexation inserted : id='.$master_indexationid,$ls);
        }
        
        
        
        if (count($indexation_keywords)>0) {
            CourseHub::log('Indexation keywords found: '.count($indexation_keywords),$ls);
            foreach($indexation_keywords AS $indexation_keyword) {
                unset($indexation_keyword->id);
                $indexation_keyword->indexationid = $master_indexationid;
                CourseHub::log('Inserting keyword :'.print_r($indexation_keyword,true),$ls);
                databaseConnection::instance()->get($masterId)->insert_record(CourseHub::TABLE_INDEX_KEYWORDS, $indexation_keyword);
            }
        }
        if (count($indexation_publics)>0) {
            CourseHub::log('Indexation publics found: '.count($indexation_publics),$ls);
            foreach($indexation_publics AS $indexation_public) {
                unset($indexation_public->id);
                $indexation_public->indexationid = $master_indexationid;
                CourseHub::log('Inserting public :'.print_r($indexation_public,true),$ls);
                databaseConnection::instance()->get($masterId)->insert_record(CourseHub::TABLE_INDEX_PUBLIC, $indexation_public);
            }
        }
        if (count($indexation_notes)>0) {
            CourseHub::log('Indexation notes found: '.count($indexation_notes),$ls);
            foreach($indexation_notes AS $indexation_note) {
                unset($indexation_note->id);
                $indexation_note->indexationid = $master_indexationid;
                CourseHub::log('Inserting note :'.print_r($indexation_note,true),$ls);
                databaseConnection::instance()->get($masterId)->insert_record(CourseHub::TABLE_INDEX_NOTES, $indexation_note);
            }
        }
        
        return true;
    }
    
    function removeMasterIndexation($publishid,$courseid) {
        
        $masterId = $this->getMasterIdentifiant();
        
        $master_indexation = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_INDEX, array('publishid'=>$publishid));
        
        if ($master_indexation === false) {
            return true;
        }
        
        databaseConnection::instance()->get($masterId)->delete_records(CourseHub::TABLE_INDEX_KEYWORDS, array('indexationid'=>$master_indexation->id));
        databaseConnection::instance()->get($masterId)->delete_records(CourseHub::TABLE_INDEX_PUBLIC, array('indexationid'=>$master_indexation->id));
        databaseConnection::instance()->get($masterId)->delete_records(CourseHub::TABLE_INDEX_NOTES, array('indexationid'=>$master_indexation->id));
        databaseConnection::instance()->get($masterId)->delete_records(CourseHub::TABLE_INDEX, array('publishid'=>$publishid));
        
    }
    
    function copyIndexationFromMaster($publishid,$courseid) {
        global $CFG, $DB;
        
        $ls = 'CourseHubSlaveLocal::copyIndexationFromMaster("'.$publishid.'","'.$courseid.'")';
        
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $masterId = $this->getMasterIdentifiant();
        
        $publish = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_COURSE,array('id'=>$publishid));
        
        if ($publish === false) {
            CourseHub::log('Publication not found',$ls);
            return false;
        }
        
        $indexation = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_INDEX, array('publishid'=>$publishid));
        
        if ($indexation === false) {
            return false;
        }
        
        
        $indexation_keywords = databaseConnection::instance()->get($masterId)->get_records(CourseHub::TABLE_INDEX_KEYWORDS, array('indexationid'=>$indexation->id));
        $indexation_publics = databaseConnection::instance()->get($masterId)->get_records(CourseHub::TABLE_INDEX_PUBLIC, array('indexationid'=>$indexation->id));
        $indexation_notes = databaseConnection::instance()->get($masterId)->get_records(CourseHub::TABLE_INDEX_NOTES, array('indexationid'=>$indexation->id));
        
        
        unset($indexation->id);
        $indexation->courseid = $courseid;
        
        $slave_indexation = $DB->get_record('local_indexation', array('courseid'=>$courseid));
        
        $slave_indexationid = 0;
        
        if ($slave_indexation !== false) {
            CourseHub::log('Indexation found, updating',$ls);
            $slave_indexationid = $slave_indexation->id;
            
            $DB->delete_records('local_indexation_keywords', array('indexationid'=>$slave_indexation->id));
            $DB->delete_records('local_indexation_public', array('indexationid'=>$slave_indexation->id));
            $DB->delete_records('local_indexation_notes', array('indexationid'=>$slave_indexation->id));
            
            $indexation->id = $slave_indexation->id;
            
            $DB->update_record('local_indexation', $indexation);
            
        }else{
            CourseHub::log('Indexation not found, insert new one',$ls);
            $slave_indexationid = $DB->insert_record('local_indexation', $indexation);
            CourseHub::log('Indexation inserted : id='.$slave_indexationid,$ls);
        }
        
        
        
        if (count($indexation_keywords)>0) {
            CourseHub::log('Indexation keywords found: '.count($indexation_keywords),$ls);
            foreach($indexation_keywords AS $indexation_keyword) {
                unset($indexation_keyword->id);
                $indexation_keyword->indexationid = $slave_indexationid;
                CourseHub::log('Inserting keyword :'.print_r($indexation_keyword,true),$ls);
                $DB->insert_record('local_indexation_keywords', $indexation_keyword);
            }
        }
        if (count($indexation_publics)>0) {
            CourseHub::log('Indexation publics found: '.count($indexation_publics),$ls);
            foreach($indexation_publics AS $indexation_public) {
                unset($indexation_public->id);
                $indexation_public->indexationid = $slave_indexationid;
                CourseHub::log('Inserting public :'.print_r($indexation_public,true),$ls);
                $DB->insert_record('local_indexation_public', $indexation_public);
            }
        }
        if (count($indexation_notes)>0) {
            CourseHub::log('Indexation notes found: '.count($indexation_notes),$ls);
            foreach($indexation_notes AS $indexation_note) {
                unset($indexation_note->id);
                $indexation_note->indexationid = $slave_indexationid;
                CourseHub::log('Inserting note :'.print_r($indexation_note,true),$ls);
                $DB->insert_record('local_indexation_notes', $indexation_note);
            }
        }
        
        return true;
    }
    
    function shareCourse($courseid,$userid = null) {
        global $USER, $CFG, $DB;
        $ls = 'CourseHubSlaveLocal::shareCourse('.$courseid.')';
        
        if(!$this->canShare()) {
            CourseHub::log('This slave is not allowed to share a course!',$ls);
            return false;
        }
        
        if (!has_capability('local/coursehub:share', context_system::instance())) {
            CourseHub::log('The user is not allowed to share a course (local/coursehub:share)',$ls);
            return false;
        }
        
        $diffuser = $USER;
        if ($userid !== null) {
            $diffuser = $DB->get_record('user', array('id'=>$userid));
        }
        
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $masterId = $this->getMasterIdentifiant();
        
        $course = $DB->get_record('course',array('id'=>$courseid));
        
        
        $slave = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_SLAVE,array('identifiant'=>$this->getIdentifiant()));
        
        if ($slave === false){
            return false;
        }
        
        // Create coursehub record with deleted flag or update existing one
        $newCourseHub = new stdClass();
        $newCourseHub->slaveid = $slave->id;
        $newCourseHub->courseid = $courseid;
        $newCourseHub->deleted = 1;
        $newCourseHub->publish = CourseHub::PUBLISH_SHARED;
        $newCourseHub->name = $course->fullname;
        $newCourseHub->shortname = $course->shortname;
        $newCourseHub->summary = $course->summary;
        $newCourseHub->courseurl = (new moodle_url('/course/view.php',array('id'=>$courseid)))->out();
        $newCourseHub->coursestartdate = $course->startdate;
        $newCourseHub->courseenddate = $course->enddate;
        $newCourseHub->username = $diffuser->username;
        $newCourseHub->firstname = $diffuser->firstname;
        $newCourseHub->lastname = $diffuser->lastname;
        $newCourseHub->email = $diffuser->email;
        $newCourseHub->timecoursemodified = $course->timemodified;
        $newCourseHub->timecreated = time();
        $newCourseHub->timemodified = time();
        
        $roleparticipant = $DB->get_record('role', array('shortname'=>'participant'));
        $enrol = $DB->get_records('enrol', array('courseid' => $course->id, 'enrol' => 'self', 'status' => 0,'customint6' => 1,'roleid'=>$roleparticipant->id),'id DESC');
        
        if (count($enrol) > 0) {
            $enrol = array_shift($enrol);
            
            $newCourseHub->inscription_method = $enrol->enrol;
            $newCourseHub->enrolstartdate = $enrol->enrolstartdate;
            $newCourseHub->enrolenddate = $enrol->enrolenddate;
            $newCourseHub->enrolrole = 'participant';
            $newCourseHub->maxparticipant = $enrol->customint3;
            
            $categorie = $DB->get_record('course_categories',array('id'=>$course->category));
            $sessionCategorie = $DB->get_record_sql('SELECT * FROM {course_categories} WHERE name LIKE "session en auto-inscription" AND depth = 2');
            $isasession = 0;
            if(strpos($categorie->path, $sessionCategorie->path) !== false){
                $isasession = 1;
            }
            
            $newCourseHub->isasession = $isasession;
        }
        
        $publishid = 0;
        
        $oldPublish = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_COURSE,array('slaveid'=>$newCourseHub->slaveid,'courseid'=>$courseid,'publish'=>$newCourseHub->publish));
        if ( $oldPublish !== false ) {
            $publishid = $oldPublish->id;
            $newCourseHub->id = $oldPublish->id;
            if ($oldPublish->deleted == 1) { // found deleted publication, we overwrite it
                databaseConnection::instance()->get($masterId)->update_record(CourseHub::TABLE_COURSE,$newCourseHub);
            }else{
                $newCourseHub->timecreated = $oldPublish->timecreated;
                databaseConnection::instance()->get($masterId)->update_record(CourseHub::TABLE_COURSE,$newCourseHub);
            }
        }else{
            $publishid = databaseConnection::instance()->get($masterId)->insert_record(CourseHub::TABLE_COURSE,$newCourseHub);
        }
        
        // get hub storage folder
        $publishFolder = $this->getShareFolderpath($publishid);
        $publishFilename = $this->getShareFilename($publishid);
        $publishFilepath = $publishFolder.'/'.$publishFilename;
        
        if (!file_exists($publishFolder)) {
            CourseHub::log('Master publish folder do not exist : '.$publishFolder,$ls);
            if (!mkdir($publishFolder,0770,true)) {
                CourseHub::log('Master publish folder creation Failed',$ls);
                return false;
            }else{
                CourseHub::log('Master publish folder creation Succeed',$ls);
            }
        }
        
        if (file_exists($publishFilepath)) {
            CourseHub::log('OLD file already exist, rename it',$ls);
            $backupfile = $publishFilepath.'.old';
            if (file_exists($backupfile)) {
                unlink($backupfile);
            }
            rename($publishFilepath, $backupfile);
        }
        
        // Do the backup in the good folder
        if ( !$this->backupCourse($courseid, $publishFolder, $publishFilename) ) {
            CourseHub::log('Backup failed',$ls);
            rename($backupfile,$publishFilepath);
            return false;
        }
        
        // copy indexation
        $this->copyIndexationToMaster($publishid, $courseid);
        
        // Update locale published course
        $newpublished = new stdClass();
        $newpublished->courseid = $newCourseHub->courseid;
        $newpublished->publish = $newCourseHub->publish;
        $newpublished->status = 1;
        $newpublished->userid = $diffuser->id;
        $newpublished->timecreated = time();
        $newpublished->timemodified = time();
        $newpublished->lastsync = time();
        
        $published = $DB->get_record(CourseHub::TABLE_PUBLISHED, array('courseid'=>$newpublished->courseid,'publish'=>$newpublished->publish));
        
        if ($published === false) {
            $DB->insert_record(CourseHub::TABLE_PUBLISHED, $newpublished);
        }else{
            $newpublished->id = $published->id;
            $newpublished->timecreated = $published->timecreated;
            $DB->update_record(CourseHub::TABLE_PUBLISHED, $newpublished);
        }
        
        // update coursehub record
        $undeletepublish = new stdClass();
        $undeletepublish->id = $publishid;
        $undeletepublish->deleted = 0;
        databaseConnection::instance()->get($masterId)->update_record(CourseHub::TABLE_COURSE, $undeletepublish);
        
    }
    
    function publishCourse($courseid, $isalocalsession = 0) {
        global $USER, $CFG, $DB;
        $ls = 'CourseHubSlaveLocal::publishCourse('.$courseid.')';
        
        
        if(!$this->canPublish()) {
            CourseHub::log('This slave is not allowed to publish a course!',$ls);
            return false;
        }
        
        if (!has_capability('local/coursehub:publish', context_system::instance())) {
            CourseHub::log('The user is not allowed to publish a course (local/coursehub:publish)',$ls);
            return false;
        }
        
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $masterId = $this->getMasterIdentifiant();
        
        $course = $DB->get_record('course',array('id'=>$courseid));
        
        
        $slave = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_SLAVE,array('identifiant'=>$this->getIdentifiant()));
        
        if ($slave === false){
            return false;
        }
        
        // Create coursehub record with deleted flag or update existing one
        $newCourseHub = new stdClass();
        $newCourseHub->slaveid = $slave->id;
        $newCourseHub->courseid = $courseid;
        $newCourseHub->deleted = 1;
        $newCourseHub->publish = CourseHub::PUBLISH_PUBLISHED;
        $newCourseHub->name = $course->fullname;
        $newCourseHub->shortname = $course->shortname;
        $newCourseHub->summary = $course->summary;
        $newCourseHub->courseurl = (new moodle_url('/course/view.php',array('id'=>$courseid)))->out();
        $newCourseHub->coursestartdate = $course->startdate;
        $newCourseHub->courseenddate = $course->enddate;
        $newCourseHub->username = $USER->username;
        $newCourseHub->firstname = $USER->firstname;
        $newCourseHub->lastname = $USER->lastname;
        $newCourseHub->email = $USER->email;
        $newCourseHub->timecoursemodified = $course->timemodified;
        $newCourseHub->timecreated = time();
        $newCourseHub->timemodified = time();
        
        $roleparticipant = $DB->get_record('role', array('shortname'=>'participant'));
        $enrol = $DB->get_records('enrol', array('courseid' => $course->id, 'enrol' => 'self', 'status' => 0,'customint6' => 1,'roleid'=>$roleparticipant->id),'id DESC');
        
        if (count($enrol) > 0) {
            $enrol = array_shift($enrol);
            
            $newCourseHub->inscription_method = $enrol->enrol;
            $newCourseHub->enrolstartdate = $enrol->enrolstartdate;
            $newCourseHub->enrolenddate = $enrol->enrolenddate;
            $newCourseHub->enrolrole = 'participant';
            $newCourseHub->maxparticipant = $enrol->customint3;
            
            $categorie = $DB->get_record('course_categories',array('id'=>$course->category));
            $sessionCategorie = $DB->get_record_sql('SELECT * FROM {course_categories} WHERE name LIKE "session en auto-inscription" AND depth = 2');
            $isasession = 0;
            if(strpos($categorie->path, $sessionCategorie->path) !== false){
                $isasession = 1;
            }
            
            $newCourseHub->isasession = $isasession;
            $newCourseHub->isalocalsession = $isalocalsession;
        }
        
        $publishid = 0;
        
        $oldPublish = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_COURSE,array('slaveid'=>$newCourseHub->slaveid,'courseid'=>$courseid,'publish'=>$newCourseHub->publish));
        if ( $oldPublish !== false ) {
            $publishid = $oldPublish->id;
            $newCourseHub->id = $oldPublish->id;
            if ($oldPublish->deleted == 1) { // found deleted publication, we overwrite it
                databaseConnection::instance()->get($masterId)->update_record(CourseHub::TABLE_COURSE,$newCourseHub);
            }else{
                $newCourseHub->timecreated = $oldPublish->timecreated;
                databaseConnection::instance()->get($masterId)->update_record(CourseHub::TABLE_COURSE,$newCourseHub);
            }
        }else{
            $publishid = databaseConnection::instance()->get($masterId)->insert_record(CourseHub::TABLE_COURSE,$newCourseHub);
        }
        
        // copy indexation
        $this->copyIndexationToMaster($publishid, $courseid);
        
        
        // Update locale published course
        $newpublished = new stdClass();
        $newpublished->courseid = $newCourseHub->courseid;
        $newpublished->publish = $newCourseHub->publish;
        $newpublished->status = 1;
        $newpublished->userid = $USER->id;
        $newpublished->timecreated = time();
        $newpublished->timemodified = time();
        $newpublished->lastsync = time();
        
        $published = $DB->get_record(CourseHub::TABLE_PUBLISHED, array('courseid'=>$newpublished->courseid,'publish'=>$newpublished->publish));
        
        if ($published === false) {
            $DB->insert_record(CourseHub::TABLE_PUBLISHED, $newpublished);
        }else{
            $newpublished->id = $published->id;
            $newpublished->timecreated = $published->timecreated;
            $DB->update_record(CourseHub::TABLE_PUBLISHED, $newpublished);
        }
        
        // update coursehub record
        $undeletepublish = new stdClass();
        $undeletepublish->id = $publishid;
        $undeletepublish->deleted = 0;
        databaseConnection::instance()->get($masterId)->update_record(CourseHub::TABLE_COURSE, $undeletepublish);
        
    }
    
    function unpublishCourse($courseid,$publishmod) {
        global $DB, $CFG;
        $ls = 'CourseHubSlaveLocal::unpublishCourse('.$courseid.','.$publishmod.')';
        
        if(!$this->canDelete()) {
            CourseHub::log('This slave is not allowed to delete a publication!',$ls);
            return false;
        }
        
        if (!has_capability('local/coursehub:unpublish', context_system::instance())) {
            CourseHub::log('The user is not allowed to delete a publication (local/coursehub:unpublish)',$ls);
            return false;
        }
        
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        if ($publishmod !== CourseHub::PUBLISH_PUBLISHED && $publishmod !== CourseHub::PUBLISH_SHARED) {
            echo 'Invalid publish mod';
            return false;
        }
        
        $course = $DB->get_record('course', array('id'=>$courseid));
        
        if ($course === false) {
            echo 'Course not found';
            return false;
        }
        
        $masterId = $this->getMasterIdentifiant();
        $slave = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_SLAVE,array('identifiant'=>$this->getIdentifiant()));
        
        if ($slave === false){
            echo 'Slave not found';
            return false;
        }
        
        
        $publish = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_COURSE,array('slaveid'=>$slave->id,'courseid'=>$courseid,'publish'=>$publishmod));
        
        
        // remove master indexation
        if ( $this->removeMasterIndexation($publish->id,$courseid) === false) {
            echo 'Indexation remove failed';
            return false;
        }
        
        // remove master file
        if ($publishmod == CourseHub::PUBLISH_SHARED) {
            $publishFolder = $this->getShareFolderpath($publish->id);
            $publishFilename = $this->getShareFilename($publish->id);
            $file = $publishFolder.'/'.$publishFilename;
            
            if (file_exists($file)) {
                unlink($file);
            }
            if (file_exists($file.'.old')) {
                unlink($file.'.old');
            }
        }
        
        // remove master publish
        $deletedPublish = new stdClass();
        $deletedPublish->id = $publish->id;
        $deletedPublish->deleted = 1;
        
        databaseConnection::instance()->get($masterId)->update_record(CourseHub::TABLE_COURSE,$deletedPublish);
        
        
        // remove local publish
        $DB->delete_records(CourseHub::TABLE_PUBLISHED,array('courseid'=>$courseid));
        
        
        
    }
    
    function restoreCourse($hubcourseid,$redirect=false) {
        global $CFG;
        $ls = 'CourseHubSlaveLocal::restoreCourse('.$hubcourseid.','.$redirect.')';
        
        if (!has_capability('local/coursehub:restore', context_system::instance())) {
            CourseHub::log('The user is not allowed to delete a publication (local/coursehub:restore)',$ls);
            return false;
        }
        
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $masterId = $this->getMasterIdentifiant();
        
        
        $hubcourse = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_COURSE,array('id'=>$hubcourseid));
        
        if ($hubcourse === false) {
            CourseHub::log('Publication not found in hub course table',$ls);
            return false;
        }
        
        if ($hubcourse->publish != CourseHub::PUBLISH_SHARED) {
            CourseHub::log('The publication is not a shared one',$ls);
            CourseHub::log('####'.print_r($hubcourse,true).'####',$ls);
            return false;
        }
        
        $publishFolder = $this->getShareFolderpath($hubcourseid);
        $publishFilename = $this->getShareFilename($hubcourseid);
        $publishFilepath = $publishFolder.'/'.$publishFilename;
        
        if (!file_exists($publishFilepath)) {
            CourseHub::log('Publication backup file not found : ##'.$publishFilepath.'##',$ls);
            return false;
        }
        
        $backuptempdir = make_backup_temp_directory('');
        $backupfilename = sha1($hubcourseid.time()).'.mbz';
        $backuppath = $backuptempdir . '/' . $backupfilename;
        
        symlink($publishFilepath, $backuppath);
        
        $url = new moodle_url('/backup/restore.php',array('contextid'=>2,'filename'=>$backupfilename));
        
        if ($redirect) {
            redirect($url);
        }
        return $url;
        
    }
    
    function fullRestoreCourse($hubcourseid,$categoryid,$fullname,$shortname,$visible=true) {
        global $CFG, $USER, $DB;
        $ls = 'CourseHubSlaveLocal::fullRestoreCourse('.$hubcourseid.','.$categoryid.',"'.$fullname.'","'.$shortname.'",'.$visible.')';
        
        $userid = $USER->id;
        if($userid == 0){
            if(($userid = OfferCourse::get_userid_by_specific_session_cookie('efe')) == 0){
                if(($userid = OfferCourse::get_userid_by_specific_session_cookie('dne-foad')) == 0){
                    CourseHub::log('The user cannot be found before to restore this publication',$ls);
                    return false;
                }
            }
        }
        
        CourseHub::log('Beginning course restoration',$ls);
        
        if (!has_capability('local/coursehub:restore', context_system::instance())) {
            CourseHub::log('The user is not allowed to delete a publication (local/coursehub:restore)',$ls);
            return false;
        }
        
        // We first get the backupo file from the master
        
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $masterId = $this->getMasterIdentifiant();
        
        $hubcourse = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_COURSE,array('id'=>$hubcourseid));
        
        if ($hubcourse === false) {
            CourseHub::log('Publication not found in hub course table',$ls);
            return false;
        }
        
        if ($hubcourse->publish != CourseHub::PUBLISH_SHARED) {
            CourseHub::log('The publication is not a shared one',$ls);
            CourseHub::log('####'.print_r($hubcourse,true).'####',$ls);
            return false;
        }
        
        $publishFolder = $this->getShareFolderpath($hubcourseid);
        $publishFilename = $this->getShareFilename($hubcourseid);
        $publishFilepath = $publishFolder.'/'.$publishFilename;
        
        if (!file_exists($publishFilepath)) {
            CourseHub::log('Publication backup file not found : ##'.$publishFilepath.'##',$ls);
            return false;
        }
        
        $backuptempdir = make_backup_temp_directory('');
        $backupfilebase = sha1($hubcourseid.time());
        $backupfilename = $backupfilebase.'.mbz';
        $backuppath = $backuptempdir . '/' . $backupfilename;
        
        symlink($publishFilepath, $backuppath);
        
        
        /*
         $backupsettings = array (
         'users' => 0,               // Include enrolled users (default = 1)
         'anonymize' => 0,           // Anonymize user information (default = 0)
         'role_assignments' => 0,    // Include user role assignments (default = 1)
         'activities' => 1,          // Include activities (default = 1)
         'blocks' => 1,              // Include blocks (default = 1)
         'filters' => 1,             // Include filters (default = 1)
         'comments' => 0,            // Include comments (default = 1)
         'userscompletion' => 0,     // Include user completion details (default = 1)
         'logs' => 0,                // Include course logs (default = 0)
         'grade_histories' => 0      // Include grade history (default = 0)
         );
         */
        
        // The backup file is available, we unpack it
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        
        // Extract backup
        $fp = get_file_packer('application/vnd.moodle.backup');
        if (!$fp->extract_to_pathname($CFG->tempdir.'/backup/'. $backupfilename, $CFG->tempdir.'/backup/'. $backupfilebase)){
            CourseHub::log('Backup extraction failed : ##'.$CFG->tempdir.'/backup/'. $backupfilename.'## => ##'.$CFG->tempdir.'/backup/'. $backupfilebase.'##',$ls);
            return false;
        }
        
        // Create new course.
        $newcourseid = restore_dbops::create_new_course($fullname, $shortname, $categoryid);
        
        $rc = new restore_controller($backupfilebase, $newcourseid,
            backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $userid, backup::TARGET_NEW_COURSE);
        /*
         foreach ($backupsettings as $name => $value) {
         $setting = $rc->get_plan()->get_setting($name);
         if ($setting->get_status() == backup_setting::NOT_LOCKED) {
         $setting->set_value($value);
         }
         }*/
        
        CourseHub::log('Execute restoration precheck',$ls);
        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupfilebase);
                }
                
                $errorinfo = '';
                
                foreach ($precheckresults['errors'] as $error) {
                    $errorinfo .= $error;
                }
                
                if (array_key_exists('warnings', $precheckresults)) {
                    foreach ($precheckresults['warnings'] as $warning) {
                        $errorinfo .= $warning;
                    }
                }
                CourseHub::log('backupprecheckerrors : ##'.print_r($errorinfo,true).'##',$ls);
            }
        }
        CourseHub::log('Launching course restoration',$ls);
        $rc->execute_plan();
        $rc->destroy();
        CourseHub::log('Course restore completed',$ls);
        
        $course = $DB->get_record('course', array('id' => $newcourseid), '*', MUST_EXIST);
        $course->fullname = $fullname;
        $course->shortname = $shortname;
        $course->visible = $visible;
        
        CourseHub::log('Set shortname and fullname back',$ls);
        $DB->update_record('course', $course);
        
        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($backupfilebase);
        }
        
        CourseHub::log('Copy indexation from master',$ls);
        $this->copyIndexationFromMaster($hubcourseid, $course->id);
        
        CourseHub::log('Add Manual enrol',$ls);
        if(!$DB->record_exists('enrol', array('courseid' => $course->id, 'enrol' => 'manual'))) {
            $role_participant = $DB->get_record('role', array('shortname' => 'participant'));
            $enrol = new stdClass();
            $enrol->enrol = 'manual';
            $enrol->courseid = $course->id;
            $enrol->status = 0;
            $enrol->enrolperiod = 0;
            $enrol->roleid = $role_participant->id;
            $enrol->timemodified = time();
            $DB->insert_record('enrol', $enrol);
        }
        
        CourseHub::log('Ending course restoration',$ls);
        return $course->id;
        
    }
    
    function getPublishedCourse($slaveIdentifiant,$courseid,$publishmod) {
        global $CFG;
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $masterId = $this->getMasterIdentifiant();
        
        $slave = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_SLAVE,array('identifiant'=>$this->getIdentifiant()));
        
        if ($slave === false){
            return false;
        }
        
        $hubcourse = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_COURSE,array('deleted'=>0,'slaveid'=>$slave->id,'courseid'=>$courseid,'publish'=>$publishmod));
        
        return $hubcourse;
    }
    
    function getPublishedCourseIndexation($slaveIdentifiant,$courseid,$publishmod) {
        global $CFG;
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $masterId = $this->getMasterIdentifiant();
        
        $slave = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_SLAVE,array('identifiant'=>$this->getIdentifiant()));
        
        if ($slave === false){
            return false;
        }
        
        $hubcourse = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_COURSE,array('deleted'=>0,'slaveid'=>$slave->id,'courseid'=>$courseid,'publish'=>$publishmod));
        
        if ($hubcourse === false){
            return false;
        }
        
        $hubcourseindex = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_INDEX,array('publishid'=>$hubcourse->id));
        
        return $hubcourseindex;
    }
    
    function getPublishedCourseById($hubcourseid) {
        global $CFG;
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $masterId = $this->getMasterIdentifiant();
        
        $hubcourse = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_COURSE,array('id'=>$hubcourseid,'deleted'=>0));
        
        return $hubcourse;
    }
    
    function getPublishedCourseIndexById($hubcourseid) {
        global $CFG;
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $masterId = $this->getMasterIdentifiant();
        
        $hubcourse = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_COURSE,array('id'=>$hubcourseid,'deleted'=>0));
        
        if ($hubcourse === false){
            return false;
        }
        
        $hubcourseindex = databaseConnection::instance()->get($masterId)->get_record(CourseHub::TABLE_INDEX,array('publishid'=>$hubcourseid));
        
        return $hubcourseindex;
    }
}


class CourseHubSlaveRemote extends CourseHubSlave // slave remote
{
    function __construct() {
        $this->data = get_config(CourseHub::PLUGIN_FULLNAME);
    }
    
    protected function getSharedFilesFolder() {
        global $CFG;
        $datafolder = str_replace(str_replace('ac-','',$CFG->academie_name), $this->getMasterIdentifiant(), $CFG->dataroot);
        return $datafolder.CourseHub::PUBLISHED_FILES_FOLDER;
    }
    
    function getId() {
        return $this->data->id;
    }
    
    function getMaster() {
        return $this->getMasterIdentifiant();
    }
    
    function getMasterIdentifiant() {
        return null;
    }
    
    function getMasterURL() {
        return $this->data->master_url;
    }
    
    function getToken(){
        return $this->data->token;
    }
    
    function getMasterToken(){
        return $this->data->mastertoken;
    }
    
    public function setPermission($name,$value) {
        switch ($name) {
            case 'canpublish':
                return $this->setCanPublish($value);
                break;
            case 'canshare':
                return $this->setCanShare($value);
                break;
            case 'candelete':
                return $this->setCanDelete($value);
                break;
            default:
                return false;
        }
    }
    
    public function setCanPublish($value) {
        if ($value != CourseHub::PERMISSION_ALLOWED && $value != CourseHub::PERMISSION_DENIED) {
            return false;
        }
        
        set_config('canpublish', $value, CourseHub::PLUGIN_FULLNAME);
    }
    
    public function setCanShare($value) {
        if ($value != CourseHub::PERMISSION_ALLOWED && $value != CourseHub::PERMISSION_DENIED) {
            return false;
        }
        
        set_config('canshare', $value, CourseHub::PLUGIN_FULLNAME);
    }
    
    public function setCanDelete($value) {
        if ($value != CourseHub::PERMISSION_ALLOWED && $value != CourseHub::PERMISSION_DENIED) {
            return false;
        }
        
        set_config('candelete', $value, CourseHub::PLUGIN_FULLNAME);
    }
    
    private function getApiURL(){
        return $this->getMasterURL().(substr($this->getMasterURL(),-1)=='/'?'':'/').'local/coursehub/api.php';
    }
    
    
    private function apicall($apiFunction,$params=array(),$file=null){
        $apiurl = $this->getApiURL();
        
        $params['function'] = $apiFunction;
        $params['slavetoken'] = $this->getToken();
        $params['slaveidentifiant'] = $this->getIdentifiant();
        
        try{
            //echo '###APIURL='.$apiurl."###\n";
            //echo '###APIPARAMS='.print_r($params,true)."###\n";
            $return = $this->post($apiurl,$params,array(),$file);
            //echo '###'.$return.'###';
        }catch(Exception $e){
            echo 'msg='.$e->getMessage();
            print_r($e);
        }
        
        return json_decode($return);
    }
    
    
    function backupCourse($courseid, $destinationfolder, $destinationfilename) {
        global $CFG;
        $ls = 'CourseHubSlaveRemote::backupCourse("'.$courseid.'","'.$destinationfolder.','.$destinationfilename.'")';
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        
        $course = $GLOBALS['DB']->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $admin = get_admin();
        
        // The array of initial backup settings.
        $backupsettings = array (
            'users' => 0,               // Include enrolled users (default = 1)
            'anonymize' => 0,           // Anonymize user information (default = 0)
            'role_assignments' => 0,    // Include user role assignments (default = 1)
            'activities' => 1,          // Include activities (default = 1)
            'blocks' => 1,              // Include blocks (default = 1)
            'filters' => 1,             // Include filters (default = 1)
            'comments' => 0,            // Include comments (default = 1)
            'userscompletion' => 0,     // Include user completion details (default = 1)
            'logs' => 0,                // Include course logs (default = 0)
            'grade_histories' => 0      // Include grade history (default = 0)
        );
        
        $bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_YES, backup::MODE_GENERAL, $admin->id);
        
        // Apply the settings to all tasks
        foreach ($bc->get_plan()->get_tasks() as $taskindex => $task) {
            $settings = $task->get_settings();
            foreach ($settings as $setting) {
                $setting->set_status(backup_setting::NOT_LOCKED);
                
                // Modify the values of the intial backup settings
                if ($taskindex == 0) {
                    foreach ($backupsettings as $key => $value) {
                        if ($setting->get_name() == $key) {
                            $setting->set_value($value);
                        }
                    }
                }
            }
        }
        
        // Set the default filename.
        $format = $bc->get_format();
        $type = $bc->get_type();
        $id = $bc->get_id();
        $users = $bc->get_plan()->get_setting('users')->get_value();
        $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
        $filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
        $bc->get_plan()->get_setting('filename')->set_value($filename);
        
        // Execution.
        $bc->finish_ui();
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination']; // May be empty if file already moved to target location.
        
        if ($file) {
            if ($file->copy_content_to($destinationfolder.'/'.$destinationfilename)) {
                $file->delete();
            } else {
                CourseHub::log('Failed to move the backup to final destination',$ls);
                return false;
            }
        }
        $bc->destroy();
        return true;
    }
    
    function shareCourse($courseid,$userid = null) {
        global $USER, $CFG, $DB;
        $ls = 'CourseHubSlaveRemote::shareCourse('.$courseid.')';
        
        if(!$this->canShare()) {
            CourseHub::log('This slave is not allowed to share a course!',$ls);
            return false;
        }
        
        if (!has_capability('local/coursehub:share', context_system::instance())) {
            CourseHub::log('The user is not allowed to share a course (local/coursehub:share)',$ls);
            return false;
        }
        
        $diffuser = $USER;
        if ($userid !== null) {
            $diffuser = $DB->get_record('user', array('id'=>$userid));
        }
        
        $course = $DB->get_record('course',array('id'=>$courseid));
        
        $slave = CourseHub::instance();
                
        $newCourseHub = array(
            'courseid'=> $courseid,
            'name'=> $course->fullname,
            'shortname'=> $course->shortname,
            'summary'=> $course->summary,
            'courseurl'=> (new moodle_url('/course/view.php',array('id'=>$courseid)))->out(),
            'coursestartdate'=> $course->startdate,
            'courseenddate'=> $course->enddate,
            'username' => $diffuser->username,
            'firstname' => $diffuser->firstname,
            'lastname' => $diffuser->lastname,
            'email' => $diffuser->email
        );
        
        
        $roleparticipant = $DB->get_record('role', array('shortname'=>'participant'));
        if ($roleparticipant !== false){
            $enrol = $DB->get_records('enrol', array('courseid' => $course->id, 'enrol' => 'self', 'status' => 0,'customint6' => 1,'roleid'=>$roleparticipant->id),'id DESC');
        }
        
        if ($roleparticipant !== false && count($enrol) > 0) {
            $enrol = array_shift($enrol);
            
            $newCourseHub['enrolmethod'] = $enrol->enrol;
            $newCourseHub['enrolstartdate'] = $enrol->enrolstartdate;
            $newCourseHub['enrolenddate'] = $enrol->enrolenddate;
            $newCourseHub['enrolrole'] = $roleparticipant->shortname;
            $newCourseHub['enrolmaxuser'] = $enrol->customint3;
            $newCourseHub['courseissession'] = 0;
            
            $categorie = $DB->get_record('course_categories',array('id'=>$course->category));
            $sessionCategorie = $DB->get_record_sql('SELECT * FROM {course_categories} WHERE name LIKE "session en auto-inscription" AND depth = 2');
            if(strpos($categorie->path, $sessionCategorie->path) !== false){
                $newCourseHub['courseissession'] = 1;
            }
        }else{
            $newCourseHub['enrolmethod'] = '';
            $newCourseHub['enrolstartdate'] = '';
            $newCourseHub['enrolenddate'] = '';
            $newCourseHub['enrolrole'] = '';
            $newCourseHub['enrolmaxuser'] = 0;
            $newCourseHub['courseissession'] = 0;
        }
        
        $publishFolder = $CFG->tempdir;
        $publishFilename = 'publish'.$courseid.'.mbz';
        $publishFilepath = $publishFolder.'/'.$publishFilename;
        $this->backupCourse($courseid, $publishFolder, $publishFilename);
        
        // Do the backup in the good folder
        if ( !$this->backupCourse($courseid, $publishFolder, $publishFilename) ) {
            CourseHub::log('Backup failed',$ls);
            return false;
        }
        
        // Share course on master
        CourseHub::log('Calling Master API',$ls);
        $reply = $this->apicall('shareCourse', $newCourseHub, $publishFilepath);
        CourseHub::log('Master API reply : '.print_r($reply,true),$ls);
        
        if (!isset($reply->error) || $reply->error != false || $reply->msg != 'SHARE_SUCCESS'){
            CourseHub::log('Sharing failed',$ls);
            return false;
        }
        
        // Update locale published course
        $newpublished = new stdClass();
        $newpublished->courseid = $newCourseHub['courseid'];
        $newpublished->publish = CourseHub::PUBLISH_SHARED;
        $newpublished->status = 1;
        $newpublished->userid = $diffuser->id;
        $newpublished->timecreated = time();
        $newpublished->timemodified = time();
        $newpublished->lastsync = time();
        
        $published = $DB->get_record(CourseHub::TABLE_PUBLISHED, array('courseid'=>$newpublished->courseid,'publish'=>$newpublished->publish));
        
        if ($published === false) {
            $DB->insert_record(CourseHub::TABLE_PUBLISHED, $newpublished);
        }else{
            $newpublished->id = $published->id;
            $newpublished->timecreated = $published->timecreated;
            $DB->update_record(CourseHub::TABLE_PUBLISHED, $newpublished);
        }
    }
    
    function publishCourse($courseid, $isalocalsession = 0) {
        global $USER, $DB;
        $ls = 'CourseHubSlaveRemote::shareCourse('.$courseid.')';
        
        if(!$this->canShare()) {
            CourseHub::log('This slave is not allowed to share a course!',$ls);
            return false;
        }
        
        if (!has_capability('local/coursehub:share', context_system::instance())) {
            CourseHub::log('The user is not allowed to share a course (local/coursehub:share)',$ls);
            return false;
        }
        
        $diffuser = $USER;
        
        $course = $DB->get_record('course',array('id'=>$courseid));
        
        $newCourseHub = array(
            'courseid'=> $courseid,
            'name'=> $course->fullname,
            'shortname'=> $course->shortname,
            'summary'=> $course->summary,
            'courseurl'=> (new moodle_url('/course/view.php',array('id'=>$courseid)))->out(),
            'coursestartdate'=> $course->startdate,
            'courseenddate'=> $course->enddate,
            'username' => $diffuser->username,
            'firstname' => $diffuser->firstname,
            'lastname' => $diffuser->lastname,
            'email' => $diffuser->email
        );
        
        
        $roleparticipant = $DB->get_record('role', array('shortname'=>'participant'));
        $enrol = $DB->get_records('enrol', array('courseid' => $course->id, 'enrol' => 'self', 'status' => 0,'customint6' => 1,'roleid'=>$roleparticipant->id),'id DESC');
        
        if (count($enrol) > 0) {
            $enrol = array_shift($enrol);
            
            $newCourseHub['enrolmethod'] = $enrol->enrol;
            $newCourseHub['enrolstartdate'] = $enrol->enrolstartdate;
            $newCourseHub['enrolenddate'] = $enrol->enrolenddate;
            $newCourseHub['enrolrole'] = $roleparticipant->shortname;
            $newCourseHub['enrolmaxuser'] = $enrol->customint3;
            $newCourseHub['courseissession'] = 0;
            
            $categorie = $DB->get_record('course_categories',array('id'=>$course->category));
            $sessionCategorie = $DB->get_record_sql('SELECT * FROM {course_categories} WHERE name LIKE "session en auto-inscription" AND depth = 2');
            if(strpos($categorie->path, $sessionCategorie->path) !== false){
                $newCourseHub['courseissession'] = 1;
            }
        }else{
            $newCourseHub['enrolmethod'] = '';
            $newCourseHub['enrolstartdate'] = '';
            $newCourseHub['enrolenddate'] = '';
            $newCourseHub['enrolrole'] = '';
            $newCourseHub['enrolmaxuser'] = 0;
            $newCourseHub['courseissession'] = 0;
        }
        
        // Publish course on master
        CourseHub::log('Calling Master API',$ls);
        $reply = $this->apicall('publishCourse', $newCourseHub);
        CourseHub::log('Master API reply : '.print_r($reply,true),$ls);
        
        if ($reply->error != false || $reply->msg != 'PUBLISH_SUCCESS'){
            CourseHub::log('Publishing failed ('.$reply->msg.')',$ls);
            return false;
        }
        
        // Update locale published course
        $newpublished = new stdClass();
        $newpublished->courseid = $newCourseHub['courseid'];
        $newpublished->publish = CourseHub::PUBLISH_PUBLISHED;
        $newpublished->status = 1;
        $newpublished->userid = $diffuser->id;
        $newpublished->timecreated = time();
        $newpublished->timemodified = time();
        $newpublished->lastsync = time();
        
        $published = $DB->get_record(CourseHub::TABLE_PUBLISHED, array('courseid'=>$newpublished->courseid,'publish'=>$newpublished->publish));
        
        if ($published === false) {
            $DB->insert_record(CourseHub::TABLE_PUBLISHED, $newpublished);
        }else{
            $newpublished->id = $published->id;
            $newpublished->timecreated = $published->timecreated;
            $DB->update_record(CourseHub::TABLE_PUBLISHED, $newpublished);
        }
        
    }
    
    function unpublishCourse($courseid,$publishmod) {
        global $DB, $CFG;
        $ls = 'CourseHubSlaveRemote::unpublishCourse('.$courseid.','.$publishmod.')';
        
        if(!$this->canDelete()) {
            CourseHub::log('This slave is not allowed to delete a publication!',$ls);
            return false;
        }
        
        if (!has_capability('local/coursehub:unpublish', context_system::instance())) {
            CourseHub::log('The user is not allowed to delete a publication (local/coursehub:unpublish)',$ls);
            return false;
        }
        
        if ($publishmod !== CourseHub::PUBLISH_PUBLISHED && $publishmod !== CourseHub::PUBLISH_SHARED) {
            echo 'Invalid publish mod';
            return false;
        }
        
        $course = $DB->get_record('course', array('id'=>$courseid));
        
        if ($course === false) {
            echo 'Course not found';
            return false;
        }
        
        $data = array(
            'courseid'=>$courseid,
            'publishmod'=>$publishmod
        );
        
        // Unpublish course on master
        CourseHub::log('Calling Master API',$ls);
        $reply = $this->apicall('unpublishCourse', $data);
        CourseHub::log('Master API reply : '.print_r($reply,true),$ls);
        
        if ($reply->error != false || $reply->msg != 'COURSE_UNPUBLISHED'){
            CourseHub::log('Unpublishing failed',$ls);
            return false;
        }
        
        // remove local publish
        $DB->delete_records(CourseHub::TABLE_PUBLISHED,array('courseid'=>$courseid));
        
        return true;
    }
    
    function restoreCourse($hubcourseid,$redirect=false) {
        // TOBEDONE
    }
    
    
    function fullRestoreCourse($hubcourseid,$categoryid,$fullname,$shortname,$visible=true) {
        // TOBEDONE
    }
    
    private static $publishedcourse = array();
    
    function getPublishedCourse($slaveIdentifiant,$courseid,$publishmod) {
        $ls = 'CourseHubSlaveRemote::getPublishedCourse('.$courseid.','.$publishmod.')';
        
        if ($publishmod !== CourseHub::PUBLISH_PUBLISHED && $publishmod !== CourseHub::PUBLISH_SHARED) {
            echo 'Invalid publish mod';
            return false;
        }
        
        if (isset(self::$publishedcourse[$courseid.'_'.$publishmod])){
            return self::$publishedcourse[$courseid.'_'.$publishmod];
        }
        
        $data = array(
            'courseid'=>$courseid,
            'publishmod'=>$publishmod
        );
        
        // Unpublish course on master
        CourseHub::log('Calling Master API',$ls);
        $reply = $this->apicall('getPublishedCourse', $data);
        CourseHub::log('Master API reply : '.print_r($reply,true),$ls);
        
        if ($reply->error != false || !isset($reply->course)){
            CourseHub::log('Getting published course failed',$ls);
            return false;
        }
        
        return self::$publishedcourse[$courseid.'_'.$publishmod] = $reply->course;
    }
    
    private static $publishedcoursebyid = array();
    
    function getPublishedCourseById($hubcourseid) {
        $ls = 'CourseHubSlaveRemote::getPublishedCourseById('.$hubcourseid.')';
        
        if (isset(self::$publishedcourse[$hubcourseid])){
            return self::$publishedcourse[$hubcourseid];
        }
        
        $data = array(
            'id'=>$hubcourseid
        );
        
        // Unpublish course on master
        CourseHub::log('Calling Master API',$ls);
        $reply = $this->apicall('getPublishedCourseById', $data);
        CourseHub::log('Master API reply : '.print_r($reply,true),$ls);
        
        if ($reply->error != false || !isset($reply->course)){
            CourseHub::log('Getting published course failed',$ls);
            return false;
        }
        
        return self::$publishedcourse[$hubcourseid] = $reply->course;
    }
    
    private static $searchedPublishedCourse = array();
    
    function searchPublishedCourse($search,$publishmod) {
        $ls = 'CourseHubSlaveRemote::searchPublishedCourse("'.$search.'")';
        
        if (isset(self::$searchedPublishedCourse[$publishmod.'_'.$search])){
            return self::$searchedPublishedCourse[$publishmod.'_'.$search];
        }
        
        $data = array(
            'search'=>$search,
            'publishmod'=>$publishmod
        );
        
        // Unpublish course on master
        CourseHub::log('Calling Master API',$ls);
        $reply = $this->apicall('searchPublishedCourses', $data);
        CourseHub::log('Master API reply : '.print_r($reply,true),$ls);
        
        if ($reply->error != false || !isset($reply->courses)){
            CourseHub::log('Searching published course failed',$ls);
            return false;
        }
        
        return self::$searchedPublishedCourse[$publishmod.'_'.$search] = $reply->courses;
    }
    
    
    public function deleteinstance() {
        $ls = 'CourseHubSlaveRemote::deleteinstance()';
        
        set_config('mod', NULL, CourseHub::PLUGIN_FULLNAME);
        set_config('type', NULL, CourseHub::PLUGIN_FULLNAME);
        set_config('identifiant', NULL, CourseHub::PLUGIN_FULLNAME);
        set_config('shortname', NULL, CourseHub::PLUGIN_FULLNAME);
        set_config('url', NULL, CourseHub::PLUGIN_FULLNAME);
        set_config('name', NULL, CourseHub::PLUGIN_FULLNAME);
        set_config('token', NULL, CourseHub::PLUGIN_FULLNAME);
        set_config('mastertoken', NULL, CourseHub::PLUGIN_FULLNAME);
        set_config('master_url', NULL, CourseHub::PLUGIN_FULLNAME);
        set_config('canpublish', NULL, CourseHub::PLUGIN_FULLNAME);
        set_config('canshare', NULL, CourseHub::PLUGIN_FULLNAME);
        set_config('candelete', NULL, CourseHub::PLUGIN_FULLNAME);
        
        return true;
    }
    
}


class CourseHubConfSlaveRemote extends CourseHubConfSlave // master
{
    function __construct($slaveid) {
        $this->slaveid = $slaveid;
        
        $this->data = $GLOBALS['DB']->get_record(CourseHub::TABLE_SLAVE, array('id'=>$slaveid));
    }
    
    protected function getSharedFilesFolder() {
        global $CFG;
        $datafolder = str_replace(str_replace('ac-','',$CFG->academie_name), $this->getMasterIdentifiant(), $CFG->dataroot);
        return $datafolder.CourseHub::PUBLISHED_FILES_FOLDER;
    }
    
    
    function getMasterToken() {
        return $this->data->mastertoken;
    }
    
    function getApiURL(){
        return $this->data->url.'/local/coursehub/api.php';
    }
    
    
    private function apicall($apiFunction,$params=array(),$file=null){
        $apiurl = $this->getApiURL();
        
        $params['function'] = $apiFunction;
        $params['mastertoken'] = $this->getMasterToken();
        $params['slaveidentifiant'] = $this->getIdentifiant();
        
        try{
            //echo '###APIURL='.$apiurl."###\n";
            //echo '###APIPARAMS='.print_r($params,true)."###\n";
            $return = $this->post($apiurl,$params,array(),$file);
            //echo '###'.$return.'###';
        }catch(Exception $e){
            echo 'msg='.$e->getMessage();
            print_r($e);
        }
        
        return json_decode($return);
    }
    
    public function setPermission($name,$value) {
        switch ($name) {
            case 'canpublish':
                return $this->setCanPublish($value);
                break;
            case 'canshare':
                return $this->setCanShare($value);
                break;
            case 'candelete':
                return $this->setCanDelete($value);
                break;
            default:
                return false;
        }
    }
    
    public function setCanPublish($value) {
        $ls = 'CourseHubConfSlaveRemote::setCanPublish('.$value.')';
        if ($value != CourseHub::PERMISSION_ALLOWED && $value != CourseHub::PERMISSION_DENIED) {
            return false;
        }
        
        $data = array(
            'canpublish'=>$value
        );
        
        // Set permission course on slave
        CourseHub::log('Calling Master API',$ls);
        $reply = $this->apicall('setPermission', $data);
        CourseHub::log('Master API reply : '.print_r($reply,true),$ls);
        
        if ($reply->error != false || !isset($reply->msg)){
            CourseHub::log('Setting slave permission failed',$ls);
            return false;
        }
        
        $slave = new stdClass();
        $slave->id = $this->slaveid;
        $slave->canpublish = $value;
        
        return $GLOBALS['DB']->update_record(CourseHub::TABLE_SLAVE, $slave);
    }
    
    public function setCanShare($value) {
        $ls = 'CourseHubConfSlaveRemote::setCanShare('.$value.')';
        if ($value != CourseHub::PERMISSION_ALLOWED && $value != CourseHub::PERMISSION_DENIED) {
            return false;
        }
        
        $data = array(
            'canshare'=>$value
        );
        
        // Set permission course on slave
        CourseHub::log('Calling Master API',$ls);
        $reply = $this->apicall('setPermission', $data);
        CourseHub::log('Master API reply : '.print_r($reply,true),$ls);
        
        if ($reply->error != false || !isset($reply->msg)){
            CourseHub::log('Searching published course failed',$ls);
            return false;
        }
        
        $slave = new stdClass();
        $slave->id = $this->slaveid;
        $slave->canshare = $value;
        
        return $GLOBALS['DB']->update_record(CourseHub::TABLE_SLAVE, $slave);
    }
    
    public function setCanDelete($value) {
        $ls = 'CourseHubConfSlaveRemote::setCanDelete('.$value.')';
        if ($value != CourseHub::PERMISSION_ALLOWED && $value != CourseHub::PERMISSION_DENIED) {
            return false;
        }
        
        $data = array(
            'candelete'=>$value
        );
        
        // Set permission course on slave
        CourseHub::log('Calling Master API',$ls);
        $reply = $this->apicall('setPermission', $data);
        CourseHub::log('Master API reply : '.print_r($reply,true),$ls);
        
        if ($reply->error != false || !isset($reply->msg)){
            CourseHub::log('Searching published course failed',$ls);
            return false;
        }
        
        $slave = new stdClass();
        $slave->id = $this->slaveid;
        $slave->candelete = $value;
        
        return $GLOBALS['DB']->update_record(CourseHub::TABLE_SLAVE, $slave);
    }
    
    public static function addinstance($slaveIdentifiant,$slaveToken,$slaveName,$slaveURL) {
        global $DB;
        
        // check if identifiant exist and is not already used
        $slave = $DB->get_record(CourseHub::TABLE_SLAVE, array('identifiant'=>$slaveIdentifiant));
        if ($slave === false){
            return CourseHub::ERROR_SLAVE_NOT_REGISTERED;
        }
        if($slave->trusted == 1){
            return CourseHub::ERROR_SLAVE_ALREADY_LINKED;
        }
        
        if ($slave->token != $slaveToken){
            return CourseHub::ERROR_SLAVE_TOKEN_MISMATCH;
        }
        
        // Define new slave
        $slave->url = $slaveURL;
        $slave->name = $slaveName;
        $slave->deleted = 0;
        $slave->trusted = 1;
        $slave->canpublish = 1;
        $slave->canshare = 1;
        $slave->candelete = 1;
        
        // Add on master
        $newid = $DB->update_record(CourseHub::TABLE_SLAVE, $slave);
        
        return $newid;
    }
    
    public static function declareinstance($slaveIdentifiant) {
        global $DB;
        
        // check if identifiant do not exist
        $slave = $DB->get_record(CourseHub::TABLE_SLAVE, array('identifiant'=>$slaveIdentifiant));
        if ($slave !== false){
            return CourseHub::ERROR_SLAVE_ALREADY_EXIST;
        }
        
        // Define new slave
        $newslave = new stdClass();
        $newslave->type = CourseHub::SLAVE_TYPE_REMOTE;
        $newslave->identifiant = $slaveIdentifiant;
        $newslave->url = '';
        $newslave->name = '';
        $newslave->shortname = $slaveIdentifiant;
        $newslave->mastertoken = random_string(32);
        $newslave->token = random_string(32);
        $newslave->deleted = 0;
        $newslave->trusted = 0;
        $newslave->canpublish = 1;
        $newslave->canshare = 1;
        $newslave->candelete = 1;
        
        // Add on master
        $newid = $DB->insert_record(CourseHub::TABLE_SLAVE, $newslave);
        
        return $newid;
    }
    
    
    
    public static function deleteinstance($identifiant) {
        $ls = 'CourseHubConfSlaveRemote::deleteinstance('.$identifiant.')';
        
        $hub = CourseHub::instance();
        
        $slave = $hub->getSlave($identifiant);
        
        if ($slave->isTrusted()){
            $data = array();
            
            // Set permission course on slave
            CourseHub::log('Calling Master API',$ls);
            $reply = $slave->apicall('removeSlave', $data);
            CourseHub::log('Master API reply : '.print_r($reply,true),$ls);
            
            if ($reply->error != false && $reply->msg != 'NO_API_AVAILABLE'){
                CourseHub::log('Removing slave failed',$ls);
                return false;
            }
        }
        
        
        $GLOBALS['DB']->delete_records(CourseHub::TABLE_SLAVE, array('id'=>$slave->getId()));
    }
    
    
    private static function purge_plugin_config($identifiant) {
        return true;
    }
    
    /***
     * Set the config $name with the value $value in the table config_plugins of the plateforme $identifiant
     * @param String $identifiant
     * @param String $name
     * @param String $value
     * @return Bool
     */
    private static function getConfig($identifiant,$name=null)
    {
        //TODO
    }
}



class CourseHubSlaveNoConfig extends CourseHubSlave // master&slave
{
    function __construct() {
        
    }
    
    /***
     * 
     * @param string $mod Values : CourseHub::CONF_MOD_MASTER
     * @return boolean
     */
    function setMod($mod) {
        if ( get_config(CourseHub::PLUGIN_FULLNAME,CourseHub::CONF_MOD) == false && ( $mod == CourseHub::CONF_MOD_MASTER || $mod == CourseHub::CONF_MOD_SLAVE ) ) {
            return set_config(CourseHub::CONF_MOD,$mod,CourseHub::PLUGIN_FULLNAME);
        }
        return false;
    }
    
    function linkSlave($masterurl,$identifiant,$token,$url,$name){
        
        if (substr($masterurl,-1) == '/'){
            $masterurl = substr($masterurl,0,-1);
        }
        $huburl = $masterurl.'/local/coursehub/api.php';
        
        $params = array(
            'function' => 'registerSlave',
            'slavetoken' => $token,
            'slaveurl' => $url,
            'slaveidentifiant' => $identifiant,
            'slavename' => $name
        );
        
        $rawresp = $this->post($huburl,$params);
        
        $res = json_decode($rawresp);
        
        print_r($res);
        
        if ($res->error == false){
            
            set_config('mod', CourseHub::CONF_MOD_SLAVE, CourseHub::PLUGIN_FULLNAME);
            set_config('type', CourseHub::SLAVE_TYPE_REMOTE, CourseHub::PLUGIN_FULLNAME);
            set_config('identifiant', $identifiant, CourseHub::PLUGIN_FULLNAME);
            set_config('shortname', $identifiant, CourseHub::PLUGIN_FULLNAME);
            set_config('url', $url, CourseHub::PLUGIN_FULLNAME);
            set_config('name', $name, CourseHub::PLUGIN_FULLNAME);
            set_config('token', $token, CourseHub::PLUGIN_FULLNAME);
            set_config('mastertoken', $res->mastertoken, CourseHub::PLUGIN_FULLNAME);
            set_config('master_url', $masterurl, CourseHub::PLUGIN_FULLNAME);
            set_config('canpublish', 1, CourseHub::PLUGIN_FULLNAME);
            set_config('canshare', 1, CourseHub::PLUGIN_FULLNAME);
            set_config('candelete', 1, CourseHub::PLUGIN_FULLNAME);
            
        }else{
            echo 'ERROR : '.$res->msg;
        }
        
    }
}




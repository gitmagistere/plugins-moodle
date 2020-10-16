<?php

require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');

class CourseHubApi
{
    
    private function __construct(){}
    
    // TOOLS
    static function formatReponse($array){
        return json_encode($array);
    }
    
    static function sendReponse($array){
        die(self::formatReponse($array));
    }
    
    // AUTH
    static function checkToken(){
        $slavetoken = optional_param('slavetoken', null, PARAM_ALPHANUM);
        $slaveIdentifiant = optional_param('slaveidentifiant', null, PARAM_ALPHANUMEXT);
        
        $hub = CourseHub::instance();
        if (!$hub->isMaster()){
            return array('error'=>true,'msg'=>'NOT_A_MASTER');
        }
        
        $slave = $hub->getSlave($slaveIdentifiant);
        
        if($slave === false){
            return array('error'=>true,'msg'=>'SLAVE_NOT_FOUND');
        }
        
        if (!$slave->isRemoteSlave()){
            return array('error'=>true,'msg'=>'NOT_A_REMOTE_SLAVE');
        }
        
        if (!$slave->isTrusted()){
            return array('error'=>true,'msg'=>'SLAVE_NOT_TRUSTED');
        }
        
        if ($slave->getToken() != $slavetoken){
            return array('error'=>true,'msg'=>'TOKEN_MISSMATCH');
        }
        
        return true;
    }
    
    
    //MASTER
    
    static function registerSlave(){
        $slavetoken = optional_param('slavetoken', null, PARAM_ALPHANUM);
        $slaveurl = optional_param('slaveurl', null, PARAM_URL);
        $slaveidentifiant = optional_param('slaveidentifiant', null, PARAM_ALPHANUMEXT);
        $slavename = optional_param('slavename', null, PARAM_TEXT);
        
        if($slavetoken===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVETOKEN')); }
        if($slaveurl===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVEURL')); }
        if($slaveidentifiant===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVEID')); }
        if($slavename===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVENAME')); }
        
        $hub = CourseHub::instance();
        if (!$hub->isMaster()){
            return array('error'=>true,'msg'=>'NOT_A_MASTER');
        }
        
        $error = $hub->addSlave(CourseHub::SLAVE_TYPE_REMOTE, $slaveidentifiant, $slavetoken, $slavename, $slaveurl);
        
        if ($error < 0){
            switch ($error){
                case CourseHub::ERROR_SLAVE_ALREADY_LINKED:
                    self::sendReponse(array('error'=>true,'msg'=>'ERROR_SLAVE_ALREADY_LINKED'));
                  break;
                case CourseHub::ERROR_SLAVE_NOT_REGISTERED:
                    self::sendReponse(array('error'=>true,'msg'=>'ERROR_SLAVE_NOT_REGISTERED'));
                  break;
                case CourseHub::ERROR_SLAVE_TOKEN_MISMATCH:
                    self::sendReponse(array('error'=>true,'msg'=>'ERROR_SLAVE_TOKEN_MISMATCH'));
                  break;
                default:
                    self::sendReponse(array('error'=>true,'msg'=>'UNKNOWN_ERROR'));
            }
        }
        
        $slave = $hub->getSlave($slaveidentifiant);
        
        self::sendReponse(array(
            'error'=>false,
            'mastertoken'=>$slave->getMasterToken()
        ));
        
    }
    
    static function publishCourse(){
        global $DB;
        
        $slavetoken = optional_param('slavetoken', null, PARAM_ALPHANUM);
        $slaveidentifiant = optional_param('slaveidentifiant', null, PARAM_ALPHANUMEXT);
        $courseid = optional_param('courseid', null, PARAM_INT);
        $name = optional_param('name', null, PARAM_TEXT);
        $shortname = optional_param('shortname', null, PARAM_TEXT);
        $summary = optional_param('summary', null, PARAM_TEXT);
        $courseurl = optional_param('courseurl', null, PARAM_URL);
        $coursestartdate = optional_param('coursestartdate', null, PARAM_INT);
        $courseenddate = optional_param('courseenddate', null, PARAM_INT);
        $username = optional_param('username', null, PARAM_USERNAME);
        $firstname = optional_param('firstname', null, PARAM_TEXT);
        $lastname = optional_param('lastname', null, PARAM_TEXT);
        $email = optional_param('email', null, PARAM_EMAIL);
        
        $enrolmethod = optional_param('enrolmethod', null, PARAM_ALPHA);
        $enrolstartdate = optional_param('enrolstartdate', null, PARAM_TIMEZONE);
        $enrolenddate = optional_param('enrolenddate', null, PARAM_TIMEZONE);
        $enrolrole = optional_param('enrolrole', null, PARAM_ALPHA);
        $enrolmaxuser = optional_param('enrolmaxuser', null, PARAM_INT);
        $courseissession = optional_param('courseissession', null, PARAM_BOOL);
        
        if($slavetoken===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVETOKEN')); }
        if($slaveidentifiant===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVEID')); }
        if($courseid===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_ID')); }
        if($name===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_NAME')); }
        if($shortname===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_SHORTNAME')); }
        if($summary===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_SUMMARY')); }
        if($courseurl===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_URL')); }
        if($coursestartdate===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_START_DATE')); }
        if($courseenddate===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_END_DATE')); }
        if($username===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_USERNAME')); }
        if($firstname===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_FIRSTNAME')); }
        if($lastname===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_LASTNAME')); }
        if($email===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_EMAIL')); }
        
        if($enrolmethod===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_ENROL_METHOD')); }
        if($enrolstartdate===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_ENROL_STARTDATE')); }
        if($enrolenddate===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_ENROL_ENDDATE')); }
        if($enrolrole===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_ENROL_ROLE')); }
        if($enrolmaxuser===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_ENROL_MAXUSER')); }
        if($courseissession===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_ISSESSION')); }
        
        
        $hub = CourseHub::instance();
        if (!$hub->isMaster()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_MASTER'));
        }
        
        $slave = $hub->getSlave($slaveidentifiant);
        
        if (!$slave->isRemoteSlave()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_VALID_SLAVE'));
        }
        
        if (!$slave->isTrusted()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_KNOWN_SLAVE'));
        }
        
        if(!$slave->canPublish()) {
            self::sendReponse(array('error'=>true,'msg'=>'NOT_ALLOWED_TO_PUBLISH'));
        }
        
        // Create coursehub record with deleted flag or update existing one
        $newCourseHub = new stdClass();
        $newCourseHub->slaveid = $slave->getId();
        $newCourseHub->courseid = $courseid;
        $newCourseHub->deleted = 0;
        $newCourseHub->publish = CourseHub::PUBLISH_PUBLISHED;
        $newCourseHub->name = $name;
        $newCourseHub->shortname = $shortname;
        $newCourseHub->summary = $summary;
        $newCourseHub->courseurl = $courseurl;
        $newCourseHub->coursestartdate = $coursestartdate;
        $newCourseHub->courseenddate = $courseenddate;
        $newCourseHub->username = $username;
        $newCourseHub->firstname = $firstname;
        $newCourseHub->lastname = $lastname;
        $newCourseHub->email = $email;
        $newCourseHub->timecoursemodified = time();
        $newCourseHub->timecreated = time();
        $newCourseHub->timemodified = time();
        
        $newCourseHub->inscription_method = $enrolmethod;
        $newCourseHub->enrolstartdate = $enrolstartdate;
        $newCourseHub->enrolenddate = $enrolenddate;
        $newCourseHub->enrolrole = $enrolrole;
        $newCourseHub->maxparticipant = $enrolmaxuser;
        $newCourseHub->isasession = $courseissession;
        $newCourseHub->isalocalsession = 0;
        
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
        
        
        self::sendReponse(array('error'=>false,'msg'=>'PUBLISH_SUCCESS'));
    }
    
    static function shareCourse(){
        global $DB;
        
        $slavetoken = optional_param('slavetoken', null, PARAM_ALPHANUM);
        $slaveidentifiant = optional_param('slaveidentifiant', null, PARAM_ALPHANUMEXT);
        $courseid = optional_param('courseid', null, PARAM_INT);
        $name = optional_param('name', null, PARAM_TEXT);
        $shortname = optional_param('shortname', null, PARAM_TEXT);
        $summary = optional_param('summary', null, PARAM_TEXT);
        $courseurl = optional_param('courseurl', null, PARAM_URL);
        $coursestartdate = optional_param('coursestartdate', null, PARAM_INT);
        $courseenddate = optional_param('courseenddate', null, PARAM_INT);
        $username = optional_param('username', null, PARAM_USERNAME);
        $firstname = optional_param('firstname', null, PARAM_TEXT);
        $lastname = optional_param('lastname', null, PARAM_TEXT);
        $email = optional_param('email', null, PARAM_EMAIL);
        
        $enrolmethod = optional_param('enrolmethod', null, PARAM_EMAIL);
        $enrolstartdate = optional_param('enrolstartdate', null, PARAM_EMAIL);
        $enrolenddate = optional_param('enrolenddate', null, PARAM_EMAIL);
        $enrolrole = optional_param('enrolrole', null, PARAM_EMAIL);
        $enrolmaxuser = optional_param('enrolmaxuser', null, PARAM_EMAIL);
        $courseissession = optional_param('courseissession', null, PARAM_BOOL);
        
        $backupfile = optional_param('backupfile', null, PARAM_FILE);
        
        
        if($slavetoken===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVETOKEN')); }
        if($slaveidentifiant===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVEID')); }
        if($courseid===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_ID')); }
        if($name===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_NAME')); }
        if($shortname===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_SHORTNAME')); }
        if($summary===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_SUMMARY')); }
        if($courseurl===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_URL')); }
        if($coursestartdate===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_START_DATE')); }
        if($courseenddate===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_END_DATE')); }
        if($username===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_USERNAME')); }
        if($firstname===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_FIRSTNAME')); }
        if($lastname===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_LASTNAME')); }
        if($email===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_EMAIL')); }
        
        if($enrolmethod===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_ENROL_METHOD')); }
        if($enrolstartdate===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_ENROL_STARTDATE')); }
        if($enrolenddate===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_ENROL_ENDDATE')); }
        if($enrolrole===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_ENROL_ROLE')); }
        if($enrolmaxuser===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_ENROL_MAXUSER')); }
        if($courseissession===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_ISSESSION')); }
        
        if (!isset($_FILES['backupfile'])){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_BACKUP_FILE')); }
        if (filesize($_FILES['backupfile']['tmp_name'])<1){ self::sendReponse(array('error'=>true,'msg'=>'EMPTY_BACKUP_FILE')); }
        
        $hub = CourseHub::instance();
        if (!$hub->isMaster()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_MASTER'));
        }
        
        $slave = $hub->getSlave($slaveidentifiant);
        
        if (!$slave->isRemoteSlave()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_VALID_SLAVE'));
        }
        
        if (!$slave->isTrusted()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_KNOWN_SLAVE'));
        }
        
        if(!$slave->canShare()) {
            self::sendReponse(array('error'=>true,'msg'=>'NOT_ALLOWED_TO_SHARE'));
        }
        
        
        echo '####FILES='.print_r($_FILES,true)."####";
        
        
        
        
        // Create coursehub record with deleted flag or update existing one
        $newCourseHub = new stdClass();
        $newCourseHub->slaveid = $slave->getId();
        $newCourseHub->courseid = $courseid;
        $newCourseHub->deleted = 1;
        $newCourseHub->publish = CourseHub::PUBLISH_SHARED;
        $newCourseHub->name = $name;
        $newCourseHub->shortname = $shortname;
        $newCourseHub->summary = $summary;
        $newCourseHub->courseurl = $courseurl;
        $newCourseHub->coursestartdate = $coursestartdate;
        $newCourseHub->courseenddate = $courseenddate;
        $newCourseHub->username = $username;
        $newCourseHub->firstname = $firstname;
        $newCourseHub->lastname = $lastname;
        $newCourseHub->email = $email;
        $newCourseHub->timecoursemodified = time();
        $newCourseHub->timecreated = time();
        $newCourseHub->timemodified = time();
        
        $newCourseHub->inscription_method = $enrolmethod;
        $newCourseHub->enrolstartdate = $enrolstartdate;
        $newCourseHub->enrolenddate = $enrolenddate;
        $newCourseHub->enrolrole = $enrolrole;
        $newCourseHub->maxparticipant = $enrolmaxuser;
        $newCourseHub->isasession = $courseissession;
        $newCourseHub->isalocalsession = 0;
        
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
        $publishFolder = $hub->getShareFolderpath($publishid);
        $publishFilename = $hub->getShareFilename($publishid);
        $publishFilepath = $publishFolder.'/'.$publishFilename;
        $ls='';
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
        
        if (!move_uploaded_file($_FILES['backupfile']['tmp_name'],$publishFilepath)){
            self::sendReponse(array('error'=>true,'msg'=>'SHARE_SUCCESS'));
        }
        
        $undeleteCourseHub = new stdClass();
        $undeleteCourseHub->id = $publishid;
        $undeleteCourseHub->deleted = 0;
        $DB->update_record(CourseHub::TABLE_COURSE,$undeleteCourseHub);
        
        if ($publishid > 0){
            self::sendReponse(array('error'=>false,'msg'=>'SHARE_SUCCESS'));
        }else{
            self::sendReponse(array('error'=>true,'msg'=>'SHARE_FAILED'));
        }
    }
    
    static function unpublishCourse(){
        global $DB;
        
        $slavetoken = optional_param('slavetoken', null, PARAM_ALPHANUM);
        $slaveidentifiant = optional_param('slaveidentifiant', null, PARAM_ALPHANUMEXT);
        $courseid = optional_param('courseid', null, PARAM_INT);
        $publishmod = optional_param('publishmod', null, PARAM_INT);
        
        if($slavetoken===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVETOKEN')); }
        if($slaveidentifiant===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVEID')); }
        if($courseid===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_ID')); }
        if($publishmod===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSE_ID')); }
        
        
        if ($publishmod != CourseHub::PUBLISH_PUBLISHED && $publishmod != CourseHub::PUBLISH_SHARED){
            self::sendReponse(array('error'=>true,'msg'=>'INVALID_PUBLISHMOD'));
        }
        
        $hub = CourseHub::instance();
        if (!$hub->isMaster()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_MASTER'));
        }
        
        $slave = $hub->getSlave($slaveidentifiant);
        
        if (!$slave->isRemoteSlave()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_VALID_SLAVE'));
        }
        
        if (!$slave->isTrusted()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_KNOWN_SLAVE'));
        }
        
        if(!$slave->canDelete()) {
            self::sendReponse(array('error'=>true,'msg'=>'NOT_ALLOWED_TO_PUBLISH'));
        }
        
        $publish = $hub->getPublishedCourse($slave->getId(), $courseid, $publishmod);
        if ($publish === false){
            self::sendReponse(array('error'=>true,'msg'=>'COURSE_NOT_FOUND'));
        }
        
        if ($publishmod == CourseHub::PUBLISH_SHARED) {
            $publishFolder = $hub->getShareFolderpath($publish->id);
            $publishFilename = $hub->getShareFilename($publish->id);
            $file = $publishFolder.'/'.$publishFilename;
            
            if (file_exists($file)) {
                unlink($file);
            }
            if (file_exists($file.'.old')) {
                unlink($file.'.old');
            }
        }
        
        $deletedPublish = new stdClass();
        $deletedPublish->id = $publish->id;
        $deletedPublish->deleted = 1;
        $DB->update_record(CourseHub::TABLE_COURSE,$deletedPublish);

        self::sendReponse(array('error'=>false,'msg'=>'COURSE_UNPUBLISHED'));
    }
    
    static function getSlaveConfig(){
        $slavetoken = optional_param('slavetoken', null, PARAM_ALPHANUM);
        $slaveidentifiant = optional_param('slaveidentifiant', null, PARAM_ALPHANUMEXT);
        
        if($slavetoken===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVETOKEN')); }
        if($slaveidentifiant===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVEID')); }
        
        $hub = CourseHub::instance();
        if (!$hub->isMaster()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_MASTER'));
        }
        
        $slave = $hub->getSlave($slaveidentifiant);
        
        if (!$slave->isRemoteSlave()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_VALID_SLAVE'));
        }
        
        if (!$slave->isTrusted()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_KNOWN_SLAVE'));
        }
        
        self::sendReponse(array(
            'error'=>false,
            'canpublish'=>($slave->canPublish()==1?true:false),
            'canshare'=>($slave->canShare()==1?true:false),
            'candelete'=>($slave->canDelete()==1?true:false)
        ));
    }
    
    static function getPublishedCourse(){
        $slaveidentifiant = optional_param('slaveidentifiant', null, PARAM_ALPHANUMEXT);
        $courseid = optional_param('courseid', null, PARAM_INT);
        $publishmod = optional_param('publishmod', null, PARAM_BOOL);
        
        if($slaveidentifiant===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVEID')); }
        if($courseid===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_COURSEID')); }
        if($publishmod===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_PUBLISHMOD')); }
        
        $hub = CourseHub::instance();
        if (!$hub->isMaster()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_MASTER'));
        }
        
        $slave = $hub->getSlave($slaveidentifiant);
        
        if (!$slave->isRemoteSlave()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_VALID_SLAVE'));
        }
        
        if (!$slave->isTrusted()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_KNOWN_SLAVE'));
        }
        
        $publish = $hub->getPublishedCourse($slave->getId(), $courseid, $publishmod);
        
        
        self::sendReponse(array(
            'error'=>false,
            'course'=>$publish
        ));
    }
    
    static function getPublishedCourseById(){
        $slaveidentifiant = optional_param('slaveidentifiant', null, PARAM_ALPHANUMEXT);
        $id = optional_param('id', null, PARAM_INT);
        
        if($slaveidentifiant===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SLAVEID')); }
        if($id===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_ID')); }
        
        $hub = CourseHub::instance();
        if (!$hub->isMaster()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_MASTER'));
        }
        
        $slave = $hub->getSlave($slaveidentifiant);
        
        if (!$slave->isRemoteSlave()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_VALID_SLAVE'));
        }
        
        if (!$slave->isTrusted()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_KNOWN_SLAVE'));
        }
        
        $publish = $hub->getPublishedCourseById($id);
        
        
        self::sendReponse(array(
            'error'=>false,
            'course'=>$publish
        ));
    }
    
    
    
    
    //SLAVE
    static function checkMasterToken(){
        $mastertoken = optional_param('mastertoken', null, PARAM_ALPHANUM);
        $slaveIdentifiant = optional_param('slaveidentifiant', null, PARAM_ALPHANUMEXT);
        
        $hub = CourseHub::instance();
        if (!$hub->isRemoteSlave()){
            return array('error'=>true,'msg'=>'NOT_A_REMOTE_SLAVE');
        }
        
        
        if ($hub->getIdentifiant() != $slaveIdentifiant){
            return array('error'=>true,'msg'=>'ID_MISSMATCH');
        }
        
        if ($hub->getMasterToken() != $mastertoken){
            return array('error'=>true,'msg'=>'TOKEN_MISSMATCH');
        }
        
        return true;
    }
    
    
    static function setPermission(){
        $mastertoken = optional_param('mastertoken', null, PARAM_ALPHANUM);
        $canpublish = optional_param('canpublish', null, PARAM_BOOL);
        $canshare = optional_param('canshare', null, PARAM_BOOL);
        $candelete = optional_param('candelete', null, PARAM_BOOL);
        
        if($mastertoken===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_TOKEN')); }
        if($canpublish===null && $canshare===null && $candelete===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_PERMISSION')); }
        
        $hub = CourseHub::instance();
        if (!$hub->isRemoteSlave()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_REMOTE_SLAVE'));
        }
        
        if($canpublish!==null){
            if ($canpublish != CourseHub::PERMISSION_ALLOWED && $canpublish != CourseHub::PERMISSION_DENIED){
                self::sendReponse(array('error'=>true,'msg'=>'PUBLISH_BAD_VALUE'));
            }
            $hub->setCanPublish($canpublish);
        }
        
        if($canshare!==null){
            if ($canshare != CourseHub::PERMISSION_ALLOWED && $canshare != CourseHub::PERMISSION_DENIED){
                self::sendReponse(array('error'=>true,'msg'=>'SHARE_BAD_VALUE'));
            }
            $hub->setCanShare($canshare);
        }
        
        if($candelete!==null){
            if ($candelete != CourseHub::PERMISSION_ALLOWED && $candelete != CourseHub::PERMISSION_DENIED){
                self::sendReponse(array('error'=>true,'msg'=>'DELETE_BAD_VALUE'));
            }
            $hub->setCanDelete($candelete);
        }
        
        self::sendReponse(array('error'=>false,'msg'=>'PERMISSION_SET'));
    }
    
    static function removeSlave(){
        $mastertoken = optional_param('mastertoken', null, PARAM_ALPHANUM);
        
        if($mastertoken===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_TOKEN')); }
        
        $hub = CourseHub::instance();
        if (!$hub->isRemoteSlave()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_REMOTE_SLAVE'));
        }
        
        $hub->deleteinstance();
                
        self::sendReponse(array('error'=>false,'msg'=>'SLAVE_DELETED'));
    }
    
    
    // OFFER
    static function searchPublishedCourses(){
        $search = optional_param('search', null, PARAM_TEXT);
        $publishmod = optional_param('publishmod', null, PARAM_BOOL);
        
        if($search===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_SEARCH')); }
        if($publishmod===null){ self::sendReponse(array('error'=>true,'msg'=>'MISSING_PUBLISHMOD')); }
        
        $hub = CourseHub::instance();
        if (!$hub->isMaster()){
            self::sendReponse(array('error'=>true,'msg'=>'NOT_A_MASTER'));
        }
        
        $courses = $hub->searchPublishedCourse($search,$publishmod);
        
        self::sendReponse(array('error'=>false,'courses'=>$courses));
        
    }
    
}
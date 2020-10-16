<?php
namespace local_coursehub\task;

class coursehub_share_task extends \core\task\scheduled_task
{      
    public function get_name() 
    {
        // Shown in admin screens
        return "CourseHub Sharing Task";
    }
                                                                     
    public function execute() 
    {
    	global $CFG, $DB;
    	require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');
    	
    	$coursehub = \CourseHub::instance(\CourseHub::LOGSMOD_ECHO);
    	
    	$tasks = $DB->get_records(\CourseHub::TABLE_TASKS,array('status'=>\CourseHub::TASK_STATUS_TODO), 'id DESC');
    	
    	echo 'Found '.count($tasks)." tasks to process\n";
    	
    	foreach ($tasks AS $task) {
    	    echo 'PROCESSING TASK '.$task->id.'  ==>> '.print_r($task,true)."\n";
    	    try {
        	    $task->status = \CourseHub::TASK_STATUS_INPROGRESS;
        	    $DB->update_record(\CourseHub::TABLE_TASKS, $task);
        	    echo 'Task '.$task->id." status set to INPROGRESS\n";
        	    if ( $coursehub->shareCourse($task->courseid,$task->userid) ) {
        	        $task->status = \CourseHub::TASK_STATUS_COMPLETED;
        	        $DB->update_record(\CourseHub::TABLE_TASKS, $task);
        	        echo 'Task '.$task->id." status set to COMPLETED\n";
        	    }else{
        	        $task->status = \CourseHub::TASK_STATUS_FAILED;
        	        $DB->update_record(\CourseHub::TABLE_TASKS, $task);
        	        echo 'Task '.$task->id." status set to FAILED\n";
        	    }
    	    }catch(\Exception $e){
    	        echo "Exception catched : ".print_r($e,true)."\n\n";
    	        $task->status = \CourseHub::TASK_STATUS_FAILED;
    	        $DB->update_record(\CourseHub::TABLE_TASKS, $task);
    	        echo 'Task '.$task->id." status set to FAILED\n";
    	    }
    	}
    }                                                                                                                               
} 
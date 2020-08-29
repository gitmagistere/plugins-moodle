<?php

namespace local_workflow\task;

/**
 * Class workflow_notification_task permettant l'execution de la class workflow_cron qui gère les envoi de notification.
 * @package local_workflow\task
 */
class workflow_notification_task extends \core\task\scheduled_task {

    /**
     * Fonction obligatoire qui retourne le nom de la tache plannifiée.
     * @return string
     */
    public function get_name(){
        return 'Workflow Notification Task';
    }

    /**
     * Fonction obligatoire qui est appelé lorsque la tache plannifiée est enclenchée.
     */
    public function execute(){
        global $CFG;
        require_once($CFG->dirroot.'/local/workflow/lib.php');
        workflow_cron();
    }
}
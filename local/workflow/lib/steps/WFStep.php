<?php

require_once($CFG->dirroot.'/local/workflow/lib/steps/WFStepGabarit.php');
require_once($CFG->dirroot.'/local/workflow/lib/steps/WFStepParcours.php');
require_once($CFG->dirroot.'/local/workflow/lib/steps/WFStepSessionPreparation.php');
require_once($CFG->dirroot.'/local/workflow/lib/steps/WKStepSessionCours.php');
require_once($CFG->dirroot.'/local/workflow/lib/steps/WFStepSessionArchive.php');

/**
 * Class WFStep qui liste les methodes obligatoire pour chaque état du workflow
 */
abstract class WFStep {
    protected $courseid;

    /**
     * Fonction qui détermine quelle class utilisée à l'état suivant et créée une instance de celle-ci.
     * @return mixed
     */
    abstract public function getNextStep();

    /**
     * Fonction qui détermine les conditions nécessaires pour passer à l'état suivant.
     * @return mixed
     */
    abstract public function isValidateState();

    /**
     * Fonction qui permet de déterminer si le formulaire du workflow a été validé.
     * @return mixed
     */
    abstract public function isSubmittedForm();

    /**
     * Fonction qui traite les données validées en post par le formulaire de workflow.
     * @return mixed
     */
    abstract public function processForm();

    /**
     * Fonction qui détermine quel type de message à afficher à la suite du traitement du formulaire de workflow.
     * @return mixed
     */
    abstract public function getNotification();

    /**
     * Fonction qui retourne le nom de l'état du workflow sous forme de texte.
     * @return mixed
     */
    abstract public function getStepName();

    /**
     * Fonction qui détermine quelles sont les notifications badges à afficher sur le workflow selon les données existantes.
     * @return mixed
     */
    abstract public function getNotificationBadges();

    /**
     * Fonction génère les notification badges au format HTML.
     * @return mixed
     */
    abstract public function getNotificationBadgesHTML();

    /**
     * Fonction qui détermine quelles sont les notifications badges à afficher dans le formulaire d'indexation du parcours
     * selon les données existantes.
     * @return mixed
     */
    abstract public function getIndexationNotificationBadges();

    /**
     * Fonction génère les notification badges au format HTML.
     * @return mixed
     */
    abstract public function getIndexationNotificationBadgesHTML();

    /**
     * Fonction génère la bannière du workflow selon l'état dans lequel se situe le parcours au format HTML.
     * @return mixed
     */
    abstract protected function getHeader();

    /**
     * Fonction génère le formulaire de workflow au format HTML.
     * @return mixed
     */
    abstract protected function getForm();


    /**
     * Fonction qui permet l'affichage du contenu du workflow selon l'état dans lequel le parcours se situe.
     * @return string
     */
    public function showWorkflow(){
        return $this->getHeader() . $this->getForm();
    }
}
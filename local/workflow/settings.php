<?php

/**
 * Workflow settings. 
 * Systeme d'optimisation avec configuration d'une taille minimum des fichiers de ressources centralisees.
 *
 * @package local_workflow
 * @copyright  2020 TCS
 */

if (has_capability('local/workflow:optimizeconfig',context_system::instance()) || has_capability('local/workflow:optimizeconfig',context_system::instance())){
    $ADMIN->add('localplugins', new admin_category('local_workflow_settings',
    get_string('local_workflow_optimizer_settings_label', 'local_workflow')));
}

require_once($CFG->dirroot.'/local/workflow/lib.php');
if (has_capability('local/workflow:optimizeconfig',context_system::instance()) && isOptimizerAvailable()){
    
    $settings = new admin_settingpage('local_workflow_optimizer', get_string('local_workflow_optimizer_settings_config', 'local_workflow'));
    
    $settings->add(new admin_setting_heading('local_workflow_optimizer', '',
        get_string('local_workflow_optimizer_settings_head', 'local_workflow')));
    
    $settings->add(new admin_setting_configtext('local_magisterelib/centralizeMinSize', get_string('local_workflow_optimizer_settings_centralizeminsize_label', 'local_workflow'),
        get_string('local_workflow_optimizer_settings_centralizeminsize_description', 'local_workflow'), 5242880, PARAM_INT));
    
    
    $ADMIN->add('local_workflow_settings', $settings);
}

if (has_capability('local/workflow:config',context_system::instance())){
    
    $settings = new admin_settingpage('local_workflow_settings_config', get_string('local_workflow_settings_config', 'local_workflow'));
    
    $roles = $DB->get_records('role', null,'sortorder ASC','id,name,shortname');
    $select_roles = array();
    foreach ($roles AS $role){
        $select_roles[$role->id] = $role->name.' ('.$role->shortname.')';
    }
    $particiants = $formateurs = $tuteurs = $concepteurs = $select_roles;
    
    if (get_config('local_workflow','role_participant') == false){
        $particiants = array('0'=>'Non défini!') + $particiants;
    }
    if (get_config('local_workflow','role_tuteur') == false){
        $tuteurs = array('0'=>'Non défini!') + $tuteurs;
    }
    if (get_config('local_workflow','role_formateur') == false){
        $formateurs = array('0'=>'Non défini!') + $formateurs;
    }
    if (get_config('local_workflow','role_concepteur') == false){
        $concepteurs = array('0'=>'Non défini!') + $concepteurs;
    }
    
    $settings->add(new admin_setting_heading('local_workflow_settings_enrol_head', '', get_string('local_workflow_settings_enrol_head', 'local_workflow')));
    $settings->add(new admin_setting_configselect('local_workflow/role_participant', get_string('settings_role_enrol_participant_label','local_workflow'), get_string('settings_role_enrol_participant_description','local_workflow'), '', $particiants));
    $settings->add(new admin_setting_configselect('local_workflow/role_tuteur', get_string('settings_role_enrol_tuteur_label','local_workflow'), get_string('settings_role_enrol_tuteur_description','local_workflow'), '', $tuteurs));
    $settings->add(new admin_setting_configselect('local_workflow/role_formateur', get_string('settings_role_enrol_formateur_label','local_workflow'), get_string('settings_role_enrol_formateur_description','local_workflow'), '', $formateurs));
    $settings->add(new admin_setting_configselect('local_workflow/role_concepteur', get_string('settings_role_enrol_concepteur_label','local_workflow'), get_string('settings_role_enrol_concepteur_description','local_workflow'), '', $concepteurs));
    
    $settings->add(new admin_setting_heading('local_workflow_settings_module_head', '', get_string('local_workflow_settings_module_head', 'local_workflow')));
    $settings->add(new admin_setting_configcheckbox('local_workflow/enable_gaia', get_string('settings_module_gaia_label','local_workflow'), get_string('settings_module_gaia_description','local_workflow'), '0'));
    $settings->add(new admin_setting_configcheckbox('local_workflow/enable_indexation', get_string('settings_module_indexation_label','local_workflow'), get_string('settings_module_indexation_description','local_workflow'), '0'));
    $settings->add(new admin_setting_configcheckbox('local_workflow/enable_coursehub', get_string('settings_module_coursehub_label','local_workflow'), get_string('settings_module_coursehub_description','local_workflow'), '0'));
    $settings->add(new admin_setting_configcheckbox('local_workflow/enable_optimizer', get_string('settings_module_optimizer_label','local_workflow'), get_string('settings_module_optimizer_description','local_workflow'), '0'));
    
    
    $ADMIN->add('local_workflow_settings', $settings);
}

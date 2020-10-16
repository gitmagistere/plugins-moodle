<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');

require_login();

function isMag(){
    return isset($GLOBALS['CFG']->magistere_domaine);
}

if ( !is_siteadmin() ) {
    print_error('Only the Site Admin can access this page!');
}

$hub = CourseHub::instance();

if ($hub->isMaster()) {
    showMasterPage($hub);
}else if ($hub->isSlave()) {
    if ($hub->getType() == CourseHub::SLAVE_TYPE_LOCAL) {
        showLocalSlavePage($hub);
    }elseif ($hub->getType() == CourseHub::SLAVE_TYPE_REMOTE) {
        showRemoteSlavePage($hub);
    }else{
        print_error('Error : Operation not supported');
    }
}else{
    showNoConfigPage($hub);
}


function showNoConfigPage($hub) {
    global $PAGE, $OUTPUT,$CFG;
    
    $context = context_system::instance();
    
    
    $PAGE->set_cacheable(false);
    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('ui');
    $PAGE->requires->jquery_plugin('ui-css');
    
    $PAGE->set_context($context);
    $PAGE->set_url('/local/coursehub/manage.php');
    $PAGE->set_pagetype('site-index');
    $PAGE->set_pagelayout('standard');
    
    $PAGE->set_title("Gestion du Master");
    $PAGE->set_heading("Gestion du Master");
    
    
    
    $becomeMasterSubmit = optional_param('becomeMasterSubmit', false, PARAM_ALPHA);
    
    if ( $becomeMasterSubmit != false) {
        if ($hub->setMod(CourseHub::CONF_MOD_MASTER)) {
            redirect($PAGE->url);
        }
    }
    
    $becomeMasterSubmit = optional_param('becomeSlaveSubmit', false, PARAM_ALPHA);
    
    if ( $becomeMasterSubmit != false) {
        
        $murl = required_param('murl', PARAM_URL);
        $token = required_param('token', PARAM_ALPHANUM);
        $identifiant = optional_param('identifiant', false, PARAM_ALPHANUMEXT);
        $name = optional_param('name', false, PARAM_TEXT);
        
        $url = $CFG->wwwroot;
        
        $hub->linkSlave($murl,$identifiant,$token,$url,$name);
        redirect($PAGE->url);
    }
    
    
    
    echo $OUTPUT->header();
    
    echo '<h3>Le hub n\'est pas encore configuré</h3>';
    echo 'Pour configurer cette plateforme en mode slave, vous devez l\'ajouter à partir d\'un Master';
    echo '<form action="" method="post">
    <input type="submit" name="becomeMasterSubmit" value="Devenir un Master" />
    </form><br/>';
    echo '<form action="" method="post">
    Master URL <input type="text" name="murl" /><br/>
    Identifiant <input type="text" name="identifiant" /><br/>
    Token <input type="text" name="token" /><br/>
    Nom <input type="text" name="name" /><br/>
    <input type="submit" name="becomeSlaveSubmit" value="Devenir un Slave" />
    </form>';
    
    echo $OUTPUT->footer();
}


function showLocalSlavePage($hub) {
    global $PAGE, $OUTPUT;
    
    $context = context_system::instance();
    
    $PAGE->set_cacheable(false);
    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('ui');
    $PAGE->requires->jquery_plugin('ui-css');
    
    $PAGE->set_context($context);
    $PAGE->set_url('/local/coursehub/manage.php');
    $PAGE->set_pagetype('site-index');
    $PAGE->set_pagelayout('standard');
    
    $PAGE->set_title("Gestion du Master");
    $PAGE->set_heading("Gestion du Master");
    
    
    echo $OUTPUT->header();
    
    echo '<h3>Le hub est configuré en mod Slave Local</h3>';
    echo 'Identifiant du Master : '.$hub->getMasterIdentifiant().'<br>';
    echo 'URL du Master : '.$hub->getMasterURL().'<br>';
    echo 'Peut publier : '.($hub->canPublish()?'Oui':'Non').'<br>';
    echo 'Peut partager : '.($hub->canShare()?'Oui':'Non').'<br>';
    echo 'Peut supprimer : '.($hub->canDelete()?'Oui':'Non').'<br>';
    
    echo $OUTPUT->footer();
}
    

function showRemoteSlavePage($hub) {
    global $PAGE, $OUTPUT;
    
    $context = context_system::instance();
    
    $PAGE->set_cacheable(false);
    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('ui');
    $PAGE->requires->jquery_plugin('ui-css');
    
    $PAGE->set_context($context);
    $PAGE->set_url('/local/coursehub/manage.php');
    $PAGE->set_pagetype('site-index');
    $PAGE->set_pagelayout('standard');
    
    $PAGE->set_title("Gestion du Master");
    $PAGE->set_heading("Gestion du Master");
    
    
    echo $OUTPUT->header();
    
    echo '<h3>Le hub est configuré en mod Slave Remote</h3>';
    echo 'URL du Master : '.$hub->getMasterURL().'<br>';
    echo 'Peut publier : '.($hub->canPublish()?'Oui':'Non').'<br>';
    echo 'Peut partager : '.($hub->canShare()?'Oui':'Non').'<br>';
    echo 'Peut supprimer : '.($hub->canDelete()?'Oui':'Non').'<br>';
    
    echo $OUTPUT->footer();
}



function showMasterPage($hub) {
    global $PAGE, $OUTPUT, $CFG, $DB;
    
    $context = context_system::instance();
    
    $PAGE->set_cacheable(false);
    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('ui');
    $PAGE->requires->jquery_plugin('ui-css');
    
    $PAGE->set_context($context);
    $PAGE->set_url('/local/coursehub/manage.php');
    $PAGE->set_pagetype('site-index');
    $PAGE->set_pagelayout('standard');
    
    $PAGE->set_title("Gestion du Master");
    $PAGE->set_heading("Gestion du Master");
    
    if (isMag()){
        $localslavesubmit = optional_param('localslavesubmit', false, PARAM_ALPHA);
        $localslavedeletesubmit = optional_param('localslavedeletesubmit', false, PARAM_ALPHA);
        
        if ( $localslavesubmit != false) {
            
            $slaveIdentifiant = optional_param('slaveIdentifiant', false, PARAM_ALPHANUMEXT);
            if ($slaveIdentifiant != false && array_key_exists($slaveIdentifiant, $hub->getAvailableLocalSlaveIdentifiant())) {
                $hub->addSlave(CourseHub::SLAVE_TYPE_LOCAL,$slaveIdentifiant);
                redirect($PAGE->url);
            }
        }else if ($localslavedeletesubmit != false) {
            $slaveid = optional_param('slaveid', false, PARAM_NUMBER);
            
            if ($slaveid != false) {
                $hub->deleteSlave($slaveid);
                redirect($PAGE->url);
            }
        }
    }
    
    
    $remoteslavesubmit = optional_param('remoteslavesubmit', false, PARAM_ALPHA);
    $remoteslavedeletesubmit = optional_param('remoteslavedeletesubmit', false, PARAM_ALPHA);
    
    if ( $remoteslavesubmit != false) {
        
        $slaveIdentifiant = optional_param('identifiant', false, PARAM_ALPHANUMEXT);
        
        if ( $hub->getSlave($slaveIdentifiant) !== false ){
            echo 'Error: slave already exist!';
        }else{
            $hub->declareSlave(CourseHub::SLAVE_TYPE_REMOTE,$slaveIdentifiant);
            redirect($PAGE->url);
        }
    }else if ($remoteslavedeletesubmit != false) {
        $slaveid = optional_param('slaveid', false, PARAM_NUMBER);
        
        if ($slaveid != false) {
            $hub->deleteSlave($slaveid);
            redirect($PAGE->url);
        }
    }
    
    
    echo $OUTPUT->header();
    
    echo '<h3>Le hub est configuré en mode Master</h3>';
    
    echo '<table border="1" cellpadding="0" cellspacing="0" style="width:100%" id="slaves">';
    
    echo '<tr><th>id</th><th>type</th><th>Linked</th><th>identifiant</th><th>URL</th><th>Name</th><th>MasterToken</th><th>SlaveToken</th><th>Publication</th><th>Partage</th><th>Suppression</th><th>Supprimé</th><th>Actions</th></tr>';
    
    foreach($hub->getSlaves() AS $slave) {
        
        echo '<tr id="slave_'.$slave->getId().'">
<td>'.$slave->getId().'</td>
<td>'.$slave->getType().'</td>
<td>'.($slave->isTrusted()?'Oui':'Non').'</td>
<td>'.$slave->getIdentifiant().'</td>
<td>'.$slave->getURL().'</td>
<td>'.$slave->getName().'</td>
<td>'.($slave->isRemoteSlave()?$slave->getMasterToken():'').'</td>
<td>'.$slave->getToken().'</td>
<td><select name="canpublish"><option value="0">Deny</option><option value="1"'.($slave->canPublish()?'selected="selected"':'').'>Allow</option></select></td>
<td><select name="canshare"><option value="0">Deny</option><option value="1"'.($slave->canShare()?'selected="selected"':'').'>Allow</option></select></td>
<td><select name="candelete"><option value="0">Deny</option><option value="1"'.($slave->canDelete()?'selected="selected"':'').'>Allow</option></select></td>
<td>'.($slave->isDeleted()?'Oui':'Non').'</td>
<td>
    <form action="" method="post">
    <input type="hidden" name="slaveid" value="'.$slave->getId().'" />
    <input type="submit" name="'.($slave->isRemoteSlave()?'remoteslavedeletesubmit':'localslavedeletesubmit').'" value="Supprimer" />
    </form>
    </td>
</tr>';
    }
    
    echo '</table>';
    
    $ajaxurl = new moodle_url('/local/coursehub/ajax.php');
    
    echo "<script>
$(function() {
$('#slaves select').change(function(){
  var slaveid = $(this).parent().parent().attr('id');
  var selectName = $(this).attr('name');
  var selectValue = $(this).children('option:selected').val();
  
  $.ajax({
        type:'POST',
        url: '".$ajaxurl."',
        data:'action=changepermission&slave='+slaveid.substr(6)+'&permission='+selectName+'&value='+selectValue,
        dataType:'json',
        success:function(response){
            
        },
        error:function(error){
            console.log(error);
        }
    });
});
});
</script>";
    
    if (isMag()){
        echo '<br><h3>Ajouter un nouveau Slave</h3>
    <div id="localslavediv">
    <form id="localslaveform" action="" method="post">
    
    <select name="slaveIdentifiant">';
    
        foreach ($hub->getAvailableLocalSlaveIdentifiant() AS $slave2) {
            echo '<option value="'.$slave2->name.'">'.$slave2->name.'</option>';
        }
    
        echo '</select>
    
    <input type="submit" name="localslavesubmit" value="Ajouter" />
    
    </form>
    </div>
    ';
        
    }else{
        echo '<br><h3>Déclarer un nouveau Slave</h3>
<div id="remoteslavediv">
    <form id="remoteslaveform" action="" method="post">
        Identifiant : <input type="text" name="identifiant" value="" />
        <input type="submit" name="remoteslavesubmit" value="Ajouter" />
    </form>
</div>';
    }
    
    echo $OUTPUT->footer();
}

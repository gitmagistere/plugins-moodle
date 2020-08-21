Format Modulaire
============================
Attention ! Le format modulaire a une dépendance direct avec le block Sommaire. Si le plugin du block Sommaire n'est pas présent sur le Moodle en question, des effets de bord peuvent apparaître.
Fonctionnalité du format :
* A la création d’un nouveau cours au format modulaire, celui-ci comportera : 1 page d’introduction, 2 modules vierges sans sous-section, 1 page de conclusion, 1 page formateur. 
* Les utilisateurs arrivent sur le parcours par la section1 et non pas sur la section 0. (c’est-à-dire que si aucune section n'est spécifiée, on redirige vers la section1.) 
* Il n'est pas possible de naviguer d'un module à l'autre mais La navigation est possible au sein d'un même module (cf. paramétrage du bloc) et au niveau des page extra module
* Le format accepte l'affichage en trois colonnes.
* Il est possible de restreindre un bloc à une section. (Nécessite des modifications dans le "noyau" de Moodle)
* Les activités et ressources sont re-nommable en cliquant sur le bouton représentant un crayon. 
* La fonction "activité furtive (stealth activity)" est opérationnelle sur le format modulaire 

Installation
============
1. Vérifiez que vous avez la bonne version de Moodle. Une autre version de moodle peut entraîner des comportements indésirables.
2. Passez Moodle en 'Maintenance Mode' (https://docs.moodle.org/35/en/Maintenance_mode)
3. Copiez le dossier 'modular' dans '/course/format/'.
4. [OPTIONNEL] Pour activer la fonctionnalité de restriction d'un bloc à une section, suivez les indications du chapitre suivant
5. Retirez le mode maintenance.

Restreindre un bloc à une section
==============
Cette fonctionnalité  nécessite d'ajouter du code dans les fichers sources de moodle. 
Vous n'avez pas besoin d'effectuer cette opération si elle a déjà été effectuée pour un autre format utilisant cette fonctionalité.
Les explications ci-dessous se font à partir d’une plateforme Moodle vierge (avec uniquement le block Sommaire installé)

Dans le fichier \lib\blocklib.php : 
1. Ligne 1746 : ajoutez le code ci-dessous : 
    ```php
    if(get_config($this->page->course->format,'canrestrictblocktosection')){
        $bi->sectionid = $data->onthissectionsectionid;
        $record = $DB->get_record('format_' . $this->page->course->format . '_bck',["blockinstanceid" => $block->instance->id]);
    
        if($record){
            $record->sectionid = $data->onthissectionsectionid;
            $DB->update_record('format_' . $this->page->course->format . '_bck',$record);
        }else{
            $record = new stdClass();
            $record->sectionid = $data->onthissectionsectionid;
            $record->blockinstanceid = $block->instance->id;
            $DB->insert_record('format_' . $this->page->course->format . '_bck',$record);
        }
    }
    ```
2. Ligne 753 : ajoutez le code ci-dessous : 
    ```php
       if(get_config($this->page->course->format,'canrestrictblocktosection')){
        if(isset(core_plugin_manager::instance()->get_installed_plugins('format')[$this->page->course->format])){
       
            $sectionid = optional_param('section', null, PARAM_INT);
            if($sectionid == null)$sectionid = 1;
       
            if ($sectionid > 0)
            {
                $ccjoin .= " LEFT JOIN {format_" . $this->page->course->format ."_bck} fb ON bi.id = fb.blockinstanceid";
                $section = $DB->get_record('course_sections', array('course'=>$this->page->course->id,'section'=>$sectionid));
       
                if ($section !== false)
                {
                    $visiblecheck .= "AND (fb.sectionid = '".$section->id."' OR fb.sectionid = 0 OR fb.sectionid IS NULL)";
                }else{
                    $visiblecheck .= "AND (fb.sectionid = '0' OR fb.sectionid IS NULL)";
                }
            }
        }
       }
    ```
Dans le fichier \blocks\edit_form.php : 

1. Ligne 226 : ajoutez le code ci-dessous : 
  ```php
    $courseid = optional_param("id", null, PARAM_INT);
    global $DB;
    if ($courseid !== null) {
    	$course = $DB->get_record('course', array('id' => $courseid));
    	
    	if(get_config($course->format,'canrestrictblocktosection')){
    		if (isset(core_plugin_manager::instance()->get_installed_plugins('format')[$course->format])) {
    			$coursedisplay = $DB->get_record('course_format_options', array('courseid'=>$courseid,'format'=>$course->format,'name'=>'coursedisplay'));
    
    			if ((isset($coursedisplay->value) && $coursedisplay->value == 1) || !isset($coursedisplay->value))
    			{
    
    				$mform->addElement('header', 'onthissection', get_string('onthissection', 'format_' . $course->format));
    
    				$sections = $DB->get_records('course_sections',array('course'=>$courseid));
    
    				$sectionoptions = array();
    				$sectionoptions[0] = 'Afficher sur toutes les sections';
    				foreach($sections AS $section)
    				{
    					if ($section->section == 0)
    					{
    						continue;
    					}
    					if (!empty($section->name))
    					{
    						$sectionoptions[$section->id] = substr($section->name,0,50);
    					}else{
    						$sectionoptions[$section->id] = 'Section '.$section->section;
    					}
    				}
    
    				$mform->addElement('select', 'onthissectionsectionid', get_string('sectionid', 'format_' . $course->format), $sectionoptions);
    				$record = $DB->get_record("format_" . $course->format ."_bck",["blockinstanceid" => $this->block->instance->id]);
    				if($record){
    					$mform->setDefault('onthissectionsectionid', $record->sectionid);
    				}else{
    					$mform->setDefault('onthissectionsectionid', 0);
    				}
    
    			}
    		}
    	}
    
    }
   ```
4. Cochez le paramètre de format modular/canrestrictblocktosection pour que la fonctionalité soit activée

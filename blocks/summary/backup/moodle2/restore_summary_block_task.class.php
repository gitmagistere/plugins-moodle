<?php
class restore_summary_block_task extends restore_block_task {
	
	/**
	 * Translates the backed up configuration data for the target course modules
	 *
	 * @global type $DB
	 */
	public function after_restore() {
		global $DB;
		//verifier la présence d'un fichier summary.xml
		
		$summary_xml = file_exists ( $this->taskbasepath.'/summary.xml');
		
		if($summary_xml){
			$summary_a = $this->summary_xml_to_array();
		}
		
		$oldcourseid = $this->get_old_courseid();
		
		//Restored course id
		$courseid = $this->get_courseid();


		if($DB->get_record('block_summary', array('courseid' => $courseid))){
		    // le bloc existe deja, on ne fait rien
		    // cas de la restoration avec fusion
		    return;
        }

		foreach ($summary_a as $values)
		{
			$new_section = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$values['section']));
			
			$pid = null;
			if ($values['parentsection'] != 0)
			{
				$new_parent_section = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$values['parentsection']));
				if ($new_parent_section !== false && $new_parent_section->id > 0)
				{
					$pid = $new_parent_section->id;
				}
			}

			$bs = new stdClass();
			$bs->courseid = $courseid;
			$bs->sectionid = $new_section->id;
			$bs->parentid = $pid;
			$bs->weight = $values['weight'];
			
			$DB->insert_record('block_summary', $bs);
		}
		
	}
	
	//transforme le contenu xml en un tableau
	public function summary_xml_to_array(){
		$summary_a = array();
		//récupération du contenu xml et boucle sur les éléments
		$doc_summary= new DOMDocument();
		$doc_summary->load($this->taskbasepath."/summary.xml");
		$elements = $doc_summary->getElementsByTagName( "elements" );
		
		foreach($elements as $ua) {
			$element = $ua->getElementsByTagName("element");
			
			foreach($element as $ua) {
				$key = $ua->getAttribute('id');
				
				$courseid_ua = $ua->getElementsByTagName("courseid");
				$courseid = $courseid_ua->item(0)->nodeValue;
				
				$sectionid_ua = $ua->getElementsByTagName("sectionid");
				$sectionid = $sectionid_ua->item(0)->nodeValue;
				
				$parentid_ua = $ua->getElementsByTagName("parentid");
				$parentid = $parentid_ua->item(0)->nodeValue;
				
				$weight_ua = $ua->getElementsByTagName("weight");
				$weight = $weight_ua->item(0)->nodeValue;
				
				$section_ua = $ua->getElementsByTagName("section");
				$section = $section_ua->item(0)->nodeValue;
				
				$parentsection_ua = $ua->getElementsByTagName("parentsection");
				$parentsection = $parentsection_ua->item(0)->nodeValue;
				
				$summary_l = array('courseid' =>  $courseid, 'sectionid' => $sectionid, 'parentid' => $parentid, 'weight' => $weight, 'section' => $section, 'parentsection' => $parentsection);
				
				$summary_a[$key] = $summary_l;
			}
		}
		return $summary_a;
	}
	
	/**
	 * There are no unusual settings for this restore
	 */
	protected function define_my_settings() {
	}
	
	/**
	 * There are no unusual steps for this restore
	 */
	protected function define_my_steps() {
	}
	
	/**
	 * There are no files associated with this block
	 *
	 * @return array An empty array
	 */
	public function get_fileareas() {
		return array();
	}
	
	/**
	 * There are no specially encoded attributes
	 *
	 * @return array An empty array
	 */
	public function get_configdata_encoded_attributes() {
		return array();
	}
	
	/**
	 * There is no coded content in the backup
	 *
	 * @return array An empty array
	 */
	static public function define_decode_contents() {
		return array();
	}
	
	/**
	 * There are no coded links in the backup
	 *
	 * @return array An empty array
	 */
	static public function define_decode_rules() {
		return array();
	}
}

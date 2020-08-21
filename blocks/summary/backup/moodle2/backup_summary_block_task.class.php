<?php

class backup_summary_block_task extends backup_block_task {
	
	protected $summary_path;
	/**
	 * override récupération locale de $basepath
	 * Block tasks have their own directory to write files
	 */
	public function get_taskbasepath() {
		$basepath = $this->get_basepath();
		
		// Module blocks are under module dir
		if (!empty($this->moduleid)) {
			$basepath .= '/activities/' . $this->modulename . '_' . $this->moduleid .
			'/blocks/' . $this->blockname . '_' . $this->blockid;
			
			// Course blocks are under course dir
		} else {
			$basepath .= '/course/blocks/' . $this->blockname . '_' . $this->blockid;
		}
		$this->summary_path= $basepath;
		return $basepath;
	}
	
	protected function define_my_settings() {
	}
	
	protected function define_my_steps() {
	}
	
	public function get_fileareas() {
		if($this->summary_path!=''){
			$this->summary_file();
		}
		return array();
	}
	
	public function get_configdata_encoded_attributes() {
		return array();
	}
	
	static public function encode_content_links($content) {
		return $content;
	}
	
	/*
	 *	création du contenu xml correspondant aux contenu monitorés
	 */
	protected function summary_file_content(){
		global $DB;
		
		$courseid = $this->get_courseid();
		
		$summary = $DB->get_records_sql('SELECT bs.*, cs.section, cs2.section AS parentsection FROM {block_summary} bs 
INNER JOIN {course_sections} cs ON (cs.id=bs.sectionid) 
LEFT JOIN {course_sections} cs2 ON (cs2.id=bs.parentid) WHERE bs.courseid = '.$courseid);
		
		
		//création du contenu XML
		$xml = new DOMDocument('1.0', 'utf-8');
		$elements = $xml->createElement('elements');
		$xml->appendChild($elements);
		
		foreach ($summary as $key => $value) {
			
			//création de l'élément monitoré
			$element = $xml->createElement('element');
			$element->setAttribute('id',$key);
			
			// courseid
			$xcourseid= $xml->createElement('courseid', $value->courseid);
			$element->appendChild($xcourseid);
			
			// sectionid
			$xsectionid= $xml->createElement('sectionid', $value->sectionid);
			$element->appendChild($xsectionid);
			
			// parentid
			$xparentid= $xml->createElement('parentid', $value->parentid);
			$element->appendChild($xparentid);
			
			// weight
			$xweight= $xml->createElement('weight', $value->weight);
			$element->appendChild($xweight);
			
			// section
			$xsection= $xml->createElement('section', $value->section);
			$element->appendChild($xsection);
			
			// parentsection
			$xparentsection= $xml->createElement('parentsection', $value->parentsection);
			$element->appendChild($xparentsection);
			
			
			$elements->appendChild($element);
		}
		return $xml->saveXML();
	}
	
	/*
	 *	création du fichier xml monitor.xml
	 */
	protected function summary_file(){
		$file_content = $this->summary_file_content();
		
		$f_summary= fopen($this->summary_path."/summary.xml","w");
		if($f_summary!=false){
			$test_write = fwrite( $f_summary , $file_content);
			fclose($f_summary);
		}
	}
}

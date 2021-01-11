<?php

use Aws\DynamoDb\Enum\ReturnValue;

class editor_tinymce_json_setting_textarea_fontawesome  extends admin_setting_configtext {
    private $rows;
    private $cols;

    /**
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param mixed $defaultsetting string or array
     * @param mixed $paramtype
     * @param string $cols The number of columns to make the editor
     * @param string $rows The number of rows to make the editor
     * @param array $pathfonts The path list to locate the svg files
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $paramtype=PARAM_RAW, $cols='60', $rows='8', $pathfonts) {
        $this->rows         = $rows;
        $this->cols         = $cols;
        $this->pathfonts    = $pathfonts;

        parent::__construct($name, $visiblename, $description, $defaultsetting, $paramtype);
    }

    /**
     * Returns an XHTML string for the editor
     *
     * @param string $data
     * @param string $query
     * @return string XHTML string for the editor
     */
    public function output_html($data, $query='') {
        global $OUTPUT, $CFG, $PAGE;

        $default = $this->get_defaultsetting();
        $defaultinfo = $default;
        if (!is_null($default) and $default !== '') {
            $defaultinfo = "\n".$default;
        }
        
        // To save the pathfontsawesome in config table, use the line below:
        //set_config('pathfontsawesome', 'fa:'.$CFG->dirroot.'/lib/fonts/fontawesome-webfont.svg');
        
        // First, check in the config_plugins table
        $pathfonts = get_config('moodle', 'pathfontsawesome');
        $findinconfig = false;
        if ($pathfonts) {

            $tabpathfonts = $this->buildDataFontsFromConfig($pathfonts);

            if ($tabpathfonts){
                $findinconfig = true;
                $pathfonts = $tabpathfonts;
            }
    
        }
        // Finally, we check in the plugin config.php file 
        if (!$findinconfig) {
            $pathfonts = $this->buildDataFontsFromConfig($this->pathfonts);
        }

        $error = null;
        $classesFA = [];
        if (is_array($pathfonts)){
            foreach ($pathfonts as $type => $path) {
                if (file_exists($path)) {
                    $cssfontawesome = file_get_contents($path);

                    $firstpartpreg = null;
                    preg_match_all('|glyph-name="(.*)".*unicode="(.*)"|sU', trim($cssfontawesome), $firstpartpreg, PREG_SET_ORDER);

                    foreach ($firstpartpreg as $item) {
                        if(isset( $item[1]) && $item[2] && substr($item[1], 0, 1) !== '_' ){
                            $classesFA[] = [
                                "type" => $type,
                                "name" => $item[1],
                                "unicode" => htmlentities($item[2])
                            ];
                        }
                    }
                } else {
                    $error = get_string("error1","atto_fontawesomepicker");
                }
            }
        }

        $name = array_column($classesFA, 'name');
        array_multisort($name, SORT_ASC, $classesFA);

        $context = (object) [
            'cols' => $this->cols,
            'rows' => $this->rows,
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
            'value' => $data,
            'forceltr' => $this->get_force_ltr(),
            'error' => $error,
            'icons' => $classesFA
        ];
        $element = $OUTPUT->render_from_template('atto_fontawesomepicker/setting_configfontawesome', $context);

        return format_admin_setting($this, $this->visiblename, $element, $this->description, true, '', $defaultinfo, $query);
    }

    /**
     * Returns a data array for the FontAwesome plugin config
     *
     * @param string $value
     * @return array|false 
     */    
    public function buildDataFontsFromConfig(string $value){

            $elements = explode('||', $value);
            
            $pathfonts = [];
            for ($i=0;$i<count($elements);$i++){

                if (strpos($elements[$i], ':') === false)
                    continue;

                $icopath = explode(':', $elements[$i]);
                $pathfonts[trim($icopath[0])] = $icopath[1];
            }
            
            if (count($pathfonts) == 0)
                return false;
            
            return $pathfonts;
    }
}

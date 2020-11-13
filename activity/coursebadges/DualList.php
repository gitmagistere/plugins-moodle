<?php
class DualList{

    const LEFT_COLUMN = 'left';
    const RIGHT_COLUMN = 'right';
    const READ_ONLY_COLUMN = 'read-only';
    const ACTION_BUTTONS = 'action-buttons';
    const BLANK_COLUMN = 'blank-column';

    private $datacolumn;
    private $readonlycolumn;

    function __construct() {
        $this->datacolumn = [];
        $this->readonlycolumn = [];
    }

    public function add_column($name, $data){
        if($name != DualList::READ_ONLY_COLUMN){
            $this->datacolumn[$name] = $data;
        } else {
            $this->readonlycolumn[$name] = $data;
        }
    }

    public function generate_html(){
        global $CFG, $PAGE;
        require_once($CFG->dirroot.'/badges/renderer.php');
        $badges_renderer = new core_badges_renderer($PAGE, '');

        $html = html_writer::start_tag('table', ['class' => 'badges-management']);
        $html .= html_writer::start_tag('tr');
        foreach ($this->datacolumn as $key => $column){
            if($key == DualList::ACTION_BUTTONS){
                // Boutons d'action
                $html .= html_writer::start_tag('td', ['class' => 'list-arrows']);

                // Bouton de basculement sur la droite
                $html .= html_writer::start_tag('button', ['class' => 'btn move-right', 'type' => 'button']);
                $html .= html_writer::tag('i','', ['class' => 'fas fa fa-angle-right']);
                $html .= html_writer::end_tag('button');

                // Bouton de basculement sur la gauche
                $html .= html_writer::start_tag('button', ['class' => 'btn move-left', 'type' => 'button']);
                $html .= html_writer::tag('i','', ['class' => 'fas fa fa-angle-left']);
                $html .= html_writer::end_tag('button');

                $html .= html_writer::end_tag('td');
            } else if($key == DualList::BLANK_COLUMN){
                // blank column
                $html .= html_writer::tag('td','', ['class' => 'blank-column']);
            } else {
                $html .= html_writer::start_tag('td', ['class' => $column->cls.' dual-list '.$column->extracls]);

                // Contenu du bloc HTML
                $html .= html_writer::start_div('content');

                // Titre du bloc HTML
                $html .= html_writer::start_div('title');
                $html .= html_writer::tag('h4', get_string($column->title, 'coursebadges'));
                $html .= html_writer::end_div();

                $html .= html_writer::start_tag('ul', ['class' => 'list-group']);
                foreach($column->badges as $badge){
                    $badge = new badge($badge->id);
                    $image_url = CourseBadges::get_imageurl_for_badge($badge->id)->out();
                    $html .= html_writer::start_tag('li',
                        ['class' => 'list-group-item',
                        'id' => $column->id_name.'_'.$badge->id,
                        'value' => $badge->id]);
                    $html .= html_writer::start_div('badge-information');
                    $html .= html_writer::start_div('badge-content');
                    if(empty($column->extracls)){
                        $html .= html_writer::checkbox('chx_'.$column->id_name.'_'.$badge->id, 0, false);
                    }
                    $html .= html_writer::img($image_url,$badge->name);
                    $html .= html_writer::span($badge->name,"badge-title");
                    $html .= html_writer::end_div();
                    $html .= html_writer::start_div('action-btn');
                    $html .= html_writer::tag('i','', ['class' => 'fas fa-sort-up fa fa-sort-asc']);
                    $html .= html_writer::end_div();
                    $html .= html_writer::end_div();
                    $html .= html_writer::start_div('badge-detail');
                    $html .= html_writer::start_div('badge-description');
                    $html .= html_writer::span('Description',"badge-description-title");
                    $html .= html_writer::tag('p',$badge->description);
                    $html .= html_writer::span('Critère',"badge-critère-title");
                    $html .= html_writer::tag('p',$badges_renderer->print_badge_criteria($badge));
                    $html .= html_writer::end_div();
                    $html .= html_writer::end_div();
                    $html .= html_writer::end_tag('li');
                }
                $html .= html_writer::end_tag('ul');
                $html .= html_writer::end_div();
                $html .= html_writer::end_tag('td');
            }
        }
        foreach ($this->readonlycolumn as $key => $column){
            $html .= html_writer::start_tag('td', ['class' => $column->cls.' dual-list '.$column->extracls]);

            // Contenu du bloc HTML
            $html .= html_writer::start_div('content');

            // Titre du bloc HTML
            $html .= html_writer::start_div('title');
            $html .= html_writer::tag('h4', get_string($column->title, 'coursebadges'));
            $html .= html_writer::end_div();

            $html .= html_writer::start_tag('ul', ['class' => 'list-group']);
            foreach($column->badges as $badge){
                $badge = new badge($badge->id);
                $image_url = CourseBadges::get_imageurl_for_badge($badge->id)->out();
                $html .= html_writer::start_tag('li',
                    ['class' => 'list-group-item',
                        'id' => $column->id_name.'_'.$badge->id,
                        'value' => $badge->id]);
                $html .= html_writer::start_div('badge-information');
                $html .= html_writer::start_div('badge-content');
                $html .= html_writer::img($image_url,$badge->name);
                $html .= html_writer::span($badge->name,"badge-title");
                $html .= html_writer::end_div();
                $html .= html_writer::start_div('action-btn');
                $html .= html_writer::tag('i','', ['class' => 'fas fa-sort-up fa fa-sort-asc']);
                $html .= html_writer::end_div();
                $html .= html_writer::end_div();
                $html .= html_writer::start_div('badge-detail');
                $html .= html_writer::start_div('badge-description');
                $html .= html_writer::span('Description',"badge-description-title");
                $html .= html_writer::tag('p',$badge->description);
                $html .= html_writer::span('Critère',"badge-critère-title");
                $html .= html_writer::tag('p',$badges_renderer->print_badge_criteria($badge));
                $html .= html_writer::end_div();
                $html .= html_writer::end_div();
                $html .= html_writer::end_tag('li');
            }
            $html .= html_writer::end_tag('ul');
            $html .= html_writer::end_div();
            $html .= html_writer::end_tag('td');
        }

        $html .= html_writer::end_tag('tr');
        $html .= html_writer::tag('tr','',['class'=> 'footer']);
        $html .= html_writer::end_tag('table');
        return $html;
    }
}
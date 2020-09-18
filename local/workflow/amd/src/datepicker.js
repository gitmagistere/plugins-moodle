/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($) {
    function init(inputName){
        //datePicker
        $('input[name="'+inputName+'"]').datepicker();
        $('input[name="'+inputName+'"]').datepicker("option", "monthNames", ['janvier', 'f&eacute;vrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'ao&ucirc;t', 'septembre', 'octobre', 'novembre', 'd&eacute;cembre']);
        $('input[name="'+inputName+'"]').datepicker("option", "monthNamesShort", ['janv.', 'f&eacute;vr.', 'mars', 'avril', 'mai', 'juin', 'juil.', 'ao&ucirc;t', 'sept.', 'oct.', 'nov.', 'd&eacute;c.']);
        $('input[name="'+inputName+'"]').datepicker("option", "dayNames", ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi']);
        $('input[name="'+inputName+'"]').datepicker("option", "dayNamesShort", ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.']);
        $('input[name="'+inputName+'"]').datepicker("option", "dayNamesMin", ['D', 'L', 'M', 'M', 'J', 'V', 'S']);
        $('input[name="'+inputName+'"]').datepicker("option", "dateFormat", 'dd/mm/yy');
        $('input[name="'+inputName+'"]').datepicker("option", "weekHeader", 'Sem.');
        $('input[name="'+inputName+'"]').datepicker("option", "firstDay", 1);
        $('input[name="'+inputName+'"]').datepicker("option", "currentText", 'Aujourd\'hui');
        $('input[name="'+inputName+'"]').datepicker("option", "closeText", 'Fermer');
        $('input[name="'+inputName+'"]').datepicker("option", "prevText", 'Pr&eacute;c&eacute;dent');
        $('input[name="'+inputName+'"]').datepicker("option", "nextText", 'Suivant');
        $('input[name="'+inputName+'"]').datepicker("option", "isRTL", false);
        $('input[name="'+inputName+'"]').datepicker("option", "showMonthAfterYear", false);
    }
    
    return {
        init: function(inputName){
            init(inputName);
        }
    };
});
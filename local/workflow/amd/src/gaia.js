/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($) {
    function init() {

        /**
         * GAIA unlink popin
         */

        $("#dialog_unlink").dialog({
            autoOpen: false,
            width: 300,
            title : "Dissocier",
            draggable:"false",
            modal: true,
            open: function(){
                $('#cancel_button').focus();
            },
            resizable:false,
            closeOnEscape: false,
            closeText: 'Annuler',
            buttons: [{
                text: "Dissocier",
                id:"launch_unlink",
                click: function(){
                    window.location.href = $(this).data('url');
                }
            },{
                text: "Annuler",
                id: "cancel_button",
                click: function(){
                    $("#dialog_unlink").dialog('close');
                }
            }]
        });

        $(".unlink_link").click(function(e){
            e.preventDefault();

            $('#dialog_unlink').data('url', $(this).attr('href'));
            $('#dialog_unlink').show();
            $('#dialog_unlink').dialog('open');

        });
        

        $(document).ready(function(){
	        if (window.location.hash == '#id_gaiaheader') {
	        	$("#id_gaiaheader").removeClass("collapsed");
	        	$(document).scrollTop( $("#id_gaiaheader").offset().top );
	        }
        });

    }

    return {
        init: function(){
            init();
        }
    };
});
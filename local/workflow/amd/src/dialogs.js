/* jshint ignore:start */
define(['jquery', 'jqueryui', 'local_workflow/jquery.loadingModal'], function($) {
    function init() {
        // Link_createparcoursfromgabarit
        $("#wf_dialog_createparcoursfromgabarit").dialog({
            autoOpen: false,
            width: 600,
            height: 250,
            title: "Créer un parcours de formation",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Créer ",
                id: "btCreerCours",
                click: function() {
                    if ($('#wf_dialog_createparcoursfromgabarit input[name="new_course_name"]').val() == '') {
                        alert("Le nom est obligatoire");
                    } else if ($('#wf_dialog_createparcoursfromgabarit input[name="new_course_shortname"]').val() == '') {
                        alert("Le nom abrégé est obligatoire");
                    } else {
                    	submit_createparcoursfromgabarit_form();
                    }
                }
            },
                {
                    text: "Annuler",
                    id: "btAnnuler",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });
        
        function submit_createparcoursfromgabarit_form(){ 
        	$('#wf_createparcoursfromgabarit_form').submit();
    	}

        $("#wf_link_createparcoursfromgabarit").click(function(e) {
            e.preventDefault();
            resetPopinFields("#wf_link_createparcoursfromgabarit");
            $('#wf_dialog_createparcoursfromgabarit').show();
            $('#wf_dialog_createparcoursfromgabarit').dialog('open');
        });

        // Link_createsessionfromparcours
        $("#wf_dialog_createsessionfromparcours").dialog({
            autoOpen: false,
            width: 600,
            height: 470,
            title: "Créer une session de formation",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Créer ",
                id: "btCreerCours",
                click: function() {
                    if ($('#wf_dialog_createsessionfromparcours input[name="new_course_name"]').val() == '') {
                        alert("Le nom est obligatoire");
                    } else if ($('#wf_dialog_createsessionfromparcours input[name="new_course_shortname"]').val() == '') {
                        alert("Le nom abrégé est obligatoire");
                    } else {
                    	submit_createsessionfromWFParcoursForm();
                    }
                }
            },
                {
                    text: "Annuler",
                    id: "btAnnuler",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });
        
        function submit_createsessionfromWFParcoursForm(){ 
        	$('#wf_createsessionfromWFParcoursForm').submit();
    	}

        $("#wf_link_createsessionfromparcours").click(function(e) {
            e.preventDefault();
            $('#wf_dialog_createsessionfromparcours').show();
            $('input[name="datepicker_session"]').show();
            $('#wf_dialog_createsessionfromparcours').dialog('open');
        });

        // Link_recreatesessionfromparcours
        $("#wf_dialog_recreatesessionfromparcours").dialog({
            autoOpen: false,
            width: 600,
            height: 470,
            title: "Créer une nouvelle session de formation",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Créer ",
                id: "btCreerCours",
                click: function() {
                    if ($('#wf_dialog_recreatesessionfromparcours input[name="new_course_name"]').val() == '') {
                        alert("Le nom est obligatoire");
                    } else if ($('#wf_dialog_recreatesessionfromparcours input[name="new_course_shortname"]').val() == '') {
                        alert("Le nom abrégé est obligatoire");
                    } else {
                    	submit_recreatesessionfromWFParcoursForm();
                    }
                }
            },
                {
                    text: "Annuler",
                    id: "btAnnuler",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });
        
        function submit_recreatesessionfromWFParcoursForm(){ 
        	$('#wf_recreatesessionfromWFParcoursForm').submit();
    	}

        $("#wf_link_recreatesessionfromparcours").click(function(e) {
            e.preventDefault();
            $('#wf_dialog_recreatesessionfromparcours').show();
            $('input[name="datepicker_session"]').show();
            $('#wf_dialog_recreatesessionfromparcours').dialog('open');
        });

        // Link_archive
        $("#wf_dialog_archive").dialog({
            autoOpen: false,
            width: 600,
            title: "Archivage de la session de formation",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Archiver",
                id: "btCreerCours",
                click: function() {
                    $('#wf_archive_form').submit();
                }
            },
                {
                    text: "Annuler",
                    id: "btAnnuler",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });

        $("#wf_link_archive").click(function(e) {
            e.preventDefault();
            resetPopinFields('#wf_link_archive');
            $('#wf_dialog_archive').show();
            $('#wf_dialog_archive').dialog('open');
        });

        // Link_duplicate
        $("#wf_dialog_duplicate").dialog({
            autoOpen: false,
            width: 600,
            title: "Duplication dans la même catégorie",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Création du cours",
                id: "btCreerCours",
                click: function() {
                    if ($('#wf_dialog_duplicate input[name="new_course_name"]').val() == '') {
                        alert("Le nom est obligatoire");
                    } else if ($('#wf_dialog_duplicate input[name="new_course_shortname"]').val() == '') {
                        alert("Le nom abrégé est obligatoire");
                    } else {
                    	submit_duplicate_form();
                    }
                }
            },
            {
                text: "Annuler",
                id: "btAnnuler",
                click: function() {
                    $(this).dialog("close");
                }
            }]
        });
        
        function submit_duplicate_form(){ 
        	$('#wf_duplicate_form').submit();
    	}

        $("#wf_link_duplicate").click(function(e) {
            e.preventDefault();
            resetPopinFields('#wf_link_duplicate');
            $('#wf_dialog_duplicate').show();
            $('#wf_dialog_duplicate').dialog('open');
        });

        // Link_unarchive
        $("#wf_dialog_unarchive").dialog({
            autoOpen: false,
            width: 600,
            title: "Réouverture ",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Rouvrir",
                id: "btCreerCours",
                click: function() {
                    $('#wf_unarchive_form').submit();
                }
            },
                {
                    text: "Annuler",
                    id: "btAnnuler",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });

        $("#wf_link_unarchive").click(function(e) {
            e.preventDefault();
            resetPopinFields('#wf_link_unarchive');
            $('#wf_dialog_unarchive').show();
            $('#wf_dialog_unarchive').dialog('open');
        });

        // Link_discard
        $("#wf_dialog_discard").dialog({
            autoOpen: false,
            width: 400,
            title: "Mettre à la corbeille",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Oui",
                id: "btDiscard",
                click: function() {
                    $('#wf_discard_form').submit();
                }
            },
                {
                    text: "Annuler",
                    id: "btCancel",
                    click: function() {
                        $(this).dialog("close");
                    }
                }]
        });

        $("#wf_link_discard").click(function(e) {
            e.preventDefault();
            resetPopinFields('#wf_link_discard');
            $('#wf_dialog_discard').show();
            $('#wf_dialog_discard').dialog('open');
        });

        // Link_open_session
        $("#wf_dialog_open_session").dialog({
            autoOpen: false,
            width: 400,
            title: "Ouvrir la session",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Oui",
                id: "btOpenSession",
                click: function() {
                    $('#wf_open_session_form').submit();
                }
            },
                {
                    text: "Annuler",
                    id: "btCancel",
                    click: function() {
                        $(this).dialog("close");
                    }
                }]
        });

        $("#wf_link_open_session").click(function(e) {
            e.preventDefault();
            resetPopinFields('#wf_link_open_session');
            $('#wf_dialog_open_session').show();
            $('#wf_dialog_open_session').dialog('open');
        });

        // Link_open_session
        $("#wf_dialog_open_auto_inscription").dialog({
            autoOpen: false,
            width: 400,
            title: "Ouvrir la session en auto-inscription",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Oui",
                id: "btOpenSession",
                click: function() {
                    $('#wf_open_auto_inscription_form').submit();
                }
            },
                {
                    text: "Annuler",
                    id: "btCancel",
                    click: function() {
                        $(this).dialog("close");
                    }
                }]
        });

        $("#wf_link_open_auto_inscription").click(function(e) {
            e.preventDefault();
            resetPopinFields('#wf_link_open_auto_inscription');
            $('#wf_dialog_open_auto_inscription').show();
            $('#wf_dialog_open_auto_inscription').dialog('open');
        });

        // Link_movetotrash
        $("#wf_dialog_restorefromtrash").dialog({
            autoOpen: false,
            width: 600,
            title: "Restauration du parcours",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Restaurer",
                id: "btDiscard",
                click: function() {
                    $('#wf_restorefromtrash_form').submit();
                }
            },
                {
                    text: "Annuler",
                    id: "btCancel",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });

        $("#wf_link_restorefromtrash").click(function(e) {
            e.preventDefault();
            resetPopinFields("#wf_link_restorefromtrash");
            $('#wf_dialog_restorefromtrash').show();
            $('#wf_dialog_restorefromtrash').dialog('open');
        });

        // Link_publish
        $("#wf_dialog_publish").dialog({
            autoOpen: false,
            width: 400,
            title: "Publier/Mettre à jour dans l'offre",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Oui",
                id: "btPublish",
                click: function(e) {
                	e.preventDefault();
                	$(this).dialog('close');
                	$("body").loadingModal({
                        position: "auto",
                        text: "Publication/Mise à jour du parcours en cours",
                        color: "#fff",
                        opacity: "0.7",
                        backgroundColor: "rgb(0,0,0)",
                        animation: "circle"
                    });
                	$.post(
                        $('#wf_publish_form').attr("action"),
                        $('#wf_publish_form').serialize(),
                        function(response) {
                            if (response.error=="false")
                            {
                                $("body").loadingModal("animation","doubleBounce");
                                $("body").loadingModal("text","Le parcours a été publié/mis à jour<br/><input type='button' id='close' value='Continuer' onclick='window.location.reload()'>");
                            }else{
                            	$("body").loadingModal("animation","doubleBounce");
                                $("body").loadingModal("text","La publication/mise à jour du parcours a échouée<br/><input type='button' id='close' value='Continuer' onclick='window.location.reload()'>");
                            }
                        },
                        "json"
                    );
                }
            },
                {
                    text: "Annuler",
                    id: "btCancel",
                    click: function() {
                        $(this).dialog("close");
                    }
                }]
        });

        $("#wf_link_publish").click(function(e) {
            e.preventDefault();
            resetPopinFields('#wf_link_publish');
            $('#wf_dialog_publish').show();
            $('#wf_dialog_publish').dialog('open');
        });

        // Link_publish
        $("#wf_dialog_unpublish").dialog({
            autoOpen: false,
            width: 400,
            title: "Dépublier de l'offre",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Oui",
                id: "btUnpublish",
                click: function() {
                    $('#wf_unpublish_form').submit();
                }
            },
                {
                    text: "Annuler",
                    id: "btCancel",
                    click: function() {
                        $(this).dialog("close");
                    }
                }]
        });

        $("#wf_link_unpublish").click(function(e) {
            e.preventDefault();
            resetPopinFields('#wf_link_unpublish');
            $('#wf_dialog_unpublish').show();
            $('#wf_dialog_unpublish').dialog('open');
        });
    }

    function resetPopinFields(element) {
        $("input").each(function() {
            if ($(this).is('input[name="new_course_name"]')) {
                $(this).val('');
            } else if ($(this).is('input[name="new_course_shortname"]')) {
                $(this).val('');
            } else if ($(this).is('input[name="datepicker_session"]')) {
                $(this).val('');
            }
        });
        $(element+' select[name*="new_category_course"]').val($(element+' select[name="new_category_course"] option').first().val());
    }

    return {
        init: function() {
            init();
        }
    };

});
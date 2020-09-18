/* jshint ignore:start */
define(['jquery', 'core/notification', 'jqueryui', 'local_workflow/jquery.loadingModal'], function($, notification) {
    function init(mailPreviewApiUrl) {

        $("#wf_dialog_preview_mails").dialog({
            autoOpen: false,
            width: 700,
            height: 'auto',
            maxHeight: 470,
            title: "Prévisualisation de l'inscription mail",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Valider",
                id: "btValidateMails",
                click: function() {
                    if ($('#mform1').length) { // test if #mform1 exist
                        $('#mform1').submit();
                    }
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

        
        $("#id_submitgeneral").click(function() {

            var filesurls = [];
            var filesRole = [];

            var emailsLists = [];
            var manualEmailsRole = [];

            var groupsLists = [];
            var csvGroupLists = [];
            var manualGroupsLists = [];
            

            $("div[id^=fitem_id_][id$=_groups_user_enrol]").map(function(index, elt) {
                var group = [];
                $(elt).find("select").find(":selected").map(function(index, elt) {
                    group.push($(elt).text());
                });
                groupsLists.push(group.join());
            });


            $("div[id^=fitem_id_][id$=_userfile]").map(function(index, elt) {
                var regexp = /fitem_id_(.*)_userfile/g; // get role with regexp
                var matches = regexp.exec($(elt).attr('id'));
                var role = matches[1];

                $(elt).find(".filepicker-filename").find("a").map(function(indexBis, eltLink) {
                    filesurls.push($(eltLink).attr('href'));
                    filesRole.push(role); // add role linked to this file
                    csvGroupLists.push(groupsLists[index]);
                });
 
            });

            $("div[id^=fitem_id_][id$=_email_user_enrol]").map(function(index, elt) {
                var regexp = /fitem_id_(.*)_email_user_enrol/g;
                var matches = regexp.exec($(elt).attr('id'));
                var role = matches[1];
                $(elt).find("textarea").filter(function(indexBis,elt){
                    return $(elt).val() != "";
                }).map(function(indexBis, elt) {
                    emailsLists.push($(elt).val());
                    manualEmailsRole.push(role);
                    manualGroupsLists.push(groupsLists[index]);
                });
            });

            
            if ((filesurls && filesurls.length > 0) || (emailsLists && emailsLists.length > 0)) {
                $("body").loadingModal({
                    position: "auto",
                    text: "Validation des emails en cours...",
                    color: "#fff",
                    opacity: "0.7",
                    backgroundColor: "rgb(0,0,0)",
                    animation: "circle"
                });

                $.post(
                    mailPreviewApiUrl,
                    {
                        urls: filesurls,
                        files_role: filesRole,
                        manual_emails: emailsLists,
                        manual_emails_role: manualEmailsRole,
                        csv_groups_lists: csvGroupLists,
                        manual_groups_lists: manualGroupsLists,
                    },
                    function(response) {
                        if (response.error=="false") {
                            notification.alert('Erreur', 'Erreur de communication avec le serveur.', 'Retour');
                        } else {
                            var table = response;
                            $('#wf_dialog_preview_mails').html(table);
                            $('#wf_dialog_preview_mails').show();
                            $('#wf_dialog_preview_mails').dialog('open');
                        }
                        $("body").loadingModal("destroy");
                    },
                    "html"
                );
            } else {
                if ($('#mform1').length) { // test if #mform1 exist
                    $('#mform1').submit();
                }
            }
            
        });
    }


    return {
        init: function(mailPreviewApiUrl) {
            init(mailPreviewApiUrl);
        }
    };

});
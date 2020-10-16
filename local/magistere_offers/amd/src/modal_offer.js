/* jshint ignore:start */
define(['jquery', 'core/templates', 'core/ajax', 'jqueryui', 'local_magistere_offers/jquery.loadingModal'], function($, templates, ajax) {

    return {
        init: function () {
            var modalLock = false;
            $(document).ready(function () {
                if (window.location.hash) {
                    modalLock = true;
                    var href = window.location.href;
                    if (href.indexOf('?') == -1) {
                        href = href.replace('#', '?');
                    } else {
                        href = href.replace('#', '&');
                    }
                    var url = new URL(href),
                        id = url.searchParams.get("offer"),
                        idModal = '#modalOffer_' + id,
                        promises = ajax.call([
                            {methodname: 'local_magistere_offers_get_detail_offer', args: {id: id}}
                        ]);

                    promises[0].done(function (response) {
                        obj = JSON.parse(response);
                        var renderName = 'local_magistere_offers/modal_formation_offer';

                        if (obj['is_formation'] === false) {
                            renderName = 'local_magistere_offers/modal_course_offer';
                            $('#restore_course_form').find('input[name="hubcourseid"]').val(id);
                        }

                        templates.render(renderName, obj)
                            .then(function (html, js) {
                                $('#detailModal').html(html);
                                $(idModal).modal('show');
                                $('#detailModal .modal').on('shown.bs.modal', function () {
                                    $('body').addClass('modal-open');
                                });
                                $('#detailModal .modal').on('hidden.bs.modal', function () {
                                    history.pushState("", document.title, window.location.pathname + window.location.search);
                                    $('body').removeClass('modal-open');
                                    modalLock = false;
                                });
                                $('<script>').append(js).appendTo('body');

                                $("#detailModal").on("click", ".restore.link.course", function(e) {
                                    resetPopinFields("#dialog_restore_course");
                                    presetFullNameAndShortname("#dialog_restore_course");
                                    $('#dialog_restore_course').show();
                                    $('.modal-backdrop').css('z-index', 1050);
                                    $('#dialog_restore_course').dialog('open');
                                });

                                $(".ui-button-icon.ui-icon-closethick").click(function(e){
                                    $('.modal-backdrop').css('z-index', 1040);
                                    resetPopinFields("#dialog_restore_course");
                                });
                            });

                    }).fail(function (ex) {
                        console.log(ex);
                        modalLock = false;
                    });
                }

                collapse_element(".notes .itemelement", ".notes .itemtitle");
                collapse_element(".statistics .itemelement", ".statistics .itemtitle");
                
                $.fn.modal.Constructor.prototype.enforceFocus = function () {};
            });

            $('#region-main').on("click", 'a[ref-bs-element="modal"]', function(){
                if(modalLock){
                    return;
                }

                modalLock = true;

                var id = $(this).attr('ref-modal-id'),
                    idModal = '#' + $(this).attr('ref-modal'),
                    promises = ajax.call([
                        {methodname: 'local_magistere_offers_get_detail_offer', args: {id: id}}
                    ]);

                promises[0].done(function(response) {
                    obj = JSON.parse(response);
                    var renderName = 'local_magistere_offers/modal_formation_offer';

                    if(obj['is_formation'] === false){
                        renderName = 'local_magistere_offers/modal_course_offer';
                        $('#restore_course_form').find('input[name="hubcourseid"]').val(id);
                    }

                    templates.render(renderName, obj)
                        .then(function(html, js) {
                            // Here eventually I have my compiled template, and any javascript that it generated.
                            // The templates object has append, prepend and replace functions.

                            $('#detailModal').html(html);
                            $(idModal).modal('show');
                            $('#detailModal .modal').on('shown.bs.modal', function() {
                                $('body').addClass('modal-open');
                            });
                            $('#detailModal .modal').on('hidden.bs.modal', function() {
                                history.pushState("", document.title, window.location.pathname + window.location.search);
                                $('body').removeClass('modal-open');
                            });

                            $('<script>').append(js).appendTo('body');

                            $("#detailModal").on("click", ".restore.link.course", function(e) {
                                resetPopinFields("#dialog_restore_course");
                                presetFullNameAndShortname("#dialog_restore_course");
                                $('#dialog_restore_course').show();
                                $('.modal-backdrop').css('z-index', 1050);
                                $('#dialog_restore_course').dialog('open');
                            });

                            $(".ui-button-icon.ui-icon-closethick").click(function(e){
                                $('.modal-backdrop').css('z-index', 1040);
                                resetPopinFields("#dialog_restore_course");
                            });

                            modalLock = false;
                        });
                }).fail(function(ex) {
                    console.log(ex);
                    modalLock = false;
                });
            });

            function collapse_element(element, title){
                $(element).off("click");
                $('#region-main').on("click", title, function(e){
                    if(!$(element).hasClass('in')){
                        $(this).removeClass('collapsed');
                        $(element).addClass('in');
                    } else {
                        $(this).addClass('collapsed');
                        $(element).removeClass('in');
                    }
                });
            }

            // Dialog restore course
            $("#dialog_restore_course").dialog({
                autoOpen: false,
                width: 600,
                height: 350,
                title: "Restaurer un parcours de formation",
                draggable: "false",
                modal: true,
                resizable: false,
                closeOnEscape: false,
                closeText: 'Fermer',
                buttons: [{
                    text: "Restaurer ",
                    id: "btRestoreCours",
                    click: function() {
                        if($('#dialog_restore_course input[name="fullname"]').val() == '') {
                            alert("Le nom est obligatoire");
                        } else if($('#dialog_restore_course input[name="shortname"]').val() == '') {
                            alert("Le nom abrégé est obligatoire");
                        } else {
                            restoreCourseFormSubmitted();
                        }
                    }
                },
                    {
                        text: "Annuler",
                        id: "btAnnuler",
                        click: function() {
                            resetPopinFields("#dialog_restore_course");
                            $(this).dialog("close");
                            $('.modal-backdrop').css('z-index', 1040);
                        }
                    }
                ]
            });
            
            
            $(document).on('submit', '#restore_course_form', function(event){
                // Stop form from submitting normally
                event.preventDefault();

                // Get some values from elements on the page:
                var $form = $(this),
                    newcoursename = $form.find('input[name="fullname"]').val(),
                    newcourseshortname = $form.find('input[name="shortname"]').val(),
                    url = $form.attr('action');
                
                if(newcoursename == '' || newcourseshortname == ''){
                    return;
                }

                $("body").loadingModal({
                    position: "auto",
                    text: "Restauration du parcours en cours",
                    color: "#fff",
                    opacity: "0.7",
                    backgroundColor: "rgb(0,0,0)",
                    animation: "circle"
                });

                // Send the data using post
                $.ajax({
                    type: "POST",
                    url: url,
                    data: $("#restore_course_form").serialize(),
                    dataType: "json",
                    success:function(response){
                        if(response.error == false){
                            var new_id = response.newid,
                                search = url.search("/local/coursehub/restore.php"),
                                uri = url.split(url.substr(search))[0],
                                restore_url = uri + "/course/view.php?id=" + new_id;
                            $('body').loadingModal("text",loadingModalHtmlAfterRestore(restore_url));
                            $('body').loadingModal("animation","doubleBounce");
                            resetPopinFields("#dialog_restore_course");
                            $('#dialog_restore_course').dialog("close");
                            $('.modal-backdrop').css('z-index', 1040);
                        } else {
                            $("body").loadingModal("text",loadingModalHtmlErrorRestore(response.msg));
                        }
                        $('.after-restore-links a').click(function(e){
                            $('body').loadingModal("hide");
                            setTimeout(function(){$("body").loadingModal("destroy")},1000);
                        });
                    },
                    error:function(error){
                    	$("body").loadingModal("text",loadingModalHtmlErrorRestore('Unknown error'));
                        console.log(error);
                    }
                });
            });


            // Fonction permettant la restauration d'un parcours au travers d'une popin
            function restoreCourseFormSubmitted(){
                $('#restore_course_form').submit();
            }

            function loadingModalHtmlAfterRestore(url){
                return '' +
                    '<div>' +
                    '   <p>La restauration du parcours a bien été effectué</p>' +
                    '   <div class="after-restore-links">' +
                    '       <a class="btn restore-course-view" href="'+url+'">Accéder au parcours restauré</a>' +
                    '       <a class="btn restore-course-close-modal" href="#">Retour sur l\'offre de parcours</a>' +
                    '   </div>' +
                    '</div>'
            }

            function loadingModalHtmlErrorRestore(error){
                return '' +
                    '<div>' +
                    '   <p>La restauration du parcours a échouée (' + error + ')</p>' +
                    '   <div class="after-restore-links">' +
                    '       <a class="btn restore-course-close-modal" href="#">Retour sur l\'offre de parcours</a>' +
                    '   </div>' +
                    '</div>'
            }

            function resetPopinFields(element){
                $(element+' input[name="fullname"]').val('');
                $(element+' input[name="shortname"]').val('');
                $(element+' select[name*="categoryid"]').val($(element+' select[name="categoryid"] option').first().val());
            }

            function presetFullNameAndShortname(element){
                var fullname = $('#detailModal #modalOfferTitle').text(),
                    shortname = $('#detailModal .hidden .shortname').text();

                $(element+' input[name="fullname"]').val(fullname);
                $(element+' input[name="shortname"]').val(shortname);
            }
        }
    }
});

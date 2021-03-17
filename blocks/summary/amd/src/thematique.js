/* jshint ignore:start */
define(['block_summary/nestable', 'block_summary/tooltipster', 'jqueryui'], function() {

    function init(newsectionstartid, coursenextweight, dragbutton, buttonspacer, buttons) {
        var lastsectionid = newsectionstartid;
        var lastpageid = coursenextweight;
        var is_form_changing = false;
        var deleted_sections = [];

        $(".dd").nestable({maxDepth: 2,
            handleClass: "dd-itemdiv",
            expandBtnHTML: "",
            collapseBtnHTML: "",
            onDragStart: function(l, e, p) {
                var handleElm = $(e).find(".dd-div").first();
                // Div.find("#"+liid+"_input").focus();
                var handleElm = $(e).find(".dd-div").first();
                var pos = handleElm.offset();
                var w = handleElm.width();
                var h = handleElm.height();

                if (p.left < pos.left || p.left > pos.left + w
                    || p.top < pos.top || p.top > pos.top + h) {
                    return false;
                }
            },
            beforeDragStop: function(){
                is_form_changing = true;
            }
        });

        $(".ishidden").parents("div.dd-itemdiv").addClass("hideClass");

        $(".dd").on("click", ".hide", function() {
            if ($(this).hasClass("ishidden")) {
                $(this).toggleClass("ishidden", false);
                $(this).addClass("fa-eye");
                $(this).removeClass("fa-eye-slash");
                $(this).tooltipster("content", $('<span>Masquer la section</span>'));
                $(this).parents("div.dd-itemdiv").removeClass("hideClass");

            } else {
                $(this).toggleClass("ishidden", true);
                $(this).removeClass("fa-eye");
                $(this).addClass("fa-eye-slash");
                $(this).tooltipster("content", $('<span>Afficher la section</span>'));
                $(this).parents("div.dd-itemdiv").addClass("hideClass");

            }
            is_form_changing = true;
        });

        $("#addsection").on("click", function(e) {
            e.preventDefault();
            $("#dd>ol").append('<li class="dd-item" data-id="' + lastsectionid + '"><div class="dd-itemdiv"><div class="dd-div">' + dragbutton + '</div>' + buttonspacer + '<div class="dd-handle">Nouvelle section (' + lastpageid + ')</div><div class="dd-buttonsblock">' + buttons + '</div></div></li>');
            lastsectionid = lastsectionid + 1;
            lastpageid = lastpageid + 1;

            var params = {
                maxWidth: 620,
                position: "bottom-right",
                timer: 3000
            };

            params.content = $("<span>Edition du titre de la section</span>");
            $(".edit").tooltipster(params);

            params.content = $("<span>Masquer la section</span>");
            $(".hide:not(.ishidden)").tooltipster(params);

            params.content = $("<span>Afficher la section</span>");
            $(".ishidden").tooltipster(params);

            params.content = $("<span>Suppression de la section</span>");
            $(".del").tooltipster(params);

            params.content = $("<span>Déplacer la section</span>");
            $(".move").tooltipster(params);

            is_form_changing = true;
        });


        $("#dd").on("click", ".del", function() {
            var mthis = $(this);

            $("#dialog-confirm").dialog({
                resizable: false,
                height: "auto",
                width: 600,
                modal: true,
                buttons: {
                    "Supprimer la section": function() {
                    	mthis.parents("li").first().find(".dd-handle").toArray().map(function(x){
                    		return deleted_sections.push(x.textContent);
                    	});
                        if (mthis.parents("ol").first().children().length <= 1) {
                            $(".dd").nestable("unsetParent", mthis.parents("ol").first().parents("li").first());
                        }
                        mthis.parents("li").first().remove();
                        $(this).dialog("close");
                    },
                    "Annuler": function() {
                        $(this).dialog("close");
                    }
                }
            });
            is_form_changing = true;

        });


        $(".dd").on("keyup", ".editinput", function(e) {
            if (e.keyCode == 13) {
                $(this).trigger("focusout");
            }
            is_form_changing = true;
        });

        $(".dd").on("focusout", ".editinput", function() {

            var div = $(this).parent();
            var editbutton = div.parent().children(".dd-buttonsblock").children(".edit");

            editbutton.removeClass("fa-check");
            editbutton.addClass("fa-pencil");

            var text = div.children().first().val();
            div.empty();
            div.append(text);

            is_form_changing = true;
        });


        /* ATTENTION : Pour  rendre la balise <input> clickable, il faut supprimer la ligne e.preventDefault (fonction onStartEvent, ligne 181) dans le fichier source du plugin Nestable*/
        $(".dd").on("click", ".edit", function() {

            var parentli = $(this).parents("li");
            var div = parentli.first().children(".dd-itemdiv").first().children(".dd-handle").first();
            var liid = parentli.attr("data-id");

            if ($(this).hasClass("fa-pencil")) {
                $(this).removeClass("fa-pencil");
                $(this).addClass("fa-check");

                var text = $.trim(div.text());

                var input = $("<input>");
                input.attr("class", "editinput");
                input.attr("id", liid + "_input");
                input.attr("value", text);
                input.css({
                    'z-index': 897456
                });
                input.attr("type", "text");

                div.empty();
                div.append(input);

                div.find("#" + liid + "_input").focus();

            } else {

                $(this).removeClass("fa-check");
                $(this).addClass("fa-pencil");

                var text = div.children().first().val();

                div.empty();
                div.append(text);
            }

            is_form_changing = true;
        });


        $("#save").on("click", function(e) {
        	e.preventDefault();
        	
            var list = $(".dd").nestable("serialize");

            for (var i = 0; i < list.length; i++) {
                var elm = $("li[data-id='" + list[i].id + "']> .dd-itemdiv .ishidden").length;
                list[i].hidden = !!elm;
                var elmname = $("li[data-id='" + list[i].id + "']> .dd-itemdiv .dd-handle").text();
                list[i].name = elmname;

                if (list[i].children && list[i].children.length) {
                    var listc = list[i].children;
                    for (var j = 0; j < listc.length; j++) {
                        var elm = $("li[data-id='" + listc[j].id + "'] .dd-itemdiv .ishidden").length;
                        listc[j].hidden = !!elm;

                        var elmname = $("li[data-id='" + listc[j].id + "'] .dd-itemdiv .dd-handle").text();
                        listc[j].name = elmname;
                    }
                }
            }
            is_form_changing = false;
            $("#treedata").val(JSON.stringify(list));
            $("#isSubmited").val(true);
            
            if(deleted_sections.length > 0) {
                $("#dialog-confirm-save-list").empty();
                for(i = 0; i < deleted_sections.length; i++){
                    $("#dialog-confirm-save-list").append("<li>" + deleted_sections[i] + "</li>");
                }

                $("#dialog-confirm-save").dialog({
                    resizable: false,
                    height: "auto",
                    maxHeight: 400,
                    width: 600,
                    modal: true,
                    buttons: {
                        "Valider": function() {
                            is_form_changing = false;
                            $("#form").submit();
                            $(this).dialog("close");
                        },
                        "Annuler": function() {
                            $(this).dialog("close");
                        }
                    }
                })
                // Focus by default the "Annuler" button;
                $('.ui-dialog-buttonset > button:last').focus();
                is_form_changing = true;
            }else{
                $("#form").submit();
            }
        });


        var params = {
            maxWidth: 620,
            position: "bottom-right",
            timer: 3000
        };

        params.content = $("<span>Edition du titre de la section</span>");
        $(".edit").tooltipster(params);

        params.content = $("<span>Masquer la section</span>");
        $(".hide:not(.ishidden)").tooltipster(params);

        params.content = $("<span>Afficher la section</span>");
        $(".ishidden").tooltipster(params);

        params.content = $("<span>Suppression de la section</span>");
        $(".del").tooltipster(params);

        params.content = $("<span>Déplacer la section</span>");
        $(".move").tooltipster(params);

        $(window).on("beforeunload", function(e) {
            if(is_form_changing){
                e = e || window.event;

                // For IE and Firefox prior to version 4
                if (e) {
                    e.returnValue = 'There is some unsaved changes!';
                }

                // For Safari
                return 'There is some unsaved changes!';
            }
        });
    }
        return {
            init: function(newsectionstartid, coursenextweight, dragbutton, buttonspacer, buttons) {
                init(newsectionstartid, coursenextweight, dragbutton, buttonspacer, buttons);
            }
        };

    });



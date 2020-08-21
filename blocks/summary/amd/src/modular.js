/* jshint ignore:start */
define(['block_summary/nestable', 'block_summary/tooltipster', 'jqueryui'], function(){

    function init(){
        var dd_start_parent = null;
        var has_content_class = "hascontent";
        var has_navigation_class = "hasnav";
        var is_form_changing = false;
        var deleted_sections = [];

        $(".dd").nestable({
            maxDepth:3,
            handleClass: "dd-itemdiv",
            expandBtnHTML: "",
            collapseBtnHTML:"",
            group: 1,
            onDragStart: function(l, e, p){
                var handleElm = $(e).find(".dd-div").first();
                var pos = handleElm.offset();
                var w = handleElm.width();
                var h = handleElm.height();

                // d&d only on the cross-handle
                if(p.left < pos.left || p.left > pos.left + w
                    || p.top < pos.top || p.top > pos.top + h){
                    return false;
                }

                dd_start_parent = e.parents("ol:first").parent("li").children(".dd-itemdiv:first");
            },
            beforeDragStop: function(l, e, p){
                var end_parent = p[0]
                var end_is_module_part = ($(p[0]).closest(".section-part[data-type='module']").length > 0);

                // if start parent is a module
                // and has no children, then remove the hasContent button and hasNavigation button
                if(dd_start_parent && dd_start_parent.parents(".section-part[data-type='module']").length){
                    if(dd_start_parent.next("ol").length == 0){
                        dd_start_parent.find("."+has_navigation_class).next(".dd-buttonsblockspacer").remove();
                        dd_start_parent.find("."+has_navigation_class).remove();
                        dd_start_parent.find("."+has_content_class).remove();

                        dd_start_parent.find(".dd-handle").removeClass('dd-handle-parent');
                    }
                }

                dd_start_parent = null;

                if(end_is_module_part){
                    // if the parent does not have the hasContent button
                    end_parent = $(end_parent).prev(".dd-itemdiv");

                    // current element
                    var elm = $(e);
                    var itemdiv = elm.find(".dd-itemdiv");

                    // add the hasContent button to the elm and child
                    var allelm = end_parent.add(itemdiv);
                    allelm.each(function(){
                        if($(this).next("ol").length > 0 && $(this).find("."+has_content_class).length == 0){
                            var spacer = $('<span>').addClass('dd-buttonsblockspacer');
                            var contentbutton = $('<button>').addClass('fa '+has_content_class);

                            $(this).find(".dd-buttonsblock").prepend(spacer, contentbutton);
                            $(this).find(".dd-handle").addClass('dd-handle-parent');

                            var params = {
                                maxWidth: 620,
                                position: "bottom-right",
                                timer: 3000
                            };

                            params.content = $("<span>Section avec contenu</span>");
                            $(".hascontent").not('.nocontent').tooltipster(params);
                        }
                    });

                    var navigationButton = $('<button>').addClass(has_navigation_class+' fa-stack');
                    navigationButton.append($('<span>').addClass('fa fa-sort fa-rotate-90 fa-stack-1x'));

                    var current = itemdiv.first();
                    // if the current item doesnt have any parents (it's a root node)
                    // and have children, and doesnt have the hasNavigation navigation
                    // then add the famous hasNavigation button
                    if(end_parent.length == 0 && itemdiv.length > 1){
                        if(current.find('.hasnav').length == 0){
                            current.find('.dd-buttonsblock').prepend(navigationButton);
                        }
                    }

                    // if the parent is a root (ie has only one ol element)
                    // add the hasNavigation button
                    var lastancestor = end_parent.parents('ol');
                    if(!end_parent || lastancestor.length == 1){

                        if(end_parent.find('.'+has_navigation_class).length == 0){
                            end_parent.find(".dd-buttonsblock").prepend(navigationButton);
                        }
                    }

                    // another case
                    // when the item is add to a parent, and the item was a root node and have the
                    // hasNavigation button
                    var hasnavelm = current.find('.hasnav');
                    console.log(hasnavelm);
                    console.log(end_parent);

                    if(end_parent.length && hasnavelm.length){
                        hasnavelm.next().remove(); // remove spacer
                        hasnavelm.remove();
                    }
                }else{
                    $(e).find("."+has_content_class).next(".dd-buttonsblockspacer").remove();
                    $(e).find("."+has_content_class).remove();
                    $(e).find("."+has_navigation_class).remove();
                }
                is_form_changing = true;
            }

        });

        $(".ishidden").parents("div.dd-itemdiv").addClass("hideClass");

        $(".dd").on( "click", ".hide", function() {
            if ($(this).hasClass("ishidden"))
            {
                $(this).toggleClass("ishidden",false);
                $(this).addClass("fa-eye");
                $(this).removeClass("fa-eye-slash");
                $(this).tooltipster("content",$('<span>Masquer la section</span>'));
                $(this).parents("div.dd-itemdiv").removeClass("hideClass");

            }else{
                $(this).toggleClass("ishidden",true);
                $(this).removeClass("fa-eye");
                $(this).addClass("fa-eye-slash");
                $(this).tooltipster("content",$('<span>Afficher la section</span>'));
                $(this).parents("div.dd-itemdiv").addClass("hideClass");

            }
            is_form_changing = true;
        });

        $(".dd").on("click", ".hascontent", function(){

            if($(this).hasClass("nocontent")){
                $(this).removeClass("nocontent");
                $(this).tooltipster("content", $("<span>Section avec contenu</span>"));
            }else{
                $(this).addClass("nocontent");
                $(this).tooltipster("content", $("<span>Section sans contenu</span>"));
            }
            is_form_changing = true;
        });

        $(".dd").on("click", ".hasnav", function(){
            if($(this).hasClass("nonav")){
                $(this).removeClass("nonav fa fa-slash fa-stack-1x");
                $(this).tooltipster("content", $("<span>Module navigable</span>"));
            }else{
                $(this).addClass("nonav");
                $(this).addClass('fa fa-slash fa-stack-1x');
                $(this).tooltipster("content", $("<span>Module non navigable</span>"));
            }
            is_form_changing = true;
        });

        var newid = -1;
        $("#addsection").on( "click", function(e) {
            e.preventDefault();

            var modulepart = $(".section-part[data-type=\'module\']>ol");

            if(!modulepart.length){
                $(".section-part[data-type='module']").append($("<ol>").attr('class', 'dd-list'));
                $(".section-part[data-type='module'] .dd-empty").remove();
            }


            $(".section-part[data-type=\'module\']>ol").append(create_section(newid));

            newid--;

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

        var create_section = function(id)
        {
            var li = $("<li>")
                .attr("class", "dd-item")
                .attr("data-id", id);

            var itemdiv =  $("<div>").addClass("dd-itemdiv");

            var dddiv = $("<div>")
                .addClass("dd-div")
                .append(dd_drag_button);

            var defaultnewname = "Nouvelle section";
            var ddhandle = $("<div>").addClass("dd-handle").append(defaultnewname);

            var button = $("<div>").addClass("dd-buttonsblock").append(dd_buttons);

            itemdiv.append(dddiv, ddhandle, button);

            return li.append(itemdiv);
        }

        $('form#form').submit(function() {
            $(window).unbind('beforeunload');
        });

        $("#save").on( "click", function(e) {
            e.preventDefault();

            var list = serialize_tree($(".block_summary"));

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
                window.onbeforeunload = null;
                $("#form").submit();
            }

        });


        $(".dd").on( "click", ".del", function() {
            var mthis = $(this);

            $( "#dialog-confirm" ).dialog({
                resizable: false,
                height: "auto",
                width: 600,
                modal: true,
                buttons: {
                    "Supprimer la section": function() {

                        // Main Section
                        var section = mthis.parents("li").first();
                        var numsection = section.attr('data-numsection');
                        var text = section.find('div.dd-handle')[0].textContent;
                        deleted_sections.push("Section " + numsection + " : " + text);
                        // All Sub-Sections and Sub-Sub-Sections
                        section.find("li").toArray().map(function(x) {
                            var soussection = $(x);
                            var numsection = soussection.attr('data-numsection');
                            var text = soussection.find('div.dd-handle')[0].textContent;
                            deleted_sections.push("Section " + numsection + " : " + text);
                        });

                        if (mthis.parents("ol").first().children().length <= 1) {
                            $(".dd").nestable("unsetParent",mthis.parents("ol").first().parents("li").first());
                        }
                        mthis.parents("li").first().remove();
                        $( this ).dialog( "close" );
                    },
                    "Annuler": function() {
                        $( this ).dialog( "close" );
                    }
                }
            });
            is_form_changing = true;

        });


        $(".dd").on( "keyup", ".editinput", function(e) {
            if(e.keyCode == 13)
            {
                $(this).trigger("focusout");
            }
        });

        $(".dd").on( "focusout", ".editinput", function() {

            var div = $(this).parent();
            var editbutton = div.parent().children(".dd-buttonsblock").children(".edit");

            editbutton.removeClass("fa-check");
            editbutton.addClass("fa-pencil");

            var text = div.children().first().val();
            div.empty();
            div.append(text);

        });


        $(".dd").on( "click", ".edit", function() {

            var parentli = $(this).parents("li");
            var div = parentli.first().children(".dd-itemdiv").first().children(".dd-handle").first();
            var liid = parentli.attr("data-id");

            if ($(this).hasClass("fa-pencil"))
            {
                $(this).removeClass("fa-pencil");
                $(this).addClass("fa-check");

                var text = $.trim(div.text());

                var input = $("<input>");
                input.attr("class", "editinput");
                input.attr("id", liid+"_input");
                input.attr("value", text);
                input.attr("type", "text");

                div.empty();
                div.append(input);

                div.find("#"+liid+"_input").focus();
            }else{

                $(this).removeClass("fa-check");
                $(this).addClass("fa-pencil");

                var text = div.children().first().val();

                div.empty();
                div.append(text);
            }
            is_form_changing = true;
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

        params.content = $("<span>Section avec contenu</span>");
        $(".hascontent").not('.nocontent').tooltipster(params);

        params.content = $("<span>Section sans contenu</span>");
        $(".nocontent").tooltipster(params);

        params.content = $("<span>Module navigable</span>");
        $(".hasnav").tooltipster(params);

        params.content = $("<span>Module non navigable</span>");
        $(".nonav").tooltipster(params);


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


    function serialize_tree(node)
    {
        var ids = [];
        var nodes = [];

        var step = function(level, parentid) {
            var array = [],
                items = level.children("li");

            items.each(function() {
                var li = $(this),
                    item = $.extend({}, li.data()),
                    sub = li.children("ol");

                nodes.push(item);

                item.children = [];

                if (sub.length) {
                    item.children = step(sub, item.id);
                }

                item.name = $(this).find(".dd-handle:first").text();
                item.visible = !$(this).find(".hide:first").hasClass("ishidden");
                item.hasContent = !($(this).children('.dd-itemdiv').find('.nocontent').length > 0);
                item.type = $(this).parents(".section-part").attr("data-type");

                item.parentid = (parentid ? parentid : null);
                item.hasNavigation = ($(this).find(".hasnav:first").hasClass("nonav")?0:1);

                if(item.id > 0){
                    ids.push(item.id);
                }
            });

            return array;
        };

        var tree = {
            nodes: nodes,
            ids: ids
        };

        node.find(".section-part>ol").each(function(){
            step($(this));
        });

        return tree;
    }

    return {
        init: function(){
            init();
        }
    };
});
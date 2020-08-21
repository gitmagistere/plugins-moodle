$(function(){

    var dd_start_parent = null;
    var has_content_class = "hascontent";

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
            // and has no children, then remove the hasContent button
            if(dd_start_parent && dd_start_parent.parents(".section-part[data-type='module']").length){
                if(dd_start_parent.next("ol").length == 0){
                    dd_start_parent.find("."+has_content_class).next(".dd-buttonsblockspacer").remove();
                    dd_start_parent.find("."+has_content_class).remove();
                    dd_start_parent.find(".dd-handle").removeClass('dd-handle-parent');
                }
            }

            dd_start_parent = null;

            if(end_is_module_part){
                // if the parent does not have the hasContent button
                end_parent = $(end_parent).prev(".dd-itemdiv");

                // add the hasContent button to the elm and child
                var elm = $(e);
                var itemdiv = elm.find(".dd-itemdiv");

                var allelm = end_parent.add(itemdiv);

                allelm.each(function(){
                    if($(this).next("ol").length && $(this).find("."+has_content_class).length == 0){
                        $(this).find(".dd-buttonsblock")
                            .prepend("<span class='dd-buttonsblockspacer'></span><button class='fa "+has_content_class+" hascontent'></button>");
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
            }else{
                $(e).find("."+has_content_class).next(".dd-buttonsblockspacer").remove();
                $(e).find("."+has_content_class).remove();
            }
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
    });

    $(".dd").on("click", ".hascontent", function(){
        
        if($(this).hasClass("nocontent")){
            $(this).removeClass("nocontent");
            $(this).tooltipster("content", $("<span>Section avec contenu</span>"));
        }else{
            $(this).addClass("nocontent");
            $(this).tooltipster("content", $("<span>Section sans contenu</span>"));
        }

    });

    $(".dd").on("click", ".hasnav", function(){

        console.log('hasnav');

        if($(this).hasClass("nonav")){
            $(this).removeClass("nonav");
            $(this).children('.fa-slash').remove();
            $(this).tooltipster("content", $("<span>Section avec contenu</span>"));
        }else{
            $(this).addClass("nonav");
            $(this).append('<span>').addClass('fa fa-slash fa-stack-1x');
            $(this).tooltipster("content", $("<span>Section sans contenu</span>"));
        }

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


    $(".dd").on( "click", ".del", function() {
        var mthis = $(this);

        $( "#dialog-confirm" ).dialog({
            resizable: false,
            height: "auto",
            width: 600,
            modal: true,
            buttons: {
                "Supprimer la section": function() {
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
    });


    $("#submit").on("click", function()
    {
        var list = serialize_tree($(".block_summary"));

        $("#treedata").val(JSON.stringify(list));

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
});

var serialize_tree = function(node)
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
};
/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($) {
    /** Fonction init permettant la gestion des dual list de choix des badges */    
    function init() {
        $('body').on('click', '.list-group .list-group-item .badge-content', function() {
            if (!$(this).parents('td').hasClass('read-only') && !$(this).parents('td').hasClass('no-modif')) {
                $(this).parents('.list-group-item').toggleClass('active');
                var checkBox = $(this).find('input[type=checkbox]');
                checkBox.attr("checked", !checkBox.attr("checked"));
            }
        });

        $('body').on('click', '.action-btn', function() {
            var badgeDetail = $(this).parents('.list-group-item').find('.badge-detail');
            var buttonIcon = $(this).find('i');
            if (buttonIcon.hasClass('fa-sort-up')
                || buttonIcon.hasClass('fa-sort-asc')) {
                badgeDetail.show();
                buttonIcon.removeClass('fa-sort-up').addClass('fa-sort-down');
                buttonIcon.removeClass('fa-sort-asc').addClass('fa-sort-desc');
            } else {
                badgeDetail.hide();
                buttonIcon.removeClass('fa-sort-down').addClass('fa-sort-up');
                buttonIcon.removeClass('fa-sort-desc').addClass('fa-sort-asc');
            }
        });

        $('.list-arrows button').click(function() {
            var $button = $(this),
                actives = '';
            if ($button.hasClass('move-left')) {
                actives = $('.list-right ul.list-group li.list-group-item.active');
                actives.clone().appendTo('.list-left ul.list-group');
                actives.remove();

            } else if ($button.hasClass('move-right')) {
                actives = $('.list-left ul.list-group li.list-group-item.active');
                actives.clone().appendTo('.list-right ul.list-group');
                actives.remove();
            }

            sortBadgeElements('.list-left');
            sortBadgeElements('.list-right');

            var liRElement = $('.list-right ul.list-group li.list-group-item');
            $('input[name="rightlistids"]').val(createListIds(liRElement));
            var liLElement = $('.list-left ul.list-group li.list-group-item');
            $('input[name="leftlistids"]').val(createListIds(liLElement));

        });

        $('.dual-list .selector').click(function() {
            var $checkBox = $(this);
            if (!$checkBox.hasClass('selected')) {
                $checkBox.addClass('selected')
                .closest('.content')
                .find('ul.list-group li.list-group-item:not(.active)')
                .addClass('active');
            } else {
                $checkBox.removeClass('selected')
                .closest('.content')
                .find('ul.list-group li.list-group-item.active')
                .removeClass('active');
            }
        });

        $("#id_changebadgeselections").click(function(e) {
            e.preventDefault();
            $('#dialog_change_badge_selections').show();
            $('#dialog_change_badge_selections').dialog('open');
        });

        $("#dialog_change_badge_selections").dialog({
            autoOpen: false,
            width: 400,
            title: "Modifier les badges proposés",
            draggable: "false",
            modal: true,
            resizable: false,
            closeOnEscape: false,
            closeText: 'Fermer',
            buttons: [{
                text: "Oui",
                id: "btValidate",
                click: function() {
                    window.location.href = window.location.href + "&dbs=1";
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
    }

    /** Fonction de tri des badges 
     * @param {string} classElement string du nom de la classe contenant la liste de badges.
    */
    function sortBadgeElements(classElement) {
        var liElements = $(classElement + ' ul.list-group').find('li.list-group-item');
        var sortList = Array.prototype.sort.bind(liElements);

        sortList(function(a, b) {

            // Cache inner content from the first element (a) and the next sibling (b)
            var aText = $(a).find('.badge-title').text();
            var bText = $(b).find('.badge-title').text();

            // Returning -1 will place element `a` before element `b`
            if (aText < bText) {
                return -1;
            }

            // Returning 1 will do the opposite
            if (aText > bText) {
                return 1;
            }

            // Returning 0 leaves them as-is
            return 0;
        });

        $(classElement + ' ul.list-group').append(liElements);
    }

    /** Fonction de création d'une liste d'identifiant de badges 
     * @param {object} elements liste d'object html contenant les informations des badges.
     * @return {string} liste d'identifiant de badges séparé par une virgule.
    */
    function createListIds(elements) {
        var i = 0;
        var len = elements.length;
        var str = "";
        if (len > 0) {
            var property;
            for (property in elements) {
                if (elements[property].tagName == 'LI') {
                    if (i == len - 1) {
                        str += elements[property].value;
                    } else {
                        str += elements[property].value + ",";
                    }
                    i++;
                }
            }
        }
        return str;
    }

    return {
        init: function() {
            init();
        }
    };
});
/* jshint ignore:start */
define(['jquery', 'mod_coursebadges/jtable'], function($) {

    /** 
     * Fonction qui initialise le jTable utilisé pour les overview
     * @param {String} columns Object JSON contenant toutes les colonnes du jTable
     * @param {String} table Identifiant de la div qui contient le jTable
     * @param {String} ajaxbaseurl Lien du fichier ajax
     * @param {String} defaultsorting Colonnes concernées par le tri + valeur par défaut
    */
    function initJtable(columns, table, ajaxbaseurl, defaultsorting) {
        columns = $('input[name="' + columns + '"]').val();
        columns = JSON.parse(columns);

        if (columns.imgurl) {
            columns.imgurl.display = function(data) {
                if (data.record.badgeurl) {
                    return '<a href="'
                    + data.record.badgeurl
                    + '"><img class="cb_overviewbadge" src="'
                    + data.record.imgurl
                    + '"/></a>';
                }
                return '<img class="cb_overviewbadge" src="' + data.record.imgurl + '"/>';
            };
        }

        if (columns.modname) {
            columns.modname.display = function(data) {
                var r = data.record;
                var res = '';
                for (var i = 0; i < r.modnames.length; i++) {
                    res += r.modnames[i].name + '<br/>';
                }
                return res;
            };
        }

        if (columns.percent) {
            columns.percent.display = function(data) {
                var r = data.record;
                if (r.urlearnedbadge && r.urlselectedbadge) {
                    return '<p><a href="' + r.urlearnedbadge + '">'
                    + r.badgeearnedcount + '</a>/<a href="'
                    + r.urlselectedbadge + '">'
                    + r.badgetotal + '</a> ('
                    + r.badgepercent + '%)</p>';
                }
                return '<p>' + r.badgeearnedcount + '/' + r.badgetotal + ' (' + r.badgepercent + '%)</p>';
            };
        }

        if (columns.groupnames) {
            columns.groupnames.display = function(data) {
                var r = data.record;
                var res = '';
                for (var i = 0; i < r.groupnames.length; i++) {
                    res += r.groupnames[i].name + '<br/>';
                }
                return res;
            };
        }

        if (columns.earnedbadges) {
            columns.earnedbadges.display = function(data) {
                var r = data.record;
                var res = '';
                for (var i = 0; i < r.earnedbadges.length; i++) {
                    if (r.earnedbadges[i].badge_url) {
                        res += '<a href="'
                        + r.earnedbadges[i].badge_url
                        + '"><img class="cb_overviewbadge_mini" src="'
                        + r.earnedbadges[i].img_url + '"/></a>';
                    } else {
                        res += '<img class="cb_overviewbadge_mini" src="' + r.earnedbadges[i].img_url + '"/>';
                    }
                }
                return res;
            };
        }

        if (columns.selectedbadges) {
            columns.selectedbadges.display = function(data) {
                var r = data.record;
                var res = '';
                for (var i = 0; i < r.selectedbadges.length; i++) {
                    if (r.selectedbadges[i].badge_url) {
                        res += '<a href="'
                        + r.selectedbadges[i].badge_url
                        + '"><img class="cb_overviewbadge_mini" src="'
                        + r.selectedbadges[i].img_url
                        + '"/></a>';
                    } else {
                        res += '<img class="cb_overviewbadge_mini" src="' + r.selectedbadges[i].img_url + '"/>';
                    }
                }
                return res;
            };
        }

        var selectionTable = $("#" + table).jtable({
            paging: true,
            pageSize: 10,
            pageSizes: [10, 25, 50, 100],
            selecting: false,
            multiselect: false,
            sorting: true,
            defaultSorting: defaultsorting,
            jqueryuiTheme: true,
            defaultDateFormat: "dd-mm-yy",
            gotoPageArea: "none",
            actions: {
                listAction: function(postData, jtParams) {
                    return $.Deferred(function($dfd) {
                        postData = $('#mform1').serialize();
                        $.ajax({
                            url: ajaxbaseurl
                            + '&si=' + jtParams.jtStartIndex
                            + '&ps=' + jtParams.jtPageSize
                            + '&so=' + jtParams.jtSorting,
                            type: "POST",
                            dataType: "json",
                            data: postData,
                            success: function(data) {
                                $dfd.resolve(data);
                            },
                            error: function() {
                                $dfd.reject();
                            }
                        });
                    });
                },
            },
            fields: columns
        });

        selectionTable.jtable("load");
    }

   /**
    * Fonction qui gère la recherche par groupe, badge et par statut
    */
    function initSelectOverview() {
        $('#mform1 select').change(function() {
            $("#results").jtable("load");
        });
    }

   /**
    * Fonction qui gère la recherche par nom et prénom des participants
    * @param {String} name 
    */
    function initNameInput(name) {
        var timer;
        $('#mform1 input[name="' + name + '"]').on('keyup', function() {
            clearTimeout(timer);
            timer = setTimeout(function() {
                $("#results").jtable("load");
            }, 500);
        });
    }

    return {
        initJtable: initJtable,
        initSelectOverview: initSelectOverview,
        initNameInput: initNameInput
    };
});
/* jshint ignore:start */
define(['jquery'], function($) {

    return {
        init: function (hasFirstConnection, display, isloggedin) {
            $(document).ready(function () {
                var action = getUrlParameter('action');

                if((!parseInt(hasFirstConnection) && ($('body').hasClass('offerformation') || $('body').hasClass('offercourse'))) || (action == "changepref" && isloggedin == true)){
                    $('#publics-modal').modal('show');
                    $('#publics-modal .modal').on('shown.bs.modal', function () {
                        $('body').addClass('modal-open');
                    });
                    $('#publics-modal .modal').on('hidden.bs.modal', function () {
                        $('body').removeClass('modal-open');
                    });
                }
            });

            var getUrlParameter = function getUrlParameter(sParam) {
                var sPageURL = window.location.search.substring(1),
                    sURLVariables = sPageURL.split('&'),
                    sParameterName,
                    i;

                for (i = 0; i < sURLVariables.length; i++) {
                    sParameterName = sURLVariables[i].split('=');

                    if (sParameterName[0] === sParam) {
                        return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
                    }
                }
            };
        }
    }
});
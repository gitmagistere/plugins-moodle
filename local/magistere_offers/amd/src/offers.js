/* jshint ignore:start */
define(['jquery'], function($) {
    return {
        validateform: function(){

            $(document).ready(function () {
                validateForm('.filter-course');
                validateForm('.filter-formation');
            });

            function validateForm(classElement){
                // Gestion des tags dans le header
                $(".button-typeahead").click(function() {
                    var elementId = $(this).attr('data-id');
                    $('input#'+elementId).prop('checked', false);
                    $(this).hide();
                    $(".mform"+classElement).submit();
                });

                // Gestion du champs de recherche dans le header
                $("#search-input").val($('input[name="search_name"]').val());
                $(".fa-search").on("click",function(){
                    $(".mform"+classElement).find('input[name="search_name"]').val($("#search-input").val());
                    $(".mform"+classElement).submit();
                });
                $("#search-input").bind("enterKey",function(){
                    $(".mform"+classElement).find('input[name="search_name"]').val($(this).val());
                    $(".mform"+classElement).submit();
                });
                $("#search-input").keyup(function(e){
                    if(e.keyCode === 13){
                        $(this).trigger("enterKey");
                    }
                });

                // Si case à cocher manipulé, lancement du submit
                $('.mform'+classElement+' input[type="checkbox"]').on("change",function() {
                    $('.mform'+classElement).submit();
                });
            }
        }
    };
});
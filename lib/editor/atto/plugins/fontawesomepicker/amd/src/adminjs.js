/* jshint ignore:start */
define(['jquery','jqueryui'], function($, templates, ajax) {

    return {
        init: function () {
            var allicons = [];
            var nbdisplay = 50;

            function includes(container, value) {
                var returnValue = false;
                var pos = container.indexOf(value);
                if (pos >= 0) {
                    returnValue = true;
                }
                return returnValue;
            }

            function displayIcons(arrayicons){
                var striconsselected;
                if (typeof($("#selectedicons").val()) !== 'undefined') {
                    striconsselected = $("#selectedicons").val().split(';');
                }
                $('#searchcontainer #load').hide();
                $('#searchcontainer div.icon').hide();
                for (var i = 0; i < nbdisplay && i < arrayicons.length; i++) {
                    var strtemp = arrayicons[i]["type"] + " fa-" + arrayicons[i]["name"];

                    if( includes(striconsselected,strtemp) ){
                        $('#searchcontainer div.icon[data-name="' + arrayicons[i]["name"] + '"][data-type="'+ arrayicons[i]["type"] + '"]').append('<i class="fa fa-check-circle fa-2x actionselected"></i>');
                        $('#searchcontainer div.icon[data-name="' + arrayicons[i]["name"] + '"][data-type="'+ arrayicons[i]["type"] + '"]').addClass('selected');
                    }
                    $('#searchcontainer div.icon[data-name="' + arrayicons[i]["name"] + '"][data-type="'+ arrayicons[i]["type"] + '"]').show();
                }
            }

            function buildSelectedIcons(){
                var selecteditems = $("#fontawesomepickercustom_setting #selected");
                selecteditems.empty();
                if(typeof($("#selectedicons").val()) !== 'undefined' && $("#selectedicons").val() != ""){
                    var stricons =  $("#selectedicons").val().split(';');


                    for (var i = 0; i < stricons.length; i++) {
                        selecteditems.append("<div class ='icon' data-name ='"+ stricons[i] + "'>" +
                            "<i class='" +stricons[i] + " fa-4x'></i>" +
                            "<i class='fa fa-times-circle fa-2x actionremove'></i>" +
                            "<span>" + stricons[i].split(' ')[1].substr(3)+ "</span>" +
                            "</div>");
                    }
                }

            }
            $(document).ready(function () {
                $('#searchcontainer div.icon').each(function( index ) {
                    allicons.push({
                        "unicode": $(this).data('unicode').replace('&amp;', '&'),
                        "name": $(this).data('name'),
                        "type": $(this).data('type')
                    })
                });
                displayIcons(allicons);
                buildSelectedIcons();


            });

            $(document).on( "click", "#searchcontainer button#showmore", function(event) {
                event.preventDefault();
                nbdisplay += 50;
                if(nbdisplay > allicons.length){
                    nbdisplay = allicons.length;
                    $(this).hide();
                }
                displayIcons(allicons);
                $(this).blur();
            });

            $(document).on( "click", "#searchcontainer div.icon", function(event) {
                var stricons =  $("#selectedicons").val().split(';');
                if(!includes(stricons,$(this).data('type') + " fa-" +  $(this).data('name')) && stricons.length < 20){

                    var newval = null;
                    if($("#selectedicons").val() == ""){
                        newval =  $(this).data('type') + " fa-" +  $(this).data('name')
                    }else{
                        newval =  $("#selectedicons").val() + ";" + $(this).data('type') + " fa-" +  $(this).data('name')
                    }
                    $("#selectedicons").val(newval);

                    buildSelectedIcons();
                    $(this).append('<i class="fa fa-check-circle fa-2x actionselected"></i>');
                    $(this).closest('.icon').addClass('selected');
                }

            });

            $(document).on( "click", "#fontawesomepickercustom_setting #selected div.icon .actionremove", function(event) {
                var icondiv = $(this).closest("div");

                var stricons =  $("#selectedicons").val().split(';');
                for( var i = 0; i < stricons.length; i++){
                    if ( stricons[i] === icondiv.data('name')) {
                        stricons.splice(i, 1);
                    }
                }
                if(stricons.length > 0 ){
                    $("#selectedicons").val(stricons.join(';'));
                }else{
                    $("#selectedicons").val("");
                }
                $("#choices ." + icondiv.data('name').replace(' ', '.')).siblings('i').remove();
                $("#choices ." + icondiv.data('name').replace(' ', '.')).closest('.icon').removeClass('selected');
                buildSelectedIcons();
            });


            $(document).on( "input", "#searchcontainer #searchbar input", function(event) {
                if($(this).val() ==""){
                    displayIcons(allicons);
                }else{
                    var search = [];
                    for (var i = 0; i < allicons.length; i++) {
                        if(includes(allicons[i]["name"],$(this).val().toLowerCase())){
                            search.push(allicons[i]);
                        }
                        if(includes(allicons[i]["unicode"],$(this).val())){
                            search.push(allicons[i]);
                        }
                    }
                    uniqueSearch = search.filter(function(item, pos) {
                        return search.indexOf(item) == pos;
                    })
                    displayIcons(uniqueSearch);
                }

            });
        }
    }
});

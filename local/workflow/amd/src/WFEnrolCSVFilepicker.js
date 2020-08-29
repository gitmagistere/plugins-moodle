/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($) {
    function init(id, ajax_url, role){
        var hasBeenProcess = false;
        var previousFile = "";
        $("#fitem_id_"+role+"_userfile").find(".filepicker-filename").bind("DOMNodeInserted",function(){
            if(hasBeenProcess == true){return;}
            var fileurl = $("#fitem_id_"+role+"_userfile").find(".filepicker-filename").find("a").attr("href");
            if(!fileurl || previousFile == fileurl){return;}
            previousFile = fileurl;
            hasBeenProcess = true;

            $.ajax({
                type: "POST",
                url: ajax_url,
                data:{
                    url: fileurl,
                    courseid: id
                },
                datatype: "json",
                success:function(response){
                    var json = JSON.parse(response);
                    if(json.type == "simple"){
                        $(".panel-complex."+id).show();
                    }else{
                        $(".panel-complex."+id).hide();
                    }
                    $("[name=\'type\']").val(json.type);
                    $("#"+role+"-workflow-csv-enrol-warning").empty();
                    if(json.msg != ""){
                        $("#"+role+"-workflow-csv-enrol-warning").append(json.msg);
                    }
                    hasBeenProcess = false;
                },
                error:function(error){
                    console.log(error);
                }
            });
        });
    }

    return {
        init: function(id, ajax_url, role){
            init(id, ajax_url, role);
        }
    };
});
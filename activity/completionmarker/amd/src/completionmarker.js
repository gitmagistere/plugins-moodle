define(['jquery'], function($) {
    return {
        init: function (label_completed, label_uncompleted) {
            var markButton = $(".activity.completionmarker a.mark");
            var form = $(".activity.completionmarker form.togglecompletion");
            var formurl = form.attr("action");
            var onSuccessMark = function(data){
                var input = form.find("input[name=\'completionstate\']");
                
                if(input.val() == 1){
                    markButton.html(label_completed);
                    input.val(0);
                }else{
                    markButton.html(label_uncompleted);
                    input.val(1);
                }
                location.reload(true);
            };
            
            var onErrorMark = function(data){
                markButton.addClass("danger");
            };
            
            $(".activity.completionmarker a.mark").click(function(e){
                e.preventDefault();
                $.ajax(formurl, {
                    data: form.serialize(),
                    success: onSuccessMark,
                    error: onErrorMark 
                });             
            });
        }
    };
});

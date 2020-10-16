/* jshint ignore:start */
define(['jquery', 'jqueryui', 'local_magistere_offers/jquery.loadingModal'], function($) {
    function init(){
        $("a.pdf-catalog").on("click", function(e) {
            e.preventDefault();
            $("body").loadingModal({
                position: "auto",
                text: "Génération du catalogue en cours",
                color: "#fff",
                opacity: "0.7",
                backgroundColor: "rgb(0,0,0)",
                animation: "circle"
            });

            $.get(
                $(this).attr("href"),
                function(response) {
                    console.log(response);
                    if (response.error=="false")
                    {
                        location.href = response.url;
                        $("body").loadingModal("hide");
                        setTimeout(function(){$("body").loadingModal("destroy")},1000);
                    }else{
                        $("body").loadingModal("text","La génération du catalogue a échouée");
                        setTimeout(function(){$("body").loadingModal("hide")},2000);
                        setTimeout(function(){$("body").loadingModal("destroy")},3000);
                    }
                },
                "json"
            );
        });
    }

    return {
        init: function(){
            init();
        }
    };
});
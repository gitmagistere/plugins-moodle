/* jshint ignore:start */
define(['jquery', 'block_course_badges/jtable'], function($) {
   function init_show_more()
   {
       var nb_badge_show = 3;
       if ($( window ).width() < 1920 && $( window ).width() > 1441){
           nb_badge_show = 2;
       } else if ($( window ).width() < 1441 && $( window ).width() >= 1118){
           nb_badge_show = 1;
       } else if ($( window ).width() < 1118 && $( window ).width() >= 768){
           nb_badge_show = 0;
       } else if ($( window ).width() < 768 && $( window ).width() >= 425){
           nb_badge_show = 4;
       } else if ($( window ).width() < 425 && $( window ).width() > 320){
           nb_badge_show = 2;
       } else if ($( window ).width() <= 320) {
           nb_badge_show = 1;
       }
       nb_badge_show = nb_badge_show *2 + 1; // pour avoir 2 lignes affichés selon la résolution

       $('.badgescontent').each(function(i, v){
           if($(v).children(('.bcb_badge')).length <= nb_badge_show){
               $(v).children('.showmore').hide();
           };
           $(v).children(('.bcb_badge:gt('+(nb_badge_show-1)+')')).hide();
       });

       $('.showmore').click(function(){
           $(this).parent().children(('.bcb_badge')).show();
           $(this).remove();
       })
   }

   function initJtable(columns, table, ajaxbaseurl, defaultsorting)
   {
       var columns = $('input[name="'+columns+'"]').val();
       columns = JSON.parse(columns);
       if(columns.imgurl){
           columns.imgurl.display = function(data){
			   if (data.record.badgeurl) {
				   return '<a href="'+data.record.badgeurl+'"><img class="cb_overviewbadge" src="'+data.record.imgurl+'"/></a>';
			   }
			   return '<img class="cb_overviewbadge" src="'+data.record.imgurl+'"/>';
           }
       }

       if(columns.modname){
           columns.modname.display = function(data){
               var r = data.record;
               var res = '';
               for(var i=0; i<r.modnames.length; i++){
				   if (r.modnames[i].coursebadgeurl) {
					   res += '<a href="'+ r.modnames[i].coursebadgeurl +'">'+r.modnames[i].name+'</a><br/>';
				   } else {
					   res += r.modnames[i].name+'<br/>';
				   }
               }
               return res;
           }
       }

       if(columns.percent){
           columns.percent.display = function(data){
               var r = data.record;
               
              if (r.urlearnedbadge && r.urlselectedbadge) {
            	  return '<p><a href="'+ r.urlearnedbadge +'">'+r.badgeearnedcount+'</a>/<a href='+ r.urlselectedbadge +'>'+r.badgetotal+'</a> ('+r.badgepercent+'%)</p>';
              }
              return '<p>'+r.badgeearnedcount+'/'+r.badgetotal+' ('+r.badgepercent+'%)</p>';
           }
       }

       if(columns.groupnames){
           columns.groupnames.display = function(data){
               var r = data.record;
               var res = '';
               for(var i=0; i<r.groupnames.length; i++){
                   res += r.groupnames[i].name+'<br/>';
               }
               return res;
           }
       }

       if(columns.earnedbadges){
           columns.earnedbadges.display = function(data){
               var r = data.record;
               var res = '';
               for(var i=0; i<r.allearnedbadgeids.length; i++){
            	   if (r.allearnedbadgeids[i].badge_url) {
            		   res += '<a href="'+r.allearnedbadgeids[i].badge_url+'"><img class="cb_overviewbadge_mini" src="'+r.allearnedbadgeids[i].img_url+'"/></a>';
            	   } else {
            		   res += '<img class="cb_overviewbadge_mini" src="'+r.allearnedbadgeids[i].img_url+'"/>';
            	   }
                   
               }
               return res;
           }
       }

       if(columns.selectedbadges){
           columns.selectedbadges.display = function(data){
               var r = data.record;
               var res = '';
               for(var i=0; i<r.selectedbadges.length; i++){
            	   if (r.selectedbadges[i].badge_url) {
            		   res += '<a href="'+r.selectedbadges[i].badge_url+'"><img class="cb_overviewbadge_mini" src="'+r.selectedbadges[i].img_url+'"/></a>';
            	   } else {
            		   res += '<img class="cb_overviewbadge_mini" src="'+r.selectedbadges[i].img_url+'"/>';
            	   }
                   
               }
               return res;
           }
       }

       var selectionTable = $("#"+table).jtable({
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
               listAction: function (postData, jtParams) {
                   return $.Deferred(function ($dfd) {

                       postData = $('#mform1').serialize();

                       $.ajax({
                           url: ajaxbaseurl+'&si=' + jtParams.jtStartIndex + '&ps=' + jtParams.jtPageSize + '&so=' + jtParams.jtSorting,
                           type: "POST",
                           dataType: "json",
                           data: postData,
                           success: function (data) {
                               $dfd.resolve(data);
                           },
                           error: function () {
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

   function init_select_overview()
   {
       $('#mform1 select').change(function(){
           $("#results").jtable("load");
       })
   }

   function init_name_input(name)
   {
       var timer;
       $('#mform1 input[name="'+name+'"]').on('keyup', function(){
           clearTimeout(timer);
           timer = setTimeout(function(){
               $("#results").jtable("load");
           }, 500);

       })
   }

   function init_block_header()
   {
       $('div[class^="block_course_badges"] h3').click(function(){
           $(this).next().toggle();
       });
   }

   function init_custom_select(name)
   {
       name = '.'+name;

       var options = name+' .options';
       var optionselected = name+' .option-selected';

       $(name+' .option-selected-arrow, '+optionselected).click(function(){
           $(name+' .option-selected-arrow').toggleClass('expand');
           $(optionselected+', '+name+' .option-selected-arrow').toggleClass('focus');
           $(options).toggle();
       });

       $(name+' .option label').click(function(){

           var opt =  $(this).closest('.option');
           var content = opt.find('label').html();
           $(optionselected).html(content);

           $(options+' .option.selected').removeClass('selected');

           opt.addClass('selected');
           opt.closest('.options').find('input').removeAttr('checked')
           opt.find('input').attr('checked','checked');
           $(options).hide();

           $('#mform1').submit();

       });

       /*
        * Here we simulate a kind of blur event, but with multiple element
        * we compute the bounding box around the elements
        */
       $('body').click(function(e){
           if(!$(options).is(':visible')){
               return;
           }

           var mouse = {
               left: e.pageX,
               top: e.pageY
           };

           var pos_options = $(options).position();
           pos_options.left += $(options).offset().left;


           var pos_selected = $(optionselected).position();
           pos_selected.top += $(optionselected).offset().top;

           var options_width = $(options).width();
           var options_height = $(options).height();

           var min = {
               top: pos_selected.top,
               left: pos_options.left
           };

           var max = {
               top: min.top + options_height,
               left: min.left + options_width
           };


           if(mouse.top >= min.top && mouse.top <= max.top && mouse.left >= min.left && mouse.left <= max.left){
               return;
           }

           $(options).hide();
           $(optionselected+', '+name+' .option-selected-arrow').removeClass('focus');
       })
   }

   return {
       init_show_more: init_show_more,
       init_block_header: init_block_header,
       init_jtable: initJtable,
       init_select_overview: init_select_overview,
       init_name_input: init_name_input,
       init_custom_select: init_custom_select
   }
});
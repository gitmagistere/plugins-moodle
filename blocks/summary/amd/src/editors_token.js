/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($) {

	function init(course_id, user_id, sesskey, api_url, course_url, refresh_delay, editors) {

		var renew_token = function() {
			setTimeout(renew_token, refresh_delay*1000);
			$.ajax({
				url: api_url + '?action=renew&courseid=' + course_id + '&userid=' + user_id + '&sesskey=' + sesskey,
				type: 'POST',
				timeout: 5000,
			});
		};

		if (editors.length > 0) {
			$(editors).each(function(key, value) {
				$('#editors-list ul').append('<li>' + value + '</li>');
			});

			$( "#dialog-editors" ).dialog({
				resizable: false,
				width: 600,
				height: 'auto',
				maxHeight: 470,
				modal: true,
				closeOnEscape: false,
				classes: {
					"ui-dialog-titlebar": "ui-corner-all dialog-editors-titlebar",
				},
				buttons: [
					{
						id: "dialog-editors-cancel",
						text: "Annuler l'édition",
						click: function() {
							$.ajax({
								url: api_url + '?action=goback&courseid=' + course_id + '&userid=' + user_id + '&sesskey=' + sesskey,
								type: 'POST',
							}).done(function() {
								window.location.href = course_url;
							});

					    },
					},
					{
						id: "dialog-editors-edit",
						html: "Editer quand même <br/> (Risque de perte de données)",
						click: function() {
							$( this ).dialog( "close" );
	                    },
	            	},
	            ],
			});
		}

		$(document).ready(function() {
			renew_token();
		});
	}

	return {
        init: function(course_id, user_id, sesskey, api_url, course_url, refresh_delay, editors) {
            init(course_id, user_id, sesskey, api_url, course_url, refresh_delay, editors);
        }
    };

});



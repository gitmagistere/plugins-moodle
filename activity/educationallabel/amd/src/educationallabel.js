/* jshint ignore:start */
define(['jquery'], function($) {

    function init() {
    	$("#fitem_id_custom_title label").css("opacity", 0.5);
		if ($("#id_customize_title_cb").attr("checked") ) {
			$("#id_custom_title").removeAttr("disabled");
			$("#fitem_id_custom_title label").css("opacity", 1);
		}
		$("#id_customize_title_cb").change(function() {
			var customTitleField = $("#id_custom_title");
			var customTitleLabel = $("#fitem_id_custom_title label");
			if (this.checked) {
				customTitleField.removeAttr("disabled");
				customTitleLabel.css("opacity", 1);
			} else {
				customTitleField.attr("disabled", true);
				customTitleLabel.css("opacity", 0.5);
			}
		});
    }

	return {
		init: function() {
			init();
		}
	};

});
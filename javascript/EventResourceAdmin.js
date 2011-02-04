;(function($) {
	function toggleQuantity() {
		$("#Quantity").toggle($(this).val() == "Limited");
	}

	$("#Form_AddForm_Type").live("change", toggleQuantity);
	$("#Form_EditForm_Type").live("change", toggleQuantity);

	Behaviour.register({
		"#Form_AddForm_Type":  { initialize: toggleQuantity },
		"#Form_EditForm_Type": { initialize: toggleQuantity }
	});
})(jQuery);
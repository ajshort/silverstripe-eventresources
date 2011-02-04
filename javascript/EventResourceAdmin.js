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

	$("#tab-Root_Bookings").live("click", function() {
		$("#Form_EditForm_Bookings").fullCalendar("render");
		$("#Form_EditForm_Bookings").fullCalendar("option", "height", $("#Root_Bookings").height());
	});
})(jQuery);
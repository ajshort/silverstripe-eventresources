;(function($) {
	Behaviour.register({
		".event-resource-calendar": {
			initialize: function() {
				$(this).fullCalendar({
					header: {
						left: 'prev,next today',
						center: 'title',
						right: 'month,agendaWeek,agendaDay'
					},
					theme: true,
					height: $(this).parent().height(),
					events: $(this).attr("href"),
					windowResize: function() {
						$(this).fullCalendar("option", "height", $(this).parent().height());
					}
				});
			}
		}
	});
})(jQuery);
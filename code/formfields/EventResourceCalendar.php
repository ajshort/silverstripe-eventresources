<?php
/**
 * A readonly calendar that displays when a resource is booked.
 *
 * @package silverstripe-eventresources
 */
class EventResourceCalendar extends FormField {

	public static $url_handlers = array(
		'bookings' => 'bookings'
	);

	protected $parent;

	public function __construct($parent, $name) {
		$this->parent = $parent;
		parent::__construct($name);
	}

	public function FieldHolder() {
		Requirements::css(Director::protocol() . 'ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/base/jquery-ui.css');
		Requirements::css('eventresources/thirdparty/jquery-fullcalendar/fullcalendar.css');
		Requirements::javascript('eventresources/thirdparty/jquery-fullcalendar/fullcalendar.min.js');
		Requirements::javascript('eventresources/javascript/EventResourceCalendar.js');

		return $this->createTag('div', array(
			'id'    => $this->id(),
			'class' => 'event-resource-calendar ' . $this->extraClass(),
			'href'  => $this->Link('bookings')
		));
	}

	public function bookings($request) {
		$start = (int) $request->getVar('start');
		$end   = (int) $request->getVar('end');

		$result = array();
		$events = $this->parent->Events(sprintf(
			'"StartDate" BETWEEN \'%1$s\' AND \'%2$s\'
			OR "EndDate" BETWEEN \'%1$s\' AND \'%2$s\'
			OR "StartDate" < \'%1$s\' AND "EndDate" > \'%2$s\'',
			date('Y-m-d', $start), date('Y-m-d', $end)
		));

		if ($events) foreach ($events as $event) {
			$title = $event->EventTitle();

			if ($this->parent->Type != 'Single') {
				$title .= " ({$event->BookingQuantity} {$this->parent->Title})";
			}

			$result[] = array(
				'id'     => $event->ID,
				'title'  => $title,
				'start'  => strtotime($event->MicroformatStart()),
				'end'    => strtotime($event->MicroformatEnd()),
				'allDay' => (bool) $event->is_all_day,
				'url'    => Controller::join_links('admin/show', $event->EventID)
			);
		}

		return Convert::array2json($result);
	}

	/**
	 * @ignore
	 */
	public function saveInto() { /* nothing */ }

}
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
		$start  = (int) $request->getVar('start');
		$end    = (int) $request->getVar('end');
		$result = array();

		// First load standard non-recurring events that fall between the start
		// and end date.
		$events = $this->parent->Events(
			sprintf(
				'"CalendarEvent"."Recursion" = 0 AND (
					"StartDate" BETWEEN \'%1$s\' AND \'%2$s\'
					OR "EndDate" BETWEEN \'%1$s\' AND \'%2$s\'
					OR ("StartDate" < \'%1$s\' AND "EndDate" > \'%2$s\')
				)',
				date('Y-m-d', $start), date('Y-m-d', $end)),
			null,
			'INNER JOIN "CalendarEvent" ON "CalendarEvent"."ID" = "CalendarDateTime"."EventID"');

		// Then load every recurring event and see if they fall between the start
		// and end.
		$recurring = $this->parent->Events(
			sprintf(
				'"CalendarEvent"."Recursion" = 1
				AND ("EndDate" IS NULL OR "EndDate" > \'%s\')
				AND ("StartDate" IS NULL OR "StartDate" < \'%s\')',
				date('Y-m-d', $start), date('Y-m-d', $end)
			),
			null,
			'INNER JOIN "CalendarEvent" ON "CalendarEvent"."ID" = "CalendarDateTime"."EventID"');

		// Now loop through each day in the specified date range, and check
		// each recurring date to see if it occurs on that day. Note that
		// recurring events always start and end on the same day.
		if ($recurring) foreach ($recurring as $datetime) {
			$counter = $start;
			$days    = 0;

			while ($counter <= $end) {
				if ($counter > strtotime($datetime->EndDate)) {
					break;
				}

				if ($datetime->Event()->recursionHappensOn($counter)) {
					$_datetime = clone $datetime;

					$_datetime->ID        = -1;
					$_datetime->StartDate = date('Y-m-d', $counter);
					$_datetime->EndDate   = date('Y-m-d', $counter);

					$events->push($_datetime);
				}

				$counter = sfTime::add($counter, 1, sfTime::DAY);
				$days++;
			}
		}

		foreach ($events as $event) {
			$title = $event->EventTitle();

			if ($this->parent->Type != 'Single') {
				$title .= " ({$event->BookingQuantity} {$this->parent->Title})";
			}

			$result[] = array(
				'id'     => $event->ID,
				'title'  => $title,
				'start'  => $event->getStartTimestamp(),
				'end'    => $event->getEndTimestamp(),
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
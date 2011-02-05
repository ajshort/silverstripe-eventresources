<?php
/**
 * A resource that can be booked out for the duration of an event.
 *
 * @package silverstripe-eventresources
 */
class EventResource extends DataObject {

	public static $db = array(
		'Title'       => 'Varchar(255)',
		'Description' => 'Text',
		'Type'        => 'Enum("Single, Limited, Unlimited")',
		'Quantity'    => 'Int'
	);

	public static $belongs_many_many = array(
		'Events' => 'CalendarDateTime'
	);

	public static $summary_fields = array(
		'Title',
		'Description',
		'Type'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('Events');

		$fields->dataFieldByName('Type')->setSource(array(
			'Single'    => 'There is one resource available',
			'Limited'   => 'There are limited items of this resource available',
			'Unlimited' => 'There is no limit on the quantity of this resource'
		));

		if ($this->isInDb()) {
			$fields->addFieldToTab('Root.Bookings', new EventResourceCalendar(
				$this, 'Bookings'
			));
		}

		return $fields;
	}

	/**
	 * @return FieldSet
	 */
	public function getCmsExtraFields($time) {
		switch ($this->Type) {
			case 'Single':
				$quantity = new ReadonlyField(
					'OneAvailable', 'Quantity', '(One available)');
				break;
			case 'Limited':
				$quantity = new DropdownField(
					'BookingQuantity',
					'Quantity',
					ArrayLib::valuekey(range(1, $this->getAvailableForEvent($time))),
					null, null, true);
				break;
			case 'Unlimited':
				$quantity = new NumericField('BookingQuantity', 'Quantity');
				break;
		}

		return new FieldSet(
			new ReadonlyField('Title', 'Resource title', $this->Title),
			$quantity
		);
	}

	public function validate() {
		$result = parent::validate();

		if ($this->Type == 'Limited' && !$this->Quantity) {
			$result->error('You must enter a quantity for limited resources.');
		}

		return $result;
	}

	/**
	 * Returns the number of this resource that are not booked during an event
	 * time.
	 *
	 * @param  CalendarDateTime $time
	 * @return bool|int
	 */
	public function getAvailableForEvent($time) {
		if ($this->Type == 'Unlimited') {
			return true;
		}

		$start = $time->getStartTimestamp();
		$end   = $time->getEndTimestamp();

		// First just get simple non-recurring bookings, often this will be
		// enough to check if a resource is available.
		$dateFilter = sprintf(
			'"StartDate" BETWEEN \'%1$s\' AND \'%2$s\'
			OR "EndDate" BETWEEN \'%1$s\' AND \'%2$s\'
			OR ("StartDate" < \'%1$s\' AND "EndDate" > \'%2$s\')',
			date('Y-m-d', $start), date('Y-m-d', $end)
		);
		$filter = "\"CalendarEvent\".\"Recursion\" = 0 AND \"CalendarDateTimeID\" <> {$time->ID} AND ($dateFilter)";

		$bookings = $this->Events(
			$filter,
			null,
			'INNER JOIN "CalendarEvent" ON "CalendarEvent"."ID" = "CalendarDateTime"."EventID"');
		$bookings = $bookings->toArray('ID');

		// Since the event calendar doesn't use a proper date time storage, we
		// need to manually filter events again here.
		foreach ($bookings as $id => $booking) {
			if ($booking->getEndTimestamp() < $start || $booking->getStartTimestamp() > $end) {
				unset($bookings[$id]);
			}
		}

		if ($bookings && $this->Type == 'Single') {
			return false;
		}

		// Now also load all the recurring events, and check if they occur on
		// this day.
		$recurring = $this->Events(
			sprintf(
				'"CalendarDateTimeID" <> %d
				AND "CalendarEvent"."Recursion" = 1
				AND ("EndDate" IS NULL OR "EndDate" > \'%s\')
				AND ("StartDate" IS NULL OR "StartDate" < \'%s\')',
				$time->ID, date('Y-m-d', $start), date('Y-m-d', $end)
			),
			null,
			'INNER JOIN "CalendarEvent" ON "CalendarEvent"."ID" = "CalendarDateTime"."EventID"');

		// Now loop through each day this event runs on, and check if any of the
		// events fall on it. If they do just push them onto the bookings set.
		foreach ($recurring as $datetime) {
			$counter = $start;

			while ($counter <= $end) {
				if ($counter > strtotime($datetime->EndDate)) {
					break;
				}

				if ($datetime->Event()->recursionHappensOn($counter)) {
					$bookings[$datetime->ID] = $datetime;
					break;
				}

				$counter = sfTime::add($counter, 1, sfTime::DAY);
			}
		}

		if ($bookings && $this->Type == 'Single') {
			return false;
		}

		if (!count($bookings)) {
			return $this->Type == 'Limited' ? (int) $this->Quantity : true;
		}

		$quantity = (int) $this->Quantity;

		foreach ($bookings as $booking) {
			$quantity -= $booking->BookingQuantity;
		}

		return $quantity > 0 ? $quantity : false;
	}

	/**
	 * @return string
	 */
	public function Summary() {
		$summary = $this->Title;

		if ($this->BookingQuantity) $summary .= " ({$this->BookingQuantity})";
		if ($this->Description)     $summary .= "<br /><span>{$this->Description}</span>";

		return $summary;
	}

}
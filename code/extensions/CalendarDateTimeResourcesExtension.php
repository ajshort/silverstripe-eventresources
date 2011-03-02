<?php
/**
 * An extension to allow a user to book resources for an event time.
 *
 * @package silverstripe-eventresources
 */
class CalendarDateTimeResourcesExtension extends DataObjectDecorator {

	public function extraStatics() {
		return array(
			'many_many' => array(
				'Resources' => 'EventResource'),
			'many_many_extraFields' => array(
				'Resources' => array(
					'BookingQuantity' => 'Int'))
		);
	}

	public function updateDateTimeCMSFields(FieldSet $fields) {
		if (!$this->owner->isInDB()) {
			$fields->addFieldToTab('Root.Resources', new LiteralField(
				'BookResourcesNote',
				'<p>You can book resources after you save for the first time.</p>'
			));
			return;
		}

		$fields->addFieldToTab('Root.Resources', $res = new ManyManyPickerField(
			$this->owner,
			'Resources',
			'Booked Resources For This Event',
			array(
				'ExtraFields'        => 'getCmsExtraFields',
				'ShowPickedInSearch' => false,
				'PopupHeight'        => 350
			))
		);
		$res->getSearchField()->setOption('FilterCallback', array(
			$this->owner, 'filterEventResource'
		));
	}

	/**
	 * If the event times have changed, it checks to make sure that the resources
	 * are still available. If not, it throws a validation exception.
	 *
	 * @throws ValiationException
	 */
	public function onBeforeWrite() {
		$changed = $this->owner->getChangedFields();
		$check   = array('StartDate', 'StartTime', 'EndDate', 'EndTime', 'is_all_day');

		if (!array_intersect_key(array_flip($check), $changed)) return;

		foreach ($this->owner->Resources() as $resource) {
			if ($resource->Type == 'Unlimited') {
				continue;
			}

			// If this is a recurring event, then check all other non-recurring
			// bookings for this resource to see if any conflict.
			if ($this->owner->Event()->Recursion) {
				$bookings = $resource->Events(
					sprintf(
						'"CalendarEvent"."Recursion" = 0 AND "CalendarDateTime"."ID" <> %d',
						$this->owner->ID),
					null,
					'INNER JOIN "CalendarEvent" ON "CalendarEvent"."ID" = "CalendarDateTime"."EventID"');

				foreach ($bookings as $booking) {
					$counter = $booking->getStartTimestamp();
					$end     = $booking->getEndTimestamp();

					// Loop through each day the other booking falls on, to see
					// if it could cause a conflict.
					while ($counter < $end) {
						if ($this->owner->Event()->recursionHappensOn($counter)) {
							$this->checkResourceAvailability($resource, $counter);
						}
						$counter = sfTime::add($counter, 1, sfTime::DAY);
					}
				}
			} else {
				$this->checkResourceAvailability($resource);
			}
		}
	}

	/**
	 * @throws ValidationException
	 */
	protected function checkResourceAvailability($resource, $ts = null) {
		$datetime = $this->owner;
		$date     = null;

		if ($ts) {
			$datetime = clone $datetime;

			$datetime->StartDate = date('Y-m-d', $ts);
			$datetime->EndDate   = date('Y-m-d', $ts);

			$date = ' on ' . $datetime->obj('StartDate')->Nice();
		}

		if ($resource->Type == 'Limited') {
			$avail = $resource->getAvailableForEvent($datetime);

			if ($resource->BookingQuantity > $avail) {
				throw new ValidationException(new ValidationResult(false, sprintf(
					'Changing the date of this event means there is only %d '    .
					'of the "%s" resource available%s, whereas you have '        .
					'requested %d. Please either select fewer of this resource ' .
					'from the "Resources" tab, or change the date to one where ' .
					'there are more available.',
					$avail, $resource->Title, $date, $resource->BookingQuantity
				)));
			}
		} elseif (!$resource->getAvailableForEvent($datetime)) {
			throw new ValidationException(new ValidationResult(false, sprintf(
				'Changing the date of this event means the "%s" resource ' .
				'is no longer available%s. Please either remove this '       .
				'resource from the "Resources" tab, or change the date '   .
				'to one where the resource has not already been booked.',
				$resource->Title, $date
			)));
		}
	}

	/**
	 * @return bool
	 */
	public function filterEventResource($resource) {
		return (bool) $resource->getAvailableForEvent($this->owner);
	}

}
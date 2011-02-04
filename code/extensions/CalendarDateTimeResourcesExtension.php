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
				'ShowPickedInSearch' => false
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
			if (!$resource->getAvailableForEvent($this->owner)) {
				throw new ValidationException(new ValidationResult(false, sprintf(
					'Changing the date of this event means the "%s" resource ' .
					'is no longer available. Please either remove this '       .
					'resource from the "Resources" tab, or change the date '   .
					'to one where the resource has not already been booked.',
					$resource->Title
				)));
			}
		}
	}

	/**
	 * @return bool
	 */
	public function filterEventResource($resource) {
		return (bool) $resource->getAvailableForEvent($this->owner);
	}

}
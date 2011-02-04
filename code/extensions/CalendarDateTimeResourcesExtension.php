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

		$fields->addFieldToTab('Root.Resources', new ManyManyPickerField(
			$this->owner,
			'Resources',
			'Booked Resources For This Event',
			array(
				'ExtraFields' => 'getCmsExtraFields'
			))
		);
	}

}
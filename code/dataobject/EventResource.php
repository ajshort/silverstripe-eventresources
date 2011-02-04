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

	public static $summary_fields = array(
		'Title',
		'Description',
		'Type'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->dataFieldByName('Type')->setSource(array(
			'Single'    => 'There is one resource available',
			'Limited'   => 'There are limited items of this resource available',
			'Unlimited' => 'There is no limit on the quantity of this resource'
		));

		return $fields;
	}

	public function validate() {
		$result = parent::validate();

		if ($this->Type == 'Limited' && !$this->Quantity) {
			$result->error('You must enter a quantity for limited resources.');
		}

		return $result;
	}

}
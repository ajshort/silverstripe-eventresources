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

		$fields->dataFieldByName('Type')->setSource(array(
			'Single'    => 'There is one resource available',
			'Limited'   => 'There are limited items of this resource available',
			'Unlimited' => 'There is no limit on the quantity of this resource'
		));

		return $fields;
	}

	/**
	 * @return FieldSet
	 */
	public function getCmsExtraFields() {
		switch ($this->Type) {
			case 'Single':
				$quantity = new ReadonlyField(
					'OneAvailable', 'Quantity', '(One available)');
				break;
			case 'Limited':
				$quantity = new DropdownField(
					'BookingQuantity',
					'Quantity',
					ArrayLib::valuekey(range(1, $this->Quantity)),
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
	 * @return string
	 */
	public function Summary() {
		$summary = $this->Title;

		if ($this->BookingQuantity) $summary .= " ({$this->BookingQuantity})";
		if ($this->Description)     $summary .= "<br /><span>{$this->Description}</span>";

		return $summary;
	}

}
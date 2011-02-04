<?php
/**
 * @package silverstripe-eventresources
 */

if (!class_exists('ItemSetField')) {
	throw new Exception('The Event Resources module requires the Item Set Field module');
}

Object::add_extension('CalendarDateTime', 'CalendarDateTimeResourcesExtension');
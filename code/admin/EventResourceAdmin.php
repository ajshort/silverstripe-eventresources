<?php
/**
 * Allows administrators to create event resources and view when they are being
 * used.
 *
 * @package silverstripe-eventresources
 */
class EventResourceAdmin extends ModelAdmin {

	public static $title       = 'Event Resources';
	public static $menu_title  = 'Event Resources';
	public static $url_segment = 'event-resources';

	public static $managed_models  = 'EventResource';
	public static $model_importers = array();

	public function init() {
		parent::init();
		Requirements::javascript('eventresources/javascript/EventResourceAdmin.js');
	}

}
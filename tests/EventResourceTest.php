<?php
/**
 * Tests for the resource conflict detection.
 *
 * @package    silverstripe-eventresources
 * @subpackage tests
 */
class EventResourceTest extends SapphireTest {

	public static $fixture_file = 'eventresources/tests/EventResourceTest.yml';

	/**
	 * @covers EventResource::getAvailableForEvent()
	 */
	public function testGetAvailableForEvent() {
		$first  = $this->objFromFixture('CalendarDateTime', 'first');
		$second = $this->objFromFixture('CalendarDateTime', 'second');

		$chairs     = $this->objFromFixture('EventResource', 'chairs');
		$projectors = $this->objFromFixture('EventResource', 'projectors');
		$auditorium = $this->objFromFixture('EventResource', 'auditorium');

		// First check that all resources are available when there are no
		// conflicts.
		$this->assertEquals(true, $chairs->getAvailableForEvent($first));
		$this->assertEquals(5, $projectors->getAvailableForEvent($first));
		$this->assertEquals(true, $auditorium->getAvailableForEvent($first));

		$this->assertEquals(true, $chairs->getAvailableForEvent($second));
		$this->assertEquals(5, $projectors->getAvailableForEvent($second));
		$this->assertEquals(true, $auditorium->getAvailableForEvent($second));

		// Then register some resources to the first event.
		$first->Resources()->add($chairs, array('BookingQuantity' => 500));
		$first->Resources()->add($projectors, array('BookingQuantity' => 3));
		$first->Resources()->add($auditorium);

		// Check they aren't available for the second event.
		$this->assertEquals(true, $chairs->getAvailableForEvent($second));
		$this->assertEquals(2, $projectors->getAvailableForEvent($second));
		$this->assertEquals(false, $auditorium->getAvailableForEvent($second));

		// Check they're still available for the first event.
		$this->assertEquals(true, $chairs->getAvailableForEvent($first));
		$this->assertEquals(5, $projectors->getAvailableForEvent($first));
		$this->assertEquals(true, $auditorium->getAvailableForEvent($first));

		// Now make the projectors complete unavailable.
		$first->Resources()->remove($projectors);
		$first->Resources()->add($projectors, array('BookingQuantity' => 5));
		$this->assertEquals(false, $projectors->getAvailableForEvent($second));
	}

	/**
	 * @covers CalendarDateTimeResourcesExtension::onBeforeWrite()
	 */
	public function testChangingDateToInvalidThrowsError() {
		$first      = $this->objFromFixture('CalendarDateTime', 'first');
		$third      = $this->objFromFixture('CalendarDateTime', 'third');
		$auditorium = $this->objFromFixture('EventResource', 'auditorium');

		$first->Resources()->removeAll();
		$third->Resources()->removeAll();

		// First set up a conflicting scenario on different dates.
		$first->Resources()->add($auditorium);
		$third->Resources()->add($auditorium);

		// Then change the dates to overlay and catch the exception.
		$this->setExpectedException('ValidationException');
		$third->StartDate = '2011-01-02';
		$third->write();
	}

}
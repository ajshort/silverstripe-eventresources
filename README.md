# SilverStripe Event Resources Module

The event resources module allows you to create items which can be booked to use
at a calendar event. When a resource has been booked out for a certain time it
can no longer be used.

This is useful for controlling access to rooms, or other resources needed to run
events.

A new "Event Resources" administration panel is created in order to let you create
and manage resources that are available, as well as view a calendar of when they
have been booked by events. There are three types of resources:

*  Single resources - These are resources that may only be used by one event at
   a time, for example rooms.
*  Limited resources - These are resources that have a specific quantity
   available, and only that many can be used at one time.
*  Unlimited resources - These are resources which are practically unlimited,
   but still need to be kept track of for provisioning (for example chairs).

Once you have created one or more resources, you can associate them to event
times. If you edit a Calendar Event date time, you will note that a new
"Resources" tab has been added. You can use this tab to book resources for
the duration of the event.

If a resource is not shown in the list of resources, it probably means that all
available resources have been booked for part of the event. You can use the
event resources admin panel to check which event booked it.

Note: Recurring events currently do not work with resource booking.

## Maintainer Contacts
*  Andrew Short (<andrew@silverstripe.com.au>)

## Requirements
*  SilverStripe 2.4+
*  The [Event Calendar Module](http://silverstripe.org/event-calendar)
*  The [Item Set Field Module](http://silverstripe.org/ItemSetField)

## Installation

*  Place this directory in the root of your SilverStripe installation. It should
   be called "eventresources".
*  Rebuild your database by visiting http://example.com/dev/build.
*  The CMS should now have a new tab named "Event Resources".

## Project Links
*  [GitHub Project Page](https://github.com/ajshort/silverstripe-eventresources)
*  [Issue Tracker](https://github.com/ajshort/silverstripe-eventresources/issues)
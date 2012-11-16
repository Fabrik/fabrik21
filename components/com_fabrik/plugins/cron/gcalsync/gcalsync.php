<?php

/**
* A cron task to email records to a give set of users
* @package Joomla
* @subpackage Fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');


class FabrikModelGcalsync extends fabrikModelPlugin {

	var $_counter = null;

	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}


	function canUse()
	{
		return true;
	}

	function _getGcalShortId($long_id )
	{
		$matches = array();
		if (preg_match('#/(\w+)$#', $long_id, $matches)) {
			return $matches[1];
		}
		else {
			return $long_id;
		}
	}

	/**
	 * Override class methods
	 */

	function requiresTableData() {
		/* whether cron should automagically load table data */
		return true;
	}

	function requiresPrefilters() {
		/* if requiresTableData(), should prefilters be applied */
		return false;
	}

	/**
	 * do the plugin action
	 *
	 */
	function process(&$data, &$tableModel)
	{
		//jimport('joomla.mail.helper');
		$params =& $this->getParams();
		$app =& Jfactory::getApplication();
		$gcal_url = $params->get('gcal_sync_gcal_url');
		$matches = array();
		// this matches a standard GCal URL, found under the Google "Calender Details" tab, using the XML button.
		// It should match any form, for public or private ...
		// http://www.google.com/calendar/feeds/hugh.messenger%40gmail.com/public/basic
		// http://www.google.com/calendar/feeds/hugh.messenger%40gmail.com/private-3081eca2b0asdfasdf8f106ea6f63343056/basic
		$gcal_url = str_replace('/basic', '/full', $gcal_url);
		$gcal_url = preg_replace('#/private-\w+/#', '/private/', $gcal_url);
		if (preg_match('#feeds/(.*?)/(\w+-\w+|\w+)/(\w+)#', $gcal_url, $matches)) {
			// grab the bits of the URL we need for the Zend framework call
			$gcal_user = $matches[1];
			$gcal_visibility = $matches[2];
			$gcal_projection = $matches[3];
			$gcal_email = urldecode($gcal_user);

			// grab the table model and find table name and PK
			$table =& $tableModel->getTable();
			$table_name 		= $table->db_table_name;
			$primary_key = $table->db_primary_key;
			$primary_key_element_long = FabrikString::safeColNameToArrayKey($table->db_primary_key);

			// for now, we have to read the table ourselves.  We can't rely on the $data passed to us
			// because it can be filtered, and we need to see all records to know if the GCal events
			// already exist in the table
			/*
			$mydata = array();
			$db = JFactory::getDBO();
			$db->setQuery("SELECT * FROM $table_name");
			$mydata[0] = $db->loadObjectList();
			*/
			$db = JFactory::getDBO();
			$mydata =& $data;

			// grab all the field names to use
			$gcal_label_element_long = $params->get('gcal_sync_label_element');
			$gcal_label_element = FabrikString::shortColName($gcal_label_element_long);
			$gcal_desc_element_long = $params->get('gcal_sync_desc_element');
			$gcal_desc_element = FabrikString::shortColName($gcal_desc_element_long);
			$gcal_start_date_element_long = $params->get('gcal_sync_startdate_element');
			$gcal_start_date_element = FabrikString::shortColName($gcal_start_date_element_long);
			$gcal_end_date_element_long = $params->get('gcal_sync_enddate_element');
			$gcal_end_date_element = FabrikString::shortColName($gcal_end_date_element_long);
			$gcal_id_element_long = $params->get('gcal_sync_id_element');
			$gcal_id_element = FabrikString::shortColName($gcal_id_element_long);
			$gcal_userid_element_long = $params->get('gcal_sync_userid_element');
			$gcal_userid_element = FabrikString::shortColName($gcal_userid_element_long);

			// sanity check, make sure required elements have been specified
			if (empty($gcal_label_element_long) || empty($gcal_start_date_element_long) || empty($gcal_end_date_element_long) || empty($gcal_id_element_long)) {
				JError::raiseNotice(500, 'missing gcal data');
				return;
			}

			// if they selected a User ID element to use, see if we can find a J! user with matching email to this feed's owner
			$our_userid = 0;
			if ($gcal_userid_element_long) {
				$db->setQuery("SELECT id FROM ".$db->nameQuote('#__users')." WHERE ".$db->nameQUote('email')." = ".$db->Quote($gcal_email));
				$our_userid = $db->loadResult();
				// better make sure it's not NULL, in case underlying column is NOT NULL
				if (empty($our_userid)) {
					$our_userid = 0;
				}
			}

			// include the Zend stuff
			$path = JPATH_SITE.DS.'libraries';
			set_include_path(get_include_path() . PATH_SEPARATOR . $path);
			$path = get_include_path();
			require_once 'Zend/Loader.php';
			Zend_Loader::loadClass('Zend_Gdata');
			Zend_Loader::loadClass('Zend_Uri_Http');
			// Won't need these loaded until we add sync'ing events back to Google
			//Zend_Loader::loadClass('Zend_Gdata_AuthSub');
			//Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
			Zend_Loader::loadClass('Zend_Gdata_Calendar');

			// see if they want to sync to gcal, and provided a login
			$gcal_sync_upload = $params->get('gcal_sync_upload_events', 'from');
			if ($gcal_sync_upload == 'both' || $gcal_sync_upload == 'to') {
				Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
				$email = $params->get('gcal_sync_login', '');
				$passwd = $params->get('gcal_sync_passwd', '');
				try {
				   $client = Zend_Gdata_ClientLogin::getHttpClient($email, $passwd, 'cl');
				} catch (Zend_Gdata_App_CaptchaRequiredException $cre) {
					echo 'URL of CAPTCHA image: ' . $cre->getCaptchaUrl() . "\n";
					echo 'Token ID: ' . $cre->getCaptchaToken() . "\n";
					return;
				} catch (Zend_Gdata_App_AuthException $ae) {
					echo 'Problem authenticating: ' . $ae->exception() . "\n";
					return;
				}
				$gdataCal = new Zend_Gdata_calendar($client);
			}
			else {
				$gdataCal = new Zend_Gdata_calendar();
			}

			// set up and execute the call to grab the feed from google
			$query = $gdataCal->newEventQuery();
			$query->setUser($gcal_user);
			$query->setVisibility($gcal_visibility);
			$query->setProjection($gcal_projection);
			$eventFeed = $gdataCal->getCalendarEventFeed($query);
			// build an array of the events from the feed, indexed by the Google ID
			$event_ids = array();
			foreach ($eventFeed as $key => $event) {
				$short_id = $this->_getGcalShortId($event->id->text);
				$gcal_event_ids[$short_id] =& $eventFeed[$key];
			}

			// run through our table data, and build an array of our events indexed by the Google ID
			// (of course not all events may have a Google ID)
			$our_event_ids = array();
			$our_upload_ids = array();
			foreach ($mydata as $gkey => $group) {
				if (is_array($group)) {
					foreach ($group as $rkey => $row) {
						if ($row->$gcal_id_element_long) {
							$our_event_ids[$row->$gcal_id_element_long] =& $mydata[$gkey][$rkey];
						}
						else {
							$our_upload_ids[] =& $mydata[$gkey][$rkey];
						}
					}
				}
			}

			// now go through the google events id's, and process the ones which aren't in our table.
			$our_event_adds = array();
			foreach ($gcal_event_ids as $id => $event) {
				if (!array_key_exists($id, $our_event_ids)) {
					// we don't have the ID, so add the event to our table
					$row = array();
					$row[$gcal_start_date_element_long] = strftime('%Y-%m-%d %H:%M:%S', strtotime($event->when[0]->startTime));
					if ($gcal_end_date_element_long) {
						$row[$gcal_end_date_element_long] = strftime('%Y-%m-%d %H:%M:%S', strtotime($event->when[0]->endTime));
					}
					$row[$gcal_label_element_long] = $event->title->text;
					if ($gcal_desc_element_long) {
						$row[$gcal_desc_element_long] = $event->content->text;
					}
					$row[$gcal_id_element_long] = $id;
					if ($gcal_userid_element_long) {
						$row[$gcal_userid_element_long] = $our_userid;
					}
					$tableModel->storeRow($row, 0);
				}
				$our_event_adds[$id] = $row;
			}
			$app->enqueueMessage(count($our_event_adds) . ' events added ');
			// if upload syncing (from us to gcal) is enabled ...
			if ($gcal_sync_upload == 'both' || $gcal_sync_upload == 'to') {
				// Grab the tzOffset.  Note that gcal want +/-XX (like -06)
				// but J! gives us +/-X (like -6) so we sprintf it to the right format
				$config =& JFactory::getConfig();
				$tzOffset = (int)$config->getValue('config.offset');
				$tzOffset = sprintf('%+03d', $tzOffset);
				// loop thru the array we built earlier of events we have that aren't in gcal
				$uploadCount = 0;
				foreach ($our_upload_ids as $id => $event) {
					// skip if a userid element is specified, and doesn't match the owner of this gcal
					if ($gcal_userid_element_long) {
						if ($event->$gcal_userid_element_long != $our_userid) {
							continue;
						}
					}
					// now start building the gcal event structure
					$newEvent = $gdataCal->newEventEntry();
					$newEvent->title = $gdataCal->newTitle($event->$gcal_label_element_long);
					if ($gcal_desc_element_long) {
						$newEvent->content = $gdataCal->newContent($event->$gcal_desc_element_long);
					}
					else {
						$newEvent->content = $gdataCal->newContent($event->$gcal_label_element_long);
					}
					$when = $gdataCal->newWhen();

					// grab the start date, apply the tx offset, and format it for gcal
					$start_date = JFactory::getDate($event->$gcal_start_date_element_long);
					$start_date->setOffset($tzOffset);
					$start_fdate = $start_date->toFormat('%Y-%m-%d %H:%M:%S');
					$date_array = explode(' ',$start_fdate);
					$when->startTime = "{$date_array[0]}T{$date_array[1]}.000{$tzOffset}:00";

					// we have to provide an end date for gcal, so if we don't have one,
					// default it to start date + 1 hour
					if (empty($gcal_end_date_element_long) || empty($event->$gcal_end_date_element_long) || $event->$gcal_end_date_element_long == '0000-00-00 00:00:00') {
						$startstamp = strtotime($event->$gcal_start_date_element_long);
						$endstamp = $startstamp + (60 * 60);
						$event->$gcal_end_date_element_long = strftime('%Y-%m-%d %H:%M:%S', $endstamp);
					}
					// grab the end date, apply the tx offset, and format it for gcal
					$end_date = JFactory::getDate($event->$gcal_end_date_element_long);
					$end_date->setOffset($tzOffset);
					$end_fdate = $end_date->toFormat('%Y-%m-%d %H:%M:%S');
					$date_array = explode(' ',$end_fdate);
					$when->endTime = "{$date_array[0]}T{$date_array[1]}.000{$tzOffset}:00";
					$newEvent->when = array($when);

					// fire off the insertEvent to gcal, catch any errors
					try {
						$retEvent = $gdataCal->insertEvent($newEvent, $gcal_url);
					}
					catch (Zend_Gdata_App_HttpException $he) {
						$errStr = 'Problem adding event: ' . $he->getRawResponseBody() . "\n";
						continue;
					}
					$uploadCount ++;
					// insertEvent worked, so grab the gcal ID from the returned event data,
					// and update our event record with the short version of the ID
					$gcal_id = $this->_getGcalShortId($retEvent->id->text);

					$our_id = $event->__pk_val;
					$db->setQuery("
						UPDATE $table_name
						SET $gcal_id_element = ".$db->Quote($gcal_id)."
						WHERE $primary_key = ".(int)$our_id);
					$db->query();
				}
				$app->enqueueMessage($uploadCount . ' events uploaded to GCal');
			}
		} else {
			JError::raiseNotice(500, 'Incorrect url');
		}
	}

	/**
	 * show a new for entering the form actions options
	 */

	function renderAdminSettings()
	{
		//JHTML::stylesheet('fabrikadmin.css', 'administrator/components/com_fabrik/views/');
		$this->getRow();
		$pluginParams =& $this->getParams();

		$document =& JFactory::getDocument();
		?>
		<div id="page-<?php echo $this->_name;?>" class="pluginSettings" style="display:none">
		<b>NOTES</b>
		<ul>
			<li>
				<b>This plugin requires the ZendGdata library be installed on your server.</b> See the README file that came with this plugin.
			</li>
			<li>
				At the moment, this plugin does not handle repeating event data.  It just uses the first start/end date occurences.
			<li>
				To sync more than one GCal into a single Fabrik cal, just add multiple instances of this plugin, configured accordingly.
			</li>
			<li>
				To force a sync (without having to wait for the next scheduled run), visit any page on your site with &amp;fabrikcron_run=1 appended to the URL.  Note that this will run all your scheduled tasks, not just the GCal ones.
			</li>
		</ul>
		<?php
			// @TODO - work out why the language diddly doesn't work here, so we can make the above notes translateable?
			//echo JText::_('GCALNOTES');
			echo $pluginParams->render('params');
			echo $pluginParams->render('params', 'fields');
			?>
		</div>
		<?php
		return;
	}

}
?>
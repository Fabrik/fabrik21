<?php
/**
 * Form email plugin
 * @package Joomla
 * @subpackage Fabrik
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-form.php');

class FabrikModelfabrikgcal extends FabrikModelFormPlugin {

	/** @param object element model **/
	var $_elementModel = null;
	
	var $elementModels = array();

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * process the plugin, called when form is submitted
	 *
	 * @param object $params
	 * @param object form model
	 * @returns bol
	 */

	function onAfterProcess($params, &$formModel)
	{
		$this->_process($params, $formModel);
		//stop default redirect from occuring
		return false;
	}

	protected function buildModel($id)
	{
		$this->formModel = JModel::getInstance('form', 'fabrikModel');
		$this->formModel->setId($id);
		$form =& $this->formModel->getForm();
		$row = $this->getRow();
		$row->attribs = $form->attribs;
		return $this->formModel;
	}

	/**
	* get an element model
	* @return object element model
	*/
	
	private function getElementModel()
	{
		if (!isset($this->_elementModel)) {
			$this->_elementModel =& JModel::getInstance('element','FabrikModel');
		}
		return $this->_elementModel;
	}
	
	/**
	 * get the element full name for the element id
	 * @param plugin params
	 * @param int element id
	 * @return string element full name
	 */
	
	private function getFieldName($params, $pname)
	{
		$elementModel =& $this->getFieldModel($params, $pname);
		return $elementModel->getFullName();
	}
	
	/**
	 * Get the element table
	 * @param object $params
	 * @param string $pname
	 */
	
	protected function getFieldElement($params, $pname)
	{
		$elementModel =& $this->getFieldModel($params, $pname);
		$element =& $elementModel->getElement(true);
		return $element;
	}
	
	/**
	 * Get the element model
	 * @param object $params
	 * @param string $pname
	 */
	
	protected function getFieldModel($params, $pname)
	{
		if (array_key_exists($pname, $this->elementModels)) {
			return $this->elementModels[$pname];
		}
		$el = $params->get($pname);
		$elementModel =& $this->getElementModel();
		$elementModel->setId($params->get($pname));
		$elementModel->getElement(true);
		$this->elementModels[$pname] = clone($elementModel);
		return $elementModel;
	}
	
	/**
	 * Get the fields value regardless of whether its in joined data or no
	 * @param object $params
	 * @param string $pname
	 * @param array posted form $data
	 */
	
	private function getFieldValue($params, $pname, $data, $default = '')
	{
		$elementModel = $this->getFieldModel($params, $pname);
		$element =& $this->getFieldElement($params, $pname);
		$group =& $elementModel->getGroup();
		$name = $elementModel->getFullName(false, true, false);
		if ($group->isJoin()) {
			$data = $data['join'][$group->getGroup()->join_id];
		}
		else {
			// $$$ hugh we're running onAfterProcess, so main table names have been shortened
			$name = FabrikString::shortColName($name);
		}

		$value = JArrayHelper::getValue($data, $name, $default);
		if (is_array($value)) {
			// $$$ hugh see if it's a date element
			if (array_key_exists('date', $value) && array_key_exists('time', $value)) {
				$value = $value['date'] . ' ' . $value['time'];
			}
		}
		return $value;
	}
	
	private function _process(&$params, &$formModel)
	{
		$app =& Jfactory::getApplication();
		$gcal_url = $params->get('gcal_url', '');
		$matches = array();
		// this matches a standard GCal URL, found under the Google "Calender Details" tab, using the XML button.
		// Because we are adding events, we need the private URL, and it needs to end in /full
		// But the default URL Google show when you look at setting is the /basic ...
		// http://www.google.com/calendar/feeds/hugh.messenger%40gmail.com/private-3081eca2b0asdfasdf8f106ea6f63343056/basic
		// So we need to replace /basic with /full before we do anything.
		// Also need to remove the magic cookie, if present.
		$gcal_url = str_replace('/basic', '/full', $gcal_url);
		$gcal_url = preg_replace('#/private-\w+/#', '/private/', $gcal_url);
		if (preg_match('#feeds/(.*?)/(\w+-\w+|\w+)/(\w+)#', $gcal_url, $matches)) {
			// grab the bits of the URL we need for the Zend framework call
			$gcal_user = $matches[1];
			$gcal_visibility = $matches[2];
			$gcal_projection = $matches[3];
			$gcal_email = urldecode($gcal_user);
		
			// grab all the field names to use
			/*
			$gcal_label_element_long = $params->get('gcal_label_element');
			$gcal_label_element = FabrikString::shortColName($gcal_label_element_long);
			$gcal_desc_element_long = $params->get('gcal_desc_element');
			$gcal_desc_element = FabrikString::shortColName($gcal_desc_element_long);
			$gcal_start_date_element_long = $params->get('gcal_start_date_element');
			$gcal_start_date_element = FabrikString::shortColName($gcal_start_date_element_long);
			$gcal_end_date_element_long = $params->get('gcal_end_date_element');
			$gcal_end_date_element = FabrikString::shortColName($gcal_end_date_element_long);
			$gcal_id_element_long = $params->get('gcal_id_element');
			$gcal_id_element = FabrikString::shortColName($gcal_id_element_long);
			$email = $params->get('gcal_login', '');
			$passwd = $params->get('gcal_passwd', '');
			*/
			$data =& $formModel->_formData;
			$email = $params->get('gcal_login', '');
			$passwd = $params->get('gcal_passwd', '');
			$gcal_label = $this->getFieldValue($params, 'gcal_label_element', $data);
			$gcal_desc = $this->getFieldValue($params, 'gcal_desc_element', $data);
			$gcal_start_date = $this->getFieldValue($params, 'gcal_start_date_element', $data);
			$gcal_end_date = $this->getFieldValue($params, 'gcal_end_date_element', $data);
			$gcal_event_id = $this->getFieldValue($params, 'gcal_id_element', $data);
			$gcal_id_element = FabrikString::shortColName($this->getFieldName($params, 'gcal_id_element'));
		
			// sanity check, make sure required elements have been specified
			if (empty($email) || empty($passwd) || empty($gcal_label) || empty($gcal_start_date) || empty($gcal_id_element)) {
				JError::raiseNotice(500, 'missing gcal data');
				return;
			}
			
			// grab the table model and find table name and PK
			$tableModel =& $formModel->getTableModel();
			$table =& $tableModel->getTable();
			$table_name 		= $table->db_table_name;
			$primary_key = $tableModel->_shortKey(null, true);
			$db =& $tableModel->getDb();
		
			// include the Zend stuff
			$path = JPATH_SITE.DS.'libraries';
			set_include_path(get_include_path() . PATH_SEPARATOR . $path);
			$path = get_include_path();
			require_once 'Zend/Loader.php';
			Zend_Loader::loadClass('Zend_Gdata');
			Zend_Loader::loadClass('Zend_Uri_Http');
			Zend_Loader::loadClass('Zend_Gdata_Calendar');
			Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
			
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
		
			// set up query
			$query = $gdataCal->newEventQuery();
			$query->setUser($gcal_user);
			$query->setVisibility($gcal_visibility);
			$query->setProjection($gcal_projection);
			
			if (!empty($gcal_event_id)) {
				$query->setEvent($gcal_event_id);
				try {
					$event = $gdataCal->getCalendarEventEntry($query);
				} catch (Zend_Gdata_App_Exception $e) {
					//echo "Error: " . $e->getMessage();
					JError::raiseNotice(500, 'Error retrieving GCal event: ' . $e->getMessage());
					return;
				}				
			}
			else {
				$event = $gdataCal->newEventEntry();
			}


			// Grab the tzOffset.  No need to offset dates from form, as they are already offset,
			// but need it for the gcal date string format.
			// Note that gcal want +/-XX (like -06)
			// but J! gives us +/-X (like -6) so we sprintf it to the right format
			
			$config =& JFactory::getConfig();
			$tzOffset = (int)$config->getValue('config.offset');
			$tzOffset = sprintf('%+03d', $tzOffset);

			$event->title = $gdataCal->newTitle($gcal_label);
			if (!empty($gcal_desc)) {
				$event->content = $gdataCal->newContent($gcal_desc);
			}
			else {
				$event->content = $gdataCal->newContent($gcal_label);
			}
			$when = $gdataCal->newWhen();

			// grab the start date, apply the tx offset, and format it for gcal
			$start_date = JFactory::getDate($gcal_start_date);
			$start_date->setOffset($tzOffset);
			$start_fdate = $start_date->toFormat('%Y-%m-%d %H:%M:%S');
			$date_array = explode(' ',$start_fdate);
			$when->startTime = "{$date_array[0]}T{$date_array[1]}.000{$tzOffset}:00";

			// we have to provide an end date for gcal, so if we don't have one,
			// default it to start date + 1 hour
			if (empty($gcal_end_date) || $gcal_end_date == '0000-00-00 00:00:00') {
				$startstamp = strtotime($gcal_start_date);
				$endstamp = $startstamp + (60 * 60);
				$gcal_end_date = strftime('%Y-%m-%d %H:%M:%S', $endstamp);
			}
			// grab the end date, apply the tx offset, and format it for gcal
			$end_date = JFactory::getDate($gcal_end_date);
			$end_date->setOffset($tzOffset);
			$end_fdate = $end_date->toFormat('%Y-%m-%d %H:%M:%S');
			$date_array = explode(' ',$end_fdate);
			$when->endTime = "{$date_array[0]}T{$date_array[1]}.000{$tzOffset}:00";
			$event->when = array($when);

			if (!empty($gcal_event_id)) {
				try {
					$event->save();
				} catch (Zend_Gdata_App_Exception $e) {
					JError::raiseNotice(500, 'Error saving GCal event: ' . $e->getMessage());
					return;
				}
			}
			else {
				// fire off the insertEvent to gcal, catch any errors
				try {
					$retEvent = $gdataCal->insertEvent($event, $gcal_url);
				}
				catch (Zend_Gdata_App_HttpException $he) {
					JError::raiseNotice(500, 'Problem adding event: ' . $he->getRawResponseBody());
					return;
				}
				// insertEvent worked, so grab the gcal ID from the returned event data,
				// and update our event record with the short version of the ID
				$gcal_event_id = $this->_getGcalShortId($retEvent->id->text);
			}

			$our_id = $formModel->_formData[$primary_key];
			if (!empty($our_id)) {
				$db->setQuery("
							UPDATE $table_name
							SET $gcal_id_element = ".$db->Quote($gcal_event_id)."
							WHERE $primary_key = ".$db->Quote($our_id)
				);
				$db->query();
			}
			else {
				JError::raiseNotice(500, 'No rowid for GCal ID update!');
			}
			$app->enqueueMessage('Event syncronized with GCal');
		} else {
			JError::raiseNotice(500, 'Incorrect GCal url');
		}
	}
	
	private function _getGcalShortId($long_id )
	{
		$matches = array();
		if (preg_match('#/(\w+)$#', $long_id, $matches)) {
			return $matches[1];
		}
		else {
			return $long_id;
		}
	}
}
?>
<?php
/**
 * Plugin element to render hour/minute/seconds dropdowns
 * @package fabrikar
 * @author Hugh Messenger
 * @copyright (C) Hugh Messenger
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikduration extends FabrikModelElement {

	var $_pluginName = 'fabrikduration';

	/** @param object element model **/
	var $_elementModel = null;
	
	var $elementModels = array();
	
	/**
	 * Constructor
	 */

	function __construct()
	{
		$this->hasSubElements = true;
		parent::__construct();
	}

	protected function _getHMS($sec) {
		$hours = intval(intval($sec) / 3600);
		$minutes = intval(($sec / 60) % 60);
		$seconds = intval($sec % 60);
		return array("hours" => $hours, "minutes" => $minutes, "seconds" => $seconds);
	}
	
	protected function _displayFormat($value) {
		$hms = $this->_getHMS($value);
		$params =& $this->getParams();
		$display_format = $params->get('duration_display_format', 'short');
		$show_seconds = (int)$params->get('duration_show_seconds', '0');
		$str = $value;
		if ($display_format == 'short') {
			$str = sprintf("%02d:%02d", $hms['hours'], $hms['minutes']);
			if ($show_seconds) {
				$str .= sprintf(":%02d", $hms['seconds']);
			}
		}
		return $str;
	}
	/**
	 * draws the form element
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		//Jaanus: needed also here to not to show 0000-00-00 in detail view;
		//see also 58, added  && !in_array($value, $aNullDates) (same reason).
		$db =& JFactory::getDBO();
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params =& $this->getParams();
		$element =& $this->getElement();

		$bits = array();
		// $$$ rob - not sure why we are setting $data to the form's data
		//but in table view when getting read only filter value from url filter this
		// _form_data was not set to no readonly value was returned
		// added little test to see if the data was actually an array before using it
		if (is_array($this->_form->_data)) {
			$data =& $this->_form->_data;
		}
		$value = $this->getValue($data, $repeatCounter);

		if (!$this->_editable) {
			$detailvalue = $this->_displayFormat($value);
			return($element->hidden == '1') ? "<!-- " . $detailvalue . " -->" : $detailvalue;
		}
		else {
			//wierdness for failed validaion
			/*
			$value = strstr($value, ',') ? array_reverse(explode(',', $value)) : explode('-', $value);
			$yearvalue = JArrayHelper::getValue($value, 0);
			$monthvalue = JArrayHelper::getValue($value, 1);
			$dayvalue = JArrayHelper::getValue($value, 2);
			*/
			$show_seconds = (int)$params->get('duration_show_seconds', '0');
			$hms = $this->_getHMS($value);
			$hours = array();
			for ($i=0; $i < 24; $i++) {
				$hours[] = JHTML::_('select.option', $i, $i);
			}
			$minutes = array();
			for ($i=0; $i<60; $i++) {
				$minutes[] = JHTML::_('select.option', $i, $i);
			}
			if ($show_seconds) {
				$seconds = array();
				for ($i=0; $i<60; $i++) {
					$seconds[] = JHTML::_('select.option', $i, $i);
				}
			}
			$errorCSS = (isset($this->_elementError) &&  $this->_elementError != '') ? " elementErrorHighlight" : '';
			$attribs 	= 'fabrikinput inputbox'.$errorCSS;
			$str = "<div class=\"fabrikSubElementContainer\">";
			$str .= '<input type="hidden" name="' .$name . '" id="'.$id.'" value="'.$value.'" />';
			//$name already suffixed with [] as element hasSubElements = true
			$str .= JHTML::_('select.genericlist', $hours, $name.'[hours]', 'class="fabrikHourMenu '.$attribs.'"', 'value', 'text', $hms['hours']);
			$str .= ' : '.JHTML::_('select.genericlist', $minutes, $name.'[minutes]', 'class="fabrikMinuteMenu '.$attribs.'"', 'value', 'text', $hms['minutes']);
			if ($show_seconds) {
				$str .= ' : '.JHTML::_('select.genericlist', $seconds, $name.'[seconds]', 'class="fabrikSecondMenu '.$attribs.'"', 'value', 'text', $hms['seconds']);
			}
			$str .= "</div>";
			return $str;
		}
	}

	/**
	 * can be overwritten by plugin class
	 * determines the value for the element in the form view
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return string value
	 */

	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		//@TODO rename $this->defaults to $this->values
		if (!isset($this->defaults)) {
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults)) {
			$groupModel =& $this->getGroup();
			$joinid = $groupModel->getGroup()->join_id;
			$formModel =& $this->getForm();

			// $$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			$value = JArrayHelper::getValue($opts, 'use_default', true) == false ? '' : $this->getDefaultValue($data);

			$name = $this->getFullName(false, true, false);
			$rawname = $name . "_raw";
			if ($groupModel->isJoin()) {
				if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid])) {
					if ($groupModel->canRepeat()) {

						if (array_key_exists($rawname, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$rawname])) {
							$value = $data['join'][$joinid][$rawname][$repeatCounter];
						} else {
							if (array_key_exists($rawname, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name])) {
								$value = $data['join'][$joinid][$name][$repeatCounter];
							}
						}
					} else {
						$value = JArrayHelper::getValue($data['join'][$joinid], $rawname, JArrayHelper::getValue($data['join'][$joinid], $name, $value));

						// $$$ rob if you have 2 tbl joins, one repeating and one not
						// the none repeating one's values will be an array of duplicate values
						// but we only want the first value
						if (is_array($value)) {
							$value = array_shift($value);
						}
					}
				}
			} else {
				if ($groupModel->canRepeat()) {
					//repeat group NO join
					$thisname = $rawname;
					if (!array_key_exists($name, $data)) {
						$thisname = $name;
					}
					if (array_key_exists($thisname, $data)) {
						if (is_array($data[$thisname])) {
							//occurs on form submission for fields at least
							$a = $data[$thisname];
						} else {
							//occurs when getting from the db
							$a = explode(GROUPSPLITTER, $data[$thisname]);
						}
						$value = JArrayHelper::getValue($a, $repeatCounter, $value);
					}

				} else {
					if (!is_array($data)) {
						$value = $data;
					} else {
						$value = JArrayHelper::getValue($data, $name, JArrayHelper::getValue($data, $rawname, $value));
					}
				}
			}

			if (is_array($value)) {
					$value = $value[0];
			}
			if ($value === '') { //query string for joined data
				$value = JArrayHelper::getValue($data, $name, $value);
			}
			//@TODO perhaps we should change this to $element->value and store $element->default as the actual default value
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1) {
				$formModel->getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			$this->defaults[$repeatCounter] = $value;
		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed the elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		$groupModel =& $this->getGroup();
		if ($groupModel->canRepeat()) {
			if (is_array($val)) {
				$res = array();
				foreach ($val as $v) {
					$res[] = $this->_indStoreDBFormat($v);
				}
				return implode(GROUPSPLITTER, $res);
			}
		}
		return $this->_indStoreDBFormat($val);
	}

	/**
	 * get the value to store the value in the db
	 *
	 * @param array $val
	 * @return int seconds
	 */

	private function _indStoreDBFormat($val)
	{
		if (is_array($val)) {
			$val = $val[0];
		}
		return $val;
	}

	/**
	* run on formModel::setFormData()
	* @param int repeat group counter
	* @return null
	*/
	public function preProcess($c)
	{
		$params = $this->getParams();
		$start_date_element_id = $params->get('duration_start_date', '');
		$end_date_element_id = $params->get('duration_end_date', '');
		if (!empty($start_date_element_id) && !empty($end_date_element_id)) {
			/*
			$start_date_elementModel =& JModel::getInstance('element', 'FabrikModel');
			$start_date_elementModel->setId($start_date_element_id);
			$start_date_fullname = $start_date_elementModel->getFullName();
			$end_date_elementModel =& JModel::getInstance('element', 'FabrikModel');
			$end_date_elementModel->setId($end_date_element_id);
			$end_date_fullname = $end_date_elementModel->getFullName();
			$duration_fullname = $this->getFullName();
			$formModel = $this->getFormModel();
			if (array_key_exists($start_date_fullname, $formModel->_formData) && array_key_exists($end_date_fullname, $formModel->_formData)) {
				if (is_array($formModel->_formData[$start_date_fullname]) && array_key_exists('time', $formModel->_formData[$start_date_fullname])) {
					$start_time = strtotime($formModel->_formData[$start_date_fullname]['date'] . ' ' . $formModel->_formData[$start_date_fullname]['time']);
					$end_time = $start_time + $formModel->_formData[$duration_fullname][0];
					list($end_date_str, $end_time_str) = explode(' ', date('Y-m-d H:i', $end_time));
					$formModel->_formData[$end_date_fullname]['date'] = $end_date_str;
					$formModel->_formData[$end_date_fullname . '_raw']['date'] = $end_date_str;
					$formModel->_formData[$end_date_fullname]['time'] = $end_time_str;
					$formModel->_formData[$end_date_fullname . '_raw']['time'] = $end_time_str;
				}
			}
			*/
			$formModel = $this->getFormModel();
			$data =& $formModel->_formData;
			$start_date = $this->getFieldValue($params, 'duration_start_date', $data);
			$start_time = strtotime($start_date);
			$duration = $this->getValue($data, $c);
			$end_time = $start_time + $duration;
			list($end_date_str, $end_time_str) = explode(' ', date('Y-m-d H:i', $end_time));
			$end_date = array('date' => $end_date_str, 'time' => $end_time_str);
			$this->setFieldValue($params, 'duration_end_date', $end_date);
		}
	}
	
	/**
	 * used in isempty validation rule
	 *
	 * @param array $data
	 * @return bol
	 */

	function dataConsideredEmpty($data, $repeatCounter)
	{
		if (strstr($data, ',')) {
			$data = explode(',', $data);
		}
		$data = (array)$data;
		foreach ($data as $d) {
			if (trim($d) == '') {
				return true;
			}
		}
		return false;
	}


	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts->show_seconds = (bool)$params->get('duration_show_seconds', '0') == '1';
		$opts = json_encode($opts);
		return "new fbDuration('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikduration/', false);
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 */
	function getFieldDescription()
	{
		$p =& $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		$groupModel =& $this->getGroup();
		if (is_object($groupModel) && ($groupModel->canRepeat() && !$groupModel->isJoin())) {
			return "VARCHAR(255)";
		} else {
			return "INT(11)";
		}
	}

	function renderTableData($data, $oAllRowsData)
	{
		$db =& JFactory::getDBO();
		$params =& $this->getParams();

		$groupModel =& $this->getGroup();
		$data = $groupModel->canRepeat() ? explode(GROUPSPLITTER, $data) : array($data);
		$display_format = $params->get('duration_display_format', 'short');
		$format = array();
		foreach ($data as $d) {
			$format[] = $this->_displayFormat($d);
		}
		$data = implode(GROUPSPLITTER, $format);
		return parent::renderTableData($data, $oAllRowsData);
	}

	/*
	 * @TODO - borrowed from juser and gcal form plugins, these should really go in a model or a helper somewhere
	 */
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
	
	private function getFieldValue($params, $pname, $data, $default = '', $shortnames = false)
	{
		$elementModel = $this->getFieldModel($params, $pname);
		//$element =& $this->getFieldElement($params, $pname);
		$group =& $elementModel->getGroup();
		$name = $elementModel->getFullName(false, true, false);
		if ($group->isJoin()) {
			$data = $data['join'][$group->getGroup()->join_id];
		}
		else {
			// $$$ hugh if we're running onAfterProcess, so main table names have been shortened
			// (added $shortnames arg)
			if ($shortnames) {
				$name = FabrikString::shortColName($name);
			}
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
	
	/**
	* Get the fields value regardless of whether its in joined data or no
	* @param object $params
	* @param string $pname
	* @param array posted form $data
	*/
	
	private function setFieldValue($params, $pname, $value, $shortnames = false)
	{
		$formModel =& $this->getFormModel();
		$data =& $formModel->_formData;
		$elementModel = $this->getFieldModel($params, $pname);
		//$element =& $this->getFieldElement($params, $pname);
		$group =& $elementModel->getGroup();
		$name = $elementModel->getFullName(false, true, false);
		if ($group->isJoin()) {
			$data =& $data['join'][$group->getGroup()->join_id];
		}
		else {
			// $$$ hugh if we're running onAfterProcess, so main table names have been shortened
			// (added $shortnames arg)
			if ($shortnames) {
				$name = FabrikString::shortColName($name);
			}
		}
		$data[$name] = $value;
		$data[$name . '_raw'] = $value;
	}
}
?>
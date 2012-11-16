<?php
/**
 * Plugin element to store IP
 * @package fabrikar
 * @author Hugh Messenger
 * @copyright (C) Hugh Messenger
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikIp extends FabrikModelElement {

	/** @var string plugin name */
	var $_pluginName = 'ip';

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$element	=& $this->getElement();
		$name 		= $this->getHTMLName($repeatCounter);
		$id 			= $this->getHTMLId($repeatCounter);
		$params 	=& $this->getParams();

		$rowid = JRequest::getVar('rowid', false);
		//@TODO when editing a form with joined repeat group the rowid will be set but
		//the record is in fact new
		if ($params->get('ip_update_on_edit') || !$rowid || ($this->_inRepeatGroup && $this->_inJoin &&  $this->_repeatGroupTotal == $repeatCounter)) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			if (empty($data) || empty($data[$name])) {
				// if $data is empty, we must (?) be a new row, so just grab the IP
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			else {
				$ip = $this->getValue($data, $repeatCounter);
			}
		}

		$str = '';
		if ($this->canView()) {
			if (!$this->_editable) {
				$str = $this->_replaceWithIcons($ip);
			}
			else {
				$str = "<input class=\"fabrikinput inputbox\" readonly=\"readonly\" name=\"$name\" id=\"$id\" value=\"$ip\" />\n";
			}
		} else {
			/* make a hidden field instead*/
			$str = "<input type=\"hidden\" class=\"fabrikinput\" name=\"$name\" id=\"$id\" value=\"$ip\" />";
		}
		return $str;
	}

	/**
	 * get element's hidden field
	 *
	 * @access private
	 * @param string $name
	 * @param string $value
	 * @param string $id
	 * @return strin
	 */
	function _getHiddenField($name, $value, $id)
	{
		return "<input class=\"fabrikinput inputbox\" type=\"hidden\" name=\"$name\" value=\"$value\" id=\"$id\" />\n";
	}

	/**
	 * if we are creating a new record, and the element was set to readonly
	 * then insert the users data into the record to be stored
	 *
	 * @param unknown_type $data
	 */

	function onStoreRow(&$data)
	{
		$element =& $this->getElement();
		if ($data['rowid'] == 0 && !in_array($element->name, $data)) {
			$data[$element->name] = $_SERVER['REMOTE_ADDR'];
		}
		else {
			$params =& $this->getParams();
			if ($params->get('ip_update_on_edit', 0)) {
				$data[$element->name] = $_SERVER['REMOTE_ADDR'];
				$data[$element->name . '_raw'] = $_SERVER['REMOTE_ADDR'];
			}
		}
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData($data, $oAllRowsData)
	{
		return parent::renderTableData($data, $oAllRowsData);
	}

	/**
	 * this really does get just the default value (as defined in the element's settings)
	 * @return unknown_type
	 */

	function getDefaultValue($data = array() )
	{
		if (!isset($this->_default)) {
			$this->_default = $_SERVER['REMOTE_ADDR'];
		}
		return $this->_default;
	}

	/**
	 * get the value
	 *
	 * @param array $data
	 * @param int $repeatCounter
	 * @param array options
	 * @return unknown
	 */

	function getValue($data, $repeatCounter = 0, $opts = array() )
	{
		//cludge for 2 scenarios
		if (array_key_exists('rowid', $data)) {
			//when validating the data on form submission
			$key = 'rowid';
		} else {
			//when rendering the element to the form
			$key = '__pk_val';
		}
		if (empty($data) || !array_key_exists($key, $data) || (array_key_exists($key, $data) && empty($data[$key]))) {
			// $$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			if (array_key_exists('use_default', $opts) && $opts['use_default'] == false) {
				$value = '';
			} else {
				$value   = $this->getDefaultValue($data);
			}
			return $value;
		}
		$res = parent::getValue($data, $repeatCounter, $opts);
		return $res;
	}


	/**
	 * defines the type of database table field that is created to store the element's data
	 * as we always store the element id turn this into INT(11) and not varchar as it was previously
	 * unless in none-joined repeating group
	 * @return string db field description
	 */

	function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		return "VARCHAR(255)";
	}

	/**
	 * render admin settings
	 */

	function renderAdminSettings()
	{
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		$element =& $this->getElement();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	echo $pluginParams->render('details');
	echo $pluginParams->render('params', 'extra');?></div>
	<?php
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts = json_encode($opts);
		return "new fbIp('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikip/', false);
	}
}
?>
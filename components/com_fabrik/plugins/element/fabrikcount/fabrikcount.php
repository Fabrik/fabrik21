<?php
/**
 * Plugin element to:
 * Counts records in a row - so adds "COUNT(x) .... GROUP BY (y)" to the main db query
 *
 * Note implementing this element will mean that only the first row of data is returned in
 * the joined group
 *
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikcount extends FabrikModelElement {

	var $_pluginName = 'fabrikcount';

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData($data, $oAllRowsData)
	{
		$params =& $this->getParams();
		return parent::renderTableData($data, $oAllRowsData);
	}

	/**
	 */
	public function getGroupByQuery()
	{
		$params =& $this->getParams();
		return $params->get('count_groupbyfield');
	}

	/**
	 * @param array
	 * @param array
	 * @param string table name (depreciated)
	 */

	function getAsField_html(&$aFields, &$aAsFields, $dbtable = '')
	{
		$dbtable = $this->actualTableName();
		if (JRequest::getVar('c') != 'form') {
			$params =& $this->getParams();
			$fullElName = "`$dbtable" . "___" . $this->_element->name . "`";
			$r = "COUNT(".$params->get('count_field', '*').")";
			$aFields[] 	= "$r AS $fullElName";
			$aAsFields[] =  $fullElName;
			$aAsFields[] =  "`$dbtable" . "___" . $this->getElement()->name . "_raw`";
		}
	}

	/**
	 * determines if the element can contain data used in sending receipts, e.g. fabrikfield returns true
	 */

	function isReceiptElement()
	{
		return false;
	}

	/**
	 * this element s only used for table displays so always return false
	 * (non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelElement#canUse()
	 */

	function canUse()
	{
		return false;
	}

	/**
	 * draws the form element
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		return '';
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
		return "new fbCount('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikcount/', false);
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 */
	function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		$group =& $this->getGroup();
		if ($group->isJoin() == 0 && $group->canRepeat()) {
			return "TEXT";
		}
		return "VARCHAR(" . $p->get('maxlength', 255) . ")";
	}

}
?>
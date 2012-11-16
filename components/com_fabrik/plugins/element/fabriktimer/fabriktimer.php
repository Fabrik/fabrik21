<?php
/**
* Plugin element to render a timestamp field
* @package fabrikar
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');
require_once(COM_FABRIK_FRONTEND.DS.'plugins'.DS.'element'.DS.'fabrikdate'.DS.'fabrikdate.php');

class FabrikModelFabriktimer extends FabrikModelFabrikDate {

	var $_pluginName = 'timer';

	var $hasSubElements = false;
	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		$return =  "0000-00-00 " . $val;
		$format = '%Y-%m-%d %H:%i:%s';
		$timebits = FabrikWorker::strToDateTime( $return, $format);
		$return = date( 'Y-m-d H:i:s', $timebits['timestamp']);
		return $return;
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData($data, $oAllRowsData)
	{
		if ($data != '') {
			$format = '%Y-%m-%d %H:%i:%s';
			$timebits = FabrikWorker::strToDateTime( $data, $format);
			$data = date( 'H:i:s', $timebits['timestamp']);
		}
		return $data;
	}

	/**
	 * determines if the element can contain data used in sending receipts, e.g. fabrikfield returns true
	 */

	function isReceiptElement()
	{
		return true;
	}

		/**
	 * draws the form element
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{

		$name 		= $this->getHTMLName($repeatCounter);
		$id				= $this->getHTMLId($repeatCounter);
		$params 	=& $this->getParams();
		$element 	=& $this->getElement();
		$size 		= $element->width;

		//$value = $element->default;
		$value 	= $this->getValue($data, $repeatCounter);
		if ($value == '') {
			$value = '00:00:00';
		} else {
			$value = explode(" ", $value);
			$value = array_pop($value);
		}
		$type = "text";
		if (isset($this->_elementError) && $this->_elementError != '') {
			$type .= " elementErrorHighlight";
		}
		if ($element->hidden == '1') {
			$type = "hidden";
		}
		$sizeInfo =  " size=\"$size\" ";
		if ($params->get('timer_readonly')) {
			$sizeInfo .= " readonly=\"readonly\" ";
			$type .= " readonly";
		}
		if (!$this->_editable) {
			return($element->hidden == '1') ? "<!-- " . $value . " -->" : $value;
		}

		$str = "<input class=\"fabrikinput inputbox $type\" type=\"$type\" name=\"$name\" id=\"$id\" $sizeInfo value=\"$value\" />\n";
		if (!$params->get('timer_readonly')) {
			$str .= "<input type=\"button\" id=\"{$id}_button\" value=\"" . JText::_('START') . "\" />";
		}
		return $str;
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$params =& $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts->autostart = $params->get('timer_autostart', false);
		$opts = json_encode($opts);
		$lang = new stdClass();
		$lang->start = JText::_('START');
		$lang->stop = JText::_('STOP');
		$lang = json_encode($lang);
		return "new fbTimer('$id', $opts, $lang)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabriktimer/', false);
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
		return "DATETIME";
	}

	/**
	 * find the sum from a set of data
	 * can be overwritten in plugin - see date for example of averaging dates
	 * @param array $data to sum
	 * @return string sum result
	 */


	protected function getSumQuery(&$tableModel, $label = "'calc'" )
	{
		$table 			=& $tableModel->getTable();
		$joinSQL 		= $tableModel->_buildQueryJoin();
		$whereSQL 	= $tableModel->_buildQueryWhere();
		$name 			= $this->getFullName(false, false, false);
		//$$$rob not actaully likely to work due to the query easily exceeding mySQL's  TIMESTAMP_MAX_VALUE value but the query in itself is correct
		//return "SELECT DATE_FORMAT(FROM_UNIXTIME(SUM(UNIX_TIMESTAMP($name))), '%H:%i:%s') AS value, $label AS label FROM `$table->db_table_name` $joinSQL $whereSQL";
		 return "SELECT SEC_TO_TIME(SUM(TIME_TO_SEC($name))) AS value, $label AS label FROM `$table->db_table_name` $joinSQL $whereSQL";
	}

	/**
	 * build the query for the avg caclculation - can be overwritten in plugin class (see date element for eg)
	 * @param model $tableModel
	 * @param string $label the label to apply to each avg
	 * @return string sql statement
	 */

	protected function getAvgQuery(&$tableModel, $label = "'calc'" )
	{
		$table 			=& $tableModel->getTable();
		$joinSQL 		= $tableModel->_buildQueryJoin();
		$whereSQL 	= $tableModel->_buildQueryWhere();
		$name 			= $this->getFullName(false, false, false);
		//return "SELECT DATE_FORMAT(FROM_UNIXTIME(AVG(UNIX_TIMESTAMP($name))), '%H:%i:%s') AS value, $label AS label FROM `$table->db_table_name` $joinSQL $whereSQL";
		return "SELECT SEC_TO_TIME(AVG(TIME_TO_SEC($name))) AS value, $label AS label FROM `$table->db_table_name` $joinSQL $whereSQL";
	}

	/**
	 * build the query for the avg caclculation - can be overwritten in plugin class (see date element for eg)
	 * @param model $tableModel
	 * @param string $label the label to apply to each avg
	 * @return string sql statement
	 */

	protected function getMedianQuery(&$tableModel, $label = "'calc'" )
	{
		$table 			=& $tableModel->getTable();
		$joinSQL 		= $tableModel->_buildQueryJoin();
		$whereSQL 	= $tableModel->_buildQueryWhere();
		$name 			= $this->getFullName(false, false, false);
		//return "SELECT DATE_FORMAT(FROM_UNIXTIME((UNIX_TIMESTAMP($name))), '%H:%i:%s') AS value, $label AS label FROM `$table->db_table_name` $joinSQL $whereSQL";
		return "SELECT SEC_TO_TIME(TIME_TO_SEC($name)) AS value, $label AS label FROM `$table->db_table_name` $joinSQL $whereSQL";

	}

	/**
	 * find the sum from a set of data
	 * can be overwritten in plugin - see date for example of averaging dates
	 * @param array $data to sum
	 * @return string sum result
	 */
	public function simpleSum($data)
	{
		$sum = 0;
		foreach ($data as $d) {
			if ($d != '') {
				$date =& JFactory::getDate($d);
				$sum += $this->toSeconds($date);
			}
		}
		return $sum;
	}

	/**
	 * get the value to use for graph calculations
	 * can be overwritten in plugin
	 * see fabriktimer which converts the value into seconds
	 * @param string $v
	 * @return mixed
	 */

	public function getCaclulationValue($v)
	{
		if ($v == '') {
			return 0;
		}
		$date = JFactory::getDate($v);
		return $this->toSeconds($date);
	}
}
?>
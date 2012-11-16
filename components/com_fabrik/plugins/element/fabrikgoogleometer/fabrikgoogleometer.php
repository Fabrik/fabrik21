<?php
/**
 * Plugin element to render a google o meter viz
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikGoogleometer extends FabrikModelElement {

	var $_pluginName = 'googleometer';

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

		$name 		= $this->getHTMLName($repeatCounter);
		$id				= $this->getHTMLId($repeatCounter);
		$params 	=& $this->getParams();
		$element 	=& $this->getElement();
		$value 		= $this->getValue($data, $repeatCounter);
		$range = $this->getRange();
		$fullName = $this->getDataElementFullName();
		if (JRequest::getCmd('task') === 'details') {
			$data = $data[$fullName];
			$str = $this->_renderTableData($data, $range);
			return $str;
		}
		return '';
	}

	private function getDataElementFullName()
	{
		$dataelement = $this->getDataElement();
		$fullName = $dataelement->getFullName();
		return $fullName;
	}

	private function getDataElement() {
		$params =& $this->getParams();
		$elementid = (int)$params->get('googleometer_element');
		$element =& JModel::getInstance('Element', 'FabrikModel');
		$element->setId($elementid);
		return $element;
	}

	private function getRange()
	{
		$tableModel =& $this->getTableModel();
		$fabrikdb =& $tableModel->getDb();
		$db =& JFactory::getDBO();
		$elementModel = $this->getDataElement();
		$element = $elementModel->getElement();
		$elementShortName = $db->nameQuote($element->name);
		if (trim($element->name) == '') {
			$r = new stdClass();
			$r->min = 0;
			$r->max = 10;
			return $r;
		} else {
			$fabrikdb->setQuery("SELECT MIN($elementShortName) AS min, MAX($elementShortName) AS max FROM " . $tableModel->getTable()->db_table_name);
			$range = $fabrikdb->loadObject();
		}
		return $range;
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData($data, $oAllRowsData)
	{
		static $range;
		static $fullName;
		if (!isset($range)) {
			$range = $this->getRange();
			$fullName = $this->getDataElementFullName();
		}
		$data = $oAllRowsData->$fullName;
		$data = $this->_renderTableData($data, $range);
		return parent::renderTableData($data, $oAllRowsData);
	}

	function _renderTableData($data, $range) {
		$options = array();
		$params =& $this->getParams();
		$options['chartsize'] = 'chs='.$params->get('googleometer_width', 200).'x'.$params->get('googleometer_height', 125);
		$options['charttype'] = 'cht=gom';
		$options['value'] = 'chd=t:'.$data;
		$options['label'] = 'chl='.$params->get('googleometer_label');
		$options['range'] = 'chds='.$range->min.','.$range->max;
		$options = implode('&amp;', $options);
		$str = '<img alt="Google-o-meter" src="http://chart.apis.google.com/chart?'.$options.'"/>';
		return $str;
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
		return "TINYINT(1)";
	}

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php echo $pluginParams->render();?></div>
		<?php
	}

}
?>
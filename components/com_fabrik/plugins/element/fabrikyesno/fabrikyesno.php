<?php
/**
 * Plugin element to render fields
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');
require_once(COM_FABRIK_FRONTEND.DS.'plugins'.DS.'element'.DS.'fabrikradiobutton'.DS.'fabrikradiobutton.php');

class FabrikModelFabrikYesNo  extends FabrikModelFabrikRadiobutton {

	var $_pluginName = 'yesno';

	/**
	 * Constructor
	 */

	function __construct()
	{
		$this->hasSubElements = true;
		parent::__construct();
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		if ($this->getGroup()->canRepeat()) {
			$str = '';
			foreach ($val as $v) {
				$str .= $v[0] . GROUPSPLITTER;
			}
			$str = FabrikString::rtrimword( $str, GROUPSPLITTER);
			return $str;
		} else {
			//if sent via inline edit val is string already
			return is_array($val) ? $val[0] : $val;
		}
	}

	/**
	 * this really does get just the default value (as defined in the element's settings)
	 * @param array data to use as parsemessage for placeholder
	 * @return unknown_type
	 */

	function getDefaultValue($data)
	{
		if (!isset($this->_default)) {
			$params =& $this->getParams();
			$this->_default = $params->get('yesno_default', 0);
		}
		return $this->_default;
	}

	function _renderTableData($data, $oAllRowsData)
	{
		//check if the data is in csv format, if so then the element is a multi drop down
		if ($data == '1') {
			return "<img src='".COM_FABRIK_LIVESITE."components/com_fabrik/plugins/element/fabrikyesno/images/1.png' alt='" . JText::_('Yes') . "' />" ;
		} else {
			return "<img src='".COM_FABRIK_LIVESITE."components/com_fabrik/plugins/element/fabrikyesno/images/0.png' alt='" . JText::_('No') . "' />";
		}
	}

	/**
	 * shows the data formatted for the table view with format = pdf
	 * note pdf lib doesnt support transparent pngs hence this func
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData_pdf( $data, $oAllRowsData )
	{
		if ($data == '1') {
			return "<img src='".COM_FABRIK_LIVESITE."components/com_fabrik/plugins/element/fabrikyesno/images/1_8bit.png' alt='" . JText::_('Yes') . "' />" ;
		} else {
			return "<img src='".COM_FABRIK_LIVESITE."components/com_fabrik/plugins/element/fabrikyesno/images/0_8bit.png' alt='" . JText::_('No') . "' />";
		}
	}

	/**
	 * shows the data formatted for CSV export
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData_csv($data, $oAllRowsData)
	{
		if ($data == '1') {
			return  JText::_('Yes');
		} else {
			return  JText::_('No');
		}
	}

	/**
	 * get the radio buttons possible values
	 * @return array of radio button values
	 */

	public function getOptionValues()
	{
		return array(0, 1);
	}

	/**
	 * get the radio buttons possible labels
	 * @return array of radio button labels
	 */

	protected function getOptionLabels()
	{
		return array(JText::_('no'), JText::_('yes'));
	}

	/**
	 * format the read only output for the page
	 * @param string $value
	 * @param string label
	 * @return string value
	 */

	protected function getReadOnlyOutput($value, $label)
	{
		if ($value == '1') {
			$value = "<img src='".COM_FABRIK_LIVESITE."components/com_fabrik/plugins/element/fabrikyesno/images/1.png' alt='" . $label . "' />" ;
		} else {
			$value = "<img src='".COM_FABRIK_LIVESITE."components/com_fabrik/plugins/element/fabrikyesno/images/0.png' alt='" . $label . "' />";
		}
		return $value;
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		return parent::render($data, $repeatCounter);
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
		//$groupModel =& $this->_group;
		$groupModel =& $this->getGroup();
		return ($groupModel->canRepeat()) ? "VARCHAR(255)" : "TINYINT(1)";
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$params =& $this->getParams();
		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts = json_encode($opts);
		return "new fbYesno('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikyesno/', false);
	}

	/**
	 * render admin settings
	 */

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php echo $pluginParams->render();?></div>
		<?php
	}

	/**
	 * Get the table filter for the element
	 * @param bol do we render as a normal filter or as an advanced searc filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 * @return string filter html
	 */

	function getFilter($counter = 0, $normal = true)
	{
		$table 				=& $this->getTableModel()->getTable();
		$elName 			= $this->getFullName(false, true, false);
		$htmlid				= $this->getHTMLId() . 'value';
		$elName 			= FabrikString::safeColName($elName);
		$v = 'fabrik___filter[table_'.$table->id.'][value]';
		$v .= ($normal) ? '['.$counter.']' : '[]';

		$default 			=  $this->getDefaultFilterVal($normal, $counter);

		$rows = $this->filterValueList($normal);
		$return 	 = JHTML::_('select.genericlist', $rows, $v, 'class="inputbox fabrik_filter" size="1" ', 'value', 'text', $default, $htmlid);
		if ($normal) {
			$return .= $this->getFilterHiddenFields($counter, $elName);
		} else {
			$return .= $this->getAdvancedFilterHiddenFields();
		}
		return $return;
	}

	/**
	 * (non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelElement#filterValueList_Exact($normal, $tableName, $label, $id, $incjoin)
	 */

	protected function filterValueList_Exact($normal, $tableName = '', $label = '', $id = '', $incjoin = true )
	{
		$opt = array(JHTML::_('select.option', '', $this->filterSelectLabel()));
		$rows = parent::filterValueList_Exact($normal, $tableName, $label, $id, $incjoin);
		foreach ($rows as &$row) {
			if ($row->value == 1) { $row->text = JText::_('yes'); }
			if ($row->value == 0) { $row->text = JText::_('no'); }
		}
		$rows = array_merge($opt, $rows);
		return $rows;
	}

	/**
	 *
	 * @param unknown_type $normal
	 * @param unknown_type $tableName
	 * @param unknown_type $label
	 * @param unknown_type $id
	 * @param unknown_type $incjoin
	 */

	protected function filterValueList_All($normal, $tableName = '', $label = '', $id = '', $incjoin = true )
	{
		$rows = array(
		JHTML::_('select.option', '', $this->filterSelectLabel()),
		JHTML::_('select.option', '0', JText::_('no')),
		JHTML::_('select.option', '1', JText::_('yes'))
		);
		return $rows;
	}

	/**
	 * (non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelElement#getFilterCondition()
	 */
	protected function getFilterCondition()
	{
		return '=';
	}

	/**
	 *
	 */

	function getAdminJS()
	{
		//leave this method as if we remove it fabrikradiobutton admin js gets added again
	}

	/**
	 * (non-PHPdoc)
	 * @see FabrikModelElement::canToggleValue()
	 */

	public function canToggleValue()
	{
		return true;
	}
}
?>
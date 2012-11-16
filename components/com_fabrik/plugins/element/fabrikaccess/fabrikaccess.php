<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelfabrikAccess  extends FabrikModelElement {

	var $_pluginName = 'access';

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
		// $$$ hugh - nope!
		//return $val[0];
		return $val;
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);

		$arSelected = array('');

		if (isset($data[$name])) {

			if (!is_array($data[$name])) {
				$arSelected = explode(',', $data[$name]);
			} else {
				$arSelected = $data[$name];
			}
		}
		$gtree =& $this->getOpts();
		if (!$this->_editable) {
			return $this->renderTableData($arSelected[0], null);
		}
		return JHTML::_('select.genericlist', $gtree, $name, 'class="inputbox" size="6"', 'value', 'text', $arSelected[0]);
	}

	protected function filterValueList_All($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		return $this->getOpts();
	}

	/**
	 * (non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelElement::_buildFilterJoin()
	 */

	protected function filterValueList_Exact($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$tableModel  	= $this->getTableModel();
		$fabrikDb 		=& $tableModel->getDb();
		$table		=& $tableModel->getTable();
		$elName2 		= $this->getFullName(false, false, false);
		$ids = $tableModel->getColumnData($elName2);

		$elName 			= FabrikString::safeColName($this->getFullName(false, true, false));
		$sql = 'SELECT name AS '.$fabrikDb->nameQuote('text').', id AS '.$fabrikDb->nameQuote('value').' from #__core_acl_aro_groups '. "WHERE id IN ('" . implode("','", $ids) . "')";
		$fabrikDb->setQuery($sql);
		$rows = $fabrikDb->loadObjectList();
		$this->nameMap($rows);
		return $rows;
	}

	protected function nameMap(&$rows)
	{
		$params =& $this->getParams();
		$map = $params->get('acl_name_map');
		if ($map == '') {
			return;
		}
		$map = str_replace("'", '"', $map);
		$map = json_decode($map);
		if (!is_object($map)) {
			return;
		}
		foreach ($map as $key => $val) {
			foreach ($rows as &$row) {
				if (strstr($row->text, $key)) {
					$row->text = $val;
				}
			}
		}
	}

	private function getOpts()
	{
		$acl =& JFactory::getACL();
		$gtree = $acl->get_group_children_tree( null, 'USERS', false);
		$optAll = array(JHTML::_('select.option', '30', ' - Everyone'), JHTML::_('select.option', "26", 'Nobody'));
		return array_merge($gtree, $optAll);
	}

	function renderTableData($data, $oAllRowsData)
	{
		$gtree =& $this->getOpts();
		$user = JFactory::getUser();
		if ($user->get('id') == 62) {
			//echo "<pre>";print_r($gtree);echo "</pre>";
		}
		$this->nameMap($gtree);
	if ($user->get('id') == 62) {
		//	echo "<pre>";print_r($gtree);echo "</pre>";
		}
		$filter = & JFilterInput::getInstance(null, null, 1, 1);
		foreach ($gtree as $o) {
			if ($o->value == $data) {
				return ltrim($filter->clean($o->text, 'word'), '&nbsp;');
			}
		}
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
		return "INT(3)";
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
		return "new FbAccess('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikaccess/', false);
	}

	function renderAdminSettings()
	{
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php echo $pluginParams->render();?></div>
		<?php
	}



}
?>
<?php

/**
* Add an action button to the table to show a pivot view
* @package Joomla
* @subpackage Fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-table.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');

class FabrikModelPivot extends FabrikModelTablePlugin {

	var $_counter = null;

	var $_buttonPrefix = 'pivot';

	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * get the pivot view heading
	 */

	public function getHeading()
	{
		$model =& $this->formModel->getTableModel();
		$params =& $model->getParams();
		return $params->get('pivot_heading', 'Pivot view');
	}

	/**
	 * create the pivot data
	 * @return array of objects - first being the headings, subsequent the data
	 */

	public function getPivot()
	{
		$model = $this->formModel->getTableModel();

		$params =& $model->getParams();
		$val = FabrikString::safeColName($params->get('pivot_value', ''));
		$xCol = FabrikString::safeColName($params->get('pivot_xcol', ''));
		$yCol = FabrikString::safeColName($params->get('pivot_ycol', ''));

		$db =& $model->getDb();
		$table =& $model->getTable();
		$join = $model->_buildQueryJoin();
		$where = $model->_buildQueryWhere();

		$db->setQuery("SELECT DISTINCT $yCol FROM $table->db_table_name");
		$yCols = $db->loadResultArray();

		$query = "select name,\n";
		$data = array();
		foreach ($yCols as $c) {
			$data[] = "SUM($val*(1-abs(sign(".$yCol."-".$c.")))) as exam".$c."\n";
		}
		$query .= implode(",", $data);
		$query .= "\nFROM ".$table->db_table_name." $join $where group by $xCol";

		$db->setQuery($query);
		$data = $db->loadObjectList();

		$headings = JArrayHelper::toObject(array_keys(JArrayHelper::fromObject($data[0])));
		array_unshift($data, $headings);
		return $data;
	}

	function button()
	{
		return "pivot";
	}

	/**
	 * return the button to the plugin manager
	 * @param int repeat group counter
	 */

	function button_result($c)
	{
		if ($this->canUse()) {
			$params =& $this->getParams();
			$loc = $params->get('emailtable_button_location', 'bottom');
			if ($loc == 'bottom' || $loc == 'both') {
				return $this->getButton();
			} else {
				return '';
		}
		}
	}

	/**
	 * build the button
	 */

	protected function getButton()
	{
		$params =& $this->getParams();
		$name = $this->_getButtonName();
		if ($this->canUse()) {
			$label = $params->get('pivot_button_label', JText::_('PLG_LIST_PIVOT_PIVOT'));
			return "<input type=\"button\" name=\"$name\" value=\"". $label . "\" class=\"button tableplugin\"/>";
		}
		return '';
	}

	/**
	 * parameter name which defines which user group can access the pivot view
	 */

	function getAclParam()
	{
		return 'pivot_access';
	}


	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 *
	 * @return bol
	 */

	function canSelectRows()
	{
		return true;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function loadJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/table/pivot/', false);
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @param object parameters
	 * @param object table model
	 * @param array [0] => string table's form id to contain plugin
	 * @return bool
	 */

	function loadJavascriptInstance($params, $model, $args)
	{
		$form_id = $args[0];
		$opts = new stdClass();
		$opts->liveSite = COM_FABRIK_LIVESITE;
		$opts->name = $this->_getButtonName();
		$opts->mooversion = (FabrikWorker::getMooVersion() == 1) ? 1.2 : 1.1;
		$opts->renderOrder = $this->renderOrder;
		//$opts->tmpl
		$opts = json_encode($opts);
		$lang = $this->_getLang();
		$lang = json_encode($lang);
		$this->jsInstance = "new fbTablePivot('$form_id', $opts, $lang)";
		return true;
	}

}
?>
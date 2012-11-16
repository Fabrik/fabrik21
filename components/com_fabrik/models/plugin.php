<?php

/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'parent.php'); //required for fabble

class FabrikModelPlugin extends FabrikModel
{

	/** @var bol determines if the admin settings are visible or hidden when rendered */
	var $_adminVisible = false;

	/** @var string path to xml file **/
	var $_xmlPath = null;

	/** @var object params **/
	var $_params  = null;

	var $attribs = null;

	var $_id = null;

	var $_row = null;

	/** @var int order that the plugin is rendered */
	var $renderOrder = null;

	/** @var object params for this given plugin **/
	var $pluginParams = null;

	var $_counter;

	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function setId($id)
	{
		if ($id !== $this->_id) {
			// $$$ rob - if we change the id we need to unset the following for
			// getParams() and getRow() to be loaded correctly (for cron plugins)
			unset($this->_row);
			unset($this->_params);
			unset($this->attribs);
		}
		$this->_id = $id;
	}

	 function getId()
	 {
	 	return $this->_id;
	 }

	function setParams(&$params, &$usedLocations = array(), &$usedEvents = array())
	{
		//for table
		if (!array_key_exists($this->renderOrder, $usedLocations)) {
			$usedLocations[$this->renderOrder] = '';
		}
		if (!array_key_exists($this->renderOrder, $usedEvents)) {
			$usedEvents[$this->renderOrder] = '';
		}

		if (isset($this->pluginParams)) {
			return $this->pluginParams;
		}
		//get some blank parameter properties
		$this->pluginParams = new fabrikParams('', $this->_xmlPath, 'fabrikplugin');

		$names =& $this->pluginParams->getParamsNames('params', '_default');
		$tmpAttribs = '';
		//build a temp attributes string to pass to the parameters object
		if (is_array($names)) {
			foreach ($names as $name) {

				$pluginElOpts = $params->get($name, "", "_default", "array");
				$val = JArrayHelper::getValue($pluginElOpts, $this->renderOrder, '');
				// $$$ hugh test fix for |
				$val = str_replace('\|', '|', $val);
				$val = str_replace('|', '\|', $val);
				$tmpAttribs .= $name . "=" . $val . "\n";
			}
		}
		//redo the params with the exploded data
		// $$$ hugh - this is screwing up stuff with | in it, like regex expressions,
		// because the J! loadINI calls stringToObject from ini.php, which explodes on |
		// (see test fix above)
		$this->pluginParams = new fabrikParams($tmpAttribs, $this->_xmlPath, 'fabrikplugin');

		//$$$ rob ensure that getParams() returns the correct raw xml data set containing the exact plug-in
		//settings and not just the val/**/val2 string
		$this->_params = $this->pluginParams;
		return $this->pluginParams;
	}

	/**
	 * write out the admin form for customising the plugin
	 *
	 * @param object $row
	 */

	function renderAdminSettings()
	{
		/* can be overwritten by action plugin */
		$params =& $this->getParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="pluginSettings"
	style="display: none">
<table>
<?php echo $params->render();?>
</table>
</div>
<?php
	}

	/**
	 * load params
	 */

	function &getParams()
	{
		if (!isset($this->_params)) {
			return $this->_loadParams();
		}else{
			return $this->_params;
		}
	}

	function &_loadParams()
	{
		if (!isset($this->attribs)) {
			$row =& $this->getRow();
			$a =& $row->attribs;
		} else {
			$a =& $this->attribs;
		}
		if (!isset($this->_params)) {
			$this->_params = new fabrikParams($a, $this->_xmlPath, 'component');
		}
		return $this->_params;
	}

	function getRow()
	{
		if (!isset($this->_row)) {
			$this->_row =& $this->getTable($this->_type);
			$this->_row->load($this->_id);
		}
		return $this->_row;
	}

	/**
	 * determine if we use the plugin or not
	 * both location and event criteria have to be match
	 * @param object calling the plugin table/form
	 * @param string location to trigger plugin on
	 * @param string event to trigger plugin on
	 * @return bol true if we should run the plugin otherwise false
	 */

	function canUse(&$model, $location, $event)
	{
		$ok = false;
		$app =& JFactory::getApplication();
		switch ($location) {
			case 'front':
				if (!$app->isAdmin()) {
					$ok = true;
				}
				break;
			case 'back':
				if ($app->isAdmin()) {
					$ok = true;
				}
				break;
			case 'both':
				$ok = true;
				break;
		}
		if ($ok) {
			$k = array_key_exists('_origRowId', $model) ? '_origRowId' : '_rowId';
			switch ($event) {
				case 'new':
					if ($model->$k != 0) {
						$ok = false;
					}
					break;
				case 'edit':
					if ($model->$k == 0) {
						$ok = false;
					}
					break;
			}
		}
		return $ok;
	}

	function customProcessResult()
	{
		return true;
	}

	/**
	 * ajax function to return a string of table drop down options
	 * based on cid variable in query string
	 *
	 */
	function ajax_tables()
	{
		$db =& JFactory::getDBO();
		$cid = JRequest::getVar('cid', -1);
		$showFabrikTables = JRequest::getVar('showf', false);
		if ($showFabrikTables) {
			$sql = "SELECT id, label FROM #__fabrik_tables WHERE connection_id = ".(int)$cid." ORDER BY label ASC";
			$db->setQuery($sql);
			$rows = $db->loadObjectList();
			$default = new stdClass;
			$default->id = '';
			$default->label = JText::_('COM_FABRIK_PLEASE_SELECT');
			array_unshift($rows, $default);
		} else {
			$cnn = JModel::getInstance('Connection', 'FabrikModel');
			$cnn->setId($cid);
			$db =& $cnn->getDb();
			$db->setQuery("SHOW TABLES");
			$rows = (array)$db->loadResultArray();
			array_unshift($rows, '');
		}
		echo json_encode($rows);
	}

	function ajax_fields()
	{
		$tid = JRequest::getVar('t');
		$keyType = JRequest::getVar('k', 1);
		$showAll = JRequest::getVar('showall', false);//if true show all fields if false show fabrik elements

		//only used if showall = false, includes validations as separate entries
		$incCalculations = JRequest::getVar('calcs', false);
		$arr = array(JHTML::_('select.option', '', JText::_('COM_FABRIK_PLEASE_SELECT')));
		if ($showAll) { //show all db columns
			$cid = JRequest::getVar('cid', -1);
			$cnn = JModel::getInstance('Connection', 'FabrikModel');
			$cnn->setId($cid);
			$db =& $cnn->getDb();
			if ($tid != '') {
				$db->setQuery("DESCRIBE ".$db->nameQuote($tid));

				$rows = $db->loadObjectList();
				if (is_array($rows)) {
					foreach ($rows as $r) {
						$c = new stdClass();
						$c->value = $r->Field;
						$c->label = $r->Field;
						$arr[] = $c; //dont use =&
					}
				}
			}
		} else {
			//show fabrik elements in the table
			//$keyType 1 = $element->id;
			//$keyType 2 = tablename___elementname
			$model =& JModel::getInstance('Table', 'FabrikModel');
			$model->setId($tid);
			$table =& $model->getTable();
			$groups = $model->getFormGroupElementData();
			$published = JRequest::getVar('published', false);
			$showintable = JRequest::getVar('showintable', false);
			foreach ($groups as $g => $groupModel) {
				if ($groupModel->isJoin()) {
					if (JRequest::getVar('excludejoined') == 1) {
						continue;
					}
					$joinModel =& $groupModel->getJoinModel();
					$join =& $joinModel->getJoin();
				}
				if ($published == true) {
					$elementModels =& $groups[$g]->getPublishedElements();
				} else {
					$elementModels =& $groups[$g]->getMyElements();
				}

				foreach ($elementModels as $e => $eVal) {
					$element =& $eVal->getElement();
					if ($showintable == true && $element->show_in_table_summary == 0) {
						continue;
					}
					if ($keyType == 1) {
						$v = $element->id;
					} else {
						//@TODO if in repeat group this is going to add [] to name - is this really
						// what we want? In timeline viz options i've simply stripped out the [] off the end
						// as a temp hack
						$v = $eVal->getFullName(false);
					}
					$c = new stdClass();
					$c->value = $v;
					$label = FabrikString::getShortDdLabel( $element->label);
					if ($groupModel->isJoin()) {
						$label = $join->table_join.'.'.$label;
					}
					$c->label = $label;
					$arr[] = $c; //dont use =&
					if ($incCalculations) {
						$params =& $eVal->getParams();
						if ($params->get('sum_on', 0)) {
							$c = new stdClass();
							$c->value = 'sum___'.$v;
							$c->label = JText::_('SUM') . ": " .$label;
							$arr[] = $c; //dont use =&
						}
						if ($params->get('avg_on', 0)) {
							$c = new stdClass();
							$c->value = 'avg___'.$v;
							$c->label = JText::_('AVERAGE') . ": " .$label;
							$arr[] = $c; //dont use =&
						}
						if ($params->get('median_on', 0)) {
							$c = new stdClass();
							$c->value = 'med___'.$v;
							$c->label = JText::_('MEDIAN') . ": " .$label;
							$arr[] = $c; //dont use =&
						}
						if ($params->get('count_on', 0)) {
							$c = new stdClass();
							$c->value = 'cnt___'.$v;
							$c->label = JText::_('COUNT') . ": " .$label;
							$arr[] = $c; //dont use =&
						}
					}
				}
			}
		}
		echo json_encode($arr);
	}


	function getAdminJs($form, $lists)
	{
		$opts = $this->getAdminJsOpts($form, $lists);
		$opts = json_encode($opts);
		$script = "new fabrikAdminPlugin('{$this->row->name}', '$this->_pluginLabel', $opts)";
		return $script;
	}

	function getAdminJsOpts($form, $lists)
	{
		$params =& $this->getParams();
		$params->_duplicate = true;
		$opts = new stdClass();
		$opts->livesite = COM_FABRIK_LIVESITE;
		$opts->html = $this->renderAdminSettings($this->row->name, $this->row, $params, $lists, 0);
		return $opts;
	}

	/**
	 * used in cron plugins
	 * can be overridden per plugin
	 */

	function requiresTableData() {
		/* whether cron should automagically load table data */
		return true;
	}

	function requiresPrefilters() {
		/* if requiresTableData(), should prefilters be applied */
		return true;
	}

	function getLog()
	{
		return '';
	}

	/**
	 * only applicable to cron plugins but as there's no sub class for them
	 * the methods here for now
	 * deterimes if the cron plug-in should be run - if require_qs is true
	 * then fabrik_cron=1 needs to be in the querystring
	 */

	public function queryStringActivated()
	{
		$params =& $this->getParams();
		if (!$params->get('require_qs')) {
			// querystring not required so plugin should be activated
			return true;
		}
		return JRequest::getInt('fabrik_cron', 0);
	}

	/**
	 * if true then the plugin is stating that any subsequent plugin in the same group
	 * should not be run.
	 * @param string current plug-in call method e.g. onBeforeStore
	 * @return bool
	 */

	public function runAway($method)
	{
		return false;
	}

/**
	 * process the plugin, called when form is submitted
	 *
	 * @param string param name which contains the PHP code to eval
	 * @param array data
	 */

	function shouldProcess($paramName, $data = null)
	{
		if (is_null($data)) {
			$data = $this->data;
		}
		$params =& $this->getParams();
		$condition = $params->get($paramName);
		if (trim($condition) == '') {
			return true;
		}
		$w = new FabrikWorker();
		$condition = trim($w->parseMessageForPlaceHolder($condition, $data));
		$res = @eval($condition);
		if (is_null($res)) {
			return true;
		}
		return $res;
	}
}
?>
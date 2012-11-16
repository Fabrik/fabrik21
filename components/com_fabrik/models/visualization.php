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

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'plugin.php');

class FabrikModelVisualization extends FabrikModelPlugin {

	var $_pluginParams = null;

	var $_row = null;

	//@var string url for filter form
	var $getFilterFormURL = null;

	var $srcBase = "components/com_fabrik/plugins/visualization/";

	/**
	 * constructor
	 */

	function __construct()
	{
		$this->pathBase = JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'plugins'.DS.'visualization'.DS;
		parent::__construct();
	}

	function getPluginParams()
	{
		if (!isset($this->_pluginParams)) {
			$this->_pluginParams = $this->_loadPluginParams();
		}
		return $this->_pluginParams;
	}

	/**
	 * load visualization plugin  params
	 * @access private - public call = getPluginParams()
	 *
	 * @return object visualization plugin parameters
	 */

	function _loadPluginParams()
	{
		$this->getVisualization();
		$pluginParams = new fabrikParams($this->_row->attribs, $this->_xmlPath, 'fabrikplugin');
		return $pluginParams;
	}

	function getVisualization()
	{
		if (!isset($this->_row)) {
			$this->_row =& $this->getTable('Visualization');
			$this->_row->load($this->_id);
		}
		return $this->_row;
	}

	function render()
	{
		//overwrite in plugin
	}


	/**
	 * get the vizualizations table models
	 *
	 * @return array table objects
	 */

	function getTableModels()
	{
		if (!isset($this->tables)) {
			$this->tables = array();
		}
		foreach ($this->tableids as $id) {
			if (!array_key_exists($id, $this->tables)) {
				$tableModel =& JModel::getInstance('Table', 'FabrikModel');
				$tableModel->setId($id);
				$tableModel->getTable();
				$this->tables[$id] = $tableModel;
			}
		}
		return $this->tables;
	}

	/**
	 * get a table model
	 * @param int $id
	 * @return object fabrik table model
	 */

	protected function &getTableModel($id)
	{
		$tables =& $this->getTableModels();
		return $tables[$id];
	}

	function getGalleryTableId()
	{
		$params =& $this->getParams();
		return $params->get('gallery_category_table');
	}

	function getContainerId()
	{
		$viz = $this->getVisualization();
		return $viz->plugin."_".$viz->id;
	}
	/**
	 * get all table models filters
	 * @return array table filters
	 */

	function getFilters()
	{
		FabrikHelperHTML::packageJS();
		$params 		=& $this->getParams();
		$tableModels =& $this->getTableModels();
		$filters = array();
		foreach ($tableModels as $tableModel) {
			$filters[$tableModel->getTable()->label] = $tableModel->getFilters($this->getContainerId(), 'vizualization', $this->_row->id);
		}
		$this->getRequireFilterMsg();
		return $filters;
	}

	/**
	 * set the url for the filter form's action
	 * @return string action url
	 */

	public function getFilterFormURL()
	{
		if (isset($this->getFilterFormURL)) {
			return $this->getFilterFormURL;
		}
		$option = JRequest::getCmd('option');
		// Get the router
		$app	= &JFactory::getApplication();
		$router = &$app->getRouter();

		$uri = clone(JURI::getInstance());
		// $$$ rob force these to be 0 once the menu item has been loaded for the first time
		//subsequent loads of the link should have this set to 0. When the menu item is re-clicked
		//rest filters is set to 1 again
		$router->setVar('resetfilters', 0);
		if ($option !== 'com_fabrik') {
			// $$$ rob these can't be set by the menu item, but can be set in {fabrik....}
			$router->setVar('clearordering', 0);
			$router->setVar('clearfilters', 0);
		}
		$queryvars = $router->getVars();
		$page = "index.php?";
		foreach ($queryvars as $k => $v) {
			$qs[] = "$k=$v";
		}
		$action = $page . implode("&amp;", $qs);
		//limitstart gets added in the pageination model
		$action = preg_replace("/limitstart{$this->_id}=(.*)?(&|)/", "", $action);
		$action = FabrikString::rtrimword($action, "&");
		$this->getFilterFormURL	= JRoute::_($action);
		return $this->getFilterFormURL;
	}

	function getRequireFilterMsg()
	{

		$tableModels =& $this->getTableModels();
		foreach ($tableModels as $model) {
			$params =& $model->getParams();

			$filters	=& $model->getFilterArray();

			$ftypes = JArrayHelper::getValue($filters, 'search_type', array());
			for ($i = count($ftypes) - 1; $i >= 0; $i--) {
				if (JArrayHelper::getValue($ftypes, $i) == 'prefilter') {
					unset($ftypes[$i]);
				}
			}

			if ($params->get('require-filter', true) && empty($ftypes)) {
				JError::raiseNotice(500, JText::_('PLEASE_SELECT_ALL_REQUIRED_FILTERS'));
			}
		}
		if (!$this->getRequiredFiltersFound()) {
			JError::raiseNotice(500, JText::_('PLEASE_SELECT_ALL_REQUIRED_FILTERS'));
		}
	}

	/**
	 * should be overwritten in plugin viz model
	 * @abstract
	 */
	function setTableIds()
	{
	}

	/**
	 * have all the required filters been met?
	 *
	 * @return bol true if they have if false we shouldnt show the table data
	 */

	function getRequiredFiltersFound()
	{
		$tableModels =& $this->getTableModels();
		$filters = array();
		foreach ($tableModels as $tableModel) {
			if (!$tableModel->getRequiredFiltersFound()) {
				return false;
			}
		}
		return true;
	}

	/**
	 * load in any table plugin classes
	 * needed for radius search filter
	 */

	function getPluginJsClasses()
	{
		$str = array();
		$tableModels =& $this->getTableModels();
		foreach ($tableModels as $model) {
			$str[] = $model->getPluginJsClasses();
		}
		return implode("\n", $str);
	}

	/**
	 * get the js code to create instances of js table plugin classes
	 * needed for radius search filter
	 */

	function getPluginJsObjects()
	{
		$str = array();
		$tableModels =& $this->getTableModels();
		foreach ($tableModels as $model) {
			$tmp = $model->getPluginJsObjects($this->getContainerId());
			foreach ($tmp as $t) {
				$str[] = $t;
			}
		}
		return implode("\n", $str);
	}
	
	function getId() {
		return $this->_id;
	}
	
	function getCustomJsAction() {
		if (file_exists(COM_FABRIK_FRONTEND.DS.'js'.DS.'viz_'.$this->getId().".js")) {
			FabrikHelperHTML::script('viz_' . $this->getId() . ".js", 'components/com_fabrik/js/');
		}
	}
	
}


?>
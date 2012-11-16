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

class FabrikModelTablePlugin extends FabrikModelPlugin
{
	/** determines if the plugin requires mocha to be loaded */
	var $useMocha = false;

	var $_buttonPrefix = '';

	var $jsInstance = null;

	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
		$usersConfig = &JComponentHelper::getParams('com_fabrik');
		$id = JRequest::getVar('tableid', $usersConfig->get('tableid'));
		$this->setId($id);
	}

	function button_result()
	{
		return '';
	}

	protected function getButton()
	{
		return '';
	}

	/**
	 * get the parameter name that defines the plugins acl access
	 * @return string
	 */

	function getAclParam()
	{
		return '';
	}

	function canUse(&$model = null, $location = null, $event = null)
	{
		$aclParam = $this->getAclParam();
		if ($aclParam == '') {
			return true;
		}
		$params =& $this->getParams();
		$access = $params->get($aclParam);
		$name = $this->_getButtonName();
		return FabrikWorker::getACL($access, $name);
	}

	/**
	 * @param $form_id string name of form js class
	 */

	function loadJavascriptInstance($form_id)
	{
		return true;
	}

	/**
	 * onGetData method
	 *
	 * @param object calling the plugin table/form
	 * @return bol currently ignored
	 */

	function onLoadData(&$params, &$oRequest)
	{
		return true;
	}

	/**
	 * onFiltersGot method - run after the table has created filters
	 *
	 * @param object calling the plugin table/form
	 * @return bol currently ignored
	 */

	function onFiltersGot(&$params, &$oRequest)
	{
		return true;
	}

	function requiresMocha()
	{
		return $this->useMocha;
	}

	/**
	 * provide some default text that most table plugins will need
	 * (this object will then be json encoded by the plugin and passed
	 * to it's js class
	 * @return object language
	 */

	function _getLang()
	{
		$lang = new stdClass();
		$lang->selectrow = JText::_('PLEASE SELECT A ROW');
		return $lang;
	}

	/**
	 * show a new for entering the form actions options
	 */

	function renderAdminSettings($elementId, &$row, &$params, $lists, $c)
	{
		$params->_counter_override = $this->_counter;
		$display =  ($this->_adminVisible) ? "display:block" : "display:none";
		$return = '<div class="page-' . $elementId . ' elementSettings" style="' . $display . '">
 		' . $params->render('params', '_default', false, $c) .
 		'</div>
 		';
		return str_replace("\r", "", $return);
	}

	/**
	 * get the html name for the button
	 * @return string
	 */

	function _getButtonName()
	{
		return $this->_buttonPrefix."-".$this->renderOrder;
	}

	/**
	 * prefilght check to ensure that the tabel plugin should process
	 * @param object $params
	 * @param object $model
	 * @return string|boolean
	 */

	function process_preflightCheck(&$params, &$model)
	{
		if ($this->_buttonPrefix == '') {
			return false;
		}
		$postedRenderOrder = JRequest::getInt('fabrik_tableplugin_renderOrder', -1);
		return JRequest::getVar('fabrik_tableplugin_name') == $this->_buttonPrefix && $this->renderOrder == $postedRenderOrder;
	}

	/**
	 * get a key name specific to the plugin class to use as the reference
	 * for the plugins filter data
	 * (Normal filter data is filtered on the element id, but here we use the plugin name)
	 * @return string key
	 */

	public function getFilterKey()
	{
		$key = get_class($this);
		return JString::strtolower(str_replace('FabrikModel', '', $key));
	}

	/**
	 * plugins should use their own name space for storing their sesssion data
	 * e.g radius search plugin stores its search values here
	 */

	protected function getSessionContext()
	{
		return 'com_fabrik.list'. $this->model->getTable()->id.'.plugins.'.$this->getFilterKey().'.';
	}

	/**
	 * used to assign the js code created in loadJavascriptInstance()
	 * to the table view.
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function loadJavascriptInstance_result()
	{
		return $this->jsInstance;
	}

	/**
	 *
	 * allows to to alter the table's select query
	 * @param object $params
	 * @param object table model
	 * @param array arguements - first value is an object with a query
	 * property which contains the current query as an array keyed on 'select','join','grouby','order':
	 * $args[0]->query
	 */

	public function onQueryBuilt(&$params, &$model, &$args)
	{

	}

	/**
	 * @since 2.0.5
	 * inject a button into each row.
	 * @param unknown_type $params
	 * @param unknown_type $model
	 * @param unknown_type $args
	 */

	public function onGetPluginRowButtons(&$params, &$model, &$data)
	{
		if ($this->canSelectRows()) {
			$button = $this->getButton();
			$data =& $data[0];
			$bname = $this->_getButtonName();
			foreach ($data as $groupKey=>$group) {
				//$group =& $data[$key]; //Messed up in php 5.1 group positioning in data became ambiguous
				$cg = count($group);
				for ($i=0; $i < $cg; $i++) {
					$row =& $data[$groupKey][$i];
					$row->$bname = $button;
				}
			}
		}
	}

	public function canSelectRows()
	{
		return true;
	}

	/**
	 * @abstract
	 * Get button label
	 * @return string
	 */

	public function getButtonLabel()
	{
		return 'click me';
	}

	/**
	 * @since 2.0.5
	 *
	 * @param unknown_type $params
	 * @param unknown_type $model
	 * @param unknown_type $data
	 */

	public function onGetPluginRowHeadings(&$params, &$model, &$args)
	{
		if ($this->renderInRow()) {
			$bname = $this->_getButtonName();
			$args[0]['tableHeadings'][$bname] = $this->getButtonLabel();
			$args[0]['headingClass'][$bname] = "class=\"$bname\"";
			$args[0]['cellClass'][$bname] = "class=\"fabrik_row___".$bname."\"";
		}
	}

	/**
	 * @abstract
	 * get the position for the button
	 */

	protected function getRenderLocation()
	{
		return '';
	}

	/**
	 * should the button (if the plugin allows for one) be render in the row
	 * @return bool
	 */

	function renderInRow()
	{
		if (!$this->canSelectRows()) {
			return false;
		}
		$render = $this->getRenderLocation();
		return ($render == 'both' || $render == 'perrow');
	}

}
?>
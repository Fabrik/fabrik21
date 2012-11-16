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

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'plugin-table.php');

class fabrikModelEmailtableplus extends FabrikModelTablePlugin {

	var $_buttonPrefix = 'emailtableplus';

	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
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
		$return = str_replace("\r", "", $return);
		return $return;
	}

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 *
	 * @return bol
	 */

	function canSelectRows()
	{
		$params =& $this->getParams();
		$access = $params->get('emailtableplus_access');
		$name = $this->_getButtonName();
		$canuse = FabrikWorker::getACL($access, $name);
		return $canuse;
	}

	function button()
	{
		return "email records";
	}

	function button_result()
	{
		if ($this->canUse()) {
			$name = $this->_getButtonName();
			return '<input type="button" name="'.$name.'" value="' . JText::_('EMAIL') . '" class="button tableplugin" onclick="oTable'.$this->model->_id.'.plugins[' . $this->renderOrder . '].watchPluginButton()"/>';
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see FabrikModelTablePlugin::getAclParam()
	 */

	function getAclParam()
	{
		return 'emailtableplus_access';
	}


	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function loadJavascriptClass()
	{
		FabrikHelperHTML::mocha();
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/table/emailtableplus/', false);
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
		$fbConfig =& JComponentHelper::getParams('com_fabrik');
		$opts = new stdClass();
		$opts->liveSite	= COM_FABRIK_LIVESITE;
		$opts->renderOrder = $this->renderOrder;
		$this->model =& $model;
		$opts->mooversion = ($fbConfig->get('usefabrik_mootools', false)) ? 1.2 : 1.1;
		$opts = json_encode($opts);
		$this->jsInstance = "new fbTableEmailPlus('$form_id', $opts)";
		return true;
	}
}


?>
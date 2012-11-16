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

class fabrikModelChown extends FabrikModelTablePlugin {

	var $_buttonPrefix = 'chown';

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
		$access = $params->get('chown_access');
		$name = $this->_getButtonName();
		$canuse = FabrikWorker::getACL($access, $name);
		return $canuse;
	}

	function button()
	{
		return "change owner";
	}

	function button_result()
	{
		$params =& $this->getParams();
		if ($this->canUse()) {
			$name = $this->_getButtonName();
			return '<input type="button" name="'.$name.'" value="' . $params->get('chown_button_label') . '" class="button tableplugin" onclick="oTable'.$this->model->_id.'.plugins[' . $this->renderOrder . '].watchPluginButton()"/>';
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see FabrikModelTablePlugin::getAclParam()
	 */

	function getAclParam()
	{
		return 'chown_access';
	}


	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function loadJavascriptClass()
	{
		FabrikHelperHTML::mocha();
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/table/chown/', false);
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
		$opts->window_title = $params->get('chown_button_label');
		$opts->window_width = (int)$params->get('chown_window_width', 300);
		$opts->window_height = (int)$params->get('chown_window_height', 100);
		$opts->liveSite	= COM_FABRIK_LIVESITE;
		$opts->renderOrder = $this->renderOrder;
		$this->model =& $model;
		$opts->mooversion = ($fbConfig->get('usefabrik_mootools', false)) ? 1.2 : 1.1;
		$opts = json_encode($opts);
		$this->jsInstance = "new fbTableChown('$form_id', $opts)";
		return true;
	}
}


?>

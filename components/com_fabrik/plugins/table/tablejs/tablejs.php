<?php

/**
 * Add an action button to the table to run js
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

//@TODO this doesnt work if you have a module on the same page pointing to the same table

class FabrikModelTableJS extends FabrikModelTablePlugin {

	var $_counter = null;

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 *
	 * @return bol
	 */

	function canSelectRows()
	{
		$params = $this->getParams();
		return $params->get('tablejs_select_rows', false);
		//return false;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function loadJavascriptClass()
	{
		//@TODO this looks wrong
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/table/tablephp/', false);
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
		$js = trim($params->get('table_js_code'));
		if ($js !== '') {
			FabrikHelperHTML::addScriptDeclaration($js);
		}
		$this->jsInstance = '';

		//script

		$script = $params->get('table_js_file');
 		if ($script == '-1') {
			return;
		}
		$className = substr($script, 0, strlen($script) -3);

		$document =& JFactory::getDocument();
		$id =& $model->getTable()->id;
		$container = 'oTable';
		if (JRequest::getVar('tmpl') != 'component') {
			FabrikHelperHTML::script($script, 'components/com_fabrik/plugins/table/tablejs/scripts/');
		} else {
			// included scripts in the head don't work in mocha window
			// read in the class and insert it into the body as an inline script
			$class = JFile::read(JPATH_BASE."/components/com_fabrik/plugins/table/tablejs/scripts/$script");
			// $$$ rob dont want/need to delay the loading of the class
			//FabrikHelperHTML::addScriptDeclaration($class);
			$document =& JFactory::getDocument();
			$document->addScriptDeclaration($class);
		}
		$this->jsInstance = "new $className({$container}{$id})";
		return true;
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
		//dont do here as if we json enocde it as we do in admin form view things go wrong
		//return  addslashes(str_replace("\n", "", $return));
	}

}
?>
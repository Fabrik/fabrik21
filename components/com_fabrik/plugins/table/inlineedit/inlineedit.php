<?php

/**
* Allows double-clicking in a cell to enable in-line editing
* @package Joomla
* @subpackage Fabrik
* @author Rob Clayburn
* @copyright (C) Pollen 8 design Ltd
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-table.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');


class FabrikModelInlineedit extends FabrikModelTablePlugin {

	var $_counter = null;

	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * (non-PHPdoc)
	 * @see FabrikModelTablePlugin::getAclParam()
	 */

	function getAclParam()
	{
		return 'inline_access';
	}

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 *
	 * @return bol
	 */

	function canSelectRows()
	{
		return false;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function loadJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/table/inlineedit/', false);
		$document =& JFactory::getDocument();
		$document->addStyleDeclaration('.fabrik_row .focusClass{border:1px solid red !important;}');
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
		FabrikHelperHTML::script('element.js', 'media/com_fabrik/js/');
		$tableModel =& JModel::getInstance('table', 'FabrikModel');
		$tableModel->setId(JRequest::getVar('tableid'));

		$elements =& $tableModel->getElements('filtername');
		$pels = $params->get('inline_editable_elements');
		$use = trim($pels) == '' ? array() : explode(",", $pels);
		$els = array();
		foreach ($elements as $key => $val) {
			$key = FabrikString::safeColNameToArrayKey($key);
			if (empty($use) || in_array($key, $use)) {
				$els[$key] = new stdClass();
				$els[$key]->elid = $val->_id;
				$els[$key]->plugin = $val->getElement()->plugin;
				//load in all element js classes
				$val->formJavascriptClass();
			}
		}
		$opts = new stdClass();
		$opts->mooversion = (FabrikWorker::getMooVersion() == 1 ) ? 1.2 : 1.1;
		$opts->elements 		= $els;
		$opts->tableid 			= $tableModel->_id;
		$opts->focusClass 	= 'focusClass';
		$opts->liveSite 		= COM_FABRIK_LIVESITE;
		$opts->editEvent = $params->get('inline_edit_event', 'dblclick');
		$opts->tabSave = $params->get('inline_tab_save', false);
		$opts->showCancel = $params->get('inline_show_cancel', true);
		$opts->showSave = $params->get('inline_show_save', true);
		$opts->loadFirst = (bool)$params->get('inline_load_first', false);
		$opts = json_encode($opts);
		$lang = $this->_getLang();
		$lang = json_encode($lang);
		$this->jsInstance = "new FbTableInlineEdit('$form_id', $opts, $lang)";
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
 	}

}
?>
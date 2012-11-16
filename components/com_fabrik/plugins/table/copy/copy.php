<?php

/**
 * Add an action button to the table to copy rows
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

class FabrikModelCopy extends FabrikModelTablePlugin {

	var $_counter = null;

	var $_buttonPrefix = 'copy';

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function button()
	{
		return "copy records";
	}

	function button_result($c)
	{
		if ($this->canUse()) {
			$name = $this->_getButtonName();
			return "<input type=\"button\" name=\"$name\" value=\"".JText::_('COPY') . "\" class=\"button tableplugin\"/>";
		}
		return '';
	}

	/**
	 * (non-PHPdoc)
	 * @see FabrikModelTablePlugin::getAclParam()
	 */

	function getAclParam()
	{
		return 'copytable_access';
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
	 * do the plugin action
	 * @param object parameters
	 * @param object table model
	 * @return string message
	 */

	function process(&$params, &$model)
	{
		$ids = JRequest::getVar('ids', array(), 'method', 'array');
		$table =& $model->getTable();
		$formModel =& $model->getForm();
		$origPost = JRequest::get('post', 2);
		JRequest::set(array(), 'post');
		foreach ($ids as $id) {
			$formModel->_rowId = $id;
			$row = $formModel->getData();

			$row['Copy'] = '1';
			$row['fabrik_copy_from_table'] = 1;
			foreach ($row as $key=>$val) {
				JRequest::setVar($key, $val, 'post');
			}
			$formModel->setFormData();
			$formModel->_formDataWithTableName = $formModel->_formData;

			//set the repeat group count for processing joined data
			$repeatCounts = array();
			if (array_key_exists('join', $row)) {
				$aPreProcessedJoins =& $model->preProcessJoin();
				foreach ($row['join'] as $joinid => $joinData) {
					foreach ($aPreProcessedJoins as $aPreProcessedJoin) {
						$oJoin = $aPreProcessedJoin['join'];
						if ($oJoin->id == $joinid) {
							$keys = array_keys($joinData);
							$repeatCounts[$oJoin->group_id] = count($joinData[$keys[0]]);
						}
					}
				}
			}
			JRequest::setVar('fabrik_repeat_group', $repeatCounts, 'post');

			//submit the form.
			$formModel->processToDB();

		}

		JRequest::set(array(), 'post');
		JRequest::set($origPost, 'post', true);
		return true;
	}

	function process_result()
	{
		$ids = JRequest::getVar('ids', array(), 'method', 'array');
		return JText::sprintf( 'ROWS COPIED', count($ids));
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function loadJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/table/copy/', false);
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
		$opts->name = $this->_getButtonName();
		$opts = json_encode($opts);
		$lang = $this->_getLang();
		$lang = json_encode($lang);
		$this->jsInstance = "new fbTableCopy('$form_id', $opts, $lang)";
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
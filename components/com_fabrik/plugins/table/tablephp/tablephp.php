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

//@TODO this doesnt work if you have a module on the same page pointing to the same table

class FabrikModelTablePHP extends FabrikModelTablePlugin {

	var $_counter = null;

	var $_buttonPrefix = 'tablephp';

	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}

	function button()
	{
		return "run php";
	}

	function button_result()
	{
		$params =& $this->getParams();
		if ($this->canUse()) {
			$name = $this->_getButtonName();
			return "<input type=\"button\" name=\"$name\" value=\"". $params->get('table_php_button_label') . "\" class=\"button tableplugin\"/>";
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see FabrikModelTablePlugin::getAclParam()
	 */

	function getAclParam()
	{
		return 'table_php_access';
	}

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 * @return bol
	 */

	function canSelectRows()
	{
		return true;
	}

	/**
	 * do the plug-in action
	 * @param object parameters
	 * @param object table model
	 * @param array custom options
	 */

	function process(&$params, &$model, $opts = array())
	{
		$file = JFilterInput::clean($params->get('table_php_file'), 'CMD');
		if ($file == -1 || $file == '') {
			$code = $params->get('table_php_code');
			$code = @eval($code);
			FabrikWorker::logEval($code, 'Caught exception on eval in tablephp::process() : %s');
		} else {
			require_once(COM_FABRIK_FRONTEND.DS.'plugins'.DS.'table'.DS.'tablephp'.DS.'scripts'.DS.$file);
		}
		return true;
	}

	function process_result()
	{
		$params =& $this->getParams();
		$msg = $params->get('table_php_msg', JText::_('Code run'));
		return $msg;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function loadJavascriptClass()
	{
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
		$opts = new stdClass();
		$opts->name = $this->_getButtonName();
		$opts->additional_data = preg_replace('#\s+#', '', $params->get('table_php_additional_data', ''));
		$opts = json_encode($opts);
		$lang = $this->_getLang();
		$lang = json_encode($lang);
		$this->jsInstance = "new fbTableRunPHP('$form_id', $opts, $lang)";
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
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

class FabrikModelTable_example extends FabrikModelTablePlugin {

	var $_counter = null;

	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * called when the active table filter array is loaded
	 *
	 */

	function onFiltersGot(&$params, &$model) {

	}

	/**
	 * called when the table HTML filters are loaded
	 *
	 */

	function onMakeFilters(&$params, &$model) {
 //echo "<Pre>onMakeFilters: ";print_r($model->viewfilters);echo "</pre>";
	}



		/**
	 * do the plugin action
	 * @param object table model
	 * @return string message
	 */
	function process(&$params, &$model)
	{}

	/**
	 * run before the table loads its data
	 * @param $model
	 * @return unknown_type
	 */
	function onPreLoadData(&$params, &$model)
	{}

	/**
	 * run when the table loads its data(non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelTablePlugin#onLoadData($params, $oRequest)
	 */
	function onLoadData(&$params, &$model)
	{}


	/**
	 * called when the model deletes rows, deleted rows should be in $model->_rowsToDelete,
	 * and will not yet have been deleted.
	 * @param object table $model
	 * @return false if fail
	 */
	function onDeleteRows(&$params, &$model)
	{

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
		$opts = json_encode($opts);
		$lang = $this->_getLang();
		$lang = json_encode($lang);
		$this->jsInstance = "new fbTableCopy('$form_id', $opts, $lang)";
		return true;
	}

}
?>
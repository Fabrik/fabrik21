<?php

/**
 * Observe other table plugins
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

class FabrikModelPhp_observer extends FabrikModelTablePlugin {

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
		return 'php_observer_access';
	}

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 * @return bol
	 */

	function canSelectRows()
	{
		return false;
	}

	/**
	 * do the plug-in action
	 * @param object parameters
	 * @param object table model
	 * @param array custom options
	 */

	function observe(&$params, &$model, $opts = array())
	{
		$method = $opts[1];
		if ($method == $params->get('php_observe_method', 'process')) {
			$file = JFilterInput::clean($params->get('php_observer_file'), 'CMD');
			$file = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'table'.DS.'php_observer'.DS.'scripts'.DS.$file;
			if (JFile::exists($file)) {
				require($file);
			}
		}
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
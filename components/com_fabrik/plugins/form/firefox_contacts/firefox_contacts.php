<?php
/**
 * Firefox contacts - enables http://mozillalabs.com/conceptseries/identity/contacts/
 * for your site - currently works only on element's named 'email'
 * @package Joomla
 * @subpackage Fabrik
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-form.php');

class FabrikModelfirefox_contacts extends FabrikModelFormPlugin {

	/**
	 */
	var $_counter = null;

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * process the plugin, called when form is loaded
	 *
	 * @param object $params
	 * @param object form model
	 * @returns bol
	 */

	function onLoad($params, &$formModel)
	{
		FabrikHelperHTML::addScriptDeclaration("window.addEvent('domready', function() {
		if(navigator.people) {
			navigator.people.find();
		}
	})");
	}

}
?>
<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin.php');
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'validation_rule.php');

class FabrikModelNotEmpty extends FabrikModelValidationRule {

	var $_pluginName = 'notempty';
	
	/** @param string classname used for formatting error messages generated by plugin */
	var $_className = 'notempty';

	/**
	 * validate the elements data against the rule
	 * @param string data to check
	 * @param object element
	 * @param int plugin sequence ref
	 * @param int repeat group count
	 * @return bol true if validation passes, false if fails
	 */

	function validate($data, &$element, $c, $repeat_count = 0)
	{
		$ok = $element->dataConsideredEmpty($data, $c);
		return !$ok;
	}

}
?>
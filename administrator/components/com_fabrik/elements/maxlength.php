<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'element.php');

/**
 * Renders a SQL element
 *
 * @author 		rob clayburn
 * @package 	fabrikar
 * @subpackage		Parameter
 * @since		1.5
 */

class JElementMaxlength extends JElement
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Width';

	function fetchElement($name, $value, &$node, $control_name)
	{
		if ($value == '0' || $value == '') { 
			$value = '255';
		}
		$fullName = ElementHelper::getFullName($this, $control_name, $name);
		return "<input onblur=\"setAll(this.value, '$fullName');\" class=\"inputbox\" type=\"text\" name=\"$fullName\" size=\"3\" value=\"$value\" />";
	}
}
?>
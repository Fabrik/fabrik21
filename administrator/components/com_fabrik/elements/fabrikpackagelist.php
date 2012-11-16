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
 * Renders a repeating drop down list of forms
 *
 * @author 		Rob Clayburn 
 * @package 	Joomla
 * @subpackage		Fabrik
 * @since		1.5
 */

class JElementFabrikpackagelist extends JElement
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Fabrikpackagelist';
	
	
	function fetchElement($name, $value, &$node, $control_name )
	{
		$db			= & JFactory::getDBO();
		$query = "SELECT id AS value, label AS `text` FROM #__fabrik_packages order by value DESC";
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		$id 			= ElementHelper::getId($this, $control_name, $name);
		$fullName = ElementHelper::getFullName($this, $control_name, $name);
		return JHTML::_('select.genericlist', $rows, $fullName, 'class="inputbox"  size="1"', 'value', 'text', $value); 
	}

}
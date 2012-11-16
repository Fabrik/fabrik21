<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/


// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * Renders a SQL element
 *
 * @author 		rob clayburn
 * @package 	fabrikar
 * @subpackage		Parameter
 * @since		1.5
 */

require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'element.php');

class JElementConnections extends JElement
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Connections';

	function fetchElement($name, $value, &$node, $control_name)
	{
		$db			= & JFactory::getDBO();
		if ($value == '') {
			$db->setQuery("SELECT id FROM #__fabrik_connections WHERE `default` = 1");
			$value = $db->loadResult();
		}
		$db->setQuery("SELECT id AS value, description AS text FROM #__fabrik_connections WHERE state = '1'");
		$cnns = array_merge( array(JHTML::_('select.option', '-1', JText::_('COM_FABRIK_PLEASE_SELECT'))), $db->loadObjectList());
		$js = "onchange=\"" . $node->attributes('js') . "\"";
		$id 			= ElementHelper::getId($this, $control_name, $name);
		$fullName = ElementHelper::getFullName($this, $control_name, $name);
		$return = JHTML::_('select.genericlist', $cnns , ''.$fullName, 'class="inputbox" ' . $js, 'value', 'text', $value, $id);
		$return .= "<img style='margin-left:10px;display:none' id='".$id."_loader' src='components/com_fabrik/images/ajax-loader.gif' alt='" . JText::_('LOADING'). "' />";
		return $return;
	}
}
?>
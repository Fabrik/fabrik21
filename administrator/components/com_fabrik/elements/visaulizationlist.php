<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'element.php');

/**
 * Renders a list of created visualizations
 *
 * @package 	Joomla
 * @subpackage	Articles
 * @since		1.5
 */

class JElementVisaulizationlist extends JElement
{
	/**
	 * Element name
	 * @access	protected
	 * @var		string
	 */

	var	$_name = 'Visaulizationlist';

	function fetchElement($name, $value, &$node, $control_name)
	{
		$a = array(JHTML::_('select.option', '', JText::_('COM_FABRIK_PLEASE_SELECT')));
		$db =& JFactory::getDBO();
		$group = $node->attributes('plugin');
		$db->setQuery("SELECT id AS value, label AS text FROM #__fabrik_visualizations WHERE state ='1' ORDER BY text");
		$elementstypes = $db->loadObjectList();
		$elementstypes = array_merge( $a, $elementstypes);
		$fullName = ElementHelper::getFullName($this, $control_name, $name);
		return JHTML::_('select.genericlist', $elementstypes, $fullName, 'class="inputbox elementtype"  size="1"', 'value', 'text', $value);
	}
}
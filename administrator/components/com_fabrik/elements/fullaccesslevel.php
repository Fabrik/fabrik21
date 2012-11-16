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
 * Renders a list of elements found in a fabrik table
 *
 * @package 	Joomla
 * @subpackage	Articles
 * @since		1.5
 */
class JElementFullaccesslevel extends JElement
{
	/**
	 * Element name
	 * @access	protected
	 * @var		string
	 */
	var	$_name = 'Fullaccesslevel';

	function fetchElement($name, $value, &$node, $control_name)
	{
		$version = new JVersion();
		if ($version->RELEASE == '1.6') {
			$acl 	=& JFactory::getACL();
			// @TODO for Joomla 1.6
			$gtree = JHTML::_('select.option', "-2", 'TODO FOR JOOMLA 1.6');
			return JHTML::_('select.genericlist',  array($gtree), $control_name.'['.$name.']', 'class="inputbox" size="1"', 'value', 'text', $value  );
			
		}else{
			
		
		if (defined('_JACL')) {
			$_JACL =& JACLPlus::getJACL();
			$where = "\n WHERE id IN (0,1,2)";
			$db =& JFactory::getDBO();
			if ($_JACL->enable_jaclplus) {
				$user =& JFactory::getUser();
				if(is_numeric($value)) $where = " OR id = '".(int) $value."'";
				else $where = "";
				switch ($_JACL->publish_alstype) {
					case "1" :
						$where = (( $user->get('gid')==25 ) ? "" : "\n WHERE id IN (".$user->get('jaclplus', '0').")".$where);
						break;
					case "2" :
						$where = (( $user->get('gid')==25 ) ? "" : "\n WHERE id NOT IN (0,1,2)".$where);
						break;
					case "3" :
						$where = (( $user->get('gid')==25 ) ? "" : "\n WHERE id IN (".$user->get('jaclplus', '0').") AND id NOT IN (0,1,2)".$where);
						break;
					case "4" :
						$where = (( $user->get('gid')==25 ) ? "" : "\n WHERE id IN (". $db->getEscaped( $_JACL->publish_jaclplus ) .")".$where);
						break;
					case "0" :
					default :
						$where = "";
						break;
				}
			}
			$query = 'SELECT id AS value, name AS text'
			. ' FROM #__groups'
			. $where
			. ' ORDER BY id'
			;
			$db->setQuery($query);
			$gtree = $db->loadObjectList();
				
		} else {
			$acl 	=& JFactory::getACL();
			$gtree = $acl->get_group_children_tree( null, 'USERS', false);
			$optAll = array(JHTML::_('select.option', '0', ' - Everyone'), JHTML::_('select.option', "26", 'Nobody'));
			$gtree = array_merge( $optAll, $gtree);
		}
		$fullName = ElementHelper::getFullName($this, $control_name, $name);
		$id = ElementHelper::getId($this, $control_name, $name);
		return JHTML::_('select.genericlist', $gtree, $fullName, 'class="inputbox fullaccesslevel" size="1"', 'value', 'text', $value, $id);
		}
	}
}
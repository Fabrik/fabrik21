<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

//required for menus
require_once(str_replace(DS.'administrator', '', JPATH_BASE).DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'params.php');
require_once(str_replace(DS.'administrator', '', JPATH_BASE).DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'string.php');
require_once(str_replace(DS.'administrator', '', JPATH_BASE).DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'html.php');
require_once(str_replace(DS.'administrator', '', JPATH_BASE).DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'parent.php');
require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'element.php');
/**
 * Renders a list of fabrik or db tables
 *
 * @author 		Rob Clayburn
 * @package 	Joomla
 * @subpackage		Fabrik
 * @since		1.5
 */

class JElementFabriktables extends JElement
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Fabriktables';

	var $_array_counter = null;

	function fetchElement($name, $value, &$node, $control_name )
	{
		static $fabriktables;
		if (!isset($fabriktables)) {
			$fabriktables = array();
		}
		FabrikHelperHTML::script('fabriktables.js', 'administrator/components/com_fabrik/elements/', true);
		$connectionDd = $node->attributes('observe', '');
		$db			= & JFactory::getDBO();
		$document =& JFactory::getDocument();
		$c 				= ElementHelper::getRepeatCounter($this);
		$repeat 	= ElementHelper::getRepeat($this);
		$id 			= ElementHelper::getId($this, $control_name, $name);
		$fullName = ElementHelper::getFullName($this, $control_name, $name);

		if ($connectionDd == '') {
			//we are not monitoring a connection drop down so load in all tables
			$query = "SELECT id AS value, label AS `$name` FROM #__fabrik_tables order by label ASC";
			$db->setQuery($query);
			$rows = $db->loadObjectList();
		} else {
			$rows = array(JHTML::_('select.option', '', JText::_('SELECT A CONNECTION FIRST'), 'value', $name));
		}

		if ($connectionDd != '' && !array_key_exists($id, $fabriktables)) {

			$connectionDd = ($c === false || $node->attributes('connection_in_repeat') == 'false') ?  $connectionDd :  $connectionDd . '-' . $c;
			$opts = new stdClass();
			$opts->livesite = COM_FABRIK_LIVESITE;
			$opts->conn = 'params' . $connectionDd;
			$opts->value = $value;
			$opts->connInRepeat = $node->attributes('connection_in_repeat');
			$opts = FastJSON::encode( $opts);

			$script = "window.addEvent('domready', function() {\n";
			$script .= "var p = new fabriktablesElement('$id', $opts);\n";
			$script .= "tableElements.set('$id', p);\n";
			$script .= "Fabrik.adminElements.set('$id', p);\n";
			$script .="});\n";

			$document->addScriptDeclaration($script);
			$fabriktables[$id] = true;

		}

		FabrikHelperHTML::cleanMootools();
		$str = JHTML::_('select.genericlist', $rows, $fullName, 'class="inputbox fabriktables"', 'value', $name, $value, $id);
		$str .= "<img style=\"margin-left:10px;display:none\" id=\"".$id."_loader\" src=\"components/com_fabrik/images/ajax-loader.gif\" alt=\"" . JText::_('LOADING'). "\" />";
		return $str;
	}

}
<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'element.php');

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * Renders a text element
 *
 * @author 		Johan Janssens <johan.janssens@joomla.org>
 * @package 	Joomla.Framework
 * @subpackage		Parameter
 * @since		1.5
 */

class JElementText extends JElement
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var	$_name = 'Text';

	var $_array_counter = null;

	function fetchElement($name, $value, &$node, $control_name)
	{
		$id 			= ElementHelper::getId($this, $control_name, $name);
		$fullName = ElementHelper::getFullName($this, $control_name, $name);
		//orig J stuff

		$size = ( $node->attributes('size') ? 'size="'.$node->attributes('size').'"' : '');
		$class = ( $node->attributes('class') ? 'class="'.$node->attributes('class').'"' : 'class="text_area"');
		/*
		 * Required to avoid a cycle of encoding &
		 * html_entity_decode was used in place of htmlspecialchars_decode because
		 * htmlspecialchars_decode is not compatible with PHP 4
		 */
		if (is_string($value)) {
			$value = htmlspecialchars( html_entity_decode($value, ENT_QUOTES ), ENT_QUOTES);
		}
		return '<input type="text" name="'.$fullName.'" id="'.$id.'" value="'.$value.'" '.$class.' '.$size.' />';
	}
}

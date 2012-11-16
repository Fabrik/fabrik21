<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Renders a list of elements found in a fabrik table
 *
 * @package 	Joomla
 * @subpackage	Articles
 * @since		1.5
 */
class JElementUpload extends JElement
{
	/**
	 * Element name
	 * @access	protected
	 * @var		string
	 */
	var	$_name = 'Upload';

	function fetchElement($name, $value, &$node, $control_name)
	{
			return '<input type="file" name="'.$control_name.'['.$name.']" id="'.$control_name.$name.'" />';
	}
}
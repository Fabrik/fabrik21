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
 * Renders a upload size field
 *
 * @package 	Joomla.Framework
 * @subpackage		Parameter
 * @since		1.5
 */

class JElementUploadsize extends JElement
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Uploadsize';

	// $$$ hugh - ini settings can be in K, M or G
	function _return_bytes($val)
	{
		$val = trim($val);
		$last = strtolower(substr($val, -1));
	   
		if($last == 'g')
			$val = $val*1024*1024*1024;
		if($last == 'm')
			$val = $val*1024*1024;
		if($last == 'k')
			$val = $val*1024;
		   
		return $val;
	}
	
	function fetchElement($name, $value, &$node, $control_name)
	{
		$id 			= ElementHelper::getId($this, $control_name, $name);
		$fullName = ElementHelper::getFullName($this, $control_name, $name);
		$size = $node->attributes('size') ? 'size="'.$node->attributes('size').'"' : '';
		$class = $node->attributes('class') ? 'class="'.$node->attributes('class').'"' : 'class="text_area"';
		$value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES), ENT_QUOTES);
		if ($value == '') {
			$value = $this->getMax();
		}
		return '<input type="text" name="'.$fullName.'" id="'.$id.'" value="'.$value.'" '.$class.' '.$size.' />';
	}
	
	function fetchTooltip($label, $description, &$xmlElement, $control_name='', $name='')
	{
		$id 			= ElementHelper::getId($this, $control_name, $name);
		$output = '<label id="'.$id.'-lbl" for="'.$id.'"';
		if ($description) {
			$description = JText::_($description). $this->getMax().'Kb';
			$output .= ' class="hasTip" title="'.JText::_($label).'::'.$description.'">';
		} else {
			$output .= '>';
		}
		$output .= JText::_($label ).'</label>';

		return $output;
	}
	
	/**
	 * get the max upload size allowed by the server.
	 * @return int kilobyte upload size
	 */
	
	function getMax()
	{
		$post_value 	= $this->_return_bytes( ini_get('post_max_size'));
		$upload_value 	= $this->_return_bytes( ini_get('upload_max_filesize'));
		$value = min( $post_value, $upload_value);
		$value = $value / 1024;
		return $value;
	}
}
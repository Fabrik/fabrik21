<?php

/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/


// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik Component Helper
 *
 * @static
 * @package		Joomla
 * @subpackage	Fabrik
 * @since 1.5
 */
class FabrikHelper
{
	
	/**
	 * prepare the date for saving
	 * DATES SHOULD BE SAVED AS UTC
	 * @param string publish down date
	 */

	function prepareSaveDate(&$strdate)
	{
		$config =& JFactory::getConfig();
		$tzoffset = $config->getValue('config.offset');
		$db =& JFactory::getDBO();
		// Handle never unpublish date
		if (trim($strdate) == JText::_('Never') || trim($strdate) == '' || trim($strdate) == $db->getNullDate())
		{
			$strdate = $db->getNullDate();
		}
		else
		{
			if (strlen(trim($strdate)) <= 10) {
				$strdate .= ' 00:00:00';
			}
			$date =& JFactory::getDate($strdate, $tzoffset);
			$strdate = $date->toMySQL();
		}
	}
}
?>
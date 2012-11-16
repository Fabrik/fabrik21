<?php

/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

function com_uninstall()
{
	jimport('joomla.filesystem.folder');
	jimport('joomla.filesystem.file');
	// remove all language files
	$files = JFolder::files(JPATH_SITE.DS.'administrator'.DS.'language', 'fabrik', true, true);
	foreach ($files as $file) {
		if (JFile::exists($file)) {
			//JFile::delete($file);
		}
	}
	if (JFolder::exists(JPATH_SITE.DS.'media'.'com_fabrik')) {
	JFolder::delete(JPATH_SITE.DS.'media'.'com_fabrik');
	}
	if (JFolder::exists(JPATH_SITE.DS.'libraries'.DS.'joomla'.DS.'document'.DS.'fabrikfeed')) {
		JFolder::delete(JPATH_SITE.DS.'libraries'.DS.'joomla'.DS.'document'.DS.'fabrikfeed');
	}
  //echo JText::_("FABRIK_UNISTALLED");
} ?>
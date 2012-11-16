<?php
/**
 * @package		Joomla
 * @subpackage	Fabik
 * @copyright	Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license		GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
$defines = JFile::exists(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php') ? JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php' : JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'defines.php';
require_once($defines);
$contentPlugin = JPATH_SITE.DS.'plugins'.DS.'content'.DS.'fabrik.php';
if (!JFile::exists($contentPlugin)) {
	JError::raiseNotice(500, 'To use the Joomla community fabrik plugin, you must first install and publish Fabrik\'s Joomla content plugin');
	return;
}
require_once($contentPlugin);

jimport( 'joomla.plugin.plugin' );

/**
 * Fabrik content plugin - renders forms and tables
 *
 * @package		Joomla
 * @subpackage	Content
 * @since 		1.5
 */

class jsFabrik extends plgContentFabrik
{
	function jsFabrik()
	{
		// need to have this func so the default plgContentFabrik constructor doesn't run
	}

	function jsFabrikRender($match)
	{
		return $this->replace($match);
	}
}

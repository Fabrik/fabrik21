<?php
/**
 * @package		Joomla
 * @subpackage Thatsmymouse
 * @copyright	Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 * Joomla! thatsmymouse plugin
 *
 * @author		Rob Clayburn <rob@pollen-8.co.uk>
 * @package		Joomla
 * @subpackage	fabrik
 */
class plgSystemFabrik_removemootools11 extends JPlugin
{

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @access	protected
	 * @param	object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since	1.0
	 */
	function plgFabrik_removemootools11(& $subject, $config)
	{
		parent::__construct($subject, $config);

	}

	function onAfterRender()
	{
		$defines = JFile::exists(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php') ? JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php' : JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'defines.php';
		require_once( $defines );
		require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );
		FabrikHelperHTML::cleanMootools();
	}

}
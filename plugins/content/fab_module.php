<?php
/**
 * @package		Joomla
 * @subpackage	Fabik
 * @copyright	Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license		GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die();

jimport( 'joomla.plugin.plugin' );

/**
 * Fabrik content plugin - renders forms and tables
 *
 * @package		Joomla
 * @subpackage	Content
 * @since 		1.5
 */

class plgContentFab_module extends JPlugin
{

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param object $params  The object that holds the plugin parameters
	 * @since 1.5
	 */

	function plgContentFab_module(&$subject, $params = null)
	{
		parent::__construct($subject, $params);
	}

	/**
	 * Example prepare content method
	 *
	 * Method is called by the view
	 *
	 * @param 	object		The article object.  Note $article->text is also available
	 * @param 	object		The article params
	 * @param 	int			The 'page' number
	 */

	function onPrepareContent(&$article, &$params, $limitstart=0)
	{
		// Get plugin info
		$plugin =& JPluginHelper::getPlugin( 'content', 'fab_module' );
		// $$$ hugh had to rename this, it was stomping on com_content and friends $params
		// $$$ which is passed by reference to us!
		$fparams = new JParameter( $plugin->params );

		$botRegex = "fab_module";
		// simple performance check to determine whether bot should process further
		$module = $fparams->get( 'module', '' );

		if (JString::strpos( $article->text, "$botRegex") === false) {
			return true;
		}
		$regex = "/{" .$botRegex ."\s*.*?}/i";
		$res = preg_replace_callback( $regex, array($this, 'replace'), $article->text );
		if (!JError::isError( $res )) {
			$article->text = $res;
		}

	}

	/**
	 * the function called from the preg_replace_callback - replace the {} with the correct html
	 *
	 * @param string plug-in match
	 * @return unknown
	 */

	function replace($match)
	{
		$match = $match[0];
		$match = trim( $match, "{" );
		$match = trim( $match, "}" );
		$match = explode( " ", $match );
		array_shift( $match );
		foreach ($match as $m) {
			$m = explode( "=", $m );
			if ($m[0] == 'module') {
				$module = trim($m[1]);
			}
		}
		$user	=& JFactory::getUser();
		$aid	= $user->get('aid', 0);
		$app =& JFactory::getApplication();
		$db =& JFactory::getDBO();

				$query = 'SELECT id, title, module, position, content, showtitle, control, params, 0 AS  user'
			. ' FROM #__modules AS m'
			. ' LEFT JOIN #__modules_menu AS mm ON mm.moduleid = m.id'
			. ' WHERE m.published = 1'
			. ' AND m.access <= '. (int)$aid
			. ' AND m.client_id = '. (int)$app->getClientId()
			. ' AND id = '.(int)$module
			. ' ORDER BY position, ordering';

		$db->setQuery($query);
		$row = $db->loadObject();
		jimport('joomla.application.module.helper');
		return JModuleHelper::renderModule($row);
	}


}
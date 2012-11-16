<?php

/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');
//just until joomla uses mootools 1.2
jimport('joomla.html.editor');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'editor.php');
//end mootools 1.2

/**
 * Fabrik Component Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Contact
 * @since 1.5
 */


//$$$rob DEPRECIATED - should always get directed to specific controller

class FabrikController extends JController
{

	var $_isMambot = false;

	/**
	 * Display the view
	 */
	function display()
	{
		//menu links use fabriklayout parameters rather than layout
		$flayout = JRequest::getVar('fabriklayout');
		if ($flayout != '') {
			JRequest::setVar('layout', $flayout);
		}
		$document =& JFactory::getDocument();

		$viewName	= JRequest::getVar('view', 'form', 'default', 'cmd');
		$modelName = $viewName;
		if ($viewName == 'emailform') {
			$modelName = 'form';
		}

		if ($viewName == 'details') {
			//huh why was this here? - stopped detailed view from ever ever being loaded
			//JRequest::setVar('view', 'form');
			$viewName = 'form';
			$modelName = 'form';
		}

		$viewType	= $document->getType();
		// Set the default view name from the Request
		$view = &$this->getView($viewName, $viewType);

		// Push a model into the view
		$model	= &$this->getModel( $modelName);
		if (!JError::isError($model) && is_object($model)) {
			$view->setModel( $model, true);
		}

		// Display the view
		$view->assign('error', $this->getError());
		$cachable = false;
		if(($viewName = 'form' || $viewName = 'details')) {
			$cachable = true;
		}
		$user =& JFactory::getUser();

		if ($viewType != 'feed' && !$this->_isMambot && $user->get('id') == 0) {
			//$cache =& JFactory::getCache('com_fabrik', 'view');
			//$cache->get($view, 'display');

				// Workaround for token caching
			ob_start();

			$option = JRequest::getCmd('option');
			$cache =& JFactory::getCache('com_fabrik', 'view');
			$cache->get($view, 'display');

			// Workaround for token caching
			$contents = ob_get_contents();

			ob_end_clean();

			$token			= JUtility::getToken();
			$search 		= '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
			$replacement 	= '<input type="hidden" name="'.$token.'" value="1" />';

			$contents = preg_replace($search, $replacement, $contents);
			echo $contents;
		} else {
			return $view->display();
		}
	}

}
?>
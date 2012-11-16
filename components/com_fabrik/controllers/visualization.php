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

/**
 * Contact Component Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Contact
 * @since 1.5
 */
class FabrikControllerVisualization extends JController
{

	/* @var int  id used from content plugin when caching turned on to ensure correct element rendered)*/
	var $cacheId = 0;

	/**
	 * Display the view
	 */
	function display()
	{
		$document =& JFactory::getDocument();

		$viewName = str_replace('FabrikControllerVisualization', '', get_class($this));

		$viewType	= $document->getType();

		// Set the default view name from the Request
		$view = &$this->getView($viewName, $viewType);

		//create a form view as well to render the add event form.
		$view->_formView = &$this->getView('Form', $viewType);

		$formModel =& $this->getModel('Form');
		$view->_formView->setModel($formModel, true);

		// Push a model into the view
		$model	= &$this->getModel($viewName);

		if (!JError::isError($model)) {
			//$model->setAdmin( false);
			$view->setModel($model, true);
		}
		// Display the view
		$view->assign('error', $this->getError());

		$post = JRequest::get('post');
		//build unique cache id on url, post and user id
		$user =& JFactory::getUser();
		$cacheid = serialize(array(JRequest::getURI(), $post, $user->get('id'), get_class($view), 'display', $this->cacheId));
		$cache =& JFactory::getCache('com_fabrik', 'view');
		echo $cache->get($view, 'display', $cacheid);
	}

}
?>
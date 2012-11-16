<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005-2011 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php');

/**
 * Fabrik Approvals Plug-in Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Fabrik
 * @since 1.5
 */
class FabrikControllerVisualizationapprovals extends JController
{
	/**
	 * Display the view
	 */
	function display()
	{
		$document =& JFactory::getDocument();

		$viewName = 'approvals';

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
			$view->setModel($model, true);
		}
		// Display the view
		$view->assign('error', $this->getError());
		return $view->display();
	}

}
?>

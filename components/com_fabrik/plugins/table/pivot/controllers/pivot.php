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
 * Pivot table plug-in Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Contact
 * @since 1.5
 */
class FabrikControllerTablepivot extends JController
{
	/** @var string path of uploaded file */
	var $filepath = null;

	/**
	 * default display mode
	 *
	 * @return unknown
	 */

	function display()
	{
		echo "display";
	}

	/**
	 * set up the popup window containing the form to create the
	 * email message
	 *
	 * @return string html
	 */

	function popupwin()
	{
		$document =& JFactory::getDocument();
		$viewName = 'pivotpopupwin';

		$viewType	= $document->getType();

		// Set the default view name from the Request
		$view = &$this->getView($viewName, $viewType);

		$tableModel =& $this->getModel('Table');
		$tableModel->setId(JRequest::getVar('id', 0));
		$formModel =& $tableModel->getForm();
		// Push a model into the view
		$model	= &$this->getModel('pivot');
		$model->formModel = $formModel;
		if (!JError::isError($model)) {
			$view->setModel($model, true);
		}
		$view->setModel($tableModel);
		$view->setModel($formModel);
		$pluginManager =& $this->getModel('PluginManager');
		$pluginManager->_loadPaths('table', 'pivot');

		// Display the view
		$view->assign('error', $this->getError());
		return $view->display();
	}

	/**
	 * send the emails
	 */

	function doemail()
	{
		$model	= &$this->getModel('emailtable');
		$tableModel 	=& $this->getModel('Table');
		$model->doEmail($tableModel);
	}

}
?>
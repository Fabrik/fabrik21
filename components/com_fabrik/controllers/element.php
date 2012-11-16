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
 * Fabrik From Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Fabrik
 * @since 1.5
 */
class FabrikControllerElement extends JController
{

	var $_isMambot = false;

	var $mode = false;

	/* @var int  id used from content plugin when caching turned on to ensure correct element rendered)*/
	var $cacheId = 0;

	/**
	 * Display the view
	 */

	function display()
	{
		$document =& JFactory::getDocument();

		$viewName	= JRequest::getVar('view', 'element', 'default', 'cmd');
		$modelName = $viewName;

		$viewType	= $document->getType();
		// Set the default view name from the Request
		$view = &$this->getView($viewName, $viewType);

		// Push a model into the view
		$model	= &$this->getModel($modelName);
		$model->_editable = ($this->mode == 'readonly') ? false : true;

		if (!JError::isError($model) && is_object($model)) {

			$view->setModel($model, true);
		}

		// Display the view
		$view->assign('error', $this->getError());

		return $view->display();
	}

	/**
	 * save an individual element value to the fabrik db
	 * used in inline edit table plguin
	 */

	function save()
	{
		$tableModel =& $this->getModel('table');
		$tableModel->setId(JRequest::getInt('tableid'));
		$rowId = JRequest::getVar('rowid');
		$key = JRequest::getVar('element');
		$key = array_pop(explode("___", $key));
		$value = JRequest::getVar('value');
		$tableModel->storeCell($rowId, $key, $value);
		$this->mode = 'readonly';
		$this->display();
	}

}
?>
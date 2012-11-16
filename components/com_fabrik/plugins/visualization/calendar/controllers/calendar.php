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

/**
 * Fabrik Calendar Plug-in Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Fabrik
 * @since 1.5
 */

class FabrikControllerVisualizationcalendar extends FabrikControllerVisualization
{

	function deleteEvent()
	{
		$model = &$this->getModel('calendar');
		$model->deleteEvent();
		$this->getEvents();
	}

	function getEvents()
	{
		$viewName = 'calendar';
		$usersConfig = &JComponentHelper::getParams('com_fabrik');
		$model	= &$this->getModel($viewName);
		$id = JRequest::getInt('id', $usersConfig->get('visualizationid', JRequest::getInt('visualizationid', 0)), 'get');
		$model->setId($id);
		echo $model->getEvents();
	}

	function chooseaddevent()
	{
		$document =& JFactory::getDocument();
		$viewName = 'calendar';

		$viewType	= $document->getType();
		$formModel =& $this->getModel('Form');

		// Push a model into the view
		$model	= &$this->getModel($viewName);

		// Set the default view name from the Request
		$view = &$this->getView($viewName, $viewType);
		$view->setModel($formModel);
		$view->setModel($model, true);
		$view->chooseaddevent();
	}

	function addEvForm()
	{
		$tableid = JRequest::getInt('tableid');
		$viewName = 'calendar';
		$usersConfig = &JComponentHelper::getParams('com_fabrik');
		$model	= &$this->getModel($viewName);
		$model->setId(JRequest::getVar('id', $usersConfig->get('visualizationid', JRequest::getInt('visualizationid', 0))));
		$model->setupEvents();
		if (array_key_exists($tableid, $model->_events)) {
			$datefield = $model->_events[$tableid][0]['startdate'];
		} else {
			$config =& JFactory::getConfig();
			$prefix = $config->getValue('config.dbprefix');
			$datefield = $prefix.'fabrik_calendar_events___start_date';
		}
		$rowid = JRequest::getInt('rowid');
		$tableModel =& JModel::getInstance('Table', 'FabrikModel');
		$tableModel->setId($tableid);
		$table =& $tableModel->getTable();
		JRequest::setVar('view', 'form');
		JRequest::setVar('fabrik', $table->form_id);
		JRequest::setVar('tmpl', 'component');
		JRequest::setVar('_postMethod', 'ajax');
		$link = 'index.php?option=com_fabrik&view=form&fabrik='.$table->form_id.'&rowid='.$rowid.'&tmpl=component&_postMethod=ajax';
		$link .= '&jos_fabrik_calendar_events___visualization_id=' . JRequest::getInt('jos_fabrik_calendar_events___visualization_id');
		$start_date = JRequest::getVar('start_date', '');
		if (!empty($start_date)) {
			$link .= "&$datefield=".JRequest::getVar('start_date');
		}
		// $$$ rob have to add this to stop the calendar filtering itself after adding an new event?
		$link .= '&clearfilters=1';
		$this->setRedirect($link);
	}
}
?>

<?php
/**
 * Fabrik Import Controller
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Pollen 8 Design Ltd. All rights reserved.
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


class FabrikControllerImport extends JController
{
	/**
	 * Display the view
	 */

	function display()
	{
		$this->tableid = JRequest::getVar('tableid', 0);
		$tableModel =& $this->getModel('table');
		$tableModel->setId($this->tableid);
		$this->table =& $tableModel->getTable();
		$document =& JFactory::getDocument();
		$viewName	= JRequest::getVar('view', 'form', 'default', 'cmd');
		$viewType	= $document->getType();
		// Set the default view name from the Request
		$view = &$this->getView($viewName, $viewType);
		$view->display();
	}

	function doimport()
	{
		$model = &$this->getModel('Importcsv');
		$tableModel =& $model->getTableModel();
		$table =& $tableModel->getTable();

		if (!$tableModel->canCSVImport()) {
			JError::raiseError(400, 'Naughty naughty!');
			jexit;
		}

		$tmp_file = $model->checkUpload();
		if ($tmp_file === false) {
			$this->display();
		}

		$model->readCSV($tmp_file);

		$model->findExistingElements();

		$document =& JFactory::getDocument();
		$viewName	= JRequest::getVar('view', 'form', 'default', 'cmd');
		$viewType	= $document->getType();
		// Set the default view name from the Request
		$view = &$this->getView($viewName, $viewType);
		$Itemid = JRequest::getInt('Itemid');
		if (!empty($model->newHeadings)) {
			//as opposed to admin you can't alter table structure with a CSV import
			//from the front end
			JError::raiseNotice(500, $model->_makeError());
			$this->setRedirect("index.php?option=com_fabrik&c=import&view=import&fietype=csv&tableid=" . $table->id."&Itemid=".$Itemid);
		} else {
			JRequest::setVar('fabrik_table', $table->id);
			$msg = $model->makeTableFromCSV();
			$this->setRedirect('index.php?option=com_fabrik&view=table&tableid='.$table->id."&Itemid=".$Itemid, $msg);
		}
	}

}
?>